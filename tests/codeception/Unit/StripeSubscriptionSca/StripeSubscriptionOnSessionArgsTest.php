<?php

namespace unit\StripeSubscriptionSca;

use Codeception\Test\Unit;
use Tests\Support\WordPressStubContext;
use UnitTester;

class StripeSubscriptionOnSessionArgsTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    protected function _before(): void
    {
        WordPressStubContext::clear();
    }

    protected function _after(): void
    {
        WordPressStubContext::clear();
        parent::_after();
    }

    /**
     * @test-id UT-345
     */
    public function test_UT_345_empty_args_require_on_session_confirmation_secret(): void
    {
        $args = ppcart_stripe_subscription_on_session_args([]);

        $this->assertSame('default_incomplete', $args['payment_behavior']);
        $this->assertSame('on_subscription', $args['payment_settings']['save_default_payment_method']);
        $this->assertSame(['latest_invoice.confirmation_secret'], $args['expand']);

        $from_null = ppcart_stripe_subscription_on_session_args(null);
        $this->assertSame('default_incomplete', $from_null['payment_behavior']);
        $this->assertSame(['latest_invoice.confirmation_secret'], $from_null['expand']);
    }

    /**
     * @test-id UT-345
     */
    public function test_UT_345_stripped_args_restore_on_session_posture(): void
    {
        $args = ppcart_stripe_subscription_on_session_args(
            [
                'customer'           => 'cus_123',
                'items'              => [['price' => 'price_123']],
                'payment_behavior'   => 'allow_incomplete',
            ]
        );

        $this->assertSame('cus_123', $args['customer']);
        $this->assertSame('default_incomplete', $args['payment_behavior']);
        $this->assertSame('on_subscription', $args['payment_settings']['save_default_payment_method']);
        $this->assertContains('latest_invoice.confirmation_secret', $args['expand']);
    }

    /**
     * @test-id UT-345
     */
    public function test_UT_345_preserves_other_expand_and_payment_settings(): void
    {
        $args = ppcart_stripe_subscription_on_session_args(
            [
                'payment_settings' => [
                    'payment_method_types'          => ['card'],
                    'save_default_payment_method'   => 'off',
                ],
                'expand' => [
                    'latest_invoice.confirmation_secret',
                    'latest_invoice.payment_intent',
                    'latest_invoice.payment_intent.client_secret',
                    'pending_setup_intent',
                ],
            ]
        );

        $this->assertSame(['card'], $args['payment_settings']['payment_method_types']);
        $this->assertSame('on_subscription', $args['payment_settings']['save_default_payment_method']);
        $this->assertSame(
            [
                'latest_invoice.confirmation_secret',
                'pending_setup_intent',
            ],
            $args['expand']
        );
    }
}
