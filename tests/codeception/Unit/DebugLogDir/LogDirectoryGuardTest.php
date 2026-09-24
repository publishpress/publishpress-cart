<?php

namespace unit\DebugLogDir;

use Codeception\Test\Unit;
use PPCart_Debug_Logger;
use PPCart_Stripe_Webhook_Logger;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Tests\Support\WordPressStubContext;
use UnitTester;

class LogDirectoryGuardTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var string[]
     */
    private $tempDirs = [];

    protected function _before(): void
    {
        WordPressStubContext::clear();

        if (! class_exists(PPCart_Debug_Logger::class, false)) {
            require_once PPCART_PLUGIN_ROOT . 'includes/logging/class-ppcart-debug-logger.php';
        }

        if (! class_exists(PPCart_Stripe_Webhook_Logger::class, false)) {
            require_once PPCART_PLUGIN_ROOT . 'includes/logging/class-ppcart-stripe-webhook-logger.php';
        }
    }

    protected function _after(): void
    {
        foreach ($this->tempDirs as $dir) {
            $this->removeDirectory($dir);
        }
        $this->tempDirs = [];
        WordPressStubContext::clear();
        parent::_after();
    }

    /**
     * @test-id UT-351
     */
    public function test_UT_351_debug_log_directory_writes_html_guard_not_php(): void
    {
        $dir    = $this->makeTempDir();
        $logger = $this->debugLoggerForDir($dir);

        $this->assertTrue($logger->ensure_log_directory());
        $this->assertHtmlGuardWithoutPhp($dir);
    }

    /**
     * @test-id UT-351
     */
    public function test_UT_351_debug_log_directory_removes_leftover_php_guard(): void
    {
        $dir = $this->makeTempDir();
        file_put_contents($dir . '/index.php', "<?php\nexit;\n");
        $logger = $this->debugLoggerForDir($dir);

        $this->assertTrue($logger->ensure_log_directory());
        $this->assertHtmlGuardWithoutPhp($dir);
    }

    /**
     * @test-id UT-351
     */
    public function test_UT_351_webhook_log_directory_writes_html_guard_not_php(): void
    {
        $upload_base = $this->makeTempDir();
        $this->stubUploadDir($upload_base);

        $ensure = new ReflectionMethod(PPCart_Stripe_Webhook_Logger::class, 'ensure_log_directory');
        $ensure->setAccessible(true);

        $this->assertTrue($ensure->invoke(null));
        $this->assertHtmlGuardWithoutPhp($upload_base . '/publishpress-cart/logs');
    }

    /**
     * @test-id UT-351
     */
    public function test_UT_351_webhook_log_directory_removes_leftover_php_guard(): void
    {
        $upload_base = $this->makeTempDir();
        $log_dir     = $upload_base . '/publishpress-cart/logs';
        mkdir($log_dir, 0755, true);
        file_put_contents($log_dir . '/index.php', "<?php\nexit;\n");
        $this->stubUploadDir($upload_base);

        $ensure = new ReflectionMethod(PPCart_Stripe_Webhook_Logger::class, 'ensure_log_directory');
        $ensure->setAccessible(true);

        $this->assertTrue($ensure->invoke(null));
        $this->assertHtmlGuardWithoutPhp($log_dir);
    }

    /**
     * @param string $dir Log folder path.
     * @return PPCart_Debug_Logger
     */
    private function debugLoggerForDir($dir)
    {
        $logger   = (new ReflectionClass(PPCart_Debug_Logger::class))->newInstanceWithoutConstructor();
        $property = new ReflectionProperty(PPCart_Debug_Logger::class, 'log_folder_path');
        $property->setAccessible(true);
        $property->setValue($logger, $dir);

        return $logger;
    }

    /**
     * @param string $upload_base Stubbed uploads basedir.
     * @return void
     */
    private function stubUploadDir($upload_base)
    {
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
    }

    /**
     * @param string $dir Log folder path.
     * @return void
     */
    private function assertHtmlGuardWithoutPhp($dir)
    {
        $html = $dir . '/index.html';
        $php  = $dir . '/index.php';

        $this->assertFileExists($html);
        $this->assertStringNotContainsString('<?php', (string) file_get_contents($html));
        $this->assertFileDoesNotExist($php);
    }

    /**
     * @return string
     */
    private function makeTempDir()
    {
        $dir = sys_get_temp_dir() . '/ppcart-log-guard-' . uniqid('', true);
        mkdir($dir, 0755, true);
        $this->tempDirs[] = $dir;

        return $dir;
    }

    /**
     * @param string $dir Directory to remove.
     * @return void
     */
    private function removeDirectory($dir)
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if (false === $items) {
            return;
        }

        foreach ($items as $item) {
            if ('.' === $item || '..' === $item) {
                continue;
            }

            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
                continue;
            }

            unlink($path);
        }

        rmdir($dir);
    }
}
