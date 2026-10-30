<?php

declare(strict_types=1);

namespace Tests\Integration\Mcp;

use Tests\Support\Integration\McpTestCase;

require_once dirname(__DIR__, 2) . '/Support/Integration/McpTestCase.php';

class PlansTest extends McpTestCase
{
    public function test_plan_preview_save_and_hidden_identifier_preservation(): void
    {
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('product'), 'post_author' => get_current_user_id()]);
        ppcart_update_post_meta($id, 'pay_options', [[
            'option_id' => 'main', 'product_type' => '', 'price' => '10', 'stripe_plan_id' => 'generated-private-id',
        ]]);
        $before = $this->execute('get-payment-plans', ['id' => $id]);
        $this->assertNotWPError($before);
        $this->assertArrayNotHasKey('stripe_plan_id', $before['plans'][0]);
        $patch = ['id' => $id, 'revision' => $before['revision'],
            'plans' => [(object) ['option_id' => 'main', 'option_name' => 'Renamed', 'price' => '12']], 'dry_run' => true];
        $preview = $this->execute('set-payment-plans', $patch);
        $this->assertNotWPError($preview);
        $this->assertSame('10', ppcart_get_post_meta($id, 'pay_options', true)[0]['price']);
        add_filter('ppcart_process_stripe_products', '__return_false');
        try {
            $patch['dry_run'] = false;
            $saved = $this->execute('set-payment-plans', $patch);
        } finally {
            remove_filter('ppcart_process_stripe_products', '__return_false');
        }
        $this->assertNotWPError($saved);
        $stored = ppcart_get_post_meta($id, 'pay_options', true);
        $this->assertSame('12', $stored[0]['price']);
        $this->assertSame('generated-private-id', $stored[0]['stripe_plan_id']);
        $this->assertWPError($this->execute('set-payment-plans', $patch));
    }

    public function test_native_plan_save_does_not_overwrite_a_concurrent_update(): void
    {
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('product'), 'post_author' => get_current_user_id()]);
        $original = [['option_id' => 'main', 'product_type' => '', 'price' => '10']];
        ppcart_update_post_meta($id, 'pay_options', $original);
        $before = $this->execute('get-payment-plans', ['id' => $id]);
        $raced = false;
        $race = static function ($check, $object_id, $key) use ($id, &$raced) {
            if (! $raced && $object_id === $id && $key === ppcart_meta_key('pay_options')) {
                $raced = true;
                ppcart_update_post_meta($id, 'pay_options', [['option_id' => 'main', 'product_type' => '', 'price' => '99']]);
            }
            return $check;
        };
        add_filter('update_post_metadata', $race, 10, 3);
        try {
            $result = $this->execute('set-payment-plans', ['id' => $id, 'revision' => $before['revision'],
                'plans' => [(object) ['option_id' => 'main', 'price' => '12']]]);
        } finally {
            remove_filter('update_post_metadata', $race, 10);
        }
        $this->assertWPError($result);
        $this->assertSame('99', ppcart_get_post_meta($id, 'pay_options', true)[0]['price']);
    }

    public function test_invalid_plan_list_is_fully_prevalidated(): void
    {
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('product'), 'post_author' => get_current_user_id()]);
        $before = $this->execute('get-payment-plans', ['id' => $id]);
        foreach ([
            [(object) ['option_id' => 'main', 'price' => '10'], (object) ['option_id' => 'main', 'price' => '12']],
            [(object) ['option_id' => 'main', 'price' => '-1']],
            [(object) ['option_id' => 'main', 'price' => '10', 'is_hidden' => true]],
            [(object) ['option_id' => 'main', 'price' => '10', 'is_hidden' => 'false']],
            [(object) ['option_id' => 'main', 'price' => '10', 'stripe_plan_id' => 'injected']],
            [(object) ['option_id' => 'main', 'price' => '10', 'product_type' => 'recurring']],
        ] as $plans) {
            $this->assertWPError($this->execute('set-payment-plans', [
                'id' => $id, 'revision' => $before['revision'], 'plans' => $plans,
            ]));
            $this->assertSame('', ppcart_get_post_meta($id, 'pay_options', true));
        }
    }
}
