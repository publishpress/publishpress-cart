<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Order_Metabox_Field_Groups_Trait
{
    /**
     * Returns an array of the all the metabox fields and their respective types
     *
     * @since 1.0.0
     * @access public
     * @return      array       Metabox fields and types
     */
    private function get_metabox_fields($post_type)
    {

        $this->set_field_groups(true);

        $fields = [];
        $groups = (ppcart_is_order_post_type($post_type)) ? ['general'] : ['sub_fields'];

        foreach ($groups as $id) {
            foreach ($this->$id as $group) {
                $type = (isset($group['field-type'])) ? $group['field-type'] : $group['type'];
                $set = [$group['id'], $type];
                $fields[] = $set;
            }
        }
        return $fields;
    }

    public function order_detail_fields($post, $params)
    {

        if (! is_admin()) {
            return;
        }

        if (! ppcart_is_order_post_type($post->post_type)) {
            return;
        }

        echo '<div class="ppcart-settings-tabs ppcart-edit-panel">';

        wp_nonce_field($this->plugin_name, 'ppcart_fields_nonce');

        echo '<div class="ppcart-edit-grid ppcart-edit-grid--order">';
        $this->metabox_fields($this->get_order_edit_fields($post), $post->post_type);
        echo '</div>';
        $this->edit_save_bar();

        echo '</div>';
    }

    public function sub_detail_fields($post, $params)
    {

        if (! is_admin()) {
            return;
        }

        if (! ppcart_is_subscription_post_type($post->post_type)) {
            return;
        }

        echo '<div class="ppcart-settings-tabs ppcart-edit-panel">';

        wp_nonce_field($this->plugin_name, 'ppcart_fields_nonce');

        echo '<div class="ppcart-edit-grid ppcart-edit-grid--subscription">';
        $this->metabox_fields($this->get_subscription_edit_fields(), $post->post_type);
        $this->render_subscription_edit_details_section($post);
        echo '</div>';
        $this->edit_save_bar();

        echo '</div>';
    }

    private function edit_save_bar()
    {
        ?>
        <div class="ppcart-edit-savebar">
            <strong><?php esc_html_e('Unsaved changes', 'publishpress-cart'); ?></strong>
            <div class="ppcart-edit-savebar__actions">
                <button type="button" class="button ppcart-edit-cancel"><?php esc_html_e('Cancel', 'publishpress-cart'); ?></button>
                <button type="submit" name="save" class="button button-primary"><?php esc_html_e('Save changes', 'publishpress-cart'); ?></button>
            </div>
        </div>
<?php
    }

    private function set_field_groups($save = false)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/order-metabox-field-groups-set-field-groups.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    // Get Cart products.
    private function get_products()
    {
        $options = ['' => 'Select Product'];
        $args = [
            // phpcs:ignore WordPressVIPMinimum.Performance.NoPaging.posts_per_page_numberposts -- This admin selector intentionally loads all matching records for option lists.
            'numberposts'   => -1,
            'post_type'     => ppcart_query_post_types('product'),
            'orderby'       => 'title',
            'order'         => 'ASC',
            'post_status'   => 'publish',
        ];
        // Get the posts
        $myProducts = get_posts($args);
        if ($myProducts) :
            foreach ($myProducts as $product) {
                $options[$product->ID] = get_the_title($product->ID);
            }
            wp_reset_postdata();
        endif;
        return $options;
    }

    // Get products and payment options.
    private function get_products_payment()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/order-metabox-field-groups-get-ppcart-products-payment.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
