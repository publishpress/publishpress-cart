<?php

defined('ABSPATH') || exit;

/**
 * Cart > Settings > Maintenance > Import Subscriptions from Stripe.
 *
 * Creates Cart subscriptions for Stripe subscriptions that have no Cart
 * record, then imports their paid invoices as orders.
 */
class PPCart_Admin_Stripe_Import
{
    public const AJAX_ACTION = 'ppcart_import_stripe_subscriptions';

    /**
     * Max subscriptions per request, so one request stays within PHP limits.
     */
    private const MAX_IDS = 20;

    /**
     * Show the import form in the Settings > Maintenance tab.
     *
     * The settings page is one form, so these controls have no name and
     * don't save with it. The Import button sends them with AJAX.
     */
    public static function render_maintenance_card()
    {
        $products = get_posts(
            [
                'post_type'      => ppcart_query_post_types('product'),
                'post_status'    => [ 'publish', 'private', 'draft' ],
                // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- The product list for the import form; stores rarely have this many products.
                'posts_per_page' => 500,
                'orderby'        => 'title',
                'order'          => 'ASC',
            ]
        );
        ?>
        <p class="ppcart-settings__card-desc"><?php esc_html_e('Add Stripe subscriptions that are missing from Cart. Each one becomes a Cart subscription, and its paid invoices become orders. Integrations don\'t run, and nothing changes in Stripe.', 'publishpress-cart'); ?></p>
        <div class="ppcart-stripe-import" data-ppcart-stripe-import>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="ppcart-stripe-import-ids"><?php esc_html_e('Stripe subscription IDs', 'publishpress-cart'); ?></label></th>
                    <td>
                        <textarea id="ppcart-stripe-import-ids" rows="4" class="large-text code" placeholder="sub_..." aria-describedby="ppcart-stripe-import-ids-desc" data-ppcart-stripe-import-ids data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-maintenance-stripe-import-ids')); ?>"></textarea>
                        <p class="description" id="ppcart-stripe-import-ids-desc">
                            <?php
                            /* translators: %d: maximum number of subscriptions */
                            echo esc_html(sprintf(__('One per line, up to %d at a time.', 'publishpress-cart'), (int) self::MAX_IDS));
                            ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="ppcart-stripe-import-product"><?php esc_html_e('Product', 'publishpress-cart'); ?></label></th>
                    <td>
                        <select id="ppcart-stripe-import-product" aria-describedby="ppcart-stripe-import-product-desc" data-ppcart-stripe-import-product data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-maintenance-stripe-import-product')); ?>">
                            <option value="0"><?php esc_html_e('Find it from the Stripe price', 'publishpress-cart'); ?></option>
                            <?php foreach ($products as $product) : ?>
                                <option value="<?php echo (int) $product->ID; ?>"><?php echo esc_html(get_the_title($product) . ' (#' . $product->ID . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description" id="ppcart-stripe-import-product-desc"><?php esc_html_e('Choose a product when no Cart product sells the subscription\'s Stripe price.', 'publishpress-cart'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Import', 'publishpress-cart'); ?></th>
                    <td>
                        <button
                            type="button"
                            class="button button-secondary"
                            data-ppcart-stripe-import-run
                            data-nonce="<?php echo esc_attr(wp_create_nonce(self::AJAX_ACTION)); ?>"
                            data-empty-text="<?php esc_attr_e('Enter at least one Stripe subscription ID.', 'publishpress-cart'); ?>"
                            data-running-text="<?php esc_attr_e('Importing. This can take a minute for subscriptions with many payments.', 'publishpress-cart'); ?>"
                            data-failed-text="<?php esc_attr_e('The import failed. Try again with fewer subscriptions.', 'publishpress-cart'); ?>"
                            data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-maintenance-stripe-import')); ?>"
                        ><?php esc_html_e('Import from Stripe', 'publishpress-cart'); ?></button>
                        <div class="ppcart-stripe-import__result" data-ppcart-stripe-import-result aria-live="polite" hidden></div>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    /**
     * AJAX: import the posted Stripe subscription IDs.
     *
     * @return void
     */
    public static function ajax_import()
    {
        ppcart_check_ajax_referer(self::AJAX_ACTION, 'nonce');

        if (! ppcart_user_can('manager_option')) {
            wp_send_json_error([ 'message' => __('You are not allowed to import subscriptions.', 'publishpress-cart') ], 403);
        }

        $raw = isset($_POST['ids']) ? sanitize_textarea_field(wp_unslash($_POST['ids'])) : '';
        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;

        $ids = array_values(array_unique(array_filter(array_map('trim', preg_split('/[\s,]+/', $raw)))));
        $ids = array_slice($ids, 0, self::MAX_IDS);
        if (! $ids) {
            wp_send_json_error([ 'message' => __('Enter at least one Stripe subscription ID.', 'publishpress-cart') ]);
        }

        $results = [];
        foreach ($ids as $id) {
            $result = PPCart_Stripe_Sync::import_subscription($id, [ 'product_id' => $product_id ]);

            if (is_wp_error($result)) {
                $results[] = [
                    'id'      => $id,
                    'success' => false,
                    'message' => $result->get_error_message(),
                ];
                continue;
            }

            $order_count = (int) $result->order_count();
            $results[] = [
                'id'      => $id,
                'success' => true,
                'url'     => (string) get_edit_post_link($result->id, 'raw'),
                'label'   => '#' . (int) $result->id,
                'message' => sprintf(
                    /* translators: %d: number of orders */
                    _n('Imported with %d order.', 'Imported with %d orders.', $order_count, 'publishpress-cart'),
                    $order_count
                ),
            ];
        }

        wp_send_json_success([ 'results' => $results ]);
    }
}
