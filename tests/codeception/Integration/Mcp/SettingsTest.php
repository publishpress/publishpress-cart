<?php

declare(strict_types=1);

namespace Tests\Integration\Mcp;

use Tests\Support\Integration\McpTestCase;

require_once dirname(__DIR__, 2) . '/Support/Integration/McpTestCase.php';

/**
 * Native option and released-adapter round trips.
 */
class SettingsTest extends McpTestCase
{
    public function test_native_settings_preview_save_and_stale_revision(): void
    {
        $before = $this->execute('get-settings', new \stdClass());
        $this->assertIsArray($before);
        $revision = $before['revision'];
        $patch = (object) [
            'changes' => (object) ['_ppcart_company_name' => 'MCP Store', '_ppcart_myaccount_hide_free' => true],
            'revision' => $revision,
            'dry_run' => true,
        ];
        $old = get_option('_ppcart_company_name', '');
        $preview = $this->execute('update-settings', $patch);
        $this->assertTrue($preview['dry_run']);
        $this->assertSame($old, get_option('_ppcart_company_name', ''));
        $patch->dry_run = false;
        $saved = $this->execute('update-settings', $patch);
        $this->assertNotWPError($saved);
        $this->assertSame('MCP Store', get_option('_ppcart_company_name'));
        $this->assertSame('1', (string) get_option('_ppcart_myaccount_hide_free'));
        $this->assertWPError($this->execute('update-settings', $patch));
    }

    public function test_invalid_mixed_patch_does_not_write(): void
    {
        $before = $this->execute('get-settings', []);
        foreach (['false', 0, [], (object) [], null] as $bad) {
            $result = $this->execute('update-settings', [
                'changes' => (object) ['_ppcart_company_name' => 'Must not save', '_ppcart_myaccount_hide_free' => $bad],
                'revision' => $before['revision'],
            ]);
            $this->assertWPError($result);
            $this->assertSame($before['revision'], $this->execute('get-settings', [])['revision']);
        }
        $this->assertWPError($this->execute('update-settings', [
            'changes' => (object) ['_ppcart_company_name' => 'Must not save', '_ppcart_api_key' => 'not-allowed'],
            'revision' => $before['revision'],
        ]));
    }

    public function test_denied_administrator_capability_is_respected(): void
    {
        $user = wp_get_current_user();
        $user->add_cap('manage_options', false);
        $this->assertWPError($this->execute('get-settings', []));
        $this->assertWPError($this->execute('update-settings', [
            'changes' => (object) ['_ppcart_company_name' => 'Denied'], 'revision' => str_repeat('a', 64),
        ]));
    }

    public function test_every_discovered_setting_uses_its_native_type_and_round_trips(): void
    {
        $initial = $this->execute('get-settings', []);
        $this->assertNotEmpty((array) $initial['settings']);
        $cases = (array) $initial['settings'];
        foreach ($cases as $id => $field) {
            $schema = $field['schema'];
            if (isset($schema['enum'])) {
                $value = $schema['enum'][0];
            } elseif ('boolean' === $schema['type']) {
                $value = false;
            } elseif ('array' === $schema['type']) {
                $value = [];
            } elseif ('integer' === $schema['type']) {
                $value = 2;
            } else {
                $value = '';
            }
            $before = $this->execute('get-settings', []);
            $patch = ['changes' => (object) [$id => $value], 'revision' => $before['revision'], 'dry_run' => true];
            $preview = $this->execute('update-settings', $patch);
            $this->assertNotWPError($preview, $id);
            $this->assertSame($before['revision'], $this->execute('get-settings', [])['revision'], $id);
            $patch['dry_run'] = false;
            $saved = $this->execute('update-settings', $patch);
            $this->assertNotWPError($saved, $id);
            $this->assertTrue(\PPCart_Mcp_Options::same_value($preview['changes']->$id, $saved['settings']->$id['stored_value']), $id);
            $this->assertTrue(\PPCart_Mcp_Options::same_value(get_option($id), $saved['settings']->$id['value']), $id);
        }
    }

