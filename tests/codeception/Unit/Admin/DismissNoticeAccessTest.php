<?php

namespace {
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

    if (! function_exists('wp_send_json_success')) {
        /**
         * @param mixed $data   Response data.
         * @param int   $status HTTP status.
         * @return void
         */
        function wp_send_json_success($data = null, $status = null)
        {
            \Tests\Support\WordPressStubContext::invoke('wp_send_json_success', func_get_args());
        }
    }
}

namespace unit\Admin {

use Codeception\Test\Unit;
use Tests\Support\WordPressStubContext;
use UnitTester;

/**
 * The dismiss-notice AJAX action needs a valid nonce, a manager capability,
 * and a known notice type before it writes an option.
 */
class DismissNoticeAccessTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var array<string, mixed>
     */
    private $updatedOptions = [];

    /**
     * @var string[]
     */
    private $caps = [];

    protected function _before(): void
    {
        WordPressStubContext::clear();
        $this->updatedOptions = [];
        $this->caps           = [];
        $_POST                = [];

        foreach ([ 'add_action', 'add_filter' ] as $function) {
            WordPressStubContext::set($function, static function () {
                return true;
            });
        }
        WordPressStubContext::set('apply_filters', static function ($hook, $value) {
            return $value;
        });
        WordPressStubContext::set('wp_verify_nonce', static function ($nonce, $action) {
            return ('good-nonce' === $nonce && 'ppcart_ajax_nonce' === $action) ? 1 : false;
        });
        WordPressStubContext::set('current_user_can', function ($capability) {
            return in_array($capability, $this->caps, true);
        });
        WordPressStubContext::set('update_option', function ($name, $value) {
            $this->updatedOptions[ $name ] = $value;

            return true;
        });
        WordPressStubContext::set('wp_send_json_error', static function ($data = null, $status = null) {
            throw new \RuntimeException('json_error:' . (string) $status);
        });
        WordPressStubContext::set('wp_send_json_success', static function () {
            throw new \RuntimeException('json_success');
        });

        if (! function_exists('ppcart_verify_nonce')) {
            require_once PPCART_PLUGIN_ROOT . 'includes/functions/ajax-security.php';
        }

        if (! function_exists('ppcart_ajax_notice_handler')) {
            require_once PPCART_PLUGIN_ROOT . 'includes/functions/admin-ajax-and-notices.php';
        }
    }

    protected function _after(): void
    {
        $_POST = [];
        WordPressStubContext::clear();
        parent::_after();
    }

    public function test_request_without_valid_nonce_writes_nothing(): void
    {
        $this->caps = [ 'manage_options' ];
        $_POST      = [ 'nonce' => 'bad-nonce', 'type' => 'ppcart_price_formatted' ];

        $this->assertSame('json_error:', $this->runHandler());
        $this->assertSame([], $this->updatedOptions);
    }

    public function test_user_without_capability_writes_nothing(): void
    {
        $this->caps = [ 'edit_posts' ];
        $_POST      = [ 'nonce' => 'good-nonce', 'type' => 'ppcart_price_formatted' ];

        $this->assertSame('json_error:403', $this->runHandler());
        $this->assertSame([], $this->updatedOptions);
    }

    public function test_unknown_notice_type_writes_nothing(): void
    {
        $this->caps = [ 'manage_options' ];
        $_POST      = [ 'nonce' => 'good-nonce', 'type' => 'anything_else' ];

        $this->assertSame('json_error:400', $this->runHandler());
        $this->assertSame([], $this->updatedOptions);
    }

    public function test_cart_manager_can_dismiss_a_known_notice(): void
    {
        $this->caps = [ ppcart_live_cap('manager_option') ];
        $_POST      = [ 'nonce' => 'good-nonce', 'type' => 'ppcart_price_formatted' ];

        $this->assertSame('json_success', $this->runHandler());
        $this->assertSame([ 'ppcart_dismissed_ppcart_price_formatted' => true ], $this->updatedOptions);
    }

    private function runHandler(): string
    {
        try {
            ppcart_ajax_notice_handler();
        } catch (\RuntimeException $exception) {
            return $exception->getMessage();
        }

        return 'no-response';
    }
}

}
