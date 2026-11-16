<?php

declare(strict_types=1);

namespace Tests\Integration\Mcp;

use Tests\Support\Integration\McpTestCase;

require_once dirname(__DIR__, 2) . '/Support/Integration/McpTestCase.php';

class ProCatalogTest extends McpTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (! ppcart_is_pro()) {
            $this->markTestSkipped('Native Pro catalog configuration requires the actual Pro bootstrap.');
        }
    }

    private function product(): int
    {
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('product'), 'post_status' => 'publish', 'post_author' => get_current_user_id()]);
        ppcart_update_post_meta($id, 'pay_options', [
            ['option_id' => 'once', 'product_type' => '', 'price' => '10'],
            ['option_id' => 'monthly', 'product_type' => 'recurring', 'price' => '5', 'frequency' => '1', 'interval' => 'month', 'installments' => '-1'],
        ]);
        return $id;
    }

    public function test_native_collection_rows_round_trip_and_reject_invalid_plans_before_writes(): void
    {
        $product = $this->product();
        $collection = $this->execute('create-record', ['kind' => 'collection', 'title' => 'MCP bundle']);
        $this->assertNotWPError($collection);
        $before = $this->execute('get-collection-settings', ['id' => $collection['id']]);
        $this->assertNotWPError($before);
        $patch = ['id' => $collection['id'], 'revision' => $before['revision'], 'changes' => (object) [
            '_ppcart_price_type' => 'product', '_ppcart_product_options' => [(object) [
                'prod_product' => (string) $product, 'prod_plan' => 'once', 'prod_on_sale' => false,
            ]],
        ]];
        $preview = $this->execute('update-collection-settings', array_merge($patch, ['dry_run' => true]));
        $this->assertNotWPError($preview);
        $this->assertSame('', ppcart_get_post_meta($collection['id'], 'product_options', true));
        $saved = $this->execute('update-collection-settings', $patch);
        $this->assertNotWPError($saved);
        $native = ppcart_setup_product($collection['id']);
        $this->assertSame((string) $product, $native->product_options[0]['prod_product']);
        $this->assertSame('once', $native->product_options[0]['prod_plan']);
        $this->assertWPError($this->execute('update-collection-settings', [
            'id' => $collection['id'], 'revision' => $saved['revision'], 'changes' => (object) [
                '_ppcart_price_type' => 'custom', '_ppcart_product_options' => [(object) ['prod_product' => (string) $product, 'prod_plan' => 'monthly']],
            ],
        ]));
        $this->assertSame('product', ppcart_get_post_meta($collection['id'], 'price_type', true));
    }

    public function test_upgrade_references_are_recurring_and_multiselects_remain_arrays(): void
    {
        $product = $this->product();
        $path = $this->execute('create-record', ['kind' => 'upgrade-path', 'title' => 'MCP upgrades']);
        $this->assertNotWPError($path);
        $before = $this->execute('get-upgrade-path-settings', ['id' => $path['id']]);
        $saved = $this->execute('update-upgrade-path-settings', [
            'id' => $path['id'], 'revision' => $before['revision'], 'changes' => (object) [
                '_ppcart_switch_enabled' => true, '_ppcart_upgrade_filter' => ['upgrade'],
                '_ppcart_pay_options' => [(object) ['prod_product' => (string) $product, 'prod_plan' => 'monthly', 'option_name' => 'Monthly plan']],
            ],
        ]);
        $this->assertNotWPError($saved);
        $this->assertSame(['upgrade'], ppcart_get_post_meta($path['id'], 'upgrade_filter', true));
        $this->assertSame('monthly', ppcart_get_post_meta($path['id'], 'pay_options', true)[0]['prod_plan']);
        $this->assertWPError($this->execute('update-upgrade-path-settings', [
            'id' => $path['id'], 'revision' => $saved['revision'], 'changes' => (object) ['_ppcart_upgrade_filter' => 'upgrade'],
        ]));
    }

    public function test_offer_steps_use_native_suffixes_and_validate_all_references(): void
    {
        $product = $this->product();
        $page = self::factory()->post->create(['post_type' => 'page', 'post_status' => 'publish']);
        $path = $this->execute('create-record', ['kind' => 'upsell-path', 'title' => 'MCP offers']);
        $this->assertNotWPError($path);
        $before = $this->execute('get-upsell-path-settings', ['id' => $path['id']]);
        $saved = $this->execute('update-upsell-path-settings', [
            'id' => $path['id'], 'revision' => $before['revision'], 'changes' => (object) [
                '_ppcart_upsell_0' => true, '_ppcart_us_product_0' => (string) $product,
                '_ppcart_us_page_0' => (string) $page, '_ppcart_us_prod_type_0' => true, '_ppcart_us_plan_0' => 'once',
            ],
        ]);
        $this->assertNotWPError($saved);
        $this->assertSame('1', (string) ppcart_get_post_meta($path['id'], 'upsell_0', true));
        $this->assertSame('once', ppcart_get_post_meta($path['id'], 'us_plan_0', true));
        $this->assertWPError($this->execute('update-upsell-path-settings', [
            'id' => $path['id'], 'revision' => $saved['revision'], 'changes' => (object) ['_ppcart_us_product_0' => '999999999'],
        ]));
        $this->assertSame((string) $product, ppcart_get_post_meta($path['id'], 'us_product_0', true));
    }
    public function test_pro_catalog_object_denial_is_not_bypassed_by_administrator(): void
    {
        $types = ppcart_pro_mcp_record_types([]);
        $id = self::factory()->post->create(['post_type' => $types['collection'][0], 'post_author' => get_current_user_id()]);
        $read = $this->execute('get-collection-settings', ['id' => $id]);
        $this->assertNotWPError($read);
        $deny = static function ($caps, $capability, $user_id, $args) use ($id) {
            if ('edit_post' === $capability && $id === (int) ($args[0] ?? 0)) {
                return ['do_not_allow'];
            }
            return $caps;
        };
        add_filter('map_meta_cap', $deny, 10, 4);
        try {
            $this->assertWPError($this->execute('get-collection-settings', ['id' => $id]));
            $this->assertWPError($this->execute('get-product-settings', ['id' => $id]));
            $this->assertWPError($this->execute('get-payment-plans', ['id' => $id]));
            $this->assertWPError($this->execute('get-custom-fields', ['id' => $id]));
        } finally {
            remove_filter('map_meta_cap', $deny, 10);
        }
    }

}
