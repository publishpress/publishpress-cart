<?php

if (!defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

require_once __DIR__ . '/order/traits/trait-ppcart-order-persistence.php';
require_once __DIR__ . '/order/traits/trait-ppcart-order-checkout-input.php';
require_once __DIR__ . '/order/traits/trait-ppcart-order-amounts.php';
require_once __DIR__ . '/order/traits/trait-ppcart-order-invoices.php';
require_once __DIR__ . '/order/traits/trait-ppcart-order-lookups.php';

class PPCart_Order extends stdClass
{
    use PPCart_Order_Persistence;
    use PPCart_Order_Checkout_Input;
    use PPCart_Order_Amounts;
    use PPCart_Order_Invoices;
    use PPCart_Order_Lookups;

    protected $attrs;
    protected $customer_attrs;
    protected $defaults;
    public const INVOICE_TOKEN_META_KEY = '_ppcart_invoice_token';

    public function __construct($obj = null)
    {
        include __DIR__ . '/templates/ppcart-order---construct.php';
    }

    public function initialize($defaults, $customer_defaults, $obj = null)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/ppcart-order-initialize.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    // Statuses
    public static $pending_str    = 'pending-payment';
    public static $failed_str     = 'failed';
    public static $paid_str       = 'paid';
    public static $completed_str  = 'completed';
    public static $uncollect_str  = 'uncollectible';
    public static $refunded_str   = 'refunded';

    // Static Gateways
    public static $free_gateway_str  = 'free';
    public static $cod_gateway_str   = 'cod';
    public static $stripe_gateway_str = 'stripe';
    public static $paypal_gateway_str = 'paypal';
}
