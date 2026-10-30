<?php

/**
 * Reviewed order-field selectors serialized to native integration map lines.
 *
 * @package PPCart
 * @since 1.1.0
 */

defined('ABSPATH') || exit;

class PPCart_Mcp_Integration_Mapping
{
    /** @since 1.1.0 @return array */
    public static function fields()
    {
        return ['convertkit_field_map', 'field_map', 'activecampaign_field_map', 'fluentcrm_fields', 'mailerlite_fields'];
    }

    /** @since 1.1.0 @return array */
    public static function sources($product_id = 0)
    {
        $sources = ['id', 'customer_firstname', 'customer_lastname', 'customer_name', 'customer_email',
            'customer_phone', 'customer_address', 'customer_address_2', 'customer_city', 'customer_state',
            'customer_zip', 'customer_country', 'product_id', 'product_name', 'gateway', 'date', 'date_time',
            'payment_plan', 'payment_plan_id', 'order_amount', 'tax_amount', 'pre_tax_amount', 'currency',
            'status', 'order_status', 'order_id', 'invoice_total', 'invoice_subtotal', 'shipping_amount'];
        return array_merge($sources, ppcart_integration_custom_sources($product_id));
    }

    /** @since 1.1.0 @return array */
    public static function schema($product_id = 0)
    {
        return ['type' => 'array', 'maxItems' => 50, 'items' => PPCart_Mcp_Input::schema([
            'destination' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 100, 'pattern' => '^[a-zA-Z0-9_%.-]+$'],
            'source' => $product_id ? ['type' => 'string', 'enum' => self::sources($product_id)] : ['type' => 'string', 'maxLength' => 200, 'pattern' => '^[a-zA-Z0-9_.-]+$'],
        ], ['destination', 'source'])];
    }

    /** @since 1.1.0 @param array $rows Reviewed pairs. @return string|WP_Error */
    public static function encode(array $rows, $product_id = 0)
    {
        $rows = PPCart_Mcp_Input::validate($rows, self::schema($product_id));
        if (is_wp_error($rows)) {
            return $rows;
        }
        $lines = [];
        foreach ($rows as $row) {
            if (! in_array($row['source'], self::sources($product_id), true) || isset($lines[$row['destination']])) {
                return PPCart_Mcp_Input::error();
            }
            $lines[$row['destination']] = $row['destination'] . ':' . $row['source'];
        }
        return implode("\n", $lines);
    }

    /** @since 1.1.0 @param mixed $stored Native lines. @return array Safe selector metadata only. */
    public static function decode($stored, $product_id = 0)
    {
        $rows = [];
        if (! is_string($stored)) {
            return $rows;
        }
        foreach (explode("\n", str_replace("\r", '', $stored)) as $line) {
            $pair = array_map('trim', explode(':', $line, 2));
            if (
                count($pair) === 2 && preg_match('/^[a-zA-Z0-9_%.-]{1,100}$/', $pair[0])
                && in_array($pair[1], self::sources($product_id), true)
            ) {
                $rows[] = ['destination' => $pair[0], 'source' => $pair[1]];
            }
        }
        return $rows;
    }
}
