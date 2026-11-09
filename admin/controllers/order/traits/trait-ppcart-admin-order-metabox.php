<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

require_once __DIR__ . '/trait-ppcart-admin-order-metabox-rendering.php';

trait PPCart_Admin_Order_Metabox_Trait
{
    use PPCart_Admin_Order_Metabox_Rendering_Trait;

    /**
     * Include the related product in WordPress metadata revisions and the editor API.
     *
     * Register for all post types: existing links may also belong to posts or CPTs.
     * Core only creates revisions for post types that support them.
     *
     * @return void
     */
    public function register_related_product_meta()
    {
        register_post_meta('', ppcart_meta_key('related_product'), [
            'type'              => 'integer',
            'single'            => true,
            'label'             => __('Related Product', 'publishpress-cart'),
            'description'       => __('The product whose checkout rules apply to this content.', 'publishpress-cart'),
            'sanitize_callback' => 'absint',
            'auth_callback'     => [$this, 'can_edit_related_product_meta'],
            'show_in_rest'      => true,
            'revisions_enabled' => true,
        ]);
    }

    /**
     * @param bool   $allowed  Whether editing the metadata is allowed.
     * @param string $meta_key Metadata key.
     * @param int    $post_id  Post being edited.
     * @return bool
     */
    public function can_edit_related_product_meta($allowed, $meta_key, $post_id)
    {
        return current_user_can('edit_post', $post_id);
    }

    public function add_metaboxes()
    {

        add_meta_box(
            'ppcart-order-notes',
            apply_filters('ppcart-metabox-title-order-notes', esc_html__('Order Notes', 'publishpress-cart')),
            [$this, 'order_notes'],
            array_merge(ppcart_query_post_types('order'), ppcart_query_post_types('subscription')),
            'side',
            'default'
        );

        // The Related Product box needs the edited post, which admin_init does not have.
        add_action('add_meta_boxes', [$this, 'add_related_product_metabox'], 10, 2);
    }

    /**
     * Adds the Related Product box to pages, and to any other post that already links a product.
     *
     * @param string $post_type Post type of the edited post.
     * @param mixed  $post      The edited object. It is a WP_Post on post edit screens.
     * @return void
     */
    public function add_related_product_metabox($post_type, $post = null)
    {
        /**
         * Filters the post types that always show the Related Product box.
         *
         * The box links a post to a product. When the product's checkout closes,
         * the post redirects. Other posts show the box only when they already
         * link a product.
         *
         * @param string[] $post_types Post types. Default is page.
         */
        $post_types  = array_map('strval', (array) apply_filters('ppcart_related_product_post_types', ['page']));
        $has_product = $post instanceof WP_Post && intval(ppcart_get_post_meta($post->ID, 'related_product', true)) > 0;

        if (! in_array((string) $post_type, $post_types, true) && ! $has_product) {
            return;
        }

        // Match the published products offered by wp_dropdown_pages(). Keep an
        // existing link editable even when its product is no longer published.
        if (
            ! $has_product && ! get_posts([
                'post_type'      => ppcart_query_post_types('product'),
                'post_status'    => 'publish',
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'no_found_rows'  => true,
            ])
        ) {
            return;
        }

        add_meta_box(
            'ppcart-product',
            apply_filters('ppcart-metabox-title-access', esc_html(apply_filters('ppcart_plugin_title', __('PublishPress Cart', 'publishpress-cart')))),
            [$this, 'related_product'],
            (string) $post_type,
            'side',
            'default'
        );
    }

    public function related_product($post)
    {

        do_action('ppcart_page_metabox', $post);

        wp_nonce_field('ppcart_related_product', 'ppcart_related_product');
        $value = intval((ppcart_get_post_meta($post->ID, 'related_product', true)));

        echo '<p class="post-attributes-label-wrapper"><label class="post-attributes-label" for="ppcart-related-product">' . esc_html__('Related Product', 'publishpress-cart') . '</label>
        <br>' . esc_html__('When this product\'s checkout closes, visitors to this page are redirected.', 'publishpress-cart') . '</p>';
        // get_pages() takes one post type as a string; an array lists nothing.
        $dropdown = wp_dropdown_pages([
            'name'      => '_ppcart_related_product',
            'id'        => 'ppcart-related-product',
            'post_type' => sanitize_key(ppcart_live_post_type('product')),
            'show_option_none' => esc_html__('None', 'publishpress-cart'),
            'selected' => absint($value),
            'echo'     => 0,
        ]);

        echo wp_kses(
            (string) $dropdown,
            [
                'select' => [
                    'class' => true,
                    'id'    => true,
                    'name'  => true,
                ],
                'option' => [
                    'class'    => true,
                    'selected' => true,
                    'value'    => true,
                ],
            ]
        );
    }

    public function save_access_info($post_id)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/order-metabox-save-access-info.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function order_notes($object)
    {
        $log_entries = ppcart_order_log($object->ID);
        $entries = '';
        if (empty($log_entries)) {
            $entries .= __('No notes found', 'publishpress-cart');
        } else {
            foreach ($log_entries as $time => $entry) {
                $time = explode(' - ', $time);
                $time = $time[0];
                $entries .= wp_date('Y-m-d g:i a', (int) $time) . ' - ' . wp_strip_all_tags((string) $entry);
                $entries .= "\r\n";
                $entries .= '------------------------------';
                $entries .= "\r\n";
            }
        }
        ?>
        <div>
            <textarea id="ppcart-order-notes" readonly><?php echo esc_textarea($entries); ?></textarea>
        </div>
<?php
    }
}
