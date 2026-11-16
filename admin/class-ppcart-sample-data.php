<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Creates removable, local-only Cart records for demonstrations and QA.
 */
class PPCart_Sample_Data
{
    private const META_KEY = '_ppcart_sample_data';

    public function register_page()
    {
        add_submenu_page(
            PPCart_Admin_Screens::menu_slug(),
            __('Sample Data', 'publishpress-cart'),
            __('Sample Data', 'publishpress-cart'),
            'manage_options',
            PPCart_Admin_Screens::PAGE_SAMPLE_DATA,
            [$this, 'render_page']
        );
    }

    public function render_page()
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $is_safe_environment = $this->is_safe_environment();
        $sample_count = $this->count_sample_posts();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('PublishPress Cart Sample Data', 'publishpress-cart'); ?></h1>
            <p><?php esc_html_e('Create fake products, customers, orders, subscriptions, payment plans, coupons, and reports for a demonstration or QA site. Records are spread across the past 12 months and clearly marked as sample data.', 'publishpress-cart'); ?></p>
            <p><strong><?php esc_html_e('No payments, emails, webhooks, or third-party integrations are triggered.', 'publishpress-cart'); ?></strong></p>
            <?php if (! $is_safe_environment) : ?>
                <div class="notice notice-warning"><p><?php esc_html_e('Sample data generation is available only on local, development, or staging sites.', 'publishpress-cart'); ?></p></div>
            <?php elseif ($sample_count) : ?>
                <p><?php echo esc_html(sprintf(_n('%d sample record exists.', '%d sample records exist.', $sample_count, 'publishpress-cart'), $sample_count)); ?></p>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="ppcart_remove_sample_data" />
                    <?php wp_nonce_field('ppcart_remove_sample_data'); ?>
                    <?php submit_button(__('Remove Sample Data', 'publishpress-cart'), 'delete', 'submit', false); ?>
                </form>
            <?php else : ?>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="ppcart_generate_sample_data" />
                    <?php wp_nonce_field('ppcart_generate_sample_data'); ?>
                    <?php submit_button(__('Generate 12 Months of Sample Data', 'publishpress-cart'), 'primary', 'submit', false); ?>
                </form>
            <?php endif; ?>
        </div>
        <?php
    }

    public function generate()
    {
        $this->authorize('ppcart_generate_sample_data');
        if (! $this->is_safe_environment()) {
            wp_die(esc_html__('Sample data can only be generated on local, development, or staging sites.', 'publishpress-cart'));
        }

        if ($this->count_sample_posts()) {
            $this->redirect('already-generated');
        }

        $products = $this->create_products();
        $customers = $this->customers();
        $orders = [];
        $now = current_time('timestamp', true);

        for ($i = 0; $i < 180; $i++) {
            $customer = $customers[$i % count($customers)];
            $product = $products[$i % count($products)];
            $timestamp = $now - (DAY_IN_SECONDS * (364 - (($i * 17) % 365)));
            $status = ['paid', 'paid', 'paid', 'refunded', 'failed', 'pending-payment'][$i % 6];
            $orders[] = $this->create_order($product, $customer, $timestamp, $status, $i);
        }

        $subscriptions = 0;
        foreach (array_slice($orders, 0, 36) as $index => $order_id) {
            $this->create_subscription($order_id, $index);
            $subscriptions++;
        }

        /** Allows Cart Pro to add its own sample-only affiliate/referral and payout records. */
        do_action('ppcart_sample_data_generate_affiliate_payments', $orders);
        do_action('ppcart_sample_data_generated', ['products' => count($products), 'orders' => count($orders), 'subscriptions' => $subscriptions]);
        $this->redirect('generated');
    }

    public function remove()
    {
        $this->authorize('ppcart_remove_sample_data');
        $ids = get_posts([
            'post_type' => [ppcart_live_post_type('product'), ppcart_live_post_type('order'), ppcart_live_post_type('subscription')],
            'posts_per_page' => -1,
            'fields' => 'ids',
            'post_status' => 'any',
            'meta_key' => self::META_KEY,
            'meta_value' => '1',
        ]);
        foreach ($ids as $id) {
            wp_delete_post($id, true);
        }
        do_action('ppcart_sample_data_removed', $ids);
        $this->redirect('removed');
    }

    private function create_products()
    {
        $products = [];
        $definitions = [
            ['Creator Course', 149, 'one-time'], ['Monthly Membership', 29, 'recurring'], ['Coaching Package', 499, 'installment'],
            ['Design Template Bundle', 39, 'one-time'], ['Live Workshop', 79, 'one-time'], ['Free Starter Guide', 0, 'free'],
        ];
        foreach ($definitions as $index => $definition) {
            [$title, $price, $type] = $definition;
            $id = wp_insert_post(['post_title' => $title . ' (Sample)', 'post_type' => ppcart_live_post_type('product'), 'post_status' => 'publish'], true);
            if (is_wp_error($id)) { continue; }
            $plan = ['option_id' => 'sample-plan-' . $index, 'option_name' => $title, 'product_type' => $type === 'installment' ? 'recurring' : $type, 'price' => (string) $price, 'interval' => 'month', 'frequency' => 1, 'installments' => $type === 'installment' ? 3 : 0];
            ppcart_update_post_meta($id, 'pay_options', [$plan]);
            update_post_meta($id, self::META_KEY, '1');
            $products[] = ['id' => $id, 'name' => $title, 'price' => $price, 'plan' => $plan];
        }
        return $products;
    }

    private function create_order($product, $customer, $timestamp, $status, $index)
    {
        $amount = (float) $product['price'];
        $post_status = 'ppcart_' . ($status === 'pending-payment' ? 'pending' : $status);
        $id = wp_insert_post(['post_title' => $customer['name'] . ' (Sample)', 'post_type' => ppcart_live_post_type('order'), 'post_status' => $post_status, 'post_date_gmt' => gmdate('Y-m-d H:i:s', $timestamp), 'post_date' => get_date_from_gmt(gmdate('Y-m-d H:i:s', $timestamp))], true);
        if (is_wp_error($id)) { return 0; }
        $meta = ['status' => $status, 'payment_status' => $status, 'product_id' => $product['id'], 'product_name' => $product['name'], 'item_name' => $product['name'], 'option_id' => $product['plan']['option_id'], 'plan' => $product['plan'], 'amount' => $amount, 'invoice_total' => $amount, 'invoice_subtotal' => $amount, 'currency' => 'USD', 'first_name' => $customer['first_name'], 'last_name' => $customer['last_name'], 'customer_name' => $customer['name'], 'email' => $customer['email'], 'pay_method' => 'sample', 'transaction_id' => 'sample_' . $index];
        if (0 === $index % 10) { $meta['coupon_id'] = 'SAMPLE10'; }
        foreach ($meta as $key => $value) { ppcart_update_post_meta($id, $key, $value); }
        update_post_meta($id, self::META_KEY, '1');
        return $id;
    }

    private function create_subscription($order_id, $index)
    {
        if (! $order_id) { return; }
        $order = new PPCart_Order($order_id);
        $status = ['active', 'paused', 'canceled', 'completed'][$index % 4];
        $id = wp_insert_post(['post_title' => $order->customer_name . ' (Sample)', 'post_type' => ppcart_live_post_type('subscription'), 'post_status' => 'ppcart_' . $status], true);
        if (is_wp_error($id)) { return; }
        foreach (['customer_name', 'first_name', 'last_name', 'email', 'product_id', 'product_name', 'currency', 'amount'] as $key) { ppcart_update_post_meta($id, $key, $order->$key); }
        ppcart_update_post_meta($id, 'status', $status);
        ppcart_update_post_meta($id, 'sub_status', $status);
        ppcart_update_post_meta($id, 'first_order', $order_id);
        ppcart_update_post_meta($id, 'sub_amount', $order->amount);
        ppcart_update_post_meta($id, 'sub_interval', 'month');
        ppcart_update_post_meta($id, 'sub_frequency', 1);
        update_post_meta($id, self::META_KEY, '1');
    }

    private function customers()
    {
        $customers = [];
        for ($i = 1; $i <= 36; $i++) {
            $customers[] = ['first_name' => 'Sample', 'last_name' => 'Customer ' . $i, 'name' => 'Sample Customer ' . $i, 'email' => 'sample-customer-' . $i . '@example.test'];
        }
        return $customers;
    }

    private function count_sample_posts()
    {
        $query = new WP_Query(['post_type' => [ppcart_live_post_type('product'), ppcart_live_post_type('order'), ppcart_live_post_type('subscription')], 'posts_per_page' => 1, 'post_status' => 'any', 'meta_key' => self::META_KEY, 'meta_value' => '1', 'fields' => 'ids']);
        return (int) $query->found_posts;
    }

    private function is_safe_environment()
    {
        return in_array(wp_get_environment_type(), ['local', 'development', 'staging'], true);
    }

    private function authorize($action)
    {
        if (! current_user_can('manage_options')) { wp_die(esc_html__('You are not allowed to manage sample data.', 'publishpress-cart')); }
        check_admin_referer($action);
    }

    private function redirect($result)
    {
        wp_safe_redirect(add_query_arg(['page' => PPCart_Admin_Screens::PAGE_SAMPLE_DATA, 'ppcart_sample_data' => $result], admin_url('admin.php')));
        exit;
    }
}
