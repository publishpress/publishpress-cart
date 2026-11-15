<?php

declare(strict_types=1);

namespace Tests\Integration\Mcp;

use Tests\Support\Integration\McpTestCase;

require_once dirname(__DIR__, 2) . '/Support/Integration/McpTestCase.php';

class LifecycleTest extends McpTestCase
{
    public function test_native_lifecycle_preview_revision_and_deletion(): void
    {
        $product = $this->execute('create-record', ['kind' => 'product', 'title' => 'Lifecycle source']);
        $id = $product['id'];
        ppcart_update_post_meta($id, 'product_name', 'Retained configuration');
        $before = $this->execute('get-catalog-lifecycle', ['kind' => 'product', 'id' => $id]);
        $request = ['kind' => 'product', 'id' => $id, 'revision' => $before['revision'], 'operation' => 'duplicate', 'dry_run' => true];
        $this->assertNotWPError($this->execute('update-catalog-lifecycle', $request));
        $request['dry_run'] = false;
        $copy = $this->execute('update-catalog-lifecycle', $request);
        $this->assertNotWPError($copy);
        $this->assertNotSame($id, $copy['id']);
        $this->assertSame('draft', $copy['status']);
        $this->assertSame('Retained configuration', ppcart_get_post_meta($copy['id'], 'product_name', true));
        ppcart_update_post_meta($id, 'product_name', 'Concurrent edit');
        $this->assertWPError($this->execute('update-catalog-lifecycle', $request));
        foreach (['trash', 'restore', 'trash', 'delete'] as $operation) {
            $state = $this->execute('get-catalog-lifecycle', ['kind' => 'product', 'id' => $id]);
            $result = $this->execute('update-catalog-lifecycle', ['kind' => 'product', 'id' => $id,
                'revision' => $state['revision'], 'operation' => $operation]);
            $this->assertNotWPError($result);
        }
        $this->assertNull(get_post($id));
    }

    public function test_denied_native_capabilities_and_payment_records(): void
    {
        $product = $this->execute('create-record', ['kind' => 'product', 'title' => 'Denied source']);
        $before = $this->execute('get-catalog-lifecycle', ['kind' => 'product', 'id' => $product['id']]);
        $type = get_post_type_object(ppcart_live_post_type('product'));
        wp_get_current_user()->add_cap($type->cap->delete_posts, false);
        $this->assertWPError($this->execute('update-catalog-lifecycle', ['kind' => 'product', 'id' => $product['id'],
            'revision' => $before['revision'], 'operation' => 'trash']));
        $this->assertSame('draft', get_post_status($product['id']));
        $this->assertWPError($this->execute('get-catalog-lifecycle', ['kind' => 'order', 'id' => $product['id']]));
    }

    public function test_stripe_combined_selector_round_trips_and_rejects_stale_and_wrong_types(): void
    {
        foreach (['classic', 'element', 'hosted'] as $choice) {
            $before = $this->execute('get-checkout-experience', []);
            $request = ['experience' => $choice, 'revision' => $before['revision'], 'dry_run' => true];
            $this->assertNotWPError($this->execute('set-checkout-experience', $request));
            $this->assertSame($before, $this->execute('get-checkout-experience', []));
            $request['dry_run'] = false;
            $after = $this->execute('set-checkout-experience', $request);
            $this->assertNotWPError($after);
            $this->assertSame($choice, ppcart_get_stripe_checkout_experience());
            $this->assertSame('element' === $choice ? '1' : '0', get_option('_ppcart_stripe_payment_element_enable'));
            $this->assertSame('hosted' === $choice ? '1' : '0', get_option('_ppcart_stripe_hosted_checkout_enable'));
        }
        $this->assertWPError($this->execute('set-checkout-experience', ['experience' => false, 'revision' => $after['revision']]));
        $this->assertWPError($this->execute('set-checkout-experience', ['experience' => 'classic', 'revision' => str_repeat('0', 64)]));
        wp_get_current_user()->add_cap('manage_options', false);
        $this->assertWPError($this->execute('get-checkout-experience', []));
    }

    public function test_runtime_checkout_lock_and_failed_pair_write_preserve_state(): void
    {
        $before = $this->execute('get-checkout-experience', []);
        $lock = static function ($sections) {
            foreach ($sections as &$fields) {
                foreach ($fields as &$field) {
                    if ('_ppcart_stripe_payment_element_enable' === ($field['settings']['id'] ?? '')) {
                        $field['settings']['disabled'] = true;
                    }
                }
                unset($field);
            }
            unset($fields);
            return $sections;
        };
        add_filter('ppcart_mcp_setting_definitions', $lock);
        try {
            $this->assertWPError($this->execute('set-checkout-experience', ['experience' => 'hosted', 'revision' => $before['revision']]));
            $this->assertSame($before, $this->execute('get-checkout-experience', []));
        } finally {
            remove_filter('ppcart_mcp_setting_definitions', $lock);
        }
        update_option('_ppcart_stripe_payment_element_enable', '1');
        update_option('_ppcart_stripe_hosted_checkout_enable', '0');
        $before = $this->execute('get-checkout-experience', []);
        $fail = static function ($value, $old) { return $old; };
        add_filter('pre_update_option__ppcart_stripe_hosted_checkout_enable', $fail, 10, 2);
        try {
            $this->assertWPError($this->execute('set-checkout-experience', ['experience' => 'hosted', 'revision' => $before['revision']]));
            $this->assertSame($before, $this->execute('get-checkout-experience', []));
        } finally {
            remove_filter('pre_update_option__ppcart_stripe_hosted_checkout_enable', $fail);
        }
    }

