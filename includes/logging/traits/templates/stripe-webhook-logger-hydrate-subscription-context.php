<?php

if (! defined('ABSPATH')) {
    exit;
}


$subscription = class_exists('PPCart_Subscription') ? new PPCart_Subscription($subscription_id) : null;

if (empty($context['amount'])) {
    $context['amount'] = self::get_record_value($subscription, $subscription_id, 'amount', 'amount');
}

if (empty($context['currency'])) {
    $context['currency'] = self::get_record_value($subscription, $subscription_id, 'currency', 'currency');
}

return self::hydrate_customer_context($subscription, $subscription_id, $context);
