<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

require_once dirname(__DIR__, 3) . '/includes/class-ppcart-base64.php';

trait PPCart_Admin_Stripe_Connect_Encryption_Trait
{
    private function generate_stripe_connect_encryption_key_pair()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-connect-generate-encryption-key-pair.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    private function decrypt_stripe_connect_credentials($encrypted_payload, $pending)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-connect-decrypt-credentials.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    private function decode_stripe_connect_credentials_plaintext($plaintext)
    {
        $data = json_decode((string) $plaintext, true);

        return is_array($data) ? $data : [];
    }

    private function base64url_encode($data)
    {
        return PPCart_Base64::url_encode((string) $data);
    }

    private function base64url_decode($data, $expected_length = null)
    {
        return PPCart_Base64::url_decode((string) $data, $expected_length);
    }
}
