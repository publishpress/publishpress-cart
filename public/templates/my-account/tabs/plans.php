<?php
if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="tab-container payment-plans-tab">

    <div id="payment-plans" class="tab-content">

        <?php
        $subscriptions = ppcart_get_user_subscriptions(get_current_user_id(), ['active', 'trialing'], 'installment');
$subscription_canceled = ppcart_get_user_subscriptions(get_current_user_id(), ['completed','canceled'], 'installment');
$subscription_pastdue = ppcart_get_user_subscriptions(get_current_user_id(), 'past_due', 'installment');

$sub_tables = [];
$sub_labels = [];
if ($subscriptions) {
    $sub_tables['all'] = $subscriptions;
    $sub_labels['all'] = __('Active Plans', 'publishpress-cart');
}
if ($subscription_pastdue) {
    $sub_tables['past_due'] = $subscription_pastdue;
    $sub_labels['past_due'] = __('Past Due Plans', 'publishpress-cart');
}
if ($subscription_canceled) {
    $sub_tables['completed'] = $subscription_canceled;
    $sub_labels['completed'] = __('Expired Plans', 'publishpress-cart');
}

foreach ($sub_tables as $subscription_type => $subscriptions) : ?>
        <div id="plan-<?php echo esc_attr($subscription_type); ?>" class="ppcart-account-tab-pane">
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
