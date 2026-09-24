<?php

namespace unit\Admin;

use Codeception\Test\Unit;
use Tests\Support\WordPressStubContext;
use UnitTester;

class ConditionalLogicScriptTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    protected function _before(): void
    {
        WordPressStubContext::clear();

        if (! function_exists('ppcart_admin_js_literal')) {
            require_once PPCART_PLUGIN_ROOT . 'includes/functions/admin-conditional-logic.php';
        }
    }

    protected function _after(): void
    {
        WordPressStubContext::clear();
        parent::_after();
    }

    /**
     * @test-id UT-349
     */
    public function test_UT_349_value_with_quotes_is_json_encoded(): void
    {
        $script = ppcart_admin_conditional_logic_js_combined(
            [
                [
                    'field' => 'custom_field',
                    'value' => '";</script><script>alert(1)//',
                    'compare' => '=',
                ],
            ],
            'rid_custom_field',
            'custom_field'
        );

        $this->assertStringContainsString(
            ppcart_admin_js_literal('";</script><script>alert(1)//'),
            $script
        );
        $this->assertStringNotContainsString("'\";</script>", $script);
    }

    /**
     * @test-id UT-349
     */
    public function test_UT_349_hostile_compare_is_not_used_as_operator(): void
    {
        $script = ppcart_admin_conditional_logic_js_combined(
            [
                [
                    'field' => 'status',
                    'value' => 'paid',
                    'compare' => ');alert(1);//',
                ],
            ],
            'rid_status',
            'status'
        );

        $this->assertStringContainsString('==', $script);
        $this->assertStringNotContainsString(');alert(1);//', $script);
    }

    /**
     * @test-id UT-349
     */
    public function test_UT_349_in_rule_uses_field_selector_and_encoded_array(): void
    {
        $values = ['mailchimp', 'activecampaign'];
        $script = ppcart_admin_conditional_logic_js_combined(
            [
                [
                    'field' => 'services',
                    'value' => $values,
                    'compare' => 'IN',
                ],
            ],
            'rid_service_action',
            'service_action'
        );

        $this->assertStringContainsString(ppcart_admin_js_literal('#services'), $script);
        $this->assertStringContainsString(ppcart_admin_js_literal($values), $script);
        $this->assertStringContainsString('.includes($("#services").val())', $script);
    }

    /**
     * @test-id UT-349
     */
    public function test_UT_349_checkbox_true_uses_checked_selector(): void
    {
        $script = ppcart_admin_conditional_logic_js_combined(
            [
                [
                    'field' => '_ppcart_on_sale',
                    'value' => true,
                    'compare' => '==',
                ],
            ],
            'rid_ppcart_show_full_price',
            'ppcart_show_full_price'
        );

        $this->assertStringContainsString(
            ppcart_admin_js_literal('#_ppcart_on_sale:checked'),
            $script
        );
    }
}
