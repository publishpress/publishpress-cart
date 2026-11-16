<?php

declare(strict_types=1);

namespace Tests\Integration\Admin;

use DOMDocument;
use DOMXPath;
use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Admin_Settings;

/**
 * Settings rows tie their visible label to the control they describe.
 */
class SettingsRowLabelsTest extends WPTestCase
{
    /**
     * Render a settings section the way WordPress core does and return its DOM.
     */
    private function renderSection(string $page, string $section): DOMXPath
    {
        if (! function_exists('do_settings_fields')) {
            require_once ABSPATH . 'wp-admin/includes/template.php';
        }

        ob_start();
        echo '<table>';
        do_settings_fields($page, $section);
        echo '</table>';
        $html = (string) ob_get_clean();

        $document = new DOMDocument();
        $document->loadHTML('<?xml encoding="utf-8"?>' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);

        return new DOMXPath($document);
    }

    /**
     * @test-id IT-412
     */
    public function test_IT_412_settings_row_labels_point_at_their_controls(): void
    {
        global $wp_settings_fields;

        if (! function_exists('add_settings_field')) {
            require_once ABSPATH . 'wp-admin/includes/template.php';
        }

        $settings = new PPCart_Admin_Settings('publishpress-cart', 'PublishPress Cart', PPCART_VERSION);
        $settings->register_fields();

        $expected = [
            '_ppcart_company_name',
            '_ppcart_company_address',
            '_ppcart_company_logo',
            '_ppcart_currency_position',
            '_ppcart_thousand_separator',
            '_ppcart_terms_url',
            '_ppcart_privacy_url',
            '_ppcart_myaccount_page_id',
            '_ppcart_email_from_name',
            '_ppcart_email_from_email',
            'ppcart_report_schedule',
            'ppcart_download_slug',
        ];
        $found = [];

        foreach ((array) $wp_settings_fields as $page => $sections) {
            foreach ((array) $sections as $section => $fields) {
                foreach ((array) $fields as $field) {
                    $id = $field['args']['id'] ?? '';
                    if (! in_array($id, $expected, true) || isset($found[$id])) {
                        continue;
                    }

                    $xpath = $this->renderSection((string) $page, (string) $section);
                    $label = $xpath->query('//th//label[@for="' . $id . '"]')->item(0);
                    $this->assertNotNull($label, "Row label for {$id} is not tied to a control.");
                    // Titles are stored HTML-escaped; the label text is what is read out.
                    $title = html_entity_decode(wp_strip_all_tags((string) $field['title']), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $this->assertSame(trim($title), trim($label->textContent));
                    $this->assertSame(1, $xpath->query('//td//*[@id="' . $id . '"][self::input or self::select or self::textarea]')->length, "No control with id {$id}.");
                    $found[$id] = true;
                }
            }
        }

        $this->assertEqualsCanonicalizing($expected, array_keys($found));
    }

    /**
     * @test-id IT-412
     */
    public function test_IT_412_test_email_row_label_names_the_email_type_select(): void
    {
        $settings = new PPCart_Admin_Settings('publishpress-cart', 'PublishPress Cart', PPCART_VERSION);
        $options  = $settings->get_options_list();

        $this->assertSame('ppcart-email-type', $options['email_settings']['email_preview']['settings']['label_for'] ?? '');
        $this->assertStringContainsString('id="ppcart-email-type"', $options['email_settings']['email_preview']['settings']['description'] ?? '');
    }
}
