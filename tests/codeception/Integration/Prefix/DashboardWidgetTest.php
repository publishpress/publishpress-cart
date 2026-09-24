<?php

declare(strict_types=1);

namespace Tests\Integration\Prefix;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Dashboard_Widget;
use ReflectionClass;

class DashboardWidgetTest extends WPTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    /**
     * @var mixed
     */
    private $previousMetaBoxes;

    /**
     * @var bool
     */
    private $hadMetaBoxes = false;

    /**
     * @var \WP_Screen|null
     */
    private $previousScreen;

    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(\WP_Screen::class, false)) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
        }

        if (! function_exists('set_current_screen')) {
            require_once ABSPATH . 'wp-admin/includes/screen.php';
        }

        if (! function_exists('wp_add_dashboard_widget')) {
            require_once ABSPATH . 'wp-admin/includes/template.php';
            require_once ABSPATH . 'wp-admin/includes/dashboard.php';
        }

        $this->previousScreen = function_exists('get_current_screen') ? get_current_screen() : null;
        set_current_screen('dashboard');

        $this->hadMetaBoxes = array_key_exists('wp_meta_boxes', $GLOBALS);
        $this->previousMetaBoxes = $GLOBALS['wp_meta_boxes'] ?? null;
        $GLOBALS['wp_meta_boxes'] = [];
    }

    protected function tearDown(): void
    {
        if ($this->hadMetaBoxes) {
            $GLOBALS['wp_meta_boxes'] = $this->previousMetaBoxes;
        } else {
            unset($GLOBALS['wp_meta_boxes']);
        }

        if ($this->previousScreen instanceof \WP_Screen) {
            set_current_screen($this->previousScreen);
        } else {
            unset($GLOBALS['current_screen']);
        }

        parent::tearDown();
    }

    /**
     * @test-id IT-315
     */
    public function test_IT_315_dashboard_registers_canonical_widget_id_without_leftover(): void
    {
        $widget = (new ReflectionClass(PPCart_Dashboard_Widget::class))->newInstanceWithoutConstructor();
        $widget->register();

        $core = $GLOBALS['wp_meta_boxes']['dashboard']['normal']['core'] ?? [];

        $this->assertArrayHasKey('ppcart_dashboard_widget', $core);
        $this->assertArrayNotHasKey('studiocart_dashboard_widget', $core);
        $this->assertSame(
            [ $widget, 'render' ],
            $core['ppcart_dashboard_widget']['callback']
        );
    }
}
