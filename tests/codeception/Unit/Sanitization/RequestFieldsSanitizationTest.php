<?php

namespace unit\Sanitization;

use Codeception\Test\Unit;
use Tests\Support\WordPressStubContext;
use UnitTester;

/**
 * Request payloads are built from a declared schema rather than blanket array
 * processing: only expected keys survive, and each value is sanitized by its type.
 */
class RequestFieldsSanitizationTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    protected function _before(): void
    {
        // Pass-through filters unless a test registers its own handler.
        WordPressStubContext::set(
            'apply_filters',
            static function ($hook_name, $value = null) {
                return $value;
            }
        );
    }

    protected function _after(): void
    {
        WordPressStubContext::clear();
        parent::_after();
    }

    public function test_UT_330_undeclared_request_keys_are_dropped(): void
    {
        $clean = ppcart_sanitize_request_fields(
            [
                'ppcart_product_id' => '42',
                'evil'              => '<script>alert(1)</script>',
                'another_stray'     => 'value',
            ],
            [ 'ppcart_product_id' => 'int' ],
            'test'
        );

        $this->assertSame([ 'ppcart_product_id' => 42 ], $clean);
        $this->assertArrayNotHasKey('evil', $clean);
        $this->assertArrayNotHasKey('another_stray', $clean);
    }

    public function test_UT_331_each_field_is_sanitized_by_its_declared_type(): void
    {
        $clean = ppcart_sanitize_request_fields(
            [
                'order_id' => '  7  ',
                'action'   => 'Ppcart Do Thing!',
                'email'    => ' buyer@example.test ',
                'note'     => '  hello  ',
            ],
            [
                'order_id' => 'int',
                'action'   => 'key',
                'email'    => 'email',
                'note'     => 'text',
            ],
            'test'
        );

        $this->assertSame(7, $clean['order_id']);
        $this->assertSame('ppcartdothing', $clean['action']);
        $this->assertSame('buyer@example.test', $clean['email']);
        $this->assertSame('hello', $clean['note']);
    }

    public function test_UT_332_url_fields_keep_their_query_separators(): void
    {
        $clean = ppcart_sanitize_request_fields(
            [ 'formAction' => 'https://example.test/thanks/?ppcart-order=12&step=2' ],
            [ 'formAction' => 'url_raw' ],
            'test'
        );

        // esc_url() would entity-encode & here, breaking the redirect the browser follows.
        $this->assertSame('https://example.test/thanks/?ppcart-order=12&step=2', $clean['formAction']);
    }

    public function test_UT_333_array_submitted_for_a_scalar_field_is_dropped_not_cast(): void
    {
        // absint(['x']) is 1, so a hostile `order_id[]=` would otherwise become order 1.
        $clean = ppcart_sanitize_request_fields(
            [ 'order_id' => [ 'x' ], 'note' => [ 'y' ] ],
            [ 'order_id' => 'int', 'note' => 'text' ],
            'test'
        );

        $this->assertArrayNotHasKey('order_id', $clean);
        $this->assertArrayNotHasKey('note', $clean);
    }

    public function test_UT_334_absent_declared_keys_are_not_invented(): void
    {
        $clean = ppcart_sanitize_request_fields(
            [ 'order_id' => '3' ],
            [ 'order_id' => 'int', 'note' => 'text' ],
            'test'
        );

        $this->assertSame([ 'order_id' => 3 ], $clean);
        $this->assertArrayNotHasKey('note', $clean);
    }

    public function test_UT_335_extensions_can_declare_extra_fields_through_the_filter(): void
    {
        WordPressStubContext::set(
            'apply_filters',
            static function ($hook_name, $value = null, $context = null) {
                if ('ppcart_expected_request_fields' === $hook_name && 'checkout' === $context) {
                    $value['my_addon_field'] = 'text';
                }

                return $value;
            }
        );

        $clean = ppcart_sanitize_request_fields(
            [ 'my_addon_field' => ' declared ', 'undeclared_field' => 'x' ],
            [],
            'checkout'
        );

        $this->assertSame([ 'my_addon_field' => 'declared' ], $clean);
    }

    public function test_UT_336_group_keys_come_from_configuration_and_keep_their_case(): void
    {
        $clean = ppcart_sanitize_request_group(
            [ 'Shirt_Size' => ' Large ', 'Colors' => [ ' Red ', ' Blue ' ] ],
            'text',
            'text'
        );

        // Custom-field ids are matched case-sensitively against the product config.
        $this->assertSame('Large', $clean['Shirt_Size']);
        $this->assertSame([ 'Red', 'Blue' ], $clean['Colors']);
    }

    public function test_UT_337_pwyw_amounts_keep_their_merchant_defined_plan_ids(): void
    {
        // "Plan ID" is a free-text admin field, so option ids are arbitrary strings.
        $clean = ppcart_parse_pwyw_amounts([ 'pro-annual' => '25', '12' => '30' ], false);

        $this->assertSame('25', (string) $clean['pro-annual']);
        $this->assertSame('30', (string) $clean[12]);
    }

    public function test_UT_338_paypal_ipn_payload_is_narrowed_to_consumed_fields(): void
    {
        $clean = ppcart_parse_paypal_ipn_fields(
            [
                'txn_id'         => '8AB12345',
                'payment_status' => 'Completed',
                'payer_email'    => 'buyer@example.test',
                'mc_gross'       => '49.00',
                'injected_field' => 'should not survive',
            ]
        );

        $this->assertSame('8AB12345', $clean['txn_id']);
        $this->assertSame('Completed', $clean['payment_status']);
        $this->assertSame('buyer@example.test', $clean['payer_email']);
        $this->assertSame('49.00', $clean['mc_gross']);
        $this->assertArrayNotHasKey('injected_field', $clean);
    }

    public function test_UT_339_checkout_schema_includes_filtered_form_field_names(): void
    {
        WordPressStubContext::set(
            'apply_filters',
            static function ($hook_name, $value = null) {
                if ('ppcart_order_form_fields' === $hook_name) {
                    $value['nickname'] = [ 'name' => 'nickname', 'required' => false ];
                }

                return $value;
            }
        );

        $schema = ppcart_checkout_request_schema(null);

        // Names added by an extension's form-field filter are accepted without a
        // second declaration, because the schema reads the same filtered list.
        $this->assertArrayHasKey('nickname', $schema);
        $this->assertSame('email', $schema['email']);
        $this->assertSame('int', $schema['ppcart_product_id']);
        $this->assertSame([ 'group' => 'price', 'keys' => 'text' ], $schema['pwyw_amount']);
    }

    public function test_UT_340_admin_ajax_payload_carries_only_dispatcher_fields(): void
    {
        $clean = ppcart_parse_admin_ajax_request(
            [
                'ppcart_action'     => 'sync_subscription',
                'ppcart_ajax_nonce' => 'abc123',
                'subscription_id'   => '99',
            ],
            'sync_subscription'
        );

        $this->assertSame('sync_subscription', $clean['ppcart_action']);
        $this->assertSame('abc123', $clean['ppcart_ajax_nonce']);
        // A handler that needs this field declares it through the schema filter.
        $this->assertArrayNotHasKey('subscription_id', $clean);
    }
}
