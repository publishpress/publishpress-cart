<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Public_Tracking_Assets_Trait
{
    public function enqueue_scripts()
    {
        include __DIR__ . '/../templates/enqueue-tracking-scripts.php';
    }

    public function js_order_tracking()
    {
        include __DIR__ . '/../templates/order-tracking-script.php';
    }

    public function ga_purchase_tracking($order)
    {
        include __DIR__ . '/../templates/ga-purchase-tracking.php';
    }
}
