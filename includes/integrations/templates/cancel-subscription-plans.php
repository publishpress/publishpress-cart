<?php

if (!defined('ABSPATH')) {
    exit;
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin integration editor reads current post context from query parameter.
if (!isset($_GET['post'])) {
    return;
}
$options = [];
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin integration editor reads current post context from query parameter.
$current_post_id = intval($_GET['post']);

if ($integrations = ppcart_get_post_meta($current_post_id, 'integrations', true)) {
    if (is_array($integrations)) {
        foreach ($integrations as $integration) {
            if (isset($integration[$key])) {
                $pid = $integration[$key];
                $options = $this->get_plan_data($pid);
            }
        }
    }
}

return $options;
