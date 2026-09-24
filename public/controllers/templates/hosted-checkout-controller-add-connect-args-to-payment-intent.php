<?php

if (! defined('ABSPATH')) {
    exit;
}


$connect = $this->get_stripe_connect_config();

if (! $connect['enabled']) {
    return $args;
}

// OAuth access_token keys belong to the connected account, so destination transfer fields are invalid.
if (! empty($connect['is_oauth_access_token_key'])) {
    if ($connect['total_fee_percent'] > 0) {
        $application_fee_amount = (int) round(((float) $amount_for_stripe) * ($connect['total_fee_percent'] / 100));
        if ($application_fee_amount < 1) {
            $application_fee_amount = 1;
        }
        $args['application_fee_amount'] = $application_fee_amount;
    }

    return $args;
}

if (empty($connect['destination'])) {
    return $args;
}

$args['transfer_data'] = [
    'destination' => $connect['destination'],
];

if ($connect['total_fee_percent'] > 0) {
    $application_fee_amount = (int) round(((float) $amount_for_stripe) * ($connect['total_fee_percent'] / 100));
    if ($application_fee_amount < 1) {
        $application_fee_amount = 1;
    }
    $args['application_fee_amount'] = $application_fee_amount;
}

return $args;
