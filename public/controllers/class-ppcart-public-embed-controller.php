<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Serves the embedded checkout: a singular front-end request with `?ppcart-embed=`
 * gets the bare shell in `public/templates/embed/checkout.php`, frame headers, no
 * admin bar, the frame bridge script, and the saved appearance as body classes and
 * CSS variables. The host page loads `public/js/ppcart-embed.js` to frame it.
 */
class PPCart_Public_Embed_Controller
{
    const SHELL_TEMPLATE = 'public/templates/embed/checkout.php';

    /**
     * Style handle of the merchant's web font. WordPress prints it as
     * `<link id="ppcart-embed-font-css">`, which the admin live preview swaps.
     */
    const FONT_HANDLE = 'ppcart-embed-font';

    const SKINS = [
        'standard' => 'normal',
        '2-step'   => '2-step',
    ];

    public function template_include($template)
    {
        if (! ppcart_is_embed_request()) {
            return $template;
        }

        $shell = PPCART_BASE_DIR . self::SHELL_TEMPLATE;

        return file_exists($shell) ? $shell : $template;
    }

    public function send_frame_headers()
    {
        if (! ppcart_is_embed_request() || headers_sent()) {
            return;
        }

        $this->replace_frame_headers();
    }

    /**
     * An embed request answers a redirect to another site with a page that asks
     * the host to open it in the top window. A 302 would load that site inside
     * the frame (a subscription's external thank-you page, a closed cart's redirect).
     *
     * @param string $location Redirect target.
     * @return string
     */
    public function bounce_external_redirect($location)
    {
        if ('' === ppcart_embed_request_mode() || headers_sent() || ! ppcart_embed_redirect_leaves_site($location, home_url())) {
            return $location;
        }

        nocache_headers();
        status_header(200);
        header('Content-Type: text/html; charset=' . get_option('blog_charset'));
        $this->replace_frame_headers();

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ppcart_embed_bounce_page() escapes the URL for HTML and for the script.
        echo ppcart_embed_bounce_page($location, PPCART_BASE_URL . 'public/js/ppcart-embed-frame.js?ver=' . PPCART_VERSION);
        exit;
    }

    /**
     * @param array<int, string> $existing_policies Values of the CSP headers already set for this response.
     * @return array<int, string>
     */
    public function frame_header_lines($existing_policies = [])
    {
        /**
         * Sources allowed to frame the embedded checkout (CSP `frame-ancestors`).
         *
         * @param array<int, string> $sources Defaults to every origin.
         */
        return ppcart_embed_frame_header_lines(apply_filters('ppcart_embed_frame_ancestors', [ '*' ]), $existing_policies);
    }

    private function replace_frame_headers()
    {
        $existing = [];

        foreach (headers_list() as $header) {
            if (0 === stripos($header, 'Content-Security-Policy:')) {
                $existing[] = trim(substr($header, strlen('Content-Security-Policy:')));
            }
        }

        header_remove('X-Frame-Options');
        header_remove('Content-Security-Policy');

        foreach ($this->frame_header_lines($existing) as $line) {
            header($line, false);
        }
    }

    public function show_admin_bar($show)
    {
        return ppcart_is_embed_request() ? false : $show;
    }

    public function enqueue_assets()
    {
        if (! ppcart_is_embed_request()) {
            return;
        }

        $appearance = $this->appearance();

        // A non-product page fires no shortcode, and the embed stylesheet depends on the checkout one.
        do_action('ppcart_enqueue_frontend_assets');

        wp_enqueue_style('ppcart-embed', PPCART_BASE_URL . 'public/css/ppcart-embed.css', [ 'ppcart' ], PPCART_VERSION);

        $variables = PPCart_Embed_Appearance::css_variables($appearance);
        if ('' !== $variables) {
            wp_add_inline_style('ppcart-embed', ':root{' . $variables . '}');
        }

        if ('' !== $appearance['font_url']) {
            // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- A third-party font stylesheet chosen by the merchant.
            wp_enqueue_style(self::FONT_HANDLE, $appearance['font_url'], [], null);
        }

        wp_enqueue_script('ppcart-embed-frame', PPCART_BASE_URL . 'public/js/ppcart-embed-frame.js', [ 'ppcart' ], PPCART_VERSION, true);
    }

    public function body_class($classes)
    {
        if (! ppcart_is_embed_request()) {
            return $classes;
        }

        return array_merge((array) $classes, [ 'ppcart-embed' ], PPCart_Embed_Appearance::body_classes($this->appearance()));
    }

    /**
     * Appearance of the embedded post: the product's saved values with the preview
     * layout override, or every value '' for a non-product post.
     *
     * @return array<string, string|int>
     */
    public function appearance()
    {
        $post_id = get_queried_object_id();

        if (! ppcart_is_product_post_type(get_post_type($post_id))) {
            return PPCart_Embed_Appearance::sanitize([]);
        }

        $appearance = ppcart_embed_appearance($post_id);
        $preview    = $this->preview_layout($post_id);

        if ('' !== $preview) {
            $appearance['layout'] = $preview;
        }

        return $appearance;
    }

    /**
     * Prints the framed content: the checkout form for a product (the shortcode also
     * renders the receipt when `ppcart-order` is present), the post content otherwise.
     */
    public function render_content()
    {
        $post_id = get_queried_object_id();

        if (! ppcart_is_product_post_type(get_post_type($post_id))) {
            while (have_posts()) {
                the_post();
                the_content();
            }

            return;
        }

        $layout = $this->appearance()['layout'];
        $skin   = isset(self::SKINS[ $layout ]) ? ' skin="' . self::SKINS[ $layout ] . '"' : '';

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The shortcode handler returns ppcart_kses_frontend_html() output.
        echo do_shortcode('[ppcart_form id="' . absint($post_id) . '"' . $skin . ']');
    }

    /**
     * The admin preview shows a layout before it is saved. Only an editor of the product may ask for it.
     */
    private function preview_layout($post_id)
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only preview flag, gated on edit_post.
        if (! isset($_GET['ppcart-embed-layout']) || ! is_scalar($_GET['ppcart-embed-layout'])) {
            return '';
        }

        if (! current_user_can('edit_post', $post_id)) {
            return '';
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only preview flag, gated on edit_post.
        $layout = sanitize_text_field(wp_unslash($_GET['ppcart-embed-layout']));

        return in_array($layout, PPCart_Embed_Appearance::LAYOUTS, true) ? $layout : '';
    }
}
