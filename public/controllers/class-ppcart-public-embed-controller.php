<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Serves the embedded checkout: a singular front-end request with `?ppcart-embed=`
 * gets the bare shell in `public/templates/embed/checkout.php`, frame headers, no
 * admin bar, and the frame bridge script. The host page loads `public/js/ppcart-embed.js` to frame it.
 */
class PPCart_Public_Embed_Controller
{
    const SHELL_TEMPLATE = 'public/templates/embed/checkout.php';

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

        // A non-product page fires no shortcode, and the embed stylesheet depends on the checkout one.
        do_action('ppcart_enqueue_frontend_assets');

        wp_enqueue_style('ppcart-embed', PPCART_BASE_URL . 'public/css/ppcart-embed.css', [ 'ppcart' ], PPCART_VERSION);

        wp_enqueue_script('ppcart-embed-frame', PPCART_BASE_URL . 'public/js/ppcart-embed-frame.js', [ 'ppcart' ], PPCART_VERSION, true);
    }

    public function body_class($classes)
    {
        if (! ppcart_is_embed_request()) {
            return $classes;
        }

        $classes   = (array) $classes;
        $classes[] = 'ppcart-embed';

        if ('2-step' === $this->layout()) {
            $classes[] = 'ppcart-embed--layout-2-step';
        }

        return $classes;
    }

    /**
     * Checkout layout of the embedded product, from its `embed_layout` meta. Free has
     * no field for it; '' keeps the product's own checkout template.
     *
     * @return string A key of SKINS, or ''.
     */
    public function layout()
    {
        $post_id = get_queried_object_id();

        if (! ppcart_is_product_post_type(get_post_type($post_id))) {
            return '';
        }

        $layout = (string) ppcart_get_post_meta($post_id, 'embed_layout', true);

        return isset(self::SKINS[ $layout ]) ? $layout : '';
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

        $layout = $this->layout();
        $skin   = '' !== $layout ? ' skin="' . self::SKINS[ $layout ] . '"' : '';

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The shortcode handler returns ppcart_kses_frontend_html() output.
        echo do_shortcode('[ppcart_form id="' . absint($post_id) . '"' . $skin . ']');
    }
}
