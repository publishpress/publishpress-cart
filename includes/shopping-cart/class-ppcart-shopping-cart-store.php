<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * The only writer of the cart table and of both cart cookies.
 *
 * Every write is a compare-and-swap on `revision`. On a miss the Store loads
 * the row again and replays the operation, so no caller saves a cart it read earlier.
 */
final class PPCart_Shopping_Cart_Store
{
    public const COOKIE = 'ppcart_cart';
    public const COUNT_COOKIE = 'ppcart_cart_count';

    private const MAX_TRIES = 3;

    public function __construct(
        private PPCart_Shopping_Cart_Catalog $catalog = new PPCart_Shopping_Cart_Catalog(),
        private PPCart_Shopping_Cart_Settings $settings = new PPCart_Shopping_Cart_Settings()
    ) {
    }

    public static function table(): string
    {
        return $GLOBALS['wpdb']->prefix . 'ppcart_shopping_carts';
    }

    public static function install(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $table = self::table();
        $charset_collate = $wpdb->get_charset_collate();
        dbDelta(
            "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			cart_key char(32) NOT NULL,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			items longtext NOT NULL,
			meta longtext NOT NULL,
			revision bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			expires_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY cart_key (cart_key),
			KEY user_id (user_id),
			KEY expires_at (expires_at)
		) {$charset_collate};"
        );
        if (! wp_next_scheduled('ppcart_shopping_cart_cleanup')) {
            wp_schedule_event(time() + DAY_IN_SECONDS, 'daily', 'ppcart_shopping_cart_cleanup');
        }
    }

    public static function delete_expired(): int
    {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Plugin-owned table.
        return (int) $wpdb->query($wpdb->prepare('DELETE FROM %i WHERE expires_at < %s', self::table(), gmdate('Y-m-d H:i:s')));
    }

    public function read(): PPCart_Shopping_Cart_Priced
    {
        $cart = $this->update(static fn (): bool => true);
        return is_wp_error($cart) ? $cart->get_error_data()['cart'] : $cart;
    }

    /** @param callable(PPCart_Shopping_Cart):(true|bool|WP_Error) $operation */
    public function update(callable $operation, bool $create = false): PPCart_Shopping_Cart_Priced|WP_Error
    {
        return $this->transact(fn (): ?object => $this->locate(), $operation, $create, true);
    }

    public function claim_checkout(int $seen_revision, array $customer): PPCart_Shopping_Cart_Priced|WP_Error
    {
        $cart = $this->read();
        $ready = $cart->ready_to_pay($seen_revision, $customer);
        if (true === $ready) {
            $row = $this->row_by_id($cart->cart_id);
            $meta = $row ? self::decode($row->meta) : [];
            $meta['_checkout_claim'] = ['at' => gmdate('Y-m-d\TH:i:s\Z')];
            if ($row && $this->compare_and_swap($cart->cart_id, $seen_revision, $row->items, wp_json_encode((object) $meta))) {
                return $cart->committed_as($cart->cart_id, $seen_revision + 1);
            }
            $cart = $this->read();
            $ready = new WP_Error('ppcart_cart_changed', __('Your cart changed. Review it, then try again.', 'publishpress-cart'));
        }
        $ready->add_data(['cart' => $cart] + (array) $ready->get_error_data());
        return $ready;
    }

    public function merge_guest_into_user(string $guest_cart_key, int $user_id): void
    {
        $row = $this->merge_guest($guest_cart_key, $user_id);
        if ($row) {
            $this->send_cookies($row->cart_key, count(self::decode($row->items)));
        }
    }

    public function merge_on_login(string $user_login, WP_User $user): void
    {
        $this->merge_guest_into_user($this->cookie_key(), (int) $user->ID);
    }

    /** @param list<array{key:string,added_at:string}> $lines */
    public function remove_paid_lines(int $cart_id, array $lines): void
    {
        $this->transact(
            fn (): ?object => $this->row_by_id($cart_id),
            static fn (PPCart_Shopping_Cart $cart): true => $cart->remove_paid_lines($lines)
        );
    }

    /**
     * @param callable():?object                                 $load      Returns the row to change, read fresh on each try.
     * @param callable(PPCart_Shopping_Cart):(true|bool|WP_Error) $operation
     */
    private function transact(callable $load, callable $operation, bool $create = false, bool $cookies = false): PPCart_Shopping_Cart_Priced|WP_Error
    {
        for ($try = 0; $try < self::MAX_TRIES; $try++) {
            $row = $load();
            $cart = $this->hydrate($row);
            $result = $operation($cart);
            $priced = $cart->validate();
            if (is_wp_error($result)) {
                $result->add_data(['cart' => $priced]);
                return $result;
            }
            $export = $cart->export();
            $items = wp_json_encode($export['lines']);
            $meta = wp_json_encode((object) $export['meta']);
            if (! $row) {
                if (! $create || $priced->is_empty()) {
                    if ($cookies) {
                        $this->send_cookies('', 0);
                    }
                    return PPCart_Shopping_Cart_Priced::empty();
                }
                $row = $this->insert($items, $meta);
                if (! $row) {
                    return new WP_Error('ppcart_cart_unavailable', __('The cart could not be saved. Please try again.', 'publishpress-cart'), ['cart' => PPCart_Shopping_Cart_Priced::empty()]);
                }
            } elseif ($items !== $row->items || $meta !== $row->meta) {
                if (! $this->compare_and_swap((int) $row->id, (int) $row->revision, $items, $meta)) {
                    continue;
                }
                $row->revision = (int) $row->revision + 1;
            }
            if ($cookies) {
                $this->send_cookies($row->cart_key, $priced->count());
            }
            return $priced->committed_as((int) $row->id, (int) $row->revision);
        }
        return new WP_Error('ppcart_cart_conflict', __('Your cart changed at the same time. Please try again.', 'publishpress-cart'), ['cart' => $this->hydrate($load())->validate()]);
    }

    private function locate(): ?object
    {
        $user_id = get_current_user_id();
        $cart_key = $this->cookie_key();
        if (! $user_id) {
            return '' === $cart_key ? null : $this->guest_row($cart_key);
        }
        if ('' !== $cart_key) {
            $this->merge_guest($cart_key, $user_id);
        }
        $rows = $this->user_rows($user_id);
        $oldest = array_shift($rows);
        foreach ($rows as $newer) {
            $oldest = $this->absorb($oldest, $newer) ?? $oldest;
        }
        return $oldest;
    }

    private function merge_guest(string $guest_cart_key, int $user_id): ?object
    {
        global $wpdb;

        $guest = '' === $guest_cart_key || ! $user_id ? null : $this->guest_row($guest_cart_key);
        if (! $guest) {
            return null;
        }
        $user = $this->user_rows($user_id)[0] ?? null;
        if (! $user) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Plugin-owned table.
            if ($wpdb->query($wpdb->prepare('UPDATE %i SET user_id = %d WHERE id = %d AND user_id = 0', self::table(), $user_id, $guest->id))) {
                return $this->row_by_id((int) $guest->id);
            }
            $user = $this->user_rows($user_id)[0] ?? null;
            $guest = $this->guest_row($guest_cart_key);
            if (! $user || ! $guest) {
                return $user;
            }
        }
        return $this->absorb($user, $guest);
    }

    /**
     * Merges the source row into the target row and deletes the source row. The
     * delete takes only the revision that was merged, so a write that lands on
     * the source in between is merged in the next try. Null means the source row
     * still holds lines that are not in the target.
     */
    private function absorb(object $target, object $source): ?object
    {
        global $wpdb;

        for ($try = 0; $try < self::MAX_TRIES; $try++) {
            $other = ['lines' => self::decode($source->items), 'meta' => self::decode($source->meta)];
            $merged = $this->transact(
                fn (): ?object => $this->row_by_id((int) $target->id),
                static fn (PPCart_Shopping_Cart $cart): true => $cart->merge($other)
            );
            if (is_wp_error($merged) || $merged->cart_id !== (int) $target->id) {
                return null;
            }
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Plugin-owned table.
            $deleted = $wpdb->query($wpdb->prepare('DELETE FROM %i WHERE id = %d AND revision = %d', self::table(), $source->id, $source->revision));
            $source = $deleted ? null : $this->row_by_id((int) $source->id);
            if (! $source) {
                return $this->row_by_id((int) $target->id);
            }
        }
        return null;
    }

    private function hydrate(?object $row): PPCart_Shopping_Cart
    {
        return $row
            ? new PPCart_Shopping_Cart($this->catalog, self::decode($row->items), self::decode($row->meta), (int) $row->id, (int) $row->revision)
            : new PPCart_Shopping_Cart($this->catalog);
    }

    private static function decode(string $json): array
    {
        $value = json_decode($json, true);
        return is_array($value) ? $value : [];
    }

    private function insert(string $items, string $meta): ?object
    {
        global $wpdb;

        $now = time();
        $row = [
            'cart_key'   => wp_generate_password(32, false),
            'user_id'    => get_current_user_id(),
            'items'      => $items,
            'meta'       => $meta,
            'revision'   => 1,
            'created_at' => gmdate('Y-m-d H:i:s', $now),
            'updated_at' => gmdate('Y-m-d H:i:s', $now),
            'expires_at' => gmdate('Y-m-d H:i:s', $now + $this->lifetime()),
        ];
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin-owned table.
        if (! $wpdb->insert(self::table(), $row, ['%s', '%d', '%s', '%s', '%d', '%s', '%s', '%s'])) {
            return null;
        }
        return (object) ($row + ['id' => (int) $wpdb->insert_id]);
    }

    private function compare_and_swap(int $id, int $revision, string $items, string $meta): bool
    {
        global $wpdb;

        $now = time();
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Plugin-owned table.
        return 1 === $wpdb->query($wpdb->prepare(
            'UPDATE %i SET items = %s, meta = %s, revision = revision + 1, updated_at = %s, expires_at = %s WHERE id = %d AND revision = %d',
            self::table(),
            $items,
            $meta,
            gmdate('Y-m-d H:i:s', $now),
            gmdate('Y-m-d H:i:s', $now + $this->lifetime()),
            $id,
            $revision
        ));
    }

    private function guest_row(string $cart_key): ?object
    {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Plugin-owned table.
        return $wpdb->get_row($wpdb->prepare('SELECT * FROM %i WHERE cart_key = %s AND user_id = 0 AND expires_at >= %s', self::table(), $cart_key, gmdate('Y-m-d H:i:s')));
    }

    /** @return list<object> */
    private function user_rows(int $user_id): array
    {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Plugin-owned table.
        return (array) $wpdb->get_results($wpdb->prepare('SELECT * FROM %i WHERE user_id = %d AND expires_at >= %s ORDER BY id ASC', self::table(), $user_id, gmdate('Y-m-d H:i:s')));
    }

    private function row_by_id(int $id): ?object
    {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Plugin-owned table.
        return $wpdb->get_row($wpdb->prepare('SELECT * FROM %i WHERE id = %d', self::table(), $id));
    }

    private function lifetime(): int
    {
        return max(1, $this->settings->lifetime_days()) * DAY_IN_SECONDS;
    }

    private function cookie_key(): string
    {
        // phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___COOKIE -- The cookie exists only after the first add; pages without it stay cacheable.
        $cart_key = isset($_COOKIE[self::COOKIE]) && is_string($_COOKIE[self::COOKIE]) ? sanitize_text_field(wp_unslash($_COOKIE[self::COOKIE])) : '';
        return 1 === preg_match('/^[A-Za-z0-9]{32}$/', $cart_key) ? $cart_key : '';
    }

    private function send_cookies(string $cart_key, int $count): void
    {
        $keep = $count > 0 && '' !== $cart_key;
        // phpcs:disable WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___COOKIE, WordPressVIPMinimum.Functions.RestrictedFunctions.cookies_setcookie -- The HttpOnly cart key must come from the server. Cart responses are never cached.
        if (! $keep && ! isset($_COOKIE[self::COOKIE])) {
            return;
        }
        if ($keep) {
            $_COOKIE[self::COOKIE] = $cart_key;
        } else {
            unset($_COOKIE[self::COOKIE]);
        }
        if (headers_sent()) {
            return;
        }
        $options = [
            'expires'  => $keep ? time() + $this->lifetime() : time() - YEAR_IN_SECONDS,
            'path'     => COOKIEPATH ?: '/',
            'domain'   => COOKIE_DOMAIN ?: '',
            'secure'   => is_ssl(),
            'samesite' => 'Lax',
        ];
        setcookie(self::COOKIE, $keep ? $cart_key : '', $options + ['httponly' => true]);
        setcookie(self::COUNT_COOKIE, $keep ? (string) $count : '', $options + ['httponly' => false]);
        // phpcs:enable WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___COOKIE, WordPressVIPMinimum.Functions.RestrictedFunctions.cookies_setcookie
    }
}
