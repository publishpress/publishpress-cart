<?php

if (! defined('ABSPATH')) {
    exit;
}


$normalized = [];
foreach ($entries as $key => $entry) {
    if (! is_array($entry)) {
        continue;
    }

    $original_key = is_string($key) ? $key : '';
    $refund_id = isset($entry['refundID']) && '' !== $entry['refundID'] ? (string) $entry['refundID'] : $original_key;
    if ('manual' === $refund_id && '' !== $original_key && 'manual' !== $original_key) {
        $refund_id = $original_key;
    }

    if ('' === $refund_id) {
        continue;
    }

    $normalized[ $refund_id ] = $entry;
}

return $normalized;
