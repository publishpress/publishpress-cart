<?php

/**
 * Option snapshots that preserve absence and stored empty values behind native getter filters.
 *
 * @package PPCart
 * @since 1.1.0
 */

defined('ABSPATH') || exit;

class PPCart_Mcp_Options
{
    /**
     * Read storage for a caller-supplied internal allowlist, without changing native getters.
     *
     * @since 1.1.0
     * @param array $ids Reviewed option names from the settings catalog.
     * @return array|WP_Error
     */
    public static function snapshots(array $ids)
    {
        global $wpdb;
        if (! $ids) {
            return [];
        }
        $snapshots = array_fill_keys($ids, ['exists' => false, 'value' => null]);
        $placeholders = implode(',', array_fill(0, count($ids), '%s'));
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared -- Bounded allowlisted option snapshot; get_option filters deliberately hide stored empty/absent distinctions.
        $rows = $wpdb->get_results($wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Only generated %s placeholders are interpolated; all option names are prepared below.
            "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name IN ($placeholders)",
            $ids
        ), ARRAY_A);
        if (! is_array($rows) || $wpdb->last_error) {
            return new WP_Error('ppcart_mcp_storage_unavailable', __('Cart settings storage could not be read.', 'publishpress-cart'));
        }
        foreach ($rows as $row) {
            $snapshots[$row['option_name']] = ['exists' => true, 'value' => maybe_unserialize($row['option_value'])];
        }
        return $snapshots;
    }
    /** @since 1.1.0 @param mixed $actual Native stored value. @param mixed $expected Sanitized value. @return bool */
    public static function same_value($actual, $expected)
    {
        return is_array($expected) ? is_array($actual) && $actual === $expected
            : is_scalar($actual) && (string) $actual === (string) $expected;
    }
}
