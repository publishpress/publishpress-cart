<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Admin_Subscription_Info_Trait
{
    private function shorten_payment_reference($reference)
    {
        $reference = (string) $reference;

        if (strlen($reference) <= 18) {
            return $reference;
        }

        return substr($reference, 0, 8) . '...' . substr($reference, -6);
    }

    public function subscription_info_callback($post)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/admin-subscription-info-callback.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
