<?php

declare(strict_types=1);

namespace Tests\Integration\StripeSync;

use PPCart_Stripe_Sync;
use PPCart_Subscription;
use PublishPress\Stripe\ApiRequestor;
use PublishPress\Stripe\HttpClient\ClientInterface;
use Tests\Support\Integration\StripeSyncTestCase;

/**
 * Serves one old Stripe subscription with no Cart metadata, and its invoices.
 */
class ImportStripeSubscriptionFakeStripe implements ClientInterface
{
    /** @var string */
    private $subscriptionId;

    /** @var string */
    private $priceId;

    /** @var int */
    private $start;

    public function __construct(string $subscriptionId, string $priceId)
    {
        $this->subscriptionId = $subscriptionId;
        $this->priceId = $priceId;
        $this->start = 1600000000;
    }

    public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null)
    {
        $path = (string) parse_url($absUrl, PHP_URL_PATH);

        if ('/v1/subscriptions/' . $this->subscriptionId === $path) {
            return array(wp_json_encode($this->subscription()), 200, array());
        }

        if ('/v1/invoices' === $path) {
            return array(
                wp_json_encode(
                    array(
                        'object' => 'list',
                        'url' => $path,
                        'has_more' => false,
                        // Newest first, as Stripe lists them.
                        'data' => array($this->invoice(1), $this->invoice(0)),
                    )
                ),
                200,
                array(),
            );
        }

        return array(wp_json_encode(array('error' => array('message' => 'Unhandled fake request: ' . $method . ' ' . $path))), 404, array());
    }

    private function subscription(): array
    {
        return array(
            'object' => 'subscription',
            'id' => $this->subscriptionId,
            'status' => 'canceled',
            'livemode' => false,
            'currency' => 'usd',
            'created' => $this->start,
            'start_date' => $this->start,
            'canceled_at' => $this->start + 40 * DAY_IN_SECONDS,
            'metadata' => array(),
            'customer' => array(
                'object' => 'customer',
                'id' => 'cus_import',
                'email' => 'imported@example.test',
                'name' => 'Imported Customer',
            ),
            'items' => array(
                'object' => 'list',
                'data' => array(
                    array(
                        'id' => 'si_import',
                        'quantity' => 1,
                        'current_period_end' => $this->start + 60 * DAY_IN_SECONDS,
                        'price' => array(
                            'object' => 'price',
                            'id' => $this->priceId,
                            'currency' => 'usd',
                            'unit_amount' => 2900,
                            'recurring' => array('interval' => 'month', 'interval_count' => 1),
                        ),
                    ),
                ),
            ),
        );
    }

    private function invoice(int $index): array
    {
        $paidAt = $this->start + $index * 30 * DAY_IN_SECONDS;

        return array(
            'object' => 'invoice',
            'id' => 'in_import_' . $index,
            'status' => 'paid',
            'currency' => 'usd',
            'amount_paid' => 2900,
            'status_transitions' => array('paid_at' => $paidAt, 'finalized_at' => $paidAt),
            'parent' => array('subscription_details' => array('subscription' => $this->subscriptionId)),
            'payments' => array(
                'object' => 'list',
                'data' => array(
                    array(
                        'status' => 'paid',
                        'payment' => array('type' => 'charge', 'charge' => 'ch_import_' . $index),
                    ),
                ),
            ),
            'lines' => array(
                'object' => 'list',
                'data' => array(
                    array(
                        'parent' => array(
                            'subscription_item_details' => array(
                                'subscription' => $this->subscriptionId,
                                'subscription_item' => 'si_import',
                            ),
                        ),
                        'metadata' => array(),
                        'period' => array('start' => $paidAt, 'end' => $paidAt + 30 * DAY_IN_SECONDS),
                    ),
                ),
            ),
        );
    }
}

