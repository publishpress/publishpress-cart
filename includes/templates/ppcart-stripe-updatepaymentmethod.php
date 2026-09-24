<?php

if (! defined('ABSPATH')) {
    exit;
}


if (! $this->stripe()) {
    return false;
}

$response = $this->attachPaymentMethodToCustomer($payment_method, $customer_id);
if (! is_object($response) || empty($response->id)) {
    return false;
}

try {
    $result = $this->setDefaultPaymentMethod($customer_id, $payment_method);

    if (false === $result) {
        return false;
    }

    return $response;
} catch (\PublishPress\Stripe\Exception\InvalidRequestException $e) {
    ppcart_helper()->logException($e, __LINE__, __FILE__);
}

return false;
