<?php

if (! defined('ABSPATH')) {
    exit;
}


foreach ($group['entries'] as $entry) {
    $context = isset($entry['context']) && is_array($entry['context']) ? $entry['context'] : [];
    $previous_status = isset($context['previous_status']) ? (string) $context['previous_status'] : '';
    $next_status     = isset($context['status']) ? (string) $context['status'] : (isset($context['next_status']) ? (string) $context['next_status'] : '');
    if (! $previous_status || $previous_status !== $next_status) {
        continue;
    }

    if (
        isset($context['previous_transaction_id'], $context['transaction_id'])
        && (string) $context['previous_transaction_id'] !== (string) $context['transaction_id']
    ) {
        return true;
    }

    if (
        isset($context['previous_transaction_id'], $context['next_transaction_id'])
        && (string) $context['previous_transaction_id'] !== (string) $context['next_transaction_id']
    ) {
        return true;
    }
}

return false;
