<?php

namespace unit\DebugLogViewer;

use Codeception\Test\Unit;
use PPCart_Admin_Settings;
use PPCart_Debug_Logger;
use Tests\Support\WordPressStubContext;
use UnitTester;

class DebugLogAdminAccessTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var string
     */
    private $debugLogDir;

    /**
     * @var array<string, mixed>
     */
    private $originalRequest = array();

    protected function _before(): void
    {
        $this->originalRequest = $_REQUEST;

        if (! defined('PPCART_BASE_DIR')) {
            define('PPCART_BASE_DIR', PPCART_PLUGIN_ROOT);
        }

        if (! defined('PPCART_DEBUG_LOG_DIR')) {
            $this->debugLogDir = sys_get_temp_dir() . '/ppcart-debug-log-access-test-' . uniqid('', true);
            define('PPCART_DEBUG_LOG_DIR', $this->debugLogDir);
        } else {
            $this->debugLogDir = PPCART_DEBUG_LOG_DIR;
        }

        WordPressStubContext::setState(
            'options',
            array(
                '_sc_enable_debug' => 1,
                '_sc_log_file'     => 'unit-access-log.txt',
            )
        );
        WordPressStubContext::setState('nonce_checked', false);
        WordPressStubContext::setState('wp_die_called', false);

        WordPressStubContext::set('add_action', function () {
            return true;
        });
        WordPressStubContext::set('add_filter', function () {
            return true;
        });
        WordPressStubContext::set(
            'get_option',
            function ($key, $default = false) {
                $options = WordPressStubContext::getState('options', array());

                return isset($options[$key]) ? $options[$key] : $default;
            }
        );
        WordPressStubContext::set(
            'update_option',
            function ($key, $value) {
                $options = WordPressStubContext::getState('options', array());
                $options[$key] = $value;
                WordPressStubContext::setState('options', $options);

                return true;
            }
        );
        WordPressStubContext::set(
            'current_user_can',
            function ($capability) {
                $caps = WordPressStubContext::getState('user_caps', array());

                return ! empty($caps[$capability]);
            }
        );
        WordPressStubContext::set(
            'wp_verify_nonce',
            function ($nonce, $action) {
                WordPressStubContext::setState('nonce_checked', true);
                WordPressStubContext::setState('nonce_action', $action);

                return true;
            }
        );
        WordPressStubContext::set(
            'do_action',
            static function () {
                return null;
            }
        );
        WordPressStubContext::set(
            'wp_die',
            function ($message = '') {
                WordPressStubContext::setState('wp_die_called', true);
                WordPressStubContext::setState('wp_die_message', (string) $message);

                throw new \RuntimeException('wp_die');
            }
        );

        $this->resetDebugLoggerSingleton();

        if (! class_exists(PPCart_Debug_Logger::class, false)) {
            require_once PPCART_PLUGIN_ROOT . 'includes/logging/class-ppcart-debug-logger.php';
        }

        if (! class_exists(PPCart_Admin_Settings::class, false)) {
            require_once PPCART_PLUGIN_ROOT . 'admin/class-ppcart-admin-settings.php';
        }
    }

    protected function _after(): void
    {
        $_REQUEST = $this->originalRequest;
        WordPressStubContext::clear();
        parent::_after();
    }

    public function test_UT_032_debug_log_view_handler_returns_early_off_the_settings_page(): void
    {
        $this->setRequest(
            array(
                'page'        => 'other-page',
                'ppcart_view_log' => '1',
            )
        );
        $this->setUserCaps(array('manage_options' => false));

        $this->callViewLogRequest();

        $this->assertFalse(WordPressStubContext::getState('wp_die_called', false));
        $this->assertFalse(WordPressStubContext::getState('nonce_checked', false));
    }

    public function test_UT_033_debug_log_view_handler_returns_early_when_no_log_action_is_requested(): void
    {
        $this->setRequest(array('page' => 'ppcart-settings'));
        $this->setUserCaps(array('manage_options' => false));

        $this->callViewLogRequest();

        $this->assertFalse(WordPressStubContext::getState('wp_die_called', false));
        $this->assertFalse(WordPressStubContext::getState('nonce_checked', false));
    }

    public function test_UT_034_debug_log_view_is_denied_without_the_manage_options_capability(): void
    {
        $this->setRequest(
            array(
                'page'        => 'ppcart-settings',
                'ppcart_view_log' => '1',
            )
        );
        $this->setUserCaps(array('manage_options' => false));

        try {
            $this->callViewLogRequest();
            $this->fail('Expected wp_die when viewing debug log without manage_options.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('wp_die', $exception->getMessage());
        }

        $this->assertTrue(WordPressStubContext::getState('wp_die_called', false));
        $this->assertFalse(WordPressStubContext::getState('nonce_checked', false));
    }

    public function test_UT_035_debug_log_download_is_denied_without_the_manage_options_capability(): void
    {
        $this->setRequest(
            array(
                'page'             => 'ppcart-settings',
                'ppcart_download_log'  => '1',
            )
        );
        $this->setUserCaps(array('manage_options' => false));

        try {
            $this->callViewLogRequest();
            $this->fail('Expected wp_die when downloading debug log without manage_options.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('wp_die', $exception->getMessage());
        }

        $this->assertTrue(WordPressStubContext::getState('wp_die_called', false));
        $this->assertFalse(WordPressStubContext::getState('nonce_checked', false));
    }

    public function test_UT_036_debug_log_view_verifies_the_nonce_after_the_capability_passes(): void
    {
        $this->setRequest(
            array(
                'page'        => 'ppcart-settings',
                'ppcart_view_log' => '1',
            )
        );
        $this->setUserCaps(array('manage_options' => true));

        WordPressStubContext::set(
            'wp_verify_nonce',
            function ($nonce, $action) {
                WordPressStubContext::setState('nonce_checked', true);
                WordPressStubContext::setState('nonce_action', $action);
                WordPressStubContext::setState('nonce_query_arg', 'ppcart_view_debug_log_nonce');

                throw new \RuntimeException('render-log-page');
            }
        );

        try {
            $this->callViewLogRequest();
            $this->fail('Expected render_log_page path after nonce verification.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('render-log-page', $exception->getMessage());
        }

        $this->assertFalse(WordPressStubContext::getState('wp_die_called', false));
        $this->assertSame('ppcart_view_debug_log', WordPressStubContext::getState('nonce_action'));
        $this->assertSame('ppcart_view_debug_log_nonce', WordPressStubContext::getState('nonce_query_arg'));
    }

    public function test_UT_037_debug_log_download_verifies_the_nonce_after_the_capability_passes(): void
    {
        $this->setRequest(
            array(
                'page'            => 'ppcart-settings',
                'ppcart_download_log' => '1',
            )
        );
        $this->setUserCaps(array('manage_options' => true));

        WordPressStubContext::set(
            'wp_verify_nonce',
            function ($nonce, $action) {
                WordPressStubContext::setState('nonce_checked', true);
                WordPressStubContext::setState('nonce_action', $action);
                WordPressStubContext::setState('nonce_query_arg', 'ppcart_download_debug_log_nonce');

                throw new \RuntimeException('download-log');
            }
        );

        try {
            $this->callViewLogRequest();
            $this->fail('Expected download path after nonce verification.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('download-log', $exception->getMessage());
        }

        $this->assertFalse(WordPressStubContext::getState('wp_die_called', false));
        $this->assertSame('ppcart_download_debug_log', WordPressStubContext::getState('nonce_action'));
        $this->assertSame('ppcart_download_debug_log_nonce', WordPressStubContext::getState('nonce_query_arg'));
    }

    public function test_UT_038_debug_log_reset_is_denied_without_the_manage_options_capability(): void
    {
        $this->setRequest(array('ppcart_reset_log' => '1'));
        $this->setUserCaps(array('manage_options' => false));

        $settings = (new \ReflectionClass(PPCart_Admin_Settings::class))->newInstanceWithoutConstructor();

        try {
            $settings->page_options();
            $this->fail('Expected wp_die when resetting debug log without manage_options.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('wp_die', $exception->getMessage());
        }

        $this->assertTrue(WordPressStubContext::getState('wp_die_called', false));
        $this->assertFalse(WordPressStubContext::getState('nonce_checked', false));
    }

    /**
     * @param array<string, mixed> $request
     * @return void
     */
    private function setRequest(array $request): void
    {
        $_REQUEST = $request;
    }

    /**
     * @param array<string, bool> $caps
     * @return void
     */
    private function setUserCaps(array $caps): void
    {
        WordPressStubContext::setState('user_caps', $caps);
    }

    /**
     * @return void
     */
    private function callViewLogRequest(): void
    {
        $logger = new PPCart_Debug_Logger();
        $logger->view_log_request();
    }

    /**
     * @return void
     */
    private function resetDebugLoggerSingleton(): void
    {
        if (! class_exists(PPCart_Debug_Logger::class, false)) {
            return;
        }

        $reflection = new \ReflectionClass(PPCart_Debug_Logger::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null);

        if ($reflection->hasProperty('current_flow_id')) {
            $flow_id = $reflection->getProperty('current_flow_id');
            $flow_id->setAccessible(true);
            $flow_id->setValue(null);
        }
    }
}
