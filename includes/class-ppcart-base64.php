<?php

/**
 * Base64 transport encoding for binary data.
 *
 * This is the one place where the plugin calls base64_encode() and
 * base64_decode(). The data is always binary transport or storage material:
 * cipher text, IVs, key bytes, HTTP Basic credentials, or image bytes for a
 * data URI. The decoded result is never evaluated or included as code.
 *
 * @package PublishPress_Cart
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Strict base64 and base64url encode/decode helpers.
 */
class PPCart_Base64
{
    /**
     * Encodes binary bytes as standard base64.
     *
     * @param string $bytes Binary bytes.
     * @return string
     */
    public static function encode($bytes)
    {
        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Binary transport/storage encoding, not code obfuscation.
        return base64_encode((string) $bytes);
    }

    /**
     * Decodes standard base64 in strict mode.
     *
     * @param mixed    $encoded         Encoded string.
     * @param int|null $expected_length Required length of the decoded bytes, or null for any length.
     * @return string|false Decoded bytes, or false when the input is not a string, not valid base64,
     *                      or not the expected length.
     */
    public static function decode($encoded, $expected_length = null)
    {
        if (! is_string($encoded)) {
            return false;
        }

        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Strict decode of binary crypto material; the result is never executed.
        $decoded = base64_decode($encoded, true);

        if (false === $decoded) {
            return false;
        }

        if (null !== $expected_length && strlen($decoded) !== (int) $expected_length) {
            return false;
        }

        return $decoded;
    }

    /**
     * Encodes binary bytes as unpadded base64url (RFC 4648 section 5).
     *
     * @param string $bytes Binary bytes.
     * @return string
     */
    public static function url_encode($bytes)
    {
        return rtrim(strtr(self::encode($bytes), '+/', '-_'), '=');
    }

    /**
     * Decodes base64url (padded or unpadded) in strict mode.
     *
     * @param mixed    $encoded         Encoded string.
     * @param int|null $expected_length Required length of the decoded bytes, or null for any length.
     * @return string|false
     */
    public static function url_decode($encoded, $expected_length = null)
    {
        if (! is_string($encoded)) {
            return false;
        }

        $encoded = strtr($encoded, '-_', '+/');
        $padding = strlen($encoded) % 4;

        if ($padding) {
            $encoded .= str_repeat('=', 4 - $padding);
        }

        return self::decode($encoded, $expected_length);
    }

    /**
     * Builds an HTTP Basic Authorization header value (RFC 7617).
     *
     * @param string $username User name or client ID.
     * @param string $password Password, API key, or client secret.
     * @return string
     */
    public static function basic_auth_header($username, $password)
    {
        return 'Basic ' . self::encode((string) $username . ':' . (string) $password);
    }
}
