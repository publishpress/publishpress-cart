<?php

namespace unit\Bootstrap;

use Codeception\Test\Unit;
use PPCart_Studiocart_Conflict;
use Tests\Support\WordPressStubContext;
use UnitTester;

class StudiocartConflictTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    protected function _before(): void
    {
        WordPressStubContext::clear();
        WordPressStubContext::set(
            'get_option',
            static function ($option, $default = false) {
                $options = WordPressStubContext::getState('options', []);

                return array_key_exists($option, $options) ? $options[$option] : $default;
            }
        );
        WordPressStubContext::set(
            'update_option',
            static function ($option, $value) {
                $options = WordPressStubContext::getState('options', []);
                $options[$option] = $value;
                WordPressStubContext::setState('options', $options);

                return true;
            }
        );
        WordPressStubContext::set(
            'delete_option',
            static function ($option) {
                $options = WordPressStubContext::getState('options', []);
                unset($options[$option]);
                WordPressStubContext::setState('options', $options);

                return true;
            }
        );
        WordPressStubContext::set(
            'wp_die',
            static function () {
                WordPressStubContext::setState('wp_die_called', true);
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

        if (! class_exists('PPCart_Studiocart_Conflict', false)) {
            require_once PPCART_PLUGIN_ROOT . 'includes/class-ppcart-studiocart-conflict.php';
        }
    }

    protected function _after(): void
    {
        WordPressStubContext::clear();
        parent::_after();
    }

    /**
     * @test-id UT-239
     */
    public function test_UT_239_detects_studiocart_and_refuses_to_treat_cart_as_studiocart(): void
    {
        $this->assertTrue(
            PPCart_Studiocart_Conflict::is_studiocart_basename('Studiocart-main/studiocart.php')
        );
        $this->assertFalse(
            PPCart_Studiocart_Conflict::is_studiocart_basename('publishpress-cart/publishpress-cart.php')
        );

        WordPressStubContext::setState(
            'options',
            [
                'active_plugins' => [ 'publishpress-cart/publishpress-cart.php' ],
            ]
        );
        $this->assertFalse(PPCart_Studiocart_Conflict::is_active());

        WordPressStubContext::setState(
            'options',
            [
                'active_plugins' => [ 'Studiocart-main/studiocart.php' ],
            ]
        );
        $this->assertTrue(PPCart_Studiocart_Conflict::is_active());
    }

    /**
     * @test-id UT-250
     */
    public function test_UT_250_conflicted_activation_does_not_die_and_marks_pending(): void
    {
        WordPressStubContext::setState(
            'options',
            [
                'active_plugins' => [ 'Studiocart-main/studiocart.php' ],
            ]
        );
        WordPressStubContext::setState('wp_die_called', false);

        PPCart_Studiocart_Conflict::on_activate('/tmp/publishpress-cart.php');

        $this->assertFalse(WordPressStubContext::getState('wp_die_called', false));
        $options = WordPressStubContext::getState('options', []);
        $this->assertSame('1', $options[PPCart_Studiocart_Conflict::PENDING_ACTIVATION_OPTION]);
    }

    /**
     * @test-id UT-250
     */
    public function test_UT_250_conflict_notice_is_admin_only(): void
    {
        WordPressStubContext::setState('caps', []);
        WordPressStubContext::setState('screen', (object) [ 'id' => 'plugins' ]);
        ob_start();
        PPCart_Studiocart_Conflict::render_notice();
        $this->assertSame('', ob_get_clean());

        WordPressStubContext::setState('caps', [ 'activate_plugins' ]);
        ob_start();
        PPCart_Studiocart_Conflict::render_notice();
        $output = ob_get_clean();
        $this->assertStringContainsString('notice notice-error', $output);
        $this->assertStringContainsString('cannot run while Studiocart is active', $output);
        $this->assertStringContainsString('Deactivate Studiocart to start those plugins', $output);
    }

    /**
     * @test-id UT-323
     */
    public function test_UT_323_conflict_notice_is_limited_to_plugins_screens(): void
    {
        WordPressStubContext::setState('caps', [ 'activate_plugins' ]);
        WordPressStubContext::setState('screen', (object) [ 'id' => 'dashboard' ]);

        ob_start();
        PPCart_Studiocart_Conflict::render_notice();
        $this->assertSame('', ob_get_clean());

        $this->assertFalse(PPCart_Studiocart_Conflict::is_plugins_list_screen());

        WordPressStubContext::setState('screen', (object) [ 'id' => 'plugins' ]);
        $this->assertTrue(PPCart_Studiocart_Conflict::is_plugins_list_screen());

        ob_start();
        PPCart_Studiocart_Conflict::render_notice();
        $this->assertStringContainsString('notice notice-error', ob_get_clean());

        WordPressStubContext::setState('screen', (object) [ 'id' => 'plugins-network' ]);
        $this->assertTrue(PPCart_Studiocart_Conflict::is_plugins_list_screen());
    }

    /**
     * @test-id UT-250
     */
    public function test_UT_250_pending_activation_is_consumed_once(): void
    {
        $this->assertFalse(PPCart_Studiocart_Conflict::consume_pending_activation());

        WordPressStubContext::setState(
            'options',
            [
                PPCart_Studiocart_Conflict::PENDING_ACTIVATION_OPTION => '1',
            ]
        );
        $this->assertTrue(PPCart_Studiocart_Conflict::consume_pending_activation());
        $this->assertFalse(PPCart_Studiocart_Conflict::consume_pending_activation());
    }
}
