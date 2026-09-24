<?php

declare(strict_types=1);

namespace Tests\Integration\StripeSync;

use PPCart_Stripe_Sync;
use PPCart_Stripe_Webhook_Logger;
use Tests\Support\Integration\StripeSyncTestCase;

class WebhookLogTest extends StripeSyncTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    /**
     * @return object
     */
    private function sampleWebhookEvent()
    {
        return (object) array(
            'id' => 'evt_log',
            'type' => 'charge.refunded',
            'livemode' => false,
            'data' => (object) array(
                'object' => (object) array(
                    'object' => 'charge',
                    'id' => 'ch_log',
                ),
            ),
        );
    }

    public function test_IT_050_stripe_webhook_log_lazily_creates_its_randomized_file_option_on_first_write(): void
    {
        $this->resetWebhookLog();

        PPCart_Stripe_Sync::record_webhook_log(
            $this->sampleWebhookEvent(),
            'not_matched',
            'Could not match order.',
            array(
                'charge_id' => 'ch_log',
                'ignored' => array('nested'),
            )
        );

        $this->assertNotSame('', get_option('_ppcart_stripe_webhook_log_file', ''));
    }

    public function test_IT_051_recorded_webhook_outcome_is_persisted_and_readable_back_from_the_log_file(): void
    {
        $this->resetWebhookLog();

        PPCart_Stripe_Sync::record_webhook_log(
            $this->sampleWebhookEvent(),
            'not_matched',
            'Could not match order.',
            array('charge_id' => 'ch_log')
        );

        $entries = PPCart_Stripe_Webhook_Logger::read_entries();

        $this->assertNotEmpty($entries);
    }

    public function test_IT_052_log_entry_preserves_the_stripe_event_id_and_the_normalized_handling_status(): void
    {
        $this->resetWebhookLog();

        PPCart_Stripe_Sync::record_webhook_log(
            $this->sampleWebhookEvent(),
            'not_matched',
            'Could not match order.',
            array('charge_id' => 'ch_log')
        );

        $entries = PPCart_Stripe_Webhook_Logger::read_entries();

        $this->assertSame('evt_log', $entries[0]['event_id']);
        $this->assertSame('not_matched', $entries[0]['status']);
    }

    public function test_IT_053_each_log_line_carries_the_full_json_lines_schema_field_set(): void
    {
        $this->resetWebhookLog();

        PPCart_Stripe_Sync::record_webhook_log(
            $this->sampleWebhookEvent(),
            'not_matched',
            'Could not match order.',
            array('charge_id' => 'ch_log')
        );

        $entry = PPCart_Stripe_Webhook_Logger::read_entries()[0];

        $this->assertArrayHasKey('timestamp', $entry);
        $this->assertArrayHasKey('time_utc', $entry);
        $this->assertArrayHasKey('type', $entry);
        $this->assertArrayHasKey('object', $entry);
        $this->assertArrayHasKey('object_id', $entry);
        $this->assertArrayHasKey('livemode', $entry);
        $this->assertArrayHasKey('message', $entry);
        $this->assertArrayHasKey('context', $entry);
    }

    public function test_IT_054_scalar_diagnostic_context_values_are_retained_in_the_log_entry(): void
    {
        $this->resetWebhookLog();

        PPCart_Stripe_Sync::record_webhook_log(
            $this->sampleWebhookEvent(),
            'not_matched',
            'Could not match order.',
            array('charge_id' => 'ch_log')
        );

        $entry = PPCart_Stripe_Webhook_Logger::read_entries()[0];

        $this->assertArrayHasKey('charge_id', $entry['context']);
    }

    public function test_IT_055_nested_array_context_values_are_stripped_from_the_stored_log_entry(): void
    {
        $this->resetWebhookLog();

        PPCart_Stripe_Sync::record_webhook_log(
            $this->sampleWebhookEvent(),
            'not_matched',
            'Could not match order.',
            array(
                'charge_id' => 'ch_log',
                'ignored' => array('nested'),
            )
        );

        $entry = PPCart_Stripe_Webhook_Logger::read_entries()[0];

        $this->assertArrayNotHasKey('ignored', $entry['context']);
    }

    public function test_IT_056_a_generic_handled_row_is_suppressed_once_the_event_already_has_a_final_row(): void
    {
        $this->resetWebhookLog();
        $event = $this->sampleWebhookEvent();

        PPCart_Stripe_Sync::record_webhook_log($event, 'not_matched', 'Could not match order.');
        PPCart_Stripe_Sync::record_webhook_log($event, 'handled', 'Stripe webhook handler completed.');

        $this->assertCount(1, PPCart_Stripe_Webhook_Logger::read_entries());
    }

    public function test_IT_057_disabling_the_webhook_log_prevents_any_file_from_being_created(): void
    {
        $this->resetWebhookLog();
        update_option('_ppcart_enable_stripe_webhook_log', 0);

        PPCart_Stripe_Sync::record_webhook_log(
            $this->sampleWebhookEvent(),
            'applied',
            'Disabled write.'
        );

        $this->assertSame('', get_option('_ppcart_stripe_webhook_log_file', ''));
    }

    public function test_IT_058_events_with_status_ignored_are_not_logged_by_default(): void
    {
        $this->resetWebhookLog();

        PPCart_Stripe_Sync::record_webhook_log(
            $this->sampleWebhookEvent(),
            'ignored',
            'Ignored by default.'
        );

        $this->assertEmpty(PPCart_Stripe_Webhook_Logger::read_entries());
    }

    public function test_IT_059_ignored_events_are_logged_when_the_include_ignored_option_is_enabled(): void
    {
        $this->resetWebhookLog();
        update_option('_ppcart_stripe_webhook_log_include_ignored', 1);

        PPCart_Stripe_Sync::record_webhook_log(
            $this->sampleWebhookEvent(),
            'ignored',
            'Ignored when enabled.'
        );

        $entries = PPCart_Stripe_Webhook_Logger::read_entries();

        $this->assertNotEmpty($entries);
        $this->assertSame('ignored', $entries[0]['status']);
    }

    public function test_IT_060_skipped_is_a_first_class_allowed_webhook_log_status(): void
    {
        $this->resetWebhookLog();

        PPCart_Stripe_Sync::record_webhook_log(
            $this->sampleWebhookEvent(),
            'skipped',
            'Skipped event.'
        );

        $entries = PPCart_Stripe_Webhook_Logger::read_entries();

        $this->assertNotEmpty($entries);
        $this->assertSame('skipped', $entries[0]['status']);
    }

    public function test_IT_061_reader_hydrates_order_customer_display_context_from_a_stored_record_id(): void
    {
        $this->resetWebhookLog();

        $orderId = $this->createStripeOrder(
            array(
                'transaction_id' => 'ch_customer',
                'amount' => 200,
                'subscription_id' => 0,
                'email' => 'customer@example.test',
                'first_name' => 'First',
                'last_name' => 'Last',
            )
        );
        $subscriptionId = $this->createStripeSubscription(
            array(
                'subscription_id' => 'sub_customer',
            )
        );
        ppcart_update_post_meta($orderId, 'subscription_id', $subscriptionId);

        PPCart_Stripe_Sync::record_webhook_log(
            (object) array(
                'id' => 'evt_customer_context',
                'type' => 'charge.succeeded',
                'data' => (object) array(
                    'object' => (object) array(
                        'object' => 'charge',
                        'id' => 'ch_customer',
                    ),
                ),
            ),
            'handled',
            'Stripe webhook handler completed.',
            array(
                'record_id' => $orderId,
            )
        );

        $context = PPCart_Stripe_Webhook_Logger::read_entries()[0]['context'];

        $this->assertEquals(200, $context['amount']);
        $this->assertEquals($subscriptionId, $context['subscription_id']);
        $this->assertSame('customer@example.test', $context['customer_email']);
        $this->assertSame('First Last', $context['customer_name']);
    }

    public function test_IT_062_log_rotates_to_a_1_file_once_the_max_size_cap_is_exceeded(): void
    {
        $this->resetWebhookLog();

        for ($i = 0; $i < 4; $i++) {
            PPCart_Stripe_Sync::record_webhook_log(
                (object) array(
                    'id' => 'evt_rotate_' . $i,
                    'type' => 'charge.refunded',
                    'data' => (object) array(
                        'object' => (object) array(
                            'object' => 'charge',
                            'id' => 'ch_rotate_' . $i,
                        ),
                    ),
                ),
                'applied',
                'Rotation row.',
                array(
                    'padding' => str_repeat('x', 500),
                )
            );
        }

        $rotatedFile = PPCART_STRIPE_WEBHOOK_LOG_DIR . '/' . get_option('_ppcart_stripe_webhook_log_file') . '.1';

        $this->assertFileExists($rotatedFile);
    }

    public function test_IT_063_clearing_the_log_deletes_rotated_files_not_just_the_active_one(): void
    {
        $this->resetWebhookLog();

        for ($i = 0; $i < 4; $i++) {
            PPCart_Stripe_Sync::record_webhook_log(
                (object) array(
                    'id' => 'evt_rotate_' . $i,
                    'type' => 'charge.refunded',
                    'data' => (object) array(
                        'object' => (object) array(
                            'object' => 'charge',
                            'id' => 'ch_rotate_' . $i,
                        ),
                    ),
                ),
                'applied',
                'Rotation row.',
                array(
                    'padding' => str_repeat('x', 500),
                )
            );
        }

        $rotatedFile = PPCART_STRIPE_WEBHOOK_LOG_DIR . '/' . get_option('_ppcart_stripe_webhook_log_file') . '.1';

        PPCart_Stripe_Webhook_Logger::clear_log_files();

        $this->assertFileNotExists($rotatedFile);
    }

    public function test_IT_064_security_an_unsigned_browser_get_is_never_logged_as_a_rejected_request(): void
    {
        $this->resetWebhookLog();
        $_SERVER = array(
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR' => '127.0.0.1',
        );

        PPCart_Stripe_Webhook_Logger::record_rejected_request('missing_signature');

        $this->assertEmpty(PPCart_Stripe_Webhook_Logger::read_entries());
    }

    public function test_IT_065_security_a_json_post_missing_its_signature_is_logged_as_a_rejected_request(): void
    {
        $this->resetWebhookLog();
        $_SERVER = array(
            'REQUEST_METHOD' => 'POST',
            'CONTENT_TYPE' => 'application/json',
            'CONTENT_LENGTH' => 50,
            'REMOTE_ADDR' => '127.0.0.1',
        );

        PPCart_Stripe_Webhook_Logger::record_rejected_request('missing_signature');

        $entries = PPCart_Stripe_Webhook_Logger::read_entries();

        $this->assertNotEmpty($entries);
        $this->assertSame('rejected', $entries[0]['status']);
    }

    public function test_IT_066_security_rejected_request_logging_never_persists_the_raw_request_body(): void
    {
        $this->resetWebhookLog();
        $_SERVER = array(
            'REQUEST_METHOD' => 'POST',
            'CONTENT_TYPE' => 'application/json',
            'CONTENT_LENGTH' => 50,
            'REMOTE_ADDR' => '127.0.0.1',
        );

        PPCart_Stripe_Webhook_Logger::record_rejected_request('missing_signature');

        $this->assertArrayNotHasKey('body', PPCart_Stripe_Webhook_Logger::read_entries()[0]['context']);
    }

    public function test_IT_067_security_repeated_rejected_requests_are_rate_limited_to_one_entry_per_window(): void
    {
        $this->resetWebhookLog();
        $_SERVER = array(
            'REQUEST_METHOD' => 'POST',
            'CONTENT_TYPE' => 'application/json',
            'CONTENT_LENGTH' => 50,
            'REMOTE_ADDR' => '127.0.0.1',
        );

        PPCart_Stripe_Webhook_Logger::record_rejected_request('missing_signature');
        PPCart_Stripe_Webhook_Logger::record_rejected_request('missing_signature');

        $this->assertCount(1, PPCart_Stripe_Webhook_Logger::read_entries());
    }

    public function test_IT_068_reader_is_resilient_to_corrupt_json_lines_and_surfaces_a_placeholder_row(): void
    {
        $this->resetWebhookLog();
        update_option('_ppcart_stripe_webhook_log_file', 'invalid-line-stripe-webhook.log');

        if (! is_dir(PPCART_STRIPE_WEBHOOK_LOG_DIR)) {
            wp_mkdir_p(PPCART_STRIPE_WEBHOOK_LOG_DIR);
        }

        file_put_contents(PPCART_STRIPE_WEBHOOK_LOG_DIR . '/invalid-line-stripe-webhook.log', "{bad json\n");

        $entries = PPCart_Stripe_Webhook_Logger::read_entries();

        $this->assertNotEmpty($entries);
        $this->assertSame('Invalid log line.', $entries[0]['message']);
    }
}
