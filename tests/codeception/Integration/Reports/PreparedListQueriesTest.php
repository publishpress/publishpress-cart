<?php

declare(strict_types=1);

namespace Tests\Integration\Reports;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Admin_Filters;
use PPCart_Contacts_Page;
use PPCart_Dashboard_Order_Data;
use PPCart_Dashboard_Plan_Data;
use PPCart_Price_Format;
use ReflectionClass;
use WP_Query;

/**
 * Queries that bind canonical post type, status, and meta key lists as
 * prepare() arguments return the rows the seeded data calls for.
 */
class PreparedListQueriesTest extends WPTestCase
{
    /**
     * @param string               $status Logical order status.
     * @param array<string, mixed> $meta   Meta suffix => value.
     * @param string               $type   Post type family.
     */
    private function insertPost(string $status, array $meta = [], string $type = 'order'): int
    {
        $postId = wp_insert_post(
            [
                'post_type'   => ppcart_live_post_type($type),
                'post_status' => \PPCart_Status_Labels::registered_slug($status),
                'post_title'  => 'Prepared list ' . $type,
            ]
        );
        $this->assertIsInt($postId);

        foreach ($meta as $name => $value) {
            update_post_meta($postId, ppcart_meta_key($name), $value);
        }

        return $postId;
    }

    private function requireAdminFile(string $class, string $file): void
    {
        if (! class_exists($class, false)) {
            require_once dirname(__DIR__, 4) . '/' . $file;
        }
    }

    /**
     * @return object
     */
    private function withoutConstructor(string $class)
    {
        return (new ReflectionClass($class))->newInstanceWithoutConstructor();
    }

    /**
     * @return mixed
     */
    private function callPrivate($object, string $method)
    {
        $reflection = new \ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($object);
    }

    /**
     * @test-id IT-387
     */
    public function test_IT_387_user_phone_is_read_from_latest_order_of_the_user(): void
    {
        $userId  = self::factory()->user->create();
        $otherId = self::factory()->user->create();

        $this->insertPost('paid', [ 'user_account' => $userId, 'phone' => '555-0100' ]);
        $this->insertPost('paid', [ 'user_account' => $userId, 'phone' => '555-0199' ]);
        $this->insertPost('paid', [ 'user_account' => $otherId, 'phone' => '555-0000' ]);

        $this->assertSame('555-0199', ppcart_get_user_phone($userId));
        $this->assertSame('555-0199', ppcart_get_user_meta($userId, 'phone', true));
    }

    /**
     * @test-id IT-387
     */
    public function test_IT_387_user_address_is_read_from_latest_order_of_the_user(): void
    {
        $userId = self::factory()->user->create();

        $this->insertPost(
            'paid',
            [
                'user_account' => $userId,
                'address1'     => '1 Main St',
                'city'         => 'Springfield',
                'country'      => 'US',
            ]
        );

        $address = ppcart_get_user_address($userId);

        $this->assertIsArray($address);
        $this->assertSame('Springfield', ppcart_get_user_meta($userId, 'city', true));
        $this->assertSame('1 Main St', ppcart_get_user_meta($userId, 'address_1', true));
    }

    /**
     * @test-id IT-387
     */
    public function test_IT_387_report_customer_search_matches_orders_only(): void
    {
        $this->insertPost('paid', [ 'email' => 'zelda.prepared@example.com', 'firstname' => 'Zelda', 'lastname' => 'Prepared' ]);
        $trashed = $this->insertPost('paid', [ 'email' => 'zelda.trashed@example.com', 'firstname' => 'Zelda', 'lastname' => 'Trashed' ]);
        wp_trash_post($trashed);
        $this->insertPost('active', [ 'email' => 'zelda.subscription@example.com', 'firstname' => 'Zelda' ], 'subscription');

        $emails = array_column(ppcart_search_report_customers('zelda'), 'email');

        $this->assertContains('zelda.prepared@example.com', $emails);
        $this->assertNotContains('zelda.trashed@example.com', $emails);
        $this->assertNotContains('zelda.subscription@example.com', $emails);
    }

    /**
     * @test-id IT-387
     */
    public function test_IT_387_subscription_order_totals_count_paid_orders_only(): void
    {
        $subscriptionId = $this->insertPost('active', [], 'subscription');
        $otherId        = $this->insertPost('active', [], 'subscription');

        $this->insertPost('paid', [ 'subscription_id' => $subscriptionId, 'amount' => '10.50' ]);
        $this->insertPost('paid', [ 'subscription_id' => $subscriptionId, 'amount' => '4.50' ]);
        $this->insertPost('pending-payment', [ 'subscription_id' => $subscriptionId, 'amount' => '99' ]);
        $this->insertPost('paid', [ 'subscription_id' => $otherId, 'amount' => '1' ]);

        $totals = ppcart_get_report_subscription_order_totals([ $subscriptionId ]);

        $this->assertSame([ $subscriptionId ], array_keys($totals));
        $this->assertSame(2, $totals[ $subscriptionId ]['count']);
        $this->assertEqualsWithDelta(15.0, $totals[ $subscriptionId ]['total'], 0.0001);
        $this->assertSame([], ppcart_get_report_subscription_order_totals([]));
    }

    /**
     * @test-id IT-387
     */
    public function test_IT_387_customers_list_includes_user_orders(): void
    {
        $userId = self::factory()->user->create([ 'user_email' => 'customer.prepared@example.com' ]);
        $paidId = $this->insertPost('paid', [ 'user_account' => $userId, 'amount' => '12' ]);

        $customers = ppcart_get_customers($userId);

        $this->assertArrayHasKey('customer.prepared@example.com', $customers);
        $this->assertSame([ $paidId ], array_column($customers['customer.prepared@example.com'], 'id'));
        $this->assertArrayHasKey('customer.prepared@example.com', ppcart_get_customers());
    }

