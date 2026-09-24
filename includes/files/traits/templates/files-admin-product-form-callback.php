<?php

if (! defined('ABSPATH')) {
    exit;
}


global $ppcart_currency_symbol;

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin screen context check.
if (! ppcart_is_order_post_type($post->post_type) || !isset($_GET['post'])) {
    return;
}

if (ppcart_get_post_meta($post->ID, 'status', true) == 'pending-payment') {
    return;
}

if (!$files = $this->get_order_downloads($post->ID, ['status' => 'all'])) {
    return;
}

?>
<div class="ppcart-product-info ppcart-product-table meta-box-sortables ui-sortable" data-testid="ppcart-admin-order-downloads">
    <table cellpadding="0" cellspacing="0" class="ppcart-order-items" style="width: 100%" data-testid="ppcart-admin-order-downloads-table">
        <thead>
            <tr>
                <th id="Files" class="item"><?php esc_html_e('File Name', 'publishpress-cart'); ?></th>
                <th id="Expires" class="line_cost"><?php esc_html_e('Downloads', 'publishpress-cart'); ?></th>
                <th id="Remaining" class="line_cost"><?php esc_html_e('Downloads Remaining', 'publishpress-cart'); ?></th>
                <th></th>
            </tr>
        </thead>
        <tbody id="order_line_items">
            <?php foreach ($files as $download) : ?>
                <tr>
                    <td><a href="<?php echo esc_url($download->path); ?>" target="_blank"><?php echo esc_html($download->name); ?></a></td>
                    <td><?php echo esc_html(count($download->downloads)); ?></td>
                    <td><input class="widefat" id="remaining[<?php echo esc_attr($download->download_id); ?>]" name="remaining[<?php echo esc_attr($download->download_id); ?>]" placeholder="Unlimited" type="number" step=1 autocomplete="new-password" autocorrect="off" autocapitalize="none" value="<?php echo esc_attr($download->downloads_remaining); ?>" data-lpignore="true"></td>
                    <td>
                        <a class="button button-primary" href="#" onclick="copyKey('<?php echo esc_js($download->url); ?>', this)"><?php echo esc_html__('Copy URL', 'publishpress-cart'); ?></a>
                        <a class="button" href="<?php echo esc_url(wp_nonce_url(get_edit_post_link($post->ID, 'edit') . '&ppcart-revoke=' . $download->download_id . '&dl=' . rawurlencode($download->order_key), 'update-post_' . $post->ID)); ?>" onclick="return confirm('<?php echo esc_js(__("Are you sure? This action can't be undone.", 'publishpress-cart')); ?>')"><?php echo esc_html__('Revoke Access', 'publishpress-cart'); ?></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<input type="hidden" name="ppcart_process_downloads" value="1" data-testid="ppcart-admin-order-downloads-process" />
<?php
wp_add_inline_script(
    'ppcart-repeater',
    "
    window.copyKey = (str, el) => {
          if (navigator && navigator.clipboard && navigator.clipboard.writeText) {
            jQuery(el).html('Copied!');
            navigator.clipboard.writeText(str);
            return false;
          }
          return Promise.reject('The Clipboard API is not available.');
        };
    "
);
