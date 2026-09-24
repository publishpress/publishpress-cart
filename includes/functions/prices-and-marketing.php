<?php

if (! defined('ABSPATH')) {
    exit;
}


function ppcart_price_in_cents($amount, $currency = false)
{
    global $ppcart_currency;

    if (!$currency) {
        $currency = $ppcart_currency;
    }

    if ($amount === '') {
        $amount = 0;
    } else {
        $amount = (float) $amount;
    }

    $zero_decimal_currency = ppcart_get_zero_decimal_currencies();
    if (!in_array($currency, $zero_decimal_currency)) {
        $amount *= 100;
    }
    return intval($amount);
}

function ppcart_currency()
{
    global $ppcart_currency, $ppcart_currency_symbol;
    $ppcart_currency = get_option('_ppcart_currency');
    if (!$ppcart_currency) {
        $ppcart_currency = 'USD';
    }
    $currency_array = ppcart_get_currency_symbols();
    $ppcart_currency_symbol = $currency_array[$ppcart_currency];
}
add_action('init', 'ppcart_currency');


/**
     * Normalizes Stripe mode values.
     *
     * @param string $mode Stripe mode.
     *
     * @return string
     */
function ppcart_normalize_stripe_mode($mode = '')
{
    $mode = sanitize_text_field((string) $mode);
    $mode = in_array($mode, [ 'test', 'live' ], true) ? $mode : sanitize_text_field((string) get_option('_ppcart_stripe_api', 'test'));

    return in_array($mode, [ 'test', 'live' ], true) ? $mode : 'test';
}


/**
     * Returns mode-specific Stripe credential option names.
     *
     * @param string $mode Stripe mode.
     *
     * @return array
     */
function ppcart_get_stripe_platform_credential_options($mode = '')
{
    $mode = ppcart_normalize_stripe_mode($mode);

    return [
        'mode'       => $mode,
        'pk'         => 'live' === $mode ? '_ppcart_stripe_live_pk' : '_ppcart_stripe_test_pk',
        'sk'         => 'live' === $mode ? '_ppcart_stripe_live_sk' : '_ppcart_stripe_test_sk',
        'key_source' => 'live' === $mode ? '_ppcart_stripe_live_key_source' : '_ppcart_stripe_test_key_source',
    ];
}


/**
     * Returns raw Stripe credentials stored in WordPress options.
     *
     * @param string $mode Stripe mode.
     *
     * @return array
     */
function ppcart_get_stripe_platform_raw_credentials($mode = '')
{
    $options = ppcart_get_stripe_platform_credential_options($mode);

    return [
        'mode'       => $options['mode'],
        'pk'         => sanitize_text_field((string) get_option($options['pk'], '')),
        'sk'         => sanitize_text_field((string) ppcart_get_sensitive_option($options['sk'], '')),
        'key_source' => sanitize_text_field((string) get_option($options['key_source'], '')),
    ];
}


/**
     * Checks whether a value looks like a direct Stripe publishable key.
     *
     * @param string $key Stripe publishable key.
     * @param string $mode Stripe mode.
     *
     * @return bool
     */
function ppcart_is_stripe_publishable_key($key, $mode = '')
{
    $mode = ppcart_normalize_stripe_mode($mode);
    $key = sanitize_text_field((string) $key);

    return 1 === preg_match('/^pk_' . preg_quote($mode, '/') . '_[A-Za-z0-9_]+/', $key);
}


/**
     * Checks whether a value looks like a direct Stripe secret or restricted key.
     *
     * @param string $key Stripe secret key.
     * @param string $mode Stripe mode.
     *
     * @return bool
     */
function ppcart_is_stripe_secret_key($key, $mode = '')
{
    $mode = ppcart_normalize_stripe_mode($mode);
    $key = sanitize_text_field((string) $key);

    return 1 === preg_match('/^(sk|rk)_' . preg_quote($mode, '/') . '_[A-Za-z0-9_]+/', $key);
}


/**
     * Returns whether Stripe credentials are direct, usable, or require reconnect.
     *
     * @param string $mode Stripe mode.
     *
     * @return array
     */
