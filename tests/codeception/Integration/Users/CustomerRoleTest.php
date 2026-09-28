<?php

declare(strict_types=1);

namespace Tests\Integration\Users;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Product_Metabox_Option_Sources;

class CustomerRoleTest extends WPTestCase
{
    public function test_IT_340_privileged_roles_are_detected(): void
    {
        $this->assertTrue(ppcart_is_privileged_role('administrator'));
        $this->assertTrue(ppcart_is_privileged_role('missing-role'));
        $this->assertFalse(ppcart_is_privileged_role('subscriber'));
    }

    public function test_IT_341_privileged_role_falls_back_to_default_role(): void
    {
        update_option('default_role', 'subscriber');

        $this->assertSame('subscriber', ppcart_safe_customer_role('administrator'));
        $this->assertSame('author', ppcart_safe_customer_role('author'));
        $this->assertSame('', ppcart_safe_customer_role(''));
    }

    public function test_IT_342_privileged_role_can_be_allowed_by_filter(): void
    {
        add_filter('ppcart_allow_privileged_customer_role', '__return_true');

        $this->assertSame('administrator', ppcart_safe_customer_role('administrator'));

        remove_filter('ppcart_allow_privileged_customer_role', '__return_true');
    }

    public function test_IT_344_integration_role_options_start_with_subscriber(): void
    {
        require_once ABSPATH . 'wp-admin/includes/user.php';
        wp_set_current_user($this->factory()->user->create([ 'role' => 'administrator' ]));

        $roles = (new PPCart_Product_Metabox_Option_Sources())->get_customer_user_roles();

        $this->assertSame('subscriber', array_key_first($roles));
        $this->assertArrayNotHasKey('administrator', $roles);
    }
}
