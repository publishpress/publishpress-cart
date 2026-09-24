<?php

namespace unit\Bootstrap;

use Codeception\Test\Unit;
use PPCart_Admin_Screens;
use PPCart_Reviews;
use Tests\Support\WordPressStubContext;
use UnitTester;

class ReviewsTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    protected function _before(): void
    {
        WordPressStubContext::clear();
        WordPressStubContext::setState('screen', null);
        WordPressStubContext::setState('caps', ['manage_options']);

        WordPressStubContext::set(
            'is_admin',
            static function () {
                return WordPressStubContext::getState('is_admin', false);
            }
        );
        WordPressStubContext::set(
            'current_user_can',
            static function ($capability) {
                $caps = WordPressStubContext::getState('caps', []);

                return in_array($capability, $caps, true);
            }
        );
        WordPressStubContext::set(
            'get_current_screen',
            static function () {
                return WordPressStubContext::getState('screen');
            }
        );
        WordPressStubContext::setState('actions', []);
        WordPressStubContext::setState('filters', []);
        WordPressStubContext::setState('filter_overrides', []);
        WordPressStubContext::set(
            'add_action',
            static function ($hook_name) {
                $actions = WordPressStubContext::getState('actions', []);
                $actions[] = $hook_name;
                WordPressStubContext::setState('actions', $actions);

                return true;
            }
        );
        WordPressStubContext::set(
            'add_filter',
            static function ($hook_name) {
                $filters = WordPressStubContext::getState('filters', []);
                $filters[] = $hook_name;
                WordPressStubContext::setState('filters', $filters);

                return true;
            }
        );
        WordPressStubContext::set(
            'apply_filters',
            static function ($hook, $value) {
                $overrides = WordPressStubContext::getState('filter_overrides', []);

                return array_key_exists($hook, $overrides) ? $overrides[$hook] : $value;
            }
        );

        if (! class_exists(PPCart_Admin_Screens::class, false)) {
            require_once PPCART_PLUGIN_ROOT . 'includes/class-ppcart-admin-screens.php';
        }
        if (! class_exists(PPCart_Reviews::class, false)) {
            require_once PPCART_PLUGIN_ROOT . 'includes/class-ppcart-reviews.php';
        }

        $this->resetRequest();
    }

    protected function _after(): void
    {
        $this->resetRequest();
        WordPressStubContext::clear();
        parent::_after();
    }

    /**
     * @test-id UT-353
     */
    public function test_UT_353_admin_init_registers_reviews_after_auth(): void
    {
        WordPressStubContext::setState('is_admin', true);

        PPCart_Reviews::init();

        $this->assertSame(['admin_init'], WordPressStubContext::getState('actions', []));
    }

    /**
     * @test-id UT-353
     */
    public function test_UT_353_filter_false_skips_review_registration(): void
    {
        WordPressStubContext::setState('filter_overrides', [
            'ppcart_show_reviews' => false,
        ]);

        PPCart_Reviews::register_reviews();

        $this->assertSame([], WordPressStubContext::getState('filters', []));
    }

    /**
     * @test-id UT-353
     */
    public function test_UT_353_non_admin_never_shows_review_banner(): void
    {
        WordPressStubContext::setState('is_admin', false);
        $this->resetRequest(['page' => 'ppcart']);

        $this->assertFalse(PPCart_Reviews::should_display_banner(true));
    }

    /**
     * @test-id UT-353
     */
    public function test_UT_353_admin_without_manage_options_never_shows_review_banner(): void
    {
        WordPressStubContext::setState('is_admin', true);
        WordPressStubContext::setState('caps', []);
        $this->resetRequest(['page' => 'ppcart']);

        $this->assertFalse(PPCart_Reviews::should_display_banner(true));
    }

    /**
     * @test-id UT-353
     */
    public function test_UT_353_unrelated_admin_page_does_not_show_review_banner(): void
    {
        WordPressStubContext::setState('is_admin', true);
        $this->resetRequest(['page' => 'plugins']);

        $this->assertFalse(PPCart_Reviews::should_display_banner(true));
    }

    /**
     * @test-id UT-353
     */
    public function test_UT_353_dashboard_shows_review_banner(): void
    {
        WordPressStubContext::setState('is_admin', true);
        $this->resetRequest(['page' => 'ppcart']);

        $this->assertTrue(PPCart_Reviews::should_display_banner(true));
    }

    /**
     * @test-id UT-353
     */
    public function test_UT_353_settings_shows_review_banner(): void
    {
        WordPressStubContext::setState('is_admin', true);
        $this->resetRequest(['page' => 'ppcart-settings']);

        $this->assertTrue(PPCart_Reviews::should_display_banner(true));
    }

    /**
     * @param array<string, mixed> $get
     * @return void
     */
    private function resetRequest(array $get = [])
    {
        $_GET     = $get;
        $_POST    = [];
        $_REQUEST = $get;

        PPCart_Admin_Screens::reset_cache();
    }
}