    public function test_duplicate_does_not_copy_privileged_user_grants(): void
    {
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('product'), 'post_author' => get_current_user_id()]);
        ppcart_update_post_meta($id, 'integrations', [['services' => 'create user', 'service_trigger' => ['purchased'], 'user_role' => 'administrator']]);
        $this->assertNotWPError($this->execute('get-product-integrations', ['id' => $id]));
        $before = $this->execute('get-catalog-lifecycle', ['kind' => 'product', 'id' => $id]);
        $count = wp_count_posts(ppcart_live_post_type('product'))->draft;
        $this->assertWPError($this->execute('update-catalog-lifecycle', ['kind' => 'product', 'id' => $id,
            'revision' => $before['revision'], 'operation' => 'duplicate']));
        $this->assertSame($count, wp_count_posts(ppcart_live_post_type('product'))->draft);
    }

    public function test_pro_branding_honors_native_configuration_locks_when_tab_is_hidden(): void
    {
        if (! ppcart_is_pro()) { $this->markTestSkipped('Pro native branding locks.'); }
        $lock = static function ($sections) {
            foreach ($sections['white_label-setting'] ?? [] as $key => $field) {
                if ('_ppcart_wl_name' === ($field['settings']['id'] ?? '')) {
                    $sections['white_label-setting'][$key]['settings']['disabled'] = true;
                }
            }
            return $sections;
        };
        foreach ([false, true] as $hidden) {
            update_option('_ppcart_wl_enable', '1');
            update_option('_ppcart_wl_hide', $hidden ? '1' : '0');
            $before = $this->execute('get-settings', []);
            $old = get_option('_ppcart_wl_name');
            add_filter('ppcart_option_list', $lock);
            try {
                $this->assertWPError($this->execute('update-settings', ['revision' => $before['revision'], 'changes' => ['_ppcart_wl_name' => 'Denied branding change']]));
                $read = $this->execute('get-settings', []);
                $this->assertArrayNotHasKey('_ppcart_wl_name', (array) $read['settings']);
                $this->assertSame($old, get_option('_ppcart_wl_name'));
            } finally {
                remove_filter('ppcart_option_list', $lock);
            }
        }
    }

    public function test_pro_white_label_native_definitions_remain_available_when_hidden(): void
    {
        if (! ppcart_is_pro()) {
            $this->markTestSkipped('Pro branding controls.');
        }
        $before = $this->execute('get-settings', []);
        $fields = (array) $before['settings'];
        $this->assertArrayHasKey('_ppcart_wl_enable', $fields);
        $saved = $this->execute('update-settings', ['revision' => $before['revision'],
            'changes' => ['_ppcart_wl_enable' => true, '_ppcart_wl_hide' => true, '_ppcart_wl_name' => 'Example store']]);
        $this->assertNotWPError($saved);
        $this->assertSame('Example store', get_option('_ppcart_wl_name'));
        $this->assertArrayHasKey('_ppcart_wl_hide', (array) $saved['settings']);
        $saved = $this->execute('update-settings', ['revision' => $saved['revision'], 'changes' => ['_ppcart_wl_hide' => false]]);
        $this->assertNotWPError($saved);
        $this->assertFalse((bool) get_option('_ppcart_wl_hide'));
    }
    public function test_native_checkout_selector_preserves_hidden_flags_and_uses_shared_selection(): void
    {
        $renderer = new class {
            use \PPCart_Admin_Settings_Text_Fields_Trait;
        };
        foreach ([[0, 0, 'classic'], [1, 0, 'element'], [0, 1, 'hosted'], [1, 1, 'hosted']] as $state) {
            update_option('_ppcart_stripe_payment_element_enable', $state[0]);
            update_option('_ppcart_stripe_hosted_checkout_enable', $state[1]);
            ob_start();
            try {
                $renderer->field_stripe_checkout_experience([]);
                $html = ob_get_contents();
            } finally {
                ob_end_clean();
            }
            $this->assertSame($state[2], ppcart_get_stripe_checkout_experience());
            $this->assertStringContainsString('name="_ppcart_stripe_payment_element_enable" value="' . $state[0] . '"', $html);
            $this->assertStringContainsString('name="_ppcart_stripe_hosted_checkout_enable" value="' . $state[1] . '"', $html);
        }
    }

}