function ppcart_get_stripe_platform_credentials_status($mode = '')
{
    $raw = ppcart_get_stripe_platform_raw_credentials($mode);
    $credentials = function_exists('ppcart_get_stripe_platform_credentials')
        ? ppcart_get_stripe_platform_credentials($raw['mode'])
        : [
            'mode' => $raw['mode'],
            'pk'   => $raw['pk'],
            'sk'   => $raw['sk'],
        ];

    $is_direct = ppcart_is_stripe_publishable_key($raw['pk'], $raw['mode']) && ppcart_is_stripe_secret_key($raw['sk'], $raw['mode']);
    $is_usable = ppcart_is_stripe_publishable_key($credentials['pk'], $raw['mode']) && ppcart_is_stripe_secret_key($credentials['sk'], $raw['mode']);
    $has_raw_keys = '' !== $raw['pk'] || '' !== $raw['sk'];

    return [
        'mode'               => $raw['mode'],
        'raw'                => $raw,
        'credentials'        => $credentials,
        'is_direct'          => $is_direct,
        'is_usable'          => $is_usable,
        'has_raw_keys'       => $has_raw_keys,
        'requires_reconnect' => ! $is_direct,
        'is_legacy_filtered' => $has_raw_keys && ! $is_direct && $is_usable,
    ];
}


/**
     * Returns internal Stripe platform credentials for a given mode.
     *
     * @param string $mode Stripe mode.
     *
     * @return array
     */
function ppcart_get_stripe_platform_credentials($mode = '')
{
    $raw = ppcart_get_stripe_platform_raw_credentials($mode);
    $mode = $raw['mode'];
    $pk = $raw['pk'];
    $sk = $raw['sk'];

    $pk = apply_filters('ppcart_stripe_platform_publishable_key', $pk, $mode);
    $sk = apply_filters('ppcart_stripe_platform_secret_key', $sk, $mode);

    return [
        'mode' => $mode,
        'pk'   => sanitize_text_field((string) $pk),
        'sk'   => sanitize_text_field((string) $sk),
    ];
}

function ppcart_setup_stripe()
{
    global $ppcart_stripe;
    if (! is_array($ppcart_stripe)) {
        $ppcart_stripe = [];
    }
    $credentials_status = ppcart_get_stripe_platform_credentials_status();
    $credentials = $credentials_status['credentials'];
    $ppcart_stripe['mode'] = $credentials['mode'];
    $ppcart_stripe['sk'] = $credentials['sk'];
    $ppcart_stripe['pk'] = $credentials['pk'];
    $ppcart_stripe['hook_id'] = get_option('_ppcart_stripe_' . $ppcart_stripe['mode'] . '_webhook_id');
    if (get_option('_ppcart_stripe_customer_portal_enable')) {
        $ppcart_stripe['is_customer_portal'] = get_option('_ppcart_stripe_customer_portal_enable');
    }
    if (get_option('_ppcart_stripe_hosted_checkout_enable')) {
        $ppcart_stripe['is_hosted_checkout'] = 1;
    }
    // Hosted Checkout wins when both are enabled; Payment Element only applies otherwise.
    if (
        empty($ppcart_stripe['is_hosted_checkout'])
        && get_option('_ppcart_stripe_payment_element_enable', '1')
    ) {
        $ppcart_stripe['is_payment_element'] = 1;
    }
    // Express Payment applies to the classic card form only; it is built into the other modes.
    if (empty($ppcart_stripe['is_hosted_checkout']) && empty($ppcart_stripe['is_payment_element']) && get_option('_ppcart_stripe_express_payment_enable')) {
        $ppcart_stripe['is_express_payment'] = get_option('_ppcart_stripe_express_payment_enable');
    }

    if (empty($ppcart_stripe['mode']) || ! $credentials_status['is_usable']) {
        $ppcart_stripe = false;
    }
}
add_action('init', 'ppcart_setup_stripe');


function ppcart_sendfox_api_request($endpoint = 'me', $data = [], $method = 'GET')
{
    $result = false;
    $base = 'https://api.sendfox.com/';
    $api_key = ppcart_get_sensitive_option('_ppcart_sendfox_api_key');

    if (empty($api_key)) {
        return $result;
    }

    // prepare request args

    $args = [
        'body' => $data,
    ];

    $args['headers'] = [
        'Authorization' => 'Bearer ' . $api_key,
    ];

    $args['method']  = $method;
    // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout -- External API requests can require more than 3 seconds.
    $args['timeout'] = 30;

    // make request

    $result = wp_remote_request($base . $endpoint, $args);

    if (
        !is_wp_error($result) &&
        ($result['response']['code'] == 200 || $result['response']['code'] == 201)
    ) {
        $result = wp_remote_retrieve_body($result);

        $result = json_decode($result, true);

        if (!empty($result)) {
            $result = [
                'status'     => 'success',
                'result'     => $result,
            ];
        } else {
            $result = [
                'status'     => 'error',
                'error'      => 'json_parse_error',
                'error_text' => __('JSON Parse', 'publishpress-cart'),
            ];
        }
    } else { // if WP_Error happened
        if (is_object($result)) {
            $result = [
                'status'     => 'error',
                'error'      => 'request_error',
                'error_text' => $result->get_error_message(),
            ];
        } else {
            $result = wp_remote_retrieve_body($result);

            $result = [
                'status'     => 'error',
                'error'      => 'request_error',
                'error_text' => $result,
            ];
        }
    }

    return $result;
}

