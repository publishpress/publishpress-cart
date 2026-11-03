<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

require_once __DIR__ . '/trait-ppcart-admin-order-metabox-rendering.php';

trait PPCart_Admin_Order_Metabox_Trait
{
    use PPCart_Admin_Order_Metabox_Rendering_Trait;

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
