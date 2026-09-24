<?php

namespace unit\Checkout {

use Codeception\Test\Unit;
use PPCart_Order;
use PPCart_Secrets;
use Tests\Support\WordPressStubContext;
use UnitTester;

class CheckoutCompletionAuthTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var array<int, array<string, mixed>>
     */
    private $meta = [];

    /**
     * @var int
     */
    private $currentUserId = 0;

    /**
     * @var bool
     */
    private $isAdmin = false;

    /**
     * @var array<string, mixed>
     */
    private $options = [];

    protected function _before(): void
    {
        WordPressStubContext::clear();
        $this->meta          = [];
        $this->currentUserId = 0;
        $this->isAdmin       = false;
        $this->options       = [];
        $_GET                = [];
        $_POST               = [];

        WordPressStubContext::set(
            'get_current_user_id',
            function () {
                return $this->currentUserId;
            }
        );
        WordPressStubContext::set(
            'current_user_can',
            function ($capability) {
                return $this->isAdmin && 'manage_options' === $capability;
            }
        );
        WordPressStubContext::set(
            'get_post_meta',
            function ($post_id, $key = '', $single = false) {
                $value = $this->meta[ (int) $post_id ][ (string) $key ] ?? '';
                if (! $single) {
                    return '' === $value ? [] : [ $value ];
                }

                return $value;
            }
        );
        WordPressStubContext::set(
            'get_option',
            function ($name, $default = false) {
                return array_key_exists((string) $name, $this->options) ? $this->options[ (string) $name ] : $default;
            }
        );
        WordPressStubContext::set(
            'apply_filters',
            function ($hook, $value) {
                return $value;
            }
        );
        WordPressStubContext::set(
            'wp_verify_nonce',
            function ($nonce, $action) {
                return ('good-nonce' === $nonce && 'ppcart_purchase_nonce' === $action) ? 1 : false;
            }
        );

        $this->ensureSecretsHelpersLoaded();
        $this->ensureCheckoutCompletionLoaded();
    }

    protected function _after(): void
    {
        $_GET  = [];
        $_POST = [];
        WordPressStubContext::clear();
        parent::_after();
    }

    private function ensureSecretsHelpersLoaded(): void
    {
        if (function_exists('ppcart_get_sensitive_option')) {
            return;
        }

        if (! defined('AUTH_KEY')) {
            define('AUTH_KEY', 'unit-test-auth-key-for-checkout-completion');
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

    private function ensureCheckoutCompletionLoaded(): void
    {
        require_once dirname(__DIR__, 2) . '/Support/checkout-completion-unit-bootstrap.php';
        ppcart_unit_bootstrap_checkout_completion();
    }


    private function seedToken(int $orderId, string $token): void
    {
        $this->meta[ $orderId ][ PPCart_Order::INVOICE_TOKEN_META_KEY ] = $token;
    }

    public function test_UT_324_post_requires_nonce_and_access(): void
    {
        $this->seedToken(5, 'order-token');

        $this->assertFalse(
            ppcart_checkout_completion_allowed(5, 'post', [ 'nonce' => 'good-nonce' ])
        );
        $this->assertTrue(
            ppcart_checkout_completion_allowed(
                5,
                'post',
                [
                    'nonce'  => 'good-nonce',
                    'access' => 'order-token',
                ]
            )
        );
    }

    public function test_UT_324_step_requires_access_only(): void
    {
        $this->seedToken(6, 'step-token');

        $this->assertFalse(ppcart_checkout_completion_allowed(6, 'step', []));
        $this->assertTrue(
            ppcart_checkout_completion_allowed(6, 'step', [ 'access' => 'step-token' ])
        );
    }

    public function test_UT_324_paypal_without_pdt_requires_access(): void
    {
        $this->seedToken(7, 'paypal-token');

        $this->assertTrue(
            ppcart_checkout_completion_allowed(7, 'paypal', [ 'access' => 'paypal-token' ])
        );
        $this->assertFalse(
            ppcart_checkout_completion_allowed(7, 'paypal', [ 'pdt_verified' => false ])
        );
    }

    public function test_UT_324_paypal_with_pdt_requires_verification(): void
    {
        $this->seedToken(8, 'paypal-token');
        $this->options['_ppcart_paypal_enable_sandbox']      = 'enable';
        $this->options['_ppcart_paypal_sandbox_pdt_token'] = 'pdt-token';

        $this->assertFalse(
            ppcart_checkout_completion_allowed(
                8,
                'paypal',
                [
                    'access'       => 'paypal-token',
                    'pdt_verified' => false,
                ]
            )
        );
        $this->assertTrue(
            ppcart_checkout_completion_allowed(
                8,
                'paypal',
                [
                    'access'       => 'paypal-token',
                    'pdt_verified' => true,
                ]
            )
        );
    }

    public function test_UT_324_admin_bypasses_completion_gate(): void
    {
        $this->isAdmin       = true;
        $this->currentUserId = 1;
        $this->seedToken(9, 'secret');

        $this->assertTrue(ppcart_checkout_completion_allowed(9, 'post', []));
    }
}

}
