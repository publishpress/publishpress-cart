<?php

namespace unit\Download;

use Codeception\Test\Unit;
use PPCart_Files;
use Tests\Support\WordPressStubContext;
use UnitTester;

class DownloadPathValidationTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var string
     */
    private $tempRoot;

    /**
     * @var string
     */
    private $uploadBase;

    /**
     * @var string
     */
    private $ppcartUploadsDir;

    protected function _before(): void
    {
        $this->tempRoot = sys_get_temp_dir() . '/ppcart-download-path-' . uniqid('', true);
        $this->uploadBase = $this->tempRoot . '/wp-content/uploads';
        $this->ppcartUploadsDir = $this->uploadBase . '/ppcart-uploads';

        wp_mkdir_p($this->ppcartUploadsDir . '/nested');
        file_put_contents($this->ppcartUploadsDir . '/product.zip', 'download-me');
        file_put_contents($this->ppcartUploadsDir . '/nested/product.zip', 'nested-download');
        file_put_contents($this->tempRoot . '/wp-config.php', 'secret-config');

        WordPressStubContext::clear();
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
        WordPressStubContext::set(
            'apply_filters',
            function ($hook, $value) {
                if ('ppcart_download_allowed_roots' === $hook) {
                    return $value;
                }

                return $value;
            }
        );

        if (! class_exists(PPCart_Files::class, false)) {
            require_once PPCART_PLUGIN_ROOT . 'includes/files/class-ppcart-files.php';
        }
    }

    protected function _after(): void
    {
        $this->removeDirectory($this->tempRoot);
        WordPressStubContext::clear();
        parent::_after();
    }

    public function test_UT_069_allows_download_of_a_file_located_inside_the_ppcart_uploads_directory(): void
    {
        $resolved = PPCart_Files::resolve_local_download_path($this->ppcartUploadsDir . '/product.zip');

        $this->assertSame(
            realpath($this->ppcartUploadsDir . '/product.zip'),
            $resolved
        );
    }

    public function test_UT_070_allows_an_uploads_url_that_maps_to_a_real_ppcart_uploads_file(): void
    {
        $resolved = PPCart_Files::resolve_local_download_path(
            'https://example.test/wp-content/uploads/ppcart-uploads/product.zip'
        );

        $this->assertSame(
            realpath($this->ppcartUploadsDir . '/product.zip'),
            $resolved
        );
    }

    public function test_UT_071_rejects_an_absolute_path_outside_the_allowed_upload_directory(): void
    {
        $this->assertFalse(
            PPCart_Files::resolve_local_download_path($this->tempRoot . '/wp-config.php')
        );
    }

    public function test_UT_072_rejects_a_traversal_that_escapes_the_upload_directory(): void
    {
        $this->assertFalse(
            PPCart_Files::resolve_local_download_path(
                $this->ppcartUploadsDir . '/../../../wp-config.php'
            )
        );
    }

    public function test_UT_073_rejects_a_nested_traversal_that_escapes_the_upload_directory(): void
    {
        $this->assertFalse(
            PPCart_Files::resolve_local_download_path(
                $this->ppcartUploadsDir . '/nested/../../../wp-config.php'
            )
        );
    }

    public function test_UT_074_rejects_an_external_url_that_does_not_map_into_the_uploads_root(): void
    {
        $this->assertFalse(
            PPCart_Files::resolve_local_download_path('https://evil.example/secret.txt')
        );
    }

    public function test_UT_075_rejects_a_nonexistent_file_inside_the_ppcart_uploads_directory(): void
    {
        $this->assertFalse(
            PPCart_Files::resolve_local_download_path($this->ppcartUploadsDir . '/missing.zip')
        );
    }

    public function test_UT_076_rejects_an_empty_download_path(): void
    {
        $this->assertFalse(PPCart_Files::resolve_local_download_path(''));
    }

    public function test_UT_077_honors_an_additional_allowed_root_registered_via_the_filter(): void
    {
        $extraRoot = $this->tempRoot . '/extra-downloads';
        wp_mkdir_p($extraRoot);
        file_put_contents($extraRoot . '/bonus.zip', 'bonus');

        WordPressStubContext::set(
            'apply_filters',
            function ($hook, $value) use ($extraRoot) {
                if ('ppcart_download_allowed_roots' === $hook) {
                    $value[] = realpath($extraRoot);

                    return $value;
                }

                return $value;
            }
        );

        $this->assertSame(
            realpath($extraRoot . '/bonus.zip'),
            PPCart_Files::resolve_local_download_path($extraRoot . '/bonus.zip')
        );
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $items = scandir($directory);

        if (false === $items) {
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
