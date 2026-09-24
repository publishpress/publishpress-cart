<?php

declare(strict_types=1);

namespace Tests\Integration\Email;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Order;

class OrderTableEmailTest extends WPTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    /**
     * @var int[]
     */
    private $createdPostIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        wp_set_current_user(0);
    }

    protected function tearDown(): void
    {
        foreach ($this->createdPostIds as $postId) {
            wp_delete_post($postId, true);
        }
        $this->createdPostIds = [];

        parent::tearDown();
    }

    public function test_IT_374_order_table_escapes_customer_text_fields(): void
    {
        $orderId   = $this->createPaidOrderWithHostileCustomerFields();
        $orderInfo = (new PPCart_Order($orderId))->get_data();
        $this->assertIsArray($orderInfo);

        ob_start();
        ppcart_do_order_table('confirmation', $orderInfo);
        $html = (string) ob_get_clean();

        $this->assertStringContainsString('Customer Information', $html);

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringNotContainsString('<a href', $html);

        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $this->assertStringContainsString('&lt;a href=&quot;evil&quot;&gt;call&lt;/a&gt;', $html);
        $this->assertStringContainsString('evil&lt;script&gt;alert(1)&lt;/script&gt;@example.test', $html);

        $this->assertStringContainsString('Ada', $html);
        $this->assertStringContainsString('Lovelace', $html);

        $this->assertStringContainsString('&lt;b&gt;123 Market&lt;/b&gt;', $html);
        $this->assertStringContainsString('<br', $html);
    }

    /**
     * @return int
     */
    private function createPaidOrderWithHostileCustomerFields(): int
    {
        $orderId = wp_insert_post(
            [
                'post_type'   => ppcart_live_post_type('order'),
                'post_status' => 'publish',
                'post_title'  => 'Order Table Email Order',
            ]
        );
        $this->assertIsInt($orderId);
        $this->assertGreaterThan(0, $orderId);
        $this->createdPostIds[] = (int) $orderId;

        ppcart_update_post_meta($orderId, 'user_account', 0);
        ppcart_update_post_meta($orderId, 'status', 'paid');
        ppcart_update_post_meta($orderId, 'amount', 10);
        ppcart_update_post_meta($orderId, 'pre_tax_amount', 10);
        ppcart_update_post_meta($orderId, 'customer_name', 'Ada<img src=x onerror=alert(1)> Lovelace');
        ppcart_update_post_meta($orderId, 'company', 'Acme<script>alert(1)</script>');
        ppcart_update_post_meta($orderId, 'email', 'evil<script>alert(1)</script>@example.test');
        ppcart_update_post_meta($orderId, 'phone', '<a href="evil">call</a>');
        ppcart_update_post_meta($orderId, 'address1', '<b>123 Market</b>');

        return (int) $orderId;
    }
}
