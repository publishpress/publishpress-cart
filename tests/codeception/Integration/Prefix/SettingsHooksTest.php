<?php

declare(strict_types=1);

namespace Tests\Integration\Prefix;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Admin_Settings;

class SettingsHooksTest extends WPTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    /**
     * @var PPCart_Admin_Settings
     */
    private $settings;

    /**
     * @var mixed
     */
    private $previousSettingsSections;

    protected function setUp(): void
    {
        parent::setUp();

        if (! function_exists('add_settings_section')) {
            require_once ABSPATH . 'wp-admin/includes/template.php';
        }

        $this->previousSettingsSections = $GLOBALS['wp_settings_sections'] ?? null;
        $this->settings = new PPCart_Admin_Settings('publishpress-cart', 'PublishPress Cart', PPCART_VERSION);
    }

    protected function tearDown(): void
    {
        $GLOBALS['wp_settings_sections'] = $this->previousSettingsSections;
        unset($GLOBALS['ppcart_product']);

        parent::tearDown();
    }

    /**
     * @test-id IT-382
     */
    public function test_IT_382_invoice_option_list_filter_extends_fields(): void
    {
        add_filter('ppcart_invoice_option_list', static function ($fields) {
            $fields['it382-canonical'] = [ 'marker' => true ];
            return $fields;
        });

        $fields = $this->settings->get_invoice_fields();

        $this->assertArrayHasKey('it382-canonical', $fields);
    }

    /**
     * @test-id IT-382
     */
    public function test_IT_382_register_gateways_receives_settings_and_payment_page(): void
    {
        $received = null;

        add_action('ppcart_register_gateways', static function ($settings, $page) use (&$received) {
            $received = [ $settings, $page ];
        }, 10, 2);

        $this->settings->register_payment_gateway_tab_section();

        $this->assertSame([ $this->settings, 'publishpress-cart-payment' ], $received);
    }

    /**
     * @test-id IT-382
     */
    public function test_IT_382_integration_sections_action_does_not_fire_argless_register_sections(): void
    {
        $received = null;
        $argless_runs = did_action('ppcart_register_sections');

        add_action('ppcart_register_integration_sections', static function ($settings, $page) use (&$received) {
            $received = [ $settings, $page ];
        }, 10, 2);

        $this->settings->register_integration_tab_section();

        $this->assertSame([ $this->settings, 'publishpress-cart' ], $received);
        $this->assertSame($argless_runs, did_action('ppcart_register_sections'));
    }

    /**
     * @test-id IT-382
     */
    public function test_IT_382_custom_tab_sections_use_dynamic_filter(): void
    {
        add_filter('ppcart_it382_tab_section', static function ($sections) {
            $sections['it382-extra'] = 'Extra';
            return $sections;
        });

        $this->settings->register_tab_section([ 'it382' => 'IT 382' ]);

        $this->assertEqualsCanonicalizing(
            [
                'publishpress-cart-it382-setting',
                'publishpress-cart-it382-extra',
            ],
            array_keys($GLOBALS['wp_settings_sections']['publishpress-cart-it382'])
        );
    }

    /**
     * @test-id IT-382
     */
    public function test_IT_382_plan_data_filter_receives_built_plan_and_option(): void
    {
        $GLOBALS['ppcart_product'] = (object) [
            'pay_options' => [
                [
                    'option_id'      => 'it382',
                    'option_name'    => 'IT 382',
                    'product_type'   => '',
                    'price'          => '10',
                    'stripe_plan_id' => '',
                ],
            ],
        ];

        add_filter('ppcart_plan_data', static function ($plan, $option) {
            $plan['canonical_option'] = $option['option_id'];
            return $plan;
        }, 10, 2);

        $plan = ppcart_plan('it382', '', '', true);

        $this->assertSame('it382', $plan['canonical_option']);
        $this->assertSame('one-time', $plan['type']);
    }
}
