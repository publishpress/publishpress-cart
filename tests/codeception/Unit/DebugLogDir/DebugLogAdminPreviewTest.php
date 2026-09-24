<?php

namespace {

    if (! function_exists('admin_url')) {
        function admin_url($path = '', $scheme = 'admin')
        {
            if (\Tests\Support\WordPressStubContext::has('admin_url')) {
                return \Tests\Support\WordPressStubContext::invoke('admin_url', func_get_args());
            }

            return 'https://example.test/wp-admin/' . ltrim((string) $path, '/');
        }
    }

    if (! function_exists('wp_nonce_url')) {
        function wp_nonce_url($actionurl, $action = -1, $name = '_wpnonce')
        {
            if (\Tests\Support\WordPressStubContext::has('wp_nonce_url')) {
                return \Tests\Support\WordPressStubContext::invoke('wp_nonce_url', func_get_args());
            }

            $separator = false === strpos((string) $actionurl, '?') ? '?' : '&';

            return $actionurl . $separator . $name . '=nonce';
        }
    }

    if (! function_exists('human_time_diff')) {
        function human_time_diff($from, $to = 0)
        {
            if (\Tests\Support\WordPressStubContext::has('human_time_diff')) {
                return \Tests\Support\WordPressStubContext::invoke('human_time_diff', func_get_args());
            }

            return '1 minute';
        }
    }

}

namespace unit\DebugLogDir {

    use Codeception\Test\Unit;
    use PPCart_Debug_Logger;
    use Tests\Support\WordPressStubContext;
    use UnitTester;

    class DebugLogAdminPreviewTest extends Unit
    {
        /**
         * @var UnitTester
         */
        protected $tester;

        /**
         * @var string
         */
        private $uploadBase;

        protected function _before(): void
        {
            WordPressStubContext::clear();
            WordPressStubContext::set(
                'get_option',
                static function ($key, $default = false) {
                    $options = WordPressStubContext::getState('options', []);

                    return array_key_exists($key, $options) ? $options[$key] : $default;
                }
            );

            if (! class_exists(PPCart_Debug_Logger::class, false)) {
                require_once PPCART_PLUGIN_ROOT . 'includes/logging/class-ppcart-debug-logger.php';
            }

            $this->uploadBase = sys_get_temp_dir() . '/ppcart-admin-preview-' . uniqid('', true);
            mkdir($this->uploadBase . '/publishpress-cart/logs', 0755, true);

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
        }

        protected function _after(): void
        {
            $this->removeDirectory($this->uploadBase);
            WordPressStubContext::clear();
            parent::_after();
        }

        /**
         * @test-id UT-356
         */
        public function test_UT_356_admin_preview_reads_uploads_log_not_wp_content_dir(): void
        {
            $this->assertFalse(
                defined('PPCART_DEBUG_LOG_DIR'),
                'PPCART_DEBUG_LOG_DIR must be undefined to lock the uploads default path'
            );

            $log_dir  = $this->uploadBase . '/publishpress-cart/logs';
            $log_path = $log_dir . '/log.txt';
            file_put_contents($log_path, "[09/20/2026 10:00 PM] - STATUS: hello-779\n");

            $data = PPCart_Debug_Logger::get_admin_log_data();

            $this->assertTrue($data['exists']);
            $this->assertSame('log.txt', $data['file_name']);
            $this->assertStringContainsString('hello-779', $data['preview']);
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
