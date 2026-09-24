<?php

namespace {

    if (! function_exists('current_time')) {
        function current_time($type = 'mysql')
        {
            if (\Tests\Support\WordPressStubContext::has('current_time')) {
                return \Tests\Support\WordPressStubContext::invoke('current_time', func_get_args());
            }

            return '2026-09-20 16:00:00';
        }
    }

}

namespace unit\DebugLogDir {

    use Codeception\Test\Unit;
    use PPCart_Helper;
    use Tests\Support\WordPressStubContext;
    use UnitTester;

    class HelperLoggerPathTest extends Unit
    {
        /**
         * @var UnitTester
         */
        protected $tester;

        /**
         * @var string
         */
        private $uploadBase;

        /**
         * @var string
         */
        private $pluginLogPath;

        /**
         * @var bool
         */
        private $pluginLogExisted = false;

        /**
         * @var string
         */
        private $expectedLogPath;

        /**
         * @var string|null
         */
        private $pluginLogBefore = null;

        protected function _before(): void
        {
            WordPressStubContext::clear();

            if (! defined('PPCART_BASE_DIR')) {
                define('PPCART_BASE_DIR', PPCART_PLUGIN_ROOT);
            }

            if (! class_exists(\PPCart_Order_Helper::class, false)) {
                require_once PPCART_PLUGIN_ROOT . 'includes/helpers/class-ppcart-order-helper.php';
            }

            if (! class_exists(PPCart_Helper::class, false)) {
                require_once PPCART_PLUGIN_ROOT . 'includes/helpers/class-ppcart-helper.php';
            }

            $this->pluginLogPath    = rtrim(PPCART_BASE_DIR, '/\\') . '/debug.log';
            $this->pluginLogExisted = file_exists($this->pluginLogPath);
            $this->pluginLogBefore  = $this->pluginLogExisted ? file_get_contents($this->pluginLogPath) : null;

            $this->uploadBase = sys_get_temp_dir() . '/ppcart-helper-log-' . uniqid('', true);
            wp_mkdir_p($this->uploadBase);

            WordPressStubContext::set(
                'wp_upload_dir',
                function () {
                    return [
                        'basedir' => $this->uploadBase,
                        'baseurl' => 'https://example.test/wp-content/uploads',
                        'error'   => false,
                    ];
                }
            );

            $this->expectedLogPath = $this->uploadBase . '/publishpress-cart/logs/debug.log';
        }

        protected function _after(): void
        {
            if (! $this->pluginLogExisted && is_string($this->pluginLogPath) && file_exists($this->pluginLogPath)) {
                @unlink($this->pluginLogPath);
            }

            $this->removeDirectory($this->uploadBase);
            WordPressStubContext::clear();
            parent::_after();
        }

        /**
         * @test-id UT-350
         */
        public function test_UT_350_helper_logger_writes_under_uploads_not_plugin_dir(): void
        {
            $this->assertFalse(
                defined('PPCART_DEBUG_LOG_DIR'),
                'PPCART_DEBUG_LOG_DIR must be undefined to lock the uploads default path'
            );

            $helper = new PPCart_Helper();
            $bytes  = $helper->ppcartLogger('hello-749');

            $this->assertNotFalse($bytes);
            $this->assertFileExists($this->expectedLogPath);
            $this->assertStringContainsString('hello-749', file_get_contents($this->expectedLogPath));
            if ($this->pluginLogExisted) {
                $this->assertSame($this->pluginLogBefore, file_get_contents($this->pluginLogPath));
            } else {
                $this->assertFileDoesNotExist($this->pluginLogPath);
            }
        }

        /**
         * @param string $directory
         * @return void
         */
        private function removeDirectory($directory): void
        {
            if (! is_dir($directory)) {
                return;
            }

            $items = scandir($directory);
            if (! is_array($items)) {
                return;
            }

            foreach ($items as $item) {
                if ('.' === $item || '..' === $item) {
                    continue;
                }

                $path = $directory . DIRECTORY_SEPARATOR . $item;
                if (is_dir($path)) {
                    $this->removeDirectory($path);
                    continue;
                }

                @unlink($path);
            }

            @rmdir($directory);
        }
    }

}
