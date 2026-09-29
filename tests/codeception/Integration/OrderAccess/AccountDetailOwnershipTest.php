<?php

declare(strict_types=1);

namespace Tests\Integration\OrderAccess;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Order;
use PPCart_Public_Account_Controller;
use PPCart_Public_Page_Controller;
use WPDieException;

/**
 * Customers can only see and manage their own orders and subscriptions.
 */
class AccountDetailOwnershipTest extends WPTestCase
{
    /** Formatted total of the owner's order; only a rendered receipt shows it. */
    private const ORDER_MARKER = '4321.87';
    private const SUB_MARKER   = 'sub_secret_owner_a';

    /**
     * @var \IntegrationTester
     */
    protected $tester;

    /**
     * @var int[]
     */
    private $createdPostIds = [];

    /**
     * @var int[]
     */
    private $createdUserIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        wp_set_current_user(0);
        $_GET  = [];
        $_POST = [];
    }

    protected function tearDown(): void
    {
        wp_set_current_user(0);
        $_GET  = [];
        $_POST = [];

        foreach ($this->createdPostIds as $postId) {
            wp_delete_post($postId, true);
        }
        $this->createdPostIds = [];

        foreach ($this->createdUserIds as $userId) {
            wp_delete_user($userId);
        }
        $this->createdUserIds = [];

        parent::tearDown();
    }

    /**
     * @test-id IT-383
     */
    public function test_IT_383_account_order_detail_shortcode_renders_only_for_owner(): void
    {
        $ownerId = $this->createCustomer();
        $otherId = $this->createCustomer();
        $orderId = $this->createPaidOrder($ownerId, 'owner-token');
        $account = new PPCart_Public_Account_Controller();

        wp_set_current_user($otherId);
        $asOther = (string) $account->order_detail_shortcode([ 'order' => $orderId ]);
        $this->assertStringNotContainsString(self::ORDER_MARKER, $asOther);

        wp_set_current_user(0);
        $asGuest = (string) $account->order_detail_shortcode([ 'order' => $orderId ]);
        $this->assertStringNotContainsString(self::ORDER_MARKER, $asGuest);

        wp_set_current_user($ownerId);
        $asOwner = (string) $account->order_detail_shortcode([ 'order' => $orderId ]);
        $this->assertStringContainsString(self::ORDER_MARKER, $asOwner);
    }

    /**
     * @test-id IT-383
     */
    public function test_IT_383_order_detail_template_fails_closed_for_other_customer(): void
    {
        $ownerId = $this->createCustomer();
        $otherId = $this->createCustomer();
        $orderId = $this->createPaidOrder($ownerId, 'owner-token');

        wp_set_current_user($otherId);
        $html = (string) ppcart_get_template('my-account/order', 'detail', [ 'order' => $orderId ]);

        $this->assertStringNotContainsString(self::ORDER_MARKER, $html);
        $this->assertStringContainsString('No orders found.', $html);
    }

    /**
     * @test-id IT-384
     */
    public function test_IT_384_account_subscription_detail_shortcode_renders_only_for_owner(): void
    {
        $ownerId        = $this->createCustomer();
        $otherId        = $this->createCustomer();
        $subscriptionId = $this->createSubscription($ownerId, self::SUB_MARKER);
        $account        = new PPCart_Public_Account_Controller();

        wp_set_current_user($otherId);
        $asOther = (string) $account->subscription_detail_shortcode([ 'plan' => $subscriptionId ]);
        $this->assertStringNotContainsString(self::SUB_MARKER, $asOther);
        $this->assertStringNotContainsString('ppcart-cancel-sub', $asOther);

        wp_set_current_user(0);
        $asGuest = (string) $account->subscription_detail_shortcode([ 'plan' => $subscriptionId ]);
        $this->assertStringNotContainsString(self::SUB_MARKER, $asGuest);

        wp_set_current_user($ownerId);
        $asOwner = (string) $account->subscription_detail_shortcode([ 'plan' => $subscriptionId ]);
        $this->assertStringContainsString(self::SUB_MARKER, $asOwner);
    }

    /**
     * @test-id IT-384
     */
    public function test_IT_384_subscription_detail_template_fails_closed_for_other_customer(): void
    {
        $ownerId        = $this->createCustomer();
        $otherId        = $this->createCustomer();
        $subscriptionId = $this->createSubscription($ownerId, self::SUB_MARKER);

        wp_set_current_user($otherId);
        $html = (string) ppcart_get_template('my-account/subscription', 'detail', [ 'plan' => $subscriptionId ]);

        $this->assertStringNotContainsString(self::SUB_MARKER, $html);
        $this->assertStringContainsString('No subscription found.', $html);
    }

    /**
     * @test-id IT-385
     */
    public function test_IT_385_receipt_shortcode_needs_order_access_proof(): void
    {
        $ownerId = $this->createCustomer();
        $otherId = $this->createCustomer();
        $orderId = $this->createPaidOrder($ownerId, 'owner-token');
        $page    = new PPCart_Public_Page_Controller();

        $_GET['ppcart-order'] = (string) $orderId;

        wp_set_current_user(0);
        $this->assertStringNotContainsString(self::ORDER_MARKER, (string) $page->receipt_shortcode());

        wp_set_current_user($otherId);
        $this->assertStringNotContainsString(self::ORDER_MARKER, (string) $page->receipt_shortcode());

        wp_set_current_user($ownerId);
        $this->assertStringContainsString(self::ORDER_MARKER, (string) $page->receipt_shortcode());
    }

    /**
     * @test-id IT-385
     */
    public function test_IT_385_receipt_shortcode_shows_guest_order_with_token(): void
    {
        $orderId = $this->createPaidOrder(0, 'guest-token');
        $page    = new PPCart_Public_Page_Controller();

        $_GET['ppcart-order'] = (string) $orderId;
        $this->assertStringNotContainsString(self::ORDER_MARKER, (string) $page->receipt_shortcode());

        $_GET['token'] = 'wrong-token';
        $this->assertStringNotContainsString(self::ORDER_MARKER, (string) $page->receipt_shortcode());

        $_GET['token'] = 'guest-token';
        $this->assertStringContainsString(self::ORDER_MARKER, (string) $page->receipt_shortcode());
    }

    /**
     * @test-id IT-386
     */
    public function test_IT_386_customer_cancel_uses_stored_gateway_subscription_id(): void
    {
        $ownerId        = $this->createCustomer();
        $subscriptionId = $this->createSubscription($ownerId, 'sub_owner_stored');
        $captured       = [];
        $capture        = static function ($canceled, $sub, $subId) use (&$captured) {
            $captured[] = $subId;
            return false;
        };
        add_filter('ppcart_cancel_subscription', $capture, 10, 3);

        wp_set_current_user($ownerId);
        $_POST = [
            'nonce'           => wp_create_nonce('ppcart_ajax_nonce'),
            'id'              => (string) $subscriptionId,
            'subscription_id' => 'sub_someone_else',
        ];

        $this->runAjaxHandler('ppcart_unsubscribe_customer');
        remove_filter('ppcart_cancel_subscription', $capture, 10);

        $this->assertSame([ 'sub_owner_stored' ], $captured);
    }

    /**
     * @test-id IT-386
     */
    public function test_IT_386_customer_cannot_cancel_another_customers_subscription(): void
    {
        $ownerId        = $this->createCustomer();
        $otherId        = $this->createCustomer();
        $subscriptionId = $this->createSubscription($ownerId, 'sub_owner_stored');
        $captured       = [];
        $capture        = static function ($canceled, $sub, $subId) use (&$captured) {
            $captured[] = $subId;
            return false;
        };
        add_filter('ppcart_cancel_subscription', $capture, 10, 3);

        wp_set_current_user($otherId);
        $_POST = [
            'nonce'           => wp_create_nonce('ppcart_ajax_nonce'),
            'id'              => (string) $subscriptionId,
            'subscription_id' => 'sub_owner_stored',
        ];

        $this->runAjaxHandler('ppcart_unsubscribe_customer');
        remove_filter('ppcart_cancel_subscription', $capture, 10);

        $this->assertSame([], $captured);
    }

    /**
     * Run an AJAX handler that ends with wp_die() or wp_send_json_*().
     *
     * @param callable-string $handler Handler function name.
     * @return string Echoed output.
     */
    private function runAjaxHandler(string $handler): string
    {
        $dieHandler = static function () {
            return static function ($message = '') {
                throw new WPDieException(is_string($message) ? $message : '');
            };
        };
        add_filter('wp_die_ajax_handler', $dieHandler, 999);
        add_filter('wp_die_handler', $dieHandler, 999);
        add_filter('wp_doing_ajax', '__return_true');

        ob_start();
        try {
            $handler();
        } catch (WPDieException $e) {
            // Expected: the handler always ends the request.
        } finally {
            $output = (string) ob_get_clean();
            remove_filter('wp_die_ajax_handler', $dieHandler, 999);
            remove_filter('wp_die_handler', $dieHandler, 999);
            remove_filter('wp_doing_ajax', '__return_true');
        }

        return $output;
    }

    /**
     * @param int    $userAccount User id, or 0 for a guest.
     * @param string $token       Order access token.
     * @return int
     */
    private function createPaidOrder(int $userAccount, string $token): int
    {
        $orderId = wp_insert_post(
            [
                'post_type'   => ppcart_live_post_type('order'),
                'post_status' => 'publish',
                'post_title'  => 'Ownership Order',
            ]
        );
        $this->assertIsInt($orderId);
        $this->assertGreaterThan(0, $orderId);
        $this->createdPostIds[] = (int) $orderId;

        ppcart_update_post_meta($orderId, 'user_account', $userAccount);
        ppcart_update_post_meta($orderId, 'status', 'paid');
        ppcart_update_post_meta($orderId, 'email', 'owner-a@example.test');
        ppcart_update_post_meta($orderId, 'amount', 4321.87);
        ppcart_update_post_meta($orderId, 'product_name', 'Ownership Product');
        ppcart_update_post_meta($orderId, PPCart_Order::INVOICE_TOKEN_META_KEY, $token);

        return (int) $orderId;
    }

    /**
     * @param int    $userAccount     User id.
     * @param string $gatewaySubId    Gateway subscription id stored on the record.
     * @return int
     */
    private function createSubscription(int $userAccount, string $gatewaySubId): int
    {
        $subscriptionId = wp_insert_post(
            [
                'post_type'   => ppcart_live_post_type('subscription'),
                'post_status' => 'publish',
                'post_title'  => 'Ownership Subscription',
            ]
        );
        $this->assertIsInt($subscriptionId);
        $this->assertGreaterThan(0, $subscriptionId);
        $this->createdPostIds[] = (int) $subscriptionId;

        ppcart_update_post_meta($subscriptionId, 'user_account', $userAccount);
        ppcart_update_post_meta($subscriptionId, 'status', 'active');
        ppcart_update_post_meta($subscriptionId, 'sub_status', 'active');
        ppcart_update_post_meta($subscriptionId, 'pay_method', 'manual');
        ppcart_update_post_meta($subscriptionId, 'subscription_id', $gatewaySubId);
        ppcart_update_post_meta($subscriptionId, 'product_name', 'Ownership Plan Product');
        ppcart_update_post_meta($subscriptionId, 'sub_installments', '-1');
        ppcart_update_post_meta($subscriptionId, 'sub_amount', 10);
        ppcart_update_post_meta($subscriptionId, 'sub_next_bill_date', time() + DAY_IN_SECONDS);

        return (int) $subscriptionId;
    }

    /**
     * @return int
     */
    private function createCustomer(): int
    {
        $userId = $this->factory()->user->create([ 'role' => 'subscriber' ]);
        $this->assertIsInt($userId);
        $this->assertGreaterThan(0, $userId);
        $this->createdUserIds[] = (int) $userId;

        return (int) $userId;
    }
}
