<?php

if (! defined('ABSPATH')) {
    exit;
}

const PPCART_EXISTING_ACCOUNT_OPTION = '_ppcart_existing_account_checkout';
const PPCART_EXISTING_ACCOUNT_RATE_WINDOW = 600;

add_action('wp_ajax_ppcart_check_email', 'ppcart_ajax_check_email');
add_action('wp_ajax_nopriv_ppcart_check_email', 'ppcart_ajax_check_email');
add_action('wp_ajax_ppcart_checkout_login', 'ppcart_ajax_checkout_login');
add_action('wp_ajax_nopriv_ppcart_checkout_login', 'ppcart_ajax_checkout_login');
add_filter('ppcart_validate_custom_fields', 'ppcart_existing_account_skip_required_fields', 100, 2);

/**
 * Whether the product's checkout creates or updates a WordPress account.
 *
 * @param object|null $product Product from ppcart_setup_product().
 * @return bool
 */
function ppcart_product_creates_account($product)
{
    if (! is_object($product) || empty($product->ID)) {
        return false;
    }

    if (! empty(ppcart_checkout_account_field_keys($product))) {
        return true;
    }

    $user_services = (array) apply_filters('ppcart_create_user_integrations', ['create user', 'update user', 'tutor']);
    $integrations = ppcart_get_post_meta($product->ID, 'integrations', true);
    foreach ((array) $integrations as $integration) {
        if (is_array($integration) && in_array($integration['services'] ?? '', $user_services, true)) {
            return true;
        }
    }

    return false;
}

/**
 * Custom field keys that only matter for a new account: the password field and the username field.
 *
 * The checkout hides these fields, and the order validation skips their required check,
 * while the buyer's email already has an account.
 *
 * @param object|null $product Product from ppcart_setup_product().
 * @return string[] Keys as posted in ppcart_custom_fields[<key>].
 */
function ppcart_checkout_account_field_keys($product)
{
    $keys = [];
    if (is_object($product) && isset($product->custom_fields) && is_iterable($product->custom_fields)) {
        foreach ($product->custom_fields as $field) {
            if (! is_array($field) || empty($field['field_id'])) {
                continue;
            }
            if ('password' === ($field['field_type'] ?? '') || ! empty($field['field_username'])) {
                $keys[] = str_replace([' ', '.'], ['_', '_'], (string) $field['field_id']);
            }
        }
    }

    /**
     * Filters the checkout custom field keys that belong to a new account.
     *
     * @param string[]    $keys    Keys as posted in ppcart_custom_fields[<key>].
     * @param object|null $product Product being purchased.
     */
    $keys = apply_filters('ppcart_checkout_account_field_keys', $keys, $product);

    return array_values(array_unique(array_filter(array_map('strval', (array) $keys), 'strlen')));
}

/**
 * Whether the checkout should check the buyer's email for an existing account.
 *
 * @param object|null $product Product from ppcart_setup_product().
 * @return bool
 */
function ppcart_existing_account_check_enabled($product)
{
    return 'login' === get_option(PPCART_EXISTING_ACCOUNT_OPTION, '')
        && ! is_user_logged_in()
        && ppcart_product_creates_account($product);
}

/**
 * Whether the email belongs to an account and the store asks such buyers to log in.
 *
 * @param string      $email   Buyer email.
 * @param object|null $product Product from ppcart_setup_product().
 * @return bool
 */
function ppcart_checkout_email_has_account($email, $product)
{
    $email = sanitize_email((string) $email);

    return '' !== $email
        && is_email($email)
        && ppcart_existing_account_check_enabled($product)
        && false !== email_exists($email);
}

/**
 * Drop the account field keys from the required list when the posted email has an account.
 *
 * Runs on ppcart_validate_custom_fields after the nonce check in the order form validation.
 *
 * @param array       $required Required field names; arrays hold custom field keys.
 * @param object|null $product  Product being purchased.
 * @return array
 */
