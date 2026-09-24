<?php

if (! defined('ABSPATH')) {
    exit;
}


require_once __DIR__ . '/stripe/traits/trait-ppcart-stripe-order-save.php';

require_once __DIR__ . '/stripe/traits/trait-ppcart-stripe-subscription-save.php';

/**
 * Stripe order/subscription save execution helper.
 *
 * Stateless, constructor-free helper that owns the Stripe order/subscription save
 * cluster shared by the checkout and the Pro upsell/downsell flow. Free composes one
 * instance per owning PPCart_Public via PPCart_Public::get_stripe_save_helper(),
 * and passes it to Pro at the ppcart_order_save_override seam so Pro can run the same
 * save path without extending Free classes.
 *
 * @link https://publishpress.com/
 *
 * @package PublishPressCart
 * @subpackage PublishPressCart/includes
 * @author PublishPress <help@publishpress.com>
 */

class PPCart_Stripe_Save_Helper
{
    use PPCart_Stripe_Order_Save_Trait;
    use PPCart_Stripe_Subscription_Save_Trait;
}
