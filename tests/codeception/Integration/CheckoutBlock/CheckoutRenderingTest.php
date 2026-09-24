<?php

declare(strict_types=1);

namespace Tests\Integration\CheckoutBlock;

use Tests\Support\Integration\CheckoutBlockTestCase;
use Tests\Support\Integration\PPCartBlockTestConfirmationPublic;

class CheckoutRenderingTest extends CheckoutBlockTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createStandardProduct();
        $this->createRecurringProduct();
        $this->createFilteredProduct();
    }

    public function test_IT_113_fse_product_template_registers_carrying_the_checkout_form_block(): void
    {
        if (! class_exists('PPCart_Product_Template') || ! function_exists('register_block_template')) {
            $this->markTestSkipped('FSE template registration requires WordPress 6.7 register_block_template().');
        }

        if (! \PPCart_Product_Template::is_supported_block_theme()) {
            $this->markTestSkipped('FSE template registration requires a supported block theme.');
        }

        $productTemplate = new \PPCart_Product_Template();
        $productTemplate->register();
        $registeredTemplate = \WP_Block_Templates_Registry::get_instance()->get_registered(
            \PPCart_Product_Template::get_registered_template_name()
        );

        $this->assertInstanceOf(\WP_Block_Template::class, $registeredTemplate);
        $this->assertStringContainsString('wp:publishpress-cart/checkout-form', $registeredTemplate->content);
    }

    public function test_IT_114_fse_product_template_is_scoped_to_the_sc_product_post_type(): void
    {
        if (! class_exists('PPCart_Product_Template') || ! function_exists('register_block_template')) {
            $this->markTestSkipped('FSE template registration requires WordPress 6.7 register_block_template().');
        }

        if (! \PPCart_Product_Template::is_supported_block_theme()) {
            $this->markTestSkipped('FSE template registration requires a supported block theme.');
        }

        $productTemplate = new \PPCart_Product_Template();
        $productTemplate->register();
        $liveProductType = function_exists('ppcart_live_post_type')
            ? ppcart_live_post_type('product')
            : 'ppcart_product';
        $registeredTemplate = \WP_Block_Templates_Registry::get_instance()->get_registered(
            \PPCart_Product_Template::get_registered_template_name($liveProductType)
        );

        $this->assertInstanceOf(\WP_Block_Template::class, $registeredTemplate);
        $this->assertContains($liveProductType, (array) $registeredTemplate->post_types);
    }

    public function test_IT_115_fse_product_template_helper_honors_filtered_product_post_types(): void
    {
        if (! class_exists('PPCart_Product_Template')) {
            $this->markTestSkipped('FSE template registration requires PPCart_Product_Template.');
        }

        add_filter('ppcart_product_post_type', 'ppcart_integration_include_filtered_product_type');

        $this->assertContains(
            'ppcart_filter_prod',
            \PPCart_Product_Template::get_product_post_types()
        );

        remove_filter('ppcart_product_post_type', 'ppcart_integration_include_filtered_product_type');
    }

    public function test_IT_116_fse_product_template_cover_falls_back_to_the_bundled_image_without_a_featured_image(): void
    {
        if (! class_exists('PPCart_Product_Template') || ! $this->temporaryProductId) {
            $this->markTestSkipped('FSE product template fallback image checks require a temporary product.');
        }

        $output = $this->withGlobalPost(
            $this->temporaryProductId,
            function () {
                return $this->renderBlocks(\PPCart_Product_Template::get_template_content());
            }
        );

        $this->assertSame(
            1,
            substr_count($output, 'publishpress-cart-product-template__fallback-image')
        );
        $this->assertStringContainsString('checkout-background.webp', $output);
    }

    public function test_IT_117_fse_product_template_cover_prefers_the_featured_image_over_the_fallback(): void
    {
        if (! class_exists('PPCart_Product_Template') || ! $this->temporaryProductId) {
            $this->markTestSkipped('Featured image precedence check requires a temporary product.');
        }

        if (! defined('PPCART_BASE_DIR')) {
            $this->markTestSkipped('Featured image fallback checks require PPCART_BASE_DIR.');
        }

        $fallbackImagePath = trailingslashit(PPCART_BASE_DIR)
            . \PPCart_Product_Template::FALLBACK_IMAGE_PATH;

        if (! file_exists($fallbackImagePath)) {
            $this->markTestSkipped('Featured image fallback checks require the fallback image asset.');
        }

        $upload = wp_upload_bits(
            'ppcart-featured-cover-test.webp',
            null,
            file_get_contents($fallbackImagePath)
        );

        if (! empty($upload['error'])) {
            $this->markTestSkipped('Featured image precedence check requires writing a temporary upload.');
        }

        $attachmentId = wp_insert_attachment(
            array(
                'post_mime_type' => 'image/webp',
                'post_title'     => 'PublishPress Cart Featured Cover Test',
                'post_status'    => 'inherit',
            ),
            $upload['file'],
            $this->temporaryProductId
        );

        if (! $attachmentId || is_wp_error($attachmentId)) {
            wp_delete_file($upload['file']);
            $this->markTestSkipped('Featured image precedence check requires creating a temporary attachment.');
        }

        $this->temporaryFeaturedAttachmentId = (int) $attachmentId;
        set_post_thumbnail($this->temporaryProductId, $this->temporaryFeaturedAttachmentId);

        $output = $this->withGlobalPost(
            $this->temporaryProductId,
            function () {
                return $this->renderBlocks(\PPCart_Product_Template::get_template_content());
            }
        );

        delete_post_thumbnail($this->temporaryProductId);

        $this->assertStringNotContainsString('publishpress-cart-product-template__fallback-image', $output);
        $this->assertStringContainsString('wp-block-cover__image-background', $output);
    }

    public function test_IT_118_supported_block_themes_hand_product_rendering_back_to_wordpress(): void
    {
        if (! class_exists('PPCart_Product_Template')
            || ! \PPCart_Product_Template::is_supported_block_theme()) {
            $this->markTestSkipped('Template resolution checks require a supported block theme.');
        }

        if (! $this->temporaryProductId || ! class_exists('PPCart_Public_Page_Controller')) {
            $this->markTestSkipped('Template resolution checks require a product and page controller.');
        }

        $pageController = new \PPCart_Public_Page_Controller();
        $fallbackSingle = '/tmp/theme-single.php';

        $checkoutSingle = $this->withGlobalPost(
            $this->temporaryProductId,
            function () use ($pageController, $fallbackSingle) {
                return $pageController->product_template($fallbackSingle);
            }
        );

        $this->assertSame($fallbackSingle, $checkoutSingle);
    }

    public function test_IT_119_classic_themes_keep_the_legacy_php_checkout_renderer(): void
    {
        if (! $this->temporaryProductId || ! class_exists('PPCart_Public_Page_Controller')) {
            $this->markTestSkipped('Template resolution checks require a product and page controller.');
        }

        $pageController = new \PPCart_Public_Page_Controller();
        $fallbackSingle = '/tmp/theme-single.php';

        $legacySingle = $this->withGlobalPost(
            $this->temporaryProductId,
            function () use ($pageController, $fallbackSingle) {
                add_filter('ppcart_use_block_product_template', '__return_false');
                $result = $pageController->product_template($fallbackSingle);
                remove_filter('ppcart_use_block_product_template', '__return_false');

                return $result;
            }
        );

        $this->assertStringContainsString('public/templates/checkout1.php', $legacySingle);
    }

    public function test_IT_120_order_confirmation_requests_keep_the_legacy_checkout_renderer(): void
    {
        if (! $this->temporaryProductId) {
            $this->markTestSkipped('Template resolution checks require a product.');
        }

        $fallbackSingle        = '/tmp/theme-single.php';
        $confirmationPublic      = new PPCartBlockTestConfirmationPublic();
        $confirmationSingle      = $this->withGlobalPost(
            $this->temporaryProductId,
            function () use ($confirmationPublic, $fallbackSingle) {
                return $confirmationPublic->product_template($fallbackSingle);
            }
        );

        $this->assertStringContainsString('public/templates/checkout1.php', $confirmationSingle);
    }

    public function test_IT_121_per_product_theme_template_override_hands_rendering_back_to_wordpress(): void
    {
        if (! $this->temporaryProductId || ! class_exists('PPCart_Public_Page_Controller')) {
            $this->markTestSkipped('Template resolution checks require a product and page controller.');
        }

        $pageController = new \PPCart_Public_Page_Controller();
        $fallbackSingle = '/tmp/theme-single.php';

        update_post_meta($this->temporaryProductId, '_ppcart_page_template', 'theme');

        $themeSingle = $this->withGlobalPost(
            $this->temporaryProductId,
            function () use ($pageController, $fallbackSingle) {
                add_filter('ppcart_use_block_product_template', '__return_false');
                $result = $pageController->product_template($fallbackSingle);
                remove_filter('ppcart_use_block_product_template', '__return_false');

                return $result;
            }
        );

        delete_post_meta($this->temporaryProductId, '_ppcart_page_template');

        $this->assertSame($fallbackSingle, $themeSingle);
    }

    public function test_IT_122_global_template_disable_hands_rendering_back_to_wordpress(): void
    {
        if (! $this->temporaryProductId || ! class_exists('PPCart_Public_Page_Controller')) {
            $this->markTestSkipped('Template resolution checks require a product and page controller.');
        }

        $pageController = new \PPCart_Public_Page_Controller();
        $fallbackSingle = '/tmp/theme-single.php';

        update_option('_ppcart_disable_template', '1');

        $disabledSingle = $this->withGlobalPost(
            $this->temporaryProductId,
            function () use ($pageController, $fallbackSingle) {
                add_filter('ppcart_use_block_product_template', '__return_false');
                $result = $pageController->product_template($fallbackSingle);
                remove_filter('ppcart_use_block_product_template', '__return_false');

                return $result;
            }
        );

        delete_option('_ppcart_disable_template');

        $this->assertSame($fallbackSingle, $disabledSingle);
    }

    public function test_IT_123_checkout_block_renders_its_frontend_wrapper(): void
    {
        $output = $this->renderConfiguredCheckoutBlock();

        $this->assertStringContainsString('publishpress-cart-checkout-form', $output);
    }

    public function test_IT_124_checkout_block_consumes_the_shortcode_instead_of_leaking_it(): void
    {
        $output = $this->renderConfiguredCheckoutBlock();

        $this->assertStringNotContainsString('[ppcart_form', $output);
        $this->assertStringNotContainsString('[studiocart-form', $output);
    }

    public function test_IT_125_checkout_block_renders_its_style_customizations(): void
    {
        $this->renderConfiguredCheckoutBlock();
        $css = $this->getCheckoutBlockInlineCss();

        $this->assertStringContainsString('#123456', $css);
        $this->assertStringContainsString('border-radius: 10px', $css);
    }

    public function test_IT_126_checkout_block_keeps_the_order_total_price_visually_aligned(): void
    {
        $this->renderConfiguredCheckoutBlock();
        $css = $this->getCheckoutBlockInlineCss();

        $this->assertStringContainsString('.ppcart .total{align-items: center;display: flex;', $css);
        $this->assertStringContainsString('.ppcart .total .price{float: none;', $css);
    }

    public function test_IT_127_checkout_block_uses_the_configured_anchor_as_its_style_scope(): void
    {
        $output = $this->renderConfiguredCheckoutBlock();
        $css    = $this->getCheckoutBlockInlineCss();

        $this->assertStringContainsString('id="checkout-anchor"', $output);
        $this->assertStringContainsString('#checkout-anchor', $css);
    }

    public function test_IT_128_checkout_block_pre_selects_the_configured_payment_plan(): void
    {
        $output = $this->renderConfiguredCheckoutBlock();

        $this->assertStringContainsString('id="option-e2e_plan_200" checked', $output);
    }

    public function test_IT_129_checkout_block_resolves_the_current_sc_product_when_no_pid_is_set(): void
    {
        if (! $this->temporaryProductId) {
            $this->markTestSkipped('Dynamic current-product rendering requires a temporary product.');
        }

        $output = $this->withGlobalPost(
            $this->temporaryProductId,
            function () {
                return $this->renderBlocks('<!-- wp:publishpress-cart/checkout-form /-->');
            }
        );

        $this->assertStringContainsString('publishpress-cart-checkout-form', $output);
        $this->assertStringContainsString('id="ppcart-payment-form"', $output);
    }

    public function test_IT_130_checkout_block_resolves_current_products_from_filtered_post_types(): void
    {
        if (! $this->temporaryFilteredProductId) {
            $this->markTestSkipped('Filtered current-product rendering requires a temporary filtered product.');
        }

        add_filter('ppcart_product_post_type', 'ppcart_integration_include_filtered_product_type');
        add_filter('ppcart_setup_product_post_type', 'ppcart_integration_include_filtered_product_type');

        $output = $this->withGlobalPost(
            $this->temporaryFilteredProductId,
            function () {
                return $this->renderBlocks('<!-- wp:publishpress-cart/checkout-form /-->');
            }
        );

        remove_filter('ppcart_product_post_type', 'ppcart_integration_include_filtered_product_type');
        remove_filter('ppcart_setup_product_post_type', 'ppcart_integration_include_filtered_product_type');

        $this->assertStringContainsString('publishpress-cart-checkout-form', $output);
        $this->assertStringContainsString('id="ppcart-payment-form"', $output);
    }

    public function test_IT_131_duplicate_checkout_blocks_emit_only_one_live_form(): void
    {
        $productId = $this->getProductIdString();
        $content   = '<!-- wp:publishpress-cart/checkout-form {"pid":"' . esc_attr($productId) . '","template":"normal"} /-->' . "\n"
            . '<!-- wp:publishpress-cart/checkout-form {"pid":"' . esc_attr($productId) . '","template":"normal"} /-->';
        $output    = $this->renderBlocks($content);

        $this->assertSame(1, substr_count($output, 'id="ppcart-payment-form"'));
    }

    public function test_IT_132_checkout_blocks_for_different_products_render_multiple_live_forms(): void
    {
        if (! $this->temporaryRecurringProductId) {
            $this->markTestSkipped('Multi-product checkout rendering requires a second temporary product.');
        }

        $content = '<!-- wp:publishpress-cart/checkout-form {"pid":"' . esc_attr($this->getProductIdString()) . '","template":"normal"} /-->' . "\n"
            . '<!-- wp:publishpress-cart/checkout-form {"pid":"' . esc_attr($this->getRecurringProductIdString()) . '","template":"normal"} /-->';
        $output  = $this->renderBlocks($content);

        $this->assertSame(2, substr_count($output, 'id="ppcart-payment-form"'));
    }

    public function test_IT_133_checkout_block_plus_legacy_shortcode_emits_only_one_live_form(): void
    {
        $productId = $this->getProductIdString();
        $content   = '<!-- wp:publishpress-cart/checkout-form {"pid":"' . esc_attr($productId) . '","template":"normal"} /-->' . "\n"
            . '[ppcart_form id="' . esc_attr($productId) . '"]';
        $output    = $this->renderPostContent($content);

        $this->assertSame(1, substr_count($output, 'id="ppcart-payment-form"'));
    }

    public function test_IT_134_checkout_block_plus_elementor_popup_can_render_two_live_forms(): void
    {
        $productId = $this->getProductIdString();
        $content   = '<!-- wp:publishpress-cart/checkout-form {"pid":"' . esc_attr($productId) . '","template":"normal"} /-->' . "\n"
            . '[ppcart_form id="' . esc_attr($productId) . '" ele_popup="1"]';
        $output    = $this->renderPostContent($content);

        $this->assertSame(2, substr_count($output, 'id="ppcart-payment-form"'));
    }

    public function test_IT_135_checkout_block_renders_customized_section_headings_and_labels(): void
    {
        $content = '<!-- wp:publishpress-cart/checkout-form {"pid":"' . esc_attr($this->getProductIdString()) . '","template":"normal","textSettings":{"contactInfoHeading":"Buyer Details","paymentPlanHeading":"Choose Your Plan","paymentInfoHeading":"Billing Details","orderTotalHeading":"Checkout Total","dueTodayLabel":"Pay Today"}} /-->';
        $output  = $this->renderBlocks($content);

        $this->assertStringContainsString('Buyer Details', $output);
        $this->assertStringContainsString('Choose Your Plan', $output);
        $this->assertStringContainsString('Checkout Total', $output);
        $this->assertStringContainsString('Pay Today', $output);
        $this->assertStringContainsString('name="ppcart_due_today_label" value="Pay Today"', $output);
    }

    public function test_IT_136_checkout_block_shows_the_due_today_label_for_recurring_plans(): void
    {
        if (! $this->temporaryRecurringProductId) {
            $this->markTestSkipped('Recurring label checks require creating a temporary recurring product.');
        }

        $content = '<!-- wp:publishpress-cart/checkout-form {"pid":"' . esc_attr($this->getRecurringProductIdString()) . '","template":"normal"} /-->';
        $output  = $this->renderBlocks($content);

        $this->assertStringContainsString('Due Today', $output);
    }

    public function test_IT_137_checkout_block_honors_intentionally_blank_text_settings(): void
    {
        $content = '<!-- wp:publishpress-cart/checkout-form {"pid":"' . esc_attr($this->getProductIdString()) . '","template":"normal","textSettings":{"contactInfoHeading":" "}} /-->';
        $output  = $this->renderBlocks($content);

        $this->assertStringNotContainsString('>Contact Info</h3>', $output);
    }

    public function test_IT_138_checkout_block_renders_sections_in_the_configured_content_order(): void
    {
        $content = '<!-- wp:publishpress-cart/checkout-form {"pid":"' . esc_attr($this->getProductIdString()) . '","template":"normal","contentOrder":["submit_button","contact_info","payment_plan","coupon","payment_method","payment_details","order_bumps","order_summary","terms_consent","express_payment"]} /-->';
        $output  = $this->renderBlocks($content);

        $submitPosition  = strpos($output, 'id="ppcart_card_button"');
        $contactPosition = strpos($output, 'checkout-contact-info');
        $planPosition    = strpos($output, 'class="ppcart-section products');

        $this->assertNotFalse($submitPosition);
        $this->assertNotFalse($contactPosition);
        $this->assertNotFalse($planPosition);
        $this->assertLessThan($contactPosition, $submitPosition);
        $this->assertLessThan($planPosition, $contactPosition);
    }

    /**
     * @test-id IT-310
     */
    public function test_IT_310_checkout_html_uses_canonical_submit_and_terms_ids(): void
    {
        $previousTermsUrl = get_option('_ppcart_terms_url');
        update_option('_ppcart_terms_url', 'https://example.com/terms');

        try {
            $content = '<!-- wp:publishpress-cart/checkout-form {"pid":"' . esc_attr($this->getProductIdString()) . '","template":"normal"} /-->';
            $output  = $this->renderBlocks($content);

            $this->assertStringContainsString('id="ppcart_card_button"', $output);
            $this->assertStringContainsString('id="ppcart_accept_terms"', $output);
            $this->assertStringContainsString('name="ppcart_accept_terms"', $output);
            $this->assertStringNotContainsString('id="sc_card_button"', $output);
            $this->assertStringNotContainsString('id="sc_accept_terms"', $output);
        } finally {
            if (false === $previousTermsUrl) {
                delete_option('_ppcart_terms_url');
            } else {
                update_option('_ppcart_terms_url', $previousTermsUrl);
            }
        }
    }

    public function test_IT_139_arranged_content_keeps_payment_methods_inside_the_payment_info_group(): void
    {
        $content = '<!-- wp:publishpress-cart/checkout-form {"pid":"' . esc_attr($this->getProductIdString()) . '","template":"normal","contentOrder":["submit_button","contact_info","payment_plan","coupon","payment_method","payment_details","order_bumps","order_summary","terms_consent","express_payment"]} /-->';
        $output  = $this->renderBlocks($content);

        $this->assertStringContainsString('ppcart-section pay-info ppcart-payment-method-section', $output);
    }

    public function test_IT_140_checkout_block_omits_the_coupon_section_when_coupons_are_unavailable(): void
    {
        if (! function_exists('ppcart_is_pro') || ! ppcart_supports('pro')) {
            return;
        }

        $content = '<!-- wp:publishpress-cart/checkout-form {"pid":"' . esc_attr($this->getProductIdString()) . '","template":"normal","contentOrder":["submit_button","contact_info","payment_plan","coupon","payment_method","payment_details","order_bumps","order_summary","terms_consent","express_payment"]} /-->';
        $output  = $this->renderBlocks($content);

        $this->assertStringNotContainsString('sc-coupon-section', $output);
    }

    public function test_IT_141_arranged_mode_still_fires_checkout_field_extension_hooks(): void
    {
        $content = '<!-- wp:publishpress-cart/checkout-form {"pid":"' . esc_attr($this->getProductIdString()) . '","template":"normal","contentOrder":["submit_button","contact_info","payment_plan","coupon","payment_method","payment_details","order_bumps","order_summary","terms_consent","express_payment"]} /-->';

        $extensionCallback = function () {
            echo '<div class="ppcart-test-extension-field">Extension field</div>';
        };
        add_action('ppcart_card_details_fields', $extensionCallback, 7);

        $output = $this->renderBlocks($content);

        remove_action('ppcart_card_details_fields', $extensionCallback, 7);

        $this->assertStringContainsString('ppcart-test-extension-field', $output);
    }

    public function test_IT_142_legacy_product_shortcode_block_does_not_render_without_compat(): void
    {
        if (! $this->temporaryProductId) {
            $this->markTestSkipped('Legacy checkout block rendering requires a temporary product.');
        }

        $output = $this->renderBlocksWithProductContext(
            $this->temporaryProductId,
            '<!-- wp:sc-products-shortcode/product-shortcode {"template":"true","hide_labels":true} /-->'
        );

        $this->assertStringNotContainsString('publishpress-cart-checkout-form', $output);
    }

    public function test_IT_143_legacy_product_shortcode_block_does_not_leak_shortcode_without_compat(): void
    {
        if (! $this->temporaryProductId) {
            $this->markTestSkipped('Legacy checkout block rendering requires a temporary product.');
        }

        $output = $this->renderBlocksWithProductContext(
            $this->temporaryProductId,
            '<!-- wp:sc-products-shortcode/product-shortcode {"template":"true","hide_labels":true} /-->'
        );

        $this->assertStringNotContainsString('[ppcart_form', $output);
        $this->assertStringNotContainsString('[studiocart-form', $output);
    }

    private function renderConfiguredCheckoutBlock(): string
    {
        $content = '<!-- wp:publishpress-cart/checkout-form {"pid":"' . esc_attr($this->getProductIdString()) . '","anchor":"checkout-anchor","template":"normal","plan":"e2e_plan_200","hide_labels":true,"styleSettings":{"accentColor":"#123456","surfaceStyle":"card","cornerRadius":"rounded"}} /-->';

        return $this->renderBlocks($content);
    }

    private function getCheckoutBlockInlineCss(): string
    {
        if (! did_action('wp_enqueue_scripts')) {
            do_action('wp_enqueue_scripts');
        }

        return $this->getEnqueuedInlineCss(\PPCart_Checkout_Renderer::STYLE_HANDLE);
    }
}
