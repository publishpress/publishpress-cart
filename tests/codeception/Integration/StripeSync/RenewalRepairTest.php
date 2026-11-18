<?php

declare(strict_types=1);

namespace Tests\Integration\StripeSync;

use PPCart_Stripe_Sync;
use PublishPress\Stripe\ApiRequestor;
use PublishPress\Stripe\HttpClient\ClientInterface;
use Tests\Support\Integration\StripeSyncTestCase;

/**
 * Serves one Stripe subscription and its paid invoices, newest first.
 */
class RenewalRepairFakeStripe implements ClientInterface
{
    /** @var string */
    public $subscriptionId;

    /** @var int */
    public $periodStart;

    /** @var array<int, array<string, mixed>> */
    public $invoices = array();

    /** @var array<int, string> */
    public $calls = array();

    public function __construct(string $subscriptionId, int $localId, int $paidRenewals)
    {
        $this->subscriptionId = $subscriptionId;
        $this->periodStart = time() - 3 * DAY_IN_SECONDS;

        for ($i = $paidRenewals - 1; $i >= 0; $i--) {
            $paidAt = (int) strtotime('-' . $i . ' month', $this->periodStart);
            $this->invoices[] = array(
                'object' => 'invoice',
                'id' => 'in_repair_' . $subscriptionId . '_' . $i,
                'status' => 'paid',
                'currency' => 'usd',
                'amount_paid' => 2900,
                'status_transitions' => array('paid_at' => $paidAt, 'finalized_at' => $paidAt),
                'parent' => array('subscription_details' => array('subscription' => $subscriptionId)),
                'payments' => array(
                    'object' => 'list',
                    'data' => array(
                        array(
                            'status' => 'paid',
                            'payment' => array('type' => 'charge', 'charge' => 'ch_repair_' . $subscriptionId . '_' . $i),
                        ),
                    ),
                ),
                'lines' => array(
                    'object' => 'list',
                    'data' => array(
                        array(
                            'parent' => array(
                                'subscription_item_details' => array(
                                    'subscription' => $subscriptionId,
                                    'subscription_item' => 'si_repair',
                                ),
                            ),
                            'metadata' => array(
                                'origin' => get_site_url(),
                                'ppcart_subscription_id' => (string) $localId,
                            ),
                            'period' => array('start' => $paidAt, 'end' => (int) strtotime('+1 month', $paidAt)),
                        ),
                    ),
                ),
            );
        }

        // Stripe lists the newest invoice first.
        $this->invoices = array_reverse($this->invoices);
    }

    public function listCalls(): int
    {
        return count(
            array_filter(
                $this->calls,
                static function ($call) {
                    return 0 === strpos($call, 'get /v1/invoices?')
                        || 'get /v1/invoices' === $call
                        || 'get /v1/invoices/search' === $call;
                }
            )
        );
    }

    public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null)
    {
        $parts = parse_url($absUrl);
        $query = array();
        if (! empty($parts['query'])) {
            parse_str($parts['query'], $query);
        }
        $query = array_merge($query, is_array($params) ? $params : array());
        $path = $parts['path'];
        $this->calls[] = strtolower($method) . ' ' . $path . ($query && '/v1/invoices' === $path ? '?' . http_build_query($query) : '');

        if ('/v1/subscriptions/' . $this->subscriptionId === $path) {
            return array(
                wp_json_encode(
                    array(
                        'object' => 'subscription',
                        'id' => $this->subscriptionId,
                        'status' => 'active',
                        'customer' => 'cus_repair',
                        'items' => array(
                            'object' => 'list',
                            'data' => array(
                                array(
                                    'id' => 'si_repair',
                                    'current_period_start' => $this->periodStart,
                                    'current_period_end' => (int) strtotime('+1 month', $this->periodStart),
                                ),
                            ),
                        ),
                    )
                ),
                200,
                array(),
            );
        }

        if ('/v1/invoices/create_preview' === $path) {
            return array(wp_json_encode(array('object' => 'invoice', 'next_payment_attempt' => (int) strtotime('+1 month', $this->periodStart))), 200, array());
        }

        if (preg_match('#^/v1/invoices/(in_[A-Za-z0-9_]+)$#', $path, $matches)) {
            foreach ($this->invoices as $invoice) {
                if ($invoice['id'] === $matches[1]) {
                    return array(wp_json_encode($invoice), 200, array());
                }
            }
        }

        if ('/v1/invoices' === $path || '/v1/invoices/search' === $path) {
            $limit = isset($query['limit']) ? (int) $query['limit'] : 10;
            $offset = ! empty($query['page']) ? (int) $query['page'] : 0;
            if (! empty($query['starting_after'])) {
                foreach ($this->invoices as $index => $invoice) {
                    if ($invoice['id'] === $query['starting_after']) {
                        $offset = $index + 1;
                    }
                }
            }

            return array(
                wp_json_encode(
                    array(
                        'object' => '/v1/invoices' === $path ? 'list' : 'search_result',
                        'url' => $path,
                        'data' => array_slice($this->invoices, $offset, $limit),
                        'has_more' => ($offset + $limit) < count($this->invoices),
                        'next_page' => ($offset + $limit) < count($this->invoices) ? (string) ($offset + $limit) : null,
                    )
                ),
                200,
                array(),
            );
        }

        return array(wp_json_encode(array('error' => array('message' => 'Unhandled fake request: ' . $method . ' ' . $path))), 404, array());
    }
}

