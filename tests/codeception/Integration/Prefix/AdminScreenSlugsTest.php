<?php

declare(strict_types=1);

namespace Tests\Integration\Prefix;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Admin_Settings;
use PPCart_Admin_Screens;

class AdminScreenSlugsTest extends WPTestCase
{
    private const MENU_GLOBALS = [
        'menu',
        'submenu',
        'admin_page_hooks',
        '_registered_pages',
        '_parent_pages',
    ];

    /**
     * @var \IntegrationTester
     */
    protected $tester;

    /**
     * @var array<string, mixed>
     */
    private $originalGlobals = [];

    /**
     * @var array<string, bool>
     */
    private $existingGlobals = [];

    /**
     * @var int
     */
    private $originalUserId = 0;

    /**
     * @var int
     */
    private $temporaryAdminUserId = 0;

    /**
     * @var PPCart_Admin_Settings|null
     */
    private $adminSettings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalUserId = get_current_user_id();

        foreach (self::MENU_GLOBALS as $global_name) {
            $this->existingGlobals[ $global_name ] = array_key_exists($global_name, $GLOBALS);
            $this->originalGlobals[ $global_name ] = $GLOBALS[ $global_name ] ?? null;
            $GLOBALS[ $global_name ] = [];
        }

        $admin_user_id = wp_insert_user(
            [
                'user_login' => 'ppcart_admin_screen_' . wp_generate_uuid4(),
                'user_pass'  => wp_generate_password(),
                'user_email' => 'ppcart-admin-screen-' . wp_generate_uuid4() . '@example.invalid',
                'role'       => 'administrator',
            ]
        );

        if (is_wp_error($admin_user_id)) {
            $this->fail('IT-266 requires a temporary administrator: ' . $admin_user_id->get_error_message());
        }

        $this->temporaryAdminUserId = (int) $admin_user_id;
        wp_set_current_user($this->temporaryAdminUserId);
    }

    protected function tearDown(): void
    {
        if ($this->adminSettings instanceof PPCart_Admin_Settings) {
            remove_action(
                PPCart_Admin_Screens::HOOK_DASHBOARD,
                [ $this->adminSettings, 'render_settings_page_content' ]
            );
            remove_action(
                PPCart_Admin_Screens::HOOK_SETTINGS,
                [ $this->adminSettings, 'page_options' ]
            );
        }

        wp_set_current_user($this->originalUserId);

        if ($this->temporaryAdminUserId > 0) {
            if (! function_exists('wp_delete_user')) {
                require_once ABSPATH . 'wp-admin/includes/user.php';
            }

            wp_delete_user($this->temporaryAdminUserId);
        }

        foreach (self::MENU_GLOBALS as $global_name) {
            if ($this->existingGlobals[ $global_name ]) {
                $GLOBALS[ $global_name ] = $this->originalGlobals[ $global_name ];
                continue;
            }

            unset($GLOBALS[ $global_name ]);
        }

        parent::tearDown();
    }

    /**
     * @test-id IT-266
     */
    public function test_IT_266_actual_menu_registration_stabilizes_canonical_hook_suffixes(): void
    {
        global $admin_page_hooks;

        $reflection = new \ReflectionClass(PPCart_Admin_Settings::class);
        $this->adminSettings = $reflection->newInstanceWithoutConstructor();

        foreach (
            [
                'plugin_name'  => 'ppcart',
                'plugin_title' => 'PublishPress Cart',
                'version'      => 'test',
            ] as $property_name => $value
        ) {
            $property = $reflection->getProperty($property_name);
            $property->setAccessible(true);
            $property->setValue($this->adminSettings, $value);
        }

        $this->adminSettings->setup_plugin_options_menu();

        $dashboard_hook = get_plugin_page_hookname(PPCart_Admin_Screens::PAGE_DASHBOARD, '');
        $settings_hook  = get_plugin_page_hookname(
            PPCart_Admin_Screens::PAGE_SETTINGS,
            PPCart_Admin_Screens::PAGE_DASHBOARD
        );

        $this->assertSame(
            PPCart_Admin_Screens::PAGE_DASHBOARD,
            $admin_page_hooks[ PPCart_Admin_Screens::PAGE_DASHBOARD ]
        );
        $this->assertSame(PPCart_Admin_Screens::HOOK_DASHBOARD, $dashboard_hook);
        $this->assertSame(PPCart_Admin_Screens::HOOK_SETTINGS, $settings_hook);
        $this->assertArrayHasKey($dashboard_hook, $GLOBALS['_registered_pages']);
        $this->assertArrayHasKey($settings_hook, $GLOBALS['_registered_pages']);
    }
}
