<?php

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="ppcart-product-info ppcart-product-table meta-box-sortables ui-sortable" data-testid="ppcart-admin-order-downloads">
    <table cellpadding="0" cellspacing="0" class="ppcart-order-items" style="width: 100%" data-testid="ppcart-admin-order-downloads-table">
        <thead>
            <tr>
                <th id="Files" class="item"><?php esc_html_e('File Name', 'publishpress-cart'); ?></th>
                <th id="Downloads" class="line_cost"><?php esc_html_e('Downloads', 'publishpress-cart'); ?></th>
                <th id="Expires" class="line_cost"><?php esc_html_e('Expires', 'publishpress-cart'); ?></th>
                <th id="Remaining" class="line_cost"><?php esc_html_e('Downloads Remaining', 'publishpress-cart'); ?></th>
                <th></th>
            </tr>
        </thead>
        <tbody id="order_line_items">
            <?php foreach ($files as $download) : ?>
                <tr>
                    <td><a href="<?php echo esc_url($download->path); ?>" target="_blank"><?php echo esc_html($download->name); ?></a></td>
                    <td><?php echo esc_html(count($download->downloads)); ?></td>
                    <td><input class="widefat" id="expires[<?php echo esc_attr($download->download_id); ?>]" name="expires[<?php echo esc_attr($download->download_id); ?>]" placeholder="" type="datetime-local" autocomplete="new-password" autocorrect="off" autocapitalize="none" value="<?php echo esc_attr($download->download_expires); ?>" data-lpignore="true"></td>
                    <td><input class="widefat" id="remaining[<?php echo esc_attr($download->download_id); ?>]" name="remaining[<?php echo esc_attr($download->download_id); ?>]" placeholder="Unlimited" type="number" step="1" autocomplete="new-password" autocorrect="off" autocapitalize="none" value="<?php echo esc_attr($download->downloads_remaining); ?>" data-lpignore="true"></td>
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
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(str).then(() => {
                jQuery(el).html('Copied!');
            }).catch(err => {
                console.log('Failed to copy: ', err);
            });
        } else {
            const textarea = document.createElement('textarea');
            textarea.value = str;
            document.body.appendChild(textarea);
            textarea.select();
            try {
                document.execCommand('copy');
                jQuery(el).html('Copied!');
            } catch (err) {
                console.log('Fallback copy failed:', err);
            }
            document.body.removeChild(textarea);
        }
        return false;
    };
    "
);
