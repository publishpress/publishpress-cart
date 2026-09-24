<?php

if (! defined('ABSPATH')) {
    exit;
}


require_once __DIR__ . '/traits/trait-ppcart-public-subscription-create.php';

require_once __DIR__ . '/traits/trait-ppcart-public-subscription-connect.php';

/**
 * Public Stripe subscription checkout flow.
 *
 * @package PPCart
 * @subpackage PPCart/public
 */

/**
 * Creates Stripe subscriptions and syncs subscription status back to local records.
 */
class PPCart_Public_Subscription_Checkout_Controller
{
    use PPCart_Public_Subscription_Create_Trait;
    use PPCart_Public_Subscription_Connect_Trait;

    /**
     * Shared subscription checkout controller.
     *
     * @var PPCart_Public_Subscription_Checkout_Controller|null
     */
    private static $instance = null;

    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
