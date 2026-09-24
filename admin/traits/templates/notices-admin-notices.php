<?php

if (! defined('ABSPATH')) {
    exit;
}


if ($this->is_custom_notice_shell_page() && 'admin_notices' === current_filter()) {
    return;
}

if (! $this->should_render_admin_notices()) {
    return;
}

// For updating to v2.0.11
$ppcart_currency = get_option('_ppcart_currency');
$settings_url = admin_url('admin.php?page=' . PPCart_Admin_Screens::PAGE_SETTINGS . '#payment_methods');
$plugin_title = apply_filters('ppcart_plugin_title', $this->plugin_title);
$is_settings_page = $this->is_settings_page();
if ($ppcart_currency && $ppcart_currency == strtolower($ppcart_currency)) {
    echo '<div class="notice notice-error"><p>Please re-select your currency to continue using <b>' . esc_html($plugin_title) . '</b>. You can do that now by <a href="' . esc_url(admin_url('admin.php?page=' . PPCart_Admin_Screens::PAGE_SETTINGS)) . '" rel="noreferrer noopener" data-testid="' . esc_attr(ppcart_testid('ppcart-admin-notice-currency-settings')) . '">clicking here</a>.</p></div>';
}

if (!ppcart_enabled_processors() && ! $is_settings_page) {
    echo '<div class="notice notice-error"><p style="font-weight: bold">No payment methods found!</p> <p>Please enable at least one payment method in the <a href="' . esc_url(admin_url('admin.php?page=' . PPCart_Admin_Screens::PAGE_SETTINGS . '#payment_methods')) . '" rel="noreferrer noopener" data-testid="' . esc_attr(ppcart_testid('ppcart-admin-notice-payment-settings')) . '">' . esc_html($plugin_title) . ' settings</a>.</p></div>';
}

$integration_errors = ppcart_validate_payment_key();
if (!empty($integration_errors)) {
    echo '<div class="notice notice-error">
        <p><strong>' . esc_html(sprintf(
        /* translators: %s: plugin title. */
        __('%s integration error.', 'publishpress-cart'),
        $plugin_title
    )) . '</strong> ';

    echo wp_kses_post(sprintf(
        /* translators: 1: plugin title, 2: settings page URL. */
        __('Unable to connect to the integration(s) listed below. Go to the %1$s <a href="%2$s" rel="noreferrer noopener" data-testid="ppcart-admin-notice-integration-settings">settings page</a> to check your integration settings.</p>', 'publishpress-cart'),
        esc_html($plugin_title),
        esc_url($settings_url)
    ));

    foreach ($integration_errors as $gateway => $message) {
        echo '<p><strong>' . esc_html($gateway) . ':</strong> ' . wp_kses_post($message) . '</p>';
    }
    echo '</div>';
}

$stripe_mode = function_exists('ppcart_normalize_stripe_mode')
    ? ppcart_normalize_stripe_mode(get_option('_ppcart_stripe_api', 'test'))
    : sanitize_text_field((string) get_option('_ppcart_stripe_api', 'test'));

