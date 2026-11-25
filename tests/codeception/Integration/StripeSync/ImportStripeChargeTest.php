<?php

declare(strict_types=1);

namespace Tests\Integration\StripeSync;

use PPCart_Order;
use PPCart_Stripe_Sync;
use PublishPress\Stripe\ApiRequestor;
use PublishPress\Stripe\HttpClient\ClientInterface;
use Tests\Support\Integration\StripeSyncTestCase;

/**
 * Serves Stripe charges and PaymentIntents that have no invoice.
 */
class ImportStripeChargeFakeStripe implements ClientInterface
{
    /** @var array<string, array<string, mixed>> */
    private $charges = array();

    /**
     * @param array<string, mixed> $charge
     */
    public function addCharge(array $charge): self
    {
        $this->charges[$charge['id']] = array_merge(
            array(
                'object' => 'charge',
                'status' => 'succeeded',
                'currency' => 'usd',
                'amount' => 3900,
                'amount_refunded' => 0,
                'created' => 1706832000,
                'livemode' => false,
                'invoice' => null,
                'payment_intent' => null,
                'metadata' => array(),
                'refunds' => array('object' => 'list', 'data' => array(), 'has_more' => false),
            ),
            $charge
        );

        return $this;
    }

    public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null)
    {
        if ('get' !== strtolower((string) $method)) {
            return $this->error('The import must not write to Stripe: ' . $method . ' ' . $absUrl, 500);
        }

        $path = (string) parse_url($absUrl, PHP_URL_PATH);

        if (0 === strpos($path, '/v1/charges/')) {
            $id = substr($path, strlen('/v1/charges/'));
            return isset($this->charges[$id]) ? array(wp_json_encode($this->charges[$id]), 200, array()) : $this->error('No such charge', 404);
        }

        if (0 === strpos($path, '/v1/payment_intents/')) {
            $id = substr($path, strlen('/v1/payment_intents/'));
            foreach ($this->charges as $charge) {
                if ($id === $charge['payment_intent']) {
                    return array(
                        wp_json_encode(array('object' => 'payment_intent', 'id' => $id, 'status' => 'succeeded', 'latest_charge' => $charge)),
                        200,
                        array(),
                    );
                }
            }
            return $this->error('No such payment_intent', 404);
        }

        return $this->error('Unhandled fake request: ' . $method . ' ' . $path, 404);
    }

    private function error(string $message, int $code): array
    {
        return array(wp_json_encode(array('error' => array('message' => $message))), $code, array());
    }
}

