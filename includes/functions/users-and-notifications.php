<?php

if (! defined('ABSPATH')) {
    exit;
}


function ppcart_maybe_format_date($str, $format = false)
{
    if (!$str) {
        return $str;
    }
    if (!is_numeric($str)) {
        $str = strtotime($str);
    }
    if (!$format) {
        return date_i18n(get_option('date_format'), $str);
    } else {
        return date_i18n($format, $str);
    }
}

function ppcart_get_order_user_id($order, $create = false)
{

    $id = $order['id'] ?? $order['ID'];
    $user_id = email_exists($order['email']);

    if (!$user_id && $create) {
        $default_values = [
            'send_email'    => null,
            'user_role'     => 'subscriber',
        ];
        if (is_array($create)) {
            $args = wp_parse_args($create, $default_values);
        } else {
            $args = $default_values;
        }
        $user_id = ppcart_create_user($id, $order['email'], $order['first_name'], $order['last_name'], $args['user_role'], $args['send_email']);
    }

    return $user_id;
}

function ppcart_generate_login_creds($customerEmail, $password = false)
{

    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Checkout context reads posted form payload for credential generation.
    $pid = isset($_POST['ppcart_product_id']) ? absint(wp_unslash($_POST['ppcart_product_id'])) : 0;
    $ppcart_product = ppcart_setup_product($pid);

    if (!$password) {
        $password = wp_generate_password();
    }
    $username = $customerEmail;

    if (is_countable($ppcart_product->custom_fields)) {
        foreach ($ppcart_product->custom_fields as $field) {
            $key = str_replace([' ','.'], ['_','_'], $field['field_id']);
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Checkout context reads posted custom-field payload.
            if (isset($_POST['ppcart_custom_fields'][$key])) {
                if ($field['field_type'] == 'password') {
                    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Checkout context reads posted form payload for credential generation.
                    $password = sanitize_text_field(wp_unslash($_POST['ppcart_custom_fields'][$key]));
                } elseif (isset($field['field_username'])) {
                    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Checkout context reads posted form payload for credential generation.
                    $username = sanitize_text_field(wp_unslash($_POST['ppcart_custom_fields'][$key]));
                }
            }
        }
    }

    $creds = [
        'username' => $username,
        'password' => $password,
    ];

    return $creds;
}

function ppcart_create_user($order_id, $customerEmail, $first_name, $last_name, $user_role = '', $send_email_override = null, $change_roles = false)
{

    $sub_id = false;
    if (ppcart_is_order_post_type(get_post_type($order_id))) {
        $sub_id = ppcart_get_post_meta($order_id, 'subscription_id', true);
    }

    if ($user_id = email_exists($customerEmail)) {
        $u = new WP_User($user_id);
        ppcart_update_post_meta($order_id, 'user_account', $user_id);

        // add user to subscription if exists
        if ($sub_id) {
            ppcart_update_post_meta($sub_id, 'user_account', $user_id);
        }

        // Update user role
        if (is_countable($change_roles) && is_countable($u->roles)) {
            foreach ($u->roles as $role) {
                if (in_array($role, $change_roles)) {
                    $u->set_role($user_role);
                }
            }
            /* translators: 1: user ID, 2: role name. */
            ppcart_log_entry($order_id, sprintf(__('Role for User ID: %1$s updated to %2$s', 'publishpress-cart'), $user_id, $user_role));
        }
    } else {
        $creds = ppcart_generate_login_creds($customerEmail);
        $user_data = [
            'user_login' =>  $creds['username'],
            'first_name' =>  $first_name,
            'last_name'  =>  $last_name,
            'user_email' =>  $customerEmail,
            'user_pass'  =>  $creds['password'],
            'role'       =>  $user_role,
        ];
        $user_id = wp_insert_user($user_data);

        // add new user account to order info
        if ($user_id && $order_id) {
            /* translators: %s: user ID. */
            $msg = sprintf(__("New user created (ID: %s)", 'publishpress-cart'), $user_id);
            ppcart_update_post_meta($order_id, 'user_account', $user_id);

            // add user to subscription if exists
            if ($sub_id) {
                ppcart_update_post_meta($sub_id, 'user_account', $user_id);
            }

            ppcart_log_entry($order_id, $msg);
            do_action('ppcart_after_user_is_created', $user_id, $order_id);

            // send notification email
            $send_email = ($send_email_override !== null) ? $send_email_override : (bool) get_option('_ppcart_email_registration_enable');
            $send_email = apply_filters('ppcart_send_new_user_email', $send_email, $order_id);
            if ($send_email) {
                if (get_option('_ppcart_use_wp_notification')) {
                    if (get_option('_ppcart_registration_email_admin')) {
                        wp_new_user_notification($user_id, null, 'both');
                    } else {
                        wp_new_user_notification($user_id, null, 'user');
                    }
                } else {
                    ppcart_new_user_notification($user_data, $order_id);
                }
            }
        }
    }

    return $user_id;
}

