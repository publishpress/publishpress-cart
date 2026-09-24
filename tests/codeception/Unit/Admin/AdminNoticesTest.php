<?php

namespace unit\Admin;

use Codeception\Test\Unit;
use PPCart_Admin_Screens;
use Tests\Support\WordPressStubContext;
use UnitTester;

require_once PPCART_PLUGIN_ROOT . 'includes/class-ppcart-admin-screens.php';
require_once PPCART_PLUGIN_ROOT . 'admin/traits/trait-ppcart-admin-notices.php';

/**
 * Harness that exposes private notice-screen helpers for unit coverage.
 */
final class AdminNoticesHarness
{
    use \PPCart_Admin_Notices_Trait;

    /**
     * @var string
     */
    public $plugin_title = 'Cart';

    /**
     * @param string $filter
     * @return bool
     */
    public function expose_should_render_admin_notices($filter = '')
    {
        return $this->should_render_admin_notices($filter);
    }

    /**
     * @param string $key
     * @return string
     */
    public function expose_dismiss_meta_key($key)
    {
        return $this->get_admin_notice_dismiss_meta_key($key);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function expose_is_admin_notice_dismissed($key)
    {
        return $this->is_admin_notice_dismissed($key);
    }
}

class AdminNoticesTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var AdminNoticesHarness
     */
    private $harness;

    protected function _before(): void
    {
        WordPressStubContext::clear();
        WordPressStubContext::setState('screen', null);
        WordPressStubContext::setState('admin_page_parent', '');
        WordPressStubContext::setState('current_post_type', '');
        WordPressStubContext::setState('user_meta', []);
        WordPressStubContext::setState('current_user_id', 7);

        WordPressStubContext::set(
            'get_current_screen',
            static function () {
                return WordPressStubContext::getState('screen');
            }
        );
        WordPressStubContext::set(
            'get_admin_page_parent',
            static function () {
                return WordPressStubContext::getState('admin_page_parent', '');
            }
        );
        WordPressStubContext::set(
            'get_post_type',
            static function () {
                return WordPressStubContext::getState('current_post_type', '');
            }
        );
        WordPressStubContext::set(
            'get_current_user_id',
            static function () {
                return (int) WordPressStubContext::getState('current_user_id', 0);
            }
        );
        WordPressStubContext::set(
            'get_user_meta',
            static function ($user_id, $key = '', $single = false) {
                $meta = WordPressStubContext::getState('user_meta', []);
                $value = $meta[ (int) $user_id ][ (string) $key ] ?? '';

                return $single ? $value : [ $value ];
            }
        );

        $this->harness = new AdminNoticesHarness();
        PPCart_Admin_Screens::reset_cache();
    }

    protected function _after(): void
    {
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
        WordPressStubContext::clear();
        parent::_after();
    }

    /**
     * @param array<string, mixed> $get
     * @param object|null          $screen
     * @return void
     */
    private function resetAdminScreenContext(array $get = [], $screen = null): void
    {
        $_GET = $get;
        $_REQUEST = $get;
        WordPressStubContext::setState('screen', $screen);
        PPCart_Admin_Screens::reset_cache();
    }

    /**
     * @test-id UT-323
     */
    public function test_UT_323_global_admin_notices_skip_unrelated_screens(): void
    {
        $this->resetAdminScreenContext();

        $this->assertFalse($this->harness->expose_should_render_admin_notices('admin_notices'));
        $this->assertTrue($this->harness->expose_should_render_admin_notices('ppcart_settings_admin_notices'));
        $this->assertTrue($this->harness->expose_should_render_admin_notices('ppcart_customer_report_admin_notices'));

        $this->resetAdminScreenContext([ 'page' => 'ppcart-settings' ]);
        $this->assertTrue($this->harness->expose_should_render_admin_notices('admin_notices'));
    }

    /**
     * @test-id UT-323
     */
    public function test_UT_323_non_blocking_notice_dismissal_is_persisted_per_user(): void
    {
        $this->assertSame(
            '_ppcart_dismiss_admin_notice_stripe_connect_success',
            $this->harness->expose_dismiss_meta_key('stripe_connect_success')
        );
        $this->assertFalse($this->harness->expose_is_admin_notice_dismissed('stripe_connect_success'));

        WordPressStubContext::setState(
            'user_meta',
            [
                7 => [
                    '_ppcart_dismiss_admin_notice_stripe_connect_success' => '1',
                ],
            ]
        );

        $this->assertTrue($this->harness->expose_is_admin_notice_dismissed('stripe_connect_success'));
    }
}
