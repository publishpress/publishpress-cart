<?php

namespace unit\Mailchimp;

use Codeception\Test\Unit;
use PPCart_Secrets;
use Tests\Support\WordPressStubContext;
use UnitTester;

class MailchimpApiConfigTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var string
     */
    private $mailchimpKey = '';

    protected function _before(): void
    {
        WordPressStubContext::clear();
        $this->mailchimpKey = '';

        WordPressStubContext::set('apply_filters', static function ($hook, $value) {
            return $value;
        });

        $this->ensureSecretsHelpersLoaded();

        WordPressStubContext::set(
            'get_option',
            function ($option_name, $default = false) {
                return '_ppcart_mailchimp_api' === $option_name ? $this->mailchimpKey : $default;
            }
        );

        if (! function_exists('ppcart_has_mailchimp_api')) {
            require_once PPCART_PLUGIN_ROOT . 'includes/functions/mailchimp-api.php';
        }
    }

    protected function _after(): void
    {
        WordPressStubContext::clear();
        parent::_after();
    }

    private function ensureSecretsHelpersLoaded(): void
    {
        if (function_exists('ppcart_get_sensitive_option')) {
            return;
        }

        if (! defined('AUTH_KEY')) {
            define('AUTH_KEY', 'unit-test-auth-key-for-mailchimp-api');
        }

        require_once PPCART_PLUGIN_ROOT . 'includes/logging/class-ppcart-debug-log-viewer.php';
        require_once PPCART_PLUGIN_ROOT . 'includes/class-ppcart-secrets.php';
        require_once PPCART_PLUGIN_ROOT . 'includes/helpers/ppcart-secrets-functions.php';

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
    }

    /**
     * @test-id UT-357
     */
    public function test_UT_357_has_mailchimp_api_is_false_without_a_valid_key(): void
    {
        $this->mailchimpKey = '';
        $this->assertFalse(ppcart_has_mailchimp_api());

        $this->mailchimpKey = 'nodatacenter';
        $this->assertFalse(ppcart_has_mailchimp_api());
    }

    /**
     * @test-id UT-357
     */
    public function test_UT_357_has_mailchimp_api_is_true_for_a_valid_key(): void
    {
        $this->mailchimpKey = 'abc123-us1';
        $this->assertTrue(ppcart_has_mailchimp_api());
    }
}
