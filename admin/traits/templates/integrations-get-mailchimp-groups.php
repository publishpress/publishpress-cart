<?php

if (! defined('ABSPATH')) {
    exit;
}

if (!$renew && $groups = get_option('ppcart_mailchimp_groups')) {
    return $groups;
} else {
    $list_data = $this->get_mailchimp_lists();
    $groups = [];
    if ($this->mailchimp_authentication()) {
        foreach ($list_data as $list_id => $list_val) {
            if (! empty($list_id)) {
                $list_path = 'lists/' . ppcart_mailchimp_path_segment($list_id);
                $parent_groups = ppcart_mailchimp_api_request($list_path . '/interest-categories', 'GET', [], ['count' => 100]);
                if (is_wp_error($parent_groups)) {
                    /* translators: %s: error message. */
                    $message = sprintf(__('Mailchimp groups sync error: %s', 'publishpress-cart'), $parent_groups->get_error_message());
                    if (class_exists('PPCart_Debug_Logger')) {
                        PPCart_Debug_Logger::log_debug_st($message, 4);
                    }
                    continue;
                }
                if (isset($parent_groups->categories) && ! empty($parent_groups->categories)) {
                    foreach ($parent_groups->categories as $parent_group) {
                        if (! isset($parent_group->id, $parent_group->title)) {
                            continue;
                        }
                        $groups_id = $parent_group->id;
                        $endpoint = $list_path . '/interest-categories/' . ppcart_mailchimp_path_segment($groups_id) . '/interests';
                        $result = ppcart_mailchimp_api_request($endpoint, 'GET', [], ['count' => 100]);
                        if (! is_wp_error($result) && isset($result->interests) && ! empty($result->interests)) {
                            foreach ($result->interests as $group) {
                                if (isset($group->id, $group->name)) {
                                    $groups[$list_id][$group->id] = $parent_group->title . ' - ' . $group->name;
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    update_option('ppcart_mailchimp_groups', $groups);
    return $groups;
}
