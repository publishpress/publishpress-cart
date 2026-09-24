<?php

if (! defined('ABSPATH')) {
    exit;
}


$form_id = 'ppcart-stripe-webhook-form-' . $mode;
$key_page_url = 'live' === $mode
    ? 'https://dashboard.stripe.com/apikeys/create'
    : 'https://dashboard.stripe.com/test/apikeys/create';
$webhook_url = $this->get_stripe_webhook_url();
$event_count = count($this->get_expected_stripe_webhook_events());

ob_start();

echo '<p class="ppcart-step__text">'
    . sprintf(
        /* translators: %s: the Stripe permission name, wrapped in strong tags. */
        esc_html__('Stripe needs a temporary restricted key with %s. We use it once, install the endpoint, and never store it.', 'publishpress-cart'),
        '<strong>' . esc_html__('Webhook endpoints: Write', 'publishpress-cart') . '</strong>'
    )
    . '</p>';

echo '<label class="screen-reader-text" for="' . esc_attr($form_id . '-key') . '">' . esc_html__('Temporary Stripe key', 'publishpress-cart') . '</label>';
echo '<span class="ppcart-step__field">';
echo '<input type="password" autocomplete="off" spellcheck="false"'
    . ' class="ppcart-step__input"'
    . ' id="' . esc_attr($form_id . '-key') . '"'
    . ' name="ppcart_stripe_webhook_account_key"'
    . ' form="' . esc_attr($form_id) . '"'
    . ' placeholder="' . esc_attr('rk_' . $mode . '_...') . '"'
    . ' data-testid="' . esc_attr(ppcart_testid('ppcart-admin-stripe-webhook-' . $mode . '-account-key')) . '" />';
echo '<button type="submit" class="button button-primary"'
    . ' form="' . esc_attr($form_id) . '"'
    . ' data-testid="' . esc_attr(ppcart_testid('ppcart-admin-stripe-webhook-' . $mode . '-install')) . '">'
    . esc_html__('Install webhook', 'publishpress-cart')
    . '</button>';
echo '</span>';

echo '<span class="ppcart-step__links">';
echo '<a href="' . esc_url($key_page_url) . '" target="_blank" rel="noopener noreferrer" data-testid="' . esc_attr(ppcart_testid('ppcart-admin-stripe-webhook-' . $mode . '-key-page')) . '">'
    . esc_html__('Create the key in Stripe', 'publishpress-cart')
    . '</a>';
echo '<a href="#ppcart-webhook-manual-' . esc_attr($mode) . '" class="ppcart-step__toggle" data-ppcart-toggle="ppcart-webhook-manual-' . esc_attr($mode) . '">'
    . esc_html__('I want to set it up in Stripe myself', 'publishpress-cart')
    . '</a>';
echo '</span>';

$reveal_manual = $mode === get_transient('ppcart_stripe_webhook_need_signing_secret');
if ($reveal_manual) {
    delete_transient('ppcart_stripe_webhook_need_signing_secret');
}

echo '<div class="ppcart-step__manual" id="ppcart-webhook-manual-' . esc_attr($mode) . '"' . ($reveal_manual ? '' : ' hidden') . '>';
echo '<p class="ppcart-step__text">' . esc_html__('Create an endpoint in Stripe with this URL:', 'publishpress-cart') . '</p>';
echo '<p class="ppcart-step__url"><code>' . esc_html($webhook_url) . '</code></p>';
echo '<p class="ppcart-step__text">'
    . sprintf(
        /* translators: %d: number of Stripe events. */
        esc_html__('Select the %d events listed in our documentation, then paste that endpoint signing secret:', 'publishpress-cart'),
        (int) $event_count
    )
    . '</p>';
echo '<label class="screen-reader-text" for="' . esc_attr($form_id . '-secret') . '">' . esc_html__('Signing secret', 'publishpress-cart') . '</label>';
echo '<span class="ppcart-step__field">';
echo '<input type="password" autocomplete="off" spellcheck="false"'
    . ' class="ppcart-step__input"'
    . ' id="' . esc_attr($form_id . '-secret') . '"'
    . ' name="ppcart_stripe_webhook_signing_secret"'
    . ' form="' . esc_attr($form_id) . '"'
    . ' placeholder="whsec_..."'
    . ' data-testid="' . esc_attr(ppcart_testid('ppcart-admin-stripe-webhook-' . $mode . '-signing-secret')) . '" />';
echo '<button type="submit" class="button"'
    . ' form="' . esc_attr($form_id) . '"'
    . ' data-testid="' . esc_attr(ppcart_testid('ppcart-admin-stripe-webhook-' . $mode . '-save-secret')) . '">'
    . esc_html__('Save', 'publishpress-cart')
    . '</button>';
echo '</span>';
echo '</div>';

return ob_get_clean();
