<?php

namespace unit\Download;

use Codeception\Test\Unit;
use PPCart_Files_Settings_Trait;

class FilesRepeaterFieldTest extends Unit
{
    protected function _before(): void
    {
        if (! class_exists('PPCart_Product_Metaboxes', false)) {
            eval('class PPCart_Product_Metaboxes { public static function get_payment_plans() { return []; } }');
        }

        if (! trait_exists(PPCart_Files_Settings_Trait::class, false)) {
            require_once PPCART_PLUGIN_ROOT . 'includes/files/traits/trait-ppcart-files-settings.php';
        }
    }

    public function testFilesRepeaterUsesPrefixedRowClass(): void
    {
        $settings = new class {
            use PPCart_Files_Settings_Trait;
        };

        $fields = $settings->file_fields([]);

        $this->assertSame('_ppcart_files', $fields[0]['id']);
        $this->assertSame('ppcart-repeater', $fields[0]['class']);
    }
}
