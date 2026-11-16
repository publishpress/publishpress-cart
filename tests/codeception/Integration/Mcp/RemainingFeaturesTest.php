<?php

declare(strict_types=1);

namespace Tests\Integration\Mcp;

use Tests\Support\Integration\McpTestCase;

require_once dirname(__DIR__, 2) . '/Support/Integration/McpTestCase.php';

class RemainingFeaturesTest extends McpTestCase
{
    public function test_checkout_field_patches_match_native_presence_flags_and_preserve_other_fields(): void
    {
        if (! ppcart_is_pro()) {
            $this->assertFalse(wp_has_ability('publishpress-cart/get-checkout-fields'));
            $this->assertFalse(wp_has_ability('publishpress-cart/set-checkout-fields'));
            return;
        }
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('product')]);
        $before = $this->execute('get-checkout-fields', ['id' => $id, 'group' => 'default']);
        $this->assertNotWPError($before);
        $patch = ['id' => $id, 'group' => 'default', 'revision' => $before['revision'], 'changes' => (object) [
            'phone' => (object) ['default_field_label' => 'Telephone', 'default_field_disabled' => true, 'default_field_required' => false, 'default_field_size' => '12'],
        ], 'dry_run' => true];
        $this->assertNotWPError($this->execute('set-checkout-fields', $patch));
        $this->assertSame('', ppcart_get_post_meta($id, 'default_fields', true));
        $patch['dry_run'] = false;
        $saved = $this->execute('set-checkout-fields', $patch);
        $this->assertNotWPError($saved);
        $native = ppcart_get_post_meta($id, 'default_fields', true);
        $this->assertSame('Telephone', $native['phone']['default_field_label']);
        $this->assertSame('1', $native['phone']['default_field_disabled']);
        $this->assertArrayNotHasKey('default_field_required', $native['phone']);
        $this->assertArrayNotHasKey('default_field_disabled', $native['email']);
        $this->assertSame('1', $native['email']['default_field_required']);
        $this->assertWPError($this->execute('set-checkout-fields', $patch));
        $this->assertWPError($this->execute('set-checkout-fields', ['id' => $id, 'group' => 'default', 'revision' => $saved['revision'],
            'changes' => (object) ['email' => (object) ['default_field_size' => 6]]]));
        $address = $this->execute('get-checkout-fields', ['id' => $id, 'group' => 'address']);
        if (ppcart_is_pro()) {
            $this->assertNotWPError($address);
            $updated = $this->execute('set-checkout-fields', ['id' => $id, 'group' => 'address', 'revision' => $address['revision'],
                'changes' => (object) ['city' => (object) ['default_field_label' => 'Locality']]]);
            $this->assertNotWPError($updated);
            $this->assertSame($native['phone'], ppcart_get_post_meta($id, 'default_fields', true)['phone']);
            $custom_size = static function ($set) {
                foreach ($set['fields'] as &$row) {
                    foreach ($row as &$field) {
                        if ('default_field_size' === $field['id']) { $field['selections']['4'] = 'Native custom width'; }
                    }
                }
                return $set;
            };
            add_filter('ppcart_default_phone_field_settings_attributes', $custom_size);
            try {
                $current = $this->execute('get-checkout-fields', ['id' => $id, 'group' => 'default']);
                $custom = $this->execute('set-checkout-fields', ['id' => $id, 'group' => 'default', 'revision' => $current['revision'],
                    'changes' => (object) ['phone' => (object) ['default_field_size' => '4']]]);
                $this->assertNotWPError($custom);
                $this->assertSame('4', ppcart_get_post_meta($id, 'default_fields', true)['phone']['default_field_size']);
            } finally {
                remove_filter('ppcart_default_phone_field_settings_attributes', $custom_size);
            }

        } else {
            $this->assertWPError($address);
        }
    }

    public function test_secure_files_keep_sources_private_and_reject_mixed_invalid_rows(): void
    {
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('product')]);
        ppcart_update_post_meta($id, 'files', [['file_id' => 'manual', 'file_name' => 'Manual', 'file_url' => 'https://example.org/private.pdf?secret=synthetic', 'file_plan' => [], 'file_limit' => null]]);
        $before = $this->execute('get-product-files', ['id' => $id]);
        $this->assertNotWPError($before);
        $this->assertStringNotContainsString('synthetic', wp_json_encode($before));
        $row = (object) ['file_id' => 'manual', 'file_name' => 'Updated manual', 'file_limit' => 3];
        $patch = ['id' => $id, 'revision' => $before['revision'], 'rows' => [$row], 'dry_run' => true];
        $this->assertNotWPError($this->execute('set-product-files', $patch));
        $patch['dry_run'] = false;
        $saved = $this->execute('set-product-files', $patch);
        $this->assertNotWPError($saved);
        $native = ppcart_get_post_meta($id, 'files', true);
        $this->assertSame(3, $native[0]['file_limit']);
        $this->assertStringContainsString('synthetic', $native[0]['file_url']);
        $bad = clone $row;
        $bad->file_limit = '3';
        $this->assertWPError($this->execute('set-product-files', ['id' => $id, 'revision' => $saved['revision'], 'rows' => [$row, $bad]]));
        $this->assertSame($native, ppcart_get_post_meta($id, 'files', true));
        $this->assertSame(ppcart_is_pro(), isset($saved['fields']->file_expire));
    }

    public function test_pause_resume_use_native_transition_and_reject_stale_replays(): void
    {
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('subscription')]);
        foreach (['status' => 'active', 'sub_status' => 'active', 'pay_method' => 'synthetic'] as $key => $value) {
            ppcart_update_post_meta($id, $key, $value);
        }
        $calls = [];
        $gateway = static function ($result, $sub, $type) use (&$calls) { $calls[] = [$sub->id, $type]; return true; };
        add_filter('ppcart_subscription_pause_restart', $gateway, 10, 3);
        try {
            $before = $this->execute('get-record', ['kind' => 'subscription', 'id' => $id]);
            $patch = ['id' => $id, 'revision' => $before['revision'], 'dry_run' => true];
            $this->assertNotWPError($this->execute('pause-subscription', $patch));
            $this->assertSame([], $calls);
            $patch['dry_run'] = false;
            $paused = $this->execute('pause-subscription', $patch);
            $this->assertNotWPError($paused);
            $this->assertSame('paused', $paused['subscription']['status']);
            $this->assertWPError($this->execute('pause-subscription', $patch));
            $resumed = $this->execute('resume-subscription', ['id' => $id, 'revision' => $paused['subscription']['revision']]);
            $this->assertNotWPError($resumed);
            $this->assertSame('active', $resumed['subscription']['status']);
            $this->assertSame([[$id, 'paused'], [$id, 'started']], $calls);
        } finally {
            remove_filter('ppcart_subscription_pause_restart', $gateway, 10);
        }
    }
    public function test_integration_discovery_rules_types_and_credentials_are_preserved(): void
    {
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('product')]);
        $provider = static function ($choices) { $choices['mailchimp'] = 'Mailchimp fixture'; return $choices; };
        add_filter('ppcart_integrations', $provider);
        update_option('ppcart_mailchimp_lists', ['list-1' => 'Test list']);
        ppcart_update_post_meta($id, 'integrations', [['services' => 'mailchimp', 'service_trigger' => ['purchased'],
            'service_action' => 'subscribed', 'mail_list' => 'list-1', 'hook_headers' => 'Bearer synthetic-private']]);
        try {
            $read = $this->execute('get-product-integrations', ['id' => $id]);
            $this->assertNotWPError($read);
            $this->assertStringNotContainsString('synthetic-private', wp_json_encode($read));
            $patch = ['id' => $id, 'revision' => $read['revision'], 'rows' => [(object) ['row_id' => 0, 'service_trigger' => ['purchased', 'refunded']]], 'dry_run' => true];
            $preview = $this->execute('set-product-integrations', $patch);
            $this->assertNotWPError($preview);
            $this->assertStringNotContainsString('synthetic-private', wp_json_encode($preview));
            $patch['dry_run'] = false;
            $saved = $this->execute('set-product-integrations', $patch);
            $this->assertNotWPError($saved);
            $native = ppcart_get_post_meta($id, 'integrations', true);
            $this->assertSame('Bearer synthetic-private', $native[0]['hook_headers']);
            $this->assertSame(['purchased', 'refunded'], $native[0]['service_trigger']);
            $this->assertWPError($this->execute('set-product-integrations', ['id' => $id, 'revision' => $saved['revision'],
                'rows' => [(object) ['row_id' => 0], (object) ['services' => 'missing-provider', 'service_trigger' => ['purchased']]]]));
            $this->assertSame($native, ppcart_get_post_meta($id, 'integrations', true));
            $this->assertWPError($this->execute('set-product-integrations', ['id' => $id, 'revision' => $saved['revision'],
                'rows' => [(object) ['services' => 'create user', 'service_trigger' => ['purchased'], 'user_role' => 'administrator']]]));
            add_role('manage_options', 'Synthetic capability-named role', ['read' => true]);
            try {
                $this->assertWPError($this->execute('set-product-integrations', ['id' => $id, 'revision' => $saved['revision'],
                    'rows' => [(object) ['services' => 'create user', 'service_trigger' => ['purchased'], 'user_role' => 'manage_options']]]));
            } finally {
                remove_role('manage_options');
            }
            add_role('mcp_customizer_fixture', 'Synthetic privileged role', ['read' => true, 'customize' => true]);
            try {
                $this->assertWPError($this->execute('set-product-integrations', ['id' => $id, 'revision' => $saved['revision'],
                    'rows' => [(object) ['services' => 'create user', 'service_trigger' => ['purchased'], 'user_role' => 'mcp_customizer_fixture']]]));
            } finally {
                remove_role('mcp_customizer_fixture');
            }


        } finally {
            remove_filter('ppcart_integrations', $provider);
        }
    }

    public function test_new_download_source_requires_an_editable_secure_attachment(): void
    {
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('product')]);
        $uploads = wp_upload_dir();
        $directory = $uploads['basedir'] . '/ppcart-uploads';
        wp_mkdir_p($directory);
        $file = $directory . '/mcp-test-' . wp_generate_uuid4() . '.pdf';
        file_put_contents($file, 'Synthetic secure download fixture');
        $attachment = wp_insert_attachment(['post_title' => 'Secure fixture', 'post_mime_type' => 'application/pdf'], $file);
        try {
            $read = $this->execute('get-product-files', ['id' => $id]);
            $patch = ['id' => $id, 'revision' => $read['revision'], 'rows' => [(object) [
                'file_id' => 'new-file', 'file_name' => 'New file', 'attachment_id' => $attachment,
            ]]];
            $saved = $this->execute('set-product-files', $patch);
            $this->assertNotWPError($saved);
            $this->assertStringNotContainsString('mcp-test-', wp_json_encode($saved));
            $this->assertSame(wp_get_attachment_url($attachment), ppcart_get_post_meta($id, 'files', true)[0]['file_url']);
            $deny = static function ($caps) { $caps['upload_files'] = false; return $caps; };
            add_filter('user_has_cap', $deny);
            try {
                $patch['revision'] = $saved['revision'];
                $this->assertWPError($this->execute('set-product-files', $patch));
            } finally {
                remove_filter('user_has_cap', $deny);
            }
        } finally {
            wp_delete_attachment($attachment, true);
            if (file_exists($file)) { unlink($file); }
        }
    }

    public function test_order_access_updates_and_revocation_are_bound_to_order_and_do_not_expose_keys(): void
    {
        global $wpdb;
        $order = self::factory()->post->create(['post_type' => ppcart_live_post_type('order')]);
        $other = self::factory()->post->create(['post_type' => ppcart_live_post_type('order')]);
        $wpdb->insert(\PPCart_Files::live_table(), ['order_id' => $order, 'product_id' => 1, 'file_id' => 'fixture',
            'order_key' => 'synthetic-bearer-key', 'downloads_remaining' => 'unlimited']);
        $row_id = (int) $wpdb->insert_id;
        $read = $this->execute('get-order-downloads', ['id' => $order]);
        $this->assertNotWPError($read);
        $this->assertStringNotContainsString('synthetic-bearer-key', wp_json_encode($read));
        $this->assertSame($row_id, $read['downloads'][0]['download_id']);
        $patch = ['id' => $order, 'download_id' => $row_id, 'revision' => $read['revision'],
            'changes' => (object) ['downloads_remaining' => 2, 'download_expires' => '2030-01-01 00:00:00'], 'dry_run' => true];
        $this->assertNotWPError($this->execute('update-order-download', $patch));
        $patch['dry_run'] = false;
        $saved = $this->execute('update-order-download', $patch);
        $this->assertNotWPError($saved);
        $this->assertSame('2', $saved['downloads'][0]['downloads_remaining']);
        $this->assertWPError($this->execute('update-order-download', $patch));
        $other_read = $this->execute('get-order-downloads', ['id' => $other]);
        $this->assertWPError($this->execute('revoke-order-download', ['id' => $other, 'download_id' => $row_id, 'revision' => $other_read['revision']]));
        $deleted = $this->execute('revoke-order-download', ['id' => $order, 'download_id' => $row_id, 'revision' => $saved['revision']]);
        $this->assertNotWPError($deleted);
        $this->assertSame([], $deleted['downloads']);
    }

    public function test_transition_failures_remain_unconfirmed_and_do_not_leak_gateway_messages(): void
    {
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('subscription')]);
        foreach (['status' => 'active', 'sub_status' => 'active', 'pay_method' => 'synthetic'] as $key => $value) {
            ppcart_update_post_meta($id, $key, $value);
        }
        $gateway = static function () { throw new \RuntimeException('Synthetic private gateway diagnostic'); };
        add_filter('ppcart_subscription_pause_restart', $gateway);
        try {
            $read = $this->execute('get-record', ['kind' => 'subscription', 'id' => $id]);
            $error = $this->execute('pause-subscription', ['id' => $id, 'revision' => $read['revision']]);
            $this->assertWPError($error);
            $this->assertSame('ppcart_mcp_payment_unconfirmed', $error->get_error_code());
            $this->assertStringNotContainsString('private gateway diagnostic', $error->get_error_message());
            $this->assertNotFalse(get_option('_ppcart_mcp_payment_lock_' . $id));
            $this->assertWPError($this->execute('pause-subscription', ['id' => $id, 'revision' => $read['revision']]));
        } finally {
            remove_filter('ppcart_subscription_pause_restart', $gateway);
        }
    }

    public function test_pro_integration_related_object_denial_redacts_reads_and_blocks_writes(): void
    {
        if (! ppcart_is_pro()) {
            $this->markTestSkipped('Pro course integration reference checks require Pro.');
        }
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('product')]);
        $course = self::factory()->post->create(['post_status' => 'private']);
        $provider = static function ($choices) { $choices['mailchimp'] = 'Fixture'; return $choices; };
        add_filter('ppcart_integrations', $provider);
        ppcart_update_post_meta($id, 'integrations', [['services' => 'mailchimp', 'service_trigger' => ['purchased'], 'tutor_course' => [(string) $course]]]);
        $deny = static function ($caps, $capability, $user, $args) use ($course) {
            return in_array($capability, ['edit_post', 'read_post'], true) && (int) ($args[0] ?? 0) === $course ? ['do_not_allow'] : $caps;
        };
        add_filter('map_meta_cap', $deny, 10, 4);
        try {
            $read = $this->execute('get-product-integrations', ['id' => $id]);
            $this->assertNotWPError($read);
            $this->assertArrayNotHasKey('tutor_course', $read['rows'][0]);
            $this->assertWPError($this->execute('set-product-integrations', ['id' => $id, 'revision' => $read['revision'], 'rows' => [(object) ['row_id' => 0]]]));
        } finally {
            remove_filter('map_meta_cap', $deny, 10);
            remove_filter('ppcart_integrations', $provider);
        }
    }

    public function test_pro_download_visibility_and_expiry_match_native_consumption(): void
    {
        if (! ppcart_is_pro()) {
            $this->markTestSkipped('Pro download visibility requires Pro.');
        }
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('product')]);
        ppcart_update_post_meta($id, 'files', [['file_id' => 'visible', 'file_name' => 'Visible', 'file_url' => 'https://example.org/private.pdf', 'file_hide' => 1]]);
        $read = $this->execute('get-product-files', ['id' => $id]);
        $saved = $this->execute('set-product-files', ['id' => $id, 'revision' => $read['revision'],
            'rows' => [(object) ['file_id' => 'visible', 'file_hide' => false, 'file_expire' => '3 days']]]);
        $this->assertNotWPError($saved);
        $native = ppcart_get_post_meta($id, 'files', true);
        $this->assertArrayNotHasKey('file_hide', $native[0]);
        $this->assertSame('3 days', $native[0]['file_expire']);
        $download = (object) ['product_id' => $id, 'file_id' => 'visible', 'order_id' => 1, 'download_id' => 1,
            'order_key' => 'synthetic-private-key', 'downloads_remaining' => 'unlimited'];
        $this->assertIsObject(\PPCart_Files::get_active_handler()->setup_download($download, false));
    }

}
