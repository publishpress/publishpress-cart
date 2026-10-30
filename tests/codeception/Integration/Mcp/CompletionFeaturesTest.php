<?php

declare(strict_types=1);
namespace Tests\Integration\Mcp;
use Tests\Support\Integration\McpTestCase;
require_once dirname(__DIR__, 2) . '/Support/Integration/McpTestCase.php';
class CompletionFeaturesTest extends McpTestCase
{
    public function test_catalog_slug_and_featured_image_use_native_media_and_permissions(): void
    {
        $upload = wp_upload_bits('mcp-image.png', null, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a3ioAAAAASUVORK5CYII='));
        $image = wp_insert_attachment(['post_title' => 'Image', 'post_mime_type' => 'image/png', 'post_status' => 'inherit', 'post_author' => get_current_user_id()], $upload['file']);
        try {
            $create = ['kind' => 'product', 'title' => 'Catalog', 'slug' => 'catalog-native', 'featured_image_id' => $image, 'dry_run' => true];
            $this->assertNotWPError($this->execute('create-record', $create));
            $create['dry_run'] = false;
            $saved = $this->execute('create-record', $create);
            $this->assertNotWPError($saved);
            $this->assertSame('catalog-native', $saved['slug']);
            $this->assertSame($image, $saved['featured_image_id']);
            $deny = static function ($caps, $cap, $user, $args) use ($image) { return 'read_post' === $cap && (int) ($args[0] ?? 0) === $image ? ['do_not_allow'] : $caps; };
            add_filter('map_meta_cap', $deny, 10, 4);
            try {
                $this->assertWPError($this->execute('update-record', ['kind' => 'product', 'id' => $saved['id'], 'revision' => $saved['revision'], 'slug' => 'must-not-save', 'featured_image_id' => $image]));
                $this->assertSame('catalog-native', get_post_field('post_name', $saved['id']));
            } finally { remove_filter('map_meta_cap', $deny, 10); }
            $read = $this->execute('get-record', ['kind' => 'product', 'id' => $saved['id']]);
            $removed = $this->execute('update-record', ['kind' => 'product', 'id' => $saved['id'], 'revision' => $read['revision'], 'featured_image_id' => 0]);
            $this->assertNotWPError($removed);
            $this->assertSame(0, $removed['featured_image_id']);
        } finally { wp_delete_attachment($image, true); }
    }

    public function test_actual_confirmation_resend_uses_native_order_and_never_sends_in_preview(): void
    {
        update_option('_ppcart_email_confirmation_enable', 1);
        $product = self::factory()->post->create(['post_type' => ppcart_live_post_type('product')]);
        $order = self::factory()->post->create(['post_type' => ppcart_live_post_type('order')]);
        foreach (['product_id' => $product, 'status' => 'paid', 'email' => 'synthetic@example.org', 'firstname' => 'Synthetic', 'lastname' => 'Customer', 'amount' => '12', 'currency' => 'USD'] as $key => $value) { ppcart_update_post_meta($order, $key, $value); }
        $sent = [];
        $mail = static function ($result, $args) use (&$sent) { $sent[] = $args; return true; };
        add_filter('pre_wp_mail', $mail, 10, 2);
        try {
            $read = $this->execute('get-order-details', ['id' => $order]);
            $this->assertNotWPError($read);
            $request = ['id' => $order, 'revision' => $read['revision'], 'dry_run' => true];
            $this->assertNotWPError($this->execute('resend-order-confirmation', $request));
            $this->assertSame([], $sent);
            $request['dry_run'] = false;
            update_option('_ppcart_email_confirmation_enable', 0);
            $this->assertWPError($this->execute('resend-order-confirmation', $request));
            $this->assertSame([], $sent);
            update_option('_ppcart_email_confirmation_enable', 1);
            $deny = static function ($caps, $cap, $user, $args) use ($product) {
                return 'read_post' === $cap && (int) ($args[0] ?? 0) === $product ? ['do_not_allow'] : $caps;
            };
            add_filter('map_meta_cap', $deny, 10, 4);
            try {
                $this->assertWPError($this->execute('resend-order-confirmation', $request));
                $this->assertSame([], $sent);
            } finally {
                remove_filter('map_meta_cap', $deny, 10);
            }
            $this->assertNotWPError($this->execute('resend-order-confirmation', $request));
            $this->assertNotEmpty($sent);
            ppcart_update_post_meta($order, 'email', 'changed@example.org');
            $count = count($sent);
            $this->assertWPError($this->execute('resend-order-confirmation', $request));
            $this->assertCount($count, $sent);
        } finally { remove_filter('pre_wp_mail', $mail, 10); }
    }

    public function test_unknown_payment_operation_cannot_be_cleared_until_native_outcome_is_confirmed(): void
    {
        $order = self::factory()->post->create(['post_type' => ppcart_live_post_type('order')]);
        foreach (['status' => 'paid', 'pay_method' => 'cod', 'amount' => '20', 'currency' => 'USD'] as $key => $value) { ppcart_update_post_meta($order, $key, $value); }
        add_option('_ppcart_mcp_payment_lock_' . $order, ['kind' => 'order', 'token' => 'private-operation', 'state' => 'unconfirmed', 'amount' => '5', 'refund_total' => 0, 'created' => time(), 'transition' => null, 'timing' => null]);
        $read = $this->execute('get-payment-operation', ['kind' => 'order', 'id' => $order]);
        $this->assertNotWPError($read);
        $this->assertStringNotContainsString('private-operation', wp_json_encode($read));
        $this->assertWPError($this->execute('resolve-payment-operation', ['kind' => 'order', 'id' => $order, 'revision' => $read['revision']]));
        (new \PPCart_Order($order))->refund_log(5, 'manual');
        $confirmed = $this->execute('get-payment-operation', ['kind' => 'order', 'id' => $order]);
        $this->assertTrue($confirmed['native_result_confirmed']);
        $request = ['kind' => 'order', 'id' => $order, 'revision' => $confirmed['revision'], 'dry_run' => true];
        $this->assertNotWPError($this->execute('resolve-payment-operation', $request));
        $this->assertNotFalse(get_option('_ppcart_mcp_payment_lock_' . $order));
        $request['dry_run'] = false;
        $this->assertNotWPError($this->execute('resolve-payment-operation', $request));
        $this->assertFalse(get_option('_ppcart_mcp_payment_lock_' . $order));
    }
}
