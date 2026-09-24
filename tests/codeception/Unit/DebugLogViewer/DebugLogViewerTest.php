<?php

namespace unit\DebugLogViewer;

use Codeception\Test\Unit;
use PPCart_Debug_Logger;
use PPCart_Debug_Log_Viewer;
use Tests\Support\WordPressStubContext;
use UnitTester;

class DebugLogViewerTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var string
     */
    private $debugLogDir;

    protected function _before(): void
    {
        if (! defined('PPCART_BASE_DIR')) {
            define('PPCART_BASE_DIR', PPCART_PLUGIN_ROOT);
        }

        if (! defined('PPCART_DEBUG_LOG_DIR')) {
            $this->debugLogDir = sys_get_temp_dir() . '/ppcart-debug-log-test-' . uniqid('', true);
            define('PPCART_DEBUG_LOG_DIR', $this->debugLogDir);
        } else {
            $this->debugLogDir = PPCART_DEBUG_LOG_DIR;
            $this->clearDirectory($this->debugLogDir);
        }

        if (! defined('PPCART_DEBUG_LOG_MAX_BYTES')) {
            define('PPCART_DEBUG_LOG_MAX_BYTES', 520);
        }

        WordPressStubContext::setState(
            'options',
            array(
                '_ppcart_enable_debug' => 1,
            )
        );
        WordPressStubContext::setState('actions', array());

        WordPressStubContext::set(
            'add_action',
            function ($hook, $callback) {
                $actions = WordPressStubContext::getState('actions', array());
                $actions[$hook] = $callback;
                WordPressStubContext::setState('actions', $actions);

                return true;
            }
        );
        WordPressStubContext::set('add_filter', function () {
            return true;
        });
        WordPressStubContext::set(
            'get_option',
            function ($key, $default = false) {
                $options = WordPressStubContext::getState('options', array());

                return isset($options[$key]) ? $options[$key] : $default;
            }
        );
        WordPressStubContext::set(
            'update_option',
            function ($key, $value) {
                $options = WordPressStubContext::getState('options', array());
                $options[$key] = $value;
                WordPressStubContext::setState('options', $options);

                return true;
            }
        );

        $this->resetDebugLoggerSingleton();

        if (! class_exists(PPCart_Debug_Log_Viewer::class, false)) {
            require_once PPCART_PLUGIN_ROOT . 'includes/logging/class-ppcart-debug-log-viewer.php';
        }

        if (! class_exists(PPCart_Debug_Logger::class, false)) {
            require_once PPCART_PLUGIN_ROOT . 'includes/logging/class-ppcart-debug-logger.php';
        }
    }

    protected function _after(): void
    {
        $this->clearDirectory($this->debugLogDir);
        WordPressStubContext::clear();
        parent::_after();
    }

    public function test_UT_039_standard_debug_log_line_parses_level(): void
    {
        $parsed = PPCart_Debug_Log_Viewer::parse_line(
            '[05/12/2026 6:31 AM] - SUCCESS : Order #627 saved successfully with status paid.'
        );

        $this->assertSame('SUCCESS', $parsed['level']);
    }

    public function test_UT_040_standard_debug_log_line_detects_workflow(): void
    {
        $parsed = PPCart_Debug_Log_Viewer::parse_line(
            '[05/12/2026 6:31 AM] - SUCCESS : Order #627 saved successfully with status paid.'
        );

        $this->assertSame('Order', $parsed['workflow']);
    }

    public function test_UT_041_standard_debug_log_line_detects_order_id(): void
    {
        $parsed = PPCart_Debug_Log_Viewer::parse_line(
            '[05/12/2026 6:31 AM] - SUCCESS : Order #627 saved successfully with status paid.'
        );

        $this->assertSame(627, $parsed['order_id']);
    }

    public function test_UT_042_malformed_debug_log_line_becomes_unknown(): void
    {
        $malformed = PPCart_Debug_Log_Viewer::parse_line('A malformed raw debug message.');

        $this->assertSame('UNKNOWN', $malformed['level']);
    }

    public function test_UT_043_section_breaks_are_skipped(): void
    {
        $this->assertFalse(
            PPCart_Debug_Log_Viewer::parse_line('----------------------------------------------------------')
        );
    }

    public function test_UT_044_structured_event_context_drives_workflow_detection(): void
    {
        $with_context = PPCart_Debug_Log_Viewer::parse_line(
            '[05/12/2026 6:32 AM] - STATUS : Stripe PaymentIntent created successfully. context={"event":"checkout.payment_intent.created","order_id":627,"client_secret":"pi_secret","charge_id":"ch_123"}'
        );

        $this->assertSame('Checkout', $with_context['workflow']);
    }

    public function test_UT_045_structured_context_redacts_client_secrets(): void
    {
        $with_context = PPCart_Debug_Log_Viewer::parse_line(
            '[05/12/2026 6:32 AM] - STATUS : Stripe PaymentIntent created successfully. context={"event":"checkout.payment_intent.created","order_id":627,"client_secret":"pi_secret","charge_id":"ch_123"}'
        );

        $this->assertSame('[redacted]', $with_context['context']['client_secret']);
    }

    public function test_UT_046_structured_context_exposes_safe_stripe_ids(): void
    {
        $with_context = PPCart_Debug_Log_Viewer::parse_line(
            '[05/12/2026 6:32 AM] - STATUS : Stripe PaymentIntent created successfully. context={"event":"checkout.payment_intent.created","order_id":627,"client_secret":"pi_secret","charge_id":"ch_123"}'
        );

        $this->assertContains('ch_123', $with_context['stripe_ids']);
    }

    public function test_UT_047_structured_context_detects_checkout_flow_id(): void
    {
        $with_flow = PPCart_Debug_Log_Viewer::parse_line(
            '[05/12/2026 6:32 AM] - STATUS : Checkout form validation started. context={"event":"checkout.validation.started","flow_id":"checkout-flow-1","product_id":45}'
        );

        $this->assertSame('checkout-flow-1', $with_flow['flow_id']);
    }

    public function test_UT_048_stripe_subscription_ids_are_not_mistaken_for_local_subscription_ids(): void
    {
        $stripe_subscription = PPCart_Debug_Log_Viewer::parse_line(
            '[05/12/2026 6:32 AM] - SUCCESS : Stripe subscription created - ID sub_1TWCmcCgxOWS1li105rAItSD'
        );

        $this->assertSame(0, $stripe_subscription['subscription_id']);
    }

    public function test_UT_049_stripe_subscription_ids_still_appear_in_stripe_details(): void
    {
        $stripe_subscription = PPCart_Debug_Log_Viewer::parse_line(
            '[05/12/2026 6:32 AM] - SUCCESS : Stripe subscription created - ID sub_1TWCmcCgxOWS1li105rAItSD'
        );

        $this->assertContains('sub_1TWCmcCgxOWS1li105rAItSD', $stripe_subscription['stripe_ids']);
    }

    public function test_UT_050_embedded_json_detects_product_id(): void
    {
        $with_json = PPCart_Debug_Log_Viewer::parse_line(
            '[05/12/2026 6:33 AM] - WARNING : Checkout form validation failed. {"product_id":45,"nonce":"abc"}'
        );

        $this->assertSame(45, $with_json['product_id']);
    }

    public function test_UT_051_embedded_json_redacts_nonce_values(): void
    {
        $with_json = PPCart_Debug_Log_Viewer::parse_line(
            '[05/12/2026 6:33 AM] - WARNING : Checkout form validation failed. {"product_id":45,"nonce":"abc"}'
        );

        $this->assertSame('[redacted]', $with_json['context']['nonce']);
    }

    public function test_UT_052_legacy_log_debug_writes_parseable_raw_lines(): void
    {
        $logger = new PPCart_Debug_Logger();
        $logger->log_debug('Saving order after checkout validation.', 1);
        $entries = PPCart_Debug_Log_Viewer::read_entries(array('limit' => 10));

        $this->assertArrayHasKey(0, $entries);
        $this->assertSame('Saving order after checkout validation.', $entries[0]['message']);
    }

    public function test_UT_053_log_event_writes_structured_context_for_viewer(): void
    {
        $logger = new PPCart_Debug_Logger();
        $logger->log_event(
            'checkout.order.saved',
            'Order saved successfully.',
            array(
                'order_id' => 701,
                'status' => 'paid',
                'client_secret' => 'secret_value',
            ),
            0
        );
        $entries = PPCart_Debug_Log_Viewer::read_entries(array('limit' => 10, 'search' => '701'));

        $this->assertArrayHasKey(0, $entries);
        $this->assertSame(701, $entries[0]['order_id']);
    }

    public function test_UT_054_log_event_redacts_sensitive_structured_context_before_writing(): void
    {
        $logger = new PPCart_Debug_Logger();
        $logger->log_event(
            'checkout.order.saved',
            'Order saved successfully.',
            array(
                'order_id' => 701,
                'status' => 'paid',
                'client_secret' => 'secret_value',
            ),
            0
        );
        $entries = PPCart_Debug_Log_Viewer::read_entries(array('limit' => 10, 'search' => '701'));

        $this->assertSame('[redacted]', $entries[0]['context']['client_secret']);
    }

    public function test_UT_055_log_event_adds_request_flow_id_for_grouping(): void
    {
        $logger = new PPCart_Debug_Logger();
        $logger->log_event(
            'checkout.order.saved',
            'Order saved successfully.',
            array(
                'order_id' => 701,
                'status' => 'paid',
                'client_secret' => 'secret_value',
            ),
            0
        );
        $entries = PPCart_Debug_Log_Viewer::read_entries(array('limit' => 10, 'search' => '701'));

        $this->assertNotEmpty($entries[0]['flow_id']);
    }

    public function test_UT_056_debug_log_viewer_groups_events_by_flow_id(): void
    {
        $group_a = $this->getFlowGroup('flow:unit-checkout-1');

        $this->assertIsArray($group_a);
    }

    public function test_UT_057_flow_group_keeps_related_checkout_events_together(): void
    {
        $group_a = $this->getFlowGroup('flow:unit-checkout-1');

        $this->assertSame(2, $group_a['event_count']);
    }

    public function test_UT_058_flow_group_title_explains_checkout_save_phase(): void
    {
        $group_a = $this->getFlowGroup('flow:unit-checkout-1');

        $this->assertSame('Checkout saved Order #702', $group_a['title']);
    }

    public function test_UT_059_flow_group_timeline_reads_oldest_to_newest(): void
    {
        $group_a = $this->getFlowGroup('flow:unit-checkout-1');

        $this->assertSame('Checkout form validation started.', $group_a['entries'][0]['message']);
    }

    public function test_UT_060_flow_group_keeps_final_checkout_event_last(): void
    {
        $group_a = $this->getFlowGroup('flow:unit-checkout-1');

        $this->assertSame('Order #702 saved successfully.', $group_a['entries'][1]['message']);
    }

    public function test_UT_061_debug_log_groups_are_sorted_newest_first(): void
    {
        $groups = $this->createFlowGroupedEntries();

        $this->assertSame('flow:unit-checkout-2', $groups[0]['key']);
    }

    public function test_UT_062_payment_setup_group_title_explains_payment_intent_phase(): void
    {
        $payment_setup_entry = PPCart_Debug_Log_Viewer::parse_line(
            '[05/12/2026 6:34 AM] - SUCCESS : Stripe PaymentIntent created successfully. context={"event":"checkout.payment_intent.created","flow_id":"unit-payment-setup","order_id":704}'
        );
        $payment_setup_entry['sequence'] = 1;
        $payment_setup_groups = PPCart_Debug_Log_Viewer::group_entries(array($payment_setup_entry));

        $this->assertSame('Payment setup for Order #704', $payment_setup_groups[0]['title']);
    }

    public function test_UT_063_payment_confirmation_group_title_explains_paid_transition(): void
    {
        $payment_confirmed_entries = array(
            PPCart_Debug_Log_Viewer::parse_line(
                '[05/12/2026 6:35 AM] - SUCCESS : Order #705 save started with status paid. context={"event":"order.save.started","flow_id":"unit-payment-confirmed","order_id":705,"status":"paid"}'
            ),
            PPCart_Debug_Log_Viewer::parse_line(
                '[05/12/2026 6:35 AM] - SUCCESS : Order #705 updated in WordPress. context={"event":"order.save.updated","flow_id":"unit-payment-confirmed","order_id":705,"previous_status":"pending-payment","status":"paid"}'
            ),
        );
        $payment_confirmed_entries[0]['sequence'] = 1;
        $payment_confirmed_entries[1]['sequence'] = 2;
        $payment_confirmed_groups = PPCart_Debug_Log_Viewer::group_entries($payment_confirmed_entries);

        $this->assertSame('Payment confirmed for Order #705', $payment_confirmed_groups[0]['title']);
    }

    public function test_UT_064_paid_to_paid_transaction_change_title_explains_stripe_detail_sync(): void
    {
        $transaction_update_entries = array(
            PPCart_Debug_Log_Viewer::parse_line(
                '[05/12/2026 6:36 AM] - SUCCESS : Existing order #706 loaded before save. context={"event":"order.save.existing_loaded","flow_id":"unit-transaction-update","order_id":706,"previous_status":"paid","next_status":"paid","previous_transaction_id":"pi_123","next_transaction_id":"ch_123"}'
            ),
            PPCart_Debug_Log_Viewer::parse_line(
                '[05/12/2026 6:36 AM] - SUCCESS : Order #706 updated in WordPress. context={"event":"order.save.updated","flow_id":"unit-transaction-update","order_id":706,"previous_status":"paid","status":"paid","previous_transaction_id":"pi_123","transaction_id":"ch_123"}'
            ),
        );
        $transaction_update_entries[0]['sequence'] = 1;
        $transaction_update_entries[1]['sequence'] = 2;
        $transaction_update_groups = PPCart_Debug_Log_Viewer::group_entries($transaction_update_entries);

        $this->assertSame(
            'Stripe payment details updated for Order #706',
            $transaction_update_groups[0]['title']
        );
    }

    public function test_UT_065_static_debug_logger_writes_when_debug_logging_is_enabled(): void
    {
        PPCart_Debug_Logger::log_debug_st('Static logger writes when debug is enabled.', 1);
        $entries = PPCart_Debug_Log_Viewer::read_entries(
            array(
                'limit' => 10,
                'search' => 'Static logger writes',
            )
        );

        $this->assertArrayHasKey(0, $entries);
    }

    public function test_UT_066_debug_log_rotates_when_max_size_is_exceeded(): void
    {
        $logger = new PPCart_Debug_Logger();

        for ($i = 0; $i < 8; $i++) {
            $logger->log_debug('Rotation row ' . $i . ' ' . str_repeat('x', 140), 1);
        }

        $file_name = get_option('_ppcart_log_file');
        $rotated_file = trailingslashit(PPCART_DEBUG_LOG_DIR) . $file_name . '.1';

        $this->assertFileExists($rotated_file);
    }

    public function test_UT_067_debug_log_reset_clears_rotated_files(): void
    {
        $logger = new PPCart_Debug_Logger();

        for ($i = 0; $i < 8; $i++) {
            $logger->log_debug('Rotation row ' . $i . ' ' . str_repeat('x', 140), 1);
        }

        $file_name = get_option('_ppcart_log_file');
        $rotated_file = trailingslashit(PPCART_DEBUG_LOG_DIR) . $file_name . '.1';

        $logger->reset_log_file();

        $this->assertFileNotExists($rotated_file);
    }

    public function test_UT_068_debug_log_reset_keeps_reset_marker_as_latest_entry(): void
    {
        $logger = new PPCart_Debug_Logger();

        for ($i = 0; $i < 8; $i++) {
            $logger->log_debug('Rotation row ' . $i . ' ' . str_repeat('x', 140), 1);
        }

        $logger->reset_log_file();
        $entries = PPCart_Debug_Log_Viewer::read_entries(array('limit' => 10));

        $this->assertCount(1, $entries);
        $this->assertNotFalse(strpos($entries[0]['message'], 'Log File Reset'));
    }

    /**
     * @return void
     */
    private function resetDebugLoggerSingleton(): void
    {
        if (! class_exists(PPCart_Debug_Logger::class, false)) {
            return;
        }

        $reflection = new \ReflectionClass(PPCart_Debug_Logger::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null);

        if ($reflection->hasProperty('current_flow_id')) {
            $flow_id = $reflection->getProperty('current_flow_id');
            $flow_id->setAccessible(true);
            $flow_id->setValue(null);
        }
    }

    /**
     * @param string $flow_key
     * @return array<string, mixed>|null
     */
    private function getFlowGroup(string $flow_key)
    {
        $groups = $this->createFlowGroupedEntries();

        foreach ($groups as $group) {
            if ($flow_key === $group['key']) {
                return $group;
            }
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function createFlowGroupedEntries()
    {
        $logger = new PPCart_Debug_Logger();
        $logger->log_event(
            'checkout.validation.started',
            'Checkout form validation started.',
            array(
                'flow_id' => 'unit-checkout-1',
                'product_id' => 88,
            ),
            1
        );
        $logger->log_event(
            'checkout.order.saved',
            'Order #702 saved successfully.',
            array(
                'flow_id' => 'unit-checkout-1',
                'order_id' => 702,
            ),
            1
        );
        $logger->log_event(
            'checkout.order.saved',
            'Order #703 saved successfully.',
            array(
                'flow_id' => 'unit-checkout-2',
                'order_id' => 703,
            ),
            1
        );

        $entries = PPCart_Debug_Log_Viewer::read_entries(array('limit' => 10));

        return PPCart_Debug_Log_Viewer::group_entries($entries);
    }

    /**
     * @param string $directory
     * @return void
     */
    private function clearDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            wp_mkdir_p($directory);
            return;
        }

        $items = scandir($directory);
        if (! is_array($items)) {
            return;
        }

        foreach ($items as $item) {
            if ('.' === $item || '..' === $item) {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
                continue;
            }

            @unlink($path);
        }
    }

    /**
     * @param string $directory
     * @return void
     */
    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $items = scandir($directory);
        if (! is_array($items)) {
            return;
        }

        foreach ($items as $item) {
            if ('.' === $item || '..' === $item) {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
                continue;
            }

            @unlink($path);
        }

        @rmdir($directory);
    }
}
