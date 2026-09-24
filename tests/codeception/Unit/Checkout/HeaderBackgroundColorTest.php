<?php

namespace unit\Checkout;

use Codeception\Test\Unit;
use Tests\Support\WordPressStubContext;
use UnitTester;

class HeaderBackgroundColorTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    protected function _before(): void
    {
        WordPressStubContext::clear();
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
                return $value;
            }
        );
        WordPressStubContext::set(
            'did_action',
            static function () {
                return 0;
            }
        );

        if (! function_exists('ppcart_enqueue_or_print_inline_style')) {
            require_once PPCART_PLUGIN_ROOT . 'includes/functions.php';
        }
    }

    protected function _after(): void
    {
        WordPressStubContext::clear();
        parent::_after();
    }

    /**
     * @test-id UT-318
     */
    public function test_UT_318_defers_checkout_inline_css_until_enqueue_scripts(): void
    {
        $queued = [];
        $inline = [];
        $css    = '.ppcart-hero-banner{background-color:#abc123}';

        WordPressStubContext::set(
            'did_action',
            static function () {
                return 0;
            }
        );
        WordPressStubContext::set(
            'add_action',
            static function ($hook, $callback, $priority = 10) use (&$queued) {
                $queued[] = [$hook, $callback, $priority];

                return true;
            }
        );
        WordPressStubContext::set(
            'wp_add_inline_style',
            function () {
                $this->fail('Inline CSS must wait until wp_enqueue_scripts has run');
            }
        );

        ppcart_enqueue_or_print_inline_style('ppcart', $css);

        $this->assertContains(['wp_enqueue_scripts', 20], array_map(
            static function ($row) {
                return [$row[0], $row[2]];
            },
            $queued
        ));
        $this->assertContains(['admin_enqueue_scripts', 20], array_map(
            static function ($row) {
                return [$row[0], $row[2]];
            },
            $queued
        ));

        WordPressStubContext::set(
            'did_action',
            static function ($hook) {
                return 'wp_enqueue_scripts' === $hook ? 1 : 0;
            }
        );
        WordPressStubContext::set(
            'wp_style_is',
            static function ($handle, $list = 'enqueued') {
                return 'ppcart' === $handle && in_array($list, ['registered', 'enqueued'], true);
            }
        );
        WordPressStubContext::set(
            'wp_enqueue_style',
            static function () {
                return true;
            }
        );
        WordPressStubContext::set(
            'wp_add_inline_style',
            static function ($handle, $data) use (&$inline) {
                $inline[] = [$handle, $data];

                return true;
            }
        );

        foreach ($queued as $row) {
            if ('wp_enqueue_scripts' === $row[0] && is_callable($row[1])) {
                call_user_func($row[1]);
            }
        }

        $this->assertSame([['ppcart', $css]], $inline);
    }

    /**
     * @test-id UT-318
     */
    public function test_UT_318_attaches_deferred_css_to_registered_handle(): void
    {
        $inline = [];

        WordPressStubContext::set(
            'did_action',
            static function ($hook) {
                return 'wp_enqueue_scripts' === $hook ? 1 : 0;
            }
        );
        WordPressStubContext::set(
            'wp_style_is',
            static function ($handle, $list = 'enqueued') {
                return 'ppcart' === $handle && in_array($list, ['registered', 'enqueued'], true);
            }
        );
        WordPressStubContext::set(
            'wp_enqueue_style',
            static function () {
                return true;
            }
        );
        WordPressStubContext::set(
            'wp_add_inline_style',
            static function ($handle, $css) use (&$inline) {
                $inline[] = [$handle, $css];

                return true;
            }
        );

        $css = '.ppcart-hero-banner{background-color:#abc123}';
        ppcart_enqueue_or_print_inline_style('ppcart', $css);

        $this->assertSame([['ppcart', $css]], $inline);
    }
}
