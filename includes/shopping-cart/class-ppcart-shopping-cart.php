<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Lines and meta of one cart, in memory. Callers outside the module use the
 * public functions and PPCart_Shopping_Cart_Store::update(), never this class.
 */
final class PPCart_Shopping_Cart
{
    private const MAX_NOTICES = 50;

    /** @var array<string,array{product_id:int,plan_id:string,quantity:int,seen_price:string,added_at:string}> */
    private array $lines = [];

    /** @var array<string,array> Catalog result for each line, filled by repair(). */
    private array $resolved = [];

    private bool $repaired = false;

    private bool $changed = false;

    public function __construct(
        private PPCart_Shopping_Cart_Catalog $catalog,
        array $lines = [],
        private array $meta = [],
        private int $cart_id = 0,
        private int $revision = 0
    ) {
        foreach ($lines as $line) {
            $product_id = absint($line['product_id'] ?? 0);
            $plan_id = is_scalar($line['plan_id'] ?? null) ? (string) $line['plan_id'] : '';
            if (! $product_id || '' === $plan_id) {
                continue;
            }
            $this->lines[self::key($product_id, $plan_id)] = [
                'product_id' => $product_id,
                'plan_id'    => $plan_id,
                'quantity'   => max(1, (int) ($line['quantity'] ?? 1)),
                'seen_price' => (string) ($line['seen_price'] ?? ''),
                'added_at'   => (string) ($line['added_at'] ?? ''),
            ];
        }
    }

    public static function key(int $product_id, string $plan_id): string
    {
        return $product_id . ':' . $plan_id;
    }

    public function add_item(int $product_id, string $plan_id): true|WP_Error
    {
        if (! (new PPCart_Shopping_Cart_Settings())->enabled()) {
            return new WP_Error('ppcart_cart_disabled', __('The shopping cart is not available.', 'publishpress-cart'));
        }
        $this->repair();
        $resolved = $this->catalog->resolve_line($product_id, $plan_id);
        if (is_wp_error($resolved)) {
            return $resolved;
        }
        $key = self::key($product_id, $plan_id);
        if (isset($this->lines[$key])) {
            return new WP_Error('ppcart_cart_already_in_cart', __('This item is already in your cart.', 'publishpress-cart'));
        }
        $max_items = max(1, (int) apply_filters('ppcart_shopping_cart_max_items', 50));
        if (count($this->lines) >= $max_items) {
            return new WP_Error('ppcart_cart_full', __('Your cart is full.', 'publishpress-cart'));
        }
        $this->lines[$key] = [
            'product_id' => $product_id,
            'plan_id'    => $plan_id,
            'quantity'   => 1,
            'seen_price' => PPCart_Shopping_Cart_Priced::decimal($resolved['unit_price']),
            'added_at'   => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.u\Z'),
        ];
        $this->resolved[$key] = $resolved;
        $this->drop_notices(static fn (array $notice): bool => $key === $notice['key']);
        return true;
    }

    public function remove_item(string $key): bool
    {
        if (! isset($this->lines[$key])) {
            return false;
        }
        unset($this->lines[$key], $this->resolved[$key]);
        $this->drop_notices(static fn (array $notice): bool => $key === $notice['key']);
        return true;
    }

    public function set_quantity(string $key, int $quantity): true|WP_Error
    {
        $this->repair();
        if (! isset($this->lines[$key])) {
            return new WP_Error('ppcart_cart_item_missing', __('This item is not in your cart.', 'publishpress-cart'));
        }
        $line = $this->lines[$key];
        if ($quantity < 1 || $quantity > $this->catalog->max_quantity($line['product_id'], $line['plan_id'])) {
            return new WP_Error('ppcart_cart_quantity_invalid', __('This quantity is not available.', 'publishpress-cart'));
        }
        $this->lines[$key]['quantity'] = $quantity;
        return true;
    }

    public function set_meta(string $key, mixed $value): true|WP_Error
    {
        if ('' === $key || str_starts_with($key, '_')) {
            return new WP_Error('ppcart_cart_meta_reserved', __('This cart meta key is reserved.', 'publishpress-cart'));
        }
        if (null === $value) {
            unset($this->meta[$key]);
        } else {
            $this->meta[$key] = $value;
        }
        return true;
    }

    /**
     * @param array{lines:list<array>,meta:array<string,mixed>} $other export() of the other cart.
     */
    public function merge(array $other): true
    {
        $max_items = max(1, (int) apply_filters('ppcart_shopping_cart_max_items', 50));
        foreach ((new self($this->catalog, $other['lines']))->lines as $key => $line) {
            if (! isset($this->lines[$key]) && count($this->lines) < $max_items) {
                $this->lines[$key] = $line;
                $this->repaired = false;
            }
        }
        foreach ($other['meta'] as $key => $value) {
            if (! str_starts_with((string) $key, '_') && ! array_key_exists($key, $this->meta)) {
                $this->meta[$key] = $value;
            }
        }
        return true;
    }

