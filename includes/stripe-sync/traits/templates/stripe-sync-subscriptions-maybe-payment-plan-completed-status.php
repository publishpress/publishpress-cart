<?php

if (! defined('ABSPATH')) {
    exit;
}


if (
    'canceled' === $mapped_status
    && $sub->sub_installments > 1
    && $sub->order_count('paid') >= $sub->sub_installments
    && strtotime($sub->sub_end_date) <= strtotime(gmdate('Y-m-d'))
) {
    return 'completed';
}

return $mapped_status;
