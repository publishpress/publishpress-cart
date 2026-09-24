<?php

declare(strict_types=1);

namespace Tests\Integration\StripeSync;

use Exception;
use PPCart_Stripe_Sync;
use PPCart_Subscription;
use Tests\Support\Integration\StripeSyncTestCase;

class ReconciliationAndHandlersTest extends StripeSyncTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    public function test_IT_026_failed_sync_records_a_reconciliation_failure_count(): void
    {
        $subscriptionId = $this->createStripeSubscription();

        PPCart_Stripe_Sync::mark_sync_failure($subscriptionId, new Exception('temporary failure'));

        $this->assertSame('1', ppcart_get_post_meta($subscriptionId, 'stripe_recon_failures', true));
    }

    public function test_IT_027_failed_sync_stores_a_backoff_timestamp(): void
    {
        $subscriptionId = $this->createStripeSubscription();

        PPCart_Stripe_Sync::mark_sync_failure($subscriptionId, new Exception('temporary failure'));

        $this->assertNotSame('', ppcart_get_post_meta($subscriptionId, 'stripe_recon_backoff_until', true));
    }

    public function test_IT_028_failed_sync_stores_the_last_error_message(): void
    {
        $subscriptionId = $this->createStripeSubscription();

        PPCart_Stripe_Sync::mark_sync_failure($subscriptionId, new Exception('temporary failure'));

        $this->assertSame('temporary failure', ppcart_get_post_meta($subscriptionId, 'last_stripe_sync_error', true));
    }

    public function test_IT_029_successful_sync_stores_the_last_sync_timestamp(): void
    {
        $subscriptionId = $this->createStripeSubscription();

        PPCart_Stripe_Sync::mark_sync_failure($subscriptionId, new Exception('temporary failure'));
        PPCart_Stripe_Sync::mark_successful_sync($subscriptionId);

        $this->assertNotSame('', ppcart_get_post_meta($subscriptionId, 'last_stripe_sync', true));
    }

    public function test_IT_030_successful_sync_clears_the_reconciliation_failure_count(): void
    {
        $subscriptionId = $this->createStripeSubscription();

        PPCart_Stripe_Sync::mark_sync_failure($subscriptionId, new Exception('temporary failure'));
        PPCart_Stripe_Sync::mark_successful_sync($subscriptionId);

        $this->assertSame('', ppcart_get_post_meta($subscriptionId, 'stripe_recon_failures', true));
    }

    public function test_IT_031_successful_sync_clears_the_backoff_timestamp(): void
    {
        $subscriptionId = $this->createStripeSubscription();

        PPCart_Stripe_Sync::mark_sync_failure($subscriptionId, new Exception('temporary failure'));
        PPCart_Stripe_Sync::mark_successful_sync($subscriptionId);

        $this->assertSame('', ppcart_get_post_meta($subscriptionId, 'stripe_recon_backoff_until', true));
    }

    public function test_IT_032_successful_sync_clears_the_last_error_message(): void
    {
        $subscriptionId = $this->createStripeSubscription();

        PPCart_Stripe_Sync::mark_sync_failure($subscriptionId, new Exception('temporary failure'));
        PPCart_Stripe_Sync::mark_successful_sync($subscriptionId);

        $this->assertSame('', ppcart_get_post_meta($subscriptionId, 'last_stripe_sync_error', true));
    }

    public function test_IT_033_unknown_webhook_event_type_is_ignored(): void
    {
        $this->assertFalse(
            PPCart_Stripe_Sync::handle_webhook_event(
                (object) array(
                    'type' => 'unknown.event',
                )
            )
        );
    }

    public function test_IT_034_stripe_reconciliation_cron_hook_is_registered(): void
    {
        $this->assertNotFalse(
            has_action(PPCart_Stripe_Sync::CRON_HOOK)
        );
    }

    public function test_IT_035_reconciliation_query_caps_each_batch_to_fifty_records(): void
    {
        $query = $this->captureReconciliationQuery();

        $this->assertNotNull($query);
        $this->assertSame(50, $query['posts_per_page']);
    }

    public function test_IT_036_reconciliation_query_filters_on_stale_sync_metadata(): void
    {
        $query = $this->captureReconciliationQuery();

        $this->assertNotNull($query);
        $this->assertArrayHasKey('relation', $query['meta_query'][1]);
    }

    public function test_IT_037_reconciliation_query_filters_on_backoff_metadata(): void
    {
        $query = $this->captureReconciliationQuery();

        $this->assertNotNull($query);
        $this->assertArrayHasKey('relation', $query['meta_query'][2]);
    }
}
