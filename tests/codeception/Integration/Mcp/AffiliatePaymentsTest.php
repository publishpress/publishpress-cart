<?php

declare(strict_types=1);

namespace Tests\Integration\Mcp;

use Tests\Support\Integration\McpTestCase;

require_once dirname(__DIR__, 2) . '/Support/Integration/McpTestCase.php';

class AffiliatePaymentsTest extends McpTestCase
{
    public function test_payout_marks_only_its_own_commissions_and_notifies_once(): void
    {
        if (! ppcart_is_pro()) {
            $this->markTestSkipped('Requires Pro affiliate tables.');
        }
        global $ppcart_pro_extensions, $wpdb;
        $this->assertNotWPError($ppcart_pro_extensions->set_control_state('affiliate', true));
        $affiliate = \PPCart_Pro_Affiliate::create_affiliate(['first_name' => 'Sample', 'last_name' => 'Affiliate', 'email' => 'payout-' . wp_generate_password(8, false) . '@example.org', 'paypal_email' => '', 'status' => 'pending', 'user_id' => 0]);
        $this->assertIsInt($affiliate);
        $order = self::factory()->post->create(['post_type' => ppcart_live_post_type('order')]);
        $product = self::factory()->post->create(['post_type' => ppcart_live_post_type('product')]);
        $commission_table = ppcart_pro_live_table('affiliate_commissions');
        $wpdb->insert($commission_table, ['affiliate_id' => $affiliate, 'affiliate_name' => 'Sample', 'product_name' => 'Sample',
            'commission_name' => 'Base', 'commission_type' => 'amount', 'commission_apply' => 'product_only',
            'order_id' => $order, 'product_id' => $product, 'commission_price' => '5', 'commission_status' => 'unpaid',
            'order_status' => 'paid', 'total' => '20', 'order_type' => 'main', 'date_recorded' => current_time('mysql')]);
        $commission = (int) $wpdb->insert_id;
        $wpdb->insert(ppcart_pro_live_table('affiliate_payouts'), ['affiliate_id' => $affiliate, 'payout_amount' => '5',
            'paypal_email' => 'payout@example.org', 'payout_status' => 'unpaid', 'commission_ids' => (string) $commission, 'transaction_id' => 'private-reference']);
        $payout = (int) $wpdb->insert_id;
        $read = $this->execute('get-affiliate-payment', ['kind' => 'payout', 'id' => $payout]);
        $this->assertNotWPError($read);
        $this->assertStringNotContainsString('private-reference', wp_json_encode($read));
        $events = 0;
        $capture = static function () use (&$events) { ++$events; };
        add_action('ppcart_affiliate_commission_paid', $capture);
        add_filter('pre_wp_mail', '__return_true');
        try {
            $patch = ['kind' => 'payout', 'id' => $payout, 'action' => 'paid', 'revision' => $read['revision'], 'dry_run' => true];
            $this->assertNotWPError($this->execute('update-affiliate-payment', $patch));
            $this->assertSame(0, $events);
            $patch['dry_run'] = false;
            $saved = $this->execute('update-affiliate-payment', $patch);
            $this->assertNotWPError($saved);
            $this->assertSame('paid', \PPCart_Pro_Affiliate_Financials::get('commission', $commission)['commission_status']);
            $this->assertSame(1, $events);
            $this->assertWPError($this->execute('update-affiliate-payment', $patch));
            $patch['revision'] = $saved['revision'];
            $this->assertNotWPError($this->execute('update-affiliate-payment', $patch));
            $this->assertSame(1, $events);
        } finally {
            remove_action('ppcart_affiliate_commission_paid', $capture);
            remove_filter('pre_wp_mail', '__return_true');
        }
        $wpdb->update(ppcart_pro_live_table('affiliate_payouts'), ['affiliate_id' => $affiliate + 1], ['id' => $payout]);
        $this->assertWPError($this->execute('get-affiliate-payment', ['kind' => 'payout', 'id' => $payout]));
    }

    public function test_native_payout_commits_its_own_transaction_without_a_caller_transaction(): void
    {
        if (! ppcart_is_pro()) {
            $this->markTestSkipped('Requires native Pro affiliate tables.');
        }
        global $wpdb;
        $previous = $wpdb;
        $database = $previous->get_var('SELECT DATABASE()');
        $connection = new \wpdb(DB_USER, DB_PASSWORD, $database, DB_HOST);
        $connection->set_prefix($previous->prefix);
        $affiliate = $commission = $payout = 0;
        $tables = ['affiliate' => ppcart_pro_live_table('affiliates'), 'commission' => ppcart_pro_live_table('affiliate_commissions'), 'payout' => ppcart_pro_live_table('affiliate_payouts')];
        add_filter('pre_wp_mail', '__return_true');
        $wpdb = $connection;
        try {
            $affiliate = \PPCart_Pro_Affiliate::create_affiliate(['first_name' => 'Standalone', 'last_name' => 'Fixture',
                'email' => 'transaction-' . wp_generate_password(8, false) . '@example.org', 'paypal_email' => '', 'status' => 'pending', 'user_id' => 0]);
            $this->assertIsInt($affiliate);
            $connection->insert($tables['commission'], ['affiliate_id' => $affiliate, 'affiliate_name' => 'Fixture', 'product_name' => 'Fixture',
                'commission_name' => 'Base', 'commission_type' => 'amount', 'commission_apply' => 'product_only', 'order_id' => 0, 'product_id' => 0,
                'commission_price' => '5', 'commission_status' => 'unpaid', 'order_status' => 'paid', 'total' => '20', 'order_type' => 'main', 'date_recorded' => current_time('mysql')]);
            $commission = (int) $connection->insert_id;
            $connection->insert($tables['payout'], ['affiliate_id' => $affiliate, 'payout_amount' => '5', 'payout_status' => 'unpaid', 'commission_ids' => (string) $commission]);
            $payout = (int) $connection->insert_id;
            $this->assertNotWPError(\PPCart_Pro_Affiliate_Financials::change('payout', $payout, 'paid'));
            $this->assertSame('paid', \PPCart_Pro_Affiliate_Financials::get('payout', $payout)['payout_status']);
            $this->assertSame('paid', \PPCart_Pro_Affiliate_Financials::get('commission', $commission)['commission_status']);
        } finally {
            $created = ['affiliate' => $affiliate, 'commission' => $commission, 'payout' => $payout];
            foreach ($tables as $kind => $table) {
                if ($created[$kind] && is_int($created[$kind])) {
                    $connection->delete($table, ['id' => $created[$kind]], ['%d']);
                }
            }
            $wpdb = $previous;
            $connection->close();
            remove_filter('pre_wp_mail', '__return_true');
        }
    }

}
