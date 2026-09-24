<?php

declare(strict_types=1);

namespace Tests\Integration\Prefix;

use lucatume\WPBrowser\TestCase\WPTestCase;

class AdminTinyMceJsTest extends WPTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    /**
     * @test-id IT-356
     */
    public function test_IT_356_email_template_editor_emits_canonical_tinymce_id(): void
    {
        $optionId = '_ppcart_email_order_confirmation_body';
        $atts = [
            'id' => $optionId,
            'name' => $optionId,
            'value' => 'Preview body',
            'settings' => [
                'textarea_name' => $optionId,
                'textarea_rows' => 8,
            ],
        ];

        ob_start();
        include PPCART_PLUGIN_ROOT . 'admin/partials/ppcart-admin-field-editor.php';
        $html = (string) ob_get_clean();

        $expectedEditorId = 'ppcart-' . $optionId;
        $this->assertStringContainsString('id="' . $expectedEditorId . '"', $html);
        $this->assertStringNotContainsString('id="sc-' . $optionId . '"', $html);
    }

    /**
     * @test-id IT-356
     */
    public function test_IT_356_email_preview_js_reads_tinymce_body_via_canonical_id(): void
    {
        $settingsJs = (string) file_get_contents(PPCART_PLUGIN_ROOT . 'admin/js/ppcart-settings.js');

        $this->assertStringContainsString("editorId = 'ppcart-' + optionId", $settingsJs);
        $this->assertStringContainsString('window.tinymce.get(editorId)', $settingsJs);
        $this->assertStringNotContainsString("editorId = 'sc-' + optionId", $settingsJs);
    }
}
