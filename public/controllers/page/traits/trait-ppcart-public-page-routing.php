<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Public_Page_Routing_Trait
{
    public function query_vars($qvars)
    {
        foreach ([ 'ppcart-preview', 'ppcart-order', 'ppcart-plan', 'ppcart-manage', 'ppcart-download', 'ppcart-coupon', 'ppcart-pay-plan' ] as $query_var) {
            if (! in_array($query_var, $qvars, true)) {
                $qvars[] = $query_var;
            }
        }

        return $qvars;
    }

    public function hosted_checkout_return()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/page-routing-ppcart-hosted-checkout-return.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function redirect()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/page-routing-ppcart-redirect.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
