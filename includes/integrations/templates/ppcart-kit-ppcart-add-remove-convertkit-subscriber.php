<?php

if (! defined('ABSPATH')) {
    exit;
}


global $wpdb;
if (empty($service_id) || empty($action_name) || empty($email)) {
    return;
}
try {
    //CONVERKIT
    $apikey = ppcart_get_sensitive_option('_ppcart_converkit_api');
    $secretKey = ppcart_get_sensitive_option('_ppcart_converkit_secret_key');
    $full_name = $fname . ' ' . $lname;

    // Parse field map
    $custom_fields = [];
    if (isset($fieldmap) && $fieldmap) {
        $maps = explode("\n", str_replace("\r", "", esc_attr($fieldmap)));
        foreach ($maps as $map_line) {
            $option = explode(':', $map_line);
            if (count($option) == 1) {
                $custom_fields[trim($option[0])] = trim($option[0]);
            } else {
                $custom_fields[trim($option[0])] = trim($option[1]);
            }
        }
    }

    // Retrieve custom field values
    $mapped_fields = [];
    $values = ppcart_get_post_meta($order_id, 'custom_fields', true);
    $info = ppcart_webhook_order_body($order_id);

    foreach ($custom_fields as $k => $v) {
        if ($v && is_array($values) && isset($values[$v])) {
            $mapped_fields[$k] = $values[$v]['value'];
        } elseif ($v && isset($info[$v])) {
            $mapped_fields[$k] = $info[$v]; // order data
        } elseif ($v && preg_match('/"([^"]+)"/', html_entity_decode($v), $val)) {
            $mapped_fields[$k] = trim($val[1]); // static value
        } elseif ($v && $val = ppcart_get_post_meta($order_id, $v, true)) {
            $mapped_fields[$k] = $val; // meta key
        }
    }

    //if subscribe
    if ($action_name == 'subscribed') {
        $data = [
            'api_key' => $apikey,
            'first_name' => $fname,
            'email' => $email,
            'fields' => array_merge(['phone' => $phone], $mapped_fields),
        ];
        if (!empty($ppcart_mail_forms)) {
            $url = "https://api.convertkit.com/v3/forms/{$ppcart_mail_forms}/subscribe";
            $log_entry = __('Subscriber added to Kit form.', 'publishpress-cart');
            if (!empty($ppcart_mail_tags)) {
                $log_entry = __('Subscriber added to Kit Form and tagged.', 'publishpress-cart');
                $data["tags"] = (array)$ppcart_mail_tags;
            }

            $response = wp_remote_post($url, [
                'method' => 'POST',
                'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
                'body' => wp_json_encode($data),
            ]);

            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();
                ppcart_log_entry($order_id, "Something went wrong adding subscriber to Kit Form: $error_message");
            } else {
                ppcart_log_entry($order_id, $log_entry);
            }
        } elseif (isset($ppcart_mail_tags) && is_array($ppcart_mail_tags) && is_countable($ppcart_mail_tags) && !empty($ppcart_mail_tags[0])) {
            $url = "https://api.convertkit.com/v3/tags/{$ppcart_mail_tags[0]}/subscribe";
            $data['api_secret'] = $secretKey;
            $response = wp_remote_post($url, [
                'method' => 'POST',
                'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
                'body' => wp_json_encode($data),
            ]);

            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();
                ppcart_log_entry($order_id, "Something went wrong with adding ConvertKit tag: $error_message");
            } else {
                if ($response['response']['code'] === 200) {
                    $log_entry = __('Kit subscriber tagged', 'publishpress-cart');
                    ppcart_log_entry($order_id, $log_entry);
                } else {
                    $error_data = json_decode($response['body'], true);
                    $error_message = $error_data['error'] ?? 'Something went wrong with adding ConvertKit tag';
                    ppcart_log_entry($order_id, "Kit error: $error_message");
                }
            }
        }
    } else { //remove contact
        if (isset($ppcart_mail_tags) && is_array($ppcart_mail_tags) && is_countable($ppcart_mail_tags) && !empty($ppcart_mail_tags[0])) {
            $url = "https://api.convertkit.com/v3/tags/{$ppcart_mail_tags[0]}/unsubscribe";
            $data = ['api_secret' => $secretKey, 'email' => $email];
            $response = wp_remote_post($url, [
                'method' => 'POST',
                'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
                'body' => wp_json_encode($data),
            ]);

            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();
                ppcart_log_entry($order_id, "Something went wrong with adding Kit tag: $error_message");
            } else {
                if ($response['response']['code'] === 200) {
                    $log_entry = __('Kit tag removed from subscriber.', 'publishpress-cart');
                    ppcart_log_entry($order_id, $log_entry);
                } else {
                    $error_data = json_decode($response['body'], true);
                    $error_message = $error_data['error'] ?? 'Something went wrong with adding Kit tag';
                    ppcart_log_entry($order_id, "Kit error: $error_message");
                }
            }
        }
    }
} catch (\Exception $e) {
    return;
}
return;
