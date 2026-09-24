<?php

if (! defined('ABSPATH')) {
    exit;
}


$authenticated_user = wp_get_current_user();
$default_test_email = ($authenticated_user && $authenticated_user->exists()) ? $authenticated_user->user_email : '';
?>
<div class="ppcart-notif-modal" data-ppcart-notif-modal hidden role="dialog" aria-modal="true" aria-labelledby="ppcart-notif-modal-title">
    <div class="ppcart-notif-modal__backdrop" data-ppcart-notif-modal-close></div>
    <div class="ppcart-notif-modal__dialog" role="document">
        <div class="ppcart-notif-modal__header">
            <div>
                <div class="ppcart-notif-modal__eyebrow"><?php esc_html_e('Product notification', 'publishpress-cart'); ?></div>
                <h3 id="ppcart-notif-modal-title" data-ppcart-notif-modal-title><?php esc_html_e('Email preview', 'publishpress-cart'); ?></h3>
            </div>
            <button type="button" class="ppcart-notif-modal__close" data-ppcart-notif-modal-close aria-label="<?php esc_attr_e('Close', 'publishpress-cart'); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-product-notification-modal-close')); ?>">&times;</button>
        </div>
        <div class="ppcart-notif-modal__body">
            <div class="ppcart-notif-modal__notice" data-ppcart-notif-disabled-notice hidden>
                <?php esc_html_e('This notification is currently disabled. Preview and test email still work.', 'publishpress-cart'); ?>
            </div>
            <div class="ppcart-notif-modal__frame-wrap">
                <?php // No allow-scripts: the preview can't run scripts; allow-same-origin keeps it writable via doc.write().?>
                <iframe class="ppcart-notif-modal__frame" data-ppcart-notif-modal-frame title="<?php esc_attr_e('Email preview', 'publishpress-cart'); ?>" sandbox="allow-same-origin" tabindex="-1"></iframe>
            </div>
        </div>
        <div class="ppcart-notif-modal__footer">
            <div class="ppcart-notif-modal__test">
                <label for="ppcart-notif-test-email"><?php esc_html_e('Send test to', 'publishpress-cart'); ?></label>
                <input type="email" id="ppcart-notif-test-email" class="regular-text" data-ppcart-notif-test-email value="<?php echo esc_attr($default_test_email); ?>" placeholder="you@example.com" />
                <button type="button" class="button button-primary" data-ppcart-notif-send-test data-testid="<?php echo esc_attr(ppcart_testid('ppcart-product-notification-send-test')); ?>"><?php esc_html_e('Send test email', 'publishpress-cart'); ?></button>
                <span class="ppcart-notif-modal__status" data-ppcart-notif-status role="status" aria-live="polite"></span>
            </div>
            <button type="button" class="button button-secondary" data-ppcart-notif-modal-close><?php esc_html_e('Close', 'publishpress-cart'); ?></button>
        </div>
    </div>
</div>
<?php
