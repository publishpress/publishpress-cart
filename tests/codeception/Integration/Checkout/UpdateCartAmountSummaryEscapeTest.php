<?php

declare(strict_types=1);

namespace Tests\Integration\Checkout;

use lucatume\WPBrowser\TestCase\WPTestCase;

class UpdateCartAmountSummaryEscapeTest extends WPTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    /**
     * @test-id IT-375
     */
    public function test_IT_375_update_cart_amount_response_uses_plain_text_summary_fields(): void
    {
        if (! function_exists('ppcart_prepare_update_cart_amount_response')) {
            require_once PPCART_BASE_DIR . 'public/controllers/order/traits/trait-ppcart-public-order-cart.php';
        }

        $productName = 'Size < 10 <img src=x onerror=alert(1)>';
        $couponId = '<b>SAVE</b>';

        $order = new \PPCart_Order();
        $order->items = [
            [
                'item_type'    => 'main',
                'product_name' => $productName,
                'subtotal'     => 100.0,
                'total_amount' => 100.0,
                'quantity'     => 1,
            ],
        ];
        $order->coupon_id = $couponId;
        $order->coupon = [
            'discount_amount' => 10.0,
        ];
        $order->amount = 90.0;
        $order->quantity = 1;
        $order->pre_tax_amount = 100.0;
        $order->plan = (object) [
            'type'            => 'recurring',
            'price'           => 100.0,
            'option_id'       => 'plan_monthly',
            'name'            => 'Monthly',
            'stripe_id'       => 'price_test',
            'installments'    => -1,
            'interval'        => 'month',
            'frequency'       => 1,
            'next_bill_date'  => gmdate('Y-m-d'),
        ];

        $applyCouponDiscount = static function ($sub) {
            $sub->sub_discount = 10.0;
            $sub->sub_discount_duration = 3;
        };
        add_action('ppcart_subscription_apply_coupon', $applyCouponDiscount, 10, 2);

        $order->calculate_final_amounts();

        $productRow = null;
        $couponRow = null;
        foreach ($order->order_summary_items as $row) {
            if (isset($row['type']) && 'main' === $row['type']) {
                $productRow = $row;
            }
            if (isset($row['type']) && 'discount' === $row['type']) {
                $couponRow = $row;
            }
        }

        $this->assertNotNull($productRow);
        $this->assertStringContainsString('Size < 10', (string) $productRow['name']);
        $this->assertStringNotContainsString('ppcart-badge', (string) $productRow['name']);

        $this->assertNotNull($couponRow);
        $this->assertSame($couponId, $couponRow['coupon_id']);
        $this->assertStringNotContainsString('<span', (string) $couponRow['name']);

        $response = ppcart_prepare_update_cart_amount_response(
            $order,
            'Amount Due',
            'Due Today'
        );

        remove_action('ppcart_subscription_apply_coupon', $applyCouponDiscount, 10);

        $this->assertArrayHasKey('sub_summary', $response);
        foreach ($response['order_summary_items'] as $summaryRow) {
            $this->assertStringNotContainsString(
                'ppcart-Price-currencySymbol',
                (string) $summaryRow['subtotal']
            );
        }
        $this->assertStringNotContainsString('<br', (string) $response['sub_summary']);
        $this->assertStringNotContainsString('<script', (string) $response['sub_summary']);
        $this->assertStringNotContainsString('ppcart-Price-currencySymbol', (string) $response['total_price']);

        $orderForList = new \PPCart_Order();
        foreach (get_object_vars($order) as $key => $value) {
            $orderForList->$key = $value;
        }
        $orderForList->id = 999374;

        add_filter(
            'ppcart_order',
            static function ($loaded) use ($orderForList) {
                return $orderForList;
            },
            10,
            1
        );

        $itemList = ppcart_get_item_list($orderForList->id, false);
        remove_all_filters('ppcart_order');

        $this->assertNotEmpty($itemList['discounts']);
        $discountName = (string) $itemList['discounts'][0]['product_name'];
        $this->assertStringContainsString('ppcart-badge', $discountName);
        $this->assertStringContainsString('&lt;B&gt;SAVE&lt;/B&gt;', $discountName);
        $this->assertStringNotContainsString('<b>SAVE</b>', $discountName);
    }

    /**
     * @test-id IT-376
     */
    public function test_IT_376_plain_text_price_decodes_currency_entities(): void
    {
        global $ppcart_currency_symbol;

        update_option('_ppcart_currency_position', 'left');
        update_option('_ppcart_decimal_number', '2');
        update_option('_ppcart_decimal_separator', '.');
        update_option('_ppcart_thousand_separator', '');

        $ppcart_currency_symbol = '&#36;';
        $this->assertSame('$23.00', ppcart_format_price(23, false));

        $ppcart_currency_symbol = '&#82;&#36;';
        $this->assertSame('R$23.00', ppcart_format_price(23, false));
    }
}
