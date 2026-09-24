<?php

namespace unit\Checkout;

use Codeception\Test\Unit;
use Tests\Support\WordPressStubContext;
use UnitTester;

class PlanPriceTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    protected function _before(): void
    {
        WordPressStubContext::clear();
        WordPressStubContext::set(
            'add_filter',
            static function () {
                return true;
            }
        );
        WordPressStubContext::set(
            'apply_filters',
            static function ($hook, $value) {
                return $value;
            }
        );

        if (! function_exists('ppcart_plan')) {
            require_once PPCART_PLUGIN_ROOT . 'includes/functions/plans-and-subscriptions.php';
        }

        $GLOBALS['ppcart_product'] = (object) [
            'pay_options' => [
                [
                    'option_id'   => 'basic',
                    'option_name' => 'Basic',
                ],
            ],
        ];
    }

    protected function _after(): void
    {
        $GLOBALS['ppcart_product'] = null;
        WordPressStubContext::clear();
        parent::_after();
    }

    /**
     * @test-id UT-358
     */
    public function test_UT_358_absent_price_is_empty_and_initial_payment_is_zero(): void
    {
        $warnings = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$warnings): bool {
            $warnings[] = $errstr;

            return true;
        });

        try {
            $plan = ppcart_plan('basic', '', '', true);
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $warnings);
        $this->assertSame('', $plan['price']);
        $this->assertSame(0.0, $plan['initial_payment']);
    }

    /**
     * @test-id UT-358
     */
    public function test_UT_358_stored_price_is_unchanged(): void
    {
        $GLOBALS['ppcart_product']->pay_options[0]['price'] = '19.00';

        $plan = ppcart_plan('basic', '', '', true);

        $this->assertSame('19.00', $plan['price']);
        $this->assertSame(19.0, $plan['initial_payment']);
    }
}
