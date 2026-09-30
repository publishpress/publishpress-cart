<?php

declare(strict_types=1);

namespace Tests\Integration\Admin;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Admin_Settings;

/**
 * Email settings preview link is escaped before wp_kses at the field echo site.
 */
class EmailPreviewHrefTest extends WPTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    /**
     * @var PPCart_Admin_Settings
     */
    private $settings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->settings = new PPCart_Admin_Settings('publishpress-cart', 'PublishPress Cart', PPCART_VERSION);
    }

    /**
     * @test-id IT-391
     */
    public function test_IT_391_email_preview_field_html_escapes_preview_href(): void
    {
        $options     = $this->settings->get_options_list();
        $description = $options['email_settings']['email_preview']['settings']['description'] ?? '';

        $this->assertNotSame('', $description);

        ob_start();
        $this->settings->field_html(
            [
                'description' => $description,
            ]
        );
        $html = (string) ob_get_clean();

        $this->assertStringContainsString('id="ppcart-preview-email"', $html);
        $this->assertStringContainsString('type=%5Bconfirmation%5D', $html);
        $this->assertStringContainsString('&amp;_wpnonce=', $html);
        $this->assertStringNotContainsString('type=[confirmation]', $html);
    }

    /**
     * @test-id IT-391
     */
    public function test_IT_391_email_preview_href_survives_filtered_site_url(): void
    {
        add_filter(
            'site_url',
            static function () {
                return 'http://example.com/?ppcart-preview=email&type=[confirmation]&_wpnonce=abc" onclick=alert(1)';
            }
        );

        $options     = $this->settings->get_options_list();
        $description = $options['email_settings']['email_preview']['settings']['description'] ?? '';

        ob_start();
        $this->settings->field_html(
            [
                'description' => $description,
            ]
        );
        $html = (string) ob_get_clean();

        // The injected text may stay inside the URL, but it must not become an attribute.
        $this->assertDoesNotMatchRegularExpression('/\sonclick=/i', $html);
        $this->assertStringNotContainsString('" onclick', $html);
        $this->assertMatchesRegularExpression('/href="[^"]*ppcart-preview=email/', $html);
    }
}
