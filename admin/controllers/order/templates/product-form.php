<?php

if (! defined('ABSPATH')) {
    exit;
}



global $ppcart_currency_symbol;

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin screen context check.
if (! ppcart_is_order_post_type($post->post_type) || !isset($_GET['post'])) {
    return;
}

$ppcart_order = new PPCart_Order($post->ID);
$ppcart_order = apply_filters('ppcart_order', $ppcart_order);
$order_data = (object) $ppcart_order->get_data();
$product_id = $order_data->product_id;
$list = ppcart_get_order_items($ppcart_order, true, true);
$has_item_thumbnail = false;

foreach ($list as $line_item) {
    if (! empty($line_item['product_id']) && has_post_thumbnail((int) $line_item['product_id'])) {
        $has_item_thumbnail = true;
        break;
    }
}

?>
<div class="ppcart-product-info ppcart-product-table meta-box-sortables ui-sortable">
    <table cellpadding="0" cellspacing="0" class="ppcart-order-items ppcart-order-line-items">
        <caption>Product Info</caption>
        <colgroup>
            <col class="ppcart-line-item-thumb-col<?php echo $has_item_thumbnail ? '' : ' ppcart-line-item-thumb-col--empty'; ?>">
            <col class="ppcart-line-item-name-col">
            <col class="ppcart-line-item-price-option-col">
            <col class="ppcart-line-item-type-col">
            <col class="ppcart-line-item-unit-price-col">
            <col class="ppcart-line-item-quantity-col">
            <col class="ppcart-line-item-total-col">
        </colgroup>
        <thead>
            <tr>
                <th id="Item" class="item" colspan="2"><?php esc_html_e('Item', 'publishpress-cart'); ?></th>
                <th id="PriceOption" class="ppcart-line-item-detail-heading"><?php esc_html_e('Plan Name', 'publishpress-cart'); ?></th>
                <th id="ItemType" class="ppcart-line-item-detail-heading"><?php esc_html_e('Item type', 'publishpress-cart'); ?></th>
                <th id="UnitPrice" class="ppcart-line-item-detail-heading"><?php esc_html_e('Unit price', 'publishpress-cart'); ?></th>
                <th id="Quantity" class="line_cost"><?php esc_html_e('Quantity', 'publishpress-cart'); ?></th>
                <th id="Total" class="line_cost"><?php esc_html_e('Total', 'publishpress-cart'); ?></th>
            </tr>
        </thead>
        <tbody id="order_line_items">

            <?php

            $related_order_context = apply_filters(
                'ppcart_admin_order_related_context',
                [
                    'show_related'   => false,
                    'item_name'      => (isset($order_data->item_name)) ? $order_data->item_name : '',
                    'parent_id'      => null,
                    'parent_prod_id' => null,
                ],
                $order_data
            );
$show_related = ! empty($related_order_context['show_related']);
$item_name = $related_order_context['item_name'] ?? '';
if (! empty($related_order_context['parent_id'])) {
    $parent_id = $related_order_context['parent_id'];
}
if (! empty($related_order_context['parent_prod_id'])) {
    $parent_prod_id = $related_order_context['parent_prod_id'];
}

