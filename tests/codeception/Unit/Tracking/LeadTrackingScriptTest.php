<?php

namespace unit\Tracking {

use Codeception\Test\Unit;
use Tests\Support\WordPressStubContext;
use UnitTester;

class LeadTrackingScriptTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var array<string, mixed>
     */
    private $options = [];

    /**
     * @var string
     */
    private $inlineScript = '';

    protected function _before(): void
    {
        WordPressStubContext::clear();
        $this->options = [];
        $this->inlineScript = '';
        $_GET = [];
        $_POST = [];
        $GLOBALS['ppcart_product'] = null;

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

        if (! function_exists('ppcart_filter_input')) {
            require_once PPCART_PLUGIN_ROOT . 'includes/functions.php';
        }
        WordPressStubContext::set(
            'do_action',
            static function () {
                return null;
            }
        );
        WordPressStubContext::set(
            'get_option',
            function ($name, $default = false) {
                return array_key_exists((string) $name, $this->options)
                    ? $this->options[ (string) $name ]
                    : $default;
            }
        );
        WordPressStubContext::set(
            'wp_add_inline_script',
            function ($handle, $data) {
                if ('ppcart' === $handle) {
                    $this->inlineScript = (string) $data;
                }
            }
        );
        WordPressStubContext::set(
            'esc_js',
            static function ($text) {
                return addcslashes((string) $text, "\n\r\\\"'");
            }
        );
        WordPressStubContext::set(
            'get_the_title',
            static function ($post = 0) {
                return 'Tracked Product';
            }
        );
    }

    protected function _after(): void
    {
        $GLOBALS['ppcart_product'] = null;
        $_GET = [];
        $_POST = [];
        WordPressStubContext::clear();
        parent::_after();
    }

    /**
     * @test-id UT-326
     */
    public function test_UT_326_legacy_snippet_is_not_emitted(): void
    {
        $marker = 'PPCART_LEGACY_TRACKING_SNIPPET_MARKER';
        $GLOBALS['ppcart_product'] = (object) [
            'ID' => 41,
            'currency' => 'USD',
            'price' => '19.00',
            'tracking_main' => $marker,
            'tracking_lead' => $marker,
        ];
        $this->options['_ppcart_fb_lead'] = 1;
        $this->options['_ppcart_ga_lead'] = 1;

        $script = $this->renderTrackingScript();

        $this->assertNotSame('', $script);
        $this->assertStringNotContainsString($marker, $script);
    }

    /**
     * @test-id UT-326
     */
    public function test_UT_326_lead_events_emit_only_when_enabled(): void
    {
        $GLOBALS['ppcart_product'] = (object) [
            'ID' => 41,
            'currency' => 'USD',
            'price' => '19.00',
        ];

        $this->options['_ppcart_fb_lead'] = 0;
        $this->options['_ppcart_ga_lead'] = 0;
        $disabled = $this->renderTrackingScript();
        $this->assertStringNotContainsString("fbq('track', 'Lead'", $disabled);
        $this->assertStringNotContainsString('generate_lead', $disabled);

        $this->options['_ppcart_fb_lead'] = 1;
        $this->options['_ppcart_ga_lead'] = 0;
        $fbOnly = $this->renderTrackingScript();
        $this->assertStringContainsString("fbq('track', 'Lead'", $fbOnly);
        $this->assertStringNotContainsString('generate_lead', $fbOnly);

        $this->options['_ppcart_fb_lead'] = 0;
        $this->options['_ppcart_ga_lead'] = 1;
        $gaOnly = $this->renderTrackingScript();
        $this->assertStringNotContainsString("fbq('track', 'Lead'", $gaOnly);
        $this->assertStringContainsString('generate_lead', $gaOnly);

        $this->options['_ppcart_fb_lead'] = 1;
        $this->options['_ppcart_ga_lead'] = 1;
        $enabled = $this->renderTrackingScript();
        $this->assertStringContainsString("fbq('track', 'Lead'", $enabled);
        $this->assertStringContainsString('generate_lead', $enabled);
        $this->assertStringContainsString('USD', $enabled);
    }

    /**
     * @test-id UT-326
     */
    public function test_UT_326_missing_product_does_not_emit_lead_events(): void
    {
        $GLOBALS['ppcart_product'] = null;
        $this->options['_ppcart_fb_lead'] = 1;
        $this->options['_ppcart_ga_lead'] = 1;

        $script = $this->renderTrackingScript();

        $this->assertNotSame('', $script);
        $this->assertStringNotContainsString("fbq('track', 'Lead'", $script);
        $this->assertStringNotContainsString('generate_lead', $script);
    }

    /**
     * @return string
     */
    private function renderTrackingScript(): string
    {
        $this->inlineScript = '';
        include PPCART_PLUGIN_ROOT . 'public/controllers/templates/order-tracking-script.php';

        return $this->inlineScript;
    }
}

}

namespace {

    use Tests\Support\WordPressStubContext;

    if (! function_exists('wp_add_inline_script')) {
        function wp_add_inline_script($handle, $data)
        {
            return WordPressStubContext::invoke('wp_add_inline_script', func_get_args());
        }
    }

    if (! function_exists('esc_js')) {
        function esc_js($text)
        {
            return WordPressStubContext::invoke('esc_js', func_get_args());
        }
    }

    if (! function_exists('get_the_title')) {
        function get_the_title($post = 0)
        {
            return WordPressStubContext::invoke('get_the_title', func_get_args());
        }
    }
}