if (get_option('_ppcart_stripe_enable') && 'live' === $stripe_mode) {
    $credentials_status = function_exists('ppcart_get_stripe_platform_credentials_status')
        ? ppcart_get_stripe_platform_credentials_status($stripe_mode)
        : [];
    $secret_key = isset($credentials_status['credentials']['sk'])
        ? sanitize_text_field((string) $credentials_status['credentials']['sk'])
        : sanitize_text_field((string) ppcart_get_sensitive_option('_ppcart_stripe_' . $stripe_mode . '_sk', ''));
    $destination_option_key = 'live' === $stripe_mode ? '_ppcart_stripe_connect_account_id_live' : '_ppcart_stripe_connect_account_id_test';
    $destination_account = sanitize_text_field((string) get_option($destination_option_key, ''));

    $dashboard_url = ('test' === $stripe_mode)
        ? 'https://dashboard.stripe.com/test/connect/accounts/' . rawurlencode($destination_account)
        : 'https://dashboard.stripe.com/connect/accounts/' . rawurlencode($destination_account);

    if ('' === $destination_account) {
        echo '<div class="notice notice-warning"><p><strong>' . esc_html__('Stripe Connect is enabled, but the destination account is missing for the active API mode.', 'publishpress-cart') . '</strong> ';
        echo wp_kses_post(__('Use the Connect with Stripe button in Stripe settings to connect an account for this mode.', 'publishpress-cart'));
        echo '</p></div>';
    } elseif (0 !== strpos($destination_account, 'acct_')) {
        echo '<div class="notice notice-warning"><p><strong>' . esc_html__('Stripe Connect destination account format is invalid.', 'publishpress-cart') . '</strong> ';
        echo wp_kses_post(__('Use a connected account ID that starts with acct_. Values like ca_ are not valid destinations.', 'publishpress-cart'));
        echo '</p></div>';
    } elseif (isset($credentials_status['is_usable']) && ! $credentials_status['is_usable']) {
        echo '<div class="notice notice-warning"><p><strong>' . esc_html__('Stripe reconnect required for the active API mode.', 'publishpress-cart') . '</strong> ';
        echo wp_kses_post(__('Stripe keys are missing or not readable. Use Connect with Stripe to sync direct keys.', 'publishpress-cart'));
        echo '</p></div>';
    } elseif (! empty($credentials_status['requires_reconnect'])) {
        echo '<div class="notice notice-warning"><p><strong>' . esc_html__('Stripe reconnect required for the active API mode.', 'publishpress-cart') . '</strong> ';
        echo wp_kses_post(__('Stripe is using legacy credentials that are not stored as direct Stripe keys. Use Connect with Stripe before removing payment decryptor support.', 'publishpress-cart'));
        echo '</p></div>';
    } elseif ('' === $secret_key) {
        echo '<div class="notice notice-warning"><p><strong>' . esc_html__('Stripe secret key is missing for the active API mode.', 'publishpress-cart') . '</strong> ';
        echo wp_kses_post(__('Connect status could not be verified without a Stripe secret key.', 'publishpress-cart'));
        echo '</p></div>';
    } else {
        try {
            $stripe = ppcart_stripe_client($secret_key);
            $account = $stripe->accounts->retrieve($destination_account, []);

            $charges_enabled = ! empty($account->charges_enabled);
            $payouts_enabled = ! empty($account->payouts_enabled);
            $currently_due_count = 0;
            $past_due_count = 0;

            if (isset($account->requirements)) {
                $currently_due = isset($account->requirements->currently_due) ? (array) $account->requirements->currently_due : [];
                $past_due = isset($account->requirements->past_due) ? (array) $account->requirements->past_due : [];
                $currently_due_count = count($currently_due);
                $past_due_count = count($past_due);
            }

            if ($charges_enabled && $payouts_enabled && 0 === $currently_due_count && 0 === $past_due_count) {
                if (! $this->is_admin_notice_dismissed('stripe_connect_success')) {
                    $stripe_connect_dismiss_url = $this->get_admin_notice_dismiss_url('stripe_connect_success');
                    echo '<div class="notice notice-success"><p><strong>' . esc_html__('Stripe Connect status:', 'publishpress-cart') . '</strong> ';
                    echo esc_html__('Connected account is fully enabled for charges and payouts.', 'publishpress-cart');
                    echo '</p><p><a href="' . esc_url($stripe_connect_dismiss_url) . '">' . esc_html__('Dismiss this notice', 'publishpress-cart') . '</a></p></div>';
                }
            } else {
                echo '<div class="notice notice-warning"><p><strong>' . esc_html__('Stripe Connect status requires attention.', 'publishpress-cart') . '</strong> ';
                /* translators: 1: charges enabled status, 2: payouts enabled status, 3: currently due count, 4: past due count. */
                echo esc_html(sprintf(__('charges_enabled: %1$s, payouts_enabled: %2$s, currently_due: %3$d, past_due: %4$d.', 'publishpress-cart'), $charges_enabled ? 'yes' : 'no', $payouts_enabled ? 'yes' : 'no', $currently_due_count, $past_due_count));
                echo ' <a href="' . esc_url($dashboard_url) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Open connected account in Stripe', 'publishpress-cart') . '</a>';
                echo '</p></div>';
            }
        } catch (Exception $e) {
            echo '<div class="notice notice-warning"><p><strong>' . esc_html__('Stripe Connect status check failed.', 'publishpress-cart') . '</strong> ';
            echo esc_html($e->getMessage());
            echo '</p></div>';
        }
    }
}
