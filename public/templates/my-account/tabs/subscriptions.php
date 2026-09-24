<?php
if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="tab-container subscriptions-tab">

    <div id="subscriptions" class="tab-content">

        <?php
        $subscriptions = ppcart_get_user_subscriptions(get_current_user_id(), ['active', 'trialing']);
$subscription_paused = ppcart_get_user_subscriptions(get_current_user_id(), 'paused');
$subscription_pastdue = ppcart_get_user_subscriptions(get_current_user_id(), 'past_due');
$subscription_canceled = ppcart_get_user_subscriptions(get_current_user_id(), 'canceled');

$sub_tables = [];
$sub_labels = [];
if ($subscriptions) {
    $sub_tables['all'] = $subscriptions;
    $sub_labels['all'] = __('Active Subscriptions', 'publishpress-cart');
}
if ($subscription_paused) {
    $sub_tables['paused'] = $subscription_paused;
    $sub_labels['paused'] = __('Paused Subscriptions', 'publishpress-cart');
}
if ($subscription_pastdue) {
    $sub_tables['past_due'] = $subscription_pastdue;
    $sub_labels['past_due'] = __('Past Due Subscriptions', 'publishpress-cart');
}
if ($subscription_canceled) {
    $sub_tables['canceled'] = $subscription_canceled;
    $sub_labels['canceled'] = __('Canceled Subscriptions', 'publishpress-cart');
}

foreach ($sub_tables as $subscription_type => $subscriptions) : ?>
        <div id="subscription-<?php echo esc_attr($subscription_type); ?>" class="ppcart-account-tab-pane">
            <h4><?php echo esc_html($sub_labels[$subscription_type]); ?></h4>
            <div class="overflow-x-auto">
                <table class="ppcart-account-table" cellpadding="0" cellspacing="0">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Product', 'publishpress-cart'); ?></th>
                            <th><?php esc_html_e('Status', 'publishpress-cart'); ?></th>
                            <th><?php esc_html_e('Next Payment', 'publishpress-cart'); ?></th>
                            <th><?php esc_html_e('Price', 'publishpress-cart'); ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($subscriptions) {
                            foreach ($subscriptions as $subscription) {
                                ppcart_template('my-account/subscription-row', '', $subscription);
                            }
                        } else { ?>
                        <tr>
                            <td colspan="6"><?php esc_html_e('No records found', 'publishpress-cart'); ?></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
           </div>
        </div>
<?php endforeach; ?>

    </div>

</div><!-- container -->
