<?php

if (! defined('ABSPATH')) {
    exit;
}


$existing = ppcart_get_post_meta(self::get_order_id_from_charge($charge), 'refund_log', true);
$log = self::normalize_refund_log(is_array($existing) ? $existing : []);
$refunds = self::path($charge, [ 'refunds', 'data' ], []);

if (is_array($refunds)) {
    foreach ($refunds as $refund) {
        $refund_id = self::get($refund, 'id', '');
        if ('' === $refund_id) {
            continue;
        }

        $log[ $refund_id ] = [
            'refundID' => $refund_id,
            'date'     => gmdate('Y-m-d H:i', absint(self::get($refund, 'created', time()))),
            'amount'   => ppcart_format_stripe_number(absint(self::get($refund, 'amount', 0)), $currency),
        ];
    }
}

return $log;
