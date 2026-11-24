<?php

if (! defined('ABSPATH')) {
    exit;
}

final readonly class PPCart_Shopping_Cart_Priced
{
    /**
     * @param list<PPCart_Shopping_Cart_Priced_Line> $lines
     * @param list<PPCart_Shopping_Cart_Notice>      $notices
     * @param array<string,mixed>                    $meta
     */
    public function __construct(
        public int $cart_id,
        public int $revision,
        public array $lines,
        public array $notices,
        public array $meta,
        public bool $changed,
        public string $currency,
    ) {
    }

    public static function empty(): self
    {
        return new self(0, 0, [], [], [], false, self::store_currency());
    }

    public static function store_currency(): string
    {
        return (string) (get_option('_ppcart_currency') ?: 'USD');
    }

    public static function decimals(): int
    {
        $decimals = get_option('_ppcart_decimal_number');
        return '0' === $decimals || ! empty($decimals) ? (int) $decimals : 2;
    }

    public static function decimal(float $amount): string
    {
        return number_format($amount, self::decimals(), '.', '');
    }

    /** The Store stamps the row identity after its write. Prices stay those of validate(). */
    public function committed_as(int $cart_id, int $revision): self
    {
        return new self($cart_id, $revision, $this->lines, $this->notices, $this->meta, $this->changed, $this->currency);
    }

    public function count(): int
    {
        return count($this->lines);
    }

    public function is_empty(): bool
    {
        return [] === $this->lines;
    }

    public function has_line(string $key): bool
    {
        foreach ($this->lines as $line) {
            if ($key === $line->key) {
                return true;
            }
        }
        return false;
    }

    public function subtotal(): float
    {
        return round(array_sum(array_map(static fn (PPCart_Shopping_Cart_Priced_Line $line): float => $line->subtotal, $this->lines)), self::decimals());
    }

    public function meta(string $key): mixed
    {
        return $this->meta[$key] ?? null;
    }

    /** @return list<array{key:string,added_at:string}> */
    public function paid_lines(): array
    {
        return array_map(static fn (PPCart_Shopping_Cart_Priced_Line $line): array => ['key' => $line->key, 'added_at' => $line->added_at], $this->lines);
    }

    /**
     * Display totals. The charged amount comes from the order, never from here.
     *
     * @return array{subtotal:float,total:float,rows:list<array{type:string,label:string,amount:float}>}
     */
    public function get_totals(): array
    {
        $subtotal = $this->subtotal();
        $totals = [
            'subtotal' => $subtotal,
            'total'    => $subtotal,
            'rows'     => [
                ['type' => 'subtotal', 'label' => __('Subtotal', 'publishpress-cart'), 'amount' => $subtotal],
                ['type' => 'total', 'label' => __('Total', 'publishpress-cart'), 'amount' => $subtotal],
            ],
        ];
        return (array) apply_filters('ppcart_shopping_cart_totals', $totals, $this);
    }

    public function ready_to_pay(int $seen_revision, array $customer): true|WP_Error
    {
        if ($this->is_empty()) {
            return new WP_Error('ppcart_cart_empty', __('Your cart is empty.', 'publishpress-cart'));
        }
        if ($this->changed || $seen_revision !== $this->revision) {
            return new WP_Error('ppcart_cart_changed', __('Your cart changed. Review it, then try again.', 'publishpress-cart'));
        }
        $quantities = [];
        foreach ($this->lines as $line) {
            $limit = ppcart_check_product_purchase_limit($line->product_id, $customer);
            if (is_wp_error($limit)) {
                return new WP_Error('ppcart_cart_purchase_limit', $limit->get_error_message(), ['key' => $line->key]);
            }
            $quantities[$line->product_id] = ($quantities[$line->product_id] ?? 0) + $line->quantity;
        }
        foreach ($this->lines as $line) {
            if (! ppcart_check_product_stock($line->product_id, $quantities[$line->product_id])) {
                /* translators: %s: product name. */
                return new WP_Error('ppcart_cart_out_of_stock', sprintf(__('%s does not have enough stock for your cart.', 'publishpress-cart'), $line->product_name), ['key' => $line->key]);
            }
        }
        return true;
    }
}
