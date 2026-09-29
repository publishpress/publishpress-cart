<?php

declare(strict_types=1);

namespace Tests\Integration\OrderAccess;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Order;

class ReceiptLinkHtmlEscapingTest extends WPTestCase
{
    /**
     * @var int[]
     */
    private $createdPostIds = [];

    protected function tearDown(): void
    {
        foreach ($this->createdPostIds as $postId) {
            wp_delete_post($postId, true);
        }

        $this->createdPostIds = [];

        parent::tearDown();
    }

    public function test_receipt_link_html_escapes_custom_label_and_url(): void
    {
        $orderId = $this->createOrderWithStatus('paid', 'receipt-token-abc');
        $order   = new PPCart_Order($orderId);

        $html = $order->receipt_link_html('<script>alert(1)</script>');

        $this->assertIsString($html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $this->assertStringContainsString('type=receipt', html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $this->assertStringContainsString('ppcart-invoice=', $html);
    }

    public function test_receipt_link_html_default_label_is_present(): void
    {
        $orderId = $this->createOrderWithStatus('paid', 'receipt-token-def');
        $order   = new PPCart_Order($orderId);

        $html = $order->receipt_link_html();

        $this->assertIsString($html);
        $this->assertStringContainsString('Download Receipt', $html);
    }

    public function test_invoice_link_html_escapes_custom_label_for_parity(): void
    {
        $orderId = $this->createOrderWithStatus('paid', 'invoice-token-ghi');
        $order   = new PPCart_Order($orderId);

        $html = $order->invoice_link_html('<img src=x onerror=alert(1)>');

        $this->assertIsString($html);
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('&lt;img', $html);
        $this->assertStringContainsString('type=invoice', html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    public function test_receipt_link_html_returns_false_for_unpaid_order(): void
    {
        $orderId = $this->createOrderWithStatus('pending-payment', 'pending-token');
        $order   = new PPCart_Order($orderId);

        $this->assertFalse($order->receipt_link_html());
    }

    private function createOrderWithStatus(string $status, string $token): int
    {
        $orderId = wp_insert_post(
            [
                'post_type'   => ppcart_live_post_type('order'),
                'post_status' => 'publish',
                'post_title'  => 'Receipt link order',
            ]
        );

        $this->assertIsInt($orderId);
        $this->assertGreaterThan(0, $orderId);
        $this->createdPostIds[] = (int) $orderId;

        ppcart_update_post_meta($orderId, 'status', $status);
        ppcart_update_post_meta($orderId, PPCart_Order::INVOICE_TOKEN_META_KEY, $token);

        return (int) $orderId;
    }
}