    /**
     * @test-id IT-387
     */
    public function test_IT_387_dashboard_order_summary_counts_seeded_orders(): void
    {
        $this->requireAdminFile(PPCart_Dashboard_Order_Data::class, 'admin/dashboard/class-ppcart-dashboard-order-data.php');
        $data   = new PPCart_Dashboard_Order_Data();
        $before = $data->get_summary();

        $this->insertPost('paid', [ 'amount' => '10', 'status' => 'paid' ]);
        $this->insertPost('paid', [ 'amount' => '5.5', 'status' => 'paid', 'renewal' => '1' ]);
        $this->insertPost('active', [ 'amount' => '100', 'status' => 'paid' ], 'subscription');

        $after = $data->get_summary();

        $this->assertSame($before['total_orders'] + 2, $after['total_orders']);
        $this->assertEqualsWithDelta($before['total_sales'] + 15.5, $after['total_sales'], 0.0001);
        $this->assertSame($before['paid_orders'] + 1, $after['paid_orders']);
    }

    /**
     * @test-id IT-387
     */
    public function test_IT_387_dashboard_plan_collected_amount_sums_paid_installment_orders(): void
    {
        $this->requireAdminFile(PPCart_Dashboard_Plan_Data::class, 'admin/dashboard/class-ppcart-dashboard-plan-data.php');
        $data   = new PPCart_Dashboard_Plan_Data();
        $before = (float) $this->callPrivate($data, 'get_collected_amount');

        $planId      = $this->insertPost('active', [ 'sub_installments' => '3' ], 'subscription');
        $unlimitedId = $this->insertPost('active', [ 'sub_installments' => '-1' ], 'subscription');

        $this->insertPost('paid', [ 'subscription_id' => $planId, 'amount' => '20' ]);
        $this->insertPost('pending-payment', [ 'subscription_id' => $planId, 'amount' => '7' ]);
        $this->insertPost('paid', [ 'subscription_id' => $unlimitedId, 'amount' => '30' ]);

        $after = (float) $this->callPrivate($data, 'get_collected_amount');

        $this->assertEqualsWithDelta($before + 20, $after, 0.0001);
    }

    /**
     * @test-id IT-387
     */
    public function test_IT_387_contacts_page_lists_order_emails(): void
    {
        $this->requireAdminFile(PPCart_Contacts_Page::class, 'admin/class-ppcart-contacts-page.php');
        $this->insertPost('paid', [ 'email' => 'contact.prepared@example.com', 'amount' => '3' ]);
        $trashed = $this->insertPost('paid', [ 'email' => 'contact.trashed@example.com' ]);
        wp_trash_post($trashed);

        $page = $this->withoutConstructor(PPCart_Contacts_Page::class);
        ob_start();
        $page->render_page_contacts();
        $html = (string) ob_get_clean();

        $this->assertStringContainsString('contact.prepared@example.com', $html);
        $this->assertStringNotContainsString('contact.trashed@example.com', $html);
    }

    /**
     * @test-id IT-387
     */
    public function test_IT_387_admin_search_matches_order_email_meta(): void
    {
        global $wpdb;

        $this->requireAdminFile(PPCart_Admin_Filters::class, 'admin/class-ppcart-admin-filters.php');
        $matchId = $this->insertPost('paid', [ 'email' => 'needle.prepared@example.com' ]);
        $otherId = $this->insertPost('paid', [ 'email' => 'other@example.com' ]);

        if (! function_exists('set_current_screen')) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
            require_once ABSPATH . 'wp-admin/includes/screen.php';
        }

        set_current_screen('edit-post');
        $_GET['s']        = 'needle.prepared';
        $query            = new WP_Query();
        $query->is_search = true;

        try {
            $filters  = $this->withoutConstructor(PPCart_Admin_Filters::class);
            $fragment = $filters->custom_posts_search('', $query);
        } finally {
            unset($_GET['s']);
            set_current_screen('front');
        }

        $found = array_map(
            'intval',
            $wpdb->get_col("SELECT {$wpdb->posts}.ID FROM {$wpdb->posts} WHERE {$wpdb->posts}.ID IN ({$matchId}, {$otherId}) {$fragment}")
        );

        $this->assertSame([ $matchId ], $found);
    }

    /**
     * @test-id IT-387
     */
    public function test_IT_387_price_format_migration_updates_orders_and_subscriptions_only(): void
    {
        if (! class_exists(PPCart_Price_Format::class, false)) {
            require_once dirname(__DIR__, 4) . '/includes/class-ppcart-price-format.php';
        }

        $orderId        = $this->insertPost('paid', [ 'amount' => '2.000,25' ]);
        $subscriptionId = $this->insertPost('active', [ 'sub_amount' => '1.234,50' ], 'subscription');
        $productId      = $this->insertPost('paid', [ 'amount' => '2.000,25', 'sub_amount' => '1.234,50' ], 'product');

        $format               = $this->withoutConstructor(PPCart_Price_Format::class);
        $format->thousand_sep = '.';
        $format->decimal_sep  = ',';
        $format->update_ppcart_order_amount();
        $format->update_ppcart_subscriptions_amount();

        $this->assertSame('2000.250000000', ppcart_get_post_meta($orderId, 'amount', true));
        $this->assertSame('1234.500000000', ppcart_get_post_meta($subscriptionId, 'sub_amount', true));
        $this->assertSame('2.000,25', ppcart_get_post_meta($productId, 'amount', true));
        $this->assertSame('1.234,50', ppcart_get_post_meta($productId, 'sub_amount', true));
    }
}
