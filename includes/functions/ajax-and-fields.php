<?php

if (! defined('ABSPATH')) {
    exit;
}


function ppcart_json_search_user()
{
    ppcart_check_ajax_referer('ppcart_admin_search_user_nonce', 'nonce');

    if (
        ! current_user_can('manage_options')
        && ! current_user_can('list_users')
        && ! ppcart_user_can('edit_ppcart_orders')
    ) {
        wp_send_json_error([ 'message' => __('Unauthorized.', 'publishpress-cart') ], 403);
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Search term is read after ppcart_check_ajax_referer() and a capability check; the wrapper is invisible to this sniff.
    $search_term = sanitize_text_field(wp_unslash($_GET['term'] ?? ''));
    $usersArr = [];
    if (!empty($search_term)) {
        $users = get_users(
            [ 'search' => '*' . $search_term . '*',
                            'search_columns' => [ 'user_login', 'user_email','display_name' ],
                        ]
        );
        if (! empty($users)) {
            foreach ((array) $users as $user) {
                $usersArr[$user->ID] = $user->display_name . ' (' . $user->user_email . ')';
            }
        }
    }
    wp_send_json($usersArr);
}

add_filter('ppcart_order_form_fields', 'ppcart_default_fields_filter', 10, 2);
add_filter('ppcart_order_form_address_fields', 'ppcart_default_fields_filter', 10, 2);
function ppcart_default_fields_filter($fields, $ppcart_product)
{
    $new_fields = [];
    if (isset($ppcart_product->default_fields)) {
        foreach ($ppcart_product->default_fields as $k => $f) {
            $key = str_replace('_', '', $k);
            if (isset($fields[$key])) {
                $field = $fields[$key];
                if (!isset($ppcart_product->default_fields[$k]['default_field_disabled'])) {
                    $field['label'] = $ppcart_product->default_fields[$k]['default_field_label'];
                    $field['required'] = isset($ppcart_product->default_fields[$k]['default_field_required']);
                    $field['cols'] = $ppcart_product->default_fields[$k]['default_field_size'];

                    if ($k == 'state') {
                        $field['choices'][''] = $field['label'];
                    }

                    $new_fields[$k] = $field;
                }
            }
        }
        return $new_fields;
    } elseif (isset($ppcart_product->hide_fields)) { // backwards compatibility
        foreach ($fields as $k => $f) {
            if (isset($ppcart_product->hide_fields) && isset($ppcart_product->hide_fields[$k])) {
                unset($fields[$k]);
            }
        }
    }
    return $fields;
}

/**
 * Add or remove an ActiveCampaign subscriber for an order integration.
 *
 * @param int    $order_id           Order post ID.
 * @param string $service_id         Integration service identifier.
 * @param string $action_name        Subscribe or unsubscribe action.
 * @param string $list_id            ActiveCampaign list ID.
 * @param string $ppcart_mail_tags   Tag list.
 * @param string $ppcart_mail_groups Group list.
 * @param string $email              Customer email.
 * @param string $phone              Customer phone.
 * @param string $fname              First name.
 * @param string $lname              Last name.
 * @param array  $fieldmap           Custom field map.
 * @return void
 */
function ppcart_add_remove_activecampaign_subscriber($order_id, $service_id, $action_name, $list_id, $ppcart_mail_tags, $ppcart_mail_groups, $email, $phone, $fname, $lname, $fieldmap)
{
    global $wpdb;

    if (empty($service_id) || empty($action_name) || empty($email)) {
        return;
    }

    $activecampaign_url = get_option('_ppcart_activecampaign_url');
    $activecampaign_secret_key = ppcart_get_sensitive_option('_ppcart_activecampaign_secret_key');
    if (empty($activecampaign_url) || empty($activecampaign_secret_key)) {
        return;
    }

    $activecampaign_url = rtrim($activecampaign_url, '/');
    $headers = [
        'Api-Token' => $activecampaign_secret_key,
        'Content-Type' => 'application/json',
    ];

    $contact_payload = [
        'contact' => [
            'email' => $email,
            'firstName' => $fname,
            'lastName' => $lname,
            'phone' => $phone,
        ],
    ];

    if (isset($fieldmap) && $fieldmap) {
        $custom = [];
        $maps = explode("\n", str_replace("\r", "", esc_attr($fieldmap)));
        foreach ($maps as $map) {
            $option = explode(':', $map);
            if (count($option) == 1) {
                $custom[trim($option[0])] = trim($option[0]);
            } else {
                $custom[trim($option[0])] = trim($option[1]);
            }
        }

        $field_values = [];
        $values = ppcart_get_post_meta($order_id, 'custom_fields', true);
        $info = ppcart_webhook_order_body($order_id);

        foreach ($custom as $k => $v) {
            $field_id_parts = explode(',', $k, 2);
            $field_id = trim($field_id_parts[0]);
            if ($v && is_array($values) && isset($values[$v])) {
                $field_values[] = ['field' => $field_id, 'value' => $values[$v]['value']];
            } elseif ($v && isset($info[$v])) {
                $field_values[] = ['field' => $field_id, 'value' => $info[$v]];
            } elseif ($v && preg_match('/"([^"]+)"/', html_entity_decode($v), $val)) {
                $field_values[] = ['field' => $field_id, 'value' => trim($val[1])];
            } elseif ($v && $val = ppcart_get_post_meta($order_id, $v, true)) {
                $field_values[] = ['field' => $field_id, 'value' => $val];
            }
        }

        if (!empty($field_values)) {
            $contact_payload['contact']['fieldValues'] = $field_values;
        }
    }

    $response = wp_remote_post($activecampaign_url . '/api/3/contact/sync', [
        'headers' => $headers,
        // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout -- Third-party API calls may legitimately require longer than 3 seconds.
        'timeout' => 10,
        'body' => wp_json_encode($contact_payload),
    ]);

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) >= 300) {
        return;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    $contact_id = $body['contact']['id'] ?? null;
    if (empty($contact_id)) {
        return;
    }

    if (!empty($list_id)) {
        $log_entry = $email  . __(' activecampaign Listed: ', 'publishpress-cart') . $list_id ;
        ppcart_log_entry($order_id, $log_entry);
        $list_id_array = explode(',', $list_id);
        foreach ($list_id_array as $list_id) {
            $list_id = str_replace('list-', '', $list_id);
            $status = ($action_name == 'subscribed') ? 1 : 2;
            $list_payload = [
                'contactList' => [
                    'list' => $list_id,
                    'contact' => $contact_id,
                    'status' => $status,
                ],
            ];
            wp_remote_post($activecampaign_url . '/api/3/contactLists', [
                'headers' => $headers,
                // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout -- Third-party API calls may legitimately require longer than 3 seconds.
                'timeout' => 10,
                'body' => wp_json_encode($list_payload),
            ]);
        }
    }

    if (!empty($ppcart_mail_tags)) {
        $ac_tags = array_map('trim', explode(',', $ppcart_mail_tags));
        $find_tag_id = function ($tag_name) use ($activecampaign_url, $headers) {
            $response = wp_safe_remote_get($activecampaign_url . '/api/3/tags?search=' . rawurlencode($tag_name), [
                'headers' => $headers,
                // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout -- Third-party API calls may legitimately require longer than 3 seconds.
                'timeout' => 10,
            ]);
            if (is_wp_error($response) || wp_remote_retrieve_response_code($response) >= 300) {
                return null;
            }
            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (!empty($body['tags'])) {
                foreach ($body['tags'] as $tag) {
                    if (isset($tag['tag']) && strtolower($tag['tag']) === strtolower($tag_name)) {
                        return $tag['id'];
                    }
                }
            }
            return null;
        };

        foreach ($ac_tags as $tag_name) {
            $tag_id = $find_tag_id($tag_name);
            if (!$tag_id && $action_name == 'subscribed') {
                $create = wp_remote_post($activecampaign_url . '/api/3/tags', [
                    'headers' => $headers,
                    // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout -- Third-party API calls may legitimately require longer than 3 seconds.
                    'timeout' => 10,
                    'body' => wp_json_encode(['tag' => ['tag' => $tag_name, 'tagType' => 'contact']]),
                ]);
                if (!is_wp_error($create) && wp_remote_retrieve_response_code($create) < 300) {
                    $created = json_decode(wp_remote_retrieve_body($create), true);
                    $tag_id = $created['tag']['id'] ?? null;
                }
            }

            if (!$tag_id) {
                continue;
            }

            if ($action_name == 'subscribed') {
                wp_remote_post($activecampaign_url . '/api/3/contactTags', [
                    'headers' => $headers,
                    // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout -- Third-party API calls may legitimately require longer than 3 seconds.
                    'timeout' => 10,
                    'body' => wp_json_encode(['contactTag' => ['contact' => $contact_id, 'tag' => $tag_id]]),
                ]);
                $log_entry = $email  . __(' activecampaign tagged: ', 'publishpress-cart') . $ppcart_mail_tags ;
            } else {
                $existing = wp_safe_remote_get($activecampaign_url . '/api/3/contactTags?contact=' . rawurlencode($contact_id) . '&tag=' . rawurlencode($tag_id), [
                    'headers' => $headers,
                    // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout -- Third-party API calls may legitimately require longer than 3 seconds.
                    'timeout' => 10,
                ]);
                if (!is_wp_error($existing) && wp_remote_retrieve_response_code($existing) < 300) {
                    $existing_body = json_decode(wp_remote_retrieve_body($existing), true);
                    if (!empty($existing_body['contactTags'])) {
                        foreach ($existing_body['contactTags'] as $contact_tag) {
                            wp_remote_request($activecampaign_url . '/api/3/contactTags/' . $contact_tag['id'], [
                                'headers' => $headers,
                                // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout -- Third-party API calls may legitimately require longer than 3 seconds.
                                'timeout' => 10,
                                'method' => 'DELETE',
                            ]);
                        }
                    }
                }
                $log_entry = $email  . __(' activecampaign tag removed: ', 'publishpress-cart') . $ppcart_mail_tags ;
            }
            ppcart_log_entry($order_id, $log_entry);
        }
    }
    return;
}


