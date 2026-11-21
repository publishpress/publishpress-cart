<?php

defined('ABSPATH') || exit;

/**
 * Cart > Subscriptions > Import from Stripe.
 *
 * Creates Cart subscriptions for Stripe subscriptions that have no Cart
 * record, then imports their paid invoices as orders.
 */
class PPCart_Admin_Stripe_Import
{
    public const PAGE = 'ppcart-import-stripe-subscriptions';

    /**
     * Max subscriptions per submit, so one request stays within PHP limits.
     */
    private const MAX_IDS = 20;

    public static function register_page()
    {
        $title = __('Import Subscriptions from Stripe', 'publishpress-cart');
        $hook = add_submenu_page(PPCart_Admin_Screens::menu_slug(), $title, $title, ppcart_live_cap('manager_option'), self::PAGE, [ self::class, 'render' ]);
        if ($hook) {
            // Reached from the Subscriptions list, not the menu.
            add_action(
                'admin_head',
                static function () {
                    remove_submenu_page(PPCart_Admin_Screens::menu_slug(), self::PAGE);
                }
            );
        }
    }

    /**
     * Show an "Import from Stripe" button above the Subscriptions list.
     *
     * @param string $which Top or bottom table navigation.
     */
    public static function list_button($which)
    {
        global $typenow;

        if ('top' !== $which || ! ppcart_is_subscription_post_type((string) $typenow) || ! ppcart_user_can('manager_option')) {
            return;
        }

        printf(
            '<div class="alignleft actions"><a class="button" href="%s">%s</a></div>',
            esc_url(admin_url('admin.php?page=' . self::PAGE)),
            esc_html__('Import from Stripe', 'publishpress-cart')
        );
    }

    /**
     * @return array<int, array{id: string, result: PPCart_Subscription|WP_Error}>
     */
    private static function handle_submit()
    {
        if (! isset($_POST['ppcart_import_stripe_nonce'])) {
            return [];
        }

        check_admin_referer('ppcart_import_stripe', 'ppcart_import_stripe_nonce');

        $raw = isset($_POST['stripe_subscription_ids']) ? sanitize_textarea_field(wp_unslash($_POST['stripe_subscription_ids'])) : '';
        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;

        $ids = array_values(array_unique(array_filter(array_map('trim', preg_split('/[\s,]+/', $raw)))));
        $ids = array_slice($ids, 0, self::MAX_IDS);

        $results = [];
        foreach ($ids as $id) {
            $results[] = [
                'id'     => $id,
                'result' => PPCart_Stripe_Sync::import_subscription($id, [ 'product_id' => $product_id ]),
            ];
        }

        return $results;
    }

    public static function render()
    {
        if (! ppcart_user_can('manager_option')) {
            wp_die(esc_html__('Sorry, you are not allowed to access this page.', 'publishpress-cart'));
        }

        $results = self::handle_submit();
        $products = get_posts(
            [
                'post_type'      => ppcart_query_post_types('product'),
                'post_status'    => [ 'publish', 'private', 'draft' ],
                'posts_per_page' => 500,
                'orderby'        => 'title',
                'order'          => 'ASC',
            ]
        );
        $list_url = admin_url('edit.php?post_type=' . ppcart_live_post_type('subscription'));
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Import Subscriptions from Stripe', 'publishpress-cart'); ?></h1>
            <p><?php esc_html_e('Add Stripe subscriptions that are missing from Cart. Each one becomes a Cart subscription, and its paid invoices become orders. Integrations don\'t run, and nothing changes in Stripe.', 'publishpress-cart'); ?></p>

            <?php if ($results) : ?>
                <table class="widefat striped" style="max-width: 900px; margin-bottom: 20px;">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Stripe subscription', 'publishpress-cart'); ?></th>
                            <th><?php esc_html_e('Result', 'publishpress-cart'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $row) : ?>
                            <tr>
                                <td><code><?php echo esc_html($row['id']); ?></code></td>
                                <td>
                                    <?php if (is_wp_error($row['result'])) : ?>
                                        <?php echo esc_html($row['result']->get_error_message()); ?>
                                    <?php else : ?>
                                        <?php
                                        $sub = $row['result'];
                                        printf(
                                            /* translators: 1: link to subscription, 2: number of orders */
                                            esc_html__('Imported as %1$s with %2$d orders.', 'publishpress-cart'),
                                            '<a href="' . esc_url(get_edit_post_link($sub->id)) . '">#' . (int) $sub->id . '</a>', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped parts.
                                            (int) $sub->order_count()
                                        );
                                        ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <form method="post">
                <?php wp_nonce_field('ppcart_import_stripe', 'ppcart_import_stripe_nonce'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="ppcart-stripe-subscription-ids"><?php esc_html_e('Stripe subscription IDs', 'publishpress-cart'); ?></label></th>
                        <td>
                            <textarea id="ppcart-stripe-subscription-ids" name="stripe_subscription_ids" rows="6" class="large-text code" placeholder="sub_..."></textarea>
                            <p class="description">
                                <?php
                                /* translators: %d: maximum number of subscriptions */
                                printf(esc_html__('One per line, up to %d at a time.', 'publishpress-cart'), (int) self::MAX_IDS);
                                ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ppcart-import-product"><?php esc_html_e('Product', 'publishpress-cart'); ?></label></th>
                        <td>
                            <select id="ppcart-import-product" name="product_id">
                                <option value="0"><?php esc_html_e('Find it from the Stripe price', 'publishpress-cart'); ?></option>
                                <?php foreach ($products as $product) : ?>
                                    <option value="<?php echo (int) $product->ID; ?>"><?php echo esc_html(get_the_title($product) . ' (#' . $product->ID . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php esc_html_e('Choose a product when no Cart product sells the subscription\'s Stripe price.', 'publishpress-cart'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Import', 'publishpress-cart')); ?>
            </form>
            <p><a href="<?php echo esc_url($list_url); ?>">&larr; <?php esc_html_e('Back to Subscriptions', 'publishpress-cart'); ?></a></p>
        </div>
        <?php
    }
}