function ppcart_new_user_notification($user, $order_id, $test = false)
{

    $from_name = get_option('_ppcart_email_from_name', '');
    $from_email = get_option('_ppcart_email_from_email', '');
    $subject = get_option('_ppcart_registration_subject', '');
    $body = get_option('_ppcart_registration_email_body', '');

    if (!$test) {
        $order_info = (array) ppcart_setup_order($order_id);
        $order_info['username'] = $user['user_login'];
        $order_info['password'] = $user['user_pass'];

        $subject = ppcart_personalize($subject, $order_info);
        $body = ppcart_personalize($body, $order_info, false, true, true);
        $to = $user['user_email'];
    } else {
        $order_info = $order_id;
    }

    $atts = [
        'type' => 'registration',
        'order_info' => $order_info,
        'headline' => '',
        'body' => $body,
    ];

    $body = ppcart_get_email_html($atts);

    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $from_name . ' <' . $from_email . '>',
    ];

    if (!$test) {
        // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_mail_wp_mail -- Transactional customer registration email.
        wp_mail($to, $subject, $body, $headers);
    }

    if (get_option('_ppcart_registration_email_admin') || $test) {
        if (!$admin_email = get_option('ppcart_admin_email')) {
            $admin_email = get_option('admin_email');
        }

        $to = apply_filters('ppcart_admin_notification_email', $admin_email, $order_info);
        // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_mail_wp_mail -- Transactional admin registration email.
        $res = wp_mail($to, $subject, $body, $headers);

        if ($test) {
            return $res;
        }
    }
}

