<?php

namespace unit\OrderRefunds;

use Codeception\Test\Unit;
use PPCart_Order_Refunds;
use Tests\Support\WordPressStubContext;
use UnitTester;

class OrderRefundsTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    protected function _before(): void
    {
        WordPressStubContext::setState('post_meta', array());

        WordPressStubContext::set(
            'get_post_meta',
            function ($post_id, $key, $single = false) {
                $meta = WordPressStubContext::getState('post_meta', array());

                if (! isset($meta[$post_id][$key])) {
                    return $single ? '' : array();
                }

                return $single ? $meta[$post_id][$key] : array($meta[$post_id][$key]);
            }
        );

        if (! class_exists(PPCart_Order_Refunds::class, false)) {
            require_once PPCART_PLUGIN_ROOT . 'includes/class-ppcart-order-refunds.php';
        }
    }

    protected function _after(): void
    {
        WordPressStubContext::clear();
        parent::_after();
    }

    public function test_UT_090_partial_refund_total_is_read_from_refund_metadata(): void
    {
        $this->seedPartialRefundOrder667();

        $this->assertSame(100.0, PPCart_Order_Refunds::get_refund_total(667));
    }

    public function test_UT_091_partial_refund_reduces_the_displayed_net_amount(): void
    {
        $this->seedPartialRefundOrder667();

        $this->assertSame(100.0, PPCart_Order_Refunds::get_net_amount(667));
    }

    public function test_UT_092_partial_refund_is_detected_as_partial_refund_state(): void
    {
        $this->seedPartialRefundOrder667();

        $this->assertTrue(PPCart_Order_Refunds::is_partially_refunded(667));
    }

    public function test_UT_093_legacy_indexed_refund_logs_are_summed(): void
    {
        $this->seedLegacyIndexedRefundOrder668();

        $this->assertSame(75.0, PPCart_Order_Refunds::get_refund_total(668));
    }

    public function test_UT_094_legacy_indexed_refund_logs_reduce_the_net_amount(): void
    {
        $this->seedLegacyIndexedRefundOrder668();

        $this->assertSame(125.0, PPCart_Order_Refunds::get_net_amount(668));
    }

    public function test_UT_095_over_refunds_are_clamped_to_zero_net_amount(): void
    {
        $this->seedOverRefundedOrder669();

        $this->assertSame(0.0, PPCart_Order_Refunds::get_net_amount(669));
    }

    public function test_UT_096_full_or_over_refunds_are_not_detected_as_partial_refunds(): void
    {
        $this->seedOverRefundedOrder669();

        $this->assertFalse(PPCart_Order_Refunds::is_partially_refunded(669));
    }

    public function test_UT_097_formatted_amount_strings_parse_as_decimals(): void
    {
        $this->assertSame(1234.56, PPCart_Order_Refunds::parse_amount('$1,234.56'));
    }

    private function seedPartialRefundOrder667(): void
    {
        WordPressStubContext::setState(
            'post_meta',
            array(
                667 => array(
                    '_ppcart_amount' => '200.00',
                    '_ppcart_refund_amount' => '100.00',
                    '_ppcart_refund_log' => array(
                        're_partial' => array(
                            'refundID' => 're_partial',
                            'amount' => '100.00',
                        ),
                    ),
                ),
            )
        );
    }

    private function seedLegacyIndexedRefundOrder668(): void
    {
        WordPressStubContext::setState(
            'post_meta',
            array(
                668 => array(
                    '_ppcart_amount' => '200.00',
                    '_ppcart_refund_log' => array(
                        array(
                            'refundID' => 're_one',
                            'amount' => '25.00',
                        ),
                        array(
                            'refundID' => 're_two',
                            'amount' => '50.00',
                        ),
                    ),
                ),
            )
        );
    }

    private function seedOverRefundedOrder669(): void
    {
        WordPressStubContext::setState(
            'post_meta',
            array(
                669 => array(
                    '_ppcart_amount' => '200.00',
                    '_ppcart_refund_amount' => '250.00',
                ),
            )
        );
    }
}
