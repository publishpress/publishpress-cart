<?php

if (!defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

require_once __DIR__ . '/traits/trait-ppcart-subscription-orders.php';
require_once __DIR__ . '/traits/trait-ppcart-subscription-storage.php';

class PPCart_Subscription
{
    use PPCart_Subscription_Orders;
    use PPCart_Subscription_Storage;

    protected $attrs;
    protected $order_attrs;
    protected $defaults;
    protected $count_orders;
    public $id;
    public $subscription_id;
    public $status;
    public $sub_status;
    public $first_order;
    public $order_log;
    public $amount;
    public $tax_amount;
    public $sub_amount;
    public $sub_discount;
    public $sub_discount_duration;
    public $sub_item_name;
    public $sub_installments;
    public $sub_interval;
    public $sub_frequency;
    public $sub_next_bill_date;
    public $sub_end_date;
    public $cancel_at;
    public $free_trial_days;
    public $sign_up_fee;
    public $order_bump_subs;
    public $main_product_sub;
    public $cancel_date;
    public $firstname;
    public $lastname;
    public $first_name;
    public $last_name;
    public $customer_name;
    public $customer_id;
    public $custom_fields_post_data;
    public $custom_fields;
    public $custom;
    public $company;
    public $email;
    public $phone;
    public $country;
    public $address1;
    public $address2;
    public $city;
    public $state;
    public $zip;
    public $product_id;
    public $product_name;
    public $page_id;
    public $page_url;
    public $item_name;
    public $plan_id;
    public $option_id;
    public $ip_address;
    public $tax_rate;
    public $tax_desc;
    public $tax_data;
    public $tax_type;
    public $stripe_tax_id;
    public $user_account;
    public $auto_login;
    public $coupon;
    public $coupon_id;
    public $on_sale;
    public $pay_method;
    public $gateway_mode;
    public $currency;
    public $main_offer;
    public $main_offer_amt;
    public $us_parent;
    public $ds_parent;
    public $vat_number;
    public $quantity;

    public function __construct($obj = null)
    {
        $this->initialize(
            // sub only keys
            [
              'id'                => 0,
              'subscription_id'   => wp_generate_uuid4(),
              'status'            => self::$pending_str,
              'sub_status'        => self::$pending_str,
              'first_order'       => 0,
              'order_log'         => null,
              'amount'            => 0.00,
              'tax_amount'        => 0.00,
              'sub_amount'        => 0,
              'sub_discount'      => 0,
              'sub_discount_duration' => null,
              'sub_item_name'     => null,
              'sub_installments'  => null,
              'sub_interval'      => null,
              'sub_frequency'     => null,
              'sub_next_bill_date' => null,
              'sub_end_date'      => null,
              'cancel_at'         => null,
              'free_trial_days'   => 0,
              'sign_up_fee'       => 0,
              'order_bump_subs'   => null, // deprecated
              'main_product_sub'  => null, // deprecated
              'cancel_date'       => null,
            ],
            // shared order keys
            [
              'firstname'         => null, // backwards compatibility
              'lastname'          => null, // backwards compatibility
              'first_name'        => null,
              'last_name'         => null,
              'customer_name'     => null,
              'customer_id'       => null,
              'custom_fields_post_data' => null,
              'custom_fields'     => null,
              'custom'            => null,
              'company'           => null,
              'email'             => null,
              'phone'             => null,
              'country'           => null,
              'address1'          => null,
              'address2'          => null,
              'city'              => null,
              'state'             => null,
              'zip'               => null,
              'product_id'        => null,
              'product_name'      => null,
              'page_id'           => null,
              'page_url'          => null,
              'item_name'         => null,
              'plan_id'           => null,
              'option_id'         => null,
              'ip_address'        => null,
              'tax_rate'          => 0.00,
              'tax_desc'          => '',
              'tax_data'          => '',
              'tax_type'          => 'tax',
              'stripe_tax_id'     => '',
              'user_account'      => 0,
              'auto_login'        => null,
              'coupon'            => null,
              'coupon_id'         => null,
              'on_sale'           => 0,
              'pay_method'        => null,
              'gateway_mode'      => null,
              'currency'          => 'USD',
              'main_offer'        => null,
              'main_offer_amt'    => null,
              'us_parent'         => null,
              'ds_parent'         => null,
              'vat_number'        => '',
              'quantity'          => 1,
            ],
            $obj
        );
    }


    // Statuses
    public static $pending_str    = 'pending-payment';
    public static $incomplete_str = 'incomplete';
    public static $trial_str      = 'trialing';
    public static $active_str     = 'active';
    public static $past_due_str   = 'past_due';
    public static $paused_str     = 'paused';
    public static $canceled_str   = 'canceled';
    public static $completed_str  = 'completed';

    // Static Gateways
    public static $free_gateway_str  = 'free';
    public static $cod_gateway_str   = 'cod';
    public static $stripe_gateway_str = 'stripe';
    public static $paypal_gateway_str = 'paypal';
}
