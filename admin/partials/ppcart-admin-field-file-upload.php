<?php

if (! defined('ABSPATH')) {
    exit;
}


/**
 * Provides the markup for an upload field
 *
 * @link https://publishpress.com/publishpress-cart/
 * @since 1.0.0
 *
 * @package PublishPress_Cart
 * @subpackage PublishPress_Cart/admin/partials
 */

$field_dom_id = ppcart_admin_field_dom_id($atts);
$testid_source = ! empty($atts['id']) ? $atts['id'] : ($atts['name'] ?? '');
$testid = ppcart_testid('ppcart-admin-field-' . $testid_source);
$upload_testid = ppcart_testid('ppcart-admin-field-' . $testid_source . '-upload');
$remove_testid = ppcart_testid('ppcart-admin-field-' . $testid_source . '-remove');
$help_id = ! empty($atts['description']) ? $field_dom_id . '-help' : '';

if (! empty($atts['label'])) {
    ?><label for="<?php echo esc_attr($field_dom_id); ?>"><?php echo esc_html($atts['label']); ?><?php ppcart_admin_field_tooltip($atts); ?></label><?php
}

?><div class="input-group field-upload">
<input
    class="<?php echo esc_attr($atts['class']); ?>"
    data-id="url-file"
    id="<?php echo esc_attr($field_dom_id); ?>"
    name="<?php echo esc_attr($atts['name']); ?>"
    data-testid="<?php echo esc_attr($testid); ?>"
    <?php if ('' !== $help_id) :
        ?>aria-describedby="<?php echo esc_attr($help_id); ?>"<?php
    endif; ?>
    type="<?php echo esc_attr($atts['field-type']); ?>"
    value="<?php echo esc_attr($atts['value']); ?>" />
<a href="#" class="button upload-file <?php echo ($atts['value']) ? 'hide' : ''; ?>" data-testid="<?php echo esc_attr($upload_testid); ?>"><?php echo esc_html($atts['label-upload']); ?></a>
<a href="#" class="button remove-file <?php echo (!$atts['value']) ? 'hide' : ''; ?>" data-testid="<?php echo esc_attr($remove_testid); ?>"><?php echo esc_html($atts['label-remove']); ?></a>
</div>
<?php if ('' !== $help_id) : ?>
    <p class="description ppcart-field-help" id="<?php echo esc_attr($help_id); ?>"><?php echo wp_kses_post(wp_specialchars_decode($atts['description'], ENT_QUOTES)); ?></p>
<?php endif; ?>
