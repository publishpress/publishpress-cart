<?php

if (! defined('ABSPATH')) {
    exit;
}


/**
 * Provides the markup for any checkbox field
 *
 * @link https://publishpress.com/publishpress-cart/
 * @since 1.0.0
 *
 * @package PublishPressCart
 * @subpackage PublishPressCart/admin/partials
 */

$default_atts = [
    'value' => '',
    'class' => '',
    'note' => '',
    'description' => '',
    'disabled' => false,
    'id' => '',
];

$atts = wp_parse_args($atts, $default_atts);

$field_id = ! empty($atts['html_id']) ? $atts['html_id'] : ((isset($atts['rid'])) ? $atts['rid'] : $atts['id']);
$testid_source = ! empty($field_id) ? $field_id : ($atts['name'] ?? '');
$testid = ppcart_testid('ppcart-admin-field-' . $testid_source);
$data_attrs = isset($atts['data']) && is_array($atts['data']) ? $atts['data'] : [];
?>
<span class="ppcart-label"><?php echo esc_html($atts['label']); ?></span>
<div class="checkbox-wrap">
    <span class="ckbx-style">
        <input aria-role="checkbox"
            <?php checked(1, $atts['value'], true); ?>
            class="<?php echo esc_attr($atts['class']); ?>"
            id="<?php echo esc_attr($field_id); ?>"
            name="<?php echo esc_attr($atts['name']); ?>"
            type="checkbox"
            data-testid="<?php echo esc_attr($testid); ?>"
            <?php disabled(! empty($atts['disabled'])); ?>
            <?php foreach ($data_attrs as $data_attr => $data_value) : ?>
            <?php if (is_scalar($data_value) && preg_match('/^[a-z0-9_-]+$/', (string) $data_attr)) : ?>
            data-<?php echo esc_attr($data_attr); ?>="<?php echo esc_attr($data_value); ?>"
            <?php endif; ?>
            <?php endforeach; ?>
            value="1" />

        <label for="<?php echo esc_attr($field_id); ?>"></label>
    </span>
    <?php if ($atts['description']) : ?>
        <i class="ppcart-tooltip" data-balloon-length="medium" aria-label="<?php echo esc_attr($atts['description']); ?>" data-balloon-pos="up" tabindex="0">?</i>
    <?php endif; ?>
    <?php if ($atts['note']) : ?>
        <p class="description"><?php echo wp_kses_post($atts['note']); ?></p>
    <?php endif; ?>
</div>
