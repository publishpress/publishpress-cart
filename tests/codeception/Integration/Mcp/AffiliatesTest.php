<?php

declare(strict_types=1);

namespace Tests\Integration\Mcp;

use Tests\Support\Integration\McpTestCase;

require_once dirname(__DIR__, 2) . '/Support/Integration/McpTestCase.php';

class AffiliatesTest extends McpTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (! ppcart_is_pro()) {
            $this->markTestSkipped('Requires native Pro affiliate extension.');
        }
        global $ppcart_pro_extensions;
        $this->assertNotWPError($ppcart_pro_extensions->set_control_state('affiliate', true));
    }

    public function test_native_affiliate_crud_and_product_associations(): void
    {
        $product = self::factory()->post->create(['post_type' => ppcart_live_post_type('product')]);
        $fields = (object) ['first_name' => 'Sample', 'last_name' => 'Affiliate', 'email' => 'affiliate@example.org',
            'paypal_email' => '', 'status' => 'pending', 'user_id' => 0, 'products' => [$product]];
        $before = $this->execute('list-affiliates', []);
        $this->assertNotWPError($before);
        $preview = $this->execute('create-affiliate', ['changes' => $fields, 'dry_run' => true]);
        $this->assertNotWPError($preview);
        $this->assertEquals($before, $this->execute('list-affiliates', []));
        $created = $this->execute('create-affiliate', ['changes' => $fields]);
        $this->assertNotWPError($created);
        $id = $created['id'];
        $model = new \PPCart_Pro_Affiliate($id);
        $this->assertSame('affiliate@example.org', $model->email);
        $this->assertSame([$product], array_map('intval', $model->get_meta('affiliate_product', false)));
        $model->update_meta('unrelated', 'Keep');
        $read = $this->execute('get-affiliate', ['id' => $id]);
        $this->assertStringNotContainsString('Keep', wp_json_encode($read));
        $this->assertWPError($this->execute('update-affiliate', ['id' => $id, 'revision' => $created['revision'], 'changes' => (object) ['bio' => 'Stale']]));
        $updated = $this->execute('update-affiliate', ['id' => $id, 'revision' => $read['revision'], 'changes' => (object) ['bio' => 'Native biography', 'products' => []]]);
        $this->assertNotWPError($updated);
        $this->assertSame('Keep', $model->get_meta('unrelated'));
        $this->assertSame([], $model->get_meta('affiliate_product', false));
        $this->assertWPError($this->execute('update-affiliate', ['id' => $id, 'revision' => $updated['revision'], 'changes' => (object) ['bio' => 'Must not save', 'user_id' => '1']]));
        $this->assertSame('Native biography', $model->get_meta('bio'));
        $deleted = $this->execute('delete-affiliate', ['id' => $id, 'revision' => $updated['revision']]);
        $this->assertNotWPError($deleted);
        $this->assertNull((new \PPCart_Pro_Affiliate($id))->id);
    }

    public function test_denied_related_product_and_user_permissions_prevent_all_writes(): void
    {
        $product = self::factory()->post->create(['post_type' => ppcart_live_post_type('product')]);
        $user = self::factory()->user->create(['role' => 'subscriber']);
        $deny = static function ($caps, $cap, $user_id, $args) use ($product, $user) {
            if (('edit_post' === $cap && ($args[0] ?? 0) === $product) || ('edit_user' === $cap && ($args[0] ?? 0) === $user)) {
                return ['do_not_allow'];
            }
            return $caps;
        };
        add_filter('map_meta_cap', $deny, 10, 4);
        try {
            foreach ([['user_id' => $user], ['products' => [$product]]] as $patch) {
                $data = $patch + ['first_name' => 'Denied', 'last_name' => 'Affiliate', 'email' => 'denied@example.org', 'status' => 'pending', 'user_id' => 0];
                $this->assertWPError($this->execute('create-affiliate', ['changes' => (object) $data]));
            }
            $this->assertNull(\PPCart_Pro_Affiliate::find_by_email('denied@example.org'));
        } finally {
            remove_filter('map_meta_cap', $deny, 10);
        }
    }
}