foreach ($list as $item) :
    $product_id = $item['product_id'];
    $item_price_name = ! empty($item['price_name']) ? (string) $item['price_name'] : '';
    $item_type = ! empty($item['item_type']) ? (string) $item['item_type'] : 'main';
    $item_type_labels = [
        'main'      => __('Main product', 'publishpress-cart'),
        'line item' => __('Line item', 'publishpress-cart'),
        'bundled'   => __('Bundled item', 'publishpress-cart'),
    ];
    $item_type_labels = apply_filters('ppcart_admin_order_item_type_labels', $item_type_labels, $order_data, $item);
    $item_type_label = $item_type_labels[$item_type] ?? ucwords(str_replace(['-', '_'], ' ', $item_type));
    $item_unit_price = isset($item['unit_price']) && 'bundled' !== $item_type ? ppcart_format_price($item['unit_price']) : '';
    $item['subtotal'] = ppcart_format_price($item['subtotal']);

    if ($item_type == 'bundled') {
        $item['subtotal'] = $item['quantity'] = '';
    }

    ?>
                <tr class="item">

                    <?php
            if (has_post_thumbnail($product_id)) {
                echo '<td class="thumb">';
                echo '<div class="ppcart-order-item-thumbnail">';
                echo get_the_post_thumbnail($product_id, 'thumbnail');
                echo '</div>';
                echo '</td>';
            } else {
                echo '<td style="padding:0"></td>';
            }
    ?>

                    <td class="name">
                        <div class="ppcart-line-item">
                            <div class="ppcart-line-item__main">
                                <?php if ($item_type == 'bundled') : ?>
                                    <span class="ppcart-line-item__indent" aria-hidden="true">&rdsh;</span>
                                <?php endif; ?>
                                <span class="ppcart-line-item__id">
                                    <?php esc_html_e('Product ID: #', 'publishpress-cart'); ?><?php echo esc_html($product_id); ?>
                                </span>
                                <a href="<?php echo esc_url(ppcart_get_edit_post_url($product_id)); ?>" class="ppcart-order-item-name"><?php echo esc_html(get_the_title($product_id)); ?></a>
                            </div>
                        </div>
                    </td>

                    <td class="ppcart-line-item-detail">
                        <?php echo esc_html($item_price_name ? $item_price_name : __('Default', 'publishpress-cart')); ?>
                    </td>

                    <td class="ppcart-line-item-detail">
                        <?php echo esc_html($item_type_label); ?>
                    </td>

                    <td class="ppcart-line-item-detail">
                        <?php echo $item_unit_price ? wp_kses_post($item_unit_price) : '&mdash;'; ?>
                    </td>

                    <td class="item_cost" width="5%">
                        <div class="view">
                            <span class="ppcart-Price-amount amount">
                                <?php echo esc_html($item['quantity']); ?>
                            </span>
                        </div>
                    </td>

                    <td class="item_cost" width="1%">
                        <div class="view">
                            <span class="ppcart-Price-amount amount">
                                <?php echo wp_kses_post($item['subtotal']); ?>
                            </span>
                        </div>
                    </td>
                </tr>
