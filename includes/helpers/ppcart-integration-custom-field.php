<?php

/**
 * Declared checkout-field selectors for native integration mappings.
 *
 * @package PPCart
 * @since 1.1.0
 */

defined('ABSPATH') || exit;

/** @since 1.1.0 @param int $product_id Product. @return array */
function ppcart_integration_custom_sources($product_id)
{
    $sources = [];
    $fields = ppcart_get_post_meta($product_id, 'custom_fields', true);
    foreach (is_array($fields) ? $fields : [] as $field) {
        $id = $field['field_id'] ?? '';
        if (
            is_string($id) && preg_match('/^[a-zA-Z0-9_-]+$/', $id)
            && ! preg_match('/password|secret|token|api[ _-]?key|credential|private[ _-]?key/i', $id . ' ' . ($field['field_label'] ?? '')) && empty($field['secret']) && 'password' !== ($field['field_type'] ?? '')
        ) {
            $sources[] = 'custom.' . $id;
        }
    }
    return $sources;
}

/** @since 1.1.0 @param string $source Declared selector. @param int $order_id Order. @return mixed */
function ppcart_integration_custom_value($source, $order_id)
{
    $product_id = (int) ppcart_get_post_meta($order_id, 'product_id', true);
    if (! in_array($source, ppcart_integration_custom_sources($product_id), true)) {
        return null;
    }
    $values = ppcart_get_post_meta($order_id, 'custom_fields', true);
    $value = is_array($values) ? ($values[substr($source, 7)] ?? null) : null;
    if (! is_array($value) || 'password' === ($value['type'] ?? '') || ! array_key_exists('value', $value)) {
        return null;
    }
    $value = $value['value'];
    if (is_scalar($value) || null === $value) {
        return $value;
    }
    if (is_array($value) && array_values($value) === $value) {
        foreach ($value as $item) {
            if (! is_scalar($item)) {
                return null;
            }
        }
        return $value;
    }
    return null;
}
