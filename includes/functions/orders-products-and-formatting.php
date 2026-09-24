<?php

if (! defined('ABSPATH')) {
    exit;
}


function ppcart_get_transaction_id($order_id)
{

    if ($txnid = ppcart_get_post_meta($order_id, 'transaction_id', true)) {
        return $txnid;
    }

    $method = ppcart_get_post_meta($order_id, 'pay_method', true);
    $txnid = false;
    switch ($method) {
        case 'stripe':
            if (ppcart_is_subscription_post_type(get_post_type($order_id))) {
                $args = [
                    'post_type' => ppcart_query_post_types('order'),
                    'orderby' => 'date',
                    'order'   => 'ASC',
                    'posts_per_page' => 1,
                    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required lookup by subscription meta for legacy transaction mapping.
                    'meta_query' => [
                        [
                            'key' => ppcart_meta_key('subscription_id'),
                            'value' => $order_id,
                        ],
                    ],
                ];
                $order_id = get_posts($args)[0]->ID;
            }
            $txnid = ppcart_get_post_meta($order_id, 'stripe_charge_id', true);
            break;
        case 'paypal':
            $txnid = ppcart_get_post_meta($order_id, 'paypal_txn_id', true);
            break;
    }

    $txnid = apply_filters('ppcart_ransaction_id', $txnid, $method, $order_id);
    if ($txnid) {
        ppcart_update_post_meta($order_id, 'transaction_id', $txnid);
    }
    return $txnid;
}

function ppcart_get_products()
{

    global $ppcart_public;
    remove_filter('the_title', [ $ppcart_public, 'public_product_name' ]);

    $options = [];

    // The Query
    $args = [
        'post_type' => array_merge(ppcart_query_post_types('product'), ppcart_query_pro_post_types('collection')),
        'post_status' => 'any',
        // phpcs:ignore WordPressVIPMinimum.Performance.NoPaging.posts_per_page_posts_per_page -- This admin/report selector intentionally loads all matching records for aggregate calculations or option lists.
        'posts_per_page' => -1,
    ];
    $posts = get_posts($args);

    // The Loop
    if ($posts) {
        foreach ($posts as $post) {
            $options[$post->ID] = $post->post_title;
        }
    }

    add_filter('the_title', [ $ppcart_public, 'public_product_name' ]);
    return $options;
}

function ppcart_get_subscription_txn_id($order_id, $subsription = false)
{

    if ($txnid = ppcart_get_post_meta($order_id, 'subscription_id', true)) {
        return $txnid;
    }

    $method = ppcart_get_post_meta($order_id, 'pay_method', true);
    $txnid = false;
    if (! ppcart_is_subscription_post_type(get_post_type($order_id))) {
        $order_id = ppcart_get_post_meta($order_id, 'subscription_id', true);
        if (!$order_id) {
            return false;
        }
    }

    switch ($method) {
        case 'stripe':
            $txnid = ppcart_get_post_meta($order_id, 'stripe_subscription_id', true);
            if ($txnid === false) {
                $txnid = ppcart_get_post_meta($order_id, 'stripe_charge_id', true);
            }
            break;
        case 'paypal':
            $txnid = ppcart_get_post_meta($order_id, 'paypal_subscr_id', true);
            break;
    }

    $txnid = apply_filters('ppcart_subscription_transaction_id', $txnid, $method, $order_id);
    if ($txnid) {
        ppcart_update_post_meta($order_id, 'subscription_id', $txnid);
    }
    return $txnid;
}

