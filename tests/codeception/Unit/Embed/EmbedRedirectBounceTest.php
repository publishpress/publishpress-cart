<?php

namespace unit\Embed;

use Codeception\Test\Unit;
use UnitTester;

class EmbedRedirectBounceTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    protected function _before(): void
    {
        require_once PPCART_PLUGIN_ROOT . 'includes/embed/ppcart-embed-functions.php';
    }

    /**
     * @test-id UT-375
     */
    public function test_UT_375_only_a_redirect_to_another_origin_leaves_the_site(): void
    {
        $site = 'https://shop.example';

        $this->assertTrue(ppcart_embed_redirect_leaves_site('https://thanks.merchant.example/thank-you/', $site));
        $this->assertTrue(ppcart_embed_redirect_leaves_site('http://shop.example/thanks/', $site));
        $this->assertTrue(ppcart_embed_redirect_leaves_site('https://shop.example:8443/thanks/', $site));
        $this->assertFalse(ppcart_embed_redirect_leaves_site('https://SHOP.example/thanks/?ppcart-order=5', $site));
        $this->assertFalse(ppcart_embed_redirect_leaves_site('https://shop.example:443/thanks/', $site));
        $this->assertFalse(ppcart_embed_redirect_leaves_site('/thanks/', $site));
    }

    /**
     * @test-id UT-376
     */
    public function test_UT_376_the_bounce_page_hands_the_url_to_the_frame_bridge_escaped(): void
    {
        $html = ppcart_embed_bounce_page(
            'https://thanks.merchant.example/t?a=1&b="x"</script>',
            'https://shop.example/wp-content/plugins/publishpress-cart/public/js/ppcart-embed-frame.js?ver=1'
        );

        $this->assertSame(
            '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="robots" content="noindex"><title>Redirecting</title></head>'
            . '<body><p><a href="https://thanks.merchant.example/t?a=1&amp;b=&quot;x&quot;&lt;/script&gt;" target="_top">Continue</a></p>'
            . '<script src="https://shop.example/wp-content/plugins/publishpress-cart/public/js/ppcart-embed-frame.js?ver=1"></script>'
            . '<script>window.ppcartNavigate("https://thanks.merchant.example/t?a=1\u0026b=\u0022x\u0022\u003C/script\u003E");</script>'
            . '</body></html>',
            $html
        );
    }
}
