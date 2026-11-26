<?php

if (! defined('ABSPATH')) {
    exit;
}


$existing = ppcart_get_post_meta(self::get_order_id_from_charge($charge), 'refund_log', true);
$log = self::normalize_refund_log(is_array($existing) ? $existing : []);
foreach (self::refund_entries_from_charge($charge, $currency) as $refund_id => $refund) {
    $log[ $refund_id ] = $refund['entry'];
}

return $log;
