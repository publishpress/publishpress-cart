<?php

declare(strict_types=1);

namespace Tests\Integration\CheckoutBlock;

use Tests\Support\Integration\CheckoutBlockTestCase;

class AccountBlocksTest extends CheckoutBlockTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    public function test_IT_069_account_navigation_editor_lists_downloads_tab_hidden_on_frontend(): void
    {
        if (! class_exists('PPCart_Gutenberg_Bootstrap') || ! function_exists('ppcart_account_tabs')) {
            $this->markTestSkipped('Account navigation editor option regression check requires Gutenberg bootstrap.');
        }

        $adminId = wp_insert_user(
            array(
                'user_login' => 'ppcart_nav_admin_' . wp_generate_uuid4(),
                'user_pass'  => wp_generate_password(),
                'user_email' => 'ppcart-nav-admin-' . wp_generate_uuid4() . '@example.invalid',
                'role'       => 'administrator',
            )
        );

        if (is_wp_error($adminId)) {
            $this->markTestSkipped('Account navigation editor option regression check requires creating a temporary admin user.');
        }

        $this->temporaryNavigationAdminId = (int) $adminId;
        wp_set_current_user($this->temporaryNavigationAdminId);

        $accountNavigationOptions = \PPCart_Gutenberg_Bootstrap::get_instance()
            ->get_account_renderer()
            ->get_account_navigation_options();
        $editorTabValues   = wp_list_pluck($accountNavigationOptions, 'value');
        $frontendTabValues = wp_list_pluck(ppcart_account_tabs(), 'id');

        $this->assertContains('tab-files', $editorTabValues);
        $this->assertNotContains('tab-files', $frontendTabValues);
    }

    public function test_IT_070_account_page_builder_shows_login_and_hides_navigation_when_logged_out(): void
    {
        $output = $this->renderBlocks(
            '<!-- wp:publishpress-cart/account-page-builder {"anchor":"account-page-builder-anchor"} -->'
            . '<!-- wp:publishpress-cart/account-login /-->'
            . '<!-- wp:publishpress-cart/account-navigation /-->'
            . '<!-- /wp:publishpress-cart/account-page-builder -->'
        );

        $this->assertStringContainsString('id="account-page-builder-anchor"', $output);
        $this->assertStringContainsString('publishpress-cart-account-page-builder', $output);
        $this->assertStringContainsString('id="ppcart-login"', $output);
        $this->assertStringNotContainsString('ppcart-nav-tabs', $output);
    }

    public function test_IT_071_account_page_builder_enqueues_frontend_detail_view_script(): void
    {
        $this->renderBlocks(
            '<!-- wp:publishpress-cart/account-page-builder -->'
            . '<!-- wp:publishpress-cart/account-login /-->'
            . '<!-- /wp:publishpress-cart/account-page-builder -->'
        );

        $this->assertTrue(wp_script_is('ppcart-account-page-view', 'enqueued'));
        $this->assertStringContainsString(
            '/includes/integrations/gutenberg/build/account-page-view.js',
            $this->getRegisteredScriptSrc('ppcart-account-page-view')
        );
    }

    public function test_IT_072_account_page_builder_adds_account_page_body_class(): void
    {
        $this->temporaryAccountPageId = wp_insert_post(
            array(
                'post_type'    => 'page',
                'post_status'  => 'publish',
                'post_title'   => 'PublishPress Cart Account Block Test ' . wp_generate_uuid4(),
                'post_content' => '<!-- wp:publishpress-cart/account-page-builder -->'
                    . '<!-- wp:publishpress-cart/account-login /-->'
                    . '<!-- /wp:publishpress-cart/account-page-builder -->',
            )
        );

        if (! $this->temporaryAccountPageId || is_wp_error($this->temporaryAccountPageId)) {
            $this->markTestSkipped('Account page body class check requires a temporary page.');
        }

        if (! class_exists(\PPCart_Public_Account_Controller::class)) {
            $this->markTestSkipped('Account page body class check requires the account controller.');
        }

        $account_controller = new \PPCart_Public_Account_Controller();

        $bodyClasses = $this->withGlobalPost(
            (int) $this->temporaryAccountPageId,
            function () use ($account_controller) {
                return $account_controller->add_shortcode_specific_body_class(array());
            }
        );

        $this->assertContains('account-page', $bodyClasses);
    }

    public function test_IT_073_account_component_blocks_add_account_page_body_class(): void
    {
        $this->temporaryAccountComponentPageId = wp_insert_post(
            array(
                'post_type'    => 'page',
                'post_status'  => 'publish',
                'post_title'   => 'PublishPress Cart Account Component Block Test ' . wp_generate_uuid4(),
                'post_content' => '<!-- wp:publishpress-cart/account-page-builder -->'
                    . '<!-- wp:publishpress-cart/account-orders /-->'
                    . '<!-- /wp:publishpress-cart/account-page-builder -->',
            )
        );

        if (! $this->temporaryAccountComponentPageId || is_wp_error($this->temporaryAccountComponentPageId)) {
            $this->markTestSkipped('Account component body class check requires a temporary page.');
        }

        if (! class_exists(\PPCart_Public_Account_Controller::class)) {
            $this->markTestSkipped('Account component body class check requires the account controller.');
        }

        $account_controller = new \PPCart_Public_Account_Controller();

        $bodyClasses = $this->withGlobalPost(
            (int) $this->temporaryAccountComponentPageId,
            function () use ($account_controller) {
                return $account_controller->add_shortcode_specific_body_class(array());
            }
        );

        $this->assertContains('account-page', $bodyClasses);
    }

    public function test_IT_074_account_navigation_block_renders_only_selected_tabs_with_active_state(): void
    {
        $this->ensureAccountFixtures();

        if (! $this->temporaryUserId) {
            $this->markTestSkipped('Account component rendering checks require creating a temporary account user.');
        }

        wp_set_current_user($this->temporaryUserId);

        $output = $this->renderBlocks(
            '<!-- wp:publishpress-cart/account-navigation {"activeTab":"tab-profile","includedTabs":["tab-orders","tab-profile"],"anchor":"account-nav-anchor"} /-->'
        );

        $this->assertStringContainsString('id="account-nav-anchor"', $output);
        $this->assertStringContainsString('publishpress-cart-account-navigation', $output);
        $this->assertStringContainsString('href="#tab-orders"', $output);
        $this->assertStringContainsString('href="#tab-profile"', $output);
        $this->assertStringNotContainsString('href="#tab-subscriptions"', $output);
        $this->assertStringContainsString('tablinks active', $output);
    }

    public function test_IT_075_account_page_builder_preserves_saved_child_block_order(): void
    {
        $this->ensureAccountFixtures();

        if (! $this->temporaryUserId) {
            $this->markTestSkipped('Account component rendering checks require creating a temporary account user.');
        }

        wp_set_current_user($this->temporaryUserId);

        $output = $this->renderBlocks(
            '<!-- wp:publishpress-cart/account-page-builder {"anchor":"account-page-builder-sorted"} -->'
            . '<!-- wp:publishpress-cart/account-profile /-->'
            . '<!-- wp:publishpress-cart/account-navigation /-->'
            . '<!-- wp:publishpress-cart/account-orders /-->'
            . '<!-- /wp:publishpress-cart/account-page-builder -->'
        );

        $profilePosition    = strpos($output, 'profile-wrapper');
        $navigationPosition = strpos($output, 'ppcart-nav-tabs');
        $ordersPosition     = strpos($output, 'order-history-tab');

        $this->assertStringContainsString('id="account-page-builder-sorted"', $output);
        $this->assertStringContainsString('publishpress-cart-account-page-builder', $output);
        $this->assertNotFalse($profilePosition);
        $this->assertNotFalse($navigationPosition);
        $this->assertNotFalse($ordersPosition);
        $this->assertLessThan($navigationPosition, $profilePosition);
        $this->assertLessThan($ordersPosition, $navigationPosition);
    }

    public function test_IT_076_account_page_builder_renders_a_single_shared_account_shell(): void
    {
        $this->ensureAccountFixtures();

        if (! $this->temporaryUserId) {
            $this->markTestSkipped('Account component rendering checks require creating a temporary account user.');
        }

        wp_set_current_user($this->temporaryUserId);

        $output = $this->renderBlocks(
            '<!-- wp:publishpress-cart/account-page-builder {"anchor":"account-page-builder-sorted"} -->'
            . '<!-- wp:publishpress-cart/account-profile /-->'
            . '<!-- wp:publishpress-cart/account-navigation /-->'
            . '<!-- wp:publishpress-cart/account-orders /-->'
            . '<!-- /wp:publishpress-cart/account-page-builder -->'
        );

        $this->assertSame(1, substr_count($output, 'class="ppcart-my-account ppcart-account-list"'));
        $this->assertSame(1, substr_count($output, 'class="tabcontent active"'));
        $this->assertStringContainsString('id="tab-orders" class="tabcontent active"', $output);
        $this->assertStringContainsString('id="tab-profile" class="tabcontent"', $output);
    }

    public function test_IT_077_account_navigation_block_hosts_sortable_tab_blocks(): void
    {
        $this->ensureAccountFixtures();

        if (! $this->temporaryUserId) {
            $this->markTestSkipped('Account component rendering checks require creating a temporary account user.');
        }

        wp_set_current_user($this->temporaryUserId);

        $output = $this->renderBlocks(
            '<!-- wp:publishpress-cart/account-page-builder -->'
            . '<!-- wp:publishpress-cart/account-navigation -->'
            . '<!-- wp:publishpress-cart/account-tab {"tabId":"tab-orders","label":"Orders"} -->'
            . '<!-- wp:publishpress-cart/account-orders /-->'
            . '<!-- /wp:publishpress-cart/account-tab -->'
            . '<!-- wp:publishpress-cart/account-tab {"tabId":"tab-subscriptions","label":"Subscriptions"} -->'
            . '<!-- wp:publishpress-cart/account-subscriptions /-->'
            . '<!-- /wp:publishpress-cart/account-tab -->'
            . '<!-- wp:publishpress-cart/account-tab {"tabId":"tab-profile","label":"My Profile"} -->'
            . '<!-- wp:publishpress-cart/account-profile /-->'
            . '<!-- /wp:publishpress-cart/account-tab -->'
            . '<!-- /wp:publishpress-cart/account-navigation -->'
            . '<!-- /wp:publishpress-cart/account-page-builder -->'
        );

        $this->assertSame(1, substr_count($output, 'class="ppcart-my-account ppcart-account-list"'));
        $this->assertStringContainsString('ppcart-nav-tabs', $output);
        $this->assertStringContainsString('publishpress-cart-account-tab', $output);
        $this->assertStringContainsString('order-history-tab', $output);
        $this->assertStringContainsString('Active Subscriptions', $output);
        $this->assertStringContainsString('profile-wrapper', $output);
        $this->assertSame(1, substr_count($output, 'class="tabcontent active"'));
        $this->assertStringContainsString('id="tab-orders" class="tabcontent active"', $output);
    }

    public function test_IT_078_account_page_builder_honors_navigation_included_tabs_filter(): void
    {
        $this->ensureAccountFixtures();

        if (! $this->temporaryUserId) {
            $this->markTestSkipped('Account component rendering checks require creating a temporary account user.');
        }

        wp_set_current_user($this->temporaryUserId);

        $output = $this->renderBlocks(
            '<!-- wp:publishpress-cart/account-page-builder -->'
            . '<!-- wp:publishpress-cart/account-navigation {"includedTabs":["tab-orders"],"includedTabsConfigured":true} /-->'
            . '<!-- wp:publishpress-cart/account-orders /-->'
            . '<!-- wp:publishpress-cart/account-subscriptions /-->'
            . '<!-- /wp:publishpress-cart/account-page-builder -->'
        );

        $this->assertStringContainsString('order-history-tab', $output);
        $this->assertStringNotContainsString('subscriptions-tab', $output);
        $this->assertStringNotContainsString('id="tab-subscriptions"', $output);
    }

    public function test_IT_079_account_navigation_and_tab_blocks_emit_style_classes_and_css_variables(): void
    {
        $this->ensureAccountFixtures();

        if (! $this->temporaryUserId) {
            $this->markTestSkipped('Account component rendering checks require creating a temporary account user.');
        }

        wp_set_current_user($this->temporaryUserId);

        $output = $this->renderBlocks(
            '<!-- wp:publishpress-cart/account-page-builder -->'
            . '<!-- wp:publishpress-cart/account-navigation {"tabStyle":"pills","tabAlignment":"center","tabGap":24,"accentColor":"#1e73be","tabTextColor":"#334155","activeTabTextColor":"#ffffff","tabBackgroundColor":"#f8fafc","activeTabBackgroundColor":"#1e73be"} -->'
            . '<!-- wp:publishpress-cart/account-tab {"tabId":"tab-orders","label":"Orders","panelStyle":"card","panelPadding":32,"panelRadius":12,"panelBackgroundColor":"#ffffff","panelTextColor":"#111827","panelBorderColor":"#dbe3ef"} -->'
            . '<!-- wp:publishpress-cart/account-orders /-->'
            . '<!-- /wp:publishpress-cart/account-tab -->'
            . '<!-- /wp:publishpress-cart/account-navigation -->'
            . '<!-- /wp:publishpress-cart/account-page-builder -->'
        );

        $this->assertStringContainsString('ppcart-account-nav-style-pills', $output);
        $this->assertStringContainsString('ppcart-account-nav-align-center', $output);
        $this->assertStringContainsString('--ppcart-account-nav-gap:24px', $output);
        $this->assertStringContainsString('--ppcart-account-nav-accent:#1e73be', $output);
        $this->assertStringContainsString('--ppcart-account-nav-active-background:#1e73be', $output);
        $this->assertStringContainsString('ppcart-account-tab-panel-style-card', $output);
        $this->assertStringContainsString('--ppcart-account-panel-padding:32px', $output);
        $this->assertStringContainsString('--ppcart-account-panel-radius:12px', $output);
        $this->assertStringContainsString('--ppcart-account-panel-border-color:#dbe3ef', $output);
    }

    public function test_IT_080_account_page_builder_supports_percentage_content_max_width(): void
    {
        $this->ensureAccountFixtures();

        if (! $this->temporaryUserId) {
            $this->markTestSkipped('Account component rendering checks require creating a temporary account user.');
        }

        wp_set_current_user($this->temporaryUserId);

        $output = $this->renderBlocks(
            '<!-- wp:publishpress-cart/account-page-builder {"contentWidth":100,"contentWidthUnit":"%"} -->'
            . '<!-- wp:publishpress-cart/account-navigation /-->'
            . '<!-- /wp:publishpress-cart/account-page-builder -->'
        );

        $this->assertStringContainsString('ppcart-account-page-builder-has-container', $output);
        $this->assertStringContainsString('--ppcart-account-page-builder-max-width:100%', $output);
        $this->assertStringNotContainsString('--ppcart-account-page-builder-max-width:100px', $output);
    }

    public function test_IT_081_account_page_builder_supports_relative_content_max_width_units(): void
    {
        $this->ensureAccountFixtures();

        if (! $this->temporaryUserId) {
            $this->markTestSkipped('Account component rendering checks require creating a temporary account user.');
        }

        wp_set_current_user($this->temporaryUserId);

        $output = $this->renderBlocks(
            '<!-- wp:publishpress-cart/account-page-builder {"contentWidth":42.5,"contentWidthUnit":"rem"} -->'
            . '<!-- wp:publishpress-cart/account-navigation /-->'
            . '<!-- /wp:publishpress-cart/account-page-builder -->'
        );

        $this->assertStringContainsString('--ppcart-account-page-builder-max-width:42.5rem', $output);
    }

    public function test_IT_082_account_page_builder_supports_native_padding_and_margin_styles(): void
    {
        $this->ensureAccountFixtures();

        if (! $this->temporaryUserId) {
            $this->markTestSkipped('Account component rendering checks require creating a temporary account user.');
        }

        wp_set_current_user($this->temporaryUserId);

        $output = $this->renderBlocks(
            '<!-- wp:publishpress-cart/account-page-builder {"style":{"spacing":{"padding":{"top":"12px","right":"14px","bottom":"16px","left":"18px"},"margin":{"top":"20px","bottom":"24px"}}}} -->'
            . '<!-- wp:publishpress-cart/account-navigation /-->'
            . '<!-- /wp:publishpress-cart/account-page-builder -->'
        );

        $this->assertStringContainsString('padding-top:12px', $output);
        $this->assertStringContainsString('padding-right:14px', $output);
        $this->assertStringContainsString('padding-bottom:16px', $output);
        $this->assertStringContainsString('padding-left:18px', $output);
        $this->assertStringContainsString('margin-top:20px', $output);
        $this->assertStringContainsString('margin-bottom:24px', $output);
    }

    public function test_IT_083_account_page_builder_keeps_default_inner_layout_backward_compatible(): void
    {
        $this->ensureAccountFixtures();

        if (! $this->temporaryUserId) {
            $this->markTestSkipped('Account component rendering checks require creating a temporary account user.');
        }

        wp_set_current_user($this->temporaryUserId);

        $output = $this->renderBlocks(
            '<!-- wp:publishpress-cart/account-page-builder -->'
            . '<!-- wp:publishpress-cart/account-navigation /-->'
            . '<!-- /wp:publishpress-cart/account-page-builder -->'
        );

        $this->assertStringNotContainsString('ppcart-account-page-builder-inner-', $output);
    }

    public function test_IT_084_account_page_builder_renders_stretch_inner_layout_class(): void
    {
        $this->ensureAccountFixtures();

        if (! $this->temporaryUserId) {
            $this->markTestSkipped('Account component rendering checks require creating a temporary account user.');
        }

        wp_set_current_user($this->temporaryUserId);

        $output = $this->renderBlocks(
            '<!-- wp:publishpress-cart/account-page-builder {"innerLayout":"stretch"} -->'
            . '<!-- wp:publishpress-cart/account-navigation /-->'
            . '<!-- /wp:publishpress-cart/account-page-builder -->'
        );

        $this->assertStringContainsString('ppcart-account-page-builder-inner-stretch', $output);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public function innerLayoutProvider(): array
    {
        return array(
            'left' => array('left'),
            'center' => array('center'),
            'right' => array('right'),
        );
    }

    /**
     * @dataProvider innerLayoutProvider
     */
    public function test_IT_085_account_page_builder_renders_left_center_and_right_inner_layout_classes(string $innerLayout): void
    {
        $this->ensureAccountFixtures();

        if (! $this->temporaryUserId) {
            $this->markTestSkipped('Account component rendering checks require creating a temporary account user.');
        }

        wp_set_current_user($this->temporaryUserId);

        $output = $this->renderBlocks(
            '<!-- wp:publishpress-cart/account-page-builder {"innerLayout":"' . esc_attr($innerLayout) . '"} -->'
            . '<!-- wp:publishpress-cart/account-navigation /-->'
            . '<!-- /wp:publishpress-cart/account-page-builder -->'
        );

        $this->assertStringContainsString(
            'ppcart-account-page-builder-inner-' . $innerLayout,
            $output
        );
    }

    public function test_IT_086_account_tab_script_normalizes_hash_tab_links(): void
    {
        $pluginRoot = defined('PPCART_PLUGIN_ROOT')
            ? PPCART_PLUGIN_ROOT
            : dirname(__DIR__, 4) . '/';
        $accountTabScript = file_get_contents($pluginRoot . 'public/js/ppcart-public.js');

        $this->assertStringContainsString('tab_id.substring(1)', $accountTabScript);
        $this->assertStringContainsString('account.find("#" + tab_id).addClass', $accountTabScript);
        $this->assertStringNotContainsString("var tab_id = jQuery(this).attr('href');", $accountTabScript);
    }

    public function test_IT_087_account_orders_block_renders_order_history_and_fires_extension_hook(): void
    {
        $this->ensureAccountFixtures();

        if (! $this->temporaryUserId) {
            $this->markTestSkipped('Account component rendering checks require creating a temporary account user.');
        }

        wp_set_current_user($this->temporaryUserId);

        $extensionCallback = function () {
            echo '<div class="ppcart-account-orders-extension-test">Orders extension fixture</div>';
        };
        add_action('ppcart_tab_content_tab-orders', $extensionCallback);

        $output = $this->renderBlocks(
            '<!-- wp:publishpress-cart/account-orders {"anchor":"account-orders-anchor"} /-->'
        );

        remove_action('ppcart_tab_content_tab-orders', $extensionCallback);

        $this->assertStringContainsString('id="account-orders-anchor"', $output);
        $this->assertStringContainsString('publishpress-cart-account-orders', $output);
        $this->assertStringContainsString('Account Block Order', $output);
        $this->assertStringContainsString('order-history-tab', $output);
        $this->assertStringContainsString('ppcart-account-orders-extension-test', $output);
    }

    public function test_IT_088_account_subscriptions_block_lists_current_customer_subscriptions(): void
    {
        $this->ensureAccountFixtures();

        if (! $this->temporaryUserId) {
            $this->markTestSkipped('Account component rendering checks require creating a temporary account user.');
        }

        wp_set_current_user($this->temporaryUserId);

        $output = $this->renderBlocks('<!-- wp:publishpress-cart/account-subscriptions /-->');

        $this->assertStringContainsString('publishpress-cart-account-subscriptions', $output);
        $this->assertStringContainsString('Account Block Subscription', $output);
        $this->assertStringContainsString('Active Subscriptions', $output);
    }

    public function test_IT_089_account_payment_plans_block_lists_installment_subscriptions(): void
    {
        $this->ensureAccountFixtures();

        if (! $this->temporaryUserId) {
            $this->markTestSkipped('Account component rendering checks require creating a temporary account user.');
        }

        wp_set_current_user($this->temporaryUserId);

        $output = $this->renderBlocks('<!-- wp:publishpress-cart/account-payment-plans /-->');

        $this->assertStringContainsString('publishpress-cart-account-payment-plans', $output);
        $this->assertStringContainsString('Account Block Payment Plan', $output);
        $this->assertStringContainsString('Active Plans', $output);
    }

    public function test_IT_090_account_profile_block_renders_current_customer_profile_form(): void
    {
        $this->ensureAccountFixtures();

        if (! $this->temporaryUserId) {
            $this->markTestSkipped('Account component rendering checks require creating a temporary account user.');
        }

        wp_set_current_user($this->temporaryUserId);

        $output = $this->renderBlocks('<!-- wp:publishpress-cart/account-profile /-->');
        $user   = get_userdata($this->temporaryUserId);

        $this->assertStringContainsString('publishpress-cart-account-profile', $output);
        $this->assertStringContainsString('id="ppcart-update-profile-form"', $output);
        $this->assertNotFalse($user);
        $this->assertStringContainsString(esc_attr($user->user_email), $output);
    }

    public function test_IT_091_account_downloads_block_renders_downloads_tab_hook_output(): void
    {
        $this->ensureAccountFixtures();

        if (! $this->temporaryUserId) {
            $this->markTestSkipped('Account component rendering checks require creating a temporary account user.');
        }

        wp_set_current_user($this->temporaryUserId);

        $downloadsCallback = function () {
            echo '<div class="ppcart-account-downloads-test">Download fixture</div>';
        };
        add_action('ppcart_tab_content_tab-files', $downloadsCallback);

        $output = $this->renderBlocks('<!-- wp:publishpress-cart/account-downloads /-->');

        remove_action('ppcart_tab_content_tab-files', $downloadsCallback);

        $this->assertStringContainsString('publishpress-cart-account-downloads', $output);
        $this->assertStringContainsString('ppcart-account-downloads-test', $output);
    }

    /**
     * @test-id IT-366
     */
    public function test_IT_366_account_downloads_block_strips_script_from_tab_hook(): void
    {
        $this->ensureAccountFixtures();

        if (! $this->temporaryUserId) {
            $this->markTestSkipped('Account downloads kses check requires creating a temporary account user.');
        }

        wp_set_current_user($this->temporaryUserId);

        $downloadsCallback = function () {
            echo '<script>alert(1)</script><p class="ppcart-kses-ok">ok</p>';
        };
        add_action('ppcart_tab_content_tab-files', $downloadsCallback);

        $output = $this->renderBlocks('<!-- wp:publishpress-cart/account-downloads /-->');

        remove_action('ppcart_tab_content_tab-files', $downloadsCallback);

        $this->assertStringContainsString('publishpress-cart-account-downloads', $output);
        $this->assertStringContainsString('ppcart-kses-ok', $output);
        $this->assertStringNotContainsString('<script', $output);
    }

    public function test_IT_092_account_login_block_is_hidden_for_logged_in_customers(): void
    {
        $this->ensureAccountFixtures();

        if (! $this->temporaryUserId) {
            $this->markTestSkipped('Account component rendering checks require creating a temporary account user.');
        }

        wp_set_current_user($this->temporaryUserId);

        $output = $this->renderBlocks('<!-- wp:publishpress-cart/account-login /-->');

        $this->assertSame('', trim($output));
    }

    public function test_IT_093_account_login_block_renders_when_hide_when_logged_in_is_disabled(): void
    {
        $this->ensureAccountFixtures();

        if (! $this->temporaryUserId) {
            $this->markTestSkipped('Account component rendering checks require creating a temporary account user.');
        }

        wp_set_current_user($this->temporaryUserId);

        $output = $this->renderBlocks(
            '<!-- wp:publishpress-cart/account-login {"hideWhenLoggedIn":false} /-->'
        );

        $this->assertStringContainsString('publishpress-cart-account-login', $output);
        $this->assertStringContainsString('id="ppcart-login"', $output);
    }

    public function test_IT_094_account_login_block_renders_minimal_style_preset_class(): void
    {
        $this->ensureAccountFixtures();

        if (! $this->temporaryUserId) {
            $this->markTestSkipped('Account component rendering checks require creating a temporary account user.');
        }

        wp_set_current_user($this->temporaryUserId);

        $output = $this->renderBlocks(
            '<!-- wp:publishpress-cart/account-login {"hideWhenLoggedIn":false,"blockStyle":"minimal"} /-->'
        );

        $this->assertStringContainsString('ppcart-account-login-style-minimal', $output);
        $this->assertStringContainsString('id="ppcart-login"', $output);
    }

    public function test_IT_095_account_orders_block_presents_owned_order_detail_in_page(): void
    {
        $this->ensureAccountFixtures();

        if (! $this->temporaryUserId || ! $this->temporaryAccountOrderId) {
            $this->markTestSkipped('Account orders detail presentation requires creating a temporary order.');
        }

        wp_set_current_user($this->temporaryUserId);

        $blockContent = '<!-- wp:publishpress-cart/account-page-builder -->'
            . '<!-- wp:publishpress-cart/account-navigation -->'
            . '<!-- wp:publishpress-cart/account-tab {"tabId":"tab-orders","label":"Orders"} -->'
            . '<!-- wp:publishpress-cart/account-orders {"detailPresentation":"slide-right"} /-->'
            . '<!-- /wp:publishpress-cart/account-tab -->'
            . '<!-- wp:publishpress-cart/account-tab {"tabId":"tab-subscriptions","label":"Subscriptions"} -->'
            . '<!-- wp:publishpress-cart/account-subscriptions /-->'
            . '<!-- /wp:publishpress-cart/account-tab -->'
            . '<!-- /wp:publishpress-cart/account-navigation -->'
            . '<!-- /wp:publishpress-cart/account-page-builder -->';

        $output = $this->withRequestContext(
            array('ppcart-order' => (string) $this->temporaryAccountOrderId),
            '/?page_id=236&ppcart-order=' . rawurlencode((string) $this->temporaryAccountOrderId),
            function () use ($blockContent) {
                return $this->renderBlocks($blockContent);
            }
        );

        $this->assertStringContainsString('ppcart-nav-tabs', $output);
        $this->assertStringContainsString('order-history-tab', $output);
        $this->assertStringContainsString('id="tab-orders" class="tabcontent active"', $output);
        $this->assertStringContainsString('ppcart-account-detail-presenter--slide-right', $output);
        $this->assertStringContainsString('publishpress-cart-account-order-detail', $output);
        $this->assertStringContainsString('Account Block Order', $output);
        $this->assertStringContainsString('id="ppcart-order-details"', $output);
        $this->assertStringContainsString('Order Details', $output);
        $this->assertStringContainsString('ppcart-subscription-table', $output);
        $this->assertStringContainsString('Invoice', $output);
        $this->assertStringNotContainsString('Purchase receipt', $output);
        $this->assertStringNotContainsString('ppcart-order-detail__hero', $output);
        $this->assertStringContainsString('href="/?page_id=236"', $output);
    }

    public function test_IT_096_account_detail_rest_route_returns_owned_order_detail_html(): void
    {
        $this->ensureRestApiInit();
        $this->ensureAccountFixtures();

        if (! $this->temporaryUserId || ! $this->temporaryAccountOrderId) {
            $this->markTestSkipped('Account orders detail presentation requires creating a temporary order.');
        }

        wp_set_current_user($this->temporaryUserId);

        $returnUrl = home_url(
            '/?page_id=236&ppcart-order=' . rawurlencode((string) $this->temporaryAccountOrderId)
        );
        $request = new \WP_REST_Request('GET', '/publishpress-cart/v1/account-block/detail');
        $request->set_param('type', 'order');
        $request->set_param('id', $this->temporaryAccountOrderId);
        $request->set_param('presentation', 'slide-down');
        $request->set_param('returnUrl', $returnUrl);

        $response = rest_do_request($request);
        $data     = $response->get_data();
        $html     = is_array($data) && isset($data['html']) ? $data['html'] : '';

        $this->assertSame(200, $response->get_status());
        $this->assertStringContainsString('ppcart-account-detail-presenter--slide-down', $html);
        $this->assertStringContainsString('publishpress-cart-account-order-detail', $html);
        $this->assertStringContainsString('Account Block Order', $html);
        $this->assertStringContainsString('Order Details', $html);
        $this->assertStringContainsString('ppcart-subscription-table', $html);
        $this->assertStringNotContainsString('Purchase receipt', $html);
        $this->assertStringNotContainsString('ppcart-order-detail__hero', $html);
        $this->assertStringContainsString(
            'href="' . esc_url(
                remove_query_arg(
                    array('ppcart-order', 'ppcart-plan', 'ppcart-manage', 'action'),
                    $returnUrl
                )
            ) . '"',
            $html
        );
        $this->assertStringNotContainsString('ppcart-order=', $html);
    }

    public function test_IT_097_account_orders_block_denies_another_customer_s_order_detail(): void
    {
        $this->ensureAccountFixtures();

        if (! $this->temporaryUserId || ! $this->temporaryOtherOrderId) {
            $this->markTestSkipped('Account orders detail ownership check requires creating a second temporary order.');
        }

        wp_set_current_user($this->temporaryUserId);

        $blockContent = '<!-- wp:publishpress-cart/account-page-builder -->'
            . '<!-- wp:publishpress-cart/account-navigation -->'
            . '<!-- wp:publishpress-cart/account-tab {"tabId":"tab-orders","label":"Orders"} -->'
            . '<!-- wp:publishpress-cart/account-orders {"detailPresentation":"slide-right"} /-->'
            . '<!-- /wp:publishpress-cart/account-tab -->'
            . '<!-- /wp:publishpress-cart/account-navigation -->'
            . '<!-- /wp:publishpress-cart/account-page-builder -->';

        $output = $this->withRequestContext(
            array('ppcart-order' => (string) $this->temporaryOtherOrderId),
            '/?page_id=236&ppcart-order=' . rawurlencode((string) $this->temporaryOtherOrderId),
            function () use ($blockContent) {
                return $this->renderBlocks($blockContent);
            }
        );

        $this->assertStringContainsString('You do not have permission to access this account content.', $output);
        $this->assertStringNotContainsString('Other Account Block Order', $output);
    }

    public function test_IT_098_account_detail_rest_route_denies_another_customer_s_order(): void
    {
        $this->ensureRestApiInit();
        $this->ensureAccountFixtures();

        if (! $this->temporaryUserId || ! $this->temporaryOtherOrderId) {
            $this->markTestSkipped('Account orders detail ownership check requires creating a second temporary order.');
        }

        wp_set_current_user($this->temporaryUserId);

        $request = new \WP_REST_Request('GET', '/publishpress-cart/v1/account-block/detail');
        $request->set_param('type', 'order');
        $request->set_param('id', $this->temporaryOtherOrderId);
        $response = rest_do_request($request);
        $data     = $response->get_data();

        $this->assertSame(403, $response->get_status());
        $this->assertIsArray($data);
        $this->assertArrayHasKey('code', $data);
        $this->assertSame('publishpress_cart_account_detail_forbidden', $data['code']);
        $this->assertStringNotContainsString('Other Account Block Order', wp_json_encode($data));
    }

    public function test_IT_099_account_subscriptions_block_presents_owned_subscription_detail_in_page(): void
    {
        $this->ensureAccountFixtures();

        if (! $this->temporaryUserId || ! $this->temporaryAccountSubscriptionId) {
            $this->markTestSkipped('Account subscriptions detail presentation requires creating a temporary subscription.');
        }

        wp_set_current_user($this->temporaryUserId);

        $blockContent = '<!-- wp:publishpress-cart/account-page-builder -->'
            . '<!-- wp:publishpress-cart/account-navigation -->'
            . '<!-- wp:publishpress-cart/account-tab {"tabId":"tab-orders","label":"Orders"} -->'
            . '<!-- wp:publishpress-cart/account-orders /-->'
            . '<!-- /wp:publishpress-cart/account-tab -->'
            . '<!-- wp:publishpress-cart/account-tab {"tabId":"tab-subscriptions","label":"Subscriptions"} -->'
            . '<!-- wp:publishpress-cart/account-subscriptions {"detailPresentation":"slide-left"} /-->'
            . '<!-- /wp:publishpress-cart/account-tab -->'
            . '<!-- /wp:publishpress-cart/account-navigation -->'
            . '<!-- /wp:publishpress-cart/account-page-builder -->';

        $output = $this->withRequestContext(
            array('ppcart-plan' => (string) $this->temporaryAccountSubscriptionId),
            '/?page_id=236&ppcart-plan=' . rawurlencode((string) $this->temporaryAccountSubscriptionId),
            function () use ($blockContent) {
                return $this->renderBlocks($blockContent);
            }
        );

        $this->assertStringContainsString('ppcart-nav-tabs', $output);
        $this->assertStringContainsString('subscriptions-tab', $output);
        $this->assertStringContainsString('id="tab-subscriptions" class="tabcontent active"', $output);
        $this->assertStringContainsString('ppcart-account-detail-presenter--slide-left', $output);
        $this->assertStringContainsString('publishpress-cart-account-subscription-detail', $output);
        $this->assertStringContainsString('Account Block Subscription', $output);
        $this->assertStringContainsString('Details', $output);
        $this->assertStringContainsString('href="/?page_id=236"', $output);
        $this->assertStringContainsString('order-history-tab', $output);
    }

    public function test_IT_100_account_detail_rest_route_returns_owned_subscription_detail_html(): void
    {
        $this->ensureRestApiInit();
        $this->ensureAccountFixtures();

        if (! $this->temporaryUserId || ! $this->temporaryAccountSubscriptionId) {
            $this->markTestSkipped('Account subscriptions detail presentation requires creating a temporary subscription.');
        }

        wp_set_current_user($this->temporaryUserId);

        $returnUrl = home_url(
            '/?page_id=236&ppcart-plan=' . rawurlencode((string) $this->temporaryAccountSubscriptionId)
        );
        $request = new \WP_REST_Request('GET', '/publishpress-cart/v1/account-block/detail');
        $request->set_param('type', 'subscription');
        $request->set_param('id', $this->temporaryAccountSubscriptionId);
        $request->set_param('presentation', 'slide-left');
        $request->set_param('returnUrl', $returnUrl);

        $response = rest_do_request($request);
        $data     = $response->get_data();
        $html     = is_array($data) && isset($data['html']) ? $data['html'] : '';

        $this->assertSame(200, $response->get_status());
        $this->assertStringContainsString('ppcart-account-detail-presenter--slide-left', $html);
        $this->assertStringContainsString('publishpress-cart-account-subscription-detail', $html);
        $this->assertStringContainsString('Account Block Subscription', $html);
        $this->assertStringContainsString(
            'href="' . esc_url(
                remove_query_arg(
                    array('ppcart-order', 'ppcart-plan', 'ppcart-manage', 'action'),
                    $returnUrl
                )
            ) . '"',
            $html
        );
        $this->assertStringNotContainsString('ppcart-plan=', $html);
    }

    public function test_IT_101_account_subscriptions_block_denies_another_customer_s_subscription_detail(): void
    {
        $this->ensureAccountFixtures();

        if (! $this->temporaryUserId || ! $this->temporaryOtherSubscriptionId) {
            $this->markTestSkipped('Account subscriptions detail ownership check requires creating a second temporary subscription.');
        }

        wp_set_current_user($this->temporaryUserId);

        $blockContent = '<!-- wp:publishpress-cart/account-page-builder -->'
            . '<!-- wp:publishpress-cart/account-navigation -->'
            . '<!-- wp:publishpress-cart/account-tab {"tabId":"tab-subscriptions","label":"Subscriptions"} -->'
            . '<!-- wp:publishpress-cart/account-subscriptions {"detailPresentation":"slide-left"} /-->'
            . '<!-- /wp:publishpress-cart/account-tab -->'
            . '<!-- /wp:publishpress-cart/account-navigation -->'
            . '<!-- /wp:publishpress-cart/account-page-builder -->';

        $output = $this->withRequestContext(
            array('ppcart-plan' => (string) $this->temporaryOtherSubscriptionId),
            '/?page_id=236&ppcart-plan=' . rawurlencode((string) $this->temporaryOtherSubscriptionId),
            function () use ($blockContent) {
                return $this->renderBlocks($blockContent);
            }
        );

        $this->assertStringContainsString('You do not have permission to access this account content.', $output);
        $this->assertStringNotContainsString('Other Account Block Subscription', $output);
    }

    public function test_IT_102_account_detail_rest_route_denies_another_customer_s_subscription(): void
    {
        $this->ensureRestApiInit();
        $this->ensureAccountFixtures();

        if (! $this->temporaryUserId || ! $this->temporaryOtherSubscriptionId) {
            $this->markTestSkipped('Account subscriptions detail ownership check requires creating a second temporary subscription.');
        }

        wp_set_current_user($this->temporaryUserId);

        $request = new \WP_REST_Request('GET', '/publishpress-cart/v1/account-block/detail');
        $request->set_param('type', 'subscription');
        $request->set_param('id', $this->temporaryOtherSubscriptionId);
        $response = rest_do_request($request);
        $data     = $response->get_data();

        $this->assertSame(403, $response->get_status());
        $this->assertIsArray($data);
        $this->assertArrayHasKey('code', $data);
        $this->assertSame('publishpress_cart_account_detail_forbidden', $data['code']);
        $this->assertStringNotContainsString('Other Account Block Subscription', wp_json_encode($data));
    }
}
