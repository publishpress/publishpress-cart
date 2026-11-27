<?php

defined('ABSPATH') || exit;

/**
 * Cart > Settings > Maintenance: the "Import Stripe subscriptions" and
 * "Import a Stripe payment" cards.
 *
 * Creates Cart subscriptions for Stripe subscriptions that have no Cart
 * record, then imports their paid invoices as orders. Also imports one
 * Stripe payment as an order.
 */
class PPCart_Admin_Stripe_Import
{
    public const AJAX_ACTION = 'ppcart_import_stripe_subscriptions';

    public const CHARGE_AJAX_ACTION = 'ppcart_import_stripe_charge';

    /**
     * Max subscriptions per request, so one request stays within PHP limits.
     */
    private const MAX_IDS = 20;

    /**
     * Show the subscription import card in the Settings > Maintenance tab.
     *
     * The settings page is one form, so these controls have no name and
     * don't save with it. The Import button sends them with AJAX.
     */
    public static function render_maintenance_card()
    {
        ?>
        <p class="ppcart-settings__card-desc"><?php esc_html_e('Add Stripe subscriptions that are missing from Cart. Each one becomes a Cart subscription, and its paid invoices become orders. Nothing changes in Stripe, and integrations don\'t run.', 'publishpress-cart'); ?></p>
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
                            <?php foreach (self::get_products() as $product) : ?>
                                <option value="<?php echo (int) $product->ID; ?>"><?php echo esc_html(get_the_title($product) . ' (#' . $product->ID . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description" id="ppcart-stripe-import-product-desc"><?php esc_html_e('Choose a product when no Cart product sells the subscription\'s Stripe price.', 'publishpress-cart'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Import subscriptions', 'publishpress-cart'); ?></th>
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
                        ><?php esc_html_e('Import subscriptions', 'publishpress-cart'); ?></button>
                        <div class="ppcart-stripe-import__result" data-ppcart-stripe-import-result aria-live="polite" hidden></div>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    /**
     * Show the payment import card in the Settings > Maintenance tab.
     *
     * A payment becomes either an order of a Cart subscription or a one-time
     * order of a product. Only the field for the chosen one shows.
     */
    public static function render_payment_card()
    {
        $products = array_filter(self::get_products(), static fn ($product) => 'publish' === $product->post_status);
        ?>
        <p class="ppcart-settings__card-desc"><?php esc_html_e('Add a Stripe payment that has no invoice, such as a charge made in the Stripe Dashboard. Nothing changes in Stripe, and integrations don\'t run.', 'publishpress-cart'); ?></p>
        <div class="ppcart-stripe-import" data-ppcart-stripe-charge-import>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="ppcart-stripe-import-charge"><?php esc_html_e('Stripe payment ID', 'publishpress-cart'); ?></label></th>
                    <td>
                        <input type="text" id="ppcart-stripe-import-charge" class="regular-text code" placeholder="ch_... / pi_..." data-ppcart-stripe-charge-import-id data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-maintenance-stripe-charge-import-id')); ?>" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Add it as', 'publishpress-cart'); ?></th>
                    <td>
                        <fieldset class="ppcart-stripe-import__choice">
                            <legend class="screen-reader-text"><?php esc_html_e('Add it as', 'publishpress-cart'); ?></legend>
                            <label>
                                <input type="radio" name="ppcart_stripe_charge_import_mode" value="subscription" checked data-ppcart-stripe-charge-import-mode data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-maintenance-stripe-charge-import-mode-subscription')); ?>" />
                                <?php esc_html_e('An order of a Cart subscription', 'publishpress-cart'); ?>
                            </label>
                            <label>
                                <input type="radio" name="ppcart_stripe_charge_import_mode" value="product" data-ppcart-stripe-charge-import-mode data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-maintenance-stripe-charge-import-mode-product')); ?>" />
                                <?php esc_html_e('A one-time order of a product', 'publishpress-cart'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                <tr data-ppcart-stripe-charge-import-target="subscription">
                    <th scope="row"><label for="ppcart-stripe-import-charge-subscription"><?php esc_html_e('Subscription number', 'publishpress-cart'); ?></label></th>
                    <td>
                        <input type="text" inputmode="numeric" autocomplete="off" id="ppcart-stripe-import-charge-subscription" class="regular-text" placeholder="1234" aria-describedby="ppcart-stripe-import-charge-subscription-desc" data-ppcart-stripe-charge-import-subscription data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-maintenance-stripe-charge-import-subscription')); ?>" />
                        <p class="description" id="ppcart-stripe-import-charge-subscription-desc"><?php esc_html_e('The payment must belong to the subscription\'s Stripe customer.', 'publishpress-cart'); ?></p>
                    </td>
                </tr>
                <tr data-ppcart-stripe-charge-import-target="product" hidden>
                    <th scope="row"><label for="ppcart-stripe-import-charge-product"><?php esc_html_e('Product', 'publishpress-cart'); ?></label></th>
                    <td>
                        <select id="ppcart-stripe-import-charge-product" data-ppcart-stripe-charge-import-product data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-maintenance-stripe-charge-import-product')); ?>">
                            <option value="0"><?php esc_html_e('Choose a product', 'publishpress-cart'); ?></option>
                            <?php foreach ($products as $product) : ?>
                                <option value="<?php echo (int) $product->ID; ?>"><?php echo esc_html(get_the_title($product) . ' (#' . $product->ID . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Import payment', 'publishpress-cart'); ?></th>
                    <td>
                        <button
                            type="button"
                            class="button button-secondary"
                            data-ppcart-stripe-charge-import-run
                            data-nonce="<?php echo esc_attr(wp_create_nonce(self::CHARGE_AJAX_ACTION)); ?>"
                            data-empty-text="<?php esc_attr_e('Enter a Stripe payment ID.', 'publishpress-cart'); ?>"
                            data-no-product-text="<?php esc_attr_e('Choose a product.', 'publishpress-cart'); ?>"
                            data-invalid-subscription-text="<?php esc_attr_e('Enter the Cart subscription number, for example 1234.', 'publishpress-cart'); ?>"
                            data-running-text="<?php esc_attr_e('Importing the payment.', 'publishpress-cart'); ?>"
                            data-failed-text="<?php esc_attr_e('The import failed. Try again.', 'publishpress-cart'); ?>"
                            data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-maintenance-stripe-charge-import')); ?>"
                        ><?php esc_html_e('Import payment', 'publishpress-cart'); ?></button>
                        <div class="ppcart-stripe-import__result" data-ppcart-stripe-charge-import-result aria-live="polite" hidden></div>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    /**
     * Products for the import forms.
     *
     * @return WP_Post[]
     */
    private static function get_products()
    {
        return get_posts(
            [
                'post_type'      => ppcart_query_post_types('product'),
                'post_status'    => [ 'publish', 'private', 'draft' ],
                // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- The product list for the import form; stores rarely have this many products.
                'posts_per_page' => 500,
                'orderby'        => 'title',
                'order'          => 'ASC',
            ]
        );
    }

    /**
     * AJAX: import one Stripe payment as an order of a subscription, or as
     * a one-time order of a product.
     *
     * @return void
     */
    public static function ajax_import_charge()
    {
        ppcart_check_ajax_referer(self::CHARGE_AJAX_ACTION, 'nonce');

        if (! ppcart_user_can('manager_option')) {
            wp_send_json_error([ 'message' => __('You are not allowed to import payments.', 'publishpress-cart') ], 403);
        }

        $payment_id = isset($_POST['payment_id']) ? sanitize_text_field(wp_unslash($_POST['payment_id'])) : '';
        $subscription_input = isset($_POST['subscription_id']) ? sanitize_text_field(wp_unslash($_POST['subscription_id'])) : '';
        $subscription_id = self::parse_subscription_number($subscription_input);
        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        if ('' === trim($payment_id)) {
            wp_send_json_error([ 'message' => __('Enter a Stripe payment ID.', 'publishpress-cart') ]);
        }
        if ('' !== trim($subscription_input) && ! $subscription_id) {
            wp_send_json_error([ 'message' => __('Enter the Cart subscription number, for example 1234.', 'publishpress-cart') ]);
        }

        // import_charge() refuses both or neither of a subscription and a product.
        $result = PPCart_Stripe_Sync::import_charge($payment_id, $subscription_id, $product_id);
        if (is_wp_error($result)) {
            $data = $result->get_error_data();
            $order_id = is_array($data) && ! empty($data['order_id']) ? (int) $data['order_id'] : 0;
            wp_send_json_error(
                [
                    'message' => $result->get_error_message(),
                    'url'     => $order_id ? (string) get_edit_post_link($order_id, 'raw') : '',
                    'label'   => $order_id ? '#' . $order_id : '',
                ]
            );
        }

        wp_send_json_success(
            [
                'url'     => (string) get_edit_post_link($result->id, 'raw'),
                'label'   => '#' . (int) $result->id,
                'message' => $subscription_id
                    /* translators: %d: subscription ID */
                    ? sprintf(__('Imported as an order of subscription #%d.', 'publishpress-cart'), (int) $subscription_id)
                    : __('Imported as a one-time order.', 'publishpress-cart'),
            ]
        );
    }

    /**
     * Read a Cart subscription number as a merchant types it.
     *
     * Accepts spaces and a leading "#", so "#4762" and " 4762 " both give
     * 4762. Anything else that isn't a whole number gives 0.
     *
     * @param string $value Subscription number as entered.
     * @return int Subscription ID, or 0.
     */
    public static function parse_subscription_number($value)
    {
        $value = preg_replace('/\s+/u', '', (string) $value);
        $value = ltrim((string) $value, '#');

        return ctype_digit($value) ? absint($value) : 0;
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
