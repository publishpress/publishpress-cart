<?php

if (! defined('ABSPATH')) {
    exit;
}

if (!$renew && $tags = get_option('ppcart_mailchimp_tags')) {
    return $tags;
} else {
    $tags = [];
    $list_data = $this->get_mailchimp_lists();
    if ($list_data) {
        if ($this->mailchimp_authentication()) {
            foreach ($list_data as $list_id => $list_val) {
                if (! empty($list_id)) {
                    $endpoint = 'lists/' . ppcart_mailchimp_path_segment($list_id) . '/segments';
                    $result = ppcart_mailchimp_api_request($endpoint, 'GET', [], ['count' => 100]);
                    if (is_wp_error($result)) {
                        /* translators: %s: error message. */
                        $message = sprintf(__('Mailchimp tags sync error: %s', 'publishpress-cart'), $result->get_error_message());
                        if (class_exists('PPCart_Debug_Logger')) {
                            PPCart_Debug_Logger::log_debug_st($message, 4);
                        }
                        continue;
                    }
                    if (isset($result->segments) && ! empty($result->segments)) {
                        foreach ($result->segments as $segment) {
                            if (isset($segment->id, $segment->name)) {
                                $tags[$list_id]['tag-' . $segment->id] = $segment->name;
                            }
                        }
                    }
                }
            }
        }
    }
    update_option('ppcart_mailchimp_tags', $tags);
    return $tags;
}