function ppcart_existing_account_skip_required_fields($required, $product)
{
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- The order form validation verifies the nonce before this filter runs.
    $email = isset($_POST['email']) && is_string($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
    if (! is_array($required) || ! ppcart_checkout_email_has_account($email, $product)) {
        return $required;
    }

    $account_keys = ppcart_checkout_account_field_keys($product);
    $account_names = array_map(
        static function ($key) {
            return 'ppcart_custom_fields[' . $key . ']';
        },
        $account_keys
    );

    $filtered = [];
    foreach ($required as $entry) {
        if (is_array($entry)) {
            $entry = array_values(array_diff($entry, $account_keys));
            if (! empty($entry)) {
                $filtered[] = $entry;
            }
        } elseif (! in_array($entry, $account_keys, true) && ! in_array($entry, $account_names, true)) {
            $filtered[] = $entry;
        }
    }

    return $filtered;
}

/**
 * Count one request for the visitor's IP address and report whether it is over the limit.
 *
 * Uses a private options row per window so the count holds across PHP workers.
 *
 * @param string $bucket 'check_email' or 'checkout_login'.
 * @return bool True when the request is over the limit.
 */
function ppcart_existing_account_rate_limited($bucket)
{
    global $wpdb;

    $defaults = ['check_email' => 10, 'checkout_login' => 5];
    /**
     * Filters how many existing-account requests one IP address may make in ten minutes.
     *
     * @param int    $limit  Requests allowed per window.
     * @param string $bucket 'check_email' or 'checkout_login'.
     */
    $limit = (int) apply_filters('ppcart_existing_account_rate_limit', $defaults[$bucket] ?? 5, $bucket);
    if ($limit < 1) {
        return true;
    }

    // Trust the address supplied by the web server, never a forwarding header.
    // phpcs:ignore WordPressVIPMinimum.Variables.ServerVariables.UserControlledHeaders -- Validated as an IP address before hashing.
    $address = filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP);
    if (! $address) {
        return true;
    }

    $now = time();
    $expires = (intdiv($now, PPCART_EXISTING_ACCOUNT_RATE_WINDOW) + 1) * PPCART_EXISTING_ACCOUNT_RATE_WINDOW;
    $prefix = '_ppcart_account_rate_';
    $key = $prefix . sprintf('%010d', $expires) . '_' . sanitize_key($bucket) . '_'
        . hash_hmac('sha256', inet_pton($address), wp_salt('nonce'));

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Atomic counter reservation.
    $inserted = $wpdb->query($wpdb->prepare(
        "INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, '1', 'no')",
        $key
    ));

    if (1 === wp_rand(1, 20)) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Bounded cleanup of expired private counters.
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s AND option_name < %s ORDER BY option_name LIMIT 1000",
            $wpdb->esc_like($prefix) . '%',
            $prefix . sprintf('%010d', $now) . '_'
        ));
    }

    if (0 !== $inserted) {
        return false;
    }

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Atomic counter reservation.
    $reserved = $wpdb->query($wpdb->prepare(
        "UPDATE {$wpdb->options} SET option_value = CAST(option_value AS UNSIGNED) + 1 WHERE option_name = %s AND CAST(option_value AS UNSIGNED) < %d",
        $key,
        $limit
    ));

    return 0 === $reserved;
}

/**
 * Verify the checkout nonce and load the product of an existing-account request.
 *
 * Sends a JSON error and stops when the nonce is missing or not valid.
 *
 * @return object|null Product, or null when the request names no product.
 */
function ppcart_existing_account_request_product()
{
    // phpcs:disable WordPress.Security.NonceVerification.Missing -- The nonce is read here for immediate verification.
    $nonce = isset($_POST['ppcart-nonce']) && is_string($_POST['ppcart-nonce'])
        ? sanitize_text_field(wp_unslash($_POST['ppcart-nonce']))
        : '';
    if ('' === $nonce || ! ppcart_verify_nonce($nonce, 'ppcart_purchase_nonce')) {
        wp_send_json_error(['error' => __('Invalid Request', 'publishpress-cart')], 403);
    }

    $product_id = isset($_POST['ppcart_product_id']) ? absint(wp_unslash($_POST['ppcart_product_id'])) : 0;
    // phpcs:enable WordPress.Security.NonceVerification.Missing

    return $product_id ? ppcart_setup_product($product_id) : null;
}

/**
 * AJAX ppcart_check_email: report whether the checkout email has an account.
 *
 * The response holds only { exists: bool }. Every case that must not reveal an account
 * (setting off, no account product, logged in, over the rate limit) answers false.
 */
function ppcart_ajax_check_email()
{
    $product = ppcart_existing_account_request_product();
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in ppcart_existing_account_request_product().
    $email = isset($_POST['email']) && is_string($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';

    $exists = ppcart_existing_account_check_enabled($product)
        && ! ppcart_existing_account_rate_limited('check_email')
        && ppcart_checkout_email_has_account($email, $product);

    wp_send_json_success(['exists' => $exists]);
}

/**
 * AJAX ppcart_checkout_login: log the buyer in with the checkout email and a password.
 *
 * Uses wp_signon(), so core and security plugin login protections apply. Every failure
 * returns the same message, which never says whether the email or the password was wrong.
 */
function ppcart_ajax_checkout_login()
{
    $product = ppcart_existing_account_request_product();
    $failed = ['error' => __('We could not log you in with these details. Check your password, or reset it.', 'publishpress-cart')];

    if (! ppcart_existing_account_check_enabled($product) || ppcart_existing_account_rate_limited('checkout_login')) {
        wp_send_json_error($failed);
    }

    // phpcs:disable WordPress.Security.NonceVerification.Missing -- Verified in ppcart_existing_account_request_product().
    $email = isset($_POST['email']) && is_string($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
    // A password must reach wp_signon() unchanged; sanitizing it would alter valid passwords.
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    $password = isset($_POST['password']) && is_string($_POST['password']) ? wp_unslash($_POST['password']) : '';
    // phpcs:enable WordPress.Security.NonceVerification.Missing

    if ('' === $email || ! is_email($email) || '' === $password) {
        wp_send_json_error($failed);
    }

    $user = wp_signon(
        [
            'user_login'    => $email,
            'user_password' => $password,
            'remember'      => false,
        ],
        is_ssl()
    );

    if (is_wp_error($user)) {
        wp_send_json_error($failed);
    }

    wp_set_current_user($user->ID);
    wp_send_json_success(['reload' => true]);
}

/**
 * URLs for the existing-account notice: the login page and the lost password page.
 *
 * @return array{login_url: string, lost_password_url: string}
 */
function ppcart_existing_account_urls()
{
    $account_page = (int) get_option('_ppcart_myaccount_page_id');
    $account_url = $account_page ? get_permalink($account_page) : '';

    if ($account_url) {
        return [
            'login_url'         => $account_url,
            'lost_password_url' => add_query_arg('action', 'lostpassword', $account_url),
        ];
    }

    return [
        'login_url'         => wp_login_url(),
        'lost_password_url' => wp_lostpassword_url(),
    ];
}
