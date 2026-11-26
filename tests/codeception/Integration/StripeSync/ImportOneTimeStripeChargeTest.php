<?php

declare(strict_types=1);

namespace Tests\Integration\StripeSync;

use PPCart_Activator;
use PPCart_Admin_Stripe_Import;
use PPCart_Order;
use PPCart_Stripe_Sync;
use PublishPress\Stripe\ApiRequestor;
use PublishPress\Stripe\HttpClient\ClientInterface;
use Tests\Support\Integration\StripeSyncTestCase;

/**
 * Serves Stripe charges, PaymentIntents and customers. Refuses every write.
 */
class ImportOneTimeStripeChargeFakeStripe implements ClientInterface
{
    /** @var array<string, array<string, mixed>> */
    private $charges = array();

    /** @var array<string, string> Invoice ID by the PaymentIntent that paid it. */
    private $invoicePayments = array();

    /** @var bool */
    public $failInvoicePayments = false;

    /** @var array<string, array<string, mixed>> */
    private $customers = array();

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
                'captured' => true,
                'currency' => 'usd',
                'amount' => 3900,
                'amount_refunded' => 0,
                'created' => 1706832000,
                'livemode' => false,
                'payment_intent' => null,
                'customer' => null,
                'receipt_email' => null,
                'billing_details' => array('email' => null, 'name' => null, 'phone' => null, 'address' => null),
                'metadata' => array(),
                'refunds' => array('object' => 'list', 'data' => array(), 'has_more' => false),
            ),
            $charge
        );
        if (! array_key_exists('amount_captured', $charge)) {
            $this->charges[$charge['id']]['amount_captured'] = $this->charges[$charge['id']]['amount'];
        }

        return $this;
    }

    /**
     * @param array<string, mixed> $customer
     */
    public function addCustomer(array $customer): self
    {
        $this->customers[$customer['id']] = array_merge(array('object' => 'customer'), $customer);

        return $this;
    }

    /**
     * Record that a PaymentIntent paid an invoice. Charges don't carry the
     * invoice ID, as on the Stripe API version the SDK pins.
     */
    public function addInvoicePayment(string $paymentIntentId, string $invoiceId): self
    {
        $this->invoicePayments[$paymentIntentId] = $invoiceId;

        return $this;
    }

    public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null)
    {
        $this->requests[] = strtoupper((string) $method) . ' ' . (string) parse_url($absUrl, PHP_URL_PATH);

        if ('get' !== strtolower((string) $method)) {
            return $this->error('The import must not write to Stripe: ' . $method . ' ' . $absUrl, 500);
        }

        $path = (string) parse_url($absUrl, PHP_URL_PATH);

        if ('/v1/invoice_payments' === $path) {
            if ($this->failInvoicePayments) {
                return $this->error('Invoice payments are unavailable', 500);
            }
            $query = array();
            parse_str((string) parse_url($absUrl, PHP_URL_QUERY), $query);
            $params = array_merge($query, is_array($params) ? $params : array());
            $intentId = isset($params['payment']['payment_intent']) ? (string) $params['payment']['payment_intent'] : '';
            $data = array();
            if ('' !== $intentId && isset($this->invoicePayments[$intentId])) {
                $data[] = array(
                    'object' => 'invoice_payment',
                    'id' => 'inpay_' . $intentId,
                    'invoice' => $this->invoicePayments[$intentId],
                    'payment' => array('type' => 'payment_intent', 'payment_intent' => $intentId),
                );
            }
            return array(wp_json_encode(array('object' => 'list', 'url' => '/v1/invoice_payments', 'has_more' => false, 'data' => $data)), 200, array());
        }

        if (0 === strpos($path, '/v1/charges/')) {
            $id = substr($path, strlen('/v1/charges/'));
            return isset($this->charges[$id]) ? array(wp_json_encode($this->charges[$id]), 200, array()) : $this->error('No such charge', 404);
        }

        if (0 === strpos($path, '/v1/customers/')) {
            $id = substr($path, strlen('/v1/customers/'));
            return isset($this->customers[$id]) ? array(wp_json_encode($this->customers[$id]), 200, array()) : $this->error('No such customer', 404);
        }

        if (0 === strpos($path, '/v1/payment_intents/')) {
            $id = substr($path, strlen('/v1/payment_intents/'));
            foreach ($this->charges as $charge) {
                if ($id === $charge['payment_intent']) {
                    return array(
                        wp_json_encode(array('object' => 'payment_intent', 'id' => $id, 'status' => 'succeeded', 'latest_charge' => $charge['id'])),
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

class ImportOneTimeStripeChargeTest extends StripeSyncTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    protected function setUp(): void
    {
        parent::setUp();
        update_option('_ppcart_stripe_api', 'test');
        update_option('_ppcart_stripe_test_sk', 'sk_test_import_one_time_charge');
    }

    protected function tearDown(): void
    {
        ApiRequestor::setHttpClient(null);
        delete_option('_ppcart_stripe_test_sk');
        parent::tearDown();
    }

    public function test_IT_460_importing_a_stripe_payment_as_a_one_time_order_of_a_product(): void
    {
        $productId = $this->createProduct();
        $userId = $this->factory()->user->create(array('user_email' => 'buyer@example.test'));
        $stripe = (new ImportOneTimeStripeChargeFakeStripe())
            ->addCustomer(array('id' => 'cus_one_time', 'email' => 'other@example.test', 'name' => 'Other Name'))
            ->addCharge(
                array(
                    'id' => 'ch_one_time_1',
                    'customer' => 'cus_one_time',
                    'payment_intent' => 'pi_one_time_1',
                    'billing_details' => array(
                        'email' => 'Buyer@Example.test',
                        'name' => 'Ada Lovelace',
                        'phone' => '+15550100',
                        'address' => array('line1' => '1 Main St', 'line2' => 'Suite 2', 'city' => 'Springfield', 'state' => 'IL', 'postal_code' => '62701', 'country' => 'US'),
                    ),
                )
            );
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
            $order = PPCart_Stripe_Sync::import_charge('pi_one_time_1', 0, $productId);
        } finally {
            remove_filter('ppcart_trigger_order_integrations', $countRuns, PHP_INT_MAX);
        }

        $this->assertInstanceOf(PPCart_Order::class, $order);
        $this->createdPostIds[] = (int) $order->id;
        $this->assertSame('ch_one_time_1', $order->transaction_id);
        $this->assertEquals(39, (float) $order->amount);
        $this->assertEquals(39, (float) $order->invoice_total);
        $this->assertSame('USD', $order->currency);
        $this->assertSame('paid', $order->status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame(gmdate('Y-m-d H:i:s', 1706832000), (string) get_post_field('post_date_gmt', $order->id));
        $this->assertSame('stripe', $order->pay_method);
        $this->assertSame('test', $order->gateway_mode);
        $this->assertEmpty($order->subscription_id);
        $this->assertSame($productId, (int) $order->product_id);
        $this->assertSame('One-time plan', $order->item_name);
        $this->assertSame('single', $order->option_id);
        $this->assertSame('buyer@example.test', $order->email);
        $this->assertSame('Ada', $order->first_name);
        $this->assertSame('Lovelace', $order->last_name);
        $this->assertSame('Ada Lovelace', $order->customer_name);
        $this->assertSame('cus_one_time', $order->customer_id);
        $this->assertSame('+15550100', $order->phone);
        $this->assertSame(array('1 Main St', 'Suite 2', 'Springfield', 'IL', '62701', 'US'), array($order->address1, $order->address2, $order->city, $order->state, $order->zip, $order->country));
        $this->assertSame($userId, (int) $order->user_account);

        $items = $order->get_items();
        $this->assertCount(1, $items);
        $this->assertSame(array('main', $productId, 'single', 39.0, 1), array($items[0]->item_type, (int) $items[0]->product_id, $items[0]->price_id, (float) $items[0]->total_amount, (int) $items[0]->quantity));

        $this->assertSame(0, $integrationRuns, 'Order integrations must not run for an imported payment.');
        $this->assertSame(array(), preg_grep('/^(?!GET )/', $stripe->requests), 'The import must only read from Stripe.');
        $this->assertSame(false, get_user_by('email', 'other@example.test'), 'The import must not create users.');
    }

    public function test_IT_461_importing_a_payment_needs_exactly_one_of_a_subscription_and_a_product(): void
    {
        $productId = $this->createProduct();
        $subscriptionId = $this->createStripeSubscriptionWithOrder(array('customer_id' => 'cus_one_time'));
        $stripe = (new ImportOneTimeStripeChargeFakeStripe())->addCharge(array('id' => 'ch_one_time_target', 'receipt_email' => 'buyer@example.test'));
        ApiRequestor::setHttpClient($stripe);

        $both = PPCart_Stripe_Sync::import_charge('ch_one_time_target', $subscriptionId, $productId);
        $this->assertWPError($both);
        $this->assertSame('ppcart_import_charge_target', $both->get_error_code());

        $neither = PPCart_Stripe_Sync::import_charge('ch_one_time_target', 0, 0);
        $this->assertWPError($neither);
        $this->assertSame('ppcart_import_charge_target', $neither->get_error_code());

        $this->actAsCartManager();

        $ajaxBoth = $this->requestChargeImport(array('payment_id' => 'ch_one_time_target', 'subscription_id' => (string) $subscriptionId, 'product_id' => (string) $productId));
        $this->assertFalse($ajaxBoth['success']);
        $this->assertSame('Enter a subscription ID or choose a product, not both.', $ajaxBoth['data']['message']);

        $ajaxNeither = $this->requestChargeImport(array('payment_id' => 'ch_one_time_target', 'subscription_id' => '', 'product_id' => '0'));
        $this->assertFalse($ajaxNeither['success']);
        $this->assertSame('Enter a subscription ID or choose a product.', $ajaxNeither['data']['message']);

        $this->assertFalse(PPCart_Order::get_by_trans_id('ch_one_time_target'));
        $this->assertSame(array(), $stripe->requests, 'A refused import must not call Stripe.');
    }

    public function test_IT_462_importing_an_invoice_payment_as_a_one_time_order_is_refused(): void
    {
        $productId = $this->createProduct();
        $stripe = (new ImportOneTimeStripeChargeFakeStripe())
            // Like the Stripe API version the SDK pins, the charge has no
            // invoice field. Only invoice_payments links it to the invoice.
            ->addCharge(array('id' => 'ch_one_time_invoice', 'payment_intent' => 'pi_one_time_invoice', 'receipt_email' => 'buyer@example.test'))
            ->addInvoicePayment('pi_one_time_invoice', 'in_renewal')
            ->addCharge(array('id' => 'ch_one_time_lookup_fails', 'payment_intent' => 'pi_one_time_lookup_fails', 'receipt_email' => 'buyer@example.test'))
            ->addCharge(array('id' => 'ch_one_time_no_intent', 'receipt_email' => 'buyer@example.test'));
        ApiRequestor::setHttpClient($stripe);

        $result = PPCart_Stripe_Sync::import_charge('ch_one_time_invoice', 0, $productId);
        $this->assertWPError($result);
        $this->assertSame('ppcart_import_charge_invoice', $result->get_error_code());
        $this->assertFalse(PPCart_Order::get_by_trans_id('ch_one_time_invoice'));
        $this->assertContains('GET /v1/invoice_payments', $stripe->requests);

        // When Stripe can't say whether the payment paid an invoice, nothing is imported.
        $stripe->failInvoicePayments = true;
        $lookupFailed = PPCart_Stripe_Sync::import_charge('ch_one_time_lookup_fails', 0, $productId);
        $this->assertWPError($lookupFailed);
        $this->assertSame('ppcart_import_charge_invoice_lookup', $lookupFailed->get_error_code());
        $this->assertFalse(PPCart_Order::get_by_trans_id('ch_one_time_lookup_fails'));

        // A charge with no PaymentIntent skips the lookup.
        $stripe->requests = array();
        $noIntent = PPCart_Stripe_Sync::import_charge('ch_one_time_no_intent', 0, $productId);
        $this->assertInstanceOf(PPCart_Order::class, $noIntent);
        $this->createdPostIds[] = (int) $noIntent->id;
        $this->assertNotContains('GET /v1/invoice_payments', $stripe->requests);
    }

    public function test_IT_463_importing_a_payment_that_is_already_an_order_is_refused(): void
    {
        $productId = $this->createProduct();
        $checkoutOrderId = $this->createStripeOrder(array('transaction_id' => 'pi_one_time_checkout'));
        $stripe = (new ImportOneTimeStripeChargeFakeStripe())
            ->addCharge(array('id' => 'ch_one_time_dupe', 'payment_intent' => 'pi_one_time_dupe', 'receipt_email' => 'buyer@example.test'))
            ->addCharge(array('id' => 'ch_one_time_checkout', 'payment_intent' => 'pi_one_time_checkout', 'receipt_email' => 'buyer@example.test'));
        ApiRequestor::setHttpClient($stripe);

        $order = PPCart_Stripe_Sync::import_charge('ch_one_time_dupe', 0, $productId);
        $this->assertInstanceOf(PPCart_Order::class, $order);
        $this->createdPostIds[] = (int) $order->id;

        foreach (array('ch_one_time_dupe', 'pi_one_time_dupe') as $paymentId) {
            $again = PPCart_Stripe_Sync::import_charge($paymentId, 0, $productId);
            $this->assertWPError($again);
            $this->assertSame('ppcart_import_charge_exists', $again->get_error_code());
            $this->assertSame((int) $order->id, $again->get_error_data()['order_id']);
        }

        // A checkout order stores the PaymentIntent ID. Its charge is refused too.
        $checkoutCharge = PPCart_Stripe_Sync::import_charge('ch_one_time_checkout', 0, $productId);
        $this->assertWPError($checkoutCharge);
        $this->assertSame('ppcart_import_charge_exists', $checkoutCharge->get_error_code());
        $this->assertSame($checkoutOrderId, $checkoutCharge->get_error_data()['order_id']);
    }

    public function test_IT_464_a_fully_refunded_payment_imports_as_a_refunded_one_time_order(): void
    {
        $productId = $this->createProduct();
        ApiRequestor::setHttpClient(
            (new ImportOneTimeStripeChargeFakeStripe())->addCharge(
                array(
                    'id' => 'ch_one_time_refunded',
                    'receipt_email' => 'buyer@example.test',
                    'amount_refunded' => 3900,
                    'refunded' => true,
                    'refunds' => array(
                        'object' => 'list',
                        'has_more' => false,
                        'data' => array(
                            array('id' => 're_one_time_1', 'object' => 'refund', 'amount' => 3900, 'currency' => 'usd', 'status' => 'succeeded', 'created' => 1706918400, 'charge' => 'ch_one_time_refunded'),
                        ),
                    ),
                )
            )
        );

        $order = PPCart_Stripe_Sync::import_charge('ch_one_time_refunded', 0, $productId);

        $this->assertInstanceOf(PPCart_Order::class, $order);
        $this->createdPostIds[] = (int) $order->id;
        $this->assertSame('refunded', $order->status);
        $this->assertSame('refunded', $order->payment_status);
        $this->assertEquals(39, (float) $order->amount);
        $this->assertEquals(39, (float) ppcart_get_post_meta($order->id, 'refund_amount', true));
        $refundLog = ppcart_get_post_meta($order->id, 'refund_log', true);
        $this->assertIsArray($refundLog);
        $this->assertNotEmpty($refundLog);
        $this->assertSame('re_one_time_1', $refundLog['re_one_time_1']['refundID']);
    }

    public function test_IT_465_one_time_import_refuses_a_non_product_and_a_payment_with_no_email(): void
    {
        $productId = $this->createProduct();
        $pageId = wp_insert_post(array('post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Not a product'));
        $this->createdPostIds[] = (int) $pageId;
        $stripe = (new ImportOneTimeStripeChargeFakeStripe())
            ->addCustomer(array('id' => 'cus_no_email', 'email' => null, 'name' => 'No Email'))
            ->addCustomer(array('id' => 'cus_has_email', 'email' => 'from-customer@example.test', 'name' => 'Grace Hopper'))
            ->addCharge(array('id' => 'ch_one_time_no_email', 'customer' => 'cus_no_email'))
            ->addCharge(array('id' => 'ch_one_time_customer_email', 'customer' => 'cus_has_email', 'amount' => 2500));
        ApiRequestor::setHttpClient($stripe);

        foreach (array((int) $pageId, 999999) as $notProduct) {
            $result = PPCart_Stripe_Sync::import_charge('ch_one_time_no_email', 0, $notProduct);
            $this->assertWPError($result);
            $this->assertSame('ppcart_import_charge_product', $result->get_error_code());
        }

        $noEmail = PPCart_Stripe_Sync::import_charge('ch_one_time_no_email', 0, $productId);
        $this->assertWPError($noEmail);
        $this->assertSame('ppcart_import_charge_email', $noEmail->get_error_code());
        $this->assertFalse(PPCart_Order::get_by_trans_id('ch_one_time_no_email'));

        // No billing details: the email and name come from the Stripe customer.
        $order = PPCart_Stripe_Sync::import_charge('ch_one_time_customer_email', 0, $productId);
        $this->assertInstanceOf(PPCart_Order::class, $order);
        $this->createdPostIds[] = (int) $order->id;
        $this->assertSame('from-customer@example.test', $order->email);
        $this->assertSame('Grace Hopper', $order->customer_name);
        $this->assertEquals(25, (float) $order->amount);
        $this->assertSame(0, (int) $order->user_account);
    }

    public function test_IT_467_one_time_import_refuses_an_uncaptured_payment_and_uses_the_captured_amount(): void
    {
        $productId = $this->createProduct();
        $stripe = (new ImportOneTimeStripeChargeFakeStripe())
            ->addCharge(array('id' => 'ch_one_time_uncaptured', 'receipt_email' => 'buyer@example.test', 'captured' => false, 'amount_captured' => 0))
            ->addCharge(array('id' => 'ch_one_time_partly_captured', 'receipt_email' => 'buyer@example.test', 'amount' => 5000, 'amount_captured' => 3900));
        ApiRequestor::setHttpClient($stripe);

        $uncaptured = PPCart_Stripe_Sync::import_charge('ch_one_time_uncaptured', 0, $productId);
        $this->assertWPError($uncaptured);
        $this->assertSame('ppcart_import_charge_uncaptured', $uncaptured->get_error_code());
        $this->assertFalse(PPCart_Order::get_by_trans_id('ch_one_time_uncaptured'));

        $order = PPCart_Stripe_Sync::import_charge('ch_one_time_partly_captured', 0, $productId);
        $this->assertInstanceOf(PPCart_Order::class, $order);
        $this->createdPostIds[] = (int) $order->id;
        $this->assertEquals(39, (float) $order->amount);
        $this->assertSame('paid', $order->status);
        $this->assertSame('single', $order->option_id);
    }

    public function test_IT_468_one_time_import_matches_the_plan_by_a_plain_amount(): void
    {
        // A thousand separator must not change the stored amount or the plan match.
        update_option('_ppcart_thousand_separator', ',');
        $productId = $this->createProduct(
            array(
                array('option_id' => 'basic', 'option_name' => 'Basic', 'product_type' => '', 'price' => '49'),
                array('option_id' => 'pro', 'option_name' => 'Pro', 'product_type' => 'one-time', 'price' => '1250'),
            )
        );
        $stripe = (new ImportOneTimeStripeChargeFakeStripe())
            ->addCharge(array('id' => 'ch_one_time_large', 'receipt_email' => 'buyer@example.test', 'amount' => 125000))
            ->addCharge(array('id' => 'ch_one_time_yen', 'receipt_email' => 'buyer@example.test', 'currency' => 'jpy', 'amount' => 49));
        ApiRequestor::setHttpClient($stripe);

        try {
            $large = PPCart_Stripe_Sync::import_charge('ch_one_time_large', 0, $productId);
            $yen = PPCart_Stripe_Sync::import_charge('ch_one_time_yen', 0, $productId);
        } finally {
            delete_option('_ppcart_thousand_separator');
        }

        $this->assertInstanceOf(PPCart_Order::class, $large);
        $this->createdPostIds[] = (int) $large->id;
        $this->assertSame('pro', $large->option_id);
        $this->assertSame('Pro', $large->item_name);
        $this->assertSame(1250.0, (float) $large->amount);
        $this->assertTrue(is_numeric(ppcart_get_post_meta($large->id, 'amount', true)), 'The stored amount must be a plain number.');
        $this->assertSame(1250.0, (float) ppcart_get_post_meta($large->id, 'amount', true));
        $items = $large->get_items();
        $this->assertSame(array('pro', 1250.0), array($items[0]->price_id, (float) $items[0]->total_amount));

        // JPY has no minor unit: 49 in Stripe is 49 yen, the Basic price.
        $this->assertInstanceOf(PPCart_Order::class, $yen);
        $this->createdPostIds[] = (int) $yen->id;
        $this->assertSame('JPY', $yen->currency);
        $this->assertSame(49.0, (float) $yen->amount);
        $this->assertSame('basic', $yen->option_id);
    }

    public function test_IT_469_one_time_import_refuses_a_product_with_no_one_time_plan(): void
    {
        $productId = $this->createProduct(
            array(
                array('option_id' => 'monthly', 'option_name' => 'Monthly', 'product_type' => 'recurring', 'price' => '39', 'interval' => 'month', 'installments' => '-1'),
            )
        );
        $stripe = (new ImportOneTimeStripeChargeFakeStripe())->addCharge(array('id' => 'ch_one_time_no_plan', 'receipt_email' => 'buyer@example.test'));
        ApiRequestor::setHttpClient($stripe);

        $result = PPCart_Stripe_Sync::import_charge('ch_one_time_no_plan', 0, $productId);

        $this->assertWPError($result);
        $this->assertSame('ppcart_import_charge_plan', $result->get_error_code());
        $this->assertSame('This product has no one-time payment plan. Add one, or choose another product.', $result->get_error_message());
        $this->assertFalse(PPCart_Order::get_by_trans_id('ch_one_time_no_plan'));
        $this->assertSame(array(), $stripe->requests, 'A refused import must not call Stripe.');
    }

    public function test_IT_470_the_ajax_import_creates_a_one_time_order_for_a_product(): void
    {
        $productId = $this->createProduct();
        ApiRequestor::setHttpClient(
            (new ImportOneTimeStripeChargeFakeStripe())->addCharge(array('id' => 'ch_one_time_ajax', 'payment_intent' => 'pi_one_time_ajax', 'receipt_email' => 'buyer@example.test'))
        );
        $this->actAsCartManager();

        $response = $this->requestChargeImport(array('payment_id' => ' pi_one_time_ajax ', 'subscription_id' => '', 'product_id' => (string) $productId));

        $this->assertTrue($response['success']);
        $order = PPCart_Order::get_by_trans_id('ch_one_time_ajax');
        $this->assertInstanceOf(PPCart_Order::class, $order);
        $this->createdPostIds[] = (int) $order->id;
        $this->assertSame('Imported as a one-time order.', $response['data']['message']);
        $this->assertSame('#' . (int) $order->id, $response['data']['label']);
        $this->assertSame($productId, (int) $order->product_id);
        $this->assertSame('paid', $order->status);
        $this->assertEmpty($order->subscription_id);
    }

    /**
     * @param array<int, array<string, string>>|null $plans
     */
    private function createProduct(?array $plans = null): int
    {
        $productId = wp_insert_post(
            array(
                'post_type' => ppcart_live_post_type('product'),
                'post_status' => 'publish',
                'post_title' => 'One-time Course',
            )
        );
        $this->createdPostIds[] = (int) $productId;
        ppcart_update_post_meta(
            (int) $productId,
            'pay_options',
            $plans ?? array(
                array('option_id' => 'monthly', 'option_name' => 'Monthly', 'product_type' => 'recurring', 'price' => '39', 'interval' => 'month', 'installments' => '-1'),
                array('option_id' => 'single', 'option_name' => 'One-time plan', 'product_type' => '', 'price' => '39'),
            )
        );

        return (int) $productId;
    }

    private function actAsCartManager(): void
    {
        if (! class_exists('PPCart_Activator', false)) {
            require_once PPCART_PLUGIN_ROOT . 'includes/class-ppcart-activator.php';
        }
        PPCart_Activator::add_cap();
        if (! class_exists('PPCart_Admin_Stripe_Import', false)) {
            require_once PPCART_PLUGIN_ROOT . 'admin/class-ppcart-admin-stripe-import.php';
        }
        wp_set_current_user($this->factory()->user->create(array('role' => ppcart_live_role('cart_manager'))));
    }

    /**
     * @param array<string, string> $fields
     * @return array<string, mixed>
     */
    private function requestChargeImport(array $fields): array
    {
        $_POST = array_merge(array('nonce' => wp_create_nonce(PPCart_Admin_Stripe_Import::CHARGE_AJAX_ACTION)), $fields);
        $_REQUEST = $_POST;

        add_filter('wp_doing_ajax', '__return_true');
        add_filter('wp_die_ajax_handler', array($this, 'getAjaxDieHandler'));

        ob_start();
        try {
            PPCart_Admin_Stripe_Import::ajax_import_charge();
        } catch (\RuntimeException $exception) {
            $this->assertSame('wp_die', $exception->getMessage());
        } finally {
            remove_filter('wp_doing_ajax', '__return_true');
            remove_filter('wp_die_ajax_handler', array($this, 'getAjaxDieHandler'));
            $_POST = array();
            $_REQUEST = array();
        }

        $response = json_decode(trim((string) ob_get_clean()), true);
        $this->assertIsArray($response);

        return $response;
    }

    /**
     * @return callable
     */
    public function getAjaxDieHandler(): callable
    {
        return static function ($message = '', $title = '', $args = array()): void {
            throw new \RuntimeException('wp_die');
        };
    }
}
