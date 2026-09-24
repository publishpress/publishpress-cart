<?php

if (! defined('ABSPATH')) {
    exit;
}


/**
 * Public checkout and Stripe payment flow.
 *
 * @package PPCart
 * @subpackage PPCart/public
 */

/**
 * Handles checkout validation, Stripe intents, order saves, upsells, and subscription updates.
 */
require_once __DIR__ . '/checkout/traits/trait-ppcart-public-checkout-connect.php';
require_once __DIR__ . '/checkout/traits/trait-ppcart-public-checkout-payment.php';
require_once __DIR__ . '/checkout/traits/trait-ppcart-public-checkout-upsell.php';
require_once __DIR__ . '/checkout/traits/trait-ppcart-public-checkout-validation.php';

class PPCart_Public_Checkout_Controller
{
    use PPCart_Public_Checkout_Connect_Trait;
    use PPCart_Public_Checkout_Payment_Trait;
    use PPCart_Public_Checkout_Upsell_Trait;
    use PPCart_Public_Checkout_Validation_Trait;


    /**
     * Lazily-composed Stripe order/subscription save execution helper.
     *
     * @var PPCart_Stripe_Save_Helper|null
     */
    private $stripe_save_helper = null;

    public function __construct()
    {
        add_action('ppcart_before_create_main_order', [$this, 'check_product_purchase_limit']);
        add_action('ppcart_before_create_main_order', [$this, 'validate_order_form_submission']);
    }
}
