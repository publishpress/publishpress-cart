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
$secure_upload_testid = ppcart_testid('ppcart-admin-field-' . $testid_source . '-secure-upload');
$media_upload_testid = ppcart_testid('ppcart-admin-field-' . $testid_source . '-media-library');
$file_testid = ppcart_testid('ppcart-admin-field-' . $testid_source . '-file');

if (! empty($atts['label'])) {
    ?><label for="<?php echo esc_attr($field_dom_id); ?>"><?php echo esc_html($atts['label']); ?> </label><?php
}

?><div class="input-group field-upload">
<input
    class="<?php echo esc_attr($atts['class']); ?>"
    data-id="url-file"
    id="<?php echo esc_attr($field_dom_id); ?>"
    name="<?php echo esc_attr($atts['name']); ?>"
    data-testid="<?php echo esc_attr($testid); ?>"
    type="<?php echo esc_attr($atts['field-type']); ?>"
    value="<?php echo esc_attr($atts['value']); ?>" />
<a href="#" class="button ppcart-upload-file" style="margin:0 5px" data-testid="<?php echo esc_attr($secure_upload_testid); ?>"><?php echo esc_html($atts['label-upload']); ?></a>
<a href="#" class="button upload-file" data-testid="<?php echo esc_attr($media_upload_testid); ?>"><?php esc_html_e('Media Library', 'publishpress-cart'); ?></a>
<input type="file" name="async-upload" class="ppcart-upload-file-field image-file hide" accept="*" data-testid="<?php echo esc_attr($file_testid); ?>">
</div>
