<?php

declare(strict_types=1);

namespace Tests\Integration\StripeSync;

use PPCart_Admin_Order_List_Controller;
use PublishPress\Stripe\ApiRequestor;
use PublishPress\Stripe\HttpClient\ClientInterface;
use ReflectionClass;
use Tests\Support\Integration\StripeSyncTestCase;
use WPDieException;

/**
 * Records each Stripe request's API key and answers subscription calls.
 */
class CancelSubscriptionModeFakeStripe implements ClientInterface
{
    /** @var bool True to answer every subscription call with resource_missing. */
    public $missing = false;

    /** @var array<int, array{method:string, path:string, key:string}> */
    public $calls = array();

    public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null)
    {
        $key = '';
        foreach ((array) $headers as $header) {
            if (0 === stripos((string) $header, 'Authorization: Bearer ')) {
                $key = substr((string) $header, strlen('Authorization: Bearer '));
            }
        }

        $path = (string) parse_url($absUrl, PHP_URL_PATH);
        $this->calls[] = array(
            'method' => strtolower((string) $method),
            'path' => $path,
            'key' => $key,
        );

        if (preg_match('#^/v1/subscriptions/([A-Za-z0-9_]+)$#', $path, $matches)) {
            if ($this->missing) {
                return array(
                    wp_json_encode(
                        array(
                            'error' => array(
                                'code' => 'resource_missing',
                                'message' => "No such subscription: '" . $matches[1] . "'",
                                'param' => 'id',
                                'type' => 'invalid_request_error',
                            ),
                        )
                    ),
                    404,
                    array(),
                );
            }

            return array(
                wp_json_encode(
                    array(
                        'object' => 'subscription',
                        'id' => $matches[1],
                        'status' => 'delete' === strtolower((string) $method) ? 'canceled' : 'active',
                        'customer' => 'cus_cancel_mode',
                        'items' => array('object' => 'list', 'data' => array()),
                    )
                ),
                200,
                array(),
            );
        }

        return array(
            wp_json_encode(array('object' => 'list', 'url' => $path, 'data' => array(), 'has_more' => false)),
            200,
            array(),
        );
    }

    /**
     * @return array<int, string>
     */
    public function keys(): array
    {
        return array_values(array_unique(array_column($this->calls, 'key')));
    }

    public function cancelCalls(): int
    {
        return count(
            array_filter(
                $this->calls,
                static function ($call) {
                    return 'delete' === $call['method'];
                }
            )
        );
    }
}

/**
 * Issue #1221: cancel a Stripe subscription with its own mode's key, and let an
 * admin cancel it locally when Stripe no longer has it.
 */
class CancelSubscriptionModeTest extends StripeSyncTestCase
{
    private const TEST_KEY = 'sk_test_cancel_mode';
    private const LIVE_KEY = 'sk_live_cancel_mode';

    /**
     * @var \IntegrationTester
     */
    protected $tester;

    /** @var mixed */
    private $previousStripeSettings;

    /** @var CancelSubscriptionModeFakeStripe */
    private $http;

    protected function setUp(): void
    {
        parent::setUp();

        global $ppcart_stripe;
        $this->previousStripeSettings = $ppcart_stripe;

        // The site now runs in live mode.
        $ppcart_stripe = array('sk' => self::LIVE_KEY, 'mode' => 'live');
        update_option('_ppcart_stripe_api', 'live');
        update_option('_ppcart_stripe_live_sk', self::LIVE_KEY);
        update_option('_ppcart_stripe_test_sk', self::TEST_KEY);

        $this->http = new CancelSubscriptionModeFakeStripe();
        ApiRequestor::setHttpClient($this->http);
        add_filter('pre_wp_mail', '__return_true');
    }

    protected function tearDown(): void
    {
        global $ppcart_stripe;
        $ppcart_stripe = $this->previousStripeSettings;

        ApiRequestor::setHttpClient(null);
        remove_filter('pre_wp_mail', '__return_true');
        delete_option('_ppcart_stripe_api');
        delete_option('_ppcart_stripe_live_sk');
        delete_option('_ppcart_stripe_test_sk');
        $_POST = array();

        parent::tearDown();
    }

