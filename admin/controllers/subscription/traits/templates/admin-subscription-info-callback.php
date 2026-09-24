<?php

if (! defined('ABSPATH')) {
    exit;
}



// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin screen context check.
if (! ppcart_is_subscription_post_type($post->post_type) || !isset($_GET['post'])) {
    return;
}

$cart_subscription = new PPCart_Subscription($post->ID);
$cart_subscription = (object) $cart_subscription->get_data();

$str_mode = $cart_subscription->gateway_mode ?? $cart_subscription->stripe_mode ?? 'test';

$stripe_id = $cart_subscription->subscription_id ?? $cart_subscription->stripe_subscription_id ?? '';
$paypal_id = $cart_subscription->subscription_id ?? $cart_subscription->paypal_txn_id ?? '';

$details_link = '';
$payment_label = '';
$payment_reference = '';
$payment_reference_url = '';

if ($cart_subscription->pay_method == 'stripe' || isset($cart_subscription->stripe_subscription_id)) {
    $str_url = ($str_mode == 'test') ? 'test/' : '';
    $payment_label = __('Stripe subscription', 'publishpress-cart');
    $payment_reference = (string) $stripe_id;
    $payment_reference_url = 'https://dashboard.stripe.com/' . $str_url . 'subscriptions/' . $stripe_id;
    $details_link   .= esc_html__('Stripe ID:', 'publishpress-cart') . ' ';
    $details_link   .= '<a id="stripe-id" href="https://dashboard.stripe.com/' . esc_attr($str_url) . 'subscriptions/' . esc_attr($stripe_id) . '" target="_blank" rel="noopener noreferrer">';
    $details_link   .= esc_html($stripe_id) . '</a>';
} elseif ($cart_subscription->pay_method == 'paypal' || isset($cart_subscription->paypal_txn_id)) {
    $payment_label = __('PayPal subscription', 'publishpress-cart');
    $payment_reference = (string) $paypal_id;
    $payment_reference_url = 'https://www.paypal.com/activity/payment/' . $paypal_id;
    $details_link .= esc_html__('PayPal ID:', 'publishpress-cart') . ' ';
    $details_link .= '<a id="paypal-id" href="https://www.paypal.com/activity/payment/' . esc_attr($paypal_id) . '" target="_blank" rel="noopener noreferrer">';
    $details_link .= esc_html($paypal_id) . '</a>';
} else {
    switch ($cart_subscription->pay_method) {
        case 'paypal':
            $details_link .= esc_html__('Awaiting confirmation from PayPal', 'publishpress-cart');
            break;
        case 'stripe':
            $details_link .= esc_html__('Awaiting confirmation from Stripe', 'publishpress-cart');
            break;
        case 'cod':
            $details_link .= esc_html__('Cash on delivery', 'publishpress-cart');
            break;
        case 'manual':
            $details_link .= esc_html__('Manually created', 'publishpress-cart');
            break;
        default:
            break;
    }
}

$payment_link = apply_filters('ppcart_subscription_details_link', $details_link, $cart_subscription);
$subscription_terms = $cart_subscription->sub_payment_terms ?? $cart_subscription->sub_payment ?? ppcart_format_price($cart_subscription->amount);
$subscription_date = $cart_subscription->start_date ?? get_the_date('M j, Y', $post->ID);
$subscription_datetime = get_the_date('M j, Y g:i a', $post->ID);
$next_payment = ! empty($cart_subscription->next_pay_date) ? $cart_subscription->next_pay_date : '';
$end_date = ! empty($cart_subscription->end_date) ? $cart_subscription->end_date : '';
$subscription_term = $end_date ? $end_date : __('Ongoing', 'publishpress-cart');
$past_purchases_url = add_query_arg(
    [
        'page'         => PPCart_Admin_Screens::PAGE_CUSTOMER_REPORTS,
        'reportstypes' => 'subscription',
        'customerid'   => $cart_subscription->email,
        'customername' => trim((string) $cart_subscription->firstname . ' ' . (string) $cart_subscription->lastname),
    ],
    admin_url('admin.php')
);
$page_url = '';
if (! empty($cart_subscription->page_id)) {
    $page_url = get_permalink(absint($cart_subscription->page_id));
} elseif (! empty($cart_subscription->page_url)) {
    $page_url = $cart_subscription->page_url;
}
?>
<input type="hidden" id="ppcart_payment_method" name="ppcart_payment_method" value="<?php echo esc_attr($cart_subscription->pay_method); ?>">
<?php if ($stripe_id) : ?>
    <input type="hidden" name="ppcart_payment_intent" id="stripe_ppcart_payment_intent" value="<?php echo esc_attr($stripe_id); ?>">
<?php elseif ($paypal_id) : ?>
    <input type="hidden" name="ppcart_payment_intent" id="stripe_ppcart_payment_intent" value="<?php echo esc_attr(ppcart_get_post_meta($post->ID, 'paypal_subscr_id', true)); ?>">
<?php endif; ?>

