<?php

declare(strict_types=1);

namespace Tests\Integration\Admin;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Admin_Settings;

/**
 * The Stripe connection status box calls Stripe, so it is only built on the
 * Cart settings page, not on every admin request.
 */
class StripeConnectStatusScreenTest extends WPTestCase
{
    /**
     * @var array<string, mixed>
     */
    private $previousGet = array();

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousGet = $_GET;
    }

    protected function tearDown(): void
    {
        $_GET = $this->previousGet;
        remove_all_filters('wp_doing_ajax');
        parent::tearDown();
    }

    private function statusDescription(): string
    {
        $settings = new PPCart_Admin_Settings('publishpress-cart', 'PublishPress Cart', PPCART_VERSION);
        $fields = $settings->get_payment_fields();

        return (string) $fields['stripe']['stripe-connect-status']['settings']['description'];
    }

    public function test_status_box_is_not_built_on_other_admin_pages(): void
    {
        $_GET = array('page' => 'ppcart-orders');

        $this->assertSame('', $this->statusDescription());
    }

    public function test_status_box_is_not_built_during_admin_ajax(): void
    {
        $_GET = array('page' => 'ppcart-settings');
        add_filter('wp_doing_ajax', '__return_true');

        $this->assertSame('', $this->statusDescription());
    }

    public function test_status_box_is_built_on_the_settings_page(): void
    {
        $_GET = array('page' => 'ppcart-settings');

        $this->assertNotSame('', $this->statusDescription());
    }
}