<?php endforeach; ?>

            <?php if ($order_data->coupon_id && in_array($order_data->coupon['type'], ['cart-percent', 'cart-fixed'])) : ?>
                <tr class="item bump-item">
                    <td style="padding:0;"></td>
                    <td class="name" colspan="5">
                        <?php echo '<span class="badge">' . esc_html__('Coupon: ', 'publishpress-cart') . esc_html($order_data->coupon_id) . '<span>' ?>
                    </td>
                    <td class="item_cost" width="1%">
                        <div class="view">
                            <span class="ppcart-Price-amount amount">
                                -<?php ppcart_formatted_price($order_data->coupon['discount_amount']); ?>
                            </span>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>

            <?php if ($order_data->shipping_amount) : ?>
                <tr class="item items-total">
                    <td style="padding:0;"></td>
                    <td class="name" colspan="5">
                        <?php echo esc_html__('Shipping', 'publishpress-cart'); ?>
                    </td>
                    <td class="item_cost" width="1%">
                        <div class="view">
                            <span class="ppcart-Price-amount amount">
                                <?php ppcart_formatted_price($order_data->shipping_amount); ?>
                            </span>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>

            <?php if (isset($order_data->tax_data) && is_object($order_data->tax_data) && $order_data->tax_desc) :
                $redeem_tax = false;
                if (isset($order_data->tax_data->redeem_vat) && $order_data->tax_data->redeem_vat) :
                    $redeem_tax = true;
                    if ($order_data->tax_data->type != 'inclusive') :
                        $order_data->tax_amount = 0;
                        $order_data->tax_rate = "0%";
                    endif;
                endif; ?>
                <tr class="item items-total">
                    <td style="padding:0;"></td>
                    <td class="name" colspan="5">
                        <?php echo esc_html($order_data->tax_desc . ' (' . $order_data->tax_rate . ')'); ?>
                    </td>
                    <td class="item_cost" width="1%">
                        <div class="view">
                            <span class="ppcart-Price-amount amount">
                                <?php ppcart_formatted_price($order_data->tax_amount); ?>
                            </span>
                        </div>
                    </td>
                </tr>
                <?php if ($redeem_tax && $order_data->tax_data->type == 'inclusive') : ?>
                    <tr class="item">
                        <td style="padding:0;"></td>
                        <td class="name" colspan="5">
                            <?php echo esc_html(get_option('_ppcart_vat_reverse_charge', "VAT Reversal")); ?>
                        </td>
                        <td class="item_cost" width="2%">
                            <div class="view" style="display: block;width: 50px;">
                                - <?php ppcart_formatted_price($order_data->tax_amount); ?>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endif; ?>

            <tr class="item items-total" style="font-weight: bold">
                <td style="padding:0;"></td>
                <td class="name" colspan="5">
                    <?php esc_html_e('Total:', 'publishpress-cart') ?>
                </td>

                <td class="item_cost" width="1%">
                    <div class="view">
                        <span class="ppcart-Price-amount amount">
                            <?php ppcart_formatted_price($order_data->amount); ?>
                        </span>
                    </div>
                </td>
            </tr>

            <?php
            $show_refund_modal = false;
$show_refunds_section = false;
$refund_modal_total = 0;
$refund_entries = [];
$refund_count = 0;
$refunded_total = 0;
$remaining_amount = 0;
$right_currency = in_array(get_option('_ppcart_currency_position'), ['right', 'right-space'], true);
$ppcart_payment_intent = '';
$ppcart_payment_method = '';

if (in_array(PPCart_Status_Labels::logical_from_post($post->ID), ['paid', 'refunded'], true)) {
    $refund_logs_entrie = $order_data->refund_log;
    $refund_entries = is_array($refund_logs_entrie) ? $refund_logs_entrie : [];
    if (class_exists('PPCart_Stripe_Sync')) {
        $refund_entries = PPCart_Stripe_Sync::normalize_refund_log($refund_entries);
    }

    $order_amount = class_exists('PPCart_Order_Refunds') ? PPCart_Order_Refunds::parse_amount($order_data->amount) : (float) $order_data->amount;
    $refunded_total = class_exists('PPCart_Order_Refunds') ? PPCart_Order_Refunds::get_refund_total($post->ID, $refund_entries) : 0;

    if (! class_exists('PPCart_Order_Refunds') && ! empty($refund_entries)) {
        $refund_amount_values = array_map(
            'floatval',
            array_column($refund_entries, 'amount')
        );
        $refunded_total = array_sum($refund_amount_values);
    }

    $remaining_amount = max(0, $order_amount - min($refunded_total, $order_amount));
    $refund_count = count($refund_entries);

    if ($remaining_amount > 0) {
        $show_refund_modal = true;
        $refund_modal_total = $remaining_amount;
    }

    $show_refunds_section = $refund_count > 0 || $refunded_total > 0;

    $order_data->intent_id ??= '';
    $ppcart_payment_intent = apply_filters('ppcart_payment_intent', $order_data->intent_id, $order_data);
    $ppcart_payment_method = apply_filters('ppcart_payment_method', $order_data->pay_method, $order_data);
}
?>
        </tbody>
    </table>
    <?php if ($show_refund_modal) : ?>
        <div class="ppcart-refund-actions">
            <button type="button" id="ppcart_refund_items_btn" class="button refund-items" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-order-' . $post->ID . '-issue-refund')); ?>"><?php esc_html_e('Issue refund', 'publishpress-cart'); ?></button>
        </div>
    <?php endif; ?>
