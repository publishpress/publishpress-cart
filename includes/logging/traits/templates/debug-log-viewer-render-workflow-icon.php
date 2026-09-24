<?php

if (! defined('ABSPATH')) {
    exit;
}


$workflow = (string) $workflow;
$slug     = strtolower(preg_replace('/[^A-Za-z0-9_-]/', '-', $workflow));
$svg_path = '<path d="M6 7h12l-1 9H8L6 7Z" /><path d="M6 7 5 4H3" /><circle cx="9" cy="20" r="1.5" /><circle cx="16" cy="20" r="1.5" />';

if ('Stripe' === $workflow) {
    $svg_path = '<rect x="4" y="6" width="16" height="12" rx="2" /><path d="M4 10h16" /><path d="M7 15h4" />';
} elseif ('Subscription' === $workflow) {
    $svg_path = '<path d="M17 2v4h-4" /><path d="M7 22v-4h4" /><path d="M19 11a7 7 0 0 0-11.9-5" /><path d="M5 13a7 7 0 0 0 11.9 5" />';
} elseif ('Integration' === $workflow) {
    $svg_path = '<path d="M8 12h8" /><path d="M12 8v8" /><circle cx="12" cy="12" r="8" />';
} elseif ('Security' === $workflow) {
    $svg_path = '<path d="M12 3 5 6v6c0 4 3 7 7 9 4-2 7-5 7-9V6l-7-3Z" /><path d="m9 12 2 2 4-4" />';
} elseif ('Email' === $workflow) {
    $svg_path = '<rect x="3" y="5" width="18" height="14" rx="2" /><path d="m4 7 8 6 8-6" />';
} elseif ('Download' === $workflow) {
    $svg_path = '<path d="M12 3v12" /><path d="m7 10 5 5 5-5" /><path d="M5 21h14" />';
}

return '<span class="ppcart-debug-log-group-icon ppcart-debug-log-group-icon--' . esc_attr($slug) . '" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false">' . $svg_path . '</svg></span>';
