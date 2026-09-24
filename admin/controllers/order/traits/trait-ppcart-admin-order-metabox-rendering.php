<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Admin_Order_Metabox_Rendering_Trait
{
    public function product_info_callback($post)
    {
        include __DIR__ . '/../templates/product-info-metabox.php';
    }

    private function shorten_payment_reference($reference)
    {
        $reference = (string) $reference;

        if (strlen($reference) <= 18) {
            return $reference;
        }

        return substr($reference, 0, 8) . '...' . substr($reference, -6);
    }
}
