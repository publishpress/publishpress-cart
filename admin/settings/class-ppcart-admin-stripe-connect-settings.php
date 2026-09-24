<?php

if (! defined('ABSPATH')) {
    exit;
}


/**
 * Stripe Connect settings and webhook controller.
 *
 * @package PPCart
 * @subpackage PPCart/admin
 */

require_once __DIR__ . '/class-ppcart-admin-stripe-webhook-settings.php';
require_once __DIR__ . '/traits/trait-ppcart-admin-stripe-connect-state.php';
require_once __DIR__ . '/traits/trait-ppcart-admin-stripe-connect-encryption.php';
require_once __DIR__ . '/traits/trait-ppcart-admin-stripe-connect-flow.php';
require_once __DIR__ . '/traits/trait-ppcart-admin-stripe-connect-render.php';

class PPCart_Admin_Stripe_Connect_Settings
{
    use PPCart_Admin_Stripe_Connect_State_Trait;
    use PPCart_Admin_Stripe_Connect_Encryption_Trait;
    use PPCart_Admin_Stripe_Connect_Flow_Trait;
    use PPCart_Admin_Stripe_Connect_Render_Trait;

    /**
     * Stripe webhook settings controller.
     *
     * @var PPCart_Admin_Stripe_Webhook_Settings
     */
    private $webhooks;

    public function __construct()
    {
        $this->webhooks = new PPCart_Admin_Stripe_Webhook_Settings($this);
        add_action('admin_init', [ $this, 'maybe_handle_stripe_connect_start' ]);
        add_action('admin_init', [ $this, 'maybe_handle_stripe_connect_proxy_return' ]);
        add_action('admin_init', [ $this, 'maybe_handle_stripe_connect_disconnect' ]);
    }
}