class RenewalRepairTest extends StripeSyncTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    protected function setUp(): void
    {
        parent::setUp();
        update_option('_ppcart_stripe_test_sk', 'sk_test_renewal_repair');
    }

    protected function tearDown(): void
    {
        ApiRequestor::setHttpClient(null);
        delete_option('_ppcart_stripe_test_sk');
        parent::tearDown();
    }

    /**
     * Create a stale local subscription whose only order is the initial purchase.
     */
    private function createSubscriptionWithInitialOrder(string $orderDateGmt): int
    {
        $subscriptionId = $this->createStripeSubscriptionWithOrder(
            array(
                'gateway_mode' => 'test',
                'last_stripe_sync' => time() - 2 * DAY_IN_SECONDS,
            )
        );
        $orderId = (int) ppcart_get_post_meta($subscriptionId, 'first_order', true);
        wp_update_post(
            array(
                'ID' => $orderId,
                'post_date_gmt' => $orderDateGmt,
                'post_date' => get_date_from_gmt($orderDateGmt),
            )
        );

        return $subscriptionId;
    }

    private function useFakeStripe(int $subscriptionId, int $paidRenewals): RenewalRepairFakeStripe
    {
        $fake = new RenewalRepairFakeStripe(
            (string) ppcart_get_post_meta($subscriptionId, 'subscription_id', true),
            $subscriptionId,
            $paidRenewals
        );
        ApiRequestor::setHttpClient($fake);

        return $fake;
    }

    private function orderCount(int $subscriptionId): int
    {
        return count(
            get_posts(
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
            )
        );
    }

    public function test_background_sync_imports_renewals_that_never_became_orders(): void
    {
        $subscriptionId = $this->createSubscriptionWithInitialOrder(gmdate('Y-m-d H:i:s', (int) strtotime('-7 months')));
        $this->useFakeStripe($subscriptionId, 6);

        PPCart_Stripe_Sync::reconcile_stale_subscriptions();

        $this->assertSame(7, $this->orderCount($subscriptionId));
    }

    public function test_background_sync_tries_each_billing_period_only_once(): void
    {
        $subscriptionId = $this->createSubscriptionWithInitialOrder(gmdate('Y-m-d H:i:s', (int) strtotime('-7 months')));
        $fake = $this->useFakeStripe($subscriptionId, 6);

        PPCart_Stripe_Sync::reconcile_stale_subscriptions();
        $listCallsAfterFirstRun = $fake->listCalls();

        // Pretend the invoices were refused, so the order gap is still there.
        foreach (get_posts(array('post_type' => ppcart_query_post_types('order'), 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_query' => array(array('key' => ppcart_meta_key('subscription_id'), 'value' => $subscriptionId)), 'orderby' => 'date', 'order' => 'DESC')) as $index => $orderId) {
            if ($index < 6) {
                wp_delete_post($orderId, true);
            }
        }
        ppcart_update_post_meta($subscriptionId, 'last_stripe_sync', time() - 2 * DAY_IN_SECONDS);

        PPCart_Stripe_Sync::reconcile_stale_subscriptions();

        $this->assertSame(1, $listCallsAfterFirstRun);
        $this->assertSame($listCallsAfterFirstRun, $fake->listCalls());
    }

    public function test_background_sync_sends_no_renewal_emails_for_imported_orders(): void
    {
        update_option('_ppcart_email_renewal_enable', 1);
        $subscriptionId = $this->createSubscriptionWithInitialOrder(gmdate('Y-m-d H:i:s', (int) strtotime('-7 months')));
        $this->useFakeStripe($subscriptionId, 6);

        $renewalActions = 0;
        $countRenewal = static function () use (&$renewalActions) {
            $renewalActions++;
        };
        $mails = 0;
        $countMail = static function () use (&$mails) {
            $mails++;

            return true;
        };
        add_action('ppcart_renewal_payment', $countRenewal);
        add_filter('pre_wp_mail', $countMail);

        PPCart_Stripe_Sync::reconcile_stale_subscriptions();

        remove_action('ppcart_renewal_payment', $countRenewal);
        remove_filter('pre_wp_mail', $countMail);
        delete_option('_ppcart_email_renewal_enable');

        $this->assertSame(7, $this->orderCount($subscriptionId));
        $this->assertSame(0, $renewalActions);
        $this->assertSame(0, $mails);
        $this->assertFalse(has_filter('ppcart_trigger_order_integrations', '__return_false'));
    }

    public function test_background_sync_skips_subscriptions_with_current_orders(): void
    {
        $subscriptionId = $this->createSubscriptionWithInitialOrder(gmdate('Y-m-d H:i:s', time() - 2 * DAY_IN_SECONDS));
        $fake = $this->useFakeStripe($subscriptionId, 1);

        PPCart_Stripe_Sync::reconcile_stale_subscriptions();

        $this->assertSame(0, $fake->listCalls());
        $this->assertSame(1, $this->orderCount($subscriptionId));
    }
}
