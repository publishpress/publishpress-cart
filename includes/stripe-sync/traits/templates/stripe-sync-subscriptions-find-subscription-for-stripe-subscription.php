<?php

if (! defined('ABSPATH')) {
    exit;
}


$local_id = absint(self::metadata($stripe_sub, 'ppcart_subscription_id', 0));
if ($local_id) {
    $sub = new PPCart_Subscription($local_id);
    if ($sub->id) {
        return $sub;
    }
}

return PPCart_Subscription::get_by_sub_id(self::get($stripe_sub, 'id', ''));
