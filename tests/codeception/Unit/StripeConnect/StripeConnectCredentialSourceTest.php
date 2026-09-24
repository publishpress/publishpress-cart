<?php

namespace unit\StripeConnect;

use Codeception\Test\Unit;
use Tests\Support\WordPressStubContext;
use UnitTester;

require_once PPCART_PLUGIN_ROOT . 'includes/class-ppcart-feature-support.php';
require_once PPCART_PLUGIN_ROOT . 'public/controllers/payment/traits/trait-ppcart-public-payment-connect.php';
require_once PPCART_PLUGIN_ROOT . 'public/controllers/checkout/traits/trait-ppcart-public-checkout-connect.php';
require_once PPCART_PLUGIN_ROOT . 'public/controllers/traits/trait-ppcart-public-subscription-connect.php';
require_once PPCART_PLUGIN_ROOT . 'public/controllers/class-ppcart-public-hosted-checkout-controller.php';

class PaymentConnectHarness
{
    use \PPCart_Public_Payment_Connect_Trait;

    public function paymentIntentArgs(array $args, $amount_for_stripe): array
    {
        return $this->add_connect_args_to_payment_intent($args, $amount_for_stripe);
    }
}

class CheckoutConnectHarness
{
    use \PPCart_Public_Checkout_Connect_Trait;

    public function paymentIntentArgs(array $args, $amount_for_stripe): array
    {
        return $this->add_connect_args_to_payment_intent($args, $amount_for_stripe);
    }
}

class SubscriptionConnectHarness
{
    use \PPCart_Public_Subscription_Connect_Trait;

    public function subscriptionArgs(array $args): array
    {
        return $this->add_connect_args_to_subscription($args);
    }
}

class StripeConnectCredentialSourceTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var array<string, mixed>
     */
    private $options = [];

    protected function _before(): void
    {
        global $ppcart_stripe;

        $ppcart_stripe = [
            'mode' => 'test',
        ];

        $this->options = [
            '_ppcart_stripe_enable'                  => '1',
            '_ppcart_stripe_connect_account_id_test' => 'acct_connected_test',
            '_ppcart_stripe_test_key_source'         => 'oauth_access_token',
        ];

        WordPressStubContext::set(
            'get_option',
            function ($key, $default = false) {
                return array_key_exists($key, $this->options) ? $this->options[$key] : $default;
            }
        );

        WordPressStubContext::set(
            'apply_filters',
            static function ($hook_name, $value) {
                return $value;
            }
        );

        WordPressStubContext::set(
            'ppcart_is_pro',
            static function () {
                return false;
            }
        );
    }

    public function test_UT_179_oauth_key_source_is_authoritative_for_every_checkout_path(): void
    {
        $this->assertTrue(PaymentConnectHarness::get_stripe_connect_config()['is_oauth_access_token_key']);
        $this->assertTrue(CheckoutConnectHarness::get_stripe_connect_config()['is_oauth_access_token_key']);
        $this->assertTrue(SubscriptionConnectHarness::get_stripe_connect_config()['is_oauth_access_token_key']);
        $this->assertTrue(\PPCart_Public_Hosted_Checkout_Controller::get_stripe_connect_config()['is_oauth_access_token_key']);
    }

    public function test_UT_180_oauth_key_source_does_not_send_destination_transfer_args(): void
    {
        $payment_args = (new PaymentConnectHarness())->paymentIntentArgs([], 1000);
        $checkout_args = (new CheckoutConnectHarness())->paymentIntentArgs([], 1000);
        $subscription_args = (new SubscriptionConnectHarness())->subscriptionArgs([]);

        $this->assertArrayNotHasKey('transfer_data', $payment_args);
        $this->assertSame(20, $payment_args['application_fee_amount']);
        $this->assertArrayNotHasKey('transfer_data', $checkout_args);
        $this->assertSame(20, $checkout_args['application_fee_amount']);
        $this->assertArrayNotHasKey('transfer_data', $subscription_args);
        $this->assertSame(2.0, $subscription_args['application_fee_percent']);
    }

    public function test_UT_181_direct_key_source_keeps_destination_charges_enabled(): void
    {
        $this->options['_ppcart_stripe_test_key_source'] = 'direct';

        $this->assertFalse(PaymentConnectHarness::get_stripe_connect_config()['is_oauth_access_token_key']);
        $this->assertFalse(CheckoutConnectHarness::get_stripe_connect_config()['is_oauth_access_token_key']);
        $this->assertFalse(SubscriptionConnectHarness::get_stripe_connect_config()['is_oauth_access_token_key']);
        $this->assertFalse(\PPCart_Public_Hosted_Checkout_Controller::get_stripe_connect_config()['is_oauth_access_token_key']);
    }

    /**
     * @test-id UT-344
     */
    public function test_UT_344_connect_config_always_includes_free_extra_percent(): void
    {
        $expected_extra = 2.0;
        $paths = [
            ppcart_get_stripe_connect_config(),
            PaymentConnectHarness::get_stripe_connect_config(),
            CheckoutConnectHarness::get_stripe_connect_config(),
            SubscriptionConnectHarness::get_stripe_connect_config(),
            \PPCart_Public_Hosted_Checkout_Controller::get_stripe_connect_config(),
        ];

        foreach ($paths as $connect) {
            $this->assertArrayHasKey('free_extra_percent', $connect);
            $this->assertSame($expected_extra, $connect['free_extra_percent']);
            $this->assertSame($expected_extra, ppcart_stripe_connect_extra_percent());
            $this->assertSame(
                (float) $connect['platform_fee_percent'] + $expected_extra,
                (float) $connect['total_fee_percent']
            );
        }
    }

    /**
     * @test-id UT-341
     */
    public function test_UT_341_session_filter_cannot_remove_or_change_free_connect_fee(): void
    {
        $amount = 1000;
        $payment = [
            'mode' => 'payment',
            'line_items' => [
                [
                    'price_data' => [
                        'unit_amount' => $amount,
                    ],
                ],
            ],
            'payment_intent_data' => [
                'application_fee_amount' => 20,
            ],
        ];
        $payment_before = $payment;

        $this->assertTrue(ppcart_stripe_connect_fee_is_valid($payment, $amount));
        $this->assertSame($payment_before, $payment);

        $stripped = $payment;
        unset($stripped['payment_intent_data']['application_fee_amount']);
        $this->assertFalse(ppcart_stripe_connect_fee_is_valid($stripped, $amount));

        $changed = $payment;
        $changed['payment_intent_data']['application_fee_amount'] = 1;
        $this->assertFalse(ppcart_stripe_connect_fee_is_valid($changed, $amount));

        $subscription = [
            'mode' => 'subscription',
            'subscription_data' => [
                'application_fee_percent' => 2.0,
            ],
        ];
        $this->assertTrue(ppcart_stripe_connect_fee_is_valid($subscription, 0));

        $subscription_stripped = $subscription;
        unset($subscription_stripped['subscription_data']['application_fee_percent']);
        $this->assertFalse(ppcart_stripe_connect_fee_is_valid($subscription_stripped, 0));

        $subscription_changed = $subscription;
        $subscription_changed['subscription_data']['application_fee_percent'] = 5.0;
        $this->assertFalse(ppcart_stripe_connect_fee_is_valid($subscription_changed, 0));

        $free = [
            'mode' => 'payment',
            'line_items' => [
                [
                    'price_data' => [
                        'unit_amount' => 0,
                    ],
                ],
            ],
            'payment_intent_data' => [
                'application_fee_amount' => 1,
            ],
        ];
        $this->assertTrue(ppcart_stripe_connect_fee_is_valid($free, 0));

        $free_zero_fee = $free;
        $free_zero_fee['payment_intent_data']['application_fee_amount'] = 0;
        $this->assertFalse(ppcart_stripe_connect_fee_is_valid($free_zero_fee, 0));

        $free_missing = $free;
        unset($free_missing['payment_intent_data']['application_fee_amount']);
        $this->assertFalse(ppcart_stripe_connect_fee_is_valid($free_missing, 0));

        $free_changed = $free;
        $free_changed['payment_intent_data']['application_fee_amount'] = 5;
        $this->assertFalse(ppcart_stripe_connect_fee_is_valid($free_changed, 0));
    }

    /**
     * @test-id UT-342
     */
    public function test_UT_342_payment_intent_args_cannot_remove_or_change_free_connect_fee(): void
    {
        $amount = 1000;
        $intent = [
            'amount' => $amount,
            'currency' => 'usd',
            'application_fee_amount' => 20,
        ];
        $intent_before = $intent;

        $this->assertTrue(ppcart_stripe_connect_fee_is_valid($intent, $amount));
        $this->assertSame($intent_before, $intent);

        $stripped = $intent;
        unset($stripped['application_fee_amount']);
        $this->assertFalse(ppcart_stripe_connect_fee_is_valid($stripped, $amount));

        $changed = $intent;
        $changed['application_fee_amount'] = 1;
        $this->assertFalse(ppcart_stripe_connect_fee_is_valid($changed, $amount));

        $update = [
            'amount' => $amount,
            'application_fee_amount' => 20,
        ];
        $this->assertTrue(ppcart_stripe_connect_fee_is_valid($update, $amount));

        $update_stripped = $update;
        unset($update_stripped['application_fee_amount']);
        $this->assertFalse(ppcart_stripe_connect_fee_is_valid($update_stripped, $amount));

        $zero = [
            'amount' => 0,
            'application_fee_amount' => 1,
        ];
        $this->assertTrue(ppcart_stripe_connect_fee_is_valid($zero, 0));

        $zero_missing = $zero;
        unset($zero_missing['application_fee_amount']);
        $this->assertFalse(ppcart_stripe_connect_fee_is_valid($zero_missing, 0));
    }

    /**
     * @test-id UT-343
     */
    public function test_UT_343_subscription_args_cannot_remove_or_change_free_connect_fee(): void
    {
        $subscription = [
            'customer' => 'cus_test',
            'items' => [
                ['price' => 'price_test'],
            ],
            'application_fee_percent' => 2.0,
        ];
        $subscription_before = $subscription;

        $this->assertTrue(ppcart_stripe_connect_fee_is_valid($subscription));
        $this->assertSame($subscription_before, $subscription);

        $stripped = $subscription;
        unset($stripped['application_fee_percent']);
        $this->assertFalse(ppcart_stripe_connect_fee_is_valid($stripped));

        $changed = $subscription;
        $changed['application_fee_percent'] = 5.0;
        $this->assertFalse(ppcart_stripe_connect_fee_is_valid($changed));

        $zeroed = $subscription;
        $zeroed['application_fee_percent'] = 0.0;
        $this->assertFalse(ppcart_stripe_connect_fee_is_valid($zeroed));
    }
}
