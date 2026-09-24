<?php

namespace unit\DebugLogDir;

use Codeception\Test\Unit;
use PPCart_Debug_Log_Viewer;
use PPCart_Debug_Logger;
use ReflectionMethod;
use Tests\Support\WordPressStubContext;
use UnitTester;

class DebugLogDefaultDirTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    protected function _before(): void
    {
        WordPressStubContext::clear();

        if (! class_exists(PPCart_Debug_Logger::class, false)) {
            require_once PPCART_PLUGIN_ROOT . 'includes/logging/class-ppcart-debug-logger.php';
        }

        if (! class_exists(PPCart_Debug_Log_Viewer::class, false)) {
            require_once PPCART_PLUGIN_ROOT . 'includes/logging/class-ppcart-debug-log-viewer.php';
        }
    }

    protected function _after(): void
    {
        WordPressStubContext::clear();
        parent::_after();
    }

    /**
     * @test-id UT-221
     */
    public function test_UT_221_default_log_dir_uses_publishpress_cart_logs(): void
    {
        $this->assertFalse(
            \defined('PPCART_DEBUG_LOG_DIR'),
            'PPCART_DEBUG_LOG_DIR must be undefined to lock the uploads default path'
        );

        $resolve_log_dir = new ReflectionMethod(PPCart_Debug_Logger::class, 'resolve_log_dir');
        $resolve_log_dir->setAccessible(true);

        $get_log_folder_path = new ReflectionMethod(PPCart_Debug_Log_Viewer::class, 'get_log_folder_path');
        $get_log_folder_path->setAccessible(true);

        $upload_base = '/tmp/wp-content/uploads';
        WordPressStubContext::set(
            'wp_upload_dir',
            static function () use ($upload_base) {
                return [
                    'basedir' => $upload_base,
                    'baseurl' => 'https://example.test/wp-content/uploads',
                    'error'   => false,
                ];
            }
        );

        $this->assertDefaultPublishPressCartLogDir($resolve_log_dir->invoke(null), $upload_base);
        $this->assertDefaultPublishPressCartLogDir($get_log_folder_path->invoke(null), $upload_base);
    }

    /**
     * @param string $path        Resolved log directory.
     * @param string $upload_base Stubbed uploads basedir.
     * @return void
     */
    private function assertDefaultPublishPressCartLogDir($path, $upload_base): void
    {
        $normalized = str_replace('\\', '/', (string) $path);
        $expected   = rtrim(str_replace('\\', '/', $upload_base), '/') . '/publishpress-cart/logs';

        $this->assertStringEndsWith('/publishpress-cart/logs', $normalized);
        $this->assertStringNotContainsString('ncs-cart', $normalized);
        $this->assertStringNotContainsString('studiocart', $normalized);
        $this->assertSame($expected, $normalized);
    }
}
