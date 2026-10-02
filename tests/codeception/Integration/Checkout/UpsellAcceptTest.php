<?php

declare(strict_types=1);

namespace Tests\Integration\Checkout;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Order;
use PPCart_Stripe_Checkout_Customer;
use PublishPress\Stripe\ApiRequestor;
use PublishPress\Stripe\HttpClient\ClientInterface;

/**
 * Accepting a one-click Stripe upsell through the `ppcart_process_upsell` AJAX
 * route charges the parent order's card once and records the child order.
 *
 * Stripe is mocked at the SDK HTTP client, so the handler runs end to end.
 *
 * @see https://github.com/publishpress/publishpress-cart/issues/893
 */
class UpsellAcceptTest extends WPTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    /**
     * @var UpsellStripeHttpClientStub
     */
    private $stripeHttp;

    /**
     * @var mixed
     */
    private $previousStripeSettings;

    /**
     * @var int
     */
    private $parentOrderId = 0;

    /**
     * @var int
     */
    private $productId = 0;

    protected function setUp(): void
    {
        parent::setUp();

        global $ppcart_stripe;
        $this->previousStripeSettings = $ppcart_stripe;
        $ppcart_stripe = [ 'sk' => 'sk_test_upsell', 'mode' => 'test' ];

        $this->stripeHttp = new UpsellStripeHttpClientStub();
        ApiRequestor::setHttpClient($this->stripeHttp);

        $this->productId = (int) wp_insert_post(
            [
                'post_type'   => ppcart_live_post_type('product'),
                'post_status' => 'publish',
                'post_title'  => 'Upsell Offer Product',
            ]
        );

        $this->parentOrderId = (int) wp_insert_post(
            [
                'post_type'   => ppcart_live_post_type('order'),
                'post_status' => 'publish',
                'post_title'  => 'Upsell Parent Order',
            ]
        );
        ppcart_update_post_meta($this->parentOrderId, 'pay_method', 'stripe');
        ppcart_update_post_meta($this->parentOrderId, 'status', 'paid');
        PPCart_Stripe_Checkout_Customer::remember_order_payment_method($this->parentOrderId, 'pm_parentcard');

        add_filter('ppcart_order_child_of', [ $this, 'buildChildOrder' ], 10, 3);
    }

    protected function tearDown(): void
    {
        global $ppcart_stripe;
        $ppcart_stripe = $this->previousStripeSettings;

        ApiRequestor::setHttpClient(null);
        remove_filter('ppcart_order_child_of', [ $this, 'buildChildOrder' ], 10);

        $_POST    = [];
        $_REQUEST = [];

        parent::tearDown();
    }

    /**
     * Stands in for Pro's upsell path: a one-time offer child of the parent order.
     *
     * @param mixed  $order
     * @param int    $parentId
     * @param string $type
     * @return PPCart_Order
     */
    public function buildChildOrder($order, $parentId, $type)
    {
        $child = new PPCart_Order();
        $child->pay_method   = 'stripe';
        $child->customer_id  = 'cus_upsellbuyer';
        $child->email        = 'buyer@example.test';
        $child->currency     = 'USD';
        $child->amount       = 5;
        $child->product_id   = $this->productId;
        $child->order_type   = $type;
        $child->order_parent = (int) $parentId;
        $child->us_offer     = 1;
        $child->plan         = (object) [ 'type' => 'one-time' ];

        return $child;
    }

    /**
     * @test-id IT-393
     */
    public function test_IT_393_accepting_a_stripe_upsell_charges_the_parent_card_and_records_the_child_order(): void
    {
        $response = $this->acceptUpsell();

        $this->assertIsInt($response);
        $child = new PPCart_Order($response);
        $this->assertSame('paid', $child->status);
        $this->assertSame('pi_upsellcharge', $child->transaction_id);

        $this->assertCount(1, $this->stripeHttp->paymentIntentRequests);
        $request = $this->stripeHttp->paymentIntentRequests[0];
        $this->assertSame('pm_parentcard', $request['params']['payment_method']);
        $this->assertSame('cus_upsellbuyer', $request['params']['customer']);
        $this->assertSame(500, (int) $request['params']['amount']);
    }

    /**
     * @test-id IT-393
     */
    public function test_IT_393_retrying_the_same_upsell_reuses_one_stripe_idempotency_key(): void
    {
        $this->acceptUpsell();
        $this->acceptUpsell();

        $this->assertCount(2, $this->stripeHttp->paymentIntentRequests);
        $firstKey  = $this->stripeHttp->paymentIntentRequests[0]['idempotency_key'];
        $secondKey = $this->stripeHttp->paymentIntentRequests[1]['idempotency_key'];

        $this->assertSame('ppcart-upsell-' . $this->parentOrderId . '-1', $firstKey);
        $this->assertSame($firstKey, $secondKey);
    }

    /**
     * @return callable
     */
    public function getAjaxDieHandler(): callable
    {
        return static function ($message = '', $title = '', $args = []): void {
            throw new UpsellAjaxDie('wp_die');
        };
    }

    /**
     * Runs the handler registered on the public `ppcart_process_upsell` AJAX route.
     *
     * @return mixed Decoded JSON response.
     */
    private function acceptUpsell()
    {
        $post = [
            'ppcart-order' => (string) $this->parentOrderId,
            'ppcart-nonce' => wp_create_nonce('ppcart_upsell-' . $this->parentOrderId),
            'step'         => '1',
        ];
        $_POST    = $post;
        $_REQUEST = $post;

        $this->assertNotFalse(has_action('wp_ajax_nopriv_ppcart_process_upsell'));

        add_filter('wp_doing_ajax', '__return_true');
        add_filter('wp_die_ajax_handler', [ $this, 'getAjaxDieHandler' ]);

        ob_start();
        try {
            do_action('wp_ajax_nopriv_ppcart_process_upsell');
        } catch (UpsellAjaxDie $exception) {
            // wp_send_json() ends the request.
        } finally {
            remove_filter('wp_doing_ajax', '__return_true');
            remove_filter('wp_die_ajax_handler', [ $this, 'getAjaxDieHandler' ]);
        }

        return json_decode(trim((string) ob_get_clean()), true);
    }
}

/**
 * Not an \Exception, so the handler's Stripe catch block cannot swallow it.
 */
class UpsellAjaxDie extends \Error
{
}

/**
 * Answers the Stripe PaymentIntent create call and records what was sent.
 */
class UpsellStripeHttpClientStub implements ClientInterface
{
    /**
     * @var array<int, array{params: array<string, mixed>, idempotency_key: string}>
     */
    public $paymentIntentRequests = [];

    public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null)
    {
        if ('post' !== strtolower($method) || '/v1/payment_intents' !== parse_url($absUrl, PHP_URL_PATH)) {
            return [ '{"error":{"message":"Unexpected Stripe request in test"}}', 400, [] ];
        }

        $idempotencyKey = '';
        foreach ($headers as $header) {
            if (0 === stripos($header, 'Idempotency-Key:')) {
                $idempotencyKey = trim(substr($header, strlen('Idempotency-Key:')));
            }
        }

        $this->paymentIntentRequests[] = [
            'params'          => $params,
            'idempotency_key' => $idempotencyKey,
        ];

        return [ '{"id":"pi_upsellcharge","object":"payment_intent","status":"succeeded"}', 200, [] ];
    }
}