    public function test_empty_email_defaults_and_failed_patch_preserve_storage_presence(): void
    {
        delete_option('_ppcart_email_from_name');
        $before = $this->execute('get-settings', []);
        $this->assertFalse($before['settings']->_ppcart_email_from_name['exists']);
        $saved = $this->execute('update-settings', [
            'changes' => (object) ['_ppcart_email_from_name' => ''], 'revision' => $before['revision'],
        ]);
        $this->assertNotWPError($saved);
        $this->assertSame('', $saved['settings']->_ppcart_email_from_name['stored_value']);
        $this->assertSame(get_bloginfo('name'), $saved['settings']->_ppcart_email_from_name['value']);
        delete_option('_ppcart_company_name');
        delete_option('_ppcart_company_address');
        $before = $this->execute('get-settings', []);
        $block = static function ($new, $old) {
            return $old;
        };
        add_filter('pre_update_option__ppcart_company_address', $block, 10, 2);
        try {
            $result = $this->execute('update-settings', [
                'changes' => (object) ['_ppcart_company_name' => 'Must roll back', '_ppcart_company_address' => 'Blocked'],
                'revision' => $before['revision'],
            ]);
        } finally {
            remove_filter('pre_update_option__ppcart_company_address', $block, 10);
        }
        $this->assertWPError($result);
        $snapshots = \PPCart_Mcp_Options::snapshots(['_ppcart_company_name', '_ppcart_company_address']);
        $this->assertFalse($snapshots['_ppcart_company_name']['exists']);
        $this->assertFalse($snapshots['_ppcart_company_address']['exists']);
    }

    public function test_page_ids_created_after_registration_are_validated_live(): void
    {
        wp_get_ability('publishpress-cart/update-settings');
        $id = self::factory()->post->create(['post_type' => 'page', 'post_status' => 'publish']);
        $before = $this->execute('get-settings', []);
        $saved = $this->execute('update-settings', [
            'changes' => (object) ['_ppcart_myaccount_page_id' => (string) $id], 'revision' => $before['revision'],
        ]);
        $this->assertNotWPError($saved);
        $this->assertSame((string) $id, (string) get_option('_ppcart_myaccount_page_id'));
        $this->assertWPError($this->execute('update-settings', [
            'changes' => (object) ['_ppcart_myaccount_page_id' => $id], 'revision' => $saved['revision'],
        ]));
    }

    public function test_default_public_discovery_is_disabled(): void
    {
        $ability = wp_get_ability('publishpress-cart/get-settings');
        $this->assertFalse($ability->get_meta()['public']);
        $this->assertFalse($ability->get_meta()['mcp']['public']);
        $this->assertFalse($ability->get_meta()['show_in_rest']);
    }
    public function test_recovery_preserves_a_newer_native_setting_value(): void
    {
        update_option('_ppcart_company_name', 'Original');
        update_option('_ppcart_company_address', 'Old address');
        $before = $this->execute('get-settings', []);
        $interfere = static function ($value, $old) {
            update_option('_ppcart_company_name', 'Concurrent native edit');
            return $old;
        };
        add_filter('pre_update_option__ppcart_company_address', $interfere, 10, 2);
        try {
            $result = $this->execute('update-settings', [
                'revision' => $before['revision'], 'changes' => (object) [
                    '_ppcart_company_name' => 'MCP value', '_ppcart_company_address' => 'Blocked address',
                ],
            ]);
        } finally {
            remove_filter('pre_update_option__ppcart_company_address', $interfere, 10);
        }
        $this->assertWPError($result);
        $this->assertSame('Concurrent native edit', get_option('_ppcart_company_name'));
        $this->assertSame('Old address', get_option('_ppcart_company_address'));
    }

}
