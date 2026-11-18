<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Per-record lock for on-page PayPal reconciliation.
 *
 * Uses a MySQL advisory lock, which dies with the connection, so a crash
 * leaves nothing stale. Databases without GET_LOCK (SQLite) fall back to a
 * non-autoloaded option that goes stale after LOCK_STALE seconds.
 *
 * @package PPCart
 * @subpackage PPCart/public
 */
class PPCart_Paypal_Record_Lock
{
    private const WAIT  = 10;
    private const STALE = 60;

    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $uses_option = false;

    /**
     * @param int $record_id Cart order ID or subscription post ID.
     */
    public function __construct($record_id)
    {
        $this->name = 'ppcart_pp_' . absint($record_id);
    }

    /**
     * @return bool True when this request holds the lock.
     */
    public function acquire()
    {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Advisory lock; it has no cached equivalent.
        $got = $wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, %d)', $this->name, self::WAIT));
        if (null === $got && '' !== (string) $wpdb->last_error) {
            $this->uses_option = true;
            return $this->acquire_option();
        }

        return '1' === (string) $got;
    }

    /**
     * @return void
     */
    public function release()
    {
        global $wpdb;

        if ($this->uses_option) {
            delete_option($this->name);
            return;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Releases the advisory lock taken in acquire().
        $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $this->name));
    }

    private function acquire_option()
    {
        $now  = time();
        $held = (int) get_option($this->name, 0);
        if ($held && $now - $held < self::STALE) {
            return false;
        }
        if ($held) {
            return (bool) update_option($this->name, $now, false);
        }

        return (bool) add_option($this->name, $now, '', false);
    }
}
