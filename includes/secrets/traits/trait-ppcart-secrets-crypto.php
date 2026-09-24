<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Secrets_Crypto_Trait
{
    /**
     * Whether a stored value is encrypted.
     *
     * @param string $value Stored value.
     * @return bool
     */
    public static function is_encrypted_value($value)
    {
        return is_string($value) && 0 === strpos($value, self::ENCRYPTED_PREFIX);
    }

    /**
     * Encrypts a plaintext string.
     *
     * @param string $plaintext Plaintext.
     * @return string|false
     */
    public static function encrypt_value($plaintext)
    {
        if (! function_exists('openssl_encrypt')) {
            return false;
        }

        $key = self::get_encryption_key();
        if ('' === $key) {
            return false;
        }

        $iv_length = openssl_cipher_iv_length('AES-256-CBC');
        if (false === $iv_length || $iv_length < 1) {
            return false;
        }

        $iv = openssl_random_pseudo_bytes($iv_length);
        if (false === $iv) {
            return false;
        }

        $ciphertext = openssl_encrypt($plaintext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        if (false === $ciphertext) {
            return false;
        }

        return self::ENCRYPTED_PREFIX . base64_encode($iv . $ciphertext);
    }

    /**
     * Decrypts an encrypted string.
     *
     * @param string $stored Stored value.
     * @return string|false
     */
    public static function decrypt_value($stored)
    {
        if (! self::is_encrypted_value($stored) || ! function_exists('openssl_decrypt')) {
            return false;
        }

        $key = self::get_encryption_key();
        if ('' === $key) {
            return false;
        }

        $payload = base64_decode(substr($stored, strlen(self::ENCRYPTED_PREFIX)), true);
        if (false === $payload) {
            return false;
        }

        $iv_length = openssl_cipher_iv_length('AES-256-CBC');
        if (false === $iv_length || strlen($payload) <= $iv_length) {
            return false;
        }

        $iv         = substr($payload, 0, $iv_length);
        $ciphertext = substr($payload, $iv_length);
        $plaintext  = openssl_decrypt($ciphertext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

        return false === $plaintext ? false : $plaintext;
    }

    /**
     * Returns encryption key bytes.
     *
     * @return string
     */
    private static function get_encryption_key()
    {
        if (defined('PPCART_SECRETS_KEY') && '' !== PPCART_SECRETS_KEY) {
            return hash('sha256', (string) PPCART_SECRETS_KEY, true);
        }

        if (defined('AUTH_KEY') && '' !== AUTH_KEY) {
            return hash('sha256', AUTH_KEY . 'publishpress-cart-secrets', true);
        }

        return '';
    }

    /**
     * Human-readable encryption key source label.
     *
     * @return string
     */
    private static function get_encryption_key_source()
    {
        if (defined('PPCART_SECRETS_KEY') && '' !== PPCART_SECRETS_KEY) {
            return 'PPCART_SECRETS_KEY';
        }

        if (defined('AUTH_KEY') && '' !== AUTH_KEY) {
            return 'AUTH_KEY';
        }

        return __('Unavailable', 'publishpress-cart');
    }
}
