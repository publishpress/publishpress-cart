<?php

if (! defined('ABSPATH')) {
    exit;
}


global $ppcart_debug_logger;

// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Checkout nonce is validated by the primary order flow before this integration callback runs.
$intent_id = isset($_POST['intent_id']) ? sanitize_text_field(wp_unslash($_POST['intent_id'])) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Checkout nonce is validated by the primary order flow before this integration callback runs.
$posted_action = isset($_POST['action']) ? sanitize_key(wp_unslash($_POST['action'])) : '';
if (! empty($intent_id) || 'ppcart_create_subscription' === sanitize_key((string) $posted_action)) {
    return;
}

$site_secret = $this->site_secret;
// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Checkout nonce is validated by the primary order flow before this integration callback runs.
$recaptcha_raw = isset($_POST['g-recaptcha-response']) ? sanitize_text_field(wp_unslash($_POST['g-recaptcha-response'])) : '';
if (! is_string($recaptcha_raw) || '' === trim($recaptcha_raw)) {
    echo wp_json_encode([ 'error' => esc_html__('Captcha error: The response parameter is missing', 'publishpress-cart') ]);
    exit();
}
$recaptcha = $recaptcha_raw;
$res = $this->reCaptcha($recaptcha, $site_secret);

if (!empty($res['error-codes'][0])) {
    $errorcodes = $res['error-codes'];

    switch ($errorcodes[0]) {
        case 'missing-input-secret':
            $error_message = esc_html__('Captcha error: The secret parameter is missing', 'publishpress-cart');
            break;
        case 'invalid-input-secret':
            $error_message = esc_html__('Captcha error: The secret parameter is invalid', 'publishpress-cart');
            break;
        case 'missing-input-response':
            $error_message = esc_html__('Captcha error: The response parameter is missing', 'publishpress-cart');
            break;
        case 'invalid-input-response':
            $error_message = esc_html__('Captcha error: The response parameter is invalid', 'publishpress-cart');
            break;
        case 'bad-request':
            $error_message = esc_html__('Captcha error: The request is invalid', 'publishpress-cart');
            break;
        case 'timeout-or-duplicate':
            $error_message = esc_html__('Captcha error: The response is a duplicate or the service has timed out', 'publishpress-cart');
            break;
        default:
            $error_message = esc_html__('Something went wrong, please try again', 'publishpress-cart');
            break;
    }

    $ppcart_debug_logger->log_debug($error_message . ' - ' . wp_json_encode($res), 4);

    echo wp_json_encode(['error' => $error_message]);
    exit();
}

if ($this->version_type == 'v3' && $res["score"] < $this->score) {
    echo wp_json_encode(['error' => esc_html__('Something went wrong, please try again!', 'publishpress-cart')]);
    exit();
}
