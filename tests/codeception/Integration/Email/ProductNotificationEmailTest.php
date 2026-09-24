<?php

declare(strict_types=1);

namespace Tests\Integration\Email;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Order;

class ProductNotificationEmailTest extends WPTestCase
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

    public function test_IT_360_personalized_notification_body_is_kses_escaped(): void
    {
        $orderId   = $this->createPaidOrderWithHostileCustomerName();
        $orderInfo = (new PPCart_Order($orderId))->get_data();
        $this->assertIsArray($orderInfo);

        $email = ppcart_build_product_notification_email(
            [
                'send_to' => 'purchaser',
                'subject' => 'Thanks',
                'message' => 'Hi {customer_name}, <strong>thanks</strong>.'
                    . ' <a href="https://example.test/docs" target="_blank">Docs</a>'
                    . ' <p style="color: #333333;">Styled line</p>',
            ],
            $orderInfo
        );

        $body = (string) $email['body'];

        // Text merge tags are esc_html'd at insert; kses still runs last on the composed HTML.
        $this->assertStringNotContainsString('<script', $body);
        $this->assertStringNotContainsString('<img', $body);
        $this->assertStringContainsString('&lt;script&gt;', $body);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $body);

        // The escape must not cost the template its legitimate markup.
        $this->assertStringContainsString('Ada', $body);
        $this->assertStringContainsString('<strong>thanks</strong>', $body);
        $this->assertStringContainsString('href="https://example.test/docs"', $body);
        $this->assertStringContainsString('target="_blank"', $body);
        $this->assertStringContainsString('color: #333333', $body);

        // wpautop still runs before the allowlist, so paragraphs survive.
        $this->assertStringContainsString('<p', $body);
    }

    /**
     * Creates a paid order whose stored customer name carries markup a merge tag would inject.
     *
     * @return int
     */
    private function createPaidOrderWithHostileCustomerName(): int
    {
        $orderId = wp_insert_post(
            [
                'post_type'   => ppcart_live_post_type('order'),
                'post_status' => 'publish',
                'post_title'  => 'Notification Order',
            ]
        );
        $this->assertIsInt($orderId);
        $this->assertGreaterThan(0, $orderId);
        $this->createdPostIds[] = (int) $orderId;

        ppcart_update_post_meta($orderId, 'user_account', 0);
        ppcart_update_post_meta($orderId, 'status', 'paid');
        ppcart_update_post_meta($orderId, 'email', 'guest@example.test');
        ppcart_update_post_meta($orderId, 'amount', 10);
        ppcart_update_post_meta($orderId, 'firstname', 'Ada<script>alert(1)</script>');
        ppcart_update_post_meta($orderId, 'lastname', 'Lovelace<img src=x onerror=alert(1)>');

        return (int) $orderId;
    }
}
