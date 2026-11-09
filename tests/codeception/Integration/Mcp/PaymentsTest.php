<?php

declare(strict_types=1);

namespace Tests\Integration\Mcp;

use Tests\Support\Integration\McpTestCase;

require_once dirname(__DIR__, 2) . '/Support/Integration/McpTestCase.php';

class PaymentsTest extends McpTestCase
{
    public function test_manual_refund_preview_validation_save_and_replay(): void
    {
        update_option('_ppcart_currency', 'USD');
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('order'), 'post_author' => get_current_user_id()]);
        foreach (['status' => 'paid', 'payment_status' => 'paid', 'pay_method' => 'cod', 'amount' => '20', 'currency' => 'USD'] as $key => $value) {
            ppcart_update_post_meta($id, $key, $value);
        }
        $before = $this->execute('get-record', ['kind' => 'order', 'id' => $id]);
        $patch = ['id' => $id, 'revision' => $before['revision'], 'amount' => '5.00', 'dry_run' => true];
        $preview = $this->execute('refund-order', $patch);
        $this->assertNotWPError($preview);
        $this->assertTrue($preview['dry_run']);
        $this->assertSame(0.0, \PPCart_Order_Refunds::get_refund_total($id));
        foreach (['0', '-1', '21', '0.001', '1e1', 'NaN'] as $invalid) {
            $this->assertWPError($this->execute('refund-order', array_merge($patch, ['amount' => $invalid])));
        }
        $patch['dry_run'] = false;
        add_filter('pre_wp_mail', '__return_true');
        try {
            $result = $this->execute('refund-order', $patch);
        } finally {
            remove_filter('pre_wp_mail', '__return_true');
        }
        $this->assertNotWPError($result);
        $this->assertTrue($result['refunded']);
        $this->assertSame(5.0, \PPCart_Order_Refunds::get_refund_total($id));
        $this->assertWPError($this->execute('refund-order', $patch));
        $this->assertSame(5.0, \PPCart_Order_Refunds::get_refund_total($id));
    }

    public function test_payment_lock_and_denied_capabilities_block_writes(): void
    {
        update_option('_ppcart_currency', 'USD');
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('order'), 'post_author' => get_current_user_id()]);
        foreach (['status' => 'paid', 'pay_method' => 'cod', 'amount' => '20', 'currency' => 'USD'] as $key => $value) {
            ppcart_update_post_meta($id, $key, $value);
        }
        $before = $this->execute('get-record', ['kind' => 'order', 'id' => $id]);
        $patch = ['id' => $id, 'revision' => $before['revision'], 'amount' => '5.00'];
        $key = '_ppcart_mcp_payment_lock_' . $id;
        add_option($key, 'another-operation', '', false);
        try {
            $result = $this->execute('refund-order', $patch);
            $this->assertWPError($result);
            $this->assertSame('ppcart_mcp_payment_locked', $result->get_error_code());
        } finally {
            delete_option($key);
        }
        wp_get_current_user()->add_cap('manage_options', false);
        wp_get_current_user()->add_cap(ppcart_live_cap('manage_orders'), false);
        $this->assertWPError($this->execute('refund-order', $patch));
        $this->assertSame(0.0, \PPCart_Order_Refunds::get_refund_total($id));
    }

    public function test_cancel_preview_uses_the_persisted_gateway_identity(): void
    {
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('subscription'), 'post_author' => get_current_user_id()]);
        foreach (['status' => 'active', 'sub_status' => 'active', 'pay_method' => 'stripe', 'subscription_id' => 'sub_test'] as $key => $value) {
            ppcart_update_post_meta($id, $key, $value);
        }
        $before = $this->execute('get-record', ['kind' => 'subscription', 'id' => $id]);
        $preview = $this->execute('cancel-subscription', [
            'id' => $id, 'revision' => $before['revision'], 'timing' => 'period_end', 'dry_run' => true,
        ]);
        $this->assertNotWPError($preview);
        $this->assertTrue($preview['dry_run']);
        $this->assertSame('active', ppcart_get_post_meta($id, 'status', true));
        $this->assertWPError($this->execute('cancel-subscription', [
            'id' => $id, 'revision' => $before['revision'], 'timing' => 'immediate',
            'subscription_id' => 'unrelated-subscription', 'dry_run' => true,
        ]));
    }
}
