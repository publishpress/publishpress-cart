<?php

namespace unit\Embed;

use Codeception\Test\Unit;
use UnitTester;

class EmbedFrameHeadersTest extends Unit
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
     * @test-id UT-373
     */
    public function test_UT_373_frame_header_lines_allow_every_origin_by_default(): void
    {
        $this->assertSame(
            [ 'Content-Security-Policy: frame-ancestors *' ],
            ppcart_embed_frame_header_lines([ '*' ])
        );
    }

    /**
     * @test-id UT-373
     */
    public function test_UT_373_frame_header_lines_join_the_filtered_sources(): void
    {
        $this->assertSame(
            [ "Content-Security-Policy: frame-ancestors 'self' https://merchant.example https://*.shop.example:8443" ],
            ppcart_embed_frame_header_lines([ "'self'", ' https://merchant.example ', 'https://*.shop.example:8443', 'https://merchant.example' ])
        );
    }

    /**
     * @test-id UT-373
     */
    public function test_UT_373_frame_header_lines_block_framing_when_no_source_is_valid(): void
    {
        $this->assertSame(
            [ "Content-Security-Policy: frame-ancestors 'none'" ],
            ppcart_embed_frame_header_lines([ 'javascript:alert(1)', 'bad value;', '', 42, null ])
        );
        $this->assertSame(
            [ "Content-Security-Policy: frame-ancestors 'none'" ],
            ppcart_embed_frame_header_lines([])
        );
    }

    /**
     * @test-id UT-374
     */
    public function test_UT_374_frame_header_lines_keep_other_policies_without_their_frame_ancestors(): void
    {
        $this->assertSame(
            [
                "Content-Security-Policy: script-src 'self'; object-src 'none'",
                'Content-Security-Policy: upgrade-insecure-requests',
                'Content-Security-Policy: frame-ancestors *',
            ],
            ppcart_embed_frame_header_lines(
                [ '*' ],
                [ "script-src 'self'; frame-ancestors 'self'; object-src 'none'", "FRAME-ANCESTORS 'none'", 'upgrade-insecure-requests;' ]
            )
        );
    }
}
