<?php

declare(strict_types=1);

namespace Tests\Integration\Embed;

use Tests\Support\Integration\CheckoutBlockTestCase;

class EmbedCheckoutTest extends CheckoutBlockTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    private $temporaryPageId = 0;

    private $capturedSkins = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->createStandardProduct();
    }

    protected function tearDown(): void
    {
        if ($this->temporaryPageId) {
            wp_delete_post($this->temporaryPageId, true);
            $this->temporaryPageId = 0;
        }

        wp_set_current_user(0);
        remove_all_filters('ppcart_embed_frame_ancestors');
        remove_filter('ppcart_checkout_template_path', [ $this, 'captureSkin' ], 10);

        parent::tearDown();
    }

    public function test_IT_394_the_shell_template_serves_only_embed_requests_on_singular_posts(): void
    {
        $controller = new \PPCart_Public_Embed_Controller();
        $fallback   = '/tmp/theme-single.php';
        $shell      = 'public/templates/embed/checkout.php';

        $this->go_to(get_permalink($this->temporaryProductId));
        $this->assertSame($fallback, $controller->template_include($fallback));

        $this->go_to(add_query_arg('ppcart-embed', '1', get_permalink($this->temporaryProductId)));
        $this->assertStringEndsWith($shell, $controller->template_include($fallback));

        $this->go_to(add_query_arg('ppcart-embed', 'popup', get_permalink($this->temporaryProductId)));
        $this->assertStringEndsWith($shell, $controller->template_include($fallback));

        $this->go_to(add_query_arg('ppcart-embed', '', get_permalink($this->temporaryProductId)));
        $this->assertSame($fallback, $controller->template_include($fallback));

        $this->go_to(home_url('/?ppcart-embed=1'));
        $this->assertSame($fallback, $controller->template_include($fallback));
    }

    public function test_IT_395_the_shell_renders_the_product_form_with_the_saved_appearance(): void
    {
        update_post_meta($this->temporaryProductId, '_ppcart_embed_accent', '#ff5a1f');
        update_post_meta($this->temporaryProductId, '_ppcart_embed_radius', '12');
        update_post_meta($this->temporaryProductId, '_ppcart_embed_font_url', 'https://fonts.example/inter.css');

        $html = $this->renderShell(add_query_arg('ppcart-embed', '1', get_permalink($this->temporaryProductId)));

        $this->assertStringContainsString('id="ppcart_card_button"', $html);
        $this->assertStringContainsString('data-testid="ppcart-order-submit"', $html);
        $this->assertStringContainsString(':root{--ppcart-embed-accent:#ff5a1f;--ppcart-embed-radius:12px;}', $html);
        $this->assertMatchesRegularExpression('/<body class="[^"]*\bppcart-embed\b[^"]*\bppcart-embed--accent\b[^"]*\bppcart-embed--radius\b[^"]*"/', $html);
        $this->assertStringNotContainsString('ppcart-embed--text', $html);
        $this->assertStringContainsString('<main class="ppcart-embed__main">', $html);
        $this->assertStringContainsString('public/css/ppcart-embed.css', $html);
        $this->assertStringContainsString('public/js/ppcart-embed-frame.js', $html);
        $this->assertStringContainsString("href='https://fonts.example/inter.css'", $html);
        $this->assertFalse(apply_filters('show_admin_bar', true));
    }

    public function test_IT_396_the_2_step_layout_passes_the_skin_to_the_checkout_template(): void
    {
        update_post_meta($this->temporaryProductId, '_ppcart_embed_layout', '2-step');
        add_filter('ppcart_checkout_template_path', [ $this, 'captureSkin' ], 10, 2);

        $html = $this->renderShell(add_query_arg('ppcart-embed', '1', get_permalink($this->temporaryProductId)));

        $this->assertSame([ '2-step' ], $this->capturedSkins);
        $this->assertMatchesRegularExpression('/<body class="[^"]*\bppcart-embed--layout-2-step\b[^"]*"/', $html);
        $this->assertStringContainsString('id="ppcart_card_button"', $html);
    }

    public function test_IT_399_an_editor_previews_a_layout_before_saving_it_and_a_visitor_cannot(): void
    {
        update_post_meta($this->temporaryProductId, '_ppcart_embed_layout', '2-step');
        add_filter('ppcart_checkout_template_path', [ $this, 'captureSkin' ], 10, 2);
        $url = add_query_arg([ 'ppcart-embed' => '1', 'ppcart-embed-layout' => 'standard' ], get_permalink($this->temporaryProductId));

        $visitorHtml = $this->renderShell($url);

        $this->assertSame([ '2-step' ], $this->capturedSkins);
        $this->assertMatchesRegularExpression('/<body class="[^"]*\bppcart-embed--layout-2-step\b[^"]*"/', $visitorHtml);

        $this->capturedSkins = [];
        wp_set_current_user(self::factory()->user->create([ 'role' => 'administrator' ]));

        $editorHtml = $this->renderShell($url);

        $this->assertSame([ 'normal' ], $this->capturedSkins);
        $this->assertStringNotContainsString('ppcart-embed--layout-2-step', $editorHtml);
    }

    public function test_IT_397_only_pages_with_checkout_content_render_inside_the_shell(): void
    {
        $controller = new \PPCart_Public_Embed_Controller();
        $fallback   = '/tmp/theme-single.php';

        $this->temporaryPageId = (int) wp_insert_post([
            'post_type'    => 'page',
            'post_status'  => 'publish',
            'post_title'   => 'Embed account page',
            'post_content' => '<p>Your account.</p>',
        ]);
        $plainUrl = add_query_arg('ppcart-embed', '1', get_permalink($this->temporaryPageId));

        $this->go_to($plainUrl);
        $this->assertFalse(ppcart_is_embed_request());
        $this->assertSame($fallback, $controller->template_include($fallback));

        add_filter('ppcart_embed_allows_post', '__return_true');
        $this->go_to($plainUrl);
        $this->assertTrue(ppcart_is_embed_request());
        $this->assertStringEndsWith('public/templates/embed/checkout.php', $controller->template_include($fallback));
        remove_filter('ppcart_embed_allows_post', '__return_true');

        wp_update_post([
            'ID'           => $this->temporaryPageId,
            'post_content' => '<p>Thank you, your embedded order is complete.</p>[ppcart_receipt]',
        ]);

        $html = $this->renderShell($plainUrl);

        $this->assertStringContainsString('Thank you, your embedded order is complete.', $html);
        $this->assertStringContainsString('<main class="ppcart-embed__main">', $html);
        $this->assertMatchesRegularExpression('/<body class="[^"]*\bppcart-embed\b[^"]*"/', $html);
        $this->assertStringNotContainsString('id="ppcart_card_button"', $html);
        $this->assertStringContainsString('public/js/ppcart-embed-frame.js', $html);
    }

    public function test_IT_403_a_confirmation_page_opened_with_a_valid_order_token_renders_inside_the_shell(): void
    {
        $this->temporaryPageId = (int) wp_insert_post([
            'post_type'    => 'page',
            'post_status'  => 'publish',
            'post_title'   => 'Embed confirmation page',
            'post_content' => '<p>Thanks for buying!</p>',
        ]);
        $orderId = (int) wp_insert_post([
            'post_type'   => ppcart_live_post_type('order'),
            'post_status' => 'publish',
            'post_title'  => 'Embed order',
        ]);
        $token = \PPCart_Order::ensure_invoice_access_token($orderId);
        $page  = get_permalink($this->temporaryPageId);

        $this->go_to(add_query_arg([ 'ppcart-order' => $orderId, 'token' => 'wrong-token', 'ppcart-embed' => '1' ], $page));
        $this->assertFalse(ppcart_is_embed_request());

        $html = $this->renderShell(add_query_arg([ 'ppcart-order' => $orderId, 'token' => $token, 'ppcart-embed' => '1' ], $page));

        wp_delete_post($orderId, true);

        $this->assertStringContainsString('Thanks for buying!', $html);
        $this->assertMatchesRegularExpression('/<body class="[^"]*\bppcart-embed\b[^"]*"/', $html);
        $this->assertStringContainsString('public/js/ppcart-embed-frame.js', $html);
    }

    public function test_IT_398_frame_header_lines_come_from_the_frame_ancestors_filter(): void
    {
        $controller = new \PPCart_Public_Embed_Controller();

        $this->assertSame([ 'Content-Security-Policy: frame-ancestors *' ], $controller->frame_header_lines());

        add_filter('ppcart_embed_frame_ancestors', static function () {
            return [ 'https://merchant.example', "'self'" ];
        });

        $this->assertSame(
            [ "Content-Security-Policy: frame-ancestors https://merchant.example 'self'" ],
            $controller->frame_header_lines()
        );
    }

    public function captureSkin($path, $template)
    {
        $this->capturedSkins[] = $template;

        return $path;
    }

    private function renderShell(string $url): string
    {
        $this->go_to($url);

        // Each render is one request: fresh asset queues so the head prints again, and a fresh duplicate-render guard.
        unset($GLOBALS['wp_styles'], $GLOBALS['wp_scripts']);
        ppcart_checkout_reset_request_render_guard();

        $controller = new \PPCart_Public_Embed_Controller();
        $template   = $controller->template_include('/tmp/theme-single.php');

        $this->assertStringEndsWith('public/templates/embed/checkout.php', $template);

        ob_start();
        include $template;

        return (string) ob_get_clean();
    }
}