    /**
     * @param list<array{key:string,added_at:string}> $paid
     */
    public function remove_paid_lines(array $paid): true
    {
        $removed = [];
        foreach ($paid as $line) {
            $key = (string) ($line['key'] ?? '');
            if (isset($this->lines[$key]) && $this->lines[$key]['added_at'] === (string) ($line['added_at'] ?? '')) {
                unset($this->lines[$key], $this->resolved[$key]);
                $removed[$key] = true;
            }
        }
        if ($removed) {
            $this->drop_notices(static fn (array $notice): bool => 'removed' === $notice['code'] || isset($removed[$notice['key']]));
        }
        return true;
    }

    public function validate(): PPCart_Shopping_Cart_Priced
    {
        $this->repair();
        $lines = [];
        foreach ($this->lines as $key => $line) {
            $resolved = $this->resolved[$key];
            $lines[] = new PPCart_Shopping_Cart_Priced_Line(
                $key,
                $line['product_id'],
                $line['plan_id'],
                $line['quantity'],
                $this->catalog->max_quantity($line['product_id'], $line['plan_id']),
                $resolved['unit_price'],
                round($resolved['unit_price'] * $line['quantity'], PPCart_Shopping_Cart_Priced::decimals()),
                $resolved['product_name'],
                $resolved['plan_name'],
                $resolved['image_id'],
                $resolved['permalink'],
                $line['added_at'],
            );
        }
        return new PPCart_Shopping_Cart_Priced(
            $this->cart_id,
            $this->revision,
            $lines,
            array_map(static fn (array $notice): PPCart_Shopping_Cart_Notice => new PPCart_Shopping_Cart_Notice(...$notice), $this->notices()),
            array_filter($this->meta, static fn ($key): bool => ! str_starts_with((string) $key, '_'), ARRAY_FILTER_USE_KEY),
            $this->changed,
            PPCart_Shopping_Cart_Priced::store_currency(),
        );
    }

    /** @return array{lines:list<array>,meta:array<string,mixed>} */
    public function export(): array
    {
        return ['lines' => array_values($this->lines), 'meta' => $this->meta];
    }

    private function repair(): void
    {
        if ($this->repaired) {
            return;
        }
        $this->repaired = true;
        foreach ($this->lines as $key => $line) {
            if (isset($this->resolved[$key])) {
                continue;
            }
            $resolved = $this->catalog->resolve_line($line['product_id'], $line['plan_id']);
            if (is_wp_error($resolved)) {
                unset($this->lines[$key]);
                $name = get_the_title($line['product_id']);
                $message = __('An item was removed from your cart because it is no longer available.', 'publishpress-cart');
                if ($name) {
                    /* translators: %s: product name. */
                    $message = sprintf(__('%s was removed from your cart because it is no longer available.', 'publishpress-cart'), $name);
                }
                $this->add_notice('removed', $key, $message);
                $this->changed = true;
                continue;
            }
            $this->resolved[$key] = $resolved;
            $price = PPCart_Shopping_Cart_Priced::decimal($resolved['unit_price']);
            if ($price !== $line['seen_price']) {
                /* translators: 1: product name, 2: old price, 3: new price. */
                $message = sprintf(__('The price of %1$s changed from %2$s to %3$s.', 'publishpress-cart'), $resolved['product_name'], ppcart_format_price($line['seen_price'], false), ppcart_format_price($price, false));
                $this->add_notice('price_changed', $key, $message, $line['seen_price'], $price);
                $this->lines[$key]['seen_price'] = $price;
                $this->changed = true;
            }
            $max_quantity = $this->catalog->max_quantity($line['product_id'], $line['plan_id']);
            if ($line['quantity'] > $max_quantity) {
                $this->lines[$key]['quantity'] = $max_quantity;
                $this->changed = true;
            }
        }
    }

    /** @return list<array{code:string,key:string,message:string,old_price:?string,new_price:?string}> */
    private function notices(): array
    {
        $notices = [];
        foreach ((array) ($this->meta['_notices'] ?? []) as $notice) {
            if (is_array($notice) && isset($notice['code'], $notice['key'], $notice['message'])) {
                $notices[] = [
                    'code'      => (string) $notice['code'],
                    'key'       => (string) $notice['key'],
                    'message'   => (string) $notice['message'],
                    'old_price' => isset($notice['old_price']) ? (string) $notice['old_price'] : null,
                    'new_price' => isset($notice['new_price']) ? (string) $notice['new_price'] : null,
                ];
            }
        }
        return $notices;
    }

    private function add_notice(string $code, string $key, string $message, ?string $old_price = null, ?string $new_price = null): void
    {
        $notice = compact('code', 'key', 'message', 'old_price', 'new_price');
        $notices = array_filter(
            $this->notices(),
            static fn (array $stored): bool => [$stored['code'], $stored['key'], $stored['old_price'], $stored['new_price']] !== [$code, $key, $old_price, $new_price]
        );
        $notices[] = $notice;
        $this->meta['_notices'] = array_slice(array_values($notices), -self::MAX_NOTICES);
    }

    /** @param callable(array):bool $matches */
    private function drop_notices(callable $matches): void
    {
        $notices = array_values(array_filter($this->notices(), static fn (array $notice): bool => ! $matches($notice)));
        if ($notices) {
            $this->meta['_notices'] = $notices;
        } else {
            unset($this->meta['_notices']);
        }
    }
}
