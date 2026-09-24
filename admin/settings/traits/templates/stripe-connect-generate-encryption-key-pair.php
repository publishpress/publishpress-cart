<?php

if (! defined('ABSPATH')) {
    exit;
}


if (function_exists('sodium_crypto_box_keypair')) {
    $key_pair = sodium_crypto_box_keypair();
    $public_key = $this->base64url_encode(sodium_crypto_box_publickey($key_pair));

    return [
        'algorithm'       => 'sodium_box_seal',
        'public_key'      => $public_key,
        'private_key'     => base64_encode(sodium_crypto_box_secretkey($key_pair)),
        'public_key_hash' => hash('sha256', $public_key),
    ];
}

if (function_exists('openssl_pkey_new')) {
    $resource = openssl_pkey_new([
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ]);

    if ($resource && openssl_pkey_export($resource, $private_key)) {
        $details = openssl_pkey_get_details($resource);
        if (! empty($details['key'])) {
            $public_key = $this->base64url_encode((string) $details['key']);

            return [
                'algorithm'       => 'openssl_rsa_aes_256_cbc_hmac_sha256',
                'public_key'      => $public_key,
                'private_key'     => (string) $private_key,
                'public_key_hash' => hash('sha256', $public_key),
            ];
        }
    }
}

return [];