function ppcart_add_remove_sendfox_subscriber($order_id, $ppcart_services, $ppcart_service_action, $sendfox_list, $customerEmail, $first_name, $last_name)
{
    if (empty($sendfox_list)) {
        return;
    }

    if ($ppcart_service_action == 'subscribed') {
        $contact = [
            'email' => $customerEmail,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'lists' => [
                $sendfox_list,
            ],
        ];

        $response = ppcart_sendfox_api_request('contacts', $contact, 'POST');

        if (
            !empty($response['status']) &&
            $response['status'] === 'success' &&

            !empty($response['result']) &&
            !empty($response['result']['id']) &&
            empty($response['result']['invalid_at'])
        ) {
            $msg = __('Contact successfully added to SendFox list ID: ', 'publishpress-cart') . $sendfox_list;
        } else {
            $msg = __('Error adding contact to SendFox: ', 'publishpress-cart') . $response['error_text'];
        }
    } else {
        $response = ppcart_sendfox_api_request("contacts?email={$customerEmail}");
        if (
            !empty($response['status']) &&
            $response['status'] === 'success' &&

            !empty($response['result']) &&
            !empty($response['result']['data'][0]['id'])
        ) {
            $contact_id = $response['result']['data'][0]['id'];
            $response = ppcart_sendfox_api_request("lists/{$sendfox_list}/contacts/{$contact_id}", [], 'DELETE');

            if (
                !empty($response['status']) &&
                $response['status'] === 'success' &&

                !empty($response['result']) &&
                !empty($response['result']['id']) &&
                empty($response['result']['invalid_at'])
            ) {
                $msg = __('Contact successfully removed from SendFox, list ID: ', 'publishpress-cart') . $sendfox_list;
            } else {
                $msg = __('Error removing contact from SendFox: ', 'publishpress-cart') . $response['error_text'];
            }
        } else {
            $msg = __('Unsubscribe failed because this email wasn\'t found in SendFox list: ', 'publishpress-cart') . $sendfox_list;
        }
    }

    ppcart_log_entry($order_id, $msg);
    return;
}


