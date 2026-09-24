<?php

declare(strict_types=1);

namespace Tests\Integration\Compat;

use lucatume\WPBrowser\TestCase\WPTestCase;

class StudiocartCompatibilityModeTest extends WPTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    /**
     * @test-id IT-161
     */
    public function test_IT_161_legacy_compat_file_is_gone(): void
    {
        $this->assertDirectoryDoesNotExist(PPCART_PLUGIN_ROOT . 'includes/compat');
        $this->assertFileDoesNotExist(PPCART_PLUGIN_ROOT . 'includes/compat/legacy.php');
    }
}
