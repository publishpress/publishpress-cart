<?php

namespace unit\Bootstrap;

use Codeception\Test\Unit;
use PPCart_Companion_Loader;
use Tests\Support\WordPressStubContext;
use UnitTester;

class CompanionLoaderTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var string
     */
    private $pluginDir;

    protected function _before(): void
    {
        WordPressStubContext::clear();

        if (! defined('WP_PLUGIN_DIR')) {
            define('WP_PLUGIN_DIR', '/tmp/wp-content/plugins');
        }

        $this->pluginDir = WP_PLUGIN_DIR;

        WordPressStubContext::set(
            'get_option',
            static function ($option, $default = false) {
                $options = WordPressStubContext::getState('options', []);

                return array_key_exists($option, $options) ? $options[$option] : $default;
            }
        );
        WordPressStubContext::set(
            'get_site_option',
            static function ($option, $default = false) {
                $options = WordPressStubContext::getState('site_options', []);

                return array_key_exists($option, $options) ? $options[$option] : $default;
            }
        );

        if (! class_exists('PPCart_Companion_Loader', false)) {
            require_once PPCART_PLUGIN_ROOT . 'includes/class-ppcart-companion-loader.php';
        }
    }

    protected function _after(): void
    {
        WordPressStubContext::clear();
        parent::_after();
    }

    /**
     * @test-id UT-249
     */
    public function test_UT_249_resolves_companion_dir_from_active_and_network_plugins(): void
    {
        $expected = $this->pluginDir . '/publishpress-cart-compat/';

        WordPressStubContext::setState(
            'options',
            [
                'active_plugins' => [ 'publishpress-cart/publishpress-cart.php' ],
            ]
        );
        WordPressStubContext::setState('site_options', []);
        $this->assertSame('', PPCart_Companion_Loader::plugin_dir());
        $this->assertSame('', ppcart_companion_plugin_dir());

        WordPressStubContext::setState(
            'options',
            [
                'active_plugins' => [ PPCart_Companion_Loader::PLUGIN_BASENAME ],
            ]
        );
        $this->assertSame($expected, PPCart_Companion_Loader::plugin_dir());
        $this->assertSame($expected, ppcart_companion_plugin_dir());

        WordPressStubContext::setState('options', [ 'active_plugins' => [] ]);
        WordPressStubContext::setState(
            'site_options',
            [
                'active_sitewide_plugins' => [
                    PPCart_Companion_Loader::PLUGIN_BASENAME => 1,
                ],
            ]
        );
        $this->assertSame($expected, PPCart_Companion_Loader::plugin_dir());
        $this->assertSame($expected, ppcart_companion_plugin_dir());
    }
}
