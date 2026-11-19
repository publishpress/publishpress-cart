<?php

declare(strict_types=1);

namespace Tests\Integration\StripeSync;

use PPCart_Stripe_Sync;
use PPCart_Subscription;
use PublishPress\Stripe\ApiRequestor;
use Tests\Support\Integration\StripeSyncTestCase;

if (! class_exists(InvoicePagingFakeStripe::class, false)) {
    require_once __DIR__ . '/InvoicePagingTest.php';
}

class InvoiceOriginTest extends StripeSyncTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    protected function setUp(): void
    {
        parent::setUp();
        update_option('_ppcart_stripe_test_sk', 'sk_test_invoice_origin');
    }

    protected function tearDown(): void
    {
        ApiRequestor::setHttpClient(null);
        delete_option('_ppcart_stripe_test_sk');
        parent::tearDown();
    }

    /**
     * @param int $subscriptionId Local subscription ID.
     * @return array<int, int>
     */
    private function renewalOrderIds(int $subscriptionId): array
    {
        return get_posts(
            array(
                'post_type' => ppcart_query_post_types('order'),
                'post_status' => 'any',
                'posts_per_page' => -1,
                'fields' => 'ids',
                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                'meta_query' => array(
                    array(
                        'key' => ppcart_meta_key('subscription_id'),
                        'value' => $subscriptionId,
                    ),
                ),
            )
        );
    }

    /**
     * @param InvoicePagingFakeStripe $fake   Fake Stripe client.
     * @param string                  $origin Origin URL to put on every invoice line.
     * @return void
     */
    private function setInvoiceOrigin(InvoicePagingFakeStripe $fake, string $origin): void
    {
        foreach ($fake->invoices as $index => $invoice) {
            $fake->invoices[ $index ]['lines']['data'][0]['metadata']['origin'] = $origin;
        }
    }

    public function test_sync_imports_renewals_when_origin_is_a_previous_site_url(): void
    {
        $subscriptionId = $this->createStripeSubscriptionWithOrder(array('gateway_mode' => 'test'));
        $fake = new InvoicePagingFakeStripe(
            (string) ppcart_get_post_meta($subscriptionId, 'subscription_id', true),
            $subscriptionId,
            3
        );
        $this->setInvoiceOrigin($fake, 'https://old-domain.example');
        ApiRequestor::setHttpClient($fake);

        PPCart_Stripe_Sync::sync_subscription_by_local(new PPCart_Subscription($subscriptionId), true);

        $this->assertCount(4, $this->renewalOrderIds($subscriptionId));
    }

    public function test_invoice_from_another_site_for_an_unknown_subscription_is_skipped(): void
    {
        $fake = new InvoicePagingFakeStripe('sub_other_site_origin', 999999, 1);
        $this->setInvoiceOrigin($fake, 'https://other-site.example');
        $invoice = json_decode((string) wp_json_encode($fake->invoices[0]));

        $this->assertFalse(PPCart_Stripe_Sync::sync_invoice_resource($invoice, null, 'invoice.paid'));
    }
}