</div>
<?php if ($show_refunds_section) : ?>
    <section class="postbox ppcart-refunds-card">
        <header class="ppcart-refunds-card__header">
            <div class="ppcart-refunds-card__title">
                <h2><?php esc_html_e('Refunds', 'publishpress-cart'); ?></h2>
                <?php if ($refund_count > 0) : ?>
                    <span class="ppcart-refunds-card__count">
                        <?php
            printf(
                esc_html(
                    /* translators: %s: refund count. */
                    _n('%s refund', '%s refunds', $refund_count, 'publishpress-cart')
                ),
                esc_html(number_format_i18n($refund_count))
            );
                    ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="ppcart-refunds-card__summary">
                <span><?php esc_html_e('Total refunded:', 'publishpress-cart'); ?></span>
                <strong><?php echo wp_kses_post(ppcart_format_price($refunded_total)); ?></strong>
            </div>
        </header>
        <?php if ($refund_count > 0) : ?>
            <div class="ppcart-refunds-card__table-wrap">
                <table class="ppcart-refunds-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Refund ID', 'publishpress-cart'); ?></th>
                            <th><?php esc_html_e('Date', 'publishpress-cart'); ?></th>
                            <th><?php esc_html_e('Amount', 'publishpress-cart'); ?></th>
                            <th><?php esc_html_e('Status', 'publishpress-cart'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($refund_entries as $refund_entry_key => $value) :
                            $refund_entry_id = isset($value['refundID']) ? (string) $value['refundID'] : (is_string($refund_entry_key) ? $refund_entry_key : '');
                            $refund_entry_amount = isset($value['amount']) ? (class_exists('PPCart_Order_Refunds') ? PPCart_Order_Refunds::parse_amount($value['amount']) : (float) $value['amount']) : 0;
                            $refund_entry_date = false;
                            if (isset($value['date']) && '' !== $value['date']) {
                                $refund_entry_date = is_numeric($value['date']) ? (int) $value['date'] : strtotime((string) $value['date']);
                            }
                            $refund_entry_date_label = $refund_entry_date ? wp_date('M j, Y h:i a', $refund_entry_date) : __('Unknown date', 'publishpress-cart');
                            ?>
                            <tr>
                                <td data-label="<?php esc_attr_e('Refund ID', 'publishpress-cart'); ?>"><?php echo esc_html($refund_entry_id); ?></td>
                                <td data-label="<?php esc_attr_e('Date', 'publishpress-cart'); ?>"><?php echo esc_html($refund_entry_date_label); ?></td>
                                <td data-label="<?php esc_attr_e('Amount', 'publishpress-cart'); ?>"><span class="ppcart-refunds-table__amount"><?php echo wp_kses_post(ppcart_format_price($refund_entry_amount)); ?></span></td>
                                <td data-label="<?php esc_attr_e('Status', 'publishpress-cart'); ?>">
                                    <span class="ppcart-refund-status ppcart-refund-status--refunded"><?php esc_html_e('Refunded', 'publishpress-cart'); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else : ?>
            <div class="ppcart-refunds-empty">
                <?php esc_html_e('No refunds recorded for this order.', 'publishpress-cart'); ?>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>
<?php if ($show_refund_modal) : ?>
    <?php
    $refund_modal_id = 'ppcart-refund-modal-' . absint($post->ID);
    $refund_amount = ppcart_format_number($refund_modal_total);
    $refund_amount_label = wp_strip_all_tags(ppcart_format_price($refund_modal_total));
    $refund_button_template = sprintf(
        /* translators: %s: formatted refund amount. */
        esc_html__('Refund %s', 'publishpress-cart'),
        '{amount}'
    );
    $refund_currency_position = get_option('_ppcart_currency_position');
    $refund_decimal_number = get_option('_ppcart_decimal_number');
    $refund_decimal_number = ('0' === $refund_decimal_number || ! empty($refund_decimal_number)) ? intval($refund_decimal_number) : 2;
    $refund_decimal_separator = ! empty(get_option('_ppcart_decimal_separator')) ? get_option('_ppcart_decimal_separator') : '.';
    $refund_thousand_separator = ! empty(get_option('_ppcart_thousand_separator')) ? get_option('_ppcart_thousand_separator') : '';
    $restock_disabled = 'YES' === ppcart_get_post_meta(get_the_ID(), 'refund_restock', true);
    ?>
    <input type="hidden" name="ppcart_payment_intent" id="stripe_ppcart_payment_intent" value="<?php echo esc_attr($ppcart_payment_intent); ?>">
    <input type="hidden" id="ppcart_payment_method" name="ppcart_payment_method" value="<?php echo esc_attr($ppcart_payment_method); ?>">
    <div
        class="ppcart-order-modal ppcart-refund-modal"
        id="<?php echo esc_attr($refund_modal_id); ?>"
        role="dialog"
        aria-modal="true"
        aria-labelledby="<?php echo esc_attr($refund_modal_id . '-title'); ?>"
        aria-describedby="<?php echo esc_attr($refund_modal_id . '-description'); ?>"
        data-ppcart-refund-button-template="<?php echo esc_attr($refund_button_template); ?>"
        data-ppcart-refund-currency-symbol="<?php echo esc_attr($ppcart_currency_symbol); ?>"
        data-ppcart-refund-currency-position="<?php echo esc_attr($refund_currency_position); ?>"
        data-ppcart-refund-decimal-separator="<?php echo esc_attr($refund_decimal_separator); ?>"
        data-ppcart-refund-thousand-separator="<?php echo esc_attr($refund_thousand_separator); ?>"
        data-ppcart-refund-decimal-count="<?php echo esc_attr($refund_decimal_number); ?>"
        hidden>
        <div class="ppcart-order-modal__backdrop" data-ppcart-refund-close></div>
        <div class="ppcart-order-modal__dialog">
            <div class="ppcart-order-modal__header">
                <div>
                    <div class="ppcart-order-modal__eyebrow"><?php esc_html_e('Refund', 'publishpress-cart'); ?></div>
                    <h3 id="<?php echo esc_attr($refund_modal_id . '-title'); ?>"><?php esc_html_e('Issue refund', 'publishpress-cart'); ?></h3>
                </div>
                <button type="button" class="ppcart-order-modal__close" aria-label="<?php esc_attr_e('Close', 'publishpress-cart'); ?>" data-ppcart-refund-close data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-order-' . $post->ID . '-refund-close')); ?>">&times;</button>
            </div>
            <div class="ppcart-order-modal__body">
                <p id="<?php echo esc_attr($refund_modal_id . '-description'); ?>" class="ppcart-order-modal__description">
                    <?php esc_html_e('Refunds are processed using the payment method associated with this order.', 'publishpress-cart'); ?>
                </p>
                <div class="ppcart-refund-form">
                    <label class="ppcart-refund-field" for="ppcart_refund_amount">
                        <span><?php esc_html_e('Refund amount', 'publishpress-cart'); ?></span>
                        <span class="ppcart-refund-amount-control">
                            <?php if (! $right_currency) : ?>
                                <span class="ppcart-refund-currency"><?php echo esc_html($ppcart_currency_symbol); ?></span>
                            <?php endif; ?>
                            <input type="text" id="ppcart_refund_amount" name="ppcart_refund_amount" value="<?php echo esc_attr($refund_amount); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-order-' . $post->ID . '-refund-amount')); ?>">
                            <?php if ($right_currency) : ?>
                                <span class="ppcart-refund-currency"><?php echo esc_html($ppcart_currency_symbol); ?></span>
                            <?php endif; ?>
                        </span>
                    </label>
                    <label class="ppcart-refund-checkbox">
                        <input type="checkbox" value="YES" id="ppcart_restock_refunded" name="ppcart_restock_refunded" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-order-' . $post->ID . '-refund-restock')); ?>" <?php disabled($restock_disabled); ?>>
                        <span><?php esc_html_e('Restock refunded items', 'publishpress-cart'); ?></span>
                    </label>
                </div>
            </div>
            <div class="ppcart-order-modal__footer">
                <button type="button" class="button" data-ppcart-refund-close data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-order-' . $post->ID . '-refund-cancel')); ?>"><?php esc_html_e('Cancel', 'publishpress-cart'); ?></button>
                <button type="button" class="button button-primary ppcart-refund-submit ppcart_refund_btn" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-order-' . $post->ID . '-refund-submit')); ?>">
                    <?php
                    printf(
                        /* translators: %s: formatted refund amount. */
                        esc_html__('Refund %s', 'publishpress-cart'),
                        esc_html($refund_amount_label)
                    );
    ?>
                </button>
            </div>
        </div>
    </div>
<?php endif; ?>

<!--show if subscription order -->
<?php
$subID = $order_data->subscription_id ?? '';

if ($subID) {
    $show_related = true;
}

if ($show_related) {  ?>
    <div class="ppcart-product-info ppcart-product-table meta-box-sortables ui-sortable">
        <div class="postbox">
            <h2><?php esc_html_e('Related Orders', 'publishpress-cart'); ?></h2>
        </div>
        <table cellpadding="0" cellspacing="0" class="ppcart-order-items" width="100%">
            <thead>
                <tr>
                    <th class="item"><?php esc_html_e('Order Number', 'publishpress-cart'); ?></th>
                    <th class="item"><?php esc_html_e('Relationship', 'publishpress-cart'); ?></th>
                    <th class="item"><?php esc_html_e('Date', 'publishpress-cart'); ?></th>
                    <th class="item"><?php esc_html_e('Status', 'publishpress-cart'); ?></th>
                    <th class="line_cost"><?php esc_html_e('Total', 'publishpress-cart'); ?></th>
                </tr>
            </thead>
            <tbody id="order_line_items">

                <?php
                if (!empty($subID)) {
                    $sub = new PPCart_Subscription($subID);
                    ?>

                    <tr>
                        <td>
                            <a href="<?php echo esc_url(ppcart_get_edit_post_url($subID)); ?>" class="ppcart-order-item-name">#<?php echo esc_html($subID); ?></a>
                        </td>
                        <td><?php esc_html_e('Subscription', 'publishpress-cart'); ?></td>
                        <td><?php echo esc_html(get_the_date('F d, Y h:i a', $subID)); ?></td>
                        <td class="name">
                            <span class="ppcart-status <?php echo esc_attr($sub->status); ?>"><?php echo esc_html($sub->get_status()); ?></span>
                        </td>
                        <td class="sub_cost">
                            <div class="view">
                                <span class="ppcart-Price-amount amount">
                                    <?php ppcart_formatted_price(ppcart_get_post_meta($subID, 'sub_amount', true)); ?>
                                </span> /
                                <?php
                                    $frequency = ppcart_get_post_meta($subID, 'sub_frequency', true);
                    $interval = ppcart_get_post_meta($subID, 'sub_interval', true);
                    if ($frequency > 1) {
                        echo esc_html($frequency . ' ' . ppcart_pluralize_interval($interval));
                    } else {
                        echo esc_html($interval);
                    }
                    ?>
                            </div>
                        </td>
                    </tr>

                    <?php
                    // The Query
                    $args = [
                        'post_type' => ppcart_query_post_types('order'),
                        'orderby' => 'date',
                        'order'   => 'ASC',
                        // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- Required to exclude the currently viewed order.
                        'post__not_in' => (array) get_the_ID(),
                        // phpcs:ignore WordPressVIPMinimum.Performance.NoPaging.posts_per_page_posts_per_page -- This admin/report selector intentionally loads all matching records for aggregate calculations or option lists.
                        'posts_per_page' => -1,
                        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required to load related orders by subscription meta.
                        'meta_query' => [
                            [
                    'key' => ppcart_meta_key('subscription_id'),
                    'value' => $subID,
                            ],
                        ],
                    ];
                    $the_query = new WP_Query($args);
                    $initial_order = true;

                    // The Loop
                    if ($the_query->have_posts()) {
                        while ($the_query->have_posts()) {
                            $the_query->the_post();
                            $child_order = new PPCart_Order(get_the_ID());
                            ?>
                            <tr>
                                <td>
                                    <a href="<?php echo esc_url(get_edit_post_link(get_the_ID())); ?>" class="ppcart-order-item-name">#<?php echo esc_html(get_the_ID()); ?></a>
                                </td>
                                <td><?php echo esc_html($initial_order ? 'Initial Order' : 'Renewal Order'); ?></td>
                                <td><?php echo esc_html(get_the_date('F d, Y h:i a', get_the_ID())); ?></td>
                                <td class="name">
                                    <span class="ppcart-status <?php echo esc_attr($child_order->status); ?>"><?php echo esc_html($child_order->get_status()); ?></span>
                                </td>
                                <td class="sub_cost">
                                    <div class="view">
                                        <span class="ppcart-Price-amount amount">
                                            <?php ppcart_formatted_price($child_order->amount); ?>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                    <?php
                                    $initial_order = false;
                        }
                    }
                    /* Restore original Post Data */
                    wp_reset_postdata();
                }


    if (isset($parent_id) && $parent_id) :
        $parent = new PPCart_Order($parent_id); ?>
                    <tr>
                        <td>
                            <a href="<?php echo esc_url(ppcart_get_edit_post_url($parent_id)); ?>" class="ppcart-order-item-name">#<?php echo esc_html($parent_id); ?></a>
                        </td>
                        <td><?php esc_html_e('Parent Order', 'publishpress-cart'); ?></td>
                        <td><?php echo esc_html(get_the_date('F d, Y h:i a', $parent_id)); ?></td>
                        <td class="name"><span class="ppcart-status <?php echo esc_attr($parent->status); ?>"><?php echo esc_html($parent->get_status()); ?></span></td>
                        <td class="sub_cost">
                            <div class="view">
                                <span class="ppcart-Price-amount amount">
                                    <?php ppcart_formatted_price(ppcart_get_post_meta($parent_id, 'amount', true)); ?>
                                </span>
                            </div>
                        </td>
                    </tr>
                    <?php
    endif;

    $child_orders = ppcart_get_post_meta($post->ID, 'order_child');
    if ($child_orders) :
        foreach ($child_orders as $child_order) {
            $child_order = new PPCart_Order($child_order['id']);
            ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url(ppcart_get_edit_post_url($child_order->id)); ?>" class="ppcart-order-item-name">#<?php echo esc_html($child_order->id); ?></a>
                            </td>
                            <td><?php echo esc_html(apply_filters('ppcart_admin_order_child_order_label', __('Child Order', 'publishpress-cart'), $child_order, $order_data)); ?></td>
                            <td><?php echo esc_html(get_the_date('F d, Y h:i a', $child_order->id)); ?></td>
                            <td class="name">
                                <span class="ppcart-status <?php echo esc_attr($child_order->status); ?>"><?php echo esc_html($child_order->get_status()); ?></span>
                            </td>
                            <td class="sub_cost">
                                <div class="view">
                                    <span class="ppcart-Price-amount amount">
                                        <?php ppcart_formatted_price($child_order->amount); ?>
                                    </span>
                                </div>
                            </td>
                        </tr>
        <?php }
    endif;

    do_action('ppcart_order_related_orders', $order_data);

    ?>

            </tbody>
        </table>
    </div>
<?php } ?><!--close subscription details section-->
<?php
