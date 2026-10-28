<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * The Embed tab of the product edit screen. Its fields hold the embedded checkout
 * appearance (`_ppcart_embed_*`, read by ppcart_embed_appearance()) and are saved by
 * the product metabox like any other tab. The live preview and the embed code are
 * display-only, printed from ppcart_product_settings_panel_after_fields.
 */
class PPCart_Admin_Embed_Tab
{
    const TAB_ID = 'embed';

    const ASSET_HANDLE = 'ppcart-admin-embed';

    const PANEL_TEMPLATE = 'admin/partials/ppcart-admin-embed-panel.php';

    const PREVIEW_SANDBOX = 'allow-forms allow-modals allow-popups allow-same-origin allow-scripts';

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
        wp_localize_script(self::ASSET_HANDLE, 'ppcartEmbedAdmin', $this->client_data($post));
    }

    /**
     * What the browser needs to preview the appearance exactly as the embed shell
     * renders it and to build the popup button code. Class names, CSS variables and
     * validation patterns come from PPCart_Embed_Appearance, not a second list.
     *
     * @param WP_Post $post Product being edited.
     * @return array<string, mixed>
     */
    private function client_data($post)
    {
        $fields = [];
        foreach (array_keys($this->field_definitions()) as $key) {
            $fields[ $key ] = self::field_id($key);
        }

        return [
            'fields'     => $fields,
            'styles'     => PPCart_Embed_Appearance::style_map(),
            'rules'      => [
                'color'            => PPCart_Embed_Appearance::COLOR_PATTERN,
                'fontFamily'       => PPCart_Embed_Appearance::FONT_FAMILY_PATTERN,
                'fontUrlForbidden' => PPCart_Embed_Appearance::FONT_URL_FORBIDDEN_PATTERN,
                'radius'           => PPCart_Embed_Appearance::RADIUS_PATTERN,
                'radiusMax'        => PPCart_Embed_Appearance::RADIUS_MAX,
                'buttonTextMax'    => PPCart_Embed_Appearance::BUTTON_TEXT_MAX,
            ],
            'fontLinkId' => PPCart_Public_Embed_Controller::FONT_HANDLE . '-css',
            'product'    => [
                'buttonColor' => ppcart_meta_key('button_color'),
                'buttonText'  => ppcart_meta_key('button_text'),
            ],
            'permalink'  => get_permalink($post),
            'loaderTag'  => $this->loader_tag(),
            'i18n'       => [
                'buyNow'     => __('Buy now', 'publishpress-cart'),
                'copied'     => __('Copied', 'publishpress-cart'),
                'copyFailed' => __('Press Ctrl+C or Cmd+C to copy', 'publishpress-cart'),
            ],
        ];
    }

    /**
     * The three snippets, in the order the panel shows them. The popup button code
     * depends on the field values, so the browser writes it.
     *
     * @param WP_Post $post Published product.
     * @return array<string, array{title: string, description: string, code: string}>
     */
    private function codes($post)
    {
        $permalink = get_permalink($post);

        return [
            'inline' => [
                'title'       => __('Inline checkout', 'publishpress-cart'),
                'description' => __('The checkout form shows in the page and grows with its content.', 'publishpress-cart'),
                'code'        => '<div data-ppcart-checkout="' . esc_url($permalink) . '"></div>' . "\n" . $this->loader_tag(),
            ],
            'popup'  => [
                'title'       => __('Popup button', 'publishpress-cart'),
                'description' => __('A button that opens the checkout form in a popup. It uses the colors, corners and font above.', 'publishpress-cart'),
                'code'        => '',
            ],
            'link'   => [
                'title'       => __('Popup link', 'publishpress-cart'),
                'description' => __('For a site builder button that only accepts a link. On a page with the loader script, the link opens the popup. On other pages, it opens the checkout page.', 'publishpress-cart'),
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

    /**
     * Without the group the fields render but never save.
     *
     * @param array<int, string> $groups Product field groups.
     * @return array<int, string>
     */
    public function register_group($groups)
    {
        $groups   = (array) $groups;
        $groups[] = self::TAB_ID;

        return $groups;
    }

    /**
     * @param array<int, array<string, mixed>> $fields Embed tab fields.
     * @return array<int, array<string, mixed>>
     */
    public function fields($fields)
    {
        $fields = (array) $fields;

        foreach ($this->field_definitions() as $key => $field) {
            $fields[] = array_merge(
                [
                    'id'          => self::field_id($key),
                    'class'       => 'widefat',
                    'class_size'  => '',
                    'placeholder' => '',
                    'type'        => 'text',
                    'value'       => '',
                ],
                $field
            );
        }

        return $fields;
    }

    /**
     * Meta key and DOM id of the field for an appearance key.
     *
     * @param string $key Short key from PPCart_Embed_Appearance::KEYS.
     * @return string
     */
    public static function field_id($key)
    {
        return ppcart_meta_key('embed_' . $key);
    }

    /**
     * One field per appearance key except the layout, which Free does not offer
     * because it has no 2-step template.
     *
     * @return array<string, array<string, mixed>>
     */
    private function field_definitions()
    {
        return [
            'accent'      => [
                'class'       => 'ppcart-color-field',
                'label'       => __('Accent color', 'publishpress-cart'),
                'description' => __('Color of the buttons and the selected options. Empty keeps the Primary Color of the product.', 'publishpress-cart'),
            ],
            'text'        => [
                'class'       => 'ppcart-color-field',
                'label'       => __('Text color', 'publishpress-cart'),
                'description' => __('Color of the labels and the form text. Empty keeps the default color.', 'publishpress-cart'),
            ],
            'background'  => [
                'class'       => 'ppcart-color-field',
                'label'       => __('Background color', 'publishpress-cart'),
                'description' => __('Color behind the form. Empty keeps the background transparent, so the page around the form shows through.', 'publishpress-cart'),
            ],
            'font_family' => [
                'label'       => __('Font family', 'publishpress-cart'),
                'description' => __('A font name or a list of fonts, such as Inter, sans-serif. Empty keeps the default font.', 'publishpress-cart'),
                'placeholder' => 'Inter, sans-serif',
                'datalist'    => [
                    'system-ui, -apple-system, "Segoe UI", Roboto, sans-serif',
                    '"Helvetica Neue", Helvetica, Arial, sans-serif',
                    'Georgia, "Times New Roman", serif',
                    'Inter, sans-serif',
                    '"Courier New", Courier, monospace',
                ],
            ],
            'font_url'    => [
                'type'        => 'url',
                'label'       => __('Web font URL', 'publishpress-cart'),
                'description' => __('An https stylesheet that loads the font, such as a Google Fonts link. Write the name of the font in Font family too.', 'publishpress-cart'),
                'placeholder' => 'https://fonts.googleapis.com/css2?family=Inter&display=swap',
            ],
            'radius'      => [
                'type'        => 'number',
                'label'       => __('Corner radius', 'publishpress-cart'),
                'description' => __('Roundness of the buttons, fields and boxes, from 0 to 24 pixels. Empty keeps the default corners.', 'publishpress-cart'),
                'min'         => 0,
                'max'         => PPCart_Embed_Appearance::RADIUS_MAX,
                'step'        => 1,
            ],
            'button_text' => [
                'label'       => __('Popup button text', 'publishpress-cart'),
                'description' => __('Label of the button in the popup button code. Empty uses the Submit Button Text of the product, or "Buy now".', 'publishpress-cart'),
            ],
        ];
    }
}
