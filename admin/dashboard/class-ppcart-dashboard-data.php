<?php

if (! defined('ABSPATH')) {
    exit;
}


require_once plugin_dir_path(__FILE__) . 'class-ppcart-dashboard-order-data.php';
require_once plugin_dir_path(__FILE__) . 'class-ppcart-dashboard-subscription-data.php';
require_once plugin_dir_path(__FILE__) . 'class-ppcart-dashboard-plan-data.php';

/**
 * Aggregates monthly order, subscription, and payment-plan metrics for the dashboard widget.
 *
 * @package PPCart
 * @subpackage PPCart/admin
 */
class PPCart_Dashboard_Data
{
    private $orders;

    private $subscriptions;

    private $plans;

    public function __construct()
    {
        $this->orders        = new PPCart_Dashboard_Order_Data();
        $this->subscriptions = new PPCart_Dashboard_Subscription_Data();
        $this->plans         = new PPCart_Dashboard_Plan_Data();
    }

    public function get_summary()
    {
        return [
            'orders'        => $this->orders->get_summary(),
            'subscriptions' => $this->subscriptions->get_summary(),
            'plans'         => $this->plans->get_summary(),
        ];
    }
}
