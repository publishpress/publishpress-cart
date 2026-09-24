<?php

namespace {
    if (! function_exists('add_query_arg')) {
        /**
         * @param array  $args Query args.
         * @param string $url  Base URL.
         * @return string
         */
        function add_query_arg($args, $url)
        {
            return \Tests\Support\WordPressStubContext::invoke('add_query_arg', func_get_args());
        }
    }
}

namespace unit\OrderAccess {

use Codeception\Test\Unit;
use PPCart_Order;
use Tests\Support\WordPressStubContext;
use UnitTester;

class OrderAccessTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var array<int, array<string, mixed>>
     */
    private $meta = [];

    /**
     * @var int
     */
    private $currentUserId = 0;

    /**
     * @var bool
     */
    private $isAdmin = false;

    protected function _before(): void
    {
        WordPressStubContext::clear();
        $this->meta          = [];
        $this->currentUserId = 0;
        $this->isAdmin       = false;
        $_GET                = [];
        $_POST               = [];

        WordPressStubContext::set(
            'get_current_user_id',
            function () {
                return $this->currentUserId;
            }
        );
        WordPressStubContext::set(
            'current_user_can',
            function ($capability) {
                return $this->isAdmin && 'manage_options' === $capability;
            }
        );
        WordPressStubContext::set(
            'get_post_meta',
            function ($post_id, $key = '', $single = false) {
                $value = $this->meta[ (int) $post_id ][ (string) $key ] ?? '';
                if (! $single) {
                    return '' === $value ? [] : [ $value ];
                }

                return $value;
            }
        );
        WordPressStubContext::set(
            'add_query_arg',
            function ($args, $url) {
                $separator = (false === strpos((string) $url, '?')) ? '?' : '&';

                return (string) $url . $separator . http_build_query($args);
            }
        );

        if (! class_exists(PPCart_Order::class, false)) {
            require_once PPCART_PLUGIN_ROOT . 'models/class-ppcart-order.php';
        }
    }

    protected function _after(): void
    {
        $_GET  = [];
        $_POST = [];
        WordPressStubContext::clear();
        parent::_after();
    }

    public function test_UT_233_stranger_with_only_order_id_cannot_view(): void
    {
        $this->meta[12]['_ppcart_invoice_token'] = 'secret-token';
        $this->meta[12]['_ppcart_user_account']  = 0;

        $this->assertFalse(PPCart_Order::visitor_can_view(12));
    }

    public function test_UT_233_guest_with_matching_token_can_view(): void
    {
        $this->meta[12]['_ppcart_invoice_token'] = 'secret-token';
        $this->meta[12]['_ppcart_user_account']  = 0;
        $_GET['token'] = 'secret-token';

        $this->assertTrue(PPCart_Order::visitor_can_view(12));
    }

    public function test_UT_233_owner_without_token_can_view(): void
    {
        $this->currentUserId = 7;
        $this->meta[12]['_ppcart_user_account']  = 7;
        $this->meta[12]['_ppcart_invoice_token'] = 'secret-token';

        $this->assertTrue(PPCart_Order::visitor_can_view(12));
    }

    public function test_UT_233_admin_bypasses_public_page_proof(): void
    {
        $this->isAdmin = true;
        $this->currentUserId = 1;
        $this->meta[12]['_ppcart_user_account']  = 9;
        $this->meta[12]['_ppcart_invoice_token'] = 'secret-token';

        $this->assertTrue(PPCart_Order::visitor_can_view(12));
    }

    public function test_UT_233_confirmation_url_appends_order_id_and_token(): void
    {
        $this->meta[12]['_ppcart_invoice_token'] = 'secret-token';

        $url = PPCart_Order::confirmation_url('https://example.test/thanks/', 12);

        $this->assertStringContainsString('ppcart-order=12', $url);
        $this->assertStringContainsString('token=secret-token', $url);
    }


    public function test_UT_324_token_matches_rejects_empty_or_mismatch(): void
    {
        $this->meta[12]['_ppcart_invoice_token'] = 'secret-token';

        $this->assertFalse(PPCart_Order::token_matches(12, ''));
        $this->assertFalse(PPCart_Order::token_matches(12, 'wrong'));
        $this->assertTrue(PPCart_Order::token_matches(12, 'secret-token'));
    }

    public function test_UT_324_access_arg_returns_ppcart_access_query_arg(): void
    {
        $this->meta[12]['_ppcart_invoice_token'] = 'secret-token';

        $args = PPCart_Order::access_arg(12);

        $this->assertSame('secret-token', $args['ppcart-access']);
    }

    public function test_UT_233_child_id_is_not_unlocked_by_parent_token(): void
    {
        $this->meta[10]['_ppcart_invoice_token'] = 'parent-token';
        $this->meta[11]['_ppcart_invoice_token'] = 'child-token';
        $_GET['token'] = 'parent-token';

        $this->assertTrue(PPCart_Order::visitor_can_view(10));
        $this->assertFalse(PPCart_Order::visitor_can_view(11));
    }
}

}
