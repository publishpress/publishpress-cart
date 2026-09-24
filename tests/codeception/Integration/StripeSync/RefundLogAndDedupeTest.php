<?php

declare(strict_types=1);

namespace Tests\Integration\StripeSync;

use PPCart_Stripe_Sync;
use Tests\Support\Integration\StripeSyncDebugLoggerStub;
use Tests\Support\Integration\StripeSyncTestCase;

class RefundLogAndDedupeTest extends StripeSyncTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    public function test_IT_038_refund_log_normalizes_mixed_shape_entries_to_be_keyed_by_refund_id(): void
    {
        $normalized = PPCart_Stripe_Sync::normalize_refund_log(
            array(
                array(
                    'refundID' => 're_one',
                    'amount' => '1.00',
                ),
                're_two' => array(
                    'amount' => '2.00',
                ),
            )
        );

        $this->assertArrayHasKey('re_one', $normalized);
        $this->assertArrayHasKey('re_two', $normalized);
    }

    public function test_IT_039_manual_refund_entries_keep_distinct_keys_despite_sharing_one_refund_id(): void
    {
        $manualLog = PPCart_Stripe_Sync::normalize_refund_log(
            array(
                'manual' => array(
                    'refundID' => 'manual',
                    'amount' => '1.00',
                ),
                'manual_1_2' => array(
                    'refundID' => 'manual',
                    'amount' => '2.00',
                ),
                'manual_1_3' => array(
                    'refundID' => 'manual',
                    'amount' => '3.00',
                ),
            )
        );

        $this->assertArrayHasKey('manual', $manualLog);
        $this->assertArrayHasKey('manual_1_2', $manualLog);
        $this->assertArrayHasKey('manual_1_3', $manualLog);
    }

    public function test_IT_040_first_time_a_webhook_event_id_is_seen_the_dedupe_gate_opens(): void
    {
        $event = (object) array('id' => 'evt_duplicate');

        $this->assertTrue(PPCart_Stripe_Sync::event_dedupe_gate($event));
    }

    public function test_IT_041_re_delivered_webhook_event_id_is_rejected_by_the_dedupe_gate(): void
    {
        $event = (object) array('id' => 'evt_duplicate');

        PPCart_Stripe_Sync::event_dedupe_gate($event);

        $this->assertFalse(PPCart_Stripe_Sync::event_dedupe_gate($event));
    }

    public function test_IT_042_dedupe_gate_records_event_under_the_ppcart_stripe_evt_transient_prefix(): void
    {
        $event = (object) array('id' => 'evt_duplicate');

        PPCart_Stripe_Sync::event_dedupe_gate($event);

        $this->assertNotFalse(get_transient('ppcart_stripe_evt_evt_duplicate'));
    }

    public function test_IT_043_releasing_the_dedupe_gate_lets_a_not_yet_applied_event_be_retried(): void
    {
        $event = (object) array('id' => 'evt_retry');

        PPCart_Stripe_Sync::event_dedupe_gate($event);
        PPCart_Stripe_Sync::release_event_dedupe_gate($event);

        $this->assertTrue(PPCart_Stripe_Sync::event_dedupe_gate($event));
    }

    public function test_IT_044_unrecognized_stripe_subscription_status_is_logged_via_the_debug_logger(): void
    {
        global $ppcart_debug_logger;

        $previousLogger = $ppcart_debug_logger ?? null;
        $logger = new StripeSyncDebugLoggerStub();
        $ppcart_debug_logger = $logger;

        PPCart_Stripe_Sync::map_subscription(
            (object) array(
                'status' => 'brand_new_status',
                'current_period_end' => 1234567890,
                'cancel_at' => 0,
            )
        );

        $this->assertNotEmpty($logger->logs);
        $this->assertStringContainsString('Unknown Stripe subscription status', $logger->logs[0]['message']);

        $ppcart_debug_logger = $previousLogger;
    }
}