function ppcart_add_remove_membervault_subscriber($order_id, $service_id, $action_name, $member_vault_course_id, $email, $phone, $fname, $lname)
{

    global $wpdb;

    if (empty($service_id) || empty($action_name) || empty($email) || empty($member_vault_course_id)) {
        return;
    }

    $member_vault_api_key   = ppcart_get_sensitive_option('_ppcart_member_vault_api_key');
    $business_name  = get_option('_ppcart_membervault_name');
    $url = (strpos($business_name, 'http') !== false) ? $business_name : "https://{$business_name}.vipmembervault.com/";

    if (!empty($member_vault_api_key)) {
        $ids = explode(',', $member_vault_course_id);

        foreach ($ids as $mvid) {
            $url .= "/api/{$action_name}/?apikey={$member_vault_api_key}&course_id={$mvid}&email={$email}&first_name={$fname}&last_name={$lname}";

            $apiUrl = esc_url_raw($url);

            $response = wp_safe_remote_get($apiUrl);
            $responseBody = wp_remote_retrieve_body($response);
            $result = json_decode($responseBody, true);

            if (is_array($result) && ! is_wp_error($result)) {
                if (is_numeric($result['user_id'])) {
                    $status = ($action_name == 'add_user') ? __(' added to', 'publishpress-cart') : __(' removed from', 'publishpress-cart');
                    ppcart_log_entry($order_id, $email . $status . __(' Membervault course ID: ', 'publishpress-cart') . $mvid);
                } else {
                    /* translators: %s: Membervault API error message. */
                    ppcart_log_entry($order_id, sprintf(__('Membervault add failed: %s', 'publishpress-cart'), $result['user_id']));
                }
            } else {
                if ($action_name == 'add_user') {
                    ppcart_log_entry($order_id, __('Membervault add failed', 'publishpress-cart'));
                } else {
                    ppcart_log_entry($order_id, __('Membervault remove failed', 'publishpress-cart'));
                }
            }
        }
    }
    return;
}


function ppcart_add_remove_mailchimp_subscriber($order_id, $service_id, $action_name, $list_id, $ppcart_mail_tags, $ppcart_mail_groups, $email, $phone, $fname, $lname, $intg, $order)
{
    if (empty($service_id) || empty($action_name) || empty($list_id) || empty($email)) {
        return;
    }
    if ('mailchimp' !== $service_id || false === ppcart_get_mailchimp_api_config()) {
        return;
    }

    $list_path = 'lists/' . ppcart_mailchimp_path_segment($list_id);
    $subscriber_hash = md5(strtolower($email));
    $member_path = $list_path . '/members/' . $subscriber_hash;

    if ('subscribed' !== $action_name) {
        $result = ppcart_mailchimp_api_request($member_path, 'DELETE');
        if (! is_wp_error($result)) {
            ppcart_log_entry($order_id, $email . __(' removed from list ID: ', 'publishpress-cart') . $list_id);
        }
        return;
    }

    $merge_data = ['FNAME' => $fname, 'LNAME' => $lname];
    if (! empty($intg['mc_phone_tag']) && $phone) {
        $merge_data[$intg['mc_phone_tag']] = $phone;
    }
    $merge_data = apply_filters('ppcart_mailchimp_merge_data', $merge_data, $order_id);

    $result = ppcart_mailchimp_api_request($member_path, 'PUT', [
        'email_address' => $email,
        'merge_fields' => $merge_data,
        'status' => 'subscribed',
        'status_if_new' => 'subscribed',
    ]);
    if (is_wp_error($result)) {
        return;
    }

    ppcart_log_entry($order_id, $email . __(' added to list ID: ', 'publishpress-cart') . $list_id);

    if (! empty($ppcart_mail_groups)) {
        $groups = is_array($ppcart_mail_groups) ? $ppcart_mail_groups : explode(',', $ppcart_mail_groups);
        $interests = [];
        foreach ($groups as $group_id) {
            if ('' !== (string) $group_id) {
                $interests[(string) $group_id] = true;
            }
        }
        if (! empty($interests)) {
            $group_result = ppcart_mailchimp_api_request($member_path, 'PATCH', [
                'merge_fields' => $merge_data,
                'interests' => $interests,
            ]);
            if (! is_wp_error($group_result)) {
                ppcart_log_entry($order_id, __('MailChimp added ', 'publishpress-cart') . $email . __(' to group: ', 'publishpress-cart') . implode(',', $groups));
            }
        }
    }

    if (! empty($ppcart_mail_tags)) {
        $tags = is_array($ppcart_mail_tags) ? $ppcart_mail_tags : explode(',', $ppcart_mail_tags);
        $tags_added = [];
        foreach ($tags as $tag_id) {
            $tag_id = str_replace('tag-', '', (string) $tag_id);
            if ('' === $tag_id) {
                continue;
            }
            $tag_result = ppcart_mailchimp_api_request(
                $list_path . '/segments/' . ppcart_mailchimp_path_segment($tag_id),
                'PATCH',
                ['members_to_add' => [$email]]
            );
            if (! is_wp_error($tag_result)) {
                $tags_added[] = $tag_id;
            }
        }
        if (! empty($tags_added)) {
            ppcart_log_entry($order_id, $email . __(' MailChimp tagged: ', 'publishpress-cart') . implode(',', $tags_added));
        }
    }
}


