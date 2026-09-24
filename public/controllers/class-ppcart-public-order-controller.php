<?php

if (! defined('ABSPATH')) {
    exit;
}


/**
 * Public order save and status flow.
 *
 * @package PPCart
 * @subpackage PPCart/public
 */

/**
 * Saves checkout orders, updates payment status, and renders payment method details.
 */
require_once __DIR__ . '/order/traits/trait-ppcart-public-order-stripe.php';
require_once __DIR__ . '/order/traits/trait-ppcart-public-order-save.php';
require_once __DIR__ . '/order/traits/trait-ppcart-public-order-custom-fields.php';
require_once __DIR__ . '/order/traits/trait-ppcart-public-order-cart.php';

class PPCart_Public_Order_Controller
{
    use PPCart_Public_Order_Stripe_Trait;
    use PPCart_Public_Order_Save_Trait;
    use PPCart_Public_Order_Custom_Fields_Trait;
    use PPCart_Public_Order_Cart_Trait;


    /**
     * Lazily-composed Stripe order/subscription save execution helper.
     *
     * @var PPCart_Stripe_Save_Helper|null
     */
    private $stripe_save_helper = null;

    public function __construct()
    {
        add_action('wp_ajax_ppcart_update_cart_amount', [ $this, 'update_cart_amount' ]);
        add_action('wp_ajax_nopriv_ppcart_update_cart_amount', [ $this, 'update_cart_amount' ]);
    }
}