class ImportStripeSubscriptionTest extends StripeSyncTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    protected function setUp(): void
    {
        parent::setUp();
        update_option('_ppcart_stripe_api', 'test');
        update_option('_ppcart_stripe_test_sk', 'sk_test_import_subscription');
    }

    protected function tearDown(): void
    {
        ApiRequestor::setHttpClient(null);
        delete_option('_ppcart_stripe_test_sk');
        parent::tearDown();
    }

    public function test_IT_430_importing_a_stripe_subscription_creates_it_with_its_orders(): void
    {
        $productId = $this->createProduct('price_import_match');
        ApiRequestor::setHttpClient(new ImportStripeSubscriptionFakeStripe('sub_import_new', 'price_import_match'));

        $sub = PPCart_Stripe_Sync::import_subscription('sub_import_new');

        $this->assertInstanceOf(PPCart_Subscription::class, $sub);
        $this->createdPostIds[] = (int) $sub->id;
        $this->assertSame('sub_import_new', ppcart_get_post_meta($sub->id, 'subscription_id', true));
        $this->assertSame('canceled', ppcart_get_post_meta($sub->id, 'status', true));
        $this->assertEquals($productId, ppcart_get_post_meta($sub->id, 'product_id', true));
        $this->assertSame('imported@example.test', ppcart_get_post_meta($sub->id, 'email', true));
        $this->assertSame('cus_import', ppcart_get_post_meta($sub->id, 'customer_id', true));

        $orders = $this->ordersOf((int) $sub->id);
        $this->assertCount(2, $orders);
        foreach ($orders as $orderId) {
            $this->createdPostIds[] = (int) $orderId;
            $this->assertSame('imported@example.test', ppcart_get_post_meta($orderId, 'email', true));
        }
        $this->assertEquals($orders[0], ppcart_get_post_meta($sub->id, 'first_order', true));
    }

    public function test_IT_431_importing_a_stripe_subscription_that_cart_already_has_is_refused(): void
    {
        $existingId = $this->createStripeSubscription(array('subscription_id' => 'sub_import_known'));

        $result = PPCart_Stripe_Sync::import_subscription('sub_import_known');

        $this->assertWPError($result);
        $this->assertSame('ppcart_import_exists', $result->get_error_code());
        $this->assertSame($existingId, $result->get_error_data()['subscription_id']);
    }

    public function test_IT_432_importing_needs_a_product_when_no_product_sells_the_stripe_price(): void
    {
        ApiRequestor::setHttpClient(new ImportStripeSubscriptionFakeStripe('sub_import_no_product', 'price_import_unknown'));

        $result = PPCart_Stripe_Sync::import_subscription('sub_import_no_product');
        $this->assertWPError($result);
        $this->assertSame('ppcart_import_product', $result->get_error_code());

        $productId = $this->createProduct('price_import_other');
        $sub = PPCart_Stripe_Sync::import_subscription('sub_import_no_product', array('product_id' => $productId));

        $this->assertInstanceOf(PPCart_Subscription::class, $sub);
        $this->createdPostIds[] = (int) $sub->id;
        foreach ($this->ordersOf((int) $sub->id) as $orderId) {
            $this->createdPostIds[] = (int) $orderId;
        }
        $this->assertEquals($productId, ppcart_get_post_meta($sub->id, 'product_id', true));
    }

    private function createProduct(string $priceId): int
    {
        $productId = wp_insert_post(
            array(
                'post_type' => ppcart_live_post_type('product'),
                'post_status' => 'publish',
                'post_title' => 'Imported Membership',
            )
        );
        $this->createdPostIds[] = (int) $productId;
        ppcart_update_post_meta((int) $productId, 'pay_options', array(array('option_id' => 'monthly', 'stripe_plan_id' => $priceId)));

        return (int) $productId;
    }

    /**
     * @return array<int, int>
     */
    private function ordersOf(int $subscriptionId): array
    {
        return array_map(
            'intval',
            get_posts(
                array(
                    'post_type' => ppcart_query_post_types('order'),
                    'post_status' => 'any',
                    'posts_per_page' => -1,
                    'fields' => 'ids',
                    'orderby' => 'date',
                    'order' => 'ASC',
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
}
