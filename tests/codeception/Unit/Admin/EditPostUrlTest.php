<?php

namespace unit\Admin;

use Codeception\Test\Unit;
use Tests\Support\WordPressStubContext;
use UnitTester;

class EditPostUrlTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    protected function _before(): void
    {
        WordPressStubContext::clear();

        if (! function_exists('ppcart_get_edit_post_url')) {
            require_once PPCART_PLUGIN_ROOT . 'includes/functions/orders-products-and-formatting.php';
        }
    }

    protected function _after(): void
    {
        WordPressStubContext::clear();
        parent::_after();
    }

    public function test_UT_202_edit_post_url_never_returns_null(): void
    {
        $calledWith = [];
        WordPressStubContext::set(
            'get_edit_post_link',
            static function ($post_id) use (&$calledWith) {
                $calledWith[] = $post_id;

                if (99 === (int) $post_id) {
                    return 'https://example.test/wp-admin/post.php?post=99&action=edit';
                }

                if (7 === (int) $post_id) {
                    return '';
                }

                return null;
            }
        );

        $this->assertSame('', ppcart_get_edit_post_url(null));
        $this->assertSame('', ppcart_get_edit_post_url(0));
        $this->assertSame([], $calledWith);

        $this->assertSame('', ppcart_get_edit_post_url(42));
        $this->assertSame('', ppcart_get_edit_post_url(7));
        $this->assertSame(
            'https://example.test/wp-admin/post.php?post=99&action=edit',
            ppcart_get_edit_post_url(99)
        );
        $this->assertSame([42, 7, 99], $calledWith);
    }
}
