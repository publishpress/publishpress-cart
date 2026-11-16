<?php

declare(strict_types=1);

namespace Tests\Integration\Mcp;

use Tests\Support\Integration\McpTestCase;

require_once dirname(__DIR__, 2) . '/Support/Integration/McpTestCase.php';

class ExportTest extends McpTestCase
{
    private function order(string $email, array $extra = [], string $date = '2026-10-10 12:00:00'): int
    {
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('order'),
            'post_author' => get_current_user_id(), 'post_date' => $date, 'post_status' => 'publish']);
        foreach ($extra + ['email' => $email, 'firstname' => 'Jane', 'lastname' => 'Buyer', 'phone' => '555-0100',
            'address1' => '10 Example Street', 'address2' => 'Unit 2', 'city' => 'Example City', 'state' => 'CA',
            'zip' => '90210', 'country' => 'US', 'amount' => '25.50', 'currency' => 'USD',
            'status' => 'paid', 'payment_status' => 'paid', 'pay_method' => 'cod'] as $key => $value) {
            ppcart_update_post_meta($id, $key, $value);
        }
        return $id;
    }

    private function rows(array $result): array
    {
        $parsed = \PPCart_Mcp_Csv::parse($result['csv']);
        $this->assertNotWPError($parsed);
        return $parsed['rows'];
    }

    public function test_native_customer_fields_and_formula_escaping_exclude_secrets(): void
    {
        $id = $this->order('csv-buyer@example.test', ['firstname' => '  =2+2', 'lastname' => 'Comma, Quote "Buyer"',
            'invoice_token' => 'synthetic-private-token', 'transaction_id' => 'synthetic-private-gateway',
            'page_url' => 'https://example.test/?secret=synthetic-private-url',
            'custom_fields' => ['password' => ['value' => 'synthetic-private-password']]]);
        $this->assertSame("'  =2+2\n", \PPCart_Mcp_Csv::encode([['&#32;&#32;=2+2']]));
        $before = get_post_meta($id);
        $result = $this->execute('export-records-csv', ['kind' => 'order', 'email' => 'csv-buyer@example.test']);
        $this->assertNotWPError($result);
        $rows = $this->rows($result);
        $this->assertCount(1, $rows);
        $this->assertSame((string) $id, $rows[0]['id']);
        $this->assertSame("'=2+2", $rows[0]['firstname']);
        $this->assertSame('Comma, Quote "Buyer"', $rows[0]['lastname']);
        $this->assertSame('csv-buyer@example.test', $rows[0]['email']);
        $this->assertSame('10 Example Street', $rows[0]['address1']);
        $this->assertSame('555-0100', $rows[0]['phone']);
        $this->assertEquals(25.5, (float) $rows[0]['amount']);
        $this->assertEquals(\PPCart_Order_Refunds::get_net_amount($id), (float) $rows[0]['net_amount']);
        $this->assertStringNotContainsString('synthetic-private', $result['csv']);
        $this->assertSame($before, get_post_meta($id));
    }

    public function test_order_filters_dates_and_pagination_use_native_records(): void
    {
        $product = self::factory()->post->create(['post_type' => ppcart_live_post_type('product')]);
        $ids = [];
        foreach (['2026-10-10 00:00:00', '2026-10-10 23:59:59', '2026-10-11 12:00:00'] as $date) {
            $ids[] = $this->order('csv-paged@example.test', ['product_id' => $product], $date);
        }
        $this->order('csv-other@example.test', ['product_id' => $product]);
        $input = ['kind' => 'order', 'email' => 'csv-paged@example.test', 'product_id' => $product,
            'from' => '2026-10-10', 'to' => '2026-10-10', 'per_page' => 1];
        $first = $this->execute('export-records-csv', $input);
        $this->assertNotWPError($first);
        $this->assertSame((string) $ids[0], $this->rows($first)[0]['id']);
        $this->assertSame(2, $first['next_page']);
        $second = $this->execute('export-records-csv', $input + ['page' => 2]);
        $this->assertNotWPError($second);
        $this->assertSame((string) $ids[1], $this->rows($second)[0]['id']);
        $this->assertNull($second['next_page']);
        $empty = $this->execute('export-records-csv', $input + ['page' => 3]);
        $this->assertNotWPError($empty);
        $this->assertSame(0, $empty['rows']);
    }

    public function test_contacts_aggregate_native_orders_and_check_older_object_permissions(): void
    {
        $older = $this->order('csv-contact@example.test', [], '2026-10-01 12:00:00');
        $this->order('csv-contact@example.test', ['firstname' => 'Latest name'], '2026-10-02 12:00:00');
        $input = ['kind' => 'contacts', 'email' => 'csv-contact@example.test'];
        $result = $this->execute('export-records-csv', $input);
        $this->assertNotWPError($result);
        $rows = $this->rows($result);
        $this->assertCount(1, $rows);
        $this->assertSame('Latest name', $rows[0]['First Name']);
        $this->assertSame('2', $rows[0]['Orders']);
        $this->assertSame('csv-contact@example.test', $rows[0]['Email']);
        $deny = static function ($caps, $cap, $user, $args) use ($older) {
            return in_array($cap, ['read_post', 'edit_post'], true) && (int) ($args[0] ?? 0) === $older ? ['do_not_allow'] : $caps;
        };
        add_filter('map_meta_cap', $deny, 1000, 4);
        try {
            $result = $this->execute('export-records-csv', $input);
            $this->assertWPError($result);
            $this->assertSame('ppcart_mcp_forbidden', $result->get_error_code());
            $this->assertWPError($this->execute('export-records-csv', ['kind' => 'order', 'email' => 'csv-contact@example.test']));
        } finally {
            remove_filter('map_meta_cap', $deny, 1000);
        }
    }

    public function test_denied_report_caps_and_invalid_types_cannot_export(): void
    {
        $this->order('csv-denied@example.test');
        foreach ([['per_page' => '1'], ['per_page' => 101], ['page' => 0], ['email' => 'invalid'],
            ['from' => '2026-02-30'], ['from' => '2026-10-20', 'to' => '2026-10-01'], ['fields' => ['invoice_token']]] as $bad) {
            $this->assertWPError($this->execute('export-records-csv', $bad + ['kind' => 'order']));
        }
        $this->assertWPError($this->execute('export-records-csv', ['kind' => 'contacts', 'from' => '2026-10-01']));
        $type = get_post_type_object(ppcart_live_post_type('order'));
        wp_get_current_user()->add_cap($type->cap->read_private_posts, false);
        $this->assertWPError($this->execute('export-records-csv', ['kind' => 'order']));
        wp_set_current_user(0);
        $this->assertWPError($this->execute('export-records-csv', ['kind' => 'contacts']));
    }

    public function test_subscription_uses_native_values_without_gateway_identifiers(): void
    {
        $id = self::factory()->post->create(['post_type' => ppcart_live_post_type('subscription'), 'post_author' => get_current_user_id()]);
        foreach (['email' => 'csv-sub@example.test', 'product_name' => 'Monthly plan', 'sub_amount' => '12',
            'sub_interval' => 'month', 'sub_frequency' => 1, 'subscription_id' => 'synthetic-private-subscription'] as $key => $value) {
            ppcart_update_post_meta($id, $key, $value);
        }
        $result = $this->execute('export-records-csv', ['kind' => 'subscription', 'email' => 'csv-sub@example.test']);
        $this->assertNotWPError($result);
        $row = $this->rows($result)[0];
        $this->assertSame((string) $id, $row['id']);
        $this->assertSame('Monthly plan', $row['product_name']);
        $this->assertSame('month', $row['sub_interval']);
        $this->assertStringNotContainsString('synthetic-private', $result['csv']);
    }

    public function test_contact_pages_and_oversized_data_are_bounded(): void
    {
        $id = $this->order('000-csv-one@example.test');
        $this->order('000-csv-two@example.test');
        $result = $this->execute('export-records-csv', ['kind' => 'contacts', 'per_page' => 1]);
        $this->assertNotWPError($result);
        $this->assertSame(1, $result['rows']);
        $this->assertSame(2, $result['next_page']);
        ppcart_update_post_meta($id, 'address1', str_repeat('x', 20001));
        $this->assertWPError($this->execute('export-records-csv', ['kind' => 'order', 'email' => '000-csv-one@example.test']));
    }
    public function test_contacts_reject_more_than_one_thousand_contributing_orders(): void
    {
        $ids = self::factory()->post->create_many(1001, ['post_type' => ppcart_live_post_type('order'),
            'post_author' => get_current_user_id(), 'post_status' => 'publish']);
        foreach ($ids as $id) {
            ppcart_update_post_meta($id, 'email', 'csv-large-contact@example.test');
        }
        $result = $this->execute('export-records-csv', ['kind' => 'contacts', 'email' => 'csv-large-contact@example.test']);
        $this->assertWPError($result);
        $this->assertSame('ppcart_mcp_export_limit', $result->get_error_code());
    }

}
