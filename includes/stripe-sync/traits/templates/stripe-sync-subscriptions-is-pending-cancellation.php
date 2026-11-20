<?php

if (! defined('ABSPATH')) {
    exit;
}


$cancel_at = absint($cancel_at);
if (! $cancel_at) {
    return false;
}

// A payment plan that ends on, or after, its planned end date is finishing, not canceling.
if ((int) $sub->sub_installments > 1 && ! empty($sub->sub_end_date)) {
    $planned_end = strtotime((string) $sub->sub_end_date);
    if ($planned_end && $cancel_at >= $planned_end) {
        return false;
    }
}

return true;
