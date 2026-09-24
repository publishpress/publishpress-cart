<?php

if (! defined('ABSPATH')) {
    exit;
}


$account_id = $this->get_stripe_connect_account_id_for_mode($mode);
$credentials_status = $this->get_stripe_credentials_status_for_mode($mode);
$requires_reconnect = ! empty($credentials_status['requires_reconnect']);
$connect_url = $this->get_stripe_connect_authorize_url($mode);
$connect_label = $requires_reconnect && ! empty($credentials_status['has_raw_keys'])
    ? __('Reconnect with Stripe', 'publishpress-cart')
    : __('Connect with Stripe', 'publishpress-cart');
$disconnect_url = wp_nonce_url(
    add_query_arg(
        [
            'page'                        => PPCart_Admin_Screens::PAGE_SETTINGS,
            'ppcart_stripe_connect_disconnect' => '1',
            'ppcart_stripe_mode'              => $mode,
            'ppcart_payment_subtab'           => 'method:stripe',
        ],
        admin_url('admin.php')
    ),
    'ppcart_stripe_connect_disconnect',
    'ppcart_stripe_connect_nonce'
);

ob_start();

$allowed_html = PPCart_Admin_Stripe_Webhook_Settings::augment_allowed_html(wp_kses_allowed_html('post'));

echo '<div class="ppcart-stripe-connect__section ppcart-stripe-steps">';

if ('' === $connect_server_url) {
    echo '<div class="ppcart-stripe-connect__sync-state is-warning"><span class="ppcart-stripe-connect__dot" aria-hidden="true"></span>' . esc_html__('Connect server not configured', 'publishpress-cart') . '</div>';
    echo '<p class="ppcart-stripe-connect__helper">' . esc_html__('Set PPCART_STRIPE_CONNECT_SERVER_URL to your PublishPress intermediary URL.', 'publishpress-cart') . '</p>';
    echo '</div>';
    return ob_get_clean();
}

$is_connected = ('' !== $account_id) && ! $requires_reconnect && ! empty($credentials_status['is_usable']);

if (! $is_connected) {
    // Step 1 open, step 2 waiting.
    echo '<div class="ppcart-step is-open">';
    echo '<span class="ppcart-step__rail"><span class="ppcart-step__mark is-current">1</span><span class="ppcart-step__line"></span></span>';
    echo '<div class="ppcart-step__panel">';
    echo '<span class="ppcart-step__title">' . esc_html__('1. Connect your Stripe account', 'publishpress-cart') . '</span>';
    echo '<p class="ppcart-step__text">' . esc_html__('We sync your API keys automatically, then walk you through the webhook.', 'publishpress-cart') . '</p>';

    if (! empty($credentials_status['has_raw_keys']) && $requires_reconnect) {
        echo '<p class="ppcart-step__text">' . esc_html__('Your saved keys are not direct Stripe keys, so reconnect before you continue.', 'publishpress-cart') . '</p>';
    }

    echo '<a class="button button-primary ppcart-step__connect" href="' . esc_url($connect_url) . '" data-testid="' . esc_attr(ppcart_testid('ppcart-admin-stripe-connect-' . $mode)) . '"><span class="ppcart-step__connect-icon" aria-hidden="true">S</span>' . esc_html($connect_label) . '</a>';

    if (! $stripe_enabled) {
        echo '<p class="ppcart-step__note">' . esc_html__('Stripe checkout is disabled. You can still connect now.', 'publishpress-cart') . '</p>';
    }

    echo '</div>';
    echo '</div>';

    echo '<div class="ppcart-step is-waiting">';
    echo '<span class="ppcart-step__rail"><span class="ppcart-step__mark">2</span></span>';
    echo '<div class="ppcart-step__body">';
    echo '<span class="ppcart-step__title">' . esc_html__('2. Install the webhook', 'publishpress-cart') . '</span>';
    echo '<span class="ppcart-step__meta">' . esc_html__('Available after you connect.', 'publishpress-cart') . '</span>';
    echo '</div>';
    echo '</div>';

    echo '</div>';
    return ob_get_clean();
}

$dashboard_url = 'test' === $mode
    ? 'https://dashboard.stripe.com/test/connect/accounts/' . rawurlencode($account_id)
    : 'https://dashboard.stripe.com/connect/accounts/' . rawurlencode($account_id);

