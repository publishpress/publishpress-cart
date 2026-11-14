<?php

declare(strict_types=1);

namespace Tests\Integration\Mcp;

use Tests\Support\Integration\McpTestCase;

require_once dirname(__DIR__, 2) . '/Support/Integration/McpTestCase.php';

class AuditTest extends McpTestCase
{
    public function test_feature_discovery_and_every_registered_permission_reject_denied_caps(): void
    {
        $features = $this->execute('list-features', []);
        $this->assertNotWPError($features);
        $this->assertArrayHasKey('store-settings', (array) $features['features']);
        $this->assertSame(ppcart_is_pro(), isset($features['features']->{'custom-tax-rates'}));
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('product')]);
        $deny = static function ($caps) {
            return array_fill_keys(array_keys($caps), false);
        };
        add_filter('user_has_cap', $deny);
        try {
            foreach (wp_get_abilities() as $ability) {
                if (0 !== strpos($ability->get_name(), 'publishpress-cart/')) {
                    continue;
                }
                $meta = $ability->get_meta();
                $this->assertFalse($meta['public']);
                $this->assertFalse($meta['mcp']['public']);
                $this->assertFalse($meta['show_in_rest']);
                $this->assertFalse((bool) $ability->check_permissions(['id' => $id, 'kind' => 'product', 'taxonomy' => 'category']), $ability->get_name());
            }
            $this->assertWPError($this->execute('list-features', []));
            $this->assertWPError($this->execute('get-product-settings', ['id' => $id]));
        } finally {
            remove_filter('user_has_cap', $deny);
        }
    }

    public function test_missing_native_email_switches_and_color_round_trip(): void
    {
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('product')]);
        $before = $this->execute('get-product-settings', ['id' => $id]);
        $fields = (array) $before['fields'];
        $this->assertArrayHasKey('_ppcart_header_color', $fields);
        $this->assertArrayHasKey('_ppcart_disable_welcome_email', $fields);
        $this->assertArrayHasKey('_ppcart_disable_purchase_email', $fields);
        $changes = (object) ['_ppcart_header_color' => '#123456', '_ppcart_disable_welcome_email' => true, '_ppcart_disable_purchase_email' => false];
        $preview = $this->execute('update-product-settings', ['id' => $id, 'revision' => $before['revision'], 'changes' => $changes, 'dry_run' => true]);
        $this->assertNotWPError($preview);
        $saved = $this->execute('update-product-settings', ['id' => $id, 'revision' => $before['revision'], 'changes' => $changes]);
        $this->assertNotWPError($saved);
        $this->assertSame('#123456', ppcart_get_post_meta($id, 'header_color', true));
        $this->assertSame('1', (string) ppcart_get_post_meta($id, 'disable_welcome_email', true));
        $this->assertSame('0', (string) ppcart_get_post_meta($id, 'disable_purchase_email', true));
    }

    public function test_runtime_setting_locks_reject_the_entire_patch(): void
    {
        $before = $this->execute('get-settings', []);
        $lock = static function ($sections) {
            foreach ($sections as &$fields) {
                foreach ($fields as &$field) {
                    if ('_ppcart_company_name' === ($field['settings']['id'] ?? '')) {
                        $field['settings']['disabled'] = true;
                    }
                }
            }
            return $sections;
        };
        $stored = get_option('_ppcart_company_name');
        add_filter('ppcart_option_list', $lock);
        try {
            $result = $this->execute('update-settings', ['revision' => $before['revision'], 'changes' => (object) ['_ppcart_company_name' => 'Blocked']]);
            $this->assertWPError($result);
            $this->assertSame($stored, get_option('_ppcart_company_name'));
            $read = $this->execute('get-settings', []);
            $this->assertArrayNotHasKey('_ppcart_company_name', (array) $read['settings']);
        } finally {
            remove_filter('ppcart_option_list', $lock);
        }
    }
    public function test_repeated_registration_preserves_shared_category_and_abilities(): void
    {
        global $wp_filter;
        $ability = wp_get_ability('publishpress-cart/get-settings');
        $category = wp_get_ability_category('publishpress');
        foreach (['wp_abilities_api_categories_init', 'wp_abilities_api_init'] as $hook) {
            foreach ($wp_filter[$hook]->callbacks as $callbacks) {
                foreach ($callbacks as $callback) {
                    $operation = $callback['function'];
                    if (is_array($operation) && $operation[0] instanceof \PPCart_Mcp_Abilities) {
                        call_user_func($operation);
                    }
                }
            }
        }
        $this->assertSame($ability, wp_get_ability('publishpress-cart/get-settings'));
        $this->assertSame($category, wp_get_ability_category('publishpress'));
    }

}