<div id="ppcart-order-details" class="ppcart-product-info postbox-container ppcart-order-workspace">
    <section class="postbox ppcart-order-hero">
        <div class="ppcart-order-hero__main">
            <div class="ppcart-order-title-row">
                <h1 class="ppcart-order-title">
                    <?php
                    printf(
                        /* translators: %s: subscription ID. */
                        esc_html__('Subscription #%s', 'publishpress-cart'),
                        absint($post->ID)
                    );
?>
                </h1>
                <span class="ppcart-status <?php echo esc_attr($cart_subscription->status); ?>"><?php echo esc_html($cart_subscription->status_label); ?></span>
            </div>
            <?php if ($payment_reference) : ?>
                <div class="ppcart-order-payment">
                    <span class="ppcart-order-payment__label"><?php echo esc_html($payment_label); ?></span>
                    <div class="ppcart-order-payment__body">
                        <span class="ppcart-order-payment__field">
                            <span class="ppcart-order-payment__copy-target">
                                <code class="ppcart-order-payment__reference" title="<?php echo esc_attr($payment_reference); ?>"><?php echo esc_html($this->shorten_payment_reference($payment_reference)); ?></code>
                                <button type="button" class="button-link ppcart-order-payment__copy" data-ppcart-copy="<?php echo esc_attr($payment_reference); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-subscription-' . $post->ID . '-copy-payment-reference')); ?>"><?php esc_html_e('Copy', 'publishpress-cart'); ?></button>
                            </span>
                            <?php if ($payment_reference_url) : ?>
                                <a class="ppcart-order-payment__open" href="<?php echo esc_url($payment_reference_url); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e('Open subscription in gateway', 'publishpress-cart'); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-subscription-' . $post->ID . '-open-payment-reference')); ?>">
                                    <span class="dashicons dashicons-external" aria-hidden="true"></span>
                                </a>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            <?php elseif ($payment_link) : ?>
                <div class="ppcart-order-payment-id"><?php echo wp_kses_post($payment_link); ?></div>
            <?php endif; ?>
        </div>
        <div class="ppcart-order-hero__aside">
            <div class="ppcart-order-total-label"><?php esc_html_e('Subscription', 'publishpress-cart'); ?></div>
            <div class="ppcart-order-total ppcart-order-total--terms"><?php echo wp_kses_post($subscription_terms); ?></div>
        </div>
    </section>

    <section class="postbox ppcart-detail-card edit-hide">
        <header class="ppcart-detail-card__header">
            <h2><?php esc_html_e('Customer & Billing Details', 'publishpress-cart'); ?></h2>
            <a href="#" id="edit-order" class="button ppcart-button-secondary" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-subscription-' . $post->ID . '-edit-details')); ?>">
                <span class="dashicons dashicons-edit" aria-hidden="true"></span>
                <?php esc_html_e('Edit details', 'publishpress-cart'); ?>
            </a>
        </header>
        <div class="ppcart-detail-grid ppcart-detail-grid--three">
            <section class="ppcart-detail-section">
                <h3><?php esc_html_e('Customer', 'publishpress-cart'); ?></h3>
                <div class="ppcart-customer-block">
                    <?php $customer_name = trim((string) $cart_subscription->firstname . ' ' . (string) $cart_subscription->lastname); ?>
                    <p><?php echo $customer_name ? esc_html($customer_name) : '<span class="ppcart-empty">&mdash;</span>'; ?></p>
                    <?php if ($cart_subscription->email) : ?>
                        <p><a href="<?php echo esc_url('mailto:' . $cart_subscription->email); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-subscription-' . $post->ID . '-customer-email')); ?>"><?php echo esc_html($cart_subscription->email); ?></a></p>
                    <?php endif; ?>
                    <p><a href="<?php echo esc_url($past_purchases_url); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-subscription-' . $post->ID . '-past-purchases')); ?>"><?php esc_html_e('View Past Purchases', 'publishpress-cart'); ?> &rarr;</a></p>

                    <h4><?php esc_html_e('Customer account', 'publishpress-cart'); ?></h4>
                    <?php
