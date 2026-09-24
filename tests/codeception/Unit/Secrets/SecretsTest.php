<?php

namespace unit\Secrets;

use Codeception\Test\Unit;
use PPCart_Secrets;
use Tests\Support\WordPressStubContext;
use UnitTester;

class SecretsTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var array<string, mixed>
     */
    private $options = [];

    /**
     * @var array<string, string>
     */
    private $rawOptions = [];

    /**
     * @var bool
     */
    private $canManageOptions = true;

    protected function _before(): void
    {
        if (! defined('AUTH_KEY')) {
            define('AUTH_KEY', 'unit-test-auth-key-for-secrets');
        }

        $this->options          = [];
        $this->rawOptions       = [];
        $this->canManageOptions = true;

        require_once PPCART_PLUGIN_ROOT . 'includes/logging/class-ppcart-debug-log-viewer.php';
        require_once PPCART_PLUGIN_ROOT . 'includes/class-ppcart-secrets.php';
        require_once PPCART_PLUGIN_ROOT . 'includes/helpers/ppcart-secrets-functions.php';

        // Reset per-request alloptions latch so tests stay isolated in one process.
        $reflection = new \ReflectionClass(PPCart_Secrets::class);
        if ($reflection->hasProperty('alloptions_decrypt_done')) {
            $prop = $reflection->getProperty('alloptions_decrypt_done');
            $prop->setAccessible(true);
            $prop->setValue(null, false);
        }
        if ($reflection->hasProperty('sensitive_option_names_cache')) {
            $cache = $reflection->getProperty('sensitive_option_names_cache');
            $cache->setAccessible(true);
            $cache->setValue(null, null);
        }

        WordPressStubContext::set(
            'get_option',
            function ($key, $default = false) {
                if (array_key_exists($key, $this->options)) {
                    $value = $this->options[ $key ];
                } elseif (array_key_exists($key, $this->rawOptions)) {
                    $value = $this->rawOptions[ $key ];
                } else {
                    return $default;
                }

                if (PPCart_Secrets::ENCRYPT_SECRETS_OPTION === $key) {
                    return $value;
                }

                if (is_string($value) && PPCart_Secrets::is_encrypted_value($value)) {
                    $decrypted = PPCart_Secrets::decrypt_value($value);

                    return false !== $decrypted ? $decrypted : $value;
                }

                return $value;
            }
        );

        WordPressStubContext::set(
            'update_option',
            function ($key, $value) {
                if (is_string($value) && PPCart_Secrets::encryption_enabled() && ! PPCart_Secrets::is_encrypted_value($value)) {
                    $encrypted = PPCart_Secrets::encrypt_value($value);
                    if (false !== $encrypted) {
                        $value = $encrypted;
                    }
                }

                $this->options[ $key ]    = $value;
                $this->rawOptions[ $key ] = is_string($value) ? $value : (string) $value;

                return true;
            }
        );

        WordPressStubContext::set('apply_filters', function ($hook, $value) {
            return $value;
        });

        WordPressStubContext::set('add_filter', function () {
            return true;
        });

        WordPressStubContext::set('add_action', function () {
            return true;
        });

        if (! function_exists('current_user_can')) {
            eval('function current_user_can($cap){ return \\Tests\\Support\\WordPressStubContext::invoke("current_user_can", func_get_args()); }');
        }
        WordPressStubContext::set(
            'current_user_can',
            function ($cap) {
                return 'manage_options' === $cap ? $this->canManageOptions : false;
            }
        );

        if (! function_exists('get_current_user_id')) {
            eval('function get_current_user_id(){ return \\Tests\\Support\\WordPressStubContext::invoke("get_current_user_id", func_get_args()); }');
        }
        WordPressStubContext::set('get_current_user_id', function () {
            return 1;
        });

        if (! function_exists('is_admin')) {
            eval('function is_admin(){ return true; }');
        }

        if (! function_exists('wp_doing_cron')) {
            eval('function wp_doing_cron(){ return false; }');
        }

        if (! function_exists('wp_doing_ajax')) {
            eval('function wp_doing_ajax(){ return false; }');
        }

        if (! function_exists('set_transient')) {
            eval('function set_transient($key, $value, $expiration){ return \\Tests\\Support\\WordPressStubContext::invoke("set_transient", func_get_args()); }');
        }
        WordPressStubContext::set('set_transient', function () {
            return true;
        });

        $this->mockWpdb();

        $reflection = new \ReflectionClass(PPCart_Secrets::class);
        $property   = $reflection->getProperty('initialized');
        $property->setAccessible(true);
        $property->setValue(null, false);

        PPCart_Secrets::init();
    }

    public function test_UT_146_at_rest_encryption_stays_off_when_the_database_flag_is_absent(): void
    {
        $this->requireDatabaseEncryptionControl();

        $this->setDbEncryptionEnabled(false);

        $this->assertFalse(PPCart_Secrets::is_db_encryption_enabled());
        $this->assertFalse(PPCart_Secrets::encryption_enabled());
        $this->assertSame('disabled', PPCart_Secrets::get_encryption_control_source());
    }

    public function test_UT_147_database_flag_turns_on_at_rest_encryption_and_reports_the_database_source(): void
    {
        $this->requireDatabaseEncryptionControl();

        $this->setDbEncryptionEnabled(true);

        $this->assertTrue(PPCart_Secrets::is_db_encryption_enabled());
        $this->assertTrue(PPCart_Secrets::encryption_enabled());
        $this->assertSame('database', PPCart_Secrets::get_encryption_control_source());
    }

    public function test_UT_148_sensitive_option_registry_recognizes_credential_keys_and_ignores_non_secrets(): void
    {
        $this->assertTrue(ppcart_is_sensitive_option('_ppcart_stripe_test_sk'));
        $this->assertTrue(ppcart_is_sensitive_option('_ppcart_stripe_test_webhook_secret'));
        $this->assertTrue(ppcart_is_sensitive_option('_ppcart_mailchimp_api'));
        $this->assertTrue(ppcart_is_sensitive_option('_ppcart_drip_api_key'));
        $this->assertFalse(ppcart_is_sensitive_option('_ppcart_currency'));
        $this->assertFalse(ppcart_is_sensitive_option(PPCart_Secrets::ENCRYPT_SECRETS_OPTION));
        $this->assertFalse(ppcart_is_sensitive_option('_sc_mailchimp_api'));
        $this->assertFalse(ppcart_is_sensitive_option('_sc_currency'));
    }

    public function test_UT_148_encryption_control_flag_stays_plaintext_and_ciphertext_does_not_enable(): void
    {
        $this->requireDatabaseEncryptionControl();
        $this->requireOpenSsl();

        $this->setDbEncryptionEnabled(true);

        $stored = PPCart_Secrets::filter_pre_update_any_option(
            '1',
            PPCart_Secrets::ENCRYPT_SECRETS_OPTION,
            '0'
        );

        $this->assertSame('1', $stored);
        $this->assertFalse(PPCart_Secrets::is_encrypted_value($stored));

        $this->options[ PPCart_Secrets::ENCRYPT_SECRETS_OPTION ] = 'ppenc1:not-a-real-payload';

        $this->assertFalse(PPCart_Secrets::is_db_encryption_enabled());
        $this->assertFalse(PPCart_Secrets::encryption_enabled());
        $this->assertSame('disabled', PPCart_Secrets::get_encryption_control_source());
    }

    public function test_UT_149_encrypt_decrypt_round_trip_restores_the_exact_secret_value(): void
    {
        $this->requireOpenSsl();

        $plain     = 'sk_test_unit_secret_value_12345';
        $encrypted = PPCart_Secrets::encrypt_value($plain);

        $this->assertIsString($encrypted);
        $this->assertTrue(PPCart_Secrets::is_encrypted_value($encrypted));
        $this->assertSame($plain, PPCart_Secrets::decrypt_value($encrypted));
    }

    public function test_UT_150_pre_update_hook_encrypts_a_sensitive_option_before_it_is_stored(): void
    {
        $this->requireOpenSsl();
        $this->requireDatabaseEncryptionControl();
        $this->setDbEncryptionEnabled(true);

        $stored = PPCart_Secrets::filter_pre_update_any_option('sk_test_new_key_value', '_ppcart_stripe_test_sk', '');

        $this->assertTrue(PPCart_Secrets::is_encrypted_value($stored));
    }

    public function test_UT_151_pre_update_hook_stores_plaintext_when_the_database_flag_is_off(): void
    {
        $this->requireOpenSsl();
        $this->requireDatabaseEncryptionControl();
        $this->setDbEncryptionEnabled(false);

        $plain  = 'sk_test_plain_when_disabled';
        $stored = PPCart_Secrets::filter_pre_update_any_option($plain, '_ppcart_stripe_test_sk', '');

        $this->assertSame($plain, $stored);
        $this->assertFalse(PPCart_Secrets::is_encrypted_value($stored));
    }

    public function test_UT_152_empty_secret_field_submission_preserves_the_stored_credential(): void
    {
        $this->options['_ppcart_paypal_secret'] = 'existing-secret';

        $stored = PPCart_Secrets::sanitize_secret_field('', '_ppcart_paypal_secret');

        $this->assertSame('existing-secret', $stored);
    }

    public function test_UT_153_pre_update_hook_blocks_secret_changes_without_manage_options(): void
    {
        $this->canManageOptions = false;
        $old                    = 'sk_test_existing';

        $stored = PPCart_Secrets::filter_pre_update_any_option('sk_test_changed', '_ppcart_stripe_test_sk', $old);

        $this->assertSame($old, $stored);
    }

    public function test_UT_154_manual_migration_encrypts_existing_plaintext_credentials_at_rest(): void
    {
        $this->requireOpenSsl();
        $this->requireDatabaseEncryptionControl();
        $this->setDbEncryptionEnabled(true);

        $this->rawOptions['_ppcart_stripe_test_sk'] = 'sk_test_plaintext_migration';
        $this->options['_ppcart_stripe_test_sk']    = 'sk_test_plaintext_migration';

        $result = PPCart_Secrets::migrate_plaintext_secrets();

        $this->assertIsArray($result);
        $this->assertSame(1, $result['migrated']);
        $this->assertTrue(PPCart_Secrets::is_encrypted_value($this->rawOptions['_ppcart_stripe_test_sk']));
    }

    public function test_UT_155_migration_refuses_to_run_while_encryption_is_disabled(): void
    {
        $this->requireDatabaseEncryptionControl();
        $this->setDbEncryptionEnabled(false);

        $result = PPCart_Secrets::migrate_plaintext_secrets();

        $this->assertTrue(is_wp_error($result));
    }

    public function test_UT_156_plaintext_credential_detector_flags_unencrypted_secrets_and_builds_the_admin_notice(): void
    {
        $this->requireDatabaseEncryptionControl();
        $this->setDbEncryptionEnabled(false);

        $this->assertFalse(PPCart_Secrets::has_plaintext_stored_credentials());

        $this->rawOptions['_ppcart_stripe_test_sk'] = 'sk_test_plaintext';

        $this->assertTrue(PPCart_Secrets::has_plaintext_stored_credentials());

        $status = PPCart_Secrets::get_migration_status();
        $this->assertSame(1, $status['plaintext_count']);
        $this->assertTrue($status['has_plaintext_secrets']);
        $this->assertStringContainsString('encrypt it automatically', PPCart_Secrets::get_plaintext_migration_notice($status));
    }

    public function test_UT_157_maybe_decrypt_option_value_decrypts_ciphertext_and_passes_plaintext_through(): void
    {
        $this->requireOpenSsl();

        $plain     = 'ac-secret-key-value';
        $encrypted = PPCart_Secrets::encrypt_value($plain);

        $this->assertSame($plain, PPCart_Secrets::maybe_decrypt_option_value($encrypted));
        $this->assertSame('still-plain', PPCart_Secrets::maybe_decrypt_option_value('still-plain'));
    }

    public function test_UT_158_autoloaded_options_filter_decrypts_sensitive_keys_and_leaves_others_untouched(): void
    {
        $this->requireOpenSsl();
        $this->requireDatabaseEncryptionControl();
        $this->setDbEncryptionEnabled(true);

        $encrypted = PPCart_Secrets::encrypt_value('ac-secret-key-value');
        // decrypt_alloptions always scans (used by explicit refresh); filter path is latched.
        $filtered  = PPCart_Secrets::decrypt_alloptions(
            [
                '_ppcart_activecampaign_secret_key' => $encrypted,
                '_ppcart_currency'                  => 'USD',
            ]
        );

        $this->assertSame('ac-secret-key-value', $filtered['_ppcart_activecampaign_secret_key']);
        $this->assertSame('USD', $filtered['_ppcart_currency']);
    }

    public function test_alloptions_filter_skips_scan_when_encryption_is_disabled(): void
    {
        $this->requireOpenSsl();
        $this->requireDatabaseEncryptionControl();
        $this->setDbEncryptionEnabled(false);

        $encrypted = PPCart_Secrets::encrypt_value('ac-secret-key-value');
        $input     = [
            '_ppcart_activecampaign_secret_key' => $encrypted,
            '_ppcart_currency'                  => 'USD',
        ];

        $filtered = PPCart_Secrets::filter_alloptions_decrypt($input);

        // Encryption off: no decrypt scan; values returned as-is.
        $this->assertSame($encrypted, $filtered['_ppcart_activecampaign_secret_key']);
        $this->assertSame('USD', $filtered['_ppcart_currency']);
    }

    public function test_alloptions_filter_decrypts_when_encryption_is_enabled(): void
    {
        $this->requireOpenSsl();
        $this->requireDatabaseEncryptionControl();
        $this->setDbEncryptionEnabled(true);

        $encrypted = PPCart_Secrets::encrypt_value('ac-secret-key-value');
        $filtered  = PPCart_Secrets::filter_alloptions_decrypt(
            [
                '_ppcart_activecampaign_secret_key' => $encrypted,
                '_ppcart_currency'                  => 'USD',
            ]
        );

        $this->assertSame('ac-secret-key-value', $filtered['_ppcart_activecampaign_secret_key']);
        $this->assertSame('USD', $filtered['_ppcart_currency']);
    }

    public function test_is_sensitive_option_short_circuits_non_ppcart_prefix_before_regex(): void
    {
        $this->assertFalse(PPCart_Secrets::is_sensitive_option('unrelated_option'));
        $this->assertFalse(PPCart_Secrets::is_sensitive_option('foo_secret'));
        $this->assertTrue(PPCart_Secrets::is_sensitive_option('_ppcart_custom_api_key'));
        $this->assertFalse(PPCart_Secrets::is_sensitive_option('_sc_custom_api_key'));
    }

    public function test_UT_159_get_decrypted_option_falls_back_to_decrypt_a_value_that_is_still_encrypted(): void
    {
        $this->requireOpenSsl();

        $plain     = 'ac-secret-key-value';
        $encrypted = PPCart_Secrets::encrypt_value($plain);
        $this->options['_ppcart_activecampaign_secret_key'] = $encrypted;

        $this->assertSame($plain, PPCart_Secrets::get_decrypted_option('_ppcart_activecampaign_secret_key'));
    }

    public function test_UT_160_get_decrypted_option_lazily_migrates_plaintext_to_ciphertext_when_encryption_is_on(): void
    {
        $this->requireOpenSsl();
        $this->requireDatabaseEncryptionControl();
        $this->setDbEncryptionEnabled(true);

        $plain = 'sk_test_lazy_migration_on_read';
        $this->rawOptions['_ppcart_stripe_test_sk'] = $plain;
        $this->options['_ppcart_stripe_test_sk']    = $plain;

        $this->assertSame($plain, PPCart_Secrets::get_decrypted_option('_ppcart_stripe_test_sk'));
        $this->assertTrue(PPCart_Secrets::is_encrypted_value($this->rawOptions['_ppcart_stripe_test_sk']));
    }

    public function test_UT_161_get_decrypted_option_leaves_plaintext_untouched_when_encryption_is_off(): void
    {
        $this->requireOpenSsl();
        $this->requireDatabaseEncryptionControl();
        $this->setDbEncryptionEnabled(false);

        $plain = 'sk_test_stays_plain_on_read';
        $this->rawOptions['_ppcart_stripe_test_sk'] = $plain;
        $this->options['_ppcart_stripe_test_sk']    = $plain;

        $this->assertSame($plain, PPCart_Secrets::get_decrypted_option('_ppcart_stripe_test_sk'));
        $this->assertFalse(PPCart_Secrets::is_encrypted_value($this->rawOptions['_ppcart_stripe_test_sk']));
    }

    public function test_UT_162_secret_redaction_strips_credential_patterns_from_free_text(): void
    {
        $redacted = ppcart_redact_secrets_from_text('Stripe key sk_test_abc123xyz in log line');

        $this->assertStringContainsString('sk_[redacted]', $redacted);
        $this->assertStringNotContainsString('sk_test_abc123xyz', $redacted);
    }

    /**
     * Skips when wp-config defines PPCART_ENCRYPT_SECRETS (production-only control).
     *
     * @return void
     */
    private function requireDatabaseEncryptionControl(): void
    {
        if (defined('PPCART_ENCRYPT_SECRETS')) {
            $this->markTestSkipped('PPCART_ENCRYPT_SECRETS is defined in this PHP process.');
        }
    }

    /**
     * @return void
     */
    private function requireOpenSsl(): void
    {
        if (! function_exists('openssl_encrypt')) {
            $this->markTestSkipped('OpenSSL extension unavailable.');
        }
    }

    /**
     * @param bool $enabled Whether the database encryption flag is on.
     * @return void
     */
    private function setDbEncryptionEnabled($enabled): void
    {
        if ($enabled) {
            $this->options[ PPCart_Secrets::ENCRYPT_SECRETS_OPTION ] = '1';
            return;
        }

        unset($this->options[ PPCart_Secrets::ENCRYPT_SECRETS_OPTION ]);
    }

    private function mockWpdb(): void
    {
        global $wpdb;

        $rawOptions = &$this->rawOptions;

        $wpdb = new class($rawOptions) {
            /**
             * @var string
             */
            public $options = 'wp_options';

            /**
             * @var array<string, string>
             */
            private $values;

            /**
             * @param array<string, string> $values
             */
            public function __construct(array &$values)
            {
                $this->values = &$values;
            }

            public function prepare($query, ...$args)
            {
                if (isset($args[0])) {
                    return sprintf($query, "'" . $args[0] . "'");
                }

                return $query;
            }

            public function get_var($query)
            {
                foreach ($this->values as $name => $value) {
                    if (false !== strpos($query, "'" . $name . "'")) {
                        return $value;
                    }
                }

                return null;
            }
        };
    }
}
