<?php

declare(strict_types=1);

namespace Tests\Integration\StripeSync;

use PPCart_Admin_Order_Controller;
use PPCart_Order;
use PPCart_Stripe_Sync;
use PPCart_Subscription;
use PublishPress\Stripe\ApiRequestor;
use PublishPress\Stripe\HttpClient\ClientInterface;
use ReflectionClass;
use Tests\Support\Integration\StripeSyncTestCase;

/**
 * Serves Stripe charges and PaymentIntents. Refuses every write.
 */
class MigratedOrderSyncFakeStripe implements ClientInterface
{
    /** @var array<string, array<string, mixed>> */
    private $charges = array();

    /** @var array<int, string> */
    public $requests = array();

    /**
     * @param array<string, mixed> $charge
     */
    public function addCharge(array $charge): self
    {
        $this->charges[$charge['id']] = array_merge(
            array(
                'object' => 'charge',
                'status' => 'succeeded',
                'paid' => true,
                'captured' => true,
                'currency' => 'usd',
                'amount' => 2000,
                'amount_refunded' => 0,
                'refunded' => false,
                'created' => 1706832000,
                'livemode' => false,
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
        $path = (string) parse_url($absUrl, PHP_URL_PATH);
        $this->requests[] = strtoupper((string) $method) . ' ' . $path;

        if ('get' !== strtolower((string) $method)) {
            return $this->error('Sync must not write to Stripe: ' . $method . ' ' . $absUrl, 500);
        }

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

/**
 * Sync with Stripe on orders migrated from StudioCart: the payment method
 * shown as Cash, and refunded orders with no refund record.
 */
class MigratedOrderSyncTest extends StripeSyncTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    /** @var int */
    private $integrationRuns = 0;

    /** @var int */
    private $mailsSent = 0;

    /** @var callable */
    private $countIntegrations;

    /** @var callable */
    private $countMails;

    protected function setUp(): void
    {
        parent::setUp();
        update_option('_ppcart_stripe_api', 'test');
        update_option('_ppcart_stripe_test_sk', 'sk_test_migrated_order_sync');

        $this->integrationRuns = 0;
        $this->mailsSent = 0;
        // Runs last, so it sees whether integrations would really run.
        $this->countIntegrations = function ($run) {
            if ($run) {
                $this->integrationRuns++;
            }
            return $run;
        };
        $this->countMails = function () {
            $this->mailsSent++;
            return false;
        };
        add_filter('ppcart_trigger_order_integrations', $this->countIntegrations, PHP_INT_MAX);
        add_filter('pre_wp_mail', $this->countMails, PHP_INT_MAX);
    }

    protected function tearDown(): void
    {
        remove_filter('ppcart_trigger_order_integrations', $this->countIntegrations, PHP_INT_MAX);
        remove_filter('pre_wp_mail', $this->countMails, PHP_INT_MAX);
        ApiRequestor::setHttpClient(null);
        delete_option('_ppcart_stripe_test_sk');
        unset($_GET['post']);
        parent::tearDown();
    }

    public function test_IT_471_subscription_sync_links_a_migrated_cash_order_and_sets_it_to_stripe(): void
    {
        $paidAt = time();
        $subscriptionId = $this->createStripeSubscription(array('subscription_id' => 'sub_migrated_cash'));
        $orderId = $this->createStripeOrder(
            array(
                'pay_method' => 'cod',
                'gateway_mode' => '',
                'transaction_id' => '',
                'amount' => 20,
                'subscription_id' => $subscriptionId,
            )
        );
        $stripe = (new MigratedOrderSyncFakeStripe())->addCharge(array('id' => 'ch_migrated_cash', 'livemode' => true));
        ApiRequestor::setHttpClient($stripe);

        $result = PPCart_Stripe_Sync::sync_invoice_resource(
            $this->invoice('in_migrated_cash', 'sub_migrated_cash', 'ch_migrated_cash', $paidAt),
            new PPCart_Subscription($subscriptionId),
            'manual_sync',
            null,
            'USD',
            '',
            ppcart_stripe_client('sk_test_migrated_order_sync')
        );

        $this->assertInstanceOf(PPCart_Order::class, $result);
        $this->assertSame($orderId, (int) $result->id);
        $this->assertSame('ch_migrated_cash', ppcart_get_post_meta($orderId, 'transaction_id', true));
        $this->assertSame('stripe', ppcart_get_post_meta($orderId, 'pay_method', true));
        $this->assertSame('live', ppcart_get_post_meta($orderId, 'gateway_mode', true));
        $this->assertSame('paid', ppcart_get_post_meta($orderId, 'status', true));
        $this->assertSame(0, $this->integrationRuns);
        $this->assertSame(0, $this->mailsSent);
        $this->assertSame(array(), preg_grep('/^(?!GET )/', $stripe->requests), 'Sync must only read from Stripe.');
    }

    public function test_IT_472_sync_corrects_a_cash_order_that_already_has_a_stripe_charge(): void
    {
        // Order-level Sync with Stripe on a one-time order.
        $oneTimeId = $this->createStripeOrder(array('pay_method' => 'cod', 'gateway_mode' => '', 'transaction_id' => 'ch_cash_one_time'));
        // Subscription Sync on a renewal order.
        $subscriptionId = $this->createStripeSubscription(array('subscription_id' => 'sub_cash_renewal'));
        $renewalId = $this->createStripeOrder(
            array(
                'pay_method' => 'cod',
                'gateway_mode' => 'test',
                'transaction_id' => 'ch_cash_renewal',
                'amount' => 20,
                'subscription_id' => $subscriptionId,
            )
        );
        // An order with no Stripe ID is left alone.
        $cashId = $this->createStripeOrder(array('pay_method' => 'cod', 'gateway_mode' => '', 'transaction_id' => ''));
        $stripe = (new MigratedOrderSyncFakeStripe())
            ->addCharge(array('id' => 'ch_cash_one_time'))
            ->addCharge(array('id' => 'ch_cash_renewal', 'livemode' => true));
        ApiRequestor::setHttpClient($stripe);

        $this->assertSame('ch_cash_one_time', PPCart_Stripe_Sync::get_order_sync_payment_id(new PPCart_Order($oneTimeId)));
        $this->assertStringContainsString('Sync with Stripe', $this->renderOrderScreen($oneTimeId));
        $this->assertSame('Order synced successfully.', PPCart_Stripe_Sync::sync_order_by_local(new PPCart_Order($oneTimeId)));
        $this->assertSame('stripe', ppcart_get_post_meta($oneTimeId, 'pay_method', true));
        $this->assertSame('test', ppcart_get_post_meta($oneTimeId, 'gateway_mode', true));
        $this->assertSame('paid', ppcart_get_post_meta($oneTimeId, 'status', true));
        $this->assertContains('Payment method set to Stripe by Stripe sync.', array_values((array) ppcart_order_log($oneTimeId)));

        PPCart_Stripe_Sync::sync_invoice_resource(
            $this->invoice('in_cash_renewal', 'sub_cash_renewal', 'ch_cash_renewal', time()),
            new PPCart_Subscription($subscriptionId),
            'manual_sync',
            null,
            'USD',
            '',
            ppcart_stripe_client('sk_test_migrated_order_sync')
        );
        $this->assertSame('stripe', ppcart_get_post_meta($renewalId, 'pay_method', true));
        $this->assertSame('live', ppcart_get_post_meta($renewalId, 'gateway_mode', true));

        $this->assertSame('', PPCart_Stripe_Sync::get_order_sync_payment_id(new PPCart_Order($cashId)));
        $this->assertStringNotContainsString('ppcart_sync_order', $this->renderOrderScreen($cashId));
        try {
            PPCart_Stripe_Sync::sync_order_by_local(new PPCart_Order($cashId));
            $this->fail('An order with no Stripe payment ID must not sync.');
        } catch (\Exception $e) {
            $this->assertSame('Stripe payment ID is missing for this order.', $e->getMessage());
        }
        $this->assertSame('cod', ppcart_get_post_meta($cashId, 'pay_method', true));

        $this->assertSame(0, $this->integrationRuns);
        $this->assertSame(0, $this->mailsSent);
        $this->assertSame(array(), preg_grep('/^(?!GET )/', $stripe->requests), 'Sync must only read from Stripe.');
    }

    public function test_IT_473_order_sync_fills_in_the_missing_refund_record_of_a_refunded_one_time_order(): void
    {
        foreach (array('stripe', 'cod') as $payMethod) {
            $paymentIntentId = 'pi_refunded_' . $payMethod;
            $chargeId = 'ch_refunded_' . $payMethod;
            $orderId = $this->createStripeOrder(
                array(
                    'pay_method' => $payMethod,
                    'transaction_id' => $paymentIntentId,
                    'status' => 'refunded',
                    'payment_status' => 'refunded',
                    'amount' => 20,
                    'refund_log' => '',
                )
            );
            $stripe = (new MigratedOrderSyncFakeStripe())->addCharge(
                array(
                    'id' => $chargeId,
                    'payment_intent' => $paymentIntentId,
                    'amount_refunded' => 2000,
                    'refunded' => true,
                    'refunds' => array(
                        'object' => 'list',
                        'has_more' => false,
                        'data' => array(
                            array('object' => 'refund', 'id' => 're_' . $payMethod, 'amount' => 2000, 'created' => 1717243200, 'charge' => $chargeId),
                        ),
                    ),
                )
            );
            ApiRequestor::setHttpClient($stripe);

            $this->assertStringContainsString('Sync with Stripe', $this->renderOrderScreen($orderId), $payMethod);
            PPCart_Stripe_Sync::sync_order_by_local(new PPCart_Order($orderId));
            // A second sync must not add the refund again.
            PPCart_Stripe_Sync::sync_order_by_local(new PPCart_Order($orderId));

            $log = ppcart_get_post_meta($orderId, 'refund_log', true);
            $this->assertSame(
                array('re_' . $payMethod => array('refundID' => 're_' . $payMethod, 'date' => '2024-06-01 12:00', 'amount' => '20.00')),
                $log,
                $payMethod
            );
            $this->assertEquals(20, (float) ppcart_get_post_meta($orderId, 'refund_amount', true), $payMethod);
            $this->assertEquals(20, \PPCart_Order_Refunds::get_refund_total($orderId), $payMethod);
            $this->assertSame('refunded', ppcart_get_post_meta($orderId, 'status', true), $payMethod);
            $this->assertSame($chargeId, ppcart_get_post_meta($orderId, 'transaction_id', true), $payMethod);
            $this->assertSame('stripe', ppcart_get_post_meta($orderId, 'pay_method', true), $payMethod);
            $this->assertSame(array(), preg_grep('/^(?!GET )/', $stripe->requests), 'Sync must only read from Stripe.');
        }

        $this->assertSame(0, $this->integrationRuns);
        $this->assertSame(0, $this->mailsSent);
    }

    public function test_IT_474_subscription_sync_fills_in_the_missing_refund_record_of_a_refunded_renewal(): void
    {
        $subscriptionId = $this->createStripeSubscription(array('subscription_id' => 'sub_refunded_renewal'));
        $missingId = $this->createStripeOrder(
            array(
                'transaction_id' => 'pi_refunded_renewal',
                'status' => 'refunded',
                'payment_status' => 'refunded',
                'amount' => 20,
                'subscription_id' => $subscriptionId,
                'refund_log' => '',
            )
        );
        $existingLog = array('re_recorded' => array('refundID' => 're_recorded', 'date' => '2024-06-02 08:00', 'amount' => '20.00'));
        $recordedId = $this->createStripeOrder(
            array(
                'transaction_id' => 'ch_recorded_renewal',
                'status' => 'refunded',
                'payment_status' => 'refunded',
                'amount' => 20,
                'subscription_id' => $subscriptionId,
                'refund_log' => $existingLog,
                'refund_amount' => '20.00',
            )
        );
        $refunded = function (string $chargeId, string $refundId, ?string $paymentIntent = null): array {
            return array(
                'id' => $chargeId,
                'payment_intent' => $paymentIntent,
                'amount_refunded' => 2000,
                'refunded' => true,
                'refunds' => array(
                    'object' => 'list',
                    'has_more' => false,
                    'data' => array(
                        array('object' => 'refund', 'id' => $refundId, 'amount' => 2000, 'created' => 1717243200, 'charge' => $chargeId),
                    ),
                ),
            );
        };
        $stripe = (new MigratedOrderSyncFakeStripe())
            ->addCharge($refunded('ch_refunded_renewal', 're_renewal', 'pi_refunded_renewal'))
            ->addCharge($refunded('ch_recorded_renewal', 're_recorded'));
        ApiRequestor::setHttpClient($stripe);
        $client = ppcart_stripe_client('sk_test_migrated_order_sync');

        foreach (array(array('in_refunded_renewal', 'ch_refunded_renewal', 'pi_refunded_renewal'), array('in_recorded_renewal', 'ch_recorded_renewal', '')) as $args) {
            $invoice = $this->invoice($args[0], 'sub_refunded_renewal', $args[1], time());
            $invoice->payment_intent = $args[2];
            for ($run = 0; $run < 2; $run++) {
                PPCart_Stripe_Sync::sync_invoice_resource($invoice, new PPCart_Subscription($subscriptionId), 'manual_sync', null, 'USD', '', $client);
            }
        }

        $this->assertSame(
            array('re_renewal' => array('refundID' => 're_renewal', 'date' => '2024-06-01 12:00', 'amount' => '20.00')),
            ppcart_get_post_meta($missingId, 'refund_log', true)
        );
        $this->assertEquals(20, (float) ppcart_get_post_meta($missingId, 'refund_amount', true));
        $this->assertSame('refunded', ppcart_get_post_meta($missingId, 'status', true));
        $this->assertSame('ch_refunded_renewal', ppcart_get_post_meta($missingId, 'transaction_id', true));

        // A refund that already has a record is not added again or changed.
        $this->assertSame($existingLog, ppcart_get_post_meta($recordedId, 'refund_log', true));
        $this->assertEquals(20, \PPCart_Order_Refunds::get_refund_total($recordedId));

        $this->assertSame(0, $this->integrationRuns);
        $this->assertSame(0, $this->mailsSent);
        $this->assertSame(array(), preg_grep('/^(?!GET )/', $stripe->requests), 'Sync must only read from Stripe.');
    }

    public function test_IT_479_sync_replaces_a_matching_manual_refund_with_the_stripe_refund(): void
    {
        $manualLog = array('manual' => array('refundID' => 'manual', 'date' => '2026-11-19 23:21', 'amount' => '39.00'));
        // A one-time order, synced from the order screen.
        $oneTimeId = $this->createRefundedOrder('pi_manual_one_time', $manualLog);
        // A subscription order, synced from the subscription.
        $subscriptionId = $this->createStripeSubscription(array('subscription_id' => 'sub_manual_renewal'));
        $renewalId = $this->createRefundedOrder('pi_manual_renewal', $manualLog, $subscriptionId);
        $stripe = (new MigratedOrderSyncFakeStripe())
            ->addCharge($this->fullyRefundedCharge('ch_manual_one_time', 'pi_manual_one_time', 're_manual_one_time'))
            ->addCharge($this->fullyRefundedCharge('ch_manual_renewal', 'pi_manual_renewal', 're_manual_renewal'));
        ApiRequestor::setHttpClient($stripe);

        for ($run = 0; $run < 2; $run++) {
            PPCart_Stripe_Sync::sync_order_by_local(new PPCart_Order($oneTimeId));
            $invoice = $this->invoice('in_manual_renewal', 'sub_manual_renewal', 'ch_manual_renewal', time());
            $invoice->amount_paid = 3900;
            $invoice->payment_intent = 'pi_manual_renewal';
            PPCart_Stripe_Sync::sync_invoice_resource($invoice, new PPCart_Subscription($subscriptionId), 'manual_sync', null, 'USD', '', ppcart_stripe_client('sk_test_migrated_order_sync'));
        }

        foreach (array($oneTimeId => 're_manual_one_time', $renewalId => 're_manual_renewal') as $orderId => $refundId) {
            $this->assertSame(
                array($refundId => array('refundID' => $refundId, 'date' => '2024-06-01 12:00', 'amount' => '39.00')),
                ppcart_get_post_meta($orderId, 'refund_log', true),
                $refundId
            );
            $this->assertEquals(39, (float) ppcart_get_post_meta($orderId, 'refund_amount', true), $refundId);
            $this->assertEquals(39, \PPCart_Order_Refunds::get_refund_total($orderId), $refundId);
            $this->assertSame('refunded', ppcart_get_post_meta($orderId, 'status', true), $refundId);
            $notes = array_values((array) ppcart_order_log($orderId));
            $this->assertSame(1, count(array_keys($notes, 'Manual refund of 39.00 matched to Stripe refund ' . $refundId . '.', true)), $refundId);
        }

        $this->assertSame(0, $this->integrationRuns);
        $this->assertSame(0, $this->mailsSent);
        $this->assertSame(array(), preg_grep('/^(?!GET )/', $stripe->requests), 'Sync must only read from Stripe.');
    }

    public function test_IT_480_sync_leaves_a_manual_refund_that_does_not_match_stripe_alone(): void
    {
        $manualLog = array('manual' => array('refundID' => 'manual', 'date' => '2026-11-19 23:21', 'amount' => '20.00'));
        $oneTimeId = $this->createRefundedOrder('pi_manual_mismatch', $manualLog);
        $subscriptionId = $this->createStripeSubscription(array('subscription_id' => 'sub_manual_mismatch'));
        $renewalId = $this->createRefundedOrder('pi_manual_mismatch_renewal', $manualLog, $subscriptionId);
        // Stripe partly refunded each charge, by a different amount than the manual refund.
        $stripe = (new MigratedOrderSyncFakeStripe())
            ->addCharge($this->chargeWithRefunds('ch_manual_mismatch', 'pi_manual_mismatch', array('re_manual_mismatch' => 1000)))
            ->addCharge($this->chargeWithRefunds('ch_manual_mismatch_renewal', 'pi_manual_mismatch_renewal', array('re_manual_mismatch_renewal' => 1000)));
        ApiRequestor::setHttpClient($stripe);

        for ($run = 0; $run < 2; $run++) {
            $this->assertSame('Order synced. The refund needs a check: see the order log.', PPCart_Stripe_Sync::sync_order_by_local(new PPCart_Order($oneTimeId)));
            $invoice = $this->invoice('in_manual_mismatch', 'sub_manual_mismatch', 'ch_manual_mismatch_renewal', time());
            $invoice->amount_paid = 3900;
            $invoice->payment_intent = 'pi_manual_mismatch_renewal';
            PPCart_Stripe_Sync::sync_invoice_resource($invoice, new PPCart_Subscription($subscriptionId), 'manual_sync', null, 'USD', '', ppcart_stripe_client('sk_test_migrated_order_sync'));
        }

        foreach (array($oneTimeId => 're_manual_mismatch', $renewalId => 're_manual_mismatch_renewal') as $orderId => $refundId) {
            $this->assertSame($manualLog, ppcart_get_post_meta($orderId, 'refund_log', true), $refundId);
            $this->assertSame('20.00', ppcart_get_post_meta($orderId, 'refund_amount', true), $refundId);
            $this->assertSame('refunded', ppcart_get_post_meta($orderId, 'status', true), $refundId);
            $this->assertSame('stripe', ppcart_get_post_meta($orderId, 'pay_method', true), $refundId);
            $notes = array_values((array) ppcart_order_log($orderId));
            $note = 'Stripe refund ' . $refundId . ' (10.00) was not recorded because the manual refund total (20.00) is different. Check the refunds in Stripe and update this order.';
            $this->assertSame(1, count(array_keys($notes, $note, true)), $refundId);
        }

        $this->assertSame(0, $this->integrationRuns);
        $this->assertSame(0, $this->mailsSent);
        $this->assertSame(array(), preg_grep('/^(?!GET )/', $stripe->requests), 'Sync must only read from Stripe.');
    }

    public function test_IT_483_a_full_stripe_refund_replaces_a_manual_refund_that_does_not_match(): void
    {
        $manualLog = array('manual' => array('refundID' => 'manual', 'date' => '2026-11-19 23:21', 'amount' => '10.00'));
        $refunds = array('re_a' => 1000, 're_b' => 2900);
        // A migrated Cash order, synced from the order screen.
        $cashId = $this->createStripeOrder(
            array('pay_method' => 'cod', 'transaction_id' => 'pi_full_cash', 'status' => 'paid', 'payment_status' => 'paid', 'amount' => 39, 'refund_log' => $manualLog, 'refund_amount' => '10.00')
        );
        $stripe = (new MigratedOrderSyncFakeStripe())
            ->addCharge($this->chargeWithRefunds('ch_full_cash', 'pi_full_cash', $refunds));
        ApiRequestor::setHttpClient($stripe);

        PPCart_Stripe_Sync::sync_order_by_local(new PPCart_Order($cashId));

        $this->assertSame('refunded', ppcart_get_post_meta($cashId, 'status', true));
        $this->assertSame(array('re_a', 're_b'), array_keys(ppcart_get_post_meta($cashId, 'refund_log', true)));
        $this->assertSame('39.00', ppcart_get_post_meta($cashId, 'refund_amount', true));
        $this->assertEquals(39, \PPCart_Order_Refunds::get_refund_total($cashId));
        $this->assertContains('Manual refund of 10.00 replaced by Stripe refund re_a, re_b, because Stripe fully refunded this payment.', array_values((array) ppcart_order_log($cashId)));
        // The first sync of a Cash order runs no integrations.
        $this->assertSame(0, $this->integrationRuns);

        // A Stripe order gets the charge.refunded webhook, which marks it refunded as before.
        $stripeOrderId = $this->createStripeOrder(
            array('transaction_id' => 'ch_full_webhook', 'status' => 'paid', 'payment_status' => 'paid', 'amount' => 39, 'refund_log' => $manualLog, 'refund_amount' => '10.00')
        );
        $charge = $this->chargeWithRefunds('ch_full_webhook', 'pi_full_webhook', array('re_c' => 1000, 're_d' => 2900));
        $stripe->addCharge($charge);
        PPCart_Stripe_Sync::sync_charge_refund((object) array('id' => 'ch_full_webhook'), ppcart_stripe_client('sk_test_migrated_order_sync'), null, true);

        $this->assertSame('refunded', ppcart_get_post_meta($stripeOrderId, 'status', true));
        $this->assertSame(array('re_c', 're_d'), array_keys(ppcart_get_post_meta($stripeOrderId, 'refund_log', true)));
        $this->assertSame('39.00', ppcart_get_post_meta($stripeOrderId, 'refund_amount', true));
        $this->assertSame(1, $this->integrationRuns, 'A Stripe order that turns refunded runs its refund integrations.');
        $this->assertSame(array(), preg_grep('/^(?!GET )/', $stripe->requests), 'Sync must only read from Stripe.');
    }

    public function test_IT_484_the_first_sync_of_a_completed_cash_order_keeps_its_status(): void
    {
        $orderId = $this->createStripeOrder(array('pay_method' => 'cod', 'transaction_id' => 'ch_completed_cash', 'status' => 'completed', 'payment_status' => 'paid'));
        $stripe = (new MigratedOrderSyncFakeStripe())->addCharge(array('id' => 'ch_completed_cash'));
        ApiRequestor::setHttpClient($stripe);

        for ($run = 0; $run < 2; $run++) {
            $this->assertSame('Order synced successfully.', PPCart_Stripe_Sync::sync_order_by_local(new PPCart_Order($orderId)));
        }

        $this->assertSame('completed', ppcart_get_post_meta($orderId, 'status', true));
        $this->assertSame('stripe', ppcart_get_post_meta($orderId, 'pay_method', true));
        $this->assertSame(0, $this->integrationRuns);
    }

    public function test_IT_485_the_first_sync_of_a_refunded_cash_order_with_a_partial_stripe_refund_keeps_it_refunded(): void
    {
        $orderId = $this->createRefundedOrder('pi_partial_cash', array());
        $stripe = (new MigratedOrderSyncFakeStripe())->addCharge($this->chargeWithRefunds('ch_partial_cash', 'pi_partial_cash', array('re_partial_cash' => 1000)));
        ApiRequestor::setHttpClient($stripe);

        PPCart_Stripe_Sync::sync_order_by_local(new PPCart_Order($orderId));

        $this->assertSame('refunded', ppcart_get_post_meta($orderId, 'status', true));
        $this->assertSame(array('re_partial_cash'), array_keys(ppcart_get_post_meta($orderId, 'refund_log', true)));
        $this->assertSame('10.00', ppcart_get_post_meta($orderId, 'refund_amount', true));
        $this->assertSame('stripe', ppcart_get_post_meta($orderId, 'pay_method', true));
        $this->assertSame(0, $this->integrationRuns);
    }

    public function test_IT_486_a_failed_stripe_charge_never_marks_an_order_paid(): void
    {
        $orderId = $this->createStripeOrder(array('pay_method' => 'cod', 'transaction_id' => 'pi_failed_cash', 'status' => 'pending-payment', 'payment_status' => 'pending-payment'));
        $stripe = (new MigratedOrderSyncFakeStripe())->addCharge(
            array('id' => 'ch_failed_cash', 'payment_intent' => 'pi_failed_cash', 'status' => 'failed', 'paid' => false)
        );
        ApiRequestor::setHttpClient($stripe);

        try {
            PPCart_Stripe_Sync::sync_order_by_local(new PPCart_Order($orderId));
            $this->fail('A failed charge must not sync.');
        } catch (\Exception $e) {
            $this->assertSame('The Stripe payment for this order has not succeeded, so the order was not changed.', $e->getMessage());
        }
        $failed = (object) array('id' => 'ch_failed_cash', 'payment_intent' => 'pi_failed_cash', 'status' => 'failed', 'paid' => false, 'amount' => 2000, 'metadata' => (object) array());
        $this->assertFalse(PPCart_Stripe_Sync::sync_charge_status($failed, null, null, true));

        $this->assertSame('pending-payment', ppcart_get_post_meta($orderId, 'status', true));
        $this->assertSame('pi_failed_cash', ppcart_get_post_meta($orderId, 'transaction_id', true));
        $this->assertSame('cod', ppcart_get_post_meta($orderId, 'pay_method', true));
        $this->assertSame(0, $this->integrationRuns);
    }

    public function test_IT_487_manual_refunds_match_with_a_decimal_comma_and_thousand_dot(): void
    {
        update_option('_ppcart_decimal_separator', ',');
        update_option('_ppcart_thousand_separator', '.');
        try {
            $manualLog = array('manual' => array('refundID' => 'manual', 'date' => '2026-11-19 23:21', 'amount' => '1.039,00'));
            $orderId = $this->createRefundedOrder('pi_comma', $manualLog);
            $charge = $this->chargeWithRefunds('ch_comma', 'pi_comma', array('re_comma' => 103900));
            $charge['amount'] = 103900;
            $stripe = (new MigratedOrderSyncFakeStripe())->addCharge($charge);
            ApiRequestor::setHttpClient($stripe);

            PPCart_Stripe_Sync::sync_order_by_local(new PPCart_Order($orderId));

            $log = ppcart_get_post_meta($orderId, 'refund_log', true);
            $this->assertSame(array('re_comma'), array_keys($log));
            $this->assertSame('1.039,00', $log['re_comma']['amount']);
            $this->assertSame('1.039,00', ppcart_get_post_meta($orderId, 'refund_amount', true));
            $this->assertContains('Manual refund of 1.039,00 matched to Stripe refund re_comma.', array_values((array) ppcart_order_log($orderId)));
            $this->assertSame(103900, PPCart_Stripe_Sync::amount_to_minor_units('1.039,00', 'USD'));
            $this->assertSame(3900, PPCart_Stripe_Sync::amount_to_minor_units('39,00', 'USD'));
            $this->assertSame(3900, PPCart_Stripe_Sync::amount_to_minor_units('39.00', 'USD'));
        } finally {
            delete_option('_ppcart_decimal_separator');
            delete_option('_ppcart_thousand_separator');
        }
    }

    public function test_IT_488_failed_and_canceled_refunds_are_left_out(): void
    {
        $manualLog = array('manual' => array('refundID' => 'manual', 'date' => '2026-11-19 23:21', 'amount' => '39.00'));
        $orderId = $this->createRefundedOrder('pi_failed_refunds', $manualLog);
        $charge = $this->chargeWithRefunds('ch_failed_refunds', 'pi_failed_refunds', array('re_ok' => 3900, 're_failed' => 3900, 're_canceled' => 3900));
        $charge['amount_refunded'] = 3900;
        $charge['refunds']['data'][1]['status'] = 'failed';
        $charge['refunds']['data'][2]['status'] = 'canceled';
        $stripe = (new MigratedOrderSyncFakeStripe())->addCharge($charge);
        ApiRequestor::setHttpClient($stripe);

        PPCart_Stripe_Sync::sync_order_by_local(new PPCart_Order($orderId));

        $this->assertSame(array('re_ok'), array_keys(ppcart_get_post_meta($orderId, 'refund_log', true)));
        $this->assertSame('39.00', ppcart_get_post_meta($orderId, 'refund_amount', true));
        $this->assertContains('Manual refund of 39.00 matched to Stripe refund re_ok.', array_values((array) ppcart_order_log($orderId)));
        $this->assertSame(0, $this->integrationRuns);
    }

    /**
     * @param array<string, int> $refunds Refund amounts in cents, by refund ID.
     * @return array<string, mixed>
     */
    private function chargeWithRefunds(string $chargeId, string $paymentIntentId, array $refunds): array
    {
        $data = array();
        foreach ($refunds as $refundId => $cents) {
            $data[] = array('object' => 'refund', 'id' => $refundId, 'amount' => $cents, 'status' => 'succeeded', 'created' => 1717243200, 'charge' => $chargeId);
        }
        $refunded = array_sum($refunds);

        return array(
            'id' => $chargeId,
            'payment_intent' => $paymentIntentId,
            'amount' => 3900,
            'amount_refunded' => $refunded,
            'refunded' => $refunded >= 3900,
            'refunds' => array('object' => 'list', 'has_more' => false, 'data' => $data),
        );
    }

    /**
     * @param array<string, array<string, string>> $refundLog
     */
    private function createRefundedOrder(string $paymentIntentId, array $refundLog, int $subscriptionId = 0): int
    {
        $meta = array(
            'pay_method' => 'cod',
            'transaction_id' => $paymentIntentId,
            'status' => 'refunded',
            'payment_status' => 'refunded',
            'amount' => 39,
            'refund_log' => $refundLog,
            'refund_amount' => $refundLog ? $refundLog['manual']['amount'] : '',
        );
        if ($subscriptionId) {
            $meta['subscription_id'] = $subscriptionId;
        }

        return $this->createStripeOrder($meta);
    }

    /**
     * @return array<string, mixed>
     */
    private function fullyRefundedCharge(string $chargeId, string $paymentIntentId, string $refundId): array
    {
        return array(
            'id' => $chargeId,
            'payment_intent' => $paymentIntentId,
            'amount' => 3900,
            'amount_refunded' => 3900,
            'refunded' => true,
            'refunds' => array(
                'object' => 'list',
                'has_more' => false,
                'data' => array(
                    array('object' => 'refund', 'id' => $refundId, 'amount' => 3900, 'created' => 1717243200, 'charge' => $chargeId),
                ),
            ),
        );
    }

    private function invoice(string $invoiceId, string $stripeSubscriptionId, string $chargeId, int $paidAt): object
    {
        return (object) array(
            'id' => $invoiceId,
            'livemode' => true,
            'subscription' => $stripeSubscriptionId,
            'charge' => $chargeId,
            'payment_intent' => '',
            'amount_paid' => 2000,
            'currency' => 'usd',
            'status' => 'paid',
            'status_transitions' => (object) array(
                'paid_at' => $paidAt,
                'finalized_at' => $paidAt,
            ),
            'lines' => (object) array(
                'data' => array(
                    (object) array(
                        'subscription_item' => 'si_' . $invoiceId,
                        'metadata' => (object) array(
                            'ppcart_product_id' => 123,
                        ),
                        'period' => (object) array(
                            'end' => $paidAt + MONTH_IN_SECONDS,
                        ),
                    ),
                ),
            ),
        );
    }

    private function renderOrderScreen(int $orderId): string
    {
        $post = get_post($orderId);
        $this->assertInstanceOf(\WP_Post::class, $post);
        $_GET['post'] = (string) $orderId;

        $controller = (new ReflectionClass(PPCart_Admin_Order_Controller::class))->newInstanceWithoutConstructor();

        ob_start();
        try {
            $controller->product_info_callback($post);
        } finally {
            $html = (string) ob_get_clean();
            unset($_GET['post']);
        }

        return $html;
    }
}
