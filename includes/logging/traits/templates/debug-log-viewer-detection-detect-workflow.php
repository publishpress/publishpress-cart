<?php

if (! defined('ABSPATH')) {
    exit;
}


$event = isset($context['event']) ? strtolower((string) $context['event']) : '';
$text  = strtolower($message . ' ' . $event);

$workflow_map = [
    'Checkout'     => [ 'checkout', 'form validation', 'purchase nonce', 'submission' ],
    'Stripe'       => [ 'stripe', 'paymentintent', 'payment intent', 'charge_', 'pi_', 'sub_' ],
    'PayPal'       => [ 'paypal', 'ipn' ],
    'Order'        => [ 'order', 'scrtorder' ],
    'Subscription' => [ 'subscription', 'scrtsubscription' ],
    'Integration'  => [ 'integration', 'triggering integrations' ],
    'Email'        => [ 'email', 'mail' ],
    'Download'     => [ 'download', 'file' ],
    'Tax'          => [ 'tax' ],
    'Security'     => [ 'nonce', 'recaptcha', 'security' ],
];

foreach ($workflow_map as $workflow => $needles) {
    foreach ($needles as $needle) {
        if (false !== strpos($text, $needle)) {
            return $workflow;
        }
    }
}

return 'General';
