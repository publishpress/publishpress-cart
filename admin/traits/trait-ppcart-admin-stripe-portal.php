<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

/**
 * Stripe Customer Portal and Apple Pay domain configuration from admin settings.
 *
 * @package PPCart
 * @subpackage PPCart/admin
 */
trait PPCart_Admin_Stripe_Portal_Trait
{
    public function handle_stripe_domain_enable($option_name, $old_value, $new_value)
    {
        update_option('_ppcart_stripe_settings_changed', true);
    }

    public function handle_stripe_customer_portal_enable($option_name, $old_value, $new_value)
    {
        update_option('_ppcart_stripe_settings_changed', true);
    }

    public function manage_stripe_express_payment_customer_portal()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-portal-manage-stripe-express-payment-customer-portal.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function verify_apple_pay_domain($domain_name, $domainId, \PublishPress\Stripe\StripeClient $stripe)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-portal-verify-apple-pay-domain.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function configure_stripe_customer_portal($secret, $enabled_flag)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-portal-configure-stripe-customer-portal.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
