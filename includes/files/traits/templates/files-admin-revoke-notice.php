<?php

if (! defined('ABSPATH')) {
    exit;
}


// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin notice query parameter.
if (isset($_GET['ppcart-revoked'])) {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin notice query parameter.
    $msg = sanitize_text_field(wp_unslash($_GET['ppcart-revoked']));
    $class = 'error';

    switch ($msg) {
        case 'error':
            $msg = __('Unable to revoke download access, please try again.', 'publishpress-cart');
            break;
        case 'download-not-found':
            $msg = __('Unable to find the download associated with this order, maybe access was already revoked?', 'publishpress-cart');
            break;
        default:
            /* translators: %s: file name. */
            $msg = sprintf(__('Access to file "%s" has been revoked.', 'publishpress-cart'), $msg);
            $class = 'success';
            break;
    }
    ?>
    <div class="notice notice-<?php echo esc_attr($class); ?> is-dismissible">
        <p><?php echo esc_html($msg); ?></p>
    </div>
    <?php
}
