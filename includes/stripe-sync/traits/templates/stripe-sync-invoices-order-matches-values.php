<?php

if (! defined('ABSPATH')) {
    exit;
}


foreach ($values as $field => $value) {
    $current = $order->$field ?? null;
    if ('amount' === $field) {
        if ((float) $current !== (float) $value) {
            return false;
        }
        continue;
    }

    if ((string) $current !== (string) $value) {
        return false;
    }
}

return true;
