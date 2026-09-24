<?php

if (! defined('ABSPATH')) {
    exit;
}


/**
 * Provides the markup for any text field
 *
 * @link https://publishpress.com/publishpress-cart/
 * @since 1.0.0
 *
 * @package PublishPress_Cart
 * @subpackage PublishPress_Cart/admin/partials
 */

$name = isset($atts['name']) && '' !== $atts['name'] ? $atts['name'] : $atts['id'];
$field_dom_id = ppcart_admin_field_dom_id($atts);
$testid_source = ! empty($atts['id']) ? $atts['id'] : $name;
$testid = ppcart_testid('ppcart-admin-field-' . $testid_source);

?><input
    class="<?php echo esc_attr($atts['class']); ?>"
    id="<?php echo esc_attr($field_dom_id); ?>"
    name="<?php echo esc_attr($name); ?>"
    placeholder="<?php echo esc_attr($atts['placeholder']); ?>"
    data-testid="<?php echo esc_attr($testid); ?>"
    type="<?php echo esc_attr($atts['type']); ?>"
    value="<?php echo esc_attr($atts['value']); ?>" />
