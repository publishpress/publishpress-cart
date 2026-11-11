<?php

declare(strict_types=1);

namespace Tests\Integration\Mcp;

use Tests\Support\Integration\McpTestCase;

require_once dirname(__DIR__, 2) . '/Support/Integration/McpTestCase.php';

class AffiliateEnrollmentTest extends McpTestCase
{
    public function test_role_setting_preview_preflights_users_and_native_enrollment_stays_active(): void
    {
        if (! ppcart_is_pro()) {
            $this->markTestSkipped('Affiliate enrollment requires Pro.');
        }
        update_option('_ppcart_affiliate_user_roles', []);
        $user = self::factory()->user->create(['role' => 'subscriber', 'first_name' => 'Native', 'last_name' => 'Role']);
        $before = $this->execute('get-settings', []);
        $patch = ['revision' => $before['revision'], 'changes' => (object) ['_ppcart_affiliate_user_roles' => ['subscriber']], 'dry_run' => true];
        $this->assertNotWPError($this->execute('update-settings', $patch));
        $this->assertNull(\PPCart_Pro_Affiliate::find_by_user_id($user));
        $deny = static function ($caps, $cap, $actor, $args) use ($user) {
            return 'edit_user' === $cap && ($args[0] ?? 0) === $user ? ['do_not_allow'] : $caps;
        };
        add_filter('map_meta_cap', $deny, 10, 4);
        try {
            $patch['dry_run'] = false;
            $this->assertWPError($this->execute('update-settings', $patch));
            $this->assertSame([], get_option('_ppcart_affiliate_user_roles'));
            $this->assertNull(\PPCart_Pro_Affiliate::find_by_user_id($user));
        } finally {
            remove_filter('map_meta_cap', $deny, 10);
        }
        $this->assertNotWPError($this->execute('update-settings', $patch));
        $affiliate = \PPCart_Pro_Affiliate::find_by_user_id($user);
        $this->assertNotNull($affiliate);
        $this->assertSame('active', $affiliate->status);
        $this->assertNotEmpty($affiliate->code);
        $this->assertTrue($affiliate->is_role_affiliate());
        get_userdata($user)->set_role('author');
        $this->assertFalse((new \PPCart_Pro_Affiliate($affiliate->id))->is_role_affiliate());
    }

    public function test_adding_and_removing_a_qualifying_role_updates_native_eligibility(): void
    {
        if (! ppcart_is_pro()) {
            $this->markTestSkipped('Affiliate enrollment requires Pro.');
        }
        update_option('_ppcart_affiliate_user_roles', []);
        $user = self::factory()->user->create(['role' => 'subscriber']);
        update_option('_ppcart_affiliate_user_roles', ['author']);
        $account = get_userdata($user);
        $account->add_role('author');
        $affiliate = \PPCart_Pro_Affiliate::find_by_user_id($user);
        $this->assertNotNull($affiliate);
        $this->assertTrue($affiliate->is_role_affiliate());
        $account->remove_role('author');
        $this->assertFalse((new \PPCart_Pro_Affiliate($affiliate->id))->is_role_affiliate());
    }
}
