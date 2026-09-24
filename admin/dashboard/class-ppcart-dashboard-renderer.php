<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Renders the dashboard widget HTML from a monthly metrics summary.
 *
 * @package PPCart
 * @subpackage PPCart/admin
 */
class PPCart_Dashboard_Renderer
{
    public function render($summary)
    {
        ?>
        <div class="ppcart-dashboard-widget">
            <?php $this->render_orders($summary['orders']); ?>
            <?php $this->render_subscriptions($summary['subscriptions']); ?>
            <?php $this->render_plans($summary['plans']); ?>
            <?php $this->render_quick_links(); ?>
            <?php $this->render_support(); ?>
        </div>
        <?php
    }

    private function render_orders($orders)
    {
        $order_type        = function_exists('ppcart_live_post_type') ? ppcart_live_post_type('order') : 'ppcart_order';
        $subscription_type = function_exists('ppcart_live_post_type') ? ppcart_live_post_type('subscription') : 'ppcart_subscription';
        ?>
        <h3><?php esc_html_e('Orders this month', 'publishpress-cart'); ?> <a href="<?php echo esc_url(admin_url('edit.php?post_type=' . $order_type . '&order_type=orders')); ?>"><?php esc_html_e('View all orders', 'publishpress-cart'); ?></a></h3>
        <ul id="ppcart-order-stats">
            <li><?php esc_html_e('Total Orders', 'publishpress-cart'); ?> <b><?php echo esc_html($orders['total_orders']); ?></b></li>
            <li><?php esc_html_e('Total Sales', 'publishpress-cart'); ?> <b><?php ppcart_formatted_price($orders['total_sales']); ?></b></li>
            <li><?php esc_html_e('Avg. Order Value', 'publishpress-cart'); ?> <b><?php ppcart_formatted_price($orders['average_order_value']); ?></b></li>
            <li><?php esc_html_e('Pending Orders', 'publishpress-cart'); ?> <b><?php echo esc_html($orders['pending_orders']); ?></b></li>
            <li><?php esc_html_e('Paid Orders', 'publishpress-cart'); ?> <b><?php echo esc_html($orders['paid_orders']); ?></b></li>
            <li><?php esc_html_e('Refunded Orders', 'publishpress-cart'); ?> <b><?php echo esc_html($orders['refunded_orders']); ?></b></li>
        </ul>
        <?php
    }

    private function render_subscriptions($subscriptions)
    {
        $subscription_type = function_exists('ppcart_live_post_type') ? ppcart_live_post_type('subscription') : 'ppcart_subscription';
        ?>
        <h3><?php esc_html_e('Subscriptions this month', 'publishpress-cart'); ?> <a href="<?php echo esc_url(admin_url('edit.php?post_type=' . $subscription_type . '&subscription_type=ongoing')); ?>"><?php esc_html_e('View all subscriptions', 'publishpress-cart'); ?></a></h3>
        <ul id="ppcart-order-stats">
            <li><?php esc_html_e('Active Subscriptions', 'publishpress-cart'); ?> <b><?php echo esc_html($subscriptions['active_subscriptions']); ?></b></li>
            <li><?php esc_html_e('New Sign-ups', 'publishpress-cart'); ?> <b><?php echo esc_html($subscriptions['new_sign_ups']); ?></b></li>
            <li><?php esc_html_e('Trials', 'publishpress-cart'); ?> <b><?php echo esc_html($subscriptions['trials']); ?></b></li>
            <li><?php esc_html_e('Renewals', 'publishpress-cart'); ?> <b><?php echo esc_html($subscriptions['renewals']); ?></b></li>
            <li><?php esc_html_e('MRR', 'publishpress-cart'); ?> <b><?php echo wp_kses_post($subscriptions['mrr']); ?></b></li>
        </ul>
        <?php
    }

    private function render_plans($plans)
    {
        $subscription_type = function_exists('ppcart_live_post_type') ? ppcart_live_post_type('subscription') : 'ppcart_subscription';
        ?>
        <h3><?php esc_html_e('Payment plans this month', 'publishpress-cart'); ?> <a href="<?php echo esc_url(admin_url('edit.php?post_type=' . $subscription_type . '&subscription_type=installments')); ?>"><?php esc_html_e('View all plans', 'publishpress-cart'); ?></a></h3>
        <ul id="ppcart-order-stats">
            <li><?php esc_html_e('Active Plans', 'publishpress-cart'); ?> <b><?php echo esc_html($plans['active_plans']); ?></b></li>
            <li><?php esc_html_e('Collected Revenue', 'publishpress-cart'); ?>  <b><?php echo wp_kses_post($plans['collected']); ?></b></li>
            <li><?php esc_html_e('Revenue Expected', 'publishpress-cart'); ?> <b><?php echo wp_kses_post($plans['expected']); ?></b> <small>(<?php esc_html_e('all active plans', 'publishpress-cart'); ?>)</small></li>
            <li><?php esc_html_e('Cancelled Plans', 'publishpress-cart'); ?> <b><?php echo esc_html($plans['cancellations']); ?></b></li>
        </ul>
        <?php
    }

    private function render_quick_links()
    {
        $links = [
            admin_url('admin.php?page=' . PPCart_Admin_Screens::PAGE_REPORTS) => __('View Reports', 'publishpress-cart'),
            admin_url('edit.php?post_type=' . (function_exists('ppcart_live_post_type') ? ppcart_live_post_type('product') : 'ppcart_product'))        => __('Manage Products', 'publishpress-cart'),
            admin_url('admin.php?page=' . PPCart_Admin_Screens::PAGE_CONTACTS) => __('View Contacts', 'publishpress-cart'),
            admin_url('admin.php?page=' . PPCart_Admin_Screens::PAGE_SETTINGS) => __('Settings', 'publishpress-cart'),
        ];

        echo '<h3>' . esc_html__('Quick Links', 'publishpress-cart') . '</h3>';
        echo '<ul>';
        foreach ($links as $url => $label) {
            echo '<li><a href="' . esc_url($url) . '">' . esc_html($label) . '</a></li>';
        }
        echo '</ul>';
    }

    private function render_support()
    {
        echo '<h3>' . esc_html__('Support & Documentation', 'publishpress-cart') . '</h3>';
        echo '<p>' . wp_kses_post(sprintf(
            /* translators: 1: opening link tag, 2: closing link tag. */
            __('Visit our %1$sdocumentation%2$s for help.', 'publishpress-cart'),
            '<a href="' . esc_url(PPCART_DOCS_URL . 'getting-started/introduction') . '">',
            '</a>'
        )) . '</p>';
    }
}
