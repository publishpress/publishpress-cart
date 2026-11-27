<?php

defined('ABSPATH') || exit;

/**
 * Cart > Settings > Maintenance > Stripe fees.
 *
 * Fetches Stripe fees for existing orders in small batches. It sends GET
 * requests only, so nothing changes in Stripe. Orders that already have fee
 * data are skipped, so a stopped run can start again.
 */
class PPCart_Admin_Stripe_Fees
{
    public const AJAX_ACTION = 'ppcart_fetch_stripe_fees';

    /**
     * Orders per request, so one request stays within PHP and Stripe limits.
     */
    private const BATCH = 10;

    /**
     * @return void
     */
    public static function render_maintenance_card()
    {
        $pending = PPCart_Stripe_Fees::find_orders_without_fees(1)['total'];
        /* translators: 1: number of orders done, 2: number of orders that failed. */
        $running_text = __('Fetching fees: %1$s done, %2$s failed.', 'publishpress-cart');
        /* translators: 1: number of orders done, 2: number of orders that failed. */
        $done_text = __('Finished: %1$s done, %2$s failed.', 'publishpress-cart');
        ?>
        <p class="ppcart-settings__card-desc"><?php esc_html_e('Get the Stripe processing fee and net amount for Stripe orders that do not have them yet. New orders get their fees automatically. Nothing changes in Stripe.', 'publishpress-cart'); ?></p>
        <div class="ppcart-stripe-import" data-ppcart-stripe-fees>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Fetch fees', 'publishpress-cart'); ?></th>
                    <td>
                        <button
                            type="button"
                            class="button button-secondary"
                            data-ppcart-stripe-fees-run
                            data-nonce="<?php echo esc_attr(wp_create_nonce(self::AJAX_ACTION)); ?>"
                            data-running-text="<?php echo esc_attr($running_text); ?>"
                            data-done-text="<?php echo esc_attr($done_text); ?>"
                            data-failed-text="<?php esc_attr_e('The request failed. Click the button again to continue.', 'publishpress-cart'); ?>"
                            data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-maintenance-stripe-fees')); ?>"
                            <?php disabled(0, $pending); ?>
                        ><?php esc_html_e('Fetch Stripe fees', 'publishpress-cart'); ?></button>
                        <p class="description" data-ppcart-stripe-fees-pending>
                            <?php
                            echo esc_html(
                                sprintf(
                                    /* translators: %s: number of orders. */
                                    _n('%s Stripe order has no fee data.', '%s Stripe orders have no fee data.', $pending, 'publishpress-cart'),
                                    number_format_i18n($pending)
                                )
                            );
                            ?>
                        </p>
                        <div class="ppcart-stripe-import__result" data-ppcart-stripe-fees-result aria-live="polite" hidden></div>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    /**
     * AJAX: fetch fees for the next batch of orders.
     *
     * The browser sends the number of orders that failed so far. Those orders
     * still have no fee data, so the query skips them.
     *
     * @return void
     */
    public static function ajax_fetch()
    {
        ppcart_check_ajax_referer(self::AJAX_ACTION, 'nonce');

        if (! ppcart_user_can('manager_option')) {
            wp_send_json_error([ 'message' => __('You are not allowed to fetch Stripe fees.', 'publishpress-cart') ], 403);
        }

        $offset = isset($_POST['failed']) ? absint($_POST['failed']) : 0;
        $batch  = PPCart_Stripe_Fees::find_orders_without_fees(self::BATCH, $offset);

        $done   = 0;
        $errors = [];
        foreach ($batch['ids'] as $order_id) {
            $result = PPCart_Stripe_Fees::fetch($order_id);
            if (is_wp_error($result)) {
                $errors[] = [
                    'label'   => '#' . $order_id,
                    'url'     => (string) get_edit_post_link($order_id, 'raw'),
                    'message' => $result->get_error_message(),
                ];
                continue;
            }

            $done++;
        }

        wp_send_json_success(
            [
                'done'     => $done,
                'failed'   => count($errors),
                'errors'   => $errors,
                'finished' => count($batch['ids']) < self::BATCH,
            ]
        );
    }
}