$webhook = $this->webhooks->get_webhook_state($mode);
$webhook_state = $webhook['state'] ?? 'unknown';
$webhook_note = isset($webhook['note']) ? (string) $webhook['note'] : '';
$webhook_events = isset($webhook['events']) ? (int) $webhook['events'] : 0;

$disconnect_link = '<a class="button ppcart-step__disconnect" href="' . esc_url($disconnect_url) . '" data-testid="' . esc_attr(ppcart_testid('ppcart-admin-stripe-disconnect-' . $mode)) . '">' . esc_html__('Disconnect', 'publishpress-cart') . '</a>';
$dashboard_link = '<a href="' . esc_url($dashboard_url) . '" target="_blank" rel="noopener noreferrer" data-testid="' . esc_attr(ppcart_testid('ppcart-admin-stripe-dashboard-' . $mode)) . '">' . esc_html__('Open in Stripe Dashboard', 'publishpress-cart') . '</a>';

if ('active' === $webhook_state) {
    // Both steps are done, so they collapse into one line.
    echo '<div class="ppcart-ready">';
    echo '<span class="ppcart-step__mark is-done" aria-hidden="true"></span>';
    echo '<span class="ppcart-ready__body">';
    echo '<span class="ppcart-ready__title">' . esc_html__('Stripe is ready', 'publishpress-cart') . '</span>';
    echo '<span class="ppcart-ready__meta">'
        . esc_html__('Keys synced', 'publishpress-cart')
        . ' &middot; '
        /* translators: %d: number of Stripe events. */
        . esc_html(sprintf(__('Webhook active with %d events', 'publishpress-cart'), $webhook_events))
        . ' &middot; ' . wp_kses($dashboard_link, $allowed_html)
        . '</span>';

    if ('' !== $webhook_note) {
        echo '<span class="ppcart-step__note">' . esc_html($webhook_note) . '</span>';
    }

    echo '</span>';
    echo wp_kses($disconnect_link, $allowed_html);
    echo '</div>';
    echo '</div>';
    return ob_get_clean();
}

// Step 1 done, step 2 still open.
echo '<div class="ppcart-step is-done">';
echo '<span class="ppcart-step__rail"><span class="ppcart-step__mark is-done" aria-hidden="true"></span><span class="ppcart-step__line"></span></span>';
echo '<div class="ppcart-step__body">';
echo '<span class="ppcart-step__title">' . esc_html__('1. Stripe account connected', 'publishpress-cart') . '</span>';
echo '<span class="ppcart-step__meta">' . esc_html__('Keys synced', 'publishpress-cart') . ' &middot; ' . wp_kses($dashboard_link, $allowed_html) . '</span>';
echo '</div>';
echo wp_kses($disconnect_link, $allowed_html);
echo '</div>';

echo '<div class="ppcart-step is-open">';
echo '<span class="ppcart-step__rail"><span class="ppcart-step__mark is-current">2</span></span>';
echo '<div class="ppcart-step__panel">';
echo '<div class="ppcart-step__head">';
echo '<span class="ppcart-step__headings">';
echo '<span class="ppcart-step__title">' . esc_html__('2. Install the webhook', 'publishpress-cart') . '</span>';
echo '<span class="ppcart-step__warn">' . esc_html__('Orders will not complete until this is done.', 'publishpress-cart') . '</span>';
echo '</span>';
echo '<span class="ppcart-chip is-warning"><span class="ppcart-chip__dot" aria-hidden="true"></span>'
    . ('incomplete' === $webhook_state ? esc_html__('Needs attention', 'publishpress-cart') : esc_html__('Not installed', 'publishpress-cart'))
    . '</span>';
echo '</div>';

$show_owner_key_form = in_array($webhook_state, [ 'missing', 'blocked' ], true)
    || ('incomplete' === $webhook_state && empty($webhook['fix_url']));

if ($show_owner_key_form) {
    echo wp_kses($this->webhooks->get_stripe_webhook_manual_setup_html($mode), $allowed_html);
} else {
    if ('' !== $webhook_note) {
        echo '<p class="ppcart-step__text">' . esc_html($webhook_note) . '</p>';
    }

    if (! empty($webhook['fix_url'])) {
        echo '<p class="ppcart-step__links"><a href="' . esc_url($webhook['fix_url']) . '" data-testid="' . esc_attr(ppcart_testid('ppcart-admin-stripe-webhook-' . $mode . '-update-events')) . '">' . esc_html__('Fix the events', 'publishpress-cart') . '</a></p>';
    }
}

echo '</div>';
echo '</div>';

echo '</div>';

return ob_get_clean();