function ppcart_add_remove_mailpoet_subscriber($order_id, $ppcart_services, $ppcart_service_action, $mailpoet_list, $customerEmail, $first_name, $last_name)
{

    if (empty($mailpoet_list) || !class_exists('MailPoet\API\API')) {
        return;
    }

    $mailpoet_api = \MailPoet\API\API::MP('v1');
    $mailpoet_lists = [$mailpoet_list];

    $subscriber = [
        'email' => $customerEmail,
        'first_name' => $first_name,
        'last_name' => $last_name,
    ];

    if ($ppcart_service_action == 'subscribed') {
        // Check if subscriber exists. If subscriber doesn't exist an exception is thrown
        try {
            $get_subscriber = $mailpoet_api->getSubscriber($subscriber['email']);
        } catch (\Exception $e) {
        }

        try {
            if (!$get_subscriber) {
                // Subscriber doesn't exist let's create one
                $mailpoet_api->addSubscriber($subscriber, $mailpoet_lists);
            } else {
                // In case subscriber exists just add him to new lists
                $mailpoet_api->subscribeToLists($subscriber['email'], $mailpoet_lists, ['send_confirmation_email' => false]);
            }
            $msg = __('Contact successfully added to MailPoet list ID: ', 'publishpress-cart') . $mailpoet_list;
        } catch (\Exception $e) {
            $error_message = $e->getMessage();
            $msg = __('Error adding contact to MailPoet: ', 'publishpress-cart') . $error_message;
        }
    } else {
        // Check if subscriber exists. If subscriber doesn't exist, exit early.
        try {
            $get_subscriber = $mailpoet_api->getSubscriber($subscriber['email']);
        } catch (\Exception $e) {
            $error_message = $e->getMessage();
            $msg = __('Error removing contact from MailPoet: ', 'publishpress-cart') . esc_html($error_message);
            ppcart_log_entry($order_id, $msg);
            return;
        }

        try {
            if ($get_subscriber) {
                $mailpoet_api->unsubscribeFromList($subscriber['email'], $mailpoet_list);
            }
            $msg = __('Contact successfully removed from MailPoet list ID: ', 'publishpress-cart') . $mailpoet_list;
        } catch (\Exception $e) {
            $error_message = $e->getMessage();
            $msg = __('Error removing contact from MailPoet: ', 'publishpress-cart') . $error_message;
        }
    }
    ppcart_log_entry($order_id, $msg);
    return;
}

/**
 * DOM id for admin field partials (repeater rows use html_id; metabox fields use id).
 *
 * @param array $atts Field attributes.
 * @return string
 */
function ppcart_admin_field_dom_id($atts)
{
    if (! empty($atts['html_id'])) {
        return (string) $atts['html_id'];
    }

    return isset($atts['id']) ? (string) $atts['id'] : '';
}

/**
 * Set a unique DOM id for a field inside a repeater row.
 *
 * @param array  $atts            Field attributes (passed by reference).
 * @param string $repeater_set_id Repeater set id ($setatts['id']).
 * @param string $row_key         Row index or "hidden" for the clone template.
 */
function ppcart_admin_field_set_repeater_html_id(&$atts, $repeater_set_id, $row_key)
{
    $atts['html_id'] = 'ppcart-' . sanitize_html_class($repeater_set_id . '-' . $atts['id'] . '-' . $row_key);
}
