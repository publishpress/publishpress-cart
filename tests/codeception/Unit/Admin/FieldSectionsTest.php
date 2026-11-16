<?php

namespace unit\Admin;

use Codeception\Test\Unit;
use Tests\Support\WordPressStubContext;
use UnitTester;

class FieldSectionsTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    protected function _before(): void
    {
        WordPressStubContext::clear();

        if (! function_exists('ppcart_admin_group_fields_by_section')) {
            require_once PPCART_PLUGIN_ROOT . 'includes/functions/admin-field-sections.php';
        }
    }

    protected function _after(): void
    {
        WordPressStubContext::clear();
        parent::_after();
    }

    /**
     * @test-id UT-370
     */
    public function test_UT_370_listed_fields_follow_the_section_list_order(): void
    {
        $grouped = ppcart_admin_group_fields_by_section(
            [ 'price', 'product_type', 'frequency' ],
            [
                'main'    => [ 'fields' => [ 'product_type', 'price' ] ],
                'billing' => [ 'fields' => [ 'frequency' ] ],
            ]
        );

        $this->assertSame([ 'main' => [ 1, 0 ], 'billing' => [ 2 ] ], $grouped);
    }

    /**
     * @test-id UT-370
     */
    public function test_UT_370_unlisted_field_stays_after_the_field_before_it(): void
    {
        $grouped = ppcart_admin_group_fields_by_section(
            [ 'installments', 'sign_up_fee', 'option_name' ],
            [
                'main'    => [ 'fields' => [ 'option_name' ] ],
                'billing' => [ 'fields' => [ 'installments' ] ],
            ]
        );

        $this->assertSame([ 'main' => [ 2 ], 'billing' => [ 0, 1 ] ], $grouped);
    }

    /**
     * @test-id UT-370
     */
    public function test_UT_370_unlisted_field_after_a_collapsed_section_goes_to_the_first_open_section(): void
    {
        $grouped = ppcart_admin_group_fields_by_section(
            [ 'price', 'is_hidden', 'trial_days' ],
            [
                'main'     => [ 'fields' => [ 'price' ] ],
                'advanced' => [ 'type' => 'details', 'fields' => [ 'is_hidden' ] ],
            ]
        );

        $this->assertSame([ 'main' => [ 0, 2 ], 'advanced' => [ 1 ] ], $grouped);
    }

    /**
     * @test-id UT-370
     */
    public function test_UT_370_keeps_field_keys_and_leaves_out_empty_sections(): void
    {
        $grouped = ppcart_admin_group_fields_by_section(
            [ 'On Sale?' => '_ppcart_on_sale', 'heading' => '' ],
            [
                'sale'  => [ 'fields' => [ '_ppcart_on_sale' ] ],
                'plans' => [ 'fields' => [ '_ppcart_pay_options' ] ],
            ]
        );

        $this->assertSame([ 'sale' => [ 'On Sale?', 'heading' ] ], $grouped);
        $this->assertSame([], ppcart_admin_group_fields_by_section([ 'a' ], []));
    }
}
