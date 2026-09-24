<?php

namespace {
    if (! function_exists('add_query_arg')) {
        /**
         * @param array  $args Query args.
         * @param string $url  Base URL.
         * @return string
         */
        function add_query_arg($args, $url)
        {
            $separator = (false === strpos($url, '?')) ? '?' : '&';

            return $url . $separator . http_build_query($args);
        }
    }

    if (! function_exists('wp_safe_redirect')) {
        /**
         * @param string $location Redirect target.
         * @param int    $status   HTTP status.
         * @return void
         */
        function wp_safe_redirect($location, $status = 302)
        {
            throw new \RuntimeException('redirect:' . $location);
        }
    }

    if (! function_exists('retrieve_password')) {
        /**
         * @return true|\Tests\Support\WPError
         */
        function retrieve_password()
        {
            return \Tests\Support\WordPressStubContext::invoke('retrieve_password', func_get_args());
        }
    }

    if (! function_exists('wp_lostpassword_url')) {
        /**
         * @param string $redirect Redirect URL.
         * @return string
         */
        function wp_lostpassword_url($redirect = '')
        {
            return 'https://example.com/wp-login.php?action=lostpassword';
        }
    }

    if (! function_exists('wp_nonce_field')) {
        /**
         * @param string $action     Nonce action.
         * @param string $name         Field name.
         * @param bool   $referer      Whether to add referer field.
         * @param bool   $display      Echo vs return.
         * @return string
         */
        function wp_nonce_field($action, $name, $referer = true, $display = true)
        {
            $field = '<input type="hidden" id="' . $name . '" name="' . $name . '" value="unit-nonce" />';
            if ($display) {
                echo $field;

                return '';
            }

            return $field;
        }
    }

    if (! function_exists('ppcart_testid')) {
        /**
         * @param string $value Test id value.
         * @return string
         */
        function ppcart_testid($value)
        {
            return $value;
        }
    }

    if (! function_exists('esc_attr__')) {
        /**
         * @param string $text   Text.
         * @param string $domain Domain.
         * @return string
         */
        function esc_attr__($text, $domain = 'default')
        {
            return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
        }
    }

    if (! function_exists('esc_attr')) {
        /**
         * @param string $text Text.
         * @return string
         */
        function esc_attr($text)
        {
            return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
        }
    }

    if (! function_exists('esc_html_e')) {
        /**
         * @param string $text   Text.
         * @param string $domain Domain.
         * @return void
         */
        function esc_html_e($text, $domain = 'default')
        {
            echo htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
        }
    }
}

namespace unit\Account {

use Codeception\Test\Unit;
use ReflectionProperty;
use Tests\Support\WordPressStubContext;
use UnitTester;

class LostPasswordNonceTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var int
     */
    private $retrievePasswordCalls = 0;

    protected function _before(): void
    {
        WordPressStubContext::clear();
        $this->retrievePasswordCalls = 0;
        $_GET                        = [];
        $_POST                       = [];
        $_SERVER                     = [];

        WordPressStubContext::set(
            'add_action',
            static function () {
                return true;
            }
        );
        WordPressStubContext::set(
            'add_filter',
            static function () {
                return true;
            }
        );
        WordPressStubContext::set(
            'add_shortcode',
            static function () {
                return true;
            }
        );
        WordPressStubContext::set(
            'apply_filters',
            static function ($hook, $value) {
                if ('ppcart_use_default_authentication_logic' === $hook) {
                    return false;
                }

                return $value;
            }
        );
        WordPressStubContext::set(
            'wp_verify_nonce',
            static function ($nonce, $action) {
                return ('good-lost-password-nonce' === $nonce && 'ppcart_lost_password' === $action) ? 1 : false;
            }
        );
        WordPressStubContext::set(
            'retrieve_password',
            function () {
                ++$this->retrievePasswordCalls;

                return true;
            }
        );

        if (! function_exists('ppcart_verify_nonce')) {
            require_once PPCART_PLUGIN_ROOT . 'includes/functions/ajax-security.php';
        }

        if (! class_exists(\PPCart_Public_Account_Controller::class, false)) {
            require_once PPCART_PLUGIN_ROOT . 'public/controllers/class-ppcart-public-account-controller.php';
        }
    }

    protected function _after(): void
    {
        $_GET    = [];
        $_POST   = [];
        $_SERVER = [];
        WordPressStubContext::clear();
        parent::_after();
    }

    /**
     * @test-id UT-347
     */
    public function test_UT_347_do_password_lost_skips_retrieve_password_without_nonce(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['user_login']       = 'someone@example.com';

        $controller = $this->makeControllerWithAccountUrl('https://example.com/account');

        try {
            $controller->do_password_lost();
            $this->fail('Expected redirect exception.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('errors=lost_password_invalid_nonce', $exception->getMessage());
        }

        $this->assertSame(0, $this->retrievePasswordCalls);
    }

    /**
     * @test-id UT-347
     */
    public function test_UT_347_do_password_lost_calls_retrieve_password_with_valid_nonce(): void
    {
        $_SERVER['REQUEST_METHOD']              = 'POST';
        $_POST['user_login']                    = 'someone@example.com';
        $_POST['ppcart_lost_password_nonce']    = 'good-lost-password-nonce';

        $controller = $this->makeControllerWithAccountUrl('https://example.com/account');

        try {
            $controller->do_password_lost();
            $this->fail('Expected redirect exception.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('checkemail=confirm', $exception->getMessage());
        }

        $this->assertSame(1, $this->retrievePasswordCalls);
    }

    /**
     * @test-id UT-347
     */
    public function test_UT_347_lost_password_form_includes_nonce_field(): void
    {
        $attr = [
            'action'             => 'lostpassword',
            'lost_password_sent' => false,
            'errors'             => [],
            'login_url'          => 'https://example.com/account',
        ];

        ob_start();
        include PPCART_PLUGIN_ROOT . 'public/templates/my-account/forms/login-form.php';
        $html = (string) ob_get_clean();

        $this->assertStringContainsString('name="ppcart_lost_password_nonce"', $html);
    }

    /**
     * @param string $accountUrl Account page URL.
     * @return \PPCart_Public_Account_Controller
     */
    private function makeControllerWithAccountUrl($accountUrl)
    {
        $controller = new \PPCart_Public_Account_Controller();
        $property   = new ReflectionProperty($controller, 'my_account_url');
        $property->setAccessible(true);
        $property->setValue($controller, $accountUrl);

        return $controller;
    }
}

}