function ppcart_order_log($order_id)
{
    $log_entries = ppcart_get_post_meta($order_id, 'order_log', true);
    if (!is_array($log_entries)) {
        $log_entries = [];
    }
    return $log_entries;
}

function ppcart_log_entry($order_id, $entry)
{

    if (!$order_id) {
        return;
    }

    $log_entries = ppcart_order_log($order_id);
    $log_entries[time() . ' - sc' . wp_rand()] = sanitize_text_field($entry);
    ppcart_update_post_meta($order_id, 'order_log', $log_entries);
}

function ppcart_is_number_formatted($amt, $decisep = '.', $thousep = ',', $decinum = 2)
{

    $pattern = '/^\d{1,3}(' . preg_quote($thousep, '/') . '\d{3})*';
    if ($decinum > 0) {
        $pattern .= preg_quote($decisep, '/') . '\d{' . $decinum . '}';
    }
    $pattern .= '$/';

    return preg_match($pattern, $amt) === 1;
}

function ppcart_format_number($amt)
{

    if ($amt === '') {
        return '';
    } elseif ($amt === '0') {
        return 0;
    }

    $num = get_option('_ppcart_decimal_number');
    $decinum = ($num === '0' || !empty($num)) ? intval(get_option('_ppcart_decimal_number')) : 2 ;
    $decisep = !empty(get_option('_ppcart_decimal_separator')) ? get_option('_ppcart_decimal_separator') : '.' ;
    $thousep = !empty(get_option('_ppcart_thousand_separator')) ? get_option('_ppcart_thousand_separator') : '' ;

    // Check if the number is already formatted
    if (ppcart_is_number_formatted($amt, $decisep, $thousep, $decinum)) {
        return $amt;
    }

    $amt = (string) $amt;

    if (strpos($amt, '.') !== false) {
        $lastDotPos = strrpos($amt, '.');
        $amt = substr_replace($amt, '@', $lastDotPos, 1); // Temporarily replace last dot
        $amt = str_replace('.', '', $amt); // Remove all other dots
        $amt = str_replace('@', '.', $amt); // Restore last dot as decimal separator
    }

    $amt = (float) $amt; // Convert back to float
    $formatted_amt = number_format($amt, $decinum, $decisep, $thousep);
    return $formatted_amt;
}