class ImportStripeChargeTest extends StripeSyncTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    protected function setUp(): void
    {
        parent::setUp();
        update_option('_ppcart_stripe_api', 'test');
        update_option('_ppcart_stripe_test_sk', 'sk_test_import_charge');
    }

    protected function tearDown(): void
    {
        ApiRequestor::setHttpClient(null);
        delete_option('_ppcart_stripe_test_sk');
        parent::tearDown();
    }

    public function test_IT_435_importing_a_stripe_payment_adds_a_paid_order_to_the_subscription(): void
    {
        $subscriptionId = $this->createStripeSubscriptionWithOrder(array('customer_id' => 'cus_charge_owner', 'product_id' => 77));
        $stripe = (new ImportStripeChargeFakeStripe())->addCharge(array('id' => 'ch_manual_1', 'customer' => 'cus_charge_owner'));
        ApiRequestor::setHttpClient($stripe);

        $integrationRuns = 0;
        // Runs last, so it sees whether integrations would really run.
        $countRuns = function ($run) use (&$integrationRuns) {
            if ($run) {
                $integrationRuns++;
            }
            return $run;
        };
        add_filter('ppcart_trigger_order_integrations', $countRuns, PHP_INT_MAX);

        try {
            $order = PPCart_Stripe_Sync::import_charge('ch_manual_1', $subscriptionId);
        } finally {
            remove_filter('ppcart_trigger_order_integrations', $countRuns, PHP_INT_MAX);
        }

        $this->assertInstanceOf(PPCart_Order::class, $order);
        $this->createdPostIds[] = (int) $order->id;
        $this->assertSame('ch_manual_1', ppcart_get_post_meta($order->id, 'transaction_id', true));
        $this->assertEquals($subscriptionId, ppcart_get_post_meta($order->id, 'subscription_id', true));
        $this->assertEquals(39, (float) ppcart_get_post_meta($order->id, 'amount', true));
        $this->assertSame('paid', ppcart_get_post_meta($order->id, 'payment_status', true));
        $this->assertSame('customer@example.test', ppcart_get_post_meta($order->id, 'email', true));
        $this->assertSame(gmdate('Y-m-d', 1706832000), substr((string) get_post_field('post_date_gmt', $order->id), 0, 10));
        $this->assertSame(0, $integrationRuns, 'Order integrations must not run for an imported payment.');

        $again = PPCart_Stripe_Sync::import_charge('ch_manual_1', $subscriptionId);
        $this->assertWPError($again);
        $this->assertSame('ppcart_import_charge_exists', $again->get_error_code());
        $this->assertSame((int) $order->id, $again->get_error_data()['order_id']);
    }

    public function test_IT_436_importing_a_stripe_payment_of_another_customer_is_refused(): void
    {
        $subscriptionId = $this->createStripeSubscription(array('customer_id' => 'cus_charge_owner'));
        ApiRequestor::setHttpClient((new ImportStripeChargeFakeStripe())->addCharge(array('id' => 'ch_other_customer', 'customer' => 'cus_someone_else')));

        $result = PPCart_Stripe_Sync::import_charge('ch_other_customer', $subscriptionId);

        $this->assertWPError($result);
        $this->assertSame('ppcart_import_charge_customer', $result->get_error_code());
        $this->assertFalse(PPCart_Order::get_by_trans_id('ch_other_customer'));
    }

    public function test_IT_437_importing_a_payment_intent_uses_its_charge_and_refuses_failed_payments(): void
    {
        $subscriptionId = $this->createStripeSubscription(array('customer_id' => 'cus_charge_owner', 'product_id' => 77, 'product_name' => 'Membership'));
        $stripe = (new ImportStripeChargeFakeStripe())
            ->addCharge(array('id' => 'ch_from_intent', 'customer' => 'cus_charge_owner', 'payment_intent' => 'pi_manual_1', 'amount' => 2500))
            ->addCharge(array('id' => 'ch_failed', 'customer' => 'cus_charge_owner', 'status' => 'failed'));
        ApiRequestor::setHttpClient($stripe);

        $order = PPCart_Stripe_Sync::import_charge('pi_manual_1', $subscriptionId);

        $this->assertInstanceOf(PPCart_Order::class, $order);
        $this->createdPostIds[] = (int) $order->id;
        $this->assertSame('ch_from_intent', ppcart_get_post_meta($order->id, 'transaction_id', true));
        $this->assertEquals(25, (float) ppcart_get_post_meta($order->id, 'amount', true));

        $failed = PPCart_Stripe_Sync::import_charge('ch_failed', $subscriptionId);
        $this->assertWPError($failed);
        $this->assertSame('ppcart_import_charge_status', $failed->get_error_code());
    }

    public function test_IT_440_importing_refuses_an_invoice_payment_and_a_subscription_whose_only_order_is_unpaid(): void
    {
        $subscriptionId = $this->createStripeSubscription(array('customer_id' => 'cus_charge_owner', 'product_id' => 77));
        $unpaidOrderId = $this->createStripeOrder(array('subscription_id' => $subscriptionId, 'status' => 'pending-payment', 'payment_status' => 'pending-payment'));
        ppcart_update_post_meta($subscriptionId, 'first_order', $unpaidOrderId);
        $stripe = (new ImportStripeChargeFakeStripe())
            ->addCharge(array('id' => 'ch_invoice_paid', 'customer' => 'cus_charge_owner', 'invoice' => 'in_renewal'))
            ->addCharge(array('id' => 'ch_manual_2', 'customer' => 'cus_charge_owner'));
        ApiRequestor::setHttpClient($stripe);

        $invoicePayment = PPCart_Stripe_Sync::import_charge('ch_invoice_paid', $subscriptionId);
        $this->assertWPError($invoicePayment);
        $this->assertSame('ppcart_import_charge_invoice', $invoicePayment->get_error_code());

        $unpaidFirst = PPCart_Stripe_Sync::import_charge('ch_manual_2', $subscriptionId);
        $this->assertWPError($unpaidFirst);
        $this->assertSame('ppcart_import_charge_first_order', $unpaidFirst->get_error_code());
        $this->assertSame('pending-payment', ppcart_get_post_meta($unpaidOrderId, 'status', true));
        $this->assertFalse(PPCart_Order::get_by_trans_id('ch_manual_2'));
    }
}