if ($user_id = $cart_subscription->user_account) {
    $user_info = get_userdata($user_id);
    if ($user_info) {
        echo '<p>' . esc_html($user_info->display_name) . ' (' . esc_html($user_info->user_email) . ')</p>';
        ?>
                            <p><a href="<?php echo esc_url(get_edit_user_link($user_id)); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-subscription-' . $post->ID . '-customer-profile')); ?>"><?php esc_html_e('View Profile', 'publishpress-cart'); ?> &rarr;</a></p>
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
                        <p><a href="<?php echo esc_url($page_url); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-subscription-' . $post->ID . '-source-page')); ?>">...<?php echo esc_html(ltrim(wp_parse_url($page_url, PHP_URL_PATH), '/')); ?></a></p>
                    <?php endif; ?>
                </div>
            </section>

            <section class="ppcart-detail-section">
                <h3><?php esc_html_e('Billing Address', 'publishpress-cart'); ?></h3>
                <dl class="ppcart-kv ppcart-kv--two-columns">
                    <div>
                        <dt><?php esc_html_e('Phone', 'publishpress-cart'); ?></dt>
                        <dd><?php echo ! empty($cart_subscription->phone) ? esc_html($cart_subscription->phone) : '<span class="ppcart-empty">&mdash;</span>'; ?></dd>
                    </div>
                    <div>
                        <dt><?php esc_html_e('Company', 'publishpress-cart'); ?></dt>
                        <dd><?php echo ! empty($cart_subscription->company) ? esc_html($cart_subscription->company) : '<span class="ppcart-empty">&mdash;</span>'; ?></dd>
                    </div>
                    <div>
                        <dt><?php esc_html_e('VAT Number', 'publishpress-cart'); ?></dt>
                        <dd><?php echo ! empty($cart_subscription->vat_number) ? esc_html($cart_subscription->vat_number) : '<span class="ppcart-empty">&mdash;</span>'; ?></dd>
                    </div>
                    <div>
                        <dt><?php esc_html_e('Address', 'publishpress-cart'); ?></dt>
                        <dd><?php echo ! empty($cart_subscription->address1) ? esc_html($cart_subscription->address1) : '<span class="ppcart-empty">&mdash;</span>'; ?></dd>
                    </div>
                    <div>
                        <dt><?php esc_html_e('Address 2', 'publishpress-cart'); ?></dt>
                        <dd><?php echo ! empty($cart_subscription->address2) ? esc_html($cart_subscription->address2) : '<span class="ppcart-empty">&mdash;</span>'; ?></dd>
                    </div>
                    <div>
                        <dt><?php esc_html_e('City', 'publishpress-cart'); ?></dt>
                        <dd><?php echo ! empty($cart_subscription->city) ? esc_html($cart_subscription->city) : '<span class="ppcart-empty">&mdash;</span>'; ?></dd>
                    </div>
                    <div>
                        <dt><?php esc_html_e('State', 'publishpress-cart'); ?></dt>
                        <dd><?php echo ! empty($cart_subscription->state) ? esc_html($cart_subscription->state) : '<span class="ppcart-empty">&mdash;</span>'; ?></dd>
                    </div>
                    <div>
                        <dt><?php esc_html_e('Zip', 'publishpress-cart'); ?></dt>
                        <dd><?php echo ! empty($cart_subscription->zip) ? esc_html($cart_subscription->zip) : '<span class="ppcart-empty">&mdash;</span>'; ?></dd>
                    </div>
                    <div>
                        <dt><?php esc_html_e('Country', 'publishpress-cart'); ?></dt>
                        <dd><?php echo ! empty($cart_subscription->country) ? esc_html($cart_subscription->country) : '<span class="ppcart-empty">&mdash;</span>'; ?></dd>
                    </div>
                </dl>
            </section>

            <section class="ppcart-detail-section">
                <h3><?php esc_html_e('Subscription Details', 'publishpress-cart'); ?></h3>
                <dl class="ppcart-kv">
                    <div>
                        <dt><?php esc_html_e('Start date', 'publishpress-cart'); ?></dt>
                        <dd><?php echo esc_html($subscription_datetime); ?></dd>
                    </div>
                    <div>
                        <dt><?php esc_html_e('Next payment', 'publishpress-cart'); ?></dt>
                        <dd><?php echo $next_payment ? esc_html($next_payment) : '<span class="ppcart-empty">&mdash;</span>'; ?></dd>
                    </div>
                    <div>
                        <dt><?php esc_html_e('Term', 'publishpress-cart'); ?></dt>
                        <dd><?php echo esc_html($subscription_term); ?></dd>
                    </div>
                    <div>
                        <dt><?php esc_html_e('IP address', 'publishpress-cart'); ?></dt>
                        <dd><?php echo ! empty($cart_subscription->ip_address) ? esc_html($cart_subscription->ip_address) : '<span class="ppcart-empty">&mdash;</span>'; ?></dd>
                    </div>
                    <div>
                        <dt><?php esc_html_e('Status', 'publishpress-cart'); ?></dt>
                        <dd>
                            <span class="ppcart-status-line">
                                <span class="ppcart-status <?php echo esc_attr($cart_subscription->status); ?>"><?php echo esc_html($cart_subscription->status_label); ?></span>
                            </span>
                        </dd>
                    </div>
                </dl>
                <div class="ppcart-detail-extra">
                    <?php do_action('ppcart_sub_details', $cart_subscription); ?>
                </div>
            </section>

            <?php if (isset($cart_subscription->custom_fields) && $cart_subscription->custom_fields) : ?>
                <section class="ppcart-detail-section ppcart-detail-section--wide">
                    <h3><?php esc_html_e('Custom Fields', 'publishpress-cart'); ?></h3>
                    <dl class="ppcart-kv ppcart-kv--two-columns">
                        <?php
    foreach ($cart_subscription->custom_fields as $v) {
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
