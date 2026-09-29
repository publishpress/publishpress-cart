<?php

namespace unit\Secrets;

use Codeception\Test\Unit;
use PPCart_Base64;
use PPCart_Secrets;
use UnitTester;

require_once PPCART_PLUGIN_ROOT . 'includes/class-ppcart-base64.php';
require_once PPCART_PLUGIN_ROOT . 'admin/settings/traits/trait-ppcart-admin-stripe-connect-encryption.php';

class StripeConnectEncryptionHarness
{
    use \PPCart_Admin_Stripe_Connect_Encryption_Trait;

    public function generateKeyPair()
    {
        return $this->generate_stripe_connect_encryption_key_pair();
    }

    public function decrypt($encrypted_payload, $pending)
    {
        return $this->decrypt_stripe_connect_credentials($encrypted_payload, $pending);
    }
}

class Base64CryptoTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    protected function _before(): void
    {
        if (! defined('AUTH_KEY')) {
            define('AUTH_KEY', 'unit-test-auth-key-for-secrets');
        }

        require_once PPCART_PLUGIN_ROOT . 'includes/logging/class-ppcart-debug-log-viewer.php';
        require_once PPCART_PLUGIN_ROOT . 'includes/class-ppcart-secrets.php';
    }

    public function test_base64_round_trips_binary_bytes_and_rejects_invalid_input(): void
    {
        $bytes = random_bytes(64) . "\0\xff+/=";

        $this->assertSame($bytes, PPCart_Base64::decode(PPCart_Base64::encode($bytes)));
        $this->assertSame($bytes, PPCart_Base64::url_decode(PPCart_Base64::url_encode($bytes)));
        $this->assertDoesNotMatchRegularExpression('/[+\/=]/', PPCart_Base64::url_encode($bytes));

        $this->assertFalse(PPCart_Base64::decode('not*valid*base64'));
        $this->assertFalse(PPCart_Base64::decode(['array']));
        $this->assertFalse(PPCart_Base64::decode(null));
        $this->assertFalse(PPCart_Base64::url_decode('bad!chars'));
    }

    public function test_base64_decode_enforces_the_expected_byte_length(): void
    {
        $sixteen = PPCart_Base64::encode(str_repeat('a', 16));

        $this->assertSame(str_repeat('a', 16), PPCart_Base64::decode($sixteen, 16));
        $this->assertFalse(PPCart_Base64::decode($sixteen, 32));
        $this->assertFalse(PPCart_Base64::url_decode(PPCart_Base64::url_encode('short'), 32));
    }

    public function test_basic_auth_header_uses_rfc_7617_credentials(): void
    {
        $this->assertSame('Basic dXNlcjpwYXNzOndvcmQ=', PPCart_Base64::basic_auth_header('user', 'pass:word'));
    }

    public function test_secrets_decrypt_reads_values_stored_in_the_existing_ppenc1_format(): void
    {
        $this->requireOpenSsl();

        // Build a payload the way earlier releases stored it: prefix + base64(iv . aes-256-cbc ciphertext).
        $key        = hash('sha256', AUTH_KEY . 'publishpress-cart-secrets', true);
        $iv         = str_repeat("\x01", 16);
        $ciphertext = openssl_encrypt('sk_live_existing_value', 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        $stored     = PPCart_Secrets::ENCRYPTED_PREFIX . base64_encode($iv . $ciphertext);

        if (defined('PPCART_SECRETS_KEY')) {
            $this->markTestSkipped('PPCART_SECRETS_KEY overrides the AUTH_KEY-derived key in this process.');
        }

        $this->assertSame('sk_live_existing_value', PPCart_Secrets::decrypt_value($stored));
    }

    public function test_secrets_decrypt_rejects_malformed_and_truncated_payloads(): void
    {
        $this->requireOpenSsl();

        $valid = PPCart_Secrets::encrypt_value('sk_test_value');
        $this->assertIsString($valid);

        $payload = base64_decode(substr($valid, strlen(PPCart_Secrets::ENCRYPTED_PREFIX)), true);

        $this->assertFalse(PPCart_Secrets::decrypt_value(PPCart_Secrets::ENCRYPTED_PREFIX . '***'));
        $this->assertFalse(PPCart_Secrets::decrypt_value(PPCart_Secrets::ENCRYPTED_PREFIX));
        // IV only, no cipher text.
        $this->assertFalse(PPCart_Secrets::decrypt_value(PPCart_Secrets::ENCRYPTED_PREFIX . base64_encode(substr($payload, 0, 16))));
        // Cipher text that is not whole AES blocks.
        $this->assertFalse(PPCart_Secrets::decrypt_value(PPCart_Secrets::ENCRYPTED_PREFIX . base64_encode(substr($payload, 0, -1))));
        $this->assertFalse(PPCart_Secrets::decrypt_value('plain-value'));
    }

    public function test_stripe_connect_sodium_credentials_decrypt_and_reject_bad_key_material(): void
    {
        if (! function_exists('sodium_crypto_box_seal')) {
            $this->markTestSkipped('libsodium is not available.');
        }

        $harness  = new StripeConnectEncryptionHarness();
        $key_pair = $harness->generateKeyPair();
        $this->assertSame('sodium_box_seal', $key_pair['algorithm']);

        $public_key = PPCart_Base64::url_decode($key_pair['public_key']);
        $plaintext  = json_encode([ 'publishable_key' => 'pk_test_1', 'secret_key' => 'sk_test_1', 'account_id' => 'acct_1' ]);
        $payload    = [
            'algorithm'       => 'sodium_box_seal',
            'public_key_hash' => $key_pair['public_key_hash'],
            'ciphertext'      => base64_encode(sodium_crypto_box_seal($plaintext, $public_key)),
        ];
        $pending = [ 'encryption' => $key_pair ];

        $this->assertSame('acct_1', $harness->decrypt($payload, $pending)['account_id']);

        // A private key of the wrong length must be rejected, not passed to libsodium (which would throw).
        $bad_pending = [ 'encryption' => array_merge($key_pair, [ 'private_key' => base64_encode('short') ]) ];
        $this->assertSame([], $harness->decrypt($payload, $bad_pending));

        // Cipher text shorter than the sealed-box overhead is rejected.
        $this->assertSame([], $harness->decrypt(array_merge($payload, [ 'ciphertext' => base64_encode('x') ]), $pending));

        // Non-base64 cipher text is rejected.
        $this->assertSame([], $harness->decrypt(array_merge($payload, [ 'ciphertext' => '%%%' ]), $pending));
    }

    public function test_stripe_connect_openssl_credentials_reject_a_wrong_length_iv(): void
    {
        $this->requireOpenSsl();

        $resource = openssl_pkey_new([ 'private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA ]);
        if (! $resource || ! openssl_pkey_export($resource, $private_key)) {
            $this->markTestSkipped('OpenSSL cannot generate an RSA key here.');
        }

        $public_pem = openssl_pkey_get_details($resource)['key'];
        $aes_key    = random_bytes(32);
        $iv         = random_bytes(16);
        $plaintext  = json_encode([ 'publishable_key' => 'pk_test_2', 'secret_key' => 'sk_test_2', 'account_id' => 'acct_2' ]);
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-cbc', $aes_key, OPENSSL_RAW_DATA, $iv);
        openssl_public_encrypt($aes_key, $encrypted_key, $public_pem, OPENSSL_PKCS1_OAEP_PADDING);

        $pending = [
            'encryption' => [
                'algorithm'       => 'openssl_rsa_aes_256_cbc_hmac_sha256',
                'public_key_hash' => 'hash',
                'private_key'     => $private_key,
            ],
        ];
        $payload = [
            'algorithm'       => 'openssl_rsa_aes_256_cbc_hmac_sha256',
            'public_key_hash' => 'hash',
            'encrypted_key'   => base64_encode($encrypted_key),
            'ciphertext'      => base64_encode($ciphertext),
            'iv'              => base64_encode($iv),
            'hmac'            => hash_hmac('sha256', $iv . $ciphertext, $aes_key),
        ];

        $harness = new StripeConnectEncryptionHarness();
        $this->assertSame('acct_2', $harness->decrypt($payload, $pending)['account_id']);

        $this->assertSame([], $harness->decrypt(array_merge($payload, [ 'iv' => base64_encode(random_bytes(8)) ]), $pending));
        $this->assertSame([], $harness->decrypt(array_merge($payload, [ 'ciphertext' => base64_encode(substr($ciphertext, 0, -1)) ]), $pending));
    }

    private function requireOpenSsl(): void
    {
        if (! function_exists('openssl_encrypt')) {
            $this->markTestSkipped('OpenSSL is not available.');
        }
    }
}
