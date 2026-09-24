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
        $args['application_fee_percent'] = round((float) $connect['total_fee_percent'], 2);
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
    $args['application_fee_percent'] = round((float) $connect['total_fee_percent'], 2);
}

return $args;
