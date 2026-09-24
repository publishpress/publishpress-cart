<?php

if (! defined('ABSPATH')) {
    exit;
}


if (! $post || ! ppcart_is_subscription_post_type($post->post_type)) {
    return;
}

$subscription = new PPCart_Subscription($post->ID);
if (! $subscription->id) {
    return;
}

$subscription = (object) $subscription->get_data();
$next_payment = ! empty($subscription->next_pay_date) ? $subscription->next_pay_date : '';
$end_date = ! empty($subscription->end_date) ? $subscription->end_date : '';
$subscription_term = $end_date ? $end_date : __('Ongoing', 'publishpress-cart');
$subscription_status = ! empty($subscription->status) ? (string) $subscription->status : '';
$subscription_status_options = [
    'incomplete' => __('Incomplete', 'publishpress-cart'),
    'trialing'   => __('Trialing', 'publishpress-cart'),
    'active'     => __('Active', 'publishpress-cart'),
    'past_due'   => __('Past Due', 'publishpress-cart'),
    'unpaid'     => __('Unpaid', 'publishpress-cart'),
    'paused'     => __('Paused', 'publishpress-cart'),
    'canceled'   => __('Canceled', 'publishpress-cart'),
    'completed'  => __('Completed', 'publishpress-cart'),
];

if ($subscription_status && ! isset($subscription_status_options[ $subscription_status ])) {
    $subscription_status_options[ $subscription_status ] = ! empty($subscription->status_label) ? $subscription->status_label : ucwords(str_replace('_', ' ', $subscription_status));
}
?>
<section class="ppcart-edit-section ppcart-edit-section--subscription-details">
    <header class="ppcart-edit-section__header">
        <h3><?php esc_html_e('Subscription Details', 'publishpress-cart'); ?></h3>
    </header>
    <div class="ppcart-edit-section__fields">
        <dl class="ppcart-kv">
            <div>
                <dt><?php esc_html_e('Start date', 'publishpress-cart'); ?></dt>
                <dd><?php echo esc_html(get_the_date('M j, Y g:i a', $post->ID)); ?></dd>
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
                <dd><?php echo ! empty($subscription->ip_address) ? esc_html($subscription->ip_address) : '<span class="ppcart-empty">&mdash;</span>'; ?></dd>
            </div>
            <div>
                <dt><?php esc_html_e('Subscription Status', 'publishpress-cart'); ?></dt>
                <dd>
                    <span class="ppcart-subscription-status-control">
                        <select id="ppcart-subscription-status-display" aria-label="<?php esc_attr_e('Subscription Status', 'publishpress-cart'); ?>" disabled>
                            <?php foreach ($subscription_status_options as $status_value => $status_label) : ?>
                                <option value="<?php echo esc_attr($status_value); ?>" <?php selected($subscription_status, $status_value); ?>><?php echo esc_html($status_label); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="dashicons dashicons-lock ppcart-field-lock" aria-hidden="true"></span>
                    </span>
                </dd>
            </div>
        </dl>
    </div>
</section>
<?php
