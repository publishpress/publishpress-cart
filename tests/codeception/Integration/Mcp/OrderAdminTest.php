<?php

declare(strict_types=1);

namespace Tests\Integration\Mcp;

use Tests\Support\Integration\McpTestCase;

require_once dirname(__DIR__, 2) . '/Support/Integration/McpTestCase.php';

class OrderAdminTest extends McpTestCase
{
    public function test_native_customer_patch_preserves_omissions_and_stripe_fields(): void
    {
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('order')]);
        foreach (['firstname' => 'First', 'lastname' => 'Last', 'email' => 'old@example.org', 'country' => 'US',
            'status' => 'paid', 'pay_method' => 'stripe', 'transaction_id' => 'pi_private', 'amount' => '20', 'currency' => 'USD'] as $key => $value) {
            ppcart_update_post_meta($id, $key, $value);
        }
        ppcart_update_post_meta($id, 'unrelated', 'Keep');
        $before = $this->execute('get-order-details', ['id' => $id]);
        $this->assertNotWPError($before);
        $this->assertStringNotContainsString('pi_private', wp_json_encode($before));
        $patch = ['id' => $id, 'revision' => $before['revision'], 'changes' => (object) ['firstname' => 'Changed', 'address1' => 'New address'], 'dry_run' => true];
        $this->assertNotWPError($this->execute('update-order-details', $patch));
        $this->assertSame('First', ppcart_get_post_meta($id, 'firstname', true));
        $patch['dry_run'] = false;
        $saved = $this->execute('update-order-details', $patch);
        $this->assertNotWPError($saved);
        $this->assertSame('Changed', ppcart_get_post_meta($id, 'firstname', true));
        $this->assertSame('Last', ppcart_get_post_meta($id, 'lastname', true));
        $this->assertSame('pi_private', ppcart_get_post_meta($id, 'transaction_id', true));
        $this->assertSame('Keep', ppcart_get_post_meta($id, 'unrelated', true));
        $this->assertWPError($this->execute('update-order-details', $patch));
        $this->assertWPError($this->execute('update-order-details', ['id' => $id, 'revision' => $saved['revision'], 'changes' => (object) ['firstname' => 'Must not save', 'status' => 'failed']]));
        $this->assertSame('Changed', ppcart_get_post_meta($id, 'firstname', true));
    }

    public function test_manual_order_uses_native_pricing_without_request_mutation(): void
    {
        global $ppcart_product;
        update_option('_ppcart_currency', 'USD');
        $product = self::factory()->post->create(['post_type' => ppcart_live_post_type('product'), 'post_status' => 'publish']);
        $plans = $this->execute('get-payment-plans', ['id' => $product]);
        add_filter('ppcart_process_stripe_products', '__return_false');
        try {
            $saved = $this->execute('set-payment-plans', ['id' => $product, 'revision' => $plans['revision'], 'plans' => [(object) [
                'option_id' => 'main', 'option_name' => 'Main', 'product_type' => '', 'price' => '12',
            ]]]);
        } finally {
            remove_filter('ppcart_process_stripe_products', '__return_false');
        }
        $this->assertNotWPError($saved);
        $old_post = $_POST;
        $old_product = $ppcart_product;
        $changes = (object) ['product_id' => $product, 'plan_id' => 'main', 'firstname' => 'Manual', 'lastname' => 'Customer',
            'email' => 'manual@example.org', 'user_account' => 0, 'country' => 'US'];
        $preview = $this->execute('create-manual-order', ['changes' => $changes, 'dry_run' => true]);
        $this->assertNotWPError($preview);
        add_filter('pre_wp_mail', '__return_true');
        try {
            $created = $this->execute('create-manual-order', ['changes' => $changes]);
        } finally {
            remove_filter('pre_wp_mail', '__return_true');
        }
        $this->assertNotWPError($created);
        $order = new \PPCart_Order($created['id']);
        $this->assertSame('cod', $order->pay_method);
        $this->assertSame('pending-payment', $order->status);
        $this->assertSame(12.0, (float) $order->amount);
        $this->assertSame(0, (int) $order->user_account);
        $this->assertSame($old_post, $_POST);
        $this->assertSame($old_product, $ppcart_product);
    }
}
