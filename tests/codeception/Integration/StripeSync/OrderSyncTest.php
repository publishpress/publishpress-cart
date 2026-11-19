<?php

declare(strict_types=1);

namespace Tests\Integration\StripeSync;

use PPCart_Activator;
use PPCart_Admin_Order_Controller;
use PPCart_Admin_Order_List_Controller;
use PPCart_Admin_Subscription_Controller;
use PPCart_Order;
use PPCart_Stripe_Sync;
use ReflectionClass;
use Tests\Support\Integration\StripeSyncTestCase;

/**
 * Manual "Sync with Stripe" for single orders (issue #1219).
 */
class OrderSyncTest extends StripeSyncTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    /**
     * @var array<string, mixed>
     */
    private $previousGet = array();

    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists('PPCart_Activator', false)) {
            require_once PPCART_PLUGIN_ROOT . 'includes/class-ppcart-activator.php';
        }

        PPCart_Activator::add_cap();
        $this->previousGet = $_GET;
        $_GET = array();
    }

    protected function tearDown(): void
    {
        $_GET = $this->previousGet;
        remove_all_filters('ppcart_bulk_sync_order_limit');

        parent::tearDown();
    }

    public function test_order_sync_payment_id_accepts_only_stripe_charges_and_payment_intents(): void
    {
        $this->assertSame('ch_abc', PPCart_Stripe_Sync::get_order_sync_payment_id((object) array('pay_method' => 'stripe', 'transaction_id' => 'ch_abc')));
        $this->assertSame('pi_abc', PPCart_Stripe_Sync::get_order_sync_payment_id((object) array('pay_method' => 'stripe', 'transaction_id' => 'pi_abc')));
        $this->assertSame(
            'ch_from_charge_field',
            PPCart_Stripe_Sync::get_order_sync_payment_id((object) array('pay_method' => 'stripe', 'stripe_charge_id' => 'ch_from_charge_field', 'transaction_id' => 'pi_other'))
        );
        $this->assertSame('', PPCart_Stripe_Sync::get_order_sync_payment_id((object) array('pay_method' => 'stripe', 'transaction_id' => '')));
        $this->assertSame('', PPCart_Stripe_Sync::get_order_sync_payment_id((object) array('pay_method' => 'stripe', 'transaction_id' => 'cs_test_session')));
        $this->assertSame('', PPCart_Stripe_Sync::get_order_sync_payment_id((object) array('pay_method' => 'paypal', 'transaction_id' => 'ch_abc')));
        $this->assertSame('', PPCart_Stripe_Sync::get_order_sync_payment_id(null));
    }

    public function test_sync_order_by_local_marks_a_one_time_order_refunded_in_stripe_as_refunded(): void
    {
        $orderId = $this->createStripeOrder(
            array(
                'transaction_id' => 'ch_one_time_refunded',
                'status' => 'paid',
                'payment_status' => 'paid',
            )
        );

        $message = PPCart_Stripe_Sync::sync_order_by_local(
            new PPCart_Order($orderId),
            $this->createStripeChargeClientStub($this->charge('ch_one_time_refunded', 2000))
        );

        $this->assertSame('Order synced successfully.', $message);
        $this->assertSame('refunded', ppcart_get_post_meta($orderId, 'status', true));
        $this->assertArrayHasKey('re_one_time_refunded', ppcart_get_post_meta($orderId, 'refund_log', true));
    }

    public function test_sync_order_by_local_sets_a_partially_refunded_order_back_to_paid(): void
    {
        $orderId = $this->createStripeOrder(
            array(
                'transaction_id' => 'ch_partial_local_refunded',
                'status' => 'refunded',
                'payment_status' => 'refunded',
            )
        );

        PPCart_Stripe_Sync::sync_order_by_local(
            new PPCart_Order($orderId),
            $this->createStripeChargeClientStub($this->charge('ch_partial_local_refunded', 500))
        );

        $this->assertSame('paid', ppcart_get_post_meta($orderId, 'status', true));
        $this->assertArrayHasKey('re_partial_local_refunded', ppcart_get_post_meta($orderId, 'refund_log', true));
    }

    public function test_sync_order_by_local_loads_the_latest_charge_of_a_payment_intent(): void
    {
        $orderId = $this->createStripeOrder(
            array(
                'transaction_id' => 'pi_one_time_refunded',
                'status' => 'paid',
                'payment_status' => 'paid',
            )
        );
        $charge = $this->charge('ch_pi_one_time_refunded', 2000);
        $charge->payment_intent = 'pi_one_time_refunded';
        $stripe = (object) array(
            'charges' => $this->createStripeChargeClientStub($charge)->charges,
            'paymentIntents' => new class ($charge) {
                /** @var object */
                private $charge;

                public function __construct($charge)
                {
                    $this->charge = $charge;
                }

                public function retrieve()
                {
                    return (object) array(
                        'id' => 'pi_one_time_refunded',
                        'latest_charge' => $this->charge,
                    );
                }
            },
        );

        PPCart_Stripe_Sync::sync_order_by_local(new PPCart_Order($orderId), $stripe);

        $this->assertSame('refunded', ppcart_get_post_meta($orderId, 'status', true));
        $this->assertSame('ch_pi_one_time_refunded', ppcart_get_post_meta($orderId, 'transaction_id', true));
    }

    public function test_sync_order_by_local_rejects_an_order_without_a_stripe_payment_id(): void
    {
        $orderId = $this->createStripeOrder(array('transaction_id' => ''));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Stripe payment ID is missing for this order.');

        PPCart_Stripe_Sync::sync_order_by_local(new PPCart_Order($orderId), $this->createStripeChargeClientStub($this->charge('ch_unused', 0)));
    }

    public function test_order_list_offers_the_sync_with_stripe_bulk_action(): void
    {
        $actions = $this->listController()->order_bulk_action(array());

        $this->assertArrayHasKey('ppcart_sync_stripe', $actions);
        $this->assertSame('Sync with Stripe', $actions['ppcart_sync_stripe']);
    }

    public function test_bulk_sync_logs_each_failed_order_and_skips_orders_without_a_stripe_payment(): void
    {
        wp_set_current_user($this->factory()->user->create(array('role' => ppcart_live_role('cart_manager'))));
        $stripeOrderId = $this->createStripeOrder(array('transaction_id' => 'ch_bulk_no_key'));
        $paypalOrderId = $this->createStripeOrder(array('pay_method' => 'paypal', 'transaction_id' => 'PAYPAL123'));

        // No Stripe secret key is configured in the test site, so the sync
        // reaches the Stripe layer and fails there.
        $redirect = $this->listController()->bulk_action_handler(
            'https://example.test/wp-admin/edit.php?post_type=' . ppcart_live_post_type('order'),
            'ppcart_sync_stripe',
            array($stripeOrderId, $paypalOrderId)
        );

        $this->assertStringContainsString('bulk_ppcart_sync_stripe=0', $redirect);
        $this->assertStringContainsString('bulk_ppcart_sync_stripe_failed=1', $redirect);
        $this->assertStringContainsString('bulk_ppcart_sync_stripe_skipped=1', $redirect);
        $this->assertStringContainsString(
            'Error syncing order from Stripe! Message: Stripe secret key missing.',
            implode("\n", (array) ppcart_order_log($stripeOrderId))
        );
        $this->assertSame(array(), array_values((array) ppcart_order_log($paypalOrderId)));
    }

    public function test_bulk_sync_skips_orders_the_user_cannot_edit(): void
    {
        $orderId = $this->createStripeOrder(array('transaction_id' => 'ch_bulk_capability'));
        wp_set_current_user($this->factory()->user->create(array('role' => 'subscriber')));

        $this->assertFalse(current_user_can('edit_post', $orderId));

        $redirect = $this->listController()->bulk_action_handler(
            'https://example.test/wp-admin/edit.php?post_type=' . ppcart_live_post_type('order'),
            'ppcart_sync_stripe',
            array($orderId)
        );

        $this->assertStringContainsString('bulk_ppcart_sync_stripe=0', $redirect);
        $this->assertStringNotContainsString('bulk_ppcart_sync_stripe_failed', $redirect);
        $this->assertSame(array(), array_values((array) ppcart_order_log($orderId)));
    }

    public function test_bulk_sync_stops_at_the_order_batch_limit(): void
    {
        wp_set_current_user($this->factory()->user->create(array('role' => ppcart_live_role('cart_manager'))));
        $firstOrderId = $this->createStripeOrder(array('transaction_id' => 'ch_bulk_limit_first'));
        $secondOrderId = $this->createStripeOrder(array('transaction_id' => 'ch_bulk_limit_second'));
        add_filter('ppcart_bulk_sync_order_limit', static function () {
            return 1;
        });

        $redirect = $this->listController()->bulk_action_handler(
            'https://example.test/wp-admin/edit.php?post_type=' . ppcart_live_post_type('order'),
            'ppcart_sync_stripe',
            array($firstOrderId, $secondOrderId)
        );

        $this->assertStringContainsString('bulk_ppcart_sync_stripe_failed=1', $redirect);
        $this->assertNotEmpty((array) ppcart_order_log($firstOrderId));
        $this->assertSame(array(), array_values((array) ppcart_order_log($secondOrderId)));
    }

    public function test_bulk_sync_notice_on_the_orders_list_talks_about_orders(): void
    {
        $_GET = array(
            'post_type' => ppcart_live_post_type('order'),
            'bulk_ppcart_sync_stripe' => '2',
            'bulk_ppcart_sync_stripe_failed' => '1',
            'bulk_ppcart_sync_stripe_skipped' => '1',
        );

        ob_start();
        $this->listController()->sync_stripe_bulk_notice();
        $notice = (string) ob_get_clean();

        $this->assertStringContainsString('notice-warning', $notice);
        $this->assertStringContainsString('2 orders synced from Stripe.', $notice);
        $this->assertStringContainsString('1 failed; see its order log for the reason.', $notice);
        $this->assertStringContainsString('1 skipped because it has no Stripe payment to sync from.', $notice);
        $this->assertStringNotContainsString('subscription', $notice);
    }

    public function test_order_screen_shows_sync_button_only_for_stripe_orders_with_a_payment_id(): void
    {
        $stripeOrderId = $this->createStripeOrder(array('transaction_id' => 'pi_button_visible'));
        $missingIdOrderId = $this->createStripeOrder(array('transaction_id' => ''));
        $paypalOrderId = $this->createStripeOrder(array('pay_method' => 'paypal', 'transaction_id' => 'PAYPAL456'));

        $stripeHtml = $this->renderOrderScreen($stripeOrderId);
        $this->assertStringContainsString('ppcart_sync_order', $stripeHtml);
        $this->assertStringContainsString('data-id="' . $stripeOrderId . '"', $stripeHtml);
        $this->assertStringContainsString('Sync with Stripe', $stripeHtml);

        $this->assertStringNotContainsString('ppcart_sync_order', $this->renderOrderScreen($missingIdOrderId));
        $this->assertStringNotContainsString('ppcart_sync_order', $this->renderOrderScreen($paypalOrderId));
    }

    public function test_order_sync_ajax_requires_edit_access_to_the_order(): void
    {
        $orderId = $this->createStripeOrder(array('transaction_id' => 'ch_ajax_author'));
        wp_set_current_user($this->factory()->user->create(array('role' => 'author')));

        $response = $this->requestOrderSync($orderId);

        $this->assertFalse($response['success']);
        $this->assertSame('Unauthorized access.', $response['data']['message']);
    }

    public function test_order_sync_ajax_lets_a_cart_manager_reach_the_stripe_layer(): void
    {
        $orderId = $this->createStripeOrder(array('transaction_id' => 'ch_ajax_manager'));
        wp_set_current_user($this->factory()->user->create(array('role' => ppcart_live_role('cart_manager'))));

        $response = $this->requestOrderSync($orderId);

        $this->assertFalse($response['success']);
        $this->assertSame('Stripe secret key missing.', $response['data']['message']);
    }

    public function test_order_sync_ajax_rejects_an_order_without_a_stripe_payment(): void
    {
        $orderId = $this->createStripeOrder(array('pay_method' => 'paypal', 'transaction_id' => 'PAYPAL789'));
        wp_set_current_user($this->factory()->user->create(array('role' => ppcart_live_role('cart_manager'))));

        $response = $this->requestOrderSync($orderId);

        $this->assertFalse($response['success']);
        $this->assertSame('Stripe payment ID is missing for this order.', $response['data']['message']);
    }

    /**
     * @return array<string, mixed>
     */
    private function requestOrderSync(int $orderId): array
    {
        $_POST = array(
            'nonce'    => wp_create_nonce('ppcart_ajax_nonce'),
            'order_id' => (string) $orderId,
        );
        $_REQUEST['nonce'] = $_POST['nonce'];
        $_REQUEST['order_id'] = $_POST['order_id'];

        add_filter('wp_doing_ajax', '__return_true');
        add_filter('wp_die_ajax_handler', array($this, 'getAjaxDieHandler'));

        ob_start();
        try {
            $controller = new PPCart_Admin_Subscription_Controller('ppcart', 'Cart', 'test');
            $controller->sync_order_ajax();
        } catch (\RuntimeException $exception) {
            $this->assertSame('wp_die', $exception->getMessage());
        } finally {
            remove_filter('wp_doing_ajax', '__return_true');
            remove_filter('wp_die_ajax_handler', array($this, 'getAjaxDieHandler'));
            unset($_POST['nonce'], $_POST['order_id'], $_REQUEST['nonce'], $_REQUEST['order_id']);
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

    private function listController(): PPCart_Admin_Order_List_Controller
    {
        return (new ReflectionClass(PPCart_Admin_Order_List_Controller::class))
            ->newInstanceWithoutConstructor();
    }

    private function charge(string $chargeId, int $amountRefunded): object
    {
        return (object) array(
            'id' => $chargeId,
            'amount' => 2000,
            'amount_refunded' => $amountRefunded,
            'currency' => 'usd',
            'refunded' => $amountRefunded >= 2000,
            'metadata' => (object) array(),
            'refunds' => (object) array(
                'data' => array(
                    (object) array(
                        'id' => 're_' . substr($chargeId, 3),
                        'amount' => $amountRefunded,
                        'created' => 1234567890,
                    ),
                ),
            ),
        );
    }
}