function ppcart_notification_send($status, $order_info, $test = false)
{

    switch ($status) {
        case 'paid':
            $type = 'confirmation';
            break;
        default:
            $type = $status;
            break;
    }

    if ($type && ($test || get_option('_ppcart_email_' . $type . '_enable'))) {
        $em = '_ppcart_email_' . $type . '_';
        $from_name = get_option('_ppcart_email_from_name', '');
        $from_email = get_option('_ppcart_email_from_email', '');
        $subject = get_option($em . 'subject', '');
        $headline = get_option($em . 'headline', '');
        $body = get_option($em . 'body', '');

        if (!$test) {
            if (is_numeric($order_info)) {
                if (!in_array($status, ['completed','active','paused','canceled','past_due'])) {
                    $order_info = new PPCart_Order($order_info);
                } else {
                    $order_info = new PPCart_Subscription($order_info);
                }
                $order_info = $order_info->get_data();
            }
        }

        $subject = ppcart_personalize($subject, $order_info);
        $headline = ppcart_personalize($headline, $order_info);
        $body = ppcart_personalize($body, $order_info, false, true, true);

        $atts = [
            'type' => $type,
            'order_info' => $order_info,
            'headline' => $headline,
            'body' => $body,
        ];

        $to = apply_filters('ppcart_notification_email_to', trim($order_info['email']), $type, $order_info);
        $body = ppcart_get_email_html($atts);
        $attachments = [];

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>',
        ];

        $attach = get_option('_ppcart_invoice_attach_' . $type . '_email');

        if ($attach) {
            $order = new PPCart_Order($order_info['id']);
            $invoice = $order->get_invoice();

            // A missing or unwritable invoice must never block the notification itself.
            if (is_string($invoice) && '' !== $invoice && file_exists($invoice)) {
                $attachments[] = $invoice;
            }
        }

        if (!$test) {
            // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_mail_wp_mail -- Transactional order/subscription notification email.
            wp_mail($to, $subject, $body, $headers, $attachments);
        } else {
            /* translators: %s: email subject. */
            $subject = sprintf(__('Test: %s', 'publishpress-cart'), $subject);
        }

        if (get_option($em . 'admin') || $test) {
            if (!$admin_email = get_option('ppcart_admin_email')) {
                $admin_email = get_option('admin_email');
            }
            $to = apply_filters('ppcart_admin_notification_email', $admin_email, $order_info);
            // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_mail_wp_mail -- Transactional admin notification email.
            $res = wp_mail($to, $subject, $body, $headers, $attachments);

            if ($test) {
                return $res;
            }
        }
    }
}

function ppcart_get_email_html($atts)
{
    ob_start();
    ppcart_helper()->renderTemplate('email/email-main', $atts);
    $output_string = ob_get_contents();
    ob_end_clean();
    return $output_string;
}

/**
 * Composes one additional purchase notification email, shared by the live send, preview, and test-send.
 *
 * @param array $n          A single `_ppcart_notifications` entry.
 * @param array $order_info Order/sample data used for personalization tags.
 * @return array{to:string,subject:string,body:string,headers:string[]}
 */
function ppcart_build_product_notification_email($n, $order_info)
{
    if (!$admin_email = get_option('ppcart_admin_email')) {
        $admin_email = get_option('admin_email');
    }

    $n = is_array($n) ? $n : [];
    foreach (['send_to', 'send_to_email', 'from_name', 'from_email', 'subject', 'message', 'reply_to', 'bcc'] as $key) {
        $n[$key] ??= '';
    }

    switch ($n['send_to']) {
        case 'enter':
            $to = wp_specialchars_decode(ppcart_personalize($n['send_to_email'], $order_info));
            break;
        case 'purchaser':
            $to = $order_info['email'] ?? '';
            break;
        default:
            $to = $admin_email;
            break;
    }

    $from_name = ($n['from_name']) ? ($n['from_name']) : get_bloginfo('name');
    $from_email = ($n['from_email']) ? ($n['from_email']) : get_option('admin_email');
    // Cast: ppcart_personalize() returns null for empty input (avoids PHP 8.1 null-arg deprecations).
    $subject = wp_specialchars_decode((string) ppcart_personalize($n['subject'], $order_info));
    // Decode stored template entities before merge so later kses is not undone.
    $message = wp_specialchars_decode((string) $n['message'], ENT_QUOTES);
    $message = (string) ppcart_personalize($message, $order_info, false, true, true);
    $download_tags = apply_filters('ppcart_order_download_shortcode_tags', [ '[ppcart_order_downloads]' ]);
    if (! is_array($download_tags)) {
        $download_tags = [ '[ppcart_order_downloads]' ];
    }
    $download_tags = array_values(
        array_filter(
            $download_tags,
            static function ($tag) {
                return is_string($tag) && isset($tag[0]) && '[' === $tag[0];
            }
        )
    );
    $has_download_tag = false;
    foreach ($download_tags as $download_tag) {
        if (false !== strpos($message, $download_tag)) {
            $has_download_tag = true;
            break;
        }
    }
    if (isset($order_info['ID']) && $has_download_tag) {
        $files_class = new PPCart_Files();
        $shortcode_output = $files_class->render_order_downloads_html(
            [
                'id'         => intval($order_info['ID']),
                'full'       => false,
                'show-title' => true,
            ]
        );
        $message = str_replace($download_tags, (string) $shortcode_output, $message);
    }
    $body = wpautop($message, false);
    $body = ppcart_kses_email_html($body);
    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $from_name . ' <' . $from_email . '>',
    ];

    if ($n['reply_to']) {
        $headers[] = 'Reply-To: ' . $n['reply_to'];
    }

    if ($n['bcc']) {
        $headers[] = 'Bcc: ' . $n['bcc'];
    }

    return [
        'to'      => $to,
        'subject' => $subject,
        'body'    => $body,
        'headers' => $headers,
    ];
}

