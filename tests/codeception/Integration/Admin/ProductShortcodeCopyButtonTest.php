<?php

declare(strict_types=1);

namespace Tests\Integration\Admin;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Admin_Order_List_Controller;
use ReflectionClass;

class ProductShortcodeCopyButtonTest extends WPTestCase
{
    /**
     * @test-id IT-410
     */
    public function test_IT_410_shortcode_copy_button_name_contains_its_visible_shortcode(): void
    {
        $postId = 4242;
        $controller = (new ReflectionClass(PPCart_Admin_Order_List_Controller::class))
            ->newInstanceWithoutConstructor();

        ob_start();
        $controller->custom_product_column('shortcode', $postId);
        $html = (string) ob_get_clean();

        $document = new \DOMDocument();
        $document->loadHTML('<?xml encoding="utf-8"?><div>' . $html . '</div>', LIBXML_NOERROR | LIBXML_NOWARNING);
        $button = $document->getElementsByTagName('button')->item(0);

        $this->assertNotNull($button);
        $visibleText = trim($button->textContent);
        $this->assertSame('[ppcart_form id="' . $postId . '"]', $visibleText);
        $this->assertSame($visibleText, $button->getAttribute('data-ppcart-copy'));
        // WCAG 2.5.3: the accessible name must contain the visible label.
        $this->assertStringContainsString($visibleText, $button->getAttribute('aria-label'));
        $this->assertStringStartsWith('Copy shortcode', $button->getAttribute('aria-label'));
    }
}
