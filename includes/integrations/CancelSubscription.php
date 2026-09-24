<?php

namespace PublishPress\Cart;

use WP_Query;

if (!defined('ABSPATH')) {
    exit;
}

class CancelSubscription
{
    /**
     * The ID of this plugin.
     *
     * @since 1.0.0
     * @access private
     * @var string    $plugin_name    The ID of this plugin.
     */
    private $service_name;
    private $service_label;

    public function __construct()
    {
        $this->service_name = 'ppcart_subscription';
        $this->service_label = "Cancel Subscription";
        add_action('plugins_loaded', [$this, 'init']);
    }

    public function init()
    {
        add_filter('ppcart_integrations', [$this, 'add_subscription_service']);
        add_filter('ppcart_integration_fields', [$this, 'add_integration_fields'], 10, 2);
        add_action('ppcart_' . $this->service_name . '_integrations', [$this, 'maybe_cancel_subscription'], 10, 3);
    }

    public function add_subscription_service($options)
    {
        $options[$this->service_name] = $this->service_label;
        return $options;
    }

    public function add_integration_fields($fields, $save)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/cancel-subscription-integration-fields.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function maybe_cancel_subscription($int, $ppcart_product_id, $order)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/cancel-subscription-run.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    private function get_products()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/cancel-subscription-products.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    private function get_plans($key)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/cancel-subscription-plans.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    private function get_plan_data($product_id)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/cancel-subscription-plan-data.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
