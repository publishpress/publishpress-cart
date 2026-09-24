<?php

namespace unit\Checkout;

use Codeception\Test\Unit;
use Tests\Support\WordPressStubContext;
use UnitTester;

class FrontendAllowedHtmlTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    protected function _before(): void
    {
        WordPressStubContext::clear();
        WordPressStubContext::set(
            'add_action',
            static function () {
                return true;
            }
        );
        WordPressStubContext::set(
            'add_filter',
            static function () {
                return true;
            }
        );
        WordPressStubContext::set(
            'add_shortcode',
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
        WordPressStubContext::set(
            'wp_kses_allowed_html',
            static function () {
                return [
                    'div'   => ['class' => true],
                    'input' => ['type' => true, 'name' => true],
                ];
            }
        );

        if (! function_exists('ppcart_frontend_allowed_html')) {
            require_once PPCART_PLUGIN_ROOT . 'includes/functions.php';
        }
    }

    protected function _after(): void
    {
        WordPressStubContext::clear();
        parent::_after();
    }

    /**
     * @test-id UT-321
     */
    public function test_UT_321_frontend_kses_allowlist_keeps_plan_data_attributes(): void
    {
        $allowed = ppcart_frontend_allowed_html();

        $this->assertArrayHasKey('input', $allowed);

        foreach (
            [
                'data-price',
                'data-installments',
                'data-val',
                'data-ppcart-qty-price',
                'data-testid',
                'data-form-wrapper',
            ] as $attribute
        ) {
            $this->assertArrayHasKey($attribute, $allowed['input']);
            $this->assertTrue($allowed['input'][$attribute]);
        }
    }
}
