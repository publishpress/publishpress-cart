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

    if (! function_exists('wp_send_json')) {
        /**
         * @param mixed $response Response data.
         * @return void
         */
        function wp_send_json($response)
        {
            \Tests\Support\WordPressStubContext::invoke('wp_send_json', func_get_args());
        }
    }

    if (! function_exists('is_email')) {
        /**
         * @param string $email Email.
         * @return string|false
         */
        function is_email($email)
        {
            return false === filter_var((string) $email, FILTER_VALIDATE_EMAIL) ? false : $email;
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

class ProfilePasswordValidationTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var array<int, array<int, mixed>>
     */
    private $setPasswordCalls = [];

    protected function _before(): void
    {
        WordPressStubContext::clear();
        $this->setPasswordCalls = [];
        $_POST                  = [];

        WordPressStubContext::set('add_action', static function () {
            return true;
        });
        WordPressStubContext::set('is_user_logged_in', static function () {
            return true;
        });
        WordPressStubContext::set('wp_verify_nonce', static function ($nonce, $action) {
            return ('good-profile-nonce' === $nonce && 'ppcart_ajax_nonce' === $action) ? 1 : false;
        });
        WordPressStubContext::set('wp_get_current_user', static function () {
            return (object) [ 'ID' => 42 ];
        });
        WordPressStubContext::set('current_user_can', static function () {
            return true;
        });
        WordPressStubContext::set('wp_send_json_error', static function ($data = null) {
            throw new \RuntimeException('json_error:' . (is_scalar($data) ? (string) $data : ''));
        });
        WordPressStubContext::set('wp_send_json', static function ($response) {
            throw new \RuntimeException('json:' . json_encode($response));
        });
        WordPressStubContext::set('update_user_meta', static function () {
            return true;
        });
        WordPressStubContext::set('wp_update_user', static function () {
            return 42;
        });
        WordPressStubContext::set('wp_set_password', function () {
            $this->setPasswordCalls[] = func_get_args();
        });

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
     * @test-id UT-361
     */
    public function test_UT_361_numeric_strings_that_loosely_match_are_rejected(): void
    {
        $response = $this->submitProfile([ 'password' => '1e3', 'new_password' => '1000' ]);

        $this->assertArrayHasKey('error', $response);
        $this->assertSame([], $this->setPasswordCalls);
    }

    /**
     * @test-id UT-361
     */
    public function test_UT_361_array_password_is_ignored(): void
    {
        $response = $this->submitProfile([ 'password' => [ 'a' ], 'new_password' => [ 'a' ] ]);

        $this->assertTrue($response['success']);
        $this->assertSame([], $this->setPasswordCalls);
    }

    /**
     * @test-id UT-361
     */
    public function test_UT_361_matching_password_is_saved_unchanged(): void
    {
        $password = ' Pa<ss> %41 word ';

        $response = $this->submitProfile([ 'password' => $password, 'new_password' => $password ]);

        $this->assertTrue($response['success']);
        $this->assertSame([ [ $password, 42 ] ], $this->setPasswordCalls);
    }

    /**
     * Posts the profile form and returns the JSON response the handler sent.
     *
     * @param array<string, mixed> $fields Extra form fields.
     * @return array<string, mixed>
     */
    private function submitProfile(array $fields): array
    {
        $form = array_merge(
            [
                'first_name'      => 'Ada',
                'last_name'       => 'Lovelace',
                'email'           => 'ada@example.com',
                '_ppcart_address2' => 'Suite 1',
            ],
            $fields
        );

        $_POST['nonce']     = 'good-profile-nonce';
        $_POST['form_data'] = addslashes(http_build_query($form));

        try {
            ppcart_update_user_profile();
        } catch (\RuntimeException $exception) {
            $message = $exception->getMessage();
            $this->assertStringStartsWith('json:', $message);

            return json_decode(substr($message, 5), true);
        }

        $this->fail('Expected a JSON response.');
    }
}

}
