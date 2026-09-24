<?php

declare(strict_types=1);

namespace Tests\Support\Integration;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Stripe_Sync;
use PPCart_Stripe_Webhook_Logger;
use PPCart_Order;
use PPCart_Subscription;

abstract class StripeSyncTestCase extends WPTestCase
{
    /**
     * @var array<int, int>
     */
    protected $createdPostIds = array();

    /**
     * @var string|null
     */
    private static $webhookLogDir;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (! defined('PPCART_STRIPE_WEBHOOK_LOG_DIR')) {
            self::$webhookLogDir = sys_get_temp_dir() . '/ppcart-stripe-webhook-log-test-' . getmypid();
            define('PPCART_STRIPE_WEBHOOK_LOG_DIR', self::$webhookLogDir);
        }

        if (! defined('PPCART_STRIPE_WEBHOOK_LOG_MAX_BYTES')) {
            define('PPCART_STRIPE_WEBHOOK_LOG_MAX_BYTES', 1200);
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->createdPostIds = array();
    }

    protected function tearDown(): void
    {
        foreach (array_reverse($this->createdPostIds) as $postId) {
            wp_delete_post($postId, true);
        }

        $this->resetWebhookLog();
        parent::tearDown();
    }

    /**
     * @return void
     */
    protected function resetWebhookLog(): void
    {
        if (class_exists(PPCart_Stripe_Webhook_Logger::class, false)) {
            PPCart_Stripe_Webhook_Logger::clear_log_files();
        }

        delete_option('_ppcart_enable_stripe_webhook_log');
        delete_option('_ppcart_stripe_webhook_log_include_ignored');
        delete_option('_ppcart_stripe_webhook_log_file');

        $_SERVER = array(
            'REQUEST_URI' => '/',
            'HTTP_HOST' => 'example.org',
        );
    }

    /**
     * @param array<string, mixed> $overrides
     * @return int
     */
    protected function createStripeOrder(array $overrides = array()): int
    {
        $orderId = wp_insert_post(
            array(
                'post_type' => ppcart_live_post_type('order'),
                'post_status' => 'publish',
                'post_title' => 'Stripe Sync Test Order',
            )
        );

        $this->createdPostIds[] = (int) $orderId;

        $defaults = array(
            'pay_method' => 'stripe',
            'status' => 'paid',
            'payment_status' => 'paid',
            'transaction_id' => 'ch_test',
            'amount' => 200,
            'currency' => 'USD',
            'refund_log' => array(),
        );

        $this->seedOrderMeta((int) $orderId, array_merge($defaults, $overrides));

        return (int) $orderId;
    }

    /**
     * @param array<string, mixed> $overrides
     * @return int
     */
    protected function createStripeSubscriptionWithOrder(array $overrides = array()): int
    {
        $subscriptionId = $this->createStripeSubscription($overrides);
        $orderId = $this->createStripeOrder(
            array(
                'subscription_id' => $subscriptionId,
            )
        );
        ppcart_update_post_meta($subscriptionId, 'first_order', $orderId);

        return $subscriptionId;
    }

    /**
     * @param array<string, mixed> $overrides
     * @return int
     */
    protected function createStripeSubscription(array $overrides = array()): int
    {
        $subscriptionId = wp_insert_post(
            array(
                'post_type' => ppcart_live_post_type('subscription'),
                'post_status' => 'active',
                'post_title' => 'Stripe Sync Test Subscription',
            )
        );

        $this->createdPostIds[] = (int) $subscriptionId;

        $defaults = array(
            'pay_method' => 'stripe',
            'subscription_id' => 'sub_test_' . $subscriptionId,
            'status' => 'active',
            'sub_status' => 'active',
            'sub_next_bill_date' => '111',
            'cancel_at' => '',
            'cancel_date' => '',
            'amount' => 200,
            'currency' => 'USD',
            'email' => 'customer@example.test',
            'first_name' => 'First',
            'last_name' => 'Last',
        );

        $this->seedSubscriptionMeta((int) $subscriptionId, array_merge($defaults, $overrides));

        return (int) $subscriptionId;
    }

    /**
     * @param int                  $orderId
     * @param array<string, mixed> $values
     * @return void
     */
    protected function seedOrderMeta(int $orderId, array $values): void
    {
        foreach ($values as $key => $value) {
            ppcart_update_post_meta($orderId, $key, $value);
        }
    }

    /**
     * @param int                  $subscriptionId
     * @param array<string, mixed> $values
     * @return void
     */
    protected function seedSubscriptionMeta(int $subscriptionId, array $values): void
    {
        foreach ($values as $key => $value) {
            ppcart_update_post_meta($subscriptionId, $key, $value);
        }
    }

    /**
     * @param object $charge
     * @return object
     */
    protected function createStripeChargeClientStub($charge)
    {
        return new StripeSyncStripeClientStub($charge);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function captureReconciliationQuery(): ?array
    {
        $captured = null;

        add_filter(
            'posts_pre_query',
            function ($posts, $query) use (&$captured) {
                $captured = $query->query_vars;

                return $posts;
            },
            10,
            2
        );

        PPCart_Stripe_Sync::reconcile_stale_subscriptions();

        remove_all_filters('posts_pre_query');

        return $captured;
    }

    /**
     * @param int $postId
     * @return string
     */
    protected function getPostModified(int $postId): string
    {
        return (string) get_post_field('post_modified', $postId);
    }
}

/**
 * Minimal Stripe client double for charge retrieval tests.
 */
final class StripeSyncStripeClientStub
{
    /**
     * @var StripeSyncChargeServiceStub
     */
    public $charges;

    /**
     * @param object $charge
     */
    public function __construct($charge)
    {
        $this->charges = new StripeSyncChargeServiceStub($charge);
    }
}

final class StripeSyncChargeServiceStub
{
    /**
     * @var object
     */
    private $charge;

    /**
     * @param object $charge
     */
    public function __construct($charge)
    {
        $this->charge = $charge;
    }

    /**
     * @return object
     */
    public function retrieve()
    {
        return $this->charge;
    }
}

final class StripeSyncDebugLoggerStub
{
    /**
     * @var array<int, array<string, mixed>>
     */
    public $logs = array();

    /**
     * @param string $message
     * @param int    $level
     * @return void
     */
    public function log_debug($message, $level = 0)
    {
        $this->logs[] = array(
            'message' => $message,
            'level' => $level,
        );
    }
}