    public function test_cancel_uses_client_for_subscription_mode(): void
    {
        $subscriptionId = $this->createStripeSubscription(
            array(
                'gateway_mode' => 'test',
                'subscription_id' => 'sub_cancel_mode_test',
            )
        );
        wp_set_current_user($this->factory()->user->create(array('role' => 'administrator')));

        ppcart_do_cancel_subscription($subscriptionId, false, true, false);

        $this->assertNotEmpty($this->http->calls);
        $this->assertSame('get', $this->http->calls[0]['method']);
        $this->assertSame('/v1/subscriptions/sub_cancel_mode_test', $this->http->calls[0]['path']);
        $this->assertSame(array(self::TEST_KEY), $this->http->keys());
        $this->assertSame(1, $this->http->cancelCalls());
    }

    public function test_admin_cancels_missing_subscription_locally(): void
    {
        $this->http->missing = true;
        $subscriptionId = $this->createStripeSubscription(
            array(
                'gateway_mode' => 'test',
                'subscription_id' => 'sub_cancel_mode_missing',
            )
        );
        wp_set_current_user($this->factory()->user->create(array('role' => 'administrator')));

        $out = ppcart_do_cancel_subscription($subscriptionId, false, true, false, array('allow_local_cancel' => true));

        $this->assertSame('OK', $out);
        $this->assertSame(array(self::TEST_KEY), $this->http->keys());
        $this->assertSame(0, $this->http->cancelCalls());
        $this->assertCanceledLocally($subscriptionId, 'was not found in Stripe (test mode)');
    }

    public function test_admin_bulk_cancel_cancels_missing_subscription_locally(): void
    {
        $this->http->missing = true;
        $subscriptionId = $this->createStripeSubscription(
            array(
                'gateway_mode' => 'test',
                'subscription_id' => 'sub_cancel_mode_bulk',
            )
        );
        wp_set_current_user($this->factory()->user->create(array('role' => 'administrator')));

        $this->bulkController()->bulk_action_handler(
            'https://example.test/wp-admin/edit.php?post_type=ppcart_subscription',
            'ppcart_make_canceled',
            array($subscriptionId)
        );

        $this->assertCanceledLocally($subscriptionId, 'was not found in Stripe (test mode)');
    }

    public function test_admin_screen_cancel_button_cancels_missing_subscription_locally(): void
    {
        $this->http->missing = true;
        $subscriptionId = $this->createStripeSubscription(
            array(
                'gateway_mode' => 'test',
                'subscription_id' => 'sub_cancel_mode_ajax',
            )
        );
        wp_set_current_user($this->factory()->user->create(array('role' => 'administrator')));

        $_POST = array(
            'nonce' => wp_create_nonce('ppcart_ajax_nonce'),
            'id' => (string) $subscriptionId,
            'subscription_id' => 'sub_cancel_mode_ajax',
            'payment_method' => 'stripe',
            'cancel_timing' => 'immediate',
            'admin_cancel' => '1',
        );

        $output = $this->runAjaxHandler('ppcart_unsubscribe_customer');

        $this->assertSame('OK', trim($output));
        $this->assertCanceledLocally($subscriptionId, 'was not found in Stripe (test mode)');
    }

    public function test_admin_cancels_locally_when_mode_has_no_key(): void
    {
        delete_option('_ppcart_stripe_test_sk');
        $subscriptionId = $this->createStripeSubscription(
            array(
                'gateway_mode' => 'test',
                'subscription_id' => 'sub_cancel_mode_no_key',
            )
        );
        wp_set_current_user($this->factory()->user->create(array('role' => 'administrator')));

        $out = ppcart_do_cancel_subscription($subscriptionId, false, true, false, array('allow_local_cancel' => true));

        $this->assertSame('OK', $out);
        $this->assertSame(array(), $this->http->calls);
        $this->assertCanceledLocally($subscriptionId, 'no Stripe API key is set for test mode');
    }

    public function test_non_admin_path_keeps_status_when_subscription_missing(): void
    {
        $this->http->missing = true;
        $subscriptionId = $this->createStripeSubscription(
            array(
                'gateway_mode' => 'test',
                'subscription_id' => 'sub_cancel_mode_integration',
            )
        );
        // Integration and webhook callers pass no allow_local_cancel option.
        wp_set_current_user($this->factory()->user->create(array('role' => 'administrator')));

        $out = ppcart_do_cancel_subscription($subscriptionId, false, true, false);

        $this->assertNotSame('OK', $out);
        $this->assertStringContainsString('No such subscription', (string) $out);
        $this->assertStillActive($subscriptionId);
    }

