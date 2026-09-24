<?php

declare(strict_types=1);

namespace Tests\Integration\Prefix;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Stripe_Sync;

class TransientsTest extends WPTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clearSliceTransients();
    }

    protected function tearDown(): void
    {
        $this->clearSliceTransients();
        parent::tearDown();
    }

    /**
     * @test-id IT-316
     */
    public function test_IT_316_stripe_event_dedupe_writes_canonical_transient(): void
    {
        $event = (object) [ 'id' => 'evt_prefix_slice24' ];

        $this->assertTrue(PPCart_Stripe_Sync::event_dedupe_gate($event));
        $this->assertNotFalse(get_transient('ppcart_stripe_evt_evt_prefix_slice24'));
        $this->assertFalse(get_transient('sc_stripe_evt_evt_prefix_slice24'));
    }

    /**
     * @test-id IT-316
     */
    public function test_IT_316_hosted_session_resolve_reads_canonical_transient(): void
    {
        set_transient(
            'ppcart_hosted_session_cs_prefix_slice24',
            [
                'order_id'     => 4242,
                'gateway_mode' => 'test',
            ],
            HOUR_IN_SECONDS
        );

        $context = ppcart_hosted_checkout_resolve_return_context('cs_prefix_slice24');

        $this->assertSame(4242, $context['order_id']);
        $this->assertSame('test', $context['gateway_mode']);
        $this->assertSame('transient', $context['source']);
    }

    private function clearSliceTransients(): void
    {
        $keys = [
            'ppcart_stripe_evt_evt_prefix_slice24',
            'sc_stripe_evt_evt_prefix_slice24',
            'ppcart_hosted_session_cs_prefix_slice24',
        ];

        foreach ($keys as $key) {
            delete_transient($key);
        }
    }
}
