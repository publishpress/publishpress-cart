<?php

declare(strict_types=1);

namespace Tests\Integration\Admin;

use DOMDocument;
use DOMXPath;
use lucatume\WPBrowser\TestCase\WPTestCase;

class FieldTooltipNameTest extends WPTestCase
{
    private function renderField(string $partial, array $atts): DOMXPath
    {
        ob_start();
        include PPCART_PLUGIN_ROOT . 'admin/partials/ppcart-admin-field-' . $partial . '.php';
        $html = (string) ob_get_clean();

        $document = new DOMDocument();
        $document->loadHTML('<?xml encoding="utf-8"?><div>' . $html . '</div>', LIBXML_NOERROR | LIBXML_NOWARNING);

        return new DOMXPath($document);
    }

    /**
     * @test-id IT-411
     */
    public function test_IT_411_tooltip_text_describes_the_field_instead_of_naming_it(): void
    {
        $xpath = $this->renderField('text', [
            'id'          => 'it411_price',
            'name'        => '_it411_price',
            'label'       => 'Sale price',
            'tooltip'     => 'Shown instead of the regular price while the sale runs.',
            'description' => 'Leave empty for no sale.',
        ]);

        $input = $xpath->query('//input[@id="it411_price"]')->item(0);
        $this->assertNotNull($input);

        // Named by the label text alone, not by the tooltip.
        $labelledBy = $input->getAttribute('aria-labelledby');
        $this->assertNotSame('', $labelledBy);
        $this->assertSame('Sale price', trim($xpath->query('//*[@id="' . $labelledBy . '"]')->item(0)->textContent));

        // Described by the help line and the tooltip.
        $describedBy = preg_split('/\s+/', $input->getAttribute('aria-describedby'));
        $this->assertContains('it411_price-help', $describedBy);
        $tooltipId = end($describedBy);
        $tooltip = $xpath->query('//*[@id="' . $tooltipId . '"]')->item(0);
        $this->assertNotNull($tooltip);
        $this->assertSame('img', $tooltip->getAttribute('role'));
        $this->assertSame('0', $tooltip->getAttribute('tabindex'));
        $this->assertSame('Shown instead of the regular price while the sale runs.', $tooltip->getAttribute('aria-label'));

        // The visible label still contains the text and the "?".
        $label = $xpath->query('//label[@for="it411_price"]')->item(0);
        $this->assertSame('Sale price?', trim($label->textContent));
    }

    /**
     * @test-id IT-411
     */
    public function test_IT_411_fields_without_tooltip_keep_their_plain_label(): void
    {
        $xpath = $this->renderField('text', [
            'id'          => 'it411_plain',
            'name'        => '_it411_plain',
            'label'       => 'Plain field',
            'description' => 'Help line.',
        ]);

        $input = $xpath->query('//input[@id="it411_plain"]')->item(0);
        $this->assertFalse($input->hasAttribute('aria-labelledby'));
        $this->assertSame('it411_plain-help', $input->getAttribute('aria-describedby'));
        $this->assertSame('Plain field', trim($xpath->query('//label[@for="it411_plain"]')->item(0)->textContent));
    }
}