    public function test_local_cancel_option_needs_manage_capability(): void
    {
        $this->http->missing = true;
        $subscriptionId = $this->createStripeSubscription(
            array(
                'gateway_mode' => 'test',
                'subscription_id' => 'sub_cancel_mode_customer',
            )
        );
        wp_set_current_user($this->factory()->user->create(array('role' => 'subscriber')));

        $out = ppcart_do_cancel_subscription($subscriptionId, false, true, false, array('allow_local_cancel' => true));

        $this->assertNotSame('OK', $out);
        $this->assertStillActive($subscriptionId);
    }

    public function test_admin_ajax_without_admin_flag_keeps_status(): void
    {
        $this->http->missing = true;
        $subscriptionId = $this->createStripeSubscription(
            array(
                'gateway_mode' => 'test',
                'subscription_id' => 'sub_cancel_mode_no_flag',
            )
        );
        wp_set_current_user($this->factory()->user->create(array('role' => 'administrator')));

        $_POST = array(
            'nonce' => wp_create_nonce('ppcart_ajax_nonce'),
            'id' => (string) $subscriptionId,
            'subscription_id' => 'sub_cancel_mode_no_flag',
            'payment_method' => 'stripe',
            'cancel_timing' => 'immediate',
        );

        $output = $this->runAjaxHandler('ppcart_unsubscribe_customer');

        $this->assertNotSame('OK', trim($output));
        $this->assertStillActive($subscriptionId);
    }

    public function test_no_key_without_admin_flag_falls_back_to_site_client(): void
    {
        delete_option('_ppcart_stripe_test_sk');
        $this->http->missing = true;
        $subscriptionId = $this->createStripeSubscription(
            array(
                'gateway_mode' => 'test',
                'subscription_id' => 'sub_cancel_mode_fallback',
            )
        );

        $out = ppcart_do_cancel_subscription($subscriptionId, false, true, false);

        $this->assertNotSame('OK', $out);
        $this->assertSame(array(self::LIVE_KEY), $this->http->keys());
        $this->assertStillActive($subscriptionId);
    }

    private function assertCanceledLocally(int $subscriptionId, string $logFragment): void
    {
        $this->assertSame('canceled', ppcart_get_post_meta($subscriptionId, 'status', true));
        $this->assertSame('canceled', ppcart_get_post_meta($subscriptionId, 'sub_status', true));
        $this->assertSame(gmdate('Y-m-d'), ppcart_get_post_meta($subscriptionId, 'cancel_date', true));

        $log = implode("\n", ppcart_order_log($subscriptionId));
        $this->assertStringContainsString($logFragment, $log);
        $this->assertStringContainsString('canceled locally', $log);
    }

    private function assertStillActive(int $subscriptionId): void
    {
        $this->assertSame('active', ppcart_get_post_meta($subscriptionId, 'status', true));
        $this->assertSame('active', ppcart_get_post_meta($subscriptionId, 'sub_status', true));
        $this->assertSame('', (string) ppcart_get_post_meta($subscriptionId, 'cancel_date', true));
        $this->assertSame(0, $this->http->cancelCalls());
    }

    private function bulkController(): PPCart_Admin_Order_List_Controller
    {
        return (new ReflectionClass(PPCart_Admin_Order_List_Controller::class))
            ->newInstanceWithoutConstructor();
    }

    /**
     * Run an AJAX handler that ends with wp_die() or wp_send_json_*().
     *
     * @param callable-string $handler Handler function name.
     * @return string Echoed output.
     */
    private function runAjaxHandler(string $handler): string
    {
        $dieHandler = static function () {
            return static function ($message = '') {
                throw new WPDieException(is_string($message) ? $message : '');
            };
        };
        add_filter('wp_die_ajax_handler', $dieHandler, 999);
        add_filter('wp_die_handler', $dieHandler, 999);
        add_filter('wp_doing_ajax', '__return_true');

        ob_start();
        try {
            $handler();
        } catch (WPDieException $e) {
            // Expected: the handler always ends the request.
        } finally {
            $output = (string) ob_get_clean();
            remove_filter('wp_die_ajax_handler', $dieHandler, 999);
            remove_filter('wp_die_handler', $dieHandler, 999);
            remove_filter('wp_doing_ajax', '__return_true');
        }

        return $output;
    }
}
