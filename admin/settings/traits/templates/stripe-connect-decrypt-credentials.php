<?php

if (! defined('ABSPATH')) {
    exit;
}


if (empty($pending['encryption']) || ! is_array($pending['encryption'])) {
    return [];
}

$encryption = $pending['encryption'];
$algorithm = isset($encrypted_payload['algorithm']) ? sanitize_key((string) $encrypted_payload['algorithm']) : '';
$expected_algorithm = isset($encryption['algorithm']) ? sanitize_key((string) $encryption['algorithm']) : '';
$public_key_hash = isset($encrypted_payload['public_key_hash']) ? sanitize_text_field((string) $encrypted_payload['public_key_hash']) : '';
$expected_public_key_hash = isset($encryption['public_key_hash']) ? sanitize_text_field((string) $encryption['public_key_hash']) : '';

if ('' === $algorithm || $algorithm !== $expected_algorithm || '' === $public_key_hash || $public_key_hash !== $expected_public_key_hash) {
    return [];
}

if ('sodium_box_seal' === $algorithm && function_exists('sodium_crypto_box_seal_open')) {
    // Cipher text comes from the Connect server response; keys come from the pending state this site stored.
    // Decode strictly and check byte lengths so libsodium never receives malformed input.
    $ciphertext = ! empty($encrypted_payload['ciphertext']) ? PPCart_Base64::decode((string) $encrypted_payload['ciphertext']) : false;
    $public_key = ! empty($encryption['public_key']) ? $this->base64url_decode((string) $encryption['public_key'], SODIUM_CRYPTO_BOX_PUBLICKEYBYTES) : false;
    $private_key = ! empty($encryption['private_key']) ? PPCart_Base64::decode((string) $encryption['private_key'], SODIUM_CRYPTO_BOX_SECRETKEYBYTES) : false;

    if (false === $ciphertext || false === $public_key || false === $private_key || strlen($ciphertext) <= SODIUM_CRYPTO_BOX_SEALBYTES) {
        return [];
    }

    $key_pair = sodium_crypto_box_keypair_from_secretkey_and_publickey($private_key, $public_key);
    $plaintext = sodium_crypto_box_seal_open($ciphertext, $key_pair);

    return false !== $plaintext ? $this->decode_stripe_connect_credentials_plaintext($plaintext) : [];
}

if ('openssl_rsa_aes_256_cbc_hmac_sha256' === $algorithm && function_exists('openssl_private_decrypt')) {
    // Strict decode; the AES-256-CBC IV is exactly 16 bytes and the cipher text is whole 16-byte blocks.
    $encrypted_key = ! empty($encrypted_payload['encrypted_key']) ? PPCart_Base64::decode((string) $encrypted_payload['encrypted_key']) : false;
    $ciphertext = ! empty($encrypted_payload['ciphertext']) ? PPCart_Base64::decode((string) $encrypted_payload['ciphertext']) : false;
    $iv = ! empty($encrypted_payload['iv']) ? PPCart_Base64::decode((string) $encrypted_payload['iv'], 16) : false;
    $hmac = ! empty($encrypted_payload['hmac']) ? (string) $encrypted_payload['hmac'] : '';
    $private_key = isset($encryption['private_key']) ? (string) $encryption['private_key'] : '';

    if (false === $encrypted_key || '' === $encrypted_key || false === $iv || '' === $hmac || '' === $private_key) {
        return [];
    }

    if (false === $ciphertext || '' === $ciphertext || 0 !== strlen($ciphertext) % 16) {
        return [];
    }

    $aes_key = '';
    if (! openssl_private_decrypt($encrypted_key, $aes_key, $private_key, OPENSSL_PKCS1_OAEP_PADDING)) {
        return [];
    }

    $expected_hmac = hash_hmac('sha256', $iv . $ciphertext, $aes_key);
    if (! hash_equals($expected_hmac, $hmac)) {
        return [];
    }

    $plaintext = openssl_decrypt($ciphertext, 'aes-256-cbc', $aes_key, OPENSSL_RAW_DATA, $iv);

    return false !== $plaintext ? $this->decode_stripe_connect_credentials_plaintext($plaintext) : [];
}

return [];
