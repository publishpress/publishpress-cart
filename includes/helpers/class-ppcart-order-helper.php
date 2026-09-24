<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Class for reusable order functions.
 *
 * @link https://publishpress.com/
 * @since 1.0.0
 *
 * @package PPCart_Order_Helper
 * @author PublishPress <help@publishpress.com>
 */

class PPCart_Order_Helper
{
    /**
     * The single instance of the class.
     *
     * @var PPCart_Order_Helper
     * @since 1.0.0
     */

    protected static $_instance = null;

    public $order = null;

    /**
     * PPCart_Order_Helper class Instance
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Check if order has bump product
     */
    public function is_bump()
    {

        $bump_plan = false;
        if (is_array($this->order->order_bumps)) {
            foreach ($this->order->order_bumps as $bump) {
                // do order bumps have a subscription?
                if (isset($bump['plan'])) {
                    $bump_plan = true;
                    break;
                }
            }
        }
        return $bump_plan;
    }

    /**
     * Check if tax has been applied
     */
    public function tax_applied()
    {
    }
}
