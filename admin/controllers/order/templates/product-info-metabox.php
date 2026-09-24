<?php

if (! defined('ABSPATH')) {
    exit;
}



$orderClass = new PPCart_Order($post->ID);
$order_data = apply_filters('ppcart_order', $orderClass);
$order_data = (object) $orderClass->get_data();

if (ppcart_is_order_post_type($post->post_type) || ppcart_is_subscription_post_type($post->post_type)) {
    ppcart_enqueue_or_print_inline_style(
        'ppcart',
        '.misc-pub-section{display:none;}'
    );
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin screen context check.
if (! ppcart_is_order_post_type($post->post_type) || !isset($_GET['post'])) {
    return;
}

$str_mode = $order_data->gateway_mode ?? $order_data->stripe_mode ?? 'test';

$stripe_id = $order_data->stripe_charge_id ?? $order_data->transaction_id ?? false;

$paypal_id = $order_data->paypal_txn_id ?? $order_data->transaction_id ?? false;

$is_locked_post = in_array(PPCart_Status_Labels::logical_from_post($post), ['canceled', 'refunded'], true);

ppcart_enqueue_or_print_inline_style(
    'ppcart',
    '#edit-disabled{opacity:0.6;}'
);
?>
<input type="hidden" id="stripe-mode" value="<?php echo esc_attr($str_mode); ?>">

<?php
$order_details_link = '';
$payment_label = '';
$payment_reference = '';
$payment_reference_url = '';

if (isset($order_data->stripe_charge_id) || $order_data->pay_method == 'stripe') {
    $str_url = ($str_mode == 'test') ? 'test/' : '';
    $payment_label = __('Stripe payment', 'publishpress-cart');
    $payment_reference = (string) $stripe_id;
    $payment_reference_url = 'https://dashboard.stripe.com/' . $str_url . 'payments/' . $stripe_id;
    $order_details_link   .= esc_html__('Stripe ID:', 'publishpress-cart') . ' ';
    $order_details_link   .= '<a id="stripe-id" href="https://dashboard.stripe.com/' . esc_attr($str_url) . 'payments/' . esc_attr($stripe_id) . '" target="_blank" rel="noopener noreferrer">';
    $order_details_link   .= esc_html($stripe_id) . '</a>';
} elseif (isset($order_data->paypal_txn_id) || $order_data->pay_method == 'paypal') {
    $payment_label = __('PayPal payment', 'publishpress-cart');
    $payment_reference = (string) $paypal_id;
    $payment_reference_url = 'https://www.paypal.com/activity/payment/' . $paypal_id;
    $order_details_link .= esc_html__('PayPal ID:', 'publishpress-cart') . ' ';
    $order_details_link .= '<a id="paypal-id" href="https://www.paypal.com/activity/payment/' . esc_attr($paypal_id) . '" target="_blank" rel="noopener noreferrer">';
    $order_details_link .= esc_html($paypal_id) . '</a>';
} elseif (isset($order_data->pay_method)) {
    switch ($order_data->pay_method) {
        case 'paypal':
            $order_details_link .= esc_html__('Awaiting confirmation from PayPal', 'publishpress-cart');
            break;
        case 'stripe':
            $order_details_link .= esc_html__('Awaiting confirmation from Stripe', 'publishpress-cart');
            break;
        case 'cod':
            $order_details_link .= esc_html__('Cash on delivery', 'publishpress-cart');
            break;
        case 'manual':
            $order_details_link .= esc_html__('Manually created', 'publishpress-cart');
            break;
        default:
            break;
    }
}

$payment_link = apply_filters('ppcart_order_details_link', $order_details_link, $order_data);
$invoice_link = $orderClass->invoice_link();
$receipt_link = $orderClass->receipt_link();
$order_date = get_the_date('M j, Y', $post->ID);
$order_datetime = get_the_date('M j, Y g:i a', $post->ID);
$past_purchases_url = add_query_arg(
    [
        'page'         => PPCart_Admin_Screens::PAGE_CUSTOMER_REPORTS,
        'reportstypes' => 'order',
        'customerid'   => $order_data->email,
    ],
    admin_url('admin.php')
);
$page_url = '';
if (! empty($order_data->page_id)) {
    $page_url = get_permalink(absint($order_data->page_id));
} elseif (! empty($order_data->page_url)) {
    $page_url = $order_data->page_url;
}
$order_status_class = $order_data->status;
$order_status_label = $order_data->status_label;
if (class_exists('PPCart_Order_Refunds') && PPCart_Order_Refunds::is_partially_refunded($post->ID, $order_data->amount)) {
    $order_status_class = 'partially-refunded';
    $order_status_label = __('Partially refunded', 'publishpress-cart');
}
?>

<div id="ppcart-order-details" class="ppcart-product-info postbox-container ppcart-order-workspace">
    <section class="postbox ppcart-order-hero">
        <div class="ppcart-order-hero__main">
            <div class="ppcart-order-title-row">
                <h1 class="ppcart-order-title">
                    <?php
                    printf(
                        /* translators: %s: order ID. */
                        esc_html__('Order #%s', 'publishpress-cart'),
                        absint($post->ID)
                    );
?>
                </h1>
                <span class="ppcart-status <?php echo esc_attr($order_status_class); ?>"><?php echo esc_html($order_status_label); ?></span>
            </div>
            <?php if ($payment_reference) : ?>
                <div class="ppcart-order-payment">
                    <span class="ppcart-order-payment__label"><?php echo esc_html($payment_label); ?></span>
                    <div class="ppcart-order-payment__body">
                        <span class="ppcart-order-payment__field">
                            <span class="ppcart-order-payment__copy-target">
                                <code class="ppcart-order-payment__reference" title="<?php echo esc_attr($payment_reference); ?>"><?php echo esc_html($this->shorten_payment_reference($payment_reference)); ?></code>
                                <button type="button" class="button-link ppcart-order-payment__copy" data-ppcart-copy="<?php echo esc_attr($payment_reference); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-order-' . $post->ID . '-copy-payment-reference')); ?>"><?php esc_html_e('Copy', 'publishpress-cart'); ?></button>
                            </span>
                            <?php if ($payment_reference_url) : ?>
                                <a class="ppcart-order-payment__open" href="<?php echo esc_url($payment_reference_url); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e('Open payment in gateway', 'publishpress-cart'); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-order-' . $post->ID . '-open-payment-reference')); ?>">
                                    <span class="dashicons dashicons-external" aria-hidden="true"></span>
                                </a>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            <?php elseif ($payment_link) : ?>
                <div class="ppcart-order-payment-id"><?php echo wp_kses_post($payment_link); ?></div>
            <?php endif; ?>
            <div class="ppcart-order-actions" aria-label="<?php esc_attr_e('Order actions', 'publishpress-cart'); ?>">
                <?php if ($invoice_link) : ?>
                    <a href="<?php echo esc_url($invoice_link); ?>" class="button ppcart-hero-action" target="_blank" rel="noopener noreferrer" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-order-' . $post->ID . '-invoice')); ?>">
                        <span class="dashicons dashicons-download" aria-hidden="true"></span>
                        <?php esc_html_e('Invoice', 'publishpress-cart'); ?>
                    </a>
                <?php endif; ?>
                <?php if ($order_data->status === 'paid') : ?>
                    <?php if ($receipt_link) : ?>
                        <a href="<?php echo esc_url($receipt_link); ?>" class="button ppcart-hero-action" target="_blank" rel="noopener noreferrer" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-order-' . $post->ID . '-receipt')); ?>">
                            <span class="dashicons dashicons-download" aria-hidden="true"></span>
                            <?php esc_html_e('Receipt', 'publishpress-cart'); ?>
                        </a>
                    <?php endif; ?>
                    <a href="#" id="resend-purchase-confirmation-email" class="button ppcart-hero-action" data-order-id="<?php echo esc_attr($post->ID); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-order-' . $post->ID . '-resend-email')); ?>">
                        <span class="dashicons dashicons-media-document" aria-hidden="true"></span>
                        <?php esc_html_e('Resend email', 'publishpress-cart'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="ppcart-order-hero__aside">
            <div class="ppcart-order-total-label"><?php esc_html_e('Total paid', 'publishpress-cart'); ?></div>
            <div class="ppcart-order-total"><?php echo wp_kses_post(ppcart_format_price($order_data->amount)); ?></div>
            <div class="ppcart-order-date"><?php echo esc_html($order_date); ?></div>
        </div>
    </section>

    <section class="postbox ppcart-detail-card edit-hide">
        <header class="ppcart-detail-card__header">
            <h2><?php esc_html_e('Customer & Billing Details', 'publishpress-cart'); ?></h2>
            <?php if ($is_locked_post) : ?>
                <span id="edit-disabled" class="button ppcart-button-secondary"><?php esc_html_e('Edit details', 'publishpress-cart'); ?></span>
            <?php else : ?>
                <a href="#" class="button ppcart-button-secondary ppcart-edit-order" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-order-' . $post->ID . '-edit-details')); ?>">
                    <span class="dashicons dashicons-edit" aria-hidden="true"></span>
                    <?php esc_html_e('Edit details', 'publishpress-cart'); ?>
                </a>
            <?php endif; ?>
        </header>
        <div class="ppcart-detail-grid ppcart-detail-grid--three">
            <section class="ppcart-detail-section">
                <h3><?php esc_html_e('Customer', 'publishpress-cart'); ?></h3>
                <div class="ppcart-customer-block">
                    <p><?php echo esc_html(trim((string) $order_data->firstname . ' ' . (string) $order_data->lastname)); ?></p>
                    <?php if ($order_data->email) : ?>
                        <p><a href="<?php echo esc_url('mailto:' . $order_data->email); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-order-' . $post->ID . '-customer-email')); ?>"><?php echo esc_html($order_data->email); ?></a></p>
                    <?php endif; ?>
                    <p><a href="<?php echo esc_url($past_purchases_url); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-order-' . $post->ID . '-past-purchases')); ?>"><?php esc_html_e('View Past Purchases', 'publishpress-cart'); ?> &rarr;</a></p>

                    <h4><?php esc_html_e('Customer account', 'publishpress-cart'); ?></h4>
                    <?php
if (isset($order_data->user_account) && $user_id = $order_data->user_account) {
    $user_info = get_userdata($user_id);
    if ($user_info) {
        echo '<p>' . esc_html($user_info->display_name) . ' (' . esc_html($user_info->user_email) . ')</p>';
        ?>
                            <p><a href="<?php echo esc_url(get_edit_user_link($user_id)); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-order-' . $post->ID . '-customer-profile')); ?>"><?php esc_html_e('View Profile', 'publishpress-cart'); ?> &rarr;</a></p>
                    <?php
    } else {
        echo '<p class="ppcart-empty">&mdash;</p>';
    }
} else {
    echo '<p>' . esc_html__('Guest', 'publishpress-cart') . '</p>';
}
?>

                    <?php if ($page_url) : ?>
                        <h4><?php esc_html_e('URL', 'publishpress-cart'); ?></h4>
                        <p><a href="<?php echo esc_url($page_url); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-order-' . $post->ID . '-source-page')); ?>">...<?php echo esc_html(ltrim(wp_parse_url($page_url, PHP_URL_PATH), '/')); ?></a></p>
                    <?php endif; ?>
                </div>
            </section>

            <section class="ppcart-detail-section">
                <h3><?php esc_html_e('Billing Address', 'publishpress-cart'); ?></h3>
                <dl class="ppcart-kv ppcart-kv--two-columns">
                    <div>
                        <dt><?php esc_html_e('Phone', 'publishpress-cart'); ?></dt>
                        <dd><?php echo ! empty($order_data->phone) ? esc_html($order_data->phone) : '<span class="ppcart-empty">&mdash;</span>'; ?></dd>
                    </div>
                    <div>
                        <dt><?php esc_html_e('Company', 'publishpress-cart'); ?></dt>
                        <dd><?php echo ! empty($order_data->company) ? esc_html($order_data->company) : '<span class="ppcart-empty">&mdash;</span>'; ?></dd>
                    </div>
                    <div>
                        <dt><?php esc_html_e('VAT Number', 'publishpress-cart'); ?></dt>
                        <dd><?php echo ! empty($order_data->vat_number) ? esc_html($order_data->vat_number) : '<span class="ppcart-empty">&mdash;</span>'; ?></dd>
                    </div>
                    <div>
                        <dt><?php esc_html_e('Address', 'publishpress-cart'); ?></dt>
                        <dd><?php echo ! empty($order_data->address1) ? esc_html($order_data->address1) : '<span class="ppcart-empty">&mdash;</span>'; ?></dd>
                    </div>
                    <div>
                        <dt><?php esc_html_e('Address 2', 'publishpress-cart'); ?></dt>
                        <dd><?php echo ! empty($order_data->address2) ? esc_html($order_data->address2) : '<span class="ppcart-empty">&mdash;</span>'; ?></dd>
                    </div>
                    <div>
                        <dt><?php esc_html_e('City', 'publishpress-cart'); ?></dt>
                        <dd><?php echo ! empty($order_data->city) ? esc_html($order_data->city) : '<span class="ppcart-empty">&mdash;</span>'; ?></dd>
                    </div>
                    <div>
                        <dt><?php esc_html_e('State', 'publishpress-cart'); ?></dt>
                        <dd><?php echo ! empty($order_data->state) ? esc_html($order_data->state) : '<span class="ppcart-empty">&mdash;</span>'; ?></dd>
                    </div>
                    <div>
                        <dt><?php esc_html_e('Zip', 'publishpress-cart'); ?></dt>
                        <dd><?php echo ! empty($order_data->zip) ? esc_html($order_data->zip) : '<span class="ppcart-empty">&mdash;</span>'; ?></dd>
                    </div>
                    <div>
                        <dt><?php esc_html_e('Country', 'publishpress-cart'); ?></dt>
                        <dd><?php echo ! empty($order_data->country) ? esc_html($order_data->country) : '<span class="ppcart-empty">&mdash;</span>'; ?></dd>
                    </div>
                </dl>
            </section>

            <section class="ppcart-detail-section">
                <h3><?php esc_html_e('Order Details', 'publishpress-cart'); ?></h3>
                <dl class="ppcart-kv">
                    <div>
                        <dt><?php esc_html_e('Order date', 'publishpress-cart'); ?></dt>
                        <dd><?php echo esc_html($order_datetime); ?></dd>
                    </div>
                    <div>
                        <dt><?php esc_html_e('Order amount', 'publishpress-cart'); ?></dt>
                        <dd><?php echo wp_kses_post(ppcart_format_price($order_data->amount)); ?></dd>
                    </div>
                    <div>
                        <dt><?php esc_html_e('IP address', 'publishpress-cart'); ?></dt>
                        <dd><?php echo ! empty($order_data->ip_address) ? esc_html($order_data->ip_address) : '<span class="ppcart-empty">&mdash;</span>'; ?></dd>
                    </div>
                    <div>
                        <dt><?php esc_html_e('Order status', 'publishpress-cart'); ?></dt>
                        <dd>
                            <span class="ppcart-status-line">
                                <span class="ppcart-status <?php echo esc_attr($order_status_class); ?>"><?php echo esc_html($order_status_label); ?></span>
                            </span>
                        </dd>
                    </div>
                    <?php if (isset($order_data->consent)) : ?>
                        <div>
                            <dt><?php esc_html_e('Opted-in', 'publishpress-cart'); ?></dt>
                            <dd><?php echo esc_html($order_data->consent); ?></dd>
                        </div>
                    <?php endif; ?>
                </dl>
                <div class="ppcart-detail-extra">
                    <?php do_action('ppcart_order_details', $order_data); ?>
                </div>
            </section>

            <?php if (isset($order_data->custom_fields) && $order_data->custom_fields) : ?>
                <section class="ppcart-detail-section ppcart-detail-section--wide">
                    <h3><?php esc_html_e('Custom Fields', 'publishpress-cart'); ?></h3>
                    <dl class="ppcart-kv ppcart-kv--two-columns">
                        <?php
    foreach ($order_data->custom_fields as $v) {
        if (is_array($v['value'])) {
            $value = [];
            for ($i = 0; $i < count($v['value']); $i++) {
                $value[] = (isset($v['value_label'][$i])) ? $v['value_label'][$i] : $v['value'][$i];
            }
            $value = implode(', ', $value);
        } else {
            $value = (isset($v['value_label'])) ? $v['value_label'] : $v['value'];
        }
        ?>
                            <div>
                                <dt><?php echo esc_html($v['label']); ?></dt>
                                <dd><?php echo esc_html($value); ?></dd>
                            </div>
                        <?php
    }
?>
                    </dl>
                </section>
            <?php endif; ?>
        </div>
    </section>
</div><!-- .ppcart-product-settings -->
<?php
