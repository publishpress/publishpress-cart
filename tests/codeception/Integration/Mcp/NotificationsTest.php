<?php

declare(strict_types=1);

namespace Tests\Integration\Mcp;

use Tests\Support\Integration\McpTestCase;

require_once dirname(__DIR__, 2) . '/Support/Integration/McpTestCase.php';

class NotificationsTest extends McpTestCase
{
    public function test_native_notification_preview_save_and_invalid_mixed_rows(): void
    {
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('product')]);
        ppcart_update_post_meta($id, 'unrelated_notification_test', 'Keep');
        $read = $this->execute('get-product-notifications', ['id' => $id]);
        $this->assertNotWPError($read);
        $row = (object) ['notification_name' => 'Welcome', 'enabled' => false, 'send_to' => 'purchaser',
            'subject' => 'Thank you', 'message' => '<p>Hello {first_name}</p>'];
        $input = ['id' => $id, 'revision' => $read['revision'], 'rows' => [$row], 'dry_run' => true];
        $sent = [];
        $capture = static function ($result, $args) use (&$sent) { $sent[] = $args; return true; };
        add_filter('pre_wp_mail', $capture, 10, 2);
        try {
            $preview = $this->execute('set-product-notifications', $input);
            $this->assertNotWPError($preview);
            $this->assertSame('', ppcart_get_post_meta($id, 'notifications', true));
            $input['dry_run'] = false;
            $saved = $this->execute('set-product-notifications', $input);
            $this->assertNotWPError($saved);
            $this->assertSame([], $sent);
        } finally {
            remove_filter('pre_wp_mail', $capture, 10);
        }
        $native = ppcart_get_post_meta($id, 'notifications', true);
        $this->assertSame(0, $native[0]['enabled']);
        $this->assertNull($native[0]['reply_to']);
        $this->assertSame('<p>Hello {first_name}</p>', $native[0]['message']);
        $this->assertSame('Keep', ppcart_get_post_meta($id, 'unrelated_notification_test', true));
        $bad = clone $row;
        $bad->enabled = 'false';
        $this->assertWPError($this->execute('set-product-notifications', [
            'id' => $id, 'revision' => $saved['revision'], 'rows' => [$row, $bad],
        ]));
        $this->assertSame($native, ppcart_get_post_meta($id, 'notifications', true));
        $this->assertWPError($this->execute('set-product-notifications', $input));
        $cleared = $this->execute('set-product-notifications', ['id' => $id, 'revision' => $saved['revision'], 'rows' => []]);
        $this->assertNotWPError($cleared);
        $this->assertSame([], ppcart_get_post_meta($id, 'notifications', true));
    }
}
