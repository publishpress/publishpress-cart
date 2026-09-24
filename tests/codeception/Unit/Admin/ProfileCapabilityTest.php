<?php

namespace {
    if (! function_exists('is_user_logged_in')) {
        /**
         * @return bool
         */
        function is_user_logged_in()
        {
            return \Tests\Support\WordPressStubContext::invoke('is_user_logged_in', func_get_args());
        }
    }

    if (! function_exists('wp_get_current_user')) {
        /**
         * @return object
         */
        function wp_get_current_user()
        {
            return \Tests\Support\WordPressStubContext::invoke('wp_get_current_user', func_get_args());
        }
    }

    if (! function_exists('wp_send_json_error')) {
        /**
         * @param mixed $data   Response data.
         * @param int   $status HTTP status.
         * @return void
         */
        function wp_send_json_error($data = null, $status = null)
        {
            \Tests\Support\WordPressStubContext::invoke('wp_send_json_error', func_get_args());
        }
    }

    if (! function_exists('wp_update_user')) {
        /**
         * @param array<string, mixed> $userdata User data.
         * @return int|\Tests\Support\WPError
         */
        function wp_update_user($userdata)
        {
            return \Tests\Support\WordPressStubContext::invoke('wp_update_user', func_get_args());
        }
    }

    if (! function_exists('wp_set_password')) {
        /**
         * @param string $password Password.
         * @param int    $user_id  User id.
         * @return void
         */
        function wp_set_password($password, $user_id)
        {
            \Tests\Support\WordPressStubContext::invoke('wp_set_password', func_get_args());
        }
    }
}

namespace unit\Admin {

use Codeception\Test\Unit;
use Tests\Support\WordPressStubContext;
use UnitTester;

class ProfileCapabilityTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var int
     */
    private $wpUpdateUserCalls = 0;

    /**
     * @var int
     */
    private $wpSetPasswordCalls = 0;

    protected function _before(): void
    {
        WordPressStubContext::clear();
        $this->wpUpdateUserCalls  = 0;
        $this->wpSetPasswordCalls = 0;
        $_POST                    = [];

        WordPressStubContext::set(
            'add_action',
            static function () {
                return true;
            }
        );
        WordPressStubContext::set(
            'is_user_logged_in',
            static function () {
                return true;
            }
        );
        WordPressStubContext::set(
            'wp_verify_nonce',
            static function ($nonce, $action) {
                return ('good-profile-nonce' === $nonce && 'ppcart_ajax_nonce' === $action) ? 1 : false;
            }
        );
        WordPressStubContext::set(
            'wp_get_current_user',
            static function () {
                return (object) [
                    'ID' => 42,
                ];
            }
        );
        WordPressStubContext::set(
            'current_user_can',
            static function ($capability, $userId = null) {
                return false;
            }
        );
        WordPressStubContext::set(
            'wp_send_json_error',
            static function ($data = null, $status = null) {
                throw new \RuntimeException('json_error:' . (string) $data);
            }
        );
        WordPressStubContext::set(
            'wp_update_user',
            function () {
                ++$this->wpUpdateUserCalls;

                return 42;
            }
        );
        WordPressStubContext::set(
            'wp_set_password',
            function () {
                ++$this->wpSetPasswordCalls;
            }
        );

        if (! function_exists('ppcart_verify_nonce')) {
            require_once PPCART_PLUGIN_ROOT . 'includes/functions/ajax-security.php';
        }

        if (! function_exists('ppcart_update_user_profile')) {
            require_once PPCART_PLUGIN_ROOT . 'includes/functions/admin-ajax-and-notices.php';
        }
    }

    protected function _after(): void
    {
        $_POST = [];
        WordPressStubContext::clear();
        parent::_after();
    }

    /**
     * @test-id UT-348
     */
    public function test_UT_348_profile_ajax_denies_when_edit_user_capability_fails(): void
    {
        $_POST['nonce'] = 'good-profile-nonce';

        try {
            ppcart_update_user_profile();
            $this->fail('Expected JSON error.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('json_error:', $exception->getMessage());
        }

        $this->assertSame(0, $this->wpUpdateUserCalls);
        $this->assertSame(0, $this->wpSetPasswordCalls);
    }
}

}
