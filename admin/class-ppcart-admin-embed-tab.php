<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * The Embed tab of the product edit screen. It has no fields: it shows the embed
 * code, printed from ppcart_product_settings_panel_after_fields.
 */
class PPCart_Admin_Embed_Tab
{
    const TAB_ID = 'embed';

    const ASSET_HANDLE = 'ppcart-admin-embed';

    const PANEL_TEMPLATE = 'admin/partials/ppcart-admin-embed-panel.php';

    /**
     * @param string  $tab_id Product settings tab ID.
     * @param WP_Post $post   Product being edited.
     */
    public function render_panel($tab_id, $post)
    {
        if (self::TAB_ID !== $tab_id || ! $post instanceof WP_Post) {
            return;
        }

        $ppcart_embed_published = 'publish' === get_post_status($post);
        $ppcart_embed_codes     = $ppcart_embed_published ? $this->codes($post) : [];
        $ppcart_embed_preview   = $ppcart_embed_published ? add_query_arg('ppcart-embed', '1', get_permalink($post)) : '';

        include PPCART_BASE_DIR . self::PANEL_TEMPLATE;
    }

    /**
     * Loads the panel assets on the product edit screen only.
     *
     * @param string $hook_suffix Current admin page.
     */
    public function enqueue_assets($hook_suffix)
    {
        $post = $this->edited_product($hook_suffix);

        if (! $post) {
            return;
        }

        wp_enqueue_style(self::ASSET_HANDLE, PPCART_BASE_URL . 'admin/css/ppcart-admin-embed.css', [], PPCART_VERSION);
        wp_enqueue_script(self::ASSET_HANDLE, PPCART_BASE_URL . 'admin/js/ppcart-admin-embed.js', [ 'jquery' ], PPCART_VERSION, true);
        wp_localize_script(self::ASSET_HANDLE, 'ppcartEmbedAdmin', [
            'i18n' => [
                'copied'     => __('Copied', 'publishpress-cart'),
                'copyFailed' => __('Press Ctrl+C or Cmd+C to copy', 'publishpress-cart'),
            ],
        ]);
    }

    /**
     * The three snippets, in the order the panel shows them.
     *
     * @param WP_Post $post Published product.
     * @return array<string, array{title: string, summary: string, description: string, code: string}>
     */
    private function codes($post)
    {
        $permalink = get_permalink($post);
        $label     = trim(wp_strip_all_tags((string) ppcart_get_post_meta($post->ID, 'button_text', true)));
        $label     = '' !== $label ? $label : __('Buy now', 'publishpress-cart');

        return [
            'inline' => [
                'title'       => __('Inline checkout', 'publishpress-cart'),
                'summary'     => __('The form shows in your page.', 'publishpress-cart'),
                'description' => __('Paste this code where the checkout form must show.', 'publishpress-cart'),
                'code'        => '<div data-ppcart-checkout="' . esc_url($permalink) . '"></div>' . "\n" . $this->loader_tag(),
            ],
            'popup'  => [
                'title'       => __('Popup button', 'publishpress-cart'),
                'summary'     => __('A button opens the form over your page.', 'publishpress-cart'),
                'description' => __('Paste this code where the button must show.', 'publishpress-cart'),
                'code'        => '<button type="button" data-ppcart-popup="' . esc_url($permalink) . '">' . esc_html($label) . '</button>' . "\n" . $this->loader_tag(),
            ],
            'link'   => [
                'title'       => __('Popup link', 'publishpress-cart'),
                'summary'     => __('For a site builder button that accepts only a link.', 'publishpress-cart'),
                'description' => __('Set this address as the link of a button. On a page with the loader script, it opens the popup. On other pages, it opens the checkout page.', 'publishpress-cart'),
                'code'        => add_query_arg('ppcart-embed', 'popup', $permalink),
            ],
        ];
    }

    private function loader_tag()
    {
        $src = add_query_arg('ver', PPCART_VERSION, PPCART_BASE_URL . 'public/js/ppcart-embed.js');

        return '<script src="' . esc_url($src) . '" async></script>';
    }

    /**
     * @param string $hook_suffix Current admin page.
     * @return WP_Post|null The product on its edit screen, else null.
     */
    private function edited_product($hook_suffix)
    {
        if ('post.php' !== $hook_suffix && 'post-new.php' !== $hook_suffix) {
            return null;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $post   = get_post();

        if (! $screen || 'post' !== $screen->base || ! ppcart_is_product_post_type($screen->post_type) || ! $post instanceof WP_Post) {
            return null;
        }

        return $post;
    }

    /**
     * @param array<string, string> $tabs Product settings tabs.
     * @return array<string, string>
     */
    public function register_tab($tabs)
    {
        $tabs                 = (array) $tabs;
        $tabs[ self::TAB_ID ] = __('Embed', 'publishpress-cart');

        return $tabs;
    }
}
