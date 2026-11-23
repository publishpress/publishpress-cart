<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * The notice under the checkout email field when the email already has an account.
 * This template can be overridden by copying it to yourtheme/publishpress-cart/checkout/existing-account-notice.php.
 *
 * The checkout script finds each part by its class, so keep the ppcart-existing-account-* classes.
 * The login inputs have no name, so the checkout form never submits them.
 *
 * @var array $attr {
 *     @type string $login_url         Account login page.
 *     @type string $lost_password_url Lost password page.
 * }
 */

$password_id = wp_unique_id('ppcart-existing-account-password-');
$login_toggle = '<button type="button" class="ppcart-existing-account-login-toggle" aria-expanded="false" data-testid="'
    . esc_attr(ppcart_data_testid_attribute('ppcart-checkout-existing-account-login-toggle')) . '">'
    . esc_html__('Log in', 'publishpress-cart') . '</button>';
$reset_link = '<a class="ppcart-existing-account-reset" href="' . esc_url($attr['lost_password_url']) . '" data-testid="'
    . esc_attr(ppcart_data_testid_attribute('ppcart-checkout-existing-account-reset')) . '">'
    . esc_html__('reset your password', 'publishpress-cart') . '</a>';
?>
<div class="ppcart-existing-account" aria-live="polite" data-testid="<?php echo esc_attr(ppcart_data_testid_attribute('ppcart-checkout-existing-account')); ?>">
    <div class="ppcart-existing-account-notice" hidden>
        <p class="ppcart-existing-account-message">
            <?php
            printf(
                /* translators: 1: "Log in" button, 2: "reset your password" link. */
                esc_html__('You already have an account with this email. %1$s to continue, or %2$s.', 'publishpress-cart'),
                $login_toggle, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from escaped parts above.
                $reset_link // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from escaped parts above.
            );
            ?>
        </p>
        <div class="ppcart-existing-account-login" hidden>
            <label for="<?php echo esc_attr($password_id); ?>"><?php esc_html_e('Password', 'publishpress-cart'); ?></label>
            <input type="password" id="<?php echo esc_attr($password_id); ?>" class="ppcart-form-control ppcart-existing-account-password" autocomplete="current-password" data-testid="<?php echo esc_attr(ppcart_data_testid_attribute('ppcart-checkout-existing-account-password')); ?>">
            <button type="button" class="ppcart-btn ppcart-btn-primary ppcart-existing-account-login-submit" data-testid="<?php echo esc_attr(ppcart_data_testid_attribute('ppcart-checkout-existing-account-login-submit')); ?>"><?php esc_html_e('Log in', 'publishpress-cart'); ?></button>
            <p class="ppcart-existing-account-error" hidden data-testid="<?php echo esc_attr(ppcart_data_testid_attribute('ppcart-checkout-existing-account-error')); ?>"></p>
            <p class="ppcart-existing-account-fallback">
                <a class="ppcart-existing-account-login-page" href="<?php echo esc_url($attr['login_url']); ?>" data-testid="<?php echo esc_attr(ppcart_data_testid_attribute('ppcart-checkout-existing-account-login-page')); ?>"><?php esc_html_e('Log in on the account page instead', 'publishpress-cart'); ?></a>
            </p>
        </div>
    </div>
</div>
