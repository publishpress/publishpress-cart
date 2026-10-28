<?php

namespace unit\Embed;

use Codeception\Test\Unit;
use PPCart_Embed_Appearance;
use UnitTester;

class EmbedAppearanceTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    protected function _before(): void
    {
        require_once PPCART_PLUGIN_ROOT . 'includes/embed/class-ppcart-embed-appearance.php';
    }

    /**
     * @test-id UT-370
     */
    public function test_UT_370_sanitize_keeps_valid_values_and_drops_unknown_keys(): void
    {
        $clean = PPCart_Embed_Appearance::sanitize([
            'layout'      => '2-step',
            'accent'      => ' #FF5A1F ',
            'text'        => '#222222',
            'background'  => '',
            'font_family' => "'Inter', sans-serif",
            'font_url'    => 'https://fonts.googleapis.com/css2?family=Inter&display=swap',
            'radius'      => '12',
            'button_text' => 'Buy <b>now</b>',
            'unknown'     => 'dropped',
        ]);

        $this->assertSame([
            'layout'      => '2-step',
            'accent'      => '#ff5a1f',
            'text'        => '#222222',
            'background'  => '',
            'font_family' => "'Inter', sans-serif",
            'font_url'    => 'https://fonts.googleapis.com/css2?family=Inter&display=swap',
            'radius'      => 12,
            'button_text' => 'Buy now',
        ], $clean);
    }

    /**
     * @test-id UT-370
     */
    public function test_UT_370_sanitize_turns_each_invalid_value_into_an_empty_string(): void
    {
        $clean = PPCart_Embed_Appearance::sanitize([
            'layout'      => 'three-step',
            'accent'      => 'red',
            'text'        => '#abc',
            'background'  => '#12345g',
            'font_family' => 'Inter<script>',
            'font_url'    => 'http://fonts.example/inter.css',
            'radius'      => '25',
            'button_text' => str_repeat('x', 61),
        ]);

        $this->assertSame([
            'layout'      => '',
            'accent'      => '',
            'text'        => '',
            'background'  => '',
            'font_family' => '',
            'font_url'    => '',
            'radius'      => '',
            'button_text' => '',
        ], $clean);
    }

    /**
     * @test-id UT-370
     */
    public function test_UT_370_sanitize_rejects_font_urls_with_quotes_or_whitespace_and_negative_radius(): void
    {
        $clean = PPCart_Embed_Appearance::sanitize([
            'font_url' => 'https://fonts.example/a.css" onload="x',
            'radius'   => '-1',
        ]);

        $this->assertSame('', $clean['font_url']);
        $this->assertSame('', $clean['radius']);

        $clean = PPCart_Embed_Appearance::sanitize([ 'font_url' => 'https://fonts.example/a b.css', 'radius' => 0 ]);

        $this->assertSame('', $clean['font_url']);
        $this->assertSame(0, $clean['radius']);
    }

    /**
     * @test-id UT-370
     */
    public function test_UT_370_sanitize_returns_all_keys_empty_for_non_array_input(): void
    {
        $expected = [
            'layout'      => '',
            'accent'      => '',
            'text'        => '',
            'background'  => '',
            'font_family' => '',
            'font_url'    => '',
            'radius'      => '',
            'button_text' => '',
        ];

        $this->assertSame($expected, PPCart_Embed_Appearance::sanitize('not an array'));
        $this->assertSame($expected, PPCart_Embed_Appearance::sanitize([ 'accent' => [ '#ff5a1f' ] ]));
    }

    /**
     * @test-id UT-371
     */
    public function test_UT_371_body_classes_lists_one_class_per_set_value_and_the_2_step_layout(): void
    {
        $classes = PPCart_Embed_Appearance::body_classes(PPCart_Embed_Appearance::sanitize([
            'layout'      => '2-step',
            'accent'      => '#ff5a1f',
            'text'        => '#222222',
            'background'  => '#ffffff',
            'font_family' => 'Inter, sans-serif',
            'font_url'    => 'https://fonts.example/inter.css',
            'radius'      => 0,
            'button_text' => 'Buy now',
        ]));

        $this->assertSame([
            'ppcart-embed--accent',
            'ppcart-embed--text',
            'ppcart-embed--bg',
            'ppcart-embed--font',
            'ppcart-embed--radius',
            'ppcart-embed--layout-2-step',
        ], $classes);
    }

    /**
     * @test-id UT-371
     */
    public function test_UT_371_body_classes_is_empty_for_an_unset_appearance_and_skips_the_standard_layout(): void
    {
        $this->assertSame([], PPCart_Embed_Appearance::body_classes(PPCart_Embed_Appearance::sanitize([])));
        $this->assertSame(
            [ 'ppcart-embed--accent' ],
            PPCart_Embed_Appearance::body_classes(PPCart_Embed_Appearance::sanitize([ 'layout' => 'standard', 'accent' => '#ff5a1f' ]))
        );
    }

    /**
     * @test-id UT-372
     */
    public function test_UT_372_css_variables_declares_only_the_set_values(): void
    {
        $css = PPCart_Embed_Appearance::css_variables(PPCart_Embed_Appearance::sanitize([
            'accent'      => '#ff5a1f',
            'text'        => '#222222',
            'background'  => '#ffffff',
            'font_family' => "'Inter', sans-serif",
            'radius'      => '12',
            'button_text' => 'Buy now',
        ]));

        $this->assertSame(
            "--ppcart-embed-accent:#ff5a1f;--ppcart-embed-text:#222222;--ppcart-embed-bg:#ffffff;--ppcart-embed-font:'Inter', sans-serif;--ppcart-embed-radius:12px;",
            $css
        );
    }

    /**
     * @test-id UT-372
     */
    public function test_UT_372_css_variables_is_empty_when_nothing_is_set_and_keeps_a_zero_radius(): void
    {
        $this->assertSame('', PPCart_Embed_Appearance::css_variables(PPCart_Embed_Appearance::sanitize([])));
        $this->assertSame(
            '--ppcart-embed-radius:0px;',
            PPCart_Embed_Appearance::css_variables(PPCart_Embed_Appearance::sanitize([ 'radius' => '0' ]))
        );
    }
}
