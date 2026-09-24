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
            apply_filters($this->plugin_name . '-metabox-title-order-notes', esc_html__('Order Notes', 'publishpress-cart')),
            [$this, 'order_notes'],
            array_merge(ppcart_query_post_types('order'), ppcart_query_post_types('subscription')),
            'side',
            'default'
        );

        add_meta_box(
            'ppcart-product',
            apply_filters($this->plugin_name . '-metabox-title-access', esc_html(apply_filters('ppcart_plugin_title', __('PublishPress Cart', 'publishpress-cart')))),
            [$this, 'related_product'],
            ['page', 'post'],
            'side',
            'default'
        );
    }

    public function related_product($post)
    {

        do_action('ppcart_page_metabox', $post);

        wp_nonce_field('ppcart_related_product', 'ppcart_related_product');
        $value = intval((ppcart_get_post_meta($post->ID, 'related_product', true)));

        echo '<p class="post-attributes-label-wrapper"><label class="post-attributes-label" for="ppcart-product">' . esc_html__('Related Product', 'publishpress-cart') . '</label>
        <br>' . esc_html__('Apply a product\'s access rules to this page.', 'publishpress-cart') . '</p>';
        wp_dropdown_pages([
            'name'      => '_ppcart_related_product',
            'id'        => 'ppcart-product',
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_dropdown_pages() expects raw registered post type slugs, not HTML output.
            'post_type' => ppcart_query_post_types('product'),
            'show_option_none' => esc_html__('None', 'publishpress-cart'),
            'selected' => absint($value),
        ]);
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
