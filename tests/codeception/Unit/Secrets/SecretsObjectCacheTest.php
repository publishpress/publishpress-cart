<?php

namespace unit\Secrets;

use Codeception\Test\Unit;
use PPCart_Secrets;
use Tests\Support\WordPressStubContext;
use UnitTester;

/**
 * Encrypted secrets read back as plaintext through get_option(), and plaintext
 * never lands in the options object cache (which may be persistent).
 *
 * get_option(), update_option() and wp_load_alloptions() are emulated with the
 * same filter order and cache writes as WordPress core.
 */
class SecretsObjectCacheTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * Stored option values, as in the wp_options table.
     *
     * @var array<string, string>
     */
    private $db = [];

    /**
     * Autoloaded option names.
     *
     * @var array<string, bool>
     */
    private $autoload = [];

    /**
     * Emulated 'options' object cache group.
     *
     * @var array<string, mixed>
     */
    private $cache = [];

    /**
     * Every value written with wp_cache_set(), for leak assertions.
     *
     * @var array<int, mixed>
     */
    private $cacheWrites = [];

    /**
     * @var array<string, array<int, array{0: callable, 1: int, 2: int}>>
     */
    private $hooks = [];

    protected function _before(): void
    {
        if (! function_exists('openssl_encrypt')) {
            $this->markTestSkipped('OpenSSL extension unavailable.');
        }

        if (defined('PPCART_ENCRYPT_SECRETS')) {
            $this->markTestSkipped('PPCART_ENCRYPT_SECRETS is defined in this PHP process.');
        }

        if (! defined('AUTH_KEY')) {
            define('AUTH_KEY', 'unit-test-auth-key-for-secrets');
        }

        require_once PPCART_PLUGIN_ROOT . 'includes/logging/class-ppcart-debug-log-viewer.php';
        require_once PPCART_PLUGIN_ROOT . 'includes/class-ppcart-secrets.php';
        require_once PPCART_PLUGIN_ROOT . 'includes/helpers/ppcart-secrets-functions.php';

        $this->db          = [];
        $this->autoload    = [];
        $this->cache       = [];
        $this->cacheWrites = [];
        $this->hooks       = [];

        $this->defineMissingFunctions();
        $this->registerStubs();
        $this->mockWpdb();
        $this->resetSecretsState();

        PPCart_Secrets::init();
    }

    public function test_pattern_only_secret_reads_decrypted_through_get_option(): void
    {
        $this->enableEncryption();
        $this->store('_ppcart_acme_api_key', PPCart_Secrets::encrypt_value('acme-plain-key'), true);

        $this->assertSame('acme-plain-key', get_option('_ppcart_acme_api_key'));
        $this->assertNoPlaintextWasCached('acme-plain-key');
    }

    public function test_registered_secret_reads_decrypted_whether_autoloaded_or_not(): void
    {
        $this->enableEncryption();
        $this->store('_ppcart_stripe_test_sk', PPCart_Secrets::encrypt_value('sk_test_autoloaded'), true);
        $this->store('_ppcart_paypal_secret', PPCart_Secrets::encrypt_value('paypal-not-autoloaded'), false);

        $this->assertSame('sk_test_autoloaded', get_option('_ppcart_stripe_test_sk'));
        $this->assertSame('paypal-not-autoloaded', get_option('_ppcart_paypal_secret'));
        $this->assertNoPlaintextWasCached('sk_test_autoloaded');
        $this->assertNoPlaintextWasCached('paypal-not-autoloaded');
    }

    public function test_saving_a_secret_stores_ciphertext_and_reads_back_plaintext(): void
    {
        $this->enableEncryption();

        update_option('_ppcart_stripe_live_sk', 'sk_live_saved_value');

        $this->assertTrue(PPCart_Secrets::is_encrypted_value($this->db['_ppcart_stripe_live_sk']));
        $this->assertSame('sk_live_saved_value', get_option('_ppcart_stripe_live_sk'));
        $this->assertNoPlaintextWasCached('sk_live_saved_value');
    }

    public function test_updating_another_option_keeps_ciphertext_in_the_alloptions_cache(): void
    {
        $this->enableEncryption();
        $this->store('_ppcart_stripe_test_sk', PPCart_Secrets::encrypt_value('sk_test_in_cache'), true);
        $this->store('_ppcart_currency', 'USD', true);

        $this->assertSame('sk_test_in_cache', get_option('_ppcart_stripe_test_sk'));

        // Core writes the filtered wp_load_alloptions() array back to the cache here.
        update_option('_ppcart_currency', 'EUR');

        $this->assertTrue(PPCart_Secrets::is_encrypted_value($this->cache['alloptions']['_ppcart_stripe_test_sk']));
        $this->assertSame('EUR', get_option('_ppcart_currency'));
        $this->assertSame('sk_test_in_cache', get_option('_ppcart_stripe_test_sk'));
        $this->assertNoPlaintextWasCached('sk_test_in_cache');
    }

    public function test_refresh_removes_plaintext_left_in_the_cache_by_older_versions(): void
    {
        $this->enableEncryption();
        $this->store('_ppcart_stripe_test_sk', PPCart_Secrets::encrypt_value('sk_test_stale'), true);
        $this->store('_ppcart_paypal_secret', PPCart_Secrets::encrypt_value('paypal-stale'), false);
        wp_load_alloptions();

        // Simulate cache entries written by an older version.
        $this->cache['alloptions']['_ppcart_stripe_test_sk'] = 'sk_test_stale';
        $this->cache['_ppcart_paypal_secret']                = 'paypal-stale';
        $this->cacheWrites                                   = [];

        PPCart_Secrets::refresh_cached_secret_options();

        // Later reads reload alloptions from the database, so it holds ciphertext again.
        $this->assertTrue(PPCart_Secrets::is_encrypted_value(wp_load_alloptions()['_ppcart_stripe_test_sk']));
        $this->assertArrayNotHasKey('_ppcart_paypal_secret', $this->cache);
        $this->assertSame('sk_test_stale', get_option('_ppcart_stripe_test_sk'));
        $this->assertSame('paypal-stale', get_option('_ppcart_paypal_secret'));
        $this->assertNoPlaintextWasCached('sk_test_stale');
        $this->assertNoPlaintextWasCached('paypal-stale');
    }

    public function test_refresh_encrypts_a_plaintext_secret_instead_of_dropping_the_cache(): void
    {
        $this->enableEncryption();
        $this->store('_ppcart_stripe_test_sk', 'sk_test_still_plain', true);
        wp_load_alloptions();

        PPCart_Secrets::refresh_cached_secret_options();

        $this->assertTrue(PPCart_Secrets::is_encrypted_value($this->db['_ppcart_stripe_test_sk']));
        $this->assertArrayHasKey('alloptions', $this->cache);
        $this->assertTrue(PPCart_Secrets::is_encrypted_value($this->cache['alloptions']['_ppcart_stripe_test_sk']));
        $this->assertSame('sk_test_still_plain', get_option('_ppcart_stripe_test_sk'));
    }

    public function test_refresh_leaves_the_cache_alone_when_encryption_is_off(): void
    {
        $this->store('_ppcart_stripe_test_sk', 'sk_test_plain_off', true);
        wp_load_alloptions();

        PPCart_Secrets::refresh_cached_secret_options();

        $this->assertSame('sk_test_plain_off', $this->cache['alloptions']['_ppcart_stripe_test_sk']);
        $this->assertSame('sk_test_plain_off', $this->db['_ppcart_stripe_test_sk']);
        $this->assertSame('sk_test_plain_off', get_option('_ppcart_stripe_test_sk'));
    }

    public function test_encrypted_secret_still_reads_after_encryption_is_turned_off(): void
    {
        $this->store('_ppcart_acme_api_key', PPCart_Secrets::encrypt_value('acme-after-off'), true);

        $this->assertSame('acme-after-off', get_option('_ppcart_acme_api_key'));
    }

    private function enableEncryption(): void
    {
        $this->store(PPCart_Secrets::ENCRYPT_SECRETS_OPTION, '1', true);
    }

    private function store(string $name, string $value, bool $autoload): void
    {
        $this->db[ $name ] = $value;

        if ($autoload) {
            $this->autoload[ $name ] = true;
        }

        unset($this->cache['alloptions'], $this->cache[ $name ]);
    }

    private function assertNoPlaintextWasCached(string $plaintext): void
    {
        $haystack = serialize($this->cacheWrites) . serialize($this->cache);

        $this->assertStringNotContainsString($plaintext, $haystack, 'Plaintext secret was written to the object cache.');
    }

    /**
     * @param mixed $value
     * @param mixed ...$args
     * @return mixed
     */
    private function runFilters(string $hook, $value, ...$args)
    {
        if (empty($this->hooks[ $hook ])) {
            return $value;
        }

        $callbacks = $this->hooks[ $hook ];
        usort(
            $callbacks,
            static function ($a, $b) {
                return $a[1] <=> $b[1];
            }
        );

        foreach ($callbacks as $callback) {
            $value = call_user_func_array($callback[0], array_slice(array_merge([ $value ], $args), 0, $callback[2]));
        }

        return $value;
    }

    /**
     * @return array<string, mixed>
     */
    private function loadAlloptions(): array
    {
        if (! isset($this->cache['alloptions'])) {
            $all = [];
            foreach (array_keys($this->autoload) as $name) {
                if (array_key_exists($name, $this->db)) {
                    $all[ $name ] = $this->db[ $name ];
                }
            }
            $this->cache['alloptions'] = $all;
        }

        return $this->runFilters('alloptions', $this->cache['alloptions']);
    }

    /**
     * @param mixed $default
     * @return mixed
     */
    private function getOption(string $name, $default = false)
    {
        $pre = $this->runFilters('pre_option_' . $name, false, $name, $default);
        $pre = $this->runFilters('pre_option', $pre, $name, $default);
        if (false !== $pre) {
            return $pre;
        }

        $all = $this->loadAlloptions();
        if (isset($all[ $name ])) {
            $value = $all[ $name ];
        } elseif (array_key_exists($name, $this->cache)) {
            $value = $this->cache[ $name ];
        } elseif (array_key_exists($name, $this->db)) {
            $value                 = $this->db[ $name ];
            $this->cache[ $name ] = $value;
        } else {
            return $default;
        }

        return $this->runFilters('option_' . $name, $value, $name);
    }

    /**
     * @param mixed $value
     */
    private function updateOption(string $name, $value): bool
    {
        $old   = $this->getOption($name);
        $value = $this->runFilters('pre_update_option', $value, $name, $old);

        if ($value === $old) {
            return false;
        }

        $this->db[ $name ] = (string) $value;

        if (isset($this->autoload[ $name ])) {
            unset($this->cache['alloptions']);
            $all          = $this->loadAlloptions();
            $all[ $name ] = (string) $value;
            wp_cache_set('alloptions', $all, 'options');
        } else {
            wp_cache_set($name, (string) $value, 'options');
        }

        return true;
    }

    private function registerStubs(): void
    {
        WordPressStubContext::set(
            'add_filter',
            function ($hook, $callback, $priority = 10, $accepted_args = 1) {
                $this->hooks[ $hook ][] = [ $callback, (int) $priority, (int) $accepted_args ];

                return true;
            }
        );
        WordPressStubContext::set('add_action', function () {
            return true;
        });
        WordPressStubContext::set('apply_filters', function ($hook, $value, ...$args) {
            return $this->runFilters($hook, $value, ...$args);
        });
        WordPressStubContext::set('get_option', function ($name, $default = false) {
            return $this->getOption((string) $name, $default);
        });
        WordPressStubContext::set('update_option', function ($name, $value) {
            return $this->updateOption((string) $name, $value);
        });
        WordPressStubContext::set('wp_load_alloptions', function () {
            return $this->loadAlloptions();
        });
        WordPressStubContext::set('wp_cache_get', function ($key, $group = '') {
            return 'options' === $group && array_key_exists($key, $this->cache) ? $this->cache[ $key ] : false;
        });
        WordPressStubContext::set('wp_cache_set', function ($key, $data, $group = '') {
            if ('options' === $group) {
                $this->cache[ $key ] = $data;
                $this->cacheWrites[] = $data;
            }

            return true;
        });
        WordPressStubContext::set('wp_cache_delete', function ($key, $group = '') {
            if ('options' === $group) {
                unset($this->cache[ $key ]);
            }

            return true;
        });
        WordPressStubContext::set('current_user_can', function () {
            return true;
        });
        WordPressStubContext::set('get_current_user_id', function () {
            return 1;
        });
    }

    private function defineMissingFunctions(): void
    {
        $proxies = [
            'wp_cache_get'        => '$key, $group = "", $force = false, &$found = null',
            'wp_cache_set'        => '$key, $data, $group = "", $expire = 0',
            'wp_cache_delete'     => '$key, $group = ""',
            'wp_load_alloptions'  => '$force_cache = false',
            'current_user_can'    => '$cap',
            'get_current_user_id' => '',
        ];

        foreach ($proxies as $function => $signature) {
            if (! function_exists($function)) {
                eval('function ' . $function . '(' . $signature . '){ return \\Tests\\Support\\WordPressStubContext::invoke("' . $function . '", array_slice(func_get_args(), 0, 3)); }');
            }
        }

        if (! function_exists('is_admin')) {
            eval('function is_admin(){ return true; }');
        }

        if (! function_exists('wp_doing_cron')) {
            eval('function wp_doing_cron(){ return false; }');
        }

        if (! function_exists('wp_doing_ajax')) {
            eval('function wp_doing_ajax(){ return false; }');
        }
    }

    private function resetSecretsState(): void
    {
        $reflection = new \ReflectionClass(PPCart_Secrets::class);
        $defaults   = [
            'initialized'                  => false,
            'decrypt_filters_registered'   => [],
            'sensitive_option_names_cache' => null,
            'alloptions_decrypt_done'      => false,
            'pre_option_checked'           => [],
            'pre_option_running'           => false,
        ];

        foreach ($defaults as $name => $value) {
            if ($reflection->hasProperty($name)) {
                $property = $reflection->getProperty($name);
                $property->setAccessible(true);
                $property->setValue(null, $value);
            }
        }
    }

    private function mockWpdb(): void
    {
        global $wpdb;

        $db = &$this->db;

        $wpdb = new class($db) {
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
                return isset($args[0]) ? sprintf($query, "'" . $args[0] . "'") : $query;
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
