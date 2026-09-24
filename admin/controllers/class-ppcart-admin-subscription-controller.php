<?php

if (! defined('ABSPATH')) {
    exit;
}


/**
 * Renders subscription edit-screen details and handles manual Stripe sync requests.
 *
 * @package PPCart
 * @subpackage PPCart/admin
 */

require_once __DIR__ . '/subscription/traits/trait-ppcart-admin-subscription-info.php';
require_once __DIR__ . '/subscription/traits/trait-ppcart-admin-subscription-sync.php';
require_once __DIR__ . '/subscription/traits/trait-ppcart-admin-subscription-form.php';

class PPCart_Admin_Subscription_Controller
{
    use PPCart_Admin_Subscription_Info_Trait;
    use PPCart_Admin_Subscription_Sync_Trait;
    use PPCart_Admin_Subscription_Form_Trait;


    /** @var string */
    private $plugin_name;

    /** @var string */
    private $plugin_title;

    /** @var string */
    private $version;

    /** @var \PublishPress\Stripe\StripeClient|null */
    private $stripe;

    public function __construct($plugin_name, $plugin_title, $version)
    {
        global $ppcart_stripe;

        $this->stripe = empty($ppcart_stripe['sk']) ? null : ppcart_stripe_client($ppcart_stripe['sk']);
        $this->plugin_name = $plugin_name;
        $this->plugin_title = $plugin_title;
        $this->version = $version;

        add_action('wp_ajax_ppcart_sync_subscription', [$this, 'sync_subscription_ajax']);
        add_action('wp_ajax_ppcart_sync_order', [$this, 'sync_order_ajax']);
    }
}