function ppcart_format_price($amt, $html = true)
{
    global $ppcart_currency_symbol;

    if ($amt === '') {
        return '';
    }

    $position = get_option('_ppcart_currency_position');

    $price = '';
    $symbol = (string) ppcart_get_currency_symbol();
    if ($html) {
        $symbol = '<span class="ppcart-Price-currencySymbol">' . $symbol . '</span>';
    } else {
        // Symbols are stored as HTML entities (&#36;). Plain-text sinks such as
        // the checkout total do not decode entities, so $ would show as &#36;.
        $symbol = html_entity_decode($symbol, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    // left positioned currency
    if ($position != 'right' && $position != 'right-space') {
        $price .= $symbol;
        // with space
        if ($position == 'left-space') {
            $price .= ' ';
        }
    }

    // format price
    $price .= ppcart_format_number($amt);

    // right positioned currency
    if ($position == 'right' || $position == 'right-space') {
        // with space
        if ($position == 'right-space') {
            $price .= ' ';
        }
        $price .= $symbol;
    }
    return apply_filters('ppcart_format_price', $price, ppcart_format_number($amt), $ppcart_currency_symbol, $html);
}

function ppcart_formatted_price($price)
{
    echo wp_kses_post(ppcart_format_price($price));
}

function ppcart_format_stripe_number($amount, $ppcart_currency = 'USD')
{
    $zero_decimal_currency = ppcart_get_zero_decimal_currencies();
    if (!in_array($ppcart_currency, $zero_decimal_currency)) {
        $amount = $amount / 100;
    }
    return ppcart_format_number($amount);
}

function ppcart_get_currency_symbol()
{
    global $ppcart_currency_symbol;
    return $ppcart_currency_symbol;
}

function ppcart_currency_settings()
{
    $position = get_option('_ppcart_currency_position');
    $thousep = get_option('_ppcart_thousand_separator');
    $decisep = get_option('_ppcart_decimal_separator');
    $decinum = intval(get_option('_ppcart_decimal_number'));

    if (!$position) {
        $position = '';
    }
    if (!$thousep) {
        $thousep = ',';
    }
    if (!$decisep) {
        $decisep = '.';
    }

    return [
        'symbol' => ppcart_get_currency_symbol(),
        'position' => $position,
        'thousep' => $thousep,
        'decisep' => $decisep,
        'decinum' => $decinum,
    ];
}

function ppcart_get_user_phone($user_id)
{
    global $wpdb;
    $user_phone = ppcart_get_user_meta($user_id, 'phone', true);
    if (!$user_phone) {
        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- ppcart_sql_in_meta_keys() returns a placeholder list prepared from canonical meta keys.
        $query = $wpdb->prepare("SELECT max(post_id) FROM {$wpdb->postmeta} WHERE meta_key IN (" . ppcart_sql_in_meta_keys('user_account') . ") AND meta_value = %d", $user_id);
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared -- Query string is prepared immediately above and read once for profile hydration.
        $post_id_meta = $wpdb->get_var($query);
        $user_phone = ppcart_get_post_meta($post_id_meta, 'phone', true);
        ppcart_add_user_meta($user_id, 'phone', $user_phone, true);
    }
    return $user_phone;
}

function ppcart_get_user_address($user_id)
{
    global $wpdb;
    $user_address = ppcart_get_user_meta($user_id, 'address', true);
    if ($user_address) {
        $address = [
            'address' => $user_address,
            'address_1' => ppcart_get_user_meta($user_id, 'address_1', true),
            'address_2' => ppcart_get_user_meta($user_id, 'address_2', true),
            'city' => ppcart_get_user_meta($user_id, 'city', true),
            'state' => ppcart_get_user_meta($user_id, 'state', true),
            'zip' => ppcart_get_user_meta($user_id, 'zip', true),
            'country' => ppcart_get_user_meta($user_id, 'country', true),
        ];
        return $address;
    } else {
        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- ppcart_sql_in_meta_keys() returns a placeholder list prepared from canonical meta keys.
        $query = $wpdb->prepare("SELECT max(post_id) FROM {$wpdb->postmeta} WHERE meta_key IN (" . ppcart_sql_in_meta_keys('user_account') . ") AND meta_value = %d", $user_id);
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared -- Query string is prepared immediately above and read once for profile hydration.
        $post_id_meta = $wpdb->get_var($query);

        $address_1 = ppcart_get_post_meta($post_id_meta, 'address1', true);
        $address_2 = ppcart_get_post_meta($post_id_meta, 'address2', true);
        $city = ppcart_get_post_meta($post_id_meta, 'city', true);
        $state = ppcart_get_post_meta($post_id_meta, 'state', true);
        $zip = ppcart_get_post_meta($post_id_meta, 'zip', true);
        $country = ppcart_get_post_meta($post_id_meta, 'country', true);

        if ($address_1 || $city || $state || $zip || $country) {
            ppcart_update_user_meta($user_id, 'address_1', $address_1);
            ppcart_update_user_meta($user_id, 'address_2', $address_2);
            ppcart_update_user_meta($user_id, 'city', $city);
            ppcart_update_user_meta($user_id, 'state', $state);
            ppcart_update_user_meta($user_id, 'zip', $zip);
            ppcart_update_user_meta($user_id, 'country', $country);

            $address = [
                'address_1' => $address_1,
                'address_2' => $address_2,
                'city' => $city,
                'state' => $state,
                'zip' => $zip,
                'country' => $country,
            ];

            $address['address'] = 1;
            ppcart_update_user_meta($user_id, 'address', $address['address']);
            return $address;
        } else {
            return false;
        }
    }
}

function ppcart_format_address($address)
{
    $order = (object) $address;

    $str = '';
    if (isset($order->address1) || isset($order->city) || isset($order->state) || isset($order->zip) || isset($order->country)) {
        if (isset($order->address1) && $order->address1) {
            $str .= $order->address1 . '<br/>';
        }
        if (isset($order->address2) && $order->address2) {
            $str .= $order->address2 . '<br/>';
        }
        if (isset($order->city) || isset($order->state) || isset($order->zip)) {
            if (isset($order->city) && $order->city) {
                $str .= $order->city;
            }
            if (isset($order->state) && $order->state) {
                if ($str != '') {
                    $str .= ', ';
                }
                $str .= $order->state;
            }
            if (isset($order->zip) && $order->zip) {
                if ($str != '') {
                    $str .= ' ';
                }
                $str .= $order->zip;
            }
            if ($str != '') {
                $str .= '<br>';
            }
        }
        if (isset($order->country) && $order->country) {
            $str .= $order->country . '<br/>';
        }
    }
    return $str;
}

function ppcart_order_address($id)
{
    $order = ppcart_setup_order($id);
    $str = '';
    if (isset($order->address1) || isset($order->city) || isset($order->state) || isset($order->zip) || isset($order->country)) {
        if (isset($order->address1) && $order->address1) {
            $str .= esc_html($order->address1) . '<br/>';
        }
        if (isset($order->address2) && $order->address2) {
            $str .= esc_html($order->address2) . '<br/>';
        }
        if (isset($order->city) || isset($order->state) || isset($order->zip)) {
            if (isset($order->city) && $order->city) {
                $str .= esc_html($order->city);
            }
            if (isset($order->state) && $order->state) {
                if ($str != '') {
                    $str .= ', ';
                }
                $str .= esc_html($order->state);
            }
            if (isset($order->zip) && $order->zip) {
                if ($str != '') {
                    $str .= ' ';
                }
                $str .= esc_html($order->zip);
            }
            if ($str != '') {
                $str .= '<br>';
            }
        }
        if (isset($order->country) && $order->country) {
            $str .= esc_html($order->country) . '<br/>';
        }
    }
    return $str;
}

/**
     * Normalize product names for outbound payloads and UI rendering.
     *
     * Decodes HTML entities (including double-encoded values), strips tags,
     * and returns trimmed plain text.
     *
     * @param mixed $name Product name candidate.
     * @return string
     */
function ppcart_normalize_product_name($name)
{
    if (! is_scalar($name)) {
        return '';
    }

    $normalized = (string) $name;
    if ($normalized === '') {
        return '';
    }

    $normalized = html_entity_decode($normalized, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // Handle values that may already be entity-encoded as text (e.g. &amp;#8211;).
    if (strpos($normalized, '&') !== false) {
        $normalized = html_entity_decode($normalized, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    return trim(wp_strip_all_tags($normalized));
}


function ppcart_get_public_product_name($id = false)
{
    global $post;

    if (!$id) {
        $id = (is_object($post) && isset($post->ID)) ? $post->ID : 0;
    }

    if (!$id) {
        return '';
    }


    if (ppcart_is_product_post_type(get_post_type($id)) && $name = ppcart_get_post_meta($id, 'product_name', true)) {
        return ppcart_normalize_product_name($name);
    }

    return ppcart_normalize_product_name(get_the_title($id));
}

/**
 * Edit-post URL safe to pass to esc_url().
 *
 * get_edit_post_link() returns null when the post is missing or cannot be
 * edited. Passing that to esc_url() deprecates on PHP 8.1+ (ltrim(null)).
 * ID 0 is not forwarded: WordPress treats it as the current post.
 *
 * @param mixed $post_id Post ID.
 * @return string Empty string when there is no usable edit URL.
 */
function ppcart_get_edit_post_url($post_id): string
{
    $post_id = absint($post_id);
    if ($post_id < 1) {
        return '';
    }
    $url = get_edit_post_link($post_id);
    return is_string($url) && $url !== '' ? $url : '';
}

/**
 * Stored invoice numbering format code.
 *
 * Canonical values: ppcart_pns, ppcart_pn, ppcart_ns, ppcart_n.
 * Compatibility Mode may map leftover StudioCart codes onto these.
 *
 * @return string
 */
function ppcart_get_invoice_format()
{
    $format = get_option('_ppcart_invoice_format', 'ppcart_pns');
    $format = apply_filters('ppcart_invoice_format', $format);

    if (! is_string($format) || '' === $format) {
        return 'ppcart_pns';
    }

    return $format;
}