function ppcart_get_mailchimp_data()
{
    $dataArray = [];
    if (false === ppcart_get_mailchimp_api_config()) {
        return $dataArray;
    }
    $result = ppcart_mailchimp_api_request('lists', 'GET', [], ['count' => 100]);
    if (is_wp_error($result)) {
        return $dataArray;
    }

    if (isset($result->lists) && !empty($result->lists)) {
        foreach ($result->lists as $list) {
            $mail_chimp_list_id = $list->id;
            $mail_chimp_list_name = $list->name;
            $dataArray[$mail_chimp_list_id] = [ "mail_chimp_list_name" => $mail_chimp_list_name ];

            $list_path = 'lists/' . ppcart_mailchimp_path_segment($mail_chimp_list_id);
            $tags = ppcart_mailchimp_api_request($list_path . '/segments', 'GET', [], ['count' => 100]);

            if (! is_wp_error($tags) && isset($tags->segments) && ! empty($tags->segments)) {
                foreach ($tags->segments as $tag) {
                    $mail_chimp_tag_id = $tag->id;
                    $mail_chimp_tag_name = $tag->name;

                    $dataArray[$mail_chimp_list_id]['mail_chimp_tags'][] = [
                            "mail_chimp_tag_id" => $mail_chimp_tag_id,
                            "mail_chimp_tag_name" => $mail_chimp_tag_name,
                        ];
                }
            }

            $parent_groups = ppcart_mailchimp_api_request($list_path . '/interest-categories', 'GET', [], ['count' => 100]);

            if (! is_wp_error($parent_groups) && isset($parent_groups->categories) && ! empty($parent_groups->categories)) {
                foreach ($parent_groups->categories as $parent_group) {
                    $mail_chimp_parent_groups_id = $parent_group->id;
                    $mail_chimp_parent_groups_name = $parent_group->title;

                    $groups_endpoint = $list_path . '/interest-categories/' . ppcart_mailchimp_path_segment($mail_chimp_parent_groups_id) . '/interests';
                    $groups = ppcart_mailchimp_api_request($groups_endpoint, 'GET', [], ['count' => 100]);

                    if (is_wp_error($groups) || ! isset($groups->interests) || empty($groups->interests)) {
                        continue;
                    }

                    foreach ($groups->interests as $group) {
                        $mail_chimp_group_id = $group->id;
                        $mail_chimp_group_name = $group->name;
                        $dataArray[$mail_chimp_list_id]['mail_chimp_groups'][$mail_chimp_group_id] = [
                            "mail_chimp_parent_groups_id" => $mail_chimp_parent_groups_id,
                            "mail_chimp_parent_groups_name" => $mail_chimp_parent_groups_name,
                            "mail_chimp_group_id" => $mail_chimp_group_id,
                            "mail_chimp_group_name" => $mail_chimp_group_name,
                        ];
                    }
                }
            }
        }
    } else {
        $dataArray = ['' => __('No Data Found', 'publishpress-cart')];
    }

    return $dataArray;
}

function ppcart_maybe_update_stock($product_id, $action = 'decrease', $qty = 1)
{
    if (ppcart_get_post_meta($product_id, 'manage_stock', true) == '1') {
        $limit = ppcart_get_post_meta($product_id, 'limit', true);
        switch ($action) {
            case 'increase':
                $limit += $qty;
                break;
            default:
                $limit -= $qty;
                break;
        }
        ppcart_update_post_meta($product_id, 'limit', $limit);
        do_action('ppcart_after_update_stock', $product_id);
        return $limit;
    }
}


function ppcart_get_font_awesome_icon_html($icon)
{
    if (! is_string($icon) || '' === $icon || 'none' === $icon) {
        return false;
    }

    if (false !== strpos($icon, '<')) {
        return $icon;
    }

    preg_match_all('/fa-([a-z0-9-]+)/i', $icon, $matches);
    if (! empty($matches[1])) {
        $icon_name = strtolower((string) end($matches[1]));
    } else {
        $icon_name = strtolower(basename($icon, '.svg'));
    }

    $icon_name = preg_replace('/[^a-z0-9-]/', '', $icon_name);

    if (empty($icon_name)) {
        return false;
    }

    return sprintf(
        '<i class="fa-solid fa-%1$s" aria-hidden="true"></i>',
        esc_attr($icon_name)
    );
}
