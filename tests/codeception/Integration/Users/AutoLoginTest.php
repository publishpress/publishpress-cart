<?php

declare(strict_types=1);

namespace Tests\Integration\Users;

use lucatume\WPBrowser\TestCase\WPTestCase;

/**
 * Auto-login after purchase (publishpress/publishpress-cart#1083).
 */
class AutoLoginTest extends WPTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        add_filter('send_auth_cookies', '__return_false');
        wp_set_current_user(0);
        unset($_POST['ppcart-auto-login']);
    }

    protected function tearDown(): void
    {
        remove_filter('send_auth_cookies', '__return_false');
        unset($_POST['ppcart-auto-login']);
        wp_set_current_user(0);
        parent::tearDown();
    }

    private function make_order(string $status = 'paid'): int
    {
        $order = new \PPCart_Order();
        $order->product_name = 'Test order';
        $order->status       = $status;

        return (int) $order->store();
    }

    public function test_IT_1083_new_customer_is_logged_in_on_paid_order(): void
    {
        $order_id = $this->make_order();
        $user_id  = ppcart_create_user($order_id, 'buyer1083@example.com', 'Buyer', 'One', 'subscriber', false);
        ppcart_update_post_meta($order_id, 'auto_login', 1);

        $this->assertTrue(ppcart_maybe_auto_login_order_buyer($order_id));
        $this->assertSame($user_id, get_current_user_id());
        $this->assertNotEmpty(ppcart_get_post_meta($order_id, 'auto_login_done', true));
    }

    public function test_IT_1083_login_happens_only_once_per_order(): void
    {
        $order_id = $this->make_order();
        ppcart_create_user($order_id, 'buyer1083b@example.com', 'Buyer', 'Two', 'subscriber', false);
        ppcart_update_post_meta($order_id, 'auto_login', 1);

        $this->assertTrue(ppcart_maybe_auto_login_order_buyer($order_id));
        wp_set_current_user(0);
        $this->assertFalse(ppcart_maybe_auto_login_order_buyer($order_id));
    }

    public function test_IT_1083_existing_account_is_never_logged_in(): void
    {
        $existing = $this->factory()->user->create([ 'user_email' => 'member1083@example.com', 'role' => 'subscriber' ]);
        $order_id = $this->make_order();
        ppcart_create_user($order_id, 'member1083@example.com', 'Someone', 'Else', 'subscriber', false);
        ppcart_update_post_meta($order_id, 'auto_login', 1);

        $this->assertSame($existing, (int) ppcart_get_post_meta($order_id, 'user_account', true));
        $this->assertFalse(ppcart_maybe_auto_login_order_buyer($order_id));
        $this->assertSame(0, get_current_user_id());
    }

    public function test_IT_1083_no_login_without_auto_login_request(): void
    {
        $order_id = $this->make_order();
        ppcart_create_user($order_id, 'buyer1083c@example.com', 'Buyer', 'Three', 'subscriber', false);

        $this->assertFalse(ppcart_maybe_auto_login_order_buyer($order_id));
    }

    public function test_IT_1083_no_login_for_unpaid_order(): void
    {
        $order_id = $this->make_order('pending-payment');
        ppcart_create_user($order_id, 'buyer1083d@example.com', 'Buyer', 'Four', 'subscriber', false);
        ppcart_update_post_meta($order_id, 'auto_login', 1);

        $this->assertFalse(ppcart_maybe_auto_login_order_buyer($order_id));
    }

    public function test_IT_1083_no_login_after_window(): void
    {
        $order_id = $this->make_order();
        ppcart_create_user($order_id, 'buyer1083e@example.com', 'Buyer', 'Five', 'subscriber', false);
        ppcart_update_post_meta($order_id, 'auto_login', 1);
        add_filter('ppcart_auto_login_window', '__return_zero');

        try {
            wp_update_post([ 'ID' => $order_id, 'post_date_gmt' => gmdate('Y-m-d H:i:s', time() - 120), 'post_date' => gmdate('Y-m-d H:i:s', time() - 120) ]);
            $this->assertFalse(ppcart_maybe_auto_login_order_buyer($order_id));
        } finally {
            remove_filter('ppcart_auto_login_window', '__return_zero');
        }
    }

    public function test_IT_1083_posted_flag_is_remembered_on_order(): void
    {
        $order_id = $this->make_order();
        $_POST['ppcart-auto-login'] = '1';
        ppcart_remember_auto_login_request($order_id);
        unset($_POST['ppcart-auto-login']);

        $this->assertTrue(ppcart_order_wants_auto_login($order_id));
    }
}