/**
 * Sends a single additional purchase notification email.
 *
 * @param array       $n           A single `_ppcart_notifications` entry.
 * @param array       $order_info  Order/sample data used for personalization tags.
 * @param string|null $to_override When provided, overrides the resolved recipient (used for test sends).
 *
 * @return bool Whether the email was accepted for delivery.
 */
function ppcart_send_product_notification($n, $order_info, $to_override = null)
{
    $email = ppcart_build_product_notification_email($n, $order_info);

    $to = (null !== $to_override && '' !== $to_override) ? $to_override : $email['to'];

    // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_mail_wp_mail -- Transactional custom notification email.
    return wp_mail($to, $email['subject'], $email['body'], $email['headers']);
}

/**
 * Interprets an explicit stored "enabled" value from a repeater row.
 *
 * Missing keys are handled by the caller. Only values that clearly mean
 * off — 0, "0", false, "false", "off", "no", or an empty string — count as
 * disabled. Everything else stays enabled.
 *
 * @param mixed $value Stored enabled value.
 * @return bool
 */
function ppcart_value_is_enabled($value)
{
    if (is_bool($value)) {
        return $value;
    }

    if (is_int($value) || is_float($value)) {
        return 0 !== (int) $value;
    }

    $value = strtolower(trim((string) $value));

    return ! in_array($value, ['0', 'false', 'off', 'no', ''], true);
}

/**
 * Whether a saved repeater entry should run.
 *
 * Entries that predate the enabled field have no key and stay on, so
 * existing notifications and confirmations keep working after upgrade.
 *
 * @param mixed  $entry Repeater row.
 * @param string $key   Enabled field name.
 * @return bool
 */
function ppcart_repeater_entry_is_enabled($entry, $key = 'enabled')
{
    if (! is_array($entry) || ! array_key_exists($key, $entry)) {
        return true;
    }

    return ppcart_value_is_enabled($entry[$key]);
}

/**
 * Sends the configured product notifications for an order.
 *
 * @param array $order_info Order data used to resolve the product and personalize notifications.
 * @return void
 */
function ppcart_do_notifications($order_info)
{

    $notifications = ppcart_get_post_meta($order_info['product_id'], 'notifications', true); //get integration meta mailchimp

    if ($notifications) {
        foreach ($notifications as $k => $n) {
            $enabled = ppcart_repeater_entry_is_enabled($n);

            /**
             * Filters whether an individual product notification is sent.
             *
             * Returning the default value preserves the normal notification
             * behavior. Returning false skips only the current notification.
             * The order context includes the product_id used to load the
             * notification configuration when that value is available.
             *
             * The default is the notification's enabled state: missing or
             * on stays true, and an explicit off is false. Preview and
             * test-send do not use this filter.
             *
             * @param bool        $should_send         Default send decision.
             * @param array       $notification        Notification configuration.
             * @param array       $order_info           Order/product context.
             * @param int|string  $notification_index   Notification index.
             */
            $should_send = apply_filters('ppcart_should_send_product_notification', $enabled, $n, $order_info, $k);
            if ($should_send) {
                ppcart_send_product_notification($n, $order_info);
            }
        }
    }
}
