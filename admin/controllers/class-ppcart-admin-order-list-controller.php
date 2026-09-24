<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Manages order and subscription list table columns, filters, bulk actions, and statuses.
 *
 * @package PPCart
 * @subpackage PPCart/admin
 */
require_once plugin_dir_path(__FILE__) . 'order/traits/trait-ppcart-admin-order-query.php';

class PPCart_Admin_Order_List_Controller
{
    use PPCart_Admin_Order_Query_Trait;

    /** @var string */
    private $plugin_name;

    /** @var string */
    private $plugin_title;

    /** @var string */
    private $version;

    /** @var \PublishPress\Stripe\StripeClient|null */
    private $stripe;

    public function __construct($plugin_name, $plugin_title, $version)
    {
        global $ppcart_stripe;

        $this->stripe = empty($ppcart_stripe['sk']) ? null : ppcart_stripe_client($ppcart_stripe['sk']);
        $this->plugin_name = $plugin_name;
        $this->plugin_title = $plugin_title;
        $this->version = $version;
    }

    public function modify_order_details($data)
    {
        if (ppcart_is_order_post_type($data['post_type']) && isset($_POST['_ppcart_firstname']) && isset($_POST['post_ID'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- wp_insert_post_data filter; WordPress core verifies nonce on post save.
            $order_title = sanitize_title("#" . sanitize_text_field(wp_unslash($_POST['post_ID'])) . " " . sanitize_text_field(wp_unslash($_POST['_ppcart_firstname'])) . " " . (isset($_POST['_ppcart_lastname']) ? sanitize_text_field(wp_unslash($_POST['_ppcart_lastname'])) : '')); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- wp_insert_post_data filter.
            $data['post_title'] =  $order_title; //Updates the post title to your new title.
        }

        return $data; // Returns the modified data.
    }

    public function load_edit_php_action()
    {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- These are read-only admin list filters using query string parameters.
        $post_type = isset($_GET['post_type']) ? sanitize_key(wp_unslash($_GET['post_type'])) : '';
        $related_orders = isset($_GET['subscription_related_orders']) ? sanitize_text_field(wp_unslash($_GET['subscription_related_orders'])) : '';
        $order_email = isset($_GET['order_email']) ? sanitize_text_field(wp_unslash($_GET['order_email'])) : '';
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        if (ppcart_is_order_post_type($post_type) && '' !== $related_orders) {
            add_action('pre_get_posts', [$this, 'order_related_orders_filter']);
        } elseif (ppcart_is_order_post_type($post_type) && '' !== $order_email) {
            add_action('pre_get_posts', [$this, 'order_email_filter']);
        }
    }

    public function set_custom_edit_product_columns($columns)
    {
        $columns['shortcode'] = __('Shortcode', 'publishpress-cart');

        return $columns;
    }

    public function custom_product_column($column, $post_id)
    {
        switch ($column) {
            case 'shortcode':
                echo '<code>[ppcart_form id="' . esc_attr($post_id) . '"]</code>';
                break;
        }
    }

    public function set_custom_edit_order_columns($columns)
    {
        unset($columns['title'], $columns['author'], $columns['date']);
        $columns['order'] = __('Order #', 'publishpress-cart');
        $columns['order_date'] = __('Date', 'publishpress-cart');
        $columns['amount'] = __('Amount', 'publishpress-cart');
        $columns['status'] = __('Status', 'publishpress-cart');
        $columns['product'] = __('Product', 'publishpress-cart');
        $columns['name'] = __('Name', 'publishpress-cart');
        $columns['email'] = __('Email', 'publishpress-cart');
        if (get_option('_ppcart_enable_invoice_number', false)) {
            $columns['invoice_number'] = __('Invoice Number', 'publishpress-cart');
        }

        return $columns;
    }

    public function custom_order_column($column, $post_id)
    {
        include __DIR__ . '/templates/order-list-order-column.php';
    }

    public function set_custom_edit_subscription_columns($columns)
    {
        unset($columns['title'], $columns['author'], $columns['date']);
        $columns['sub_id'] = __('Sub ID', 'publishpress-cart');
        $columns['status'] = __('Status', 'publishpress-cart');
        $columns['amount'] = __('Amount', 'publishpress-cart');
        $columns['start_date'] = __('Start Date', 'publishpress-cart');
        $columns['next_payment'] = __('Next Payment', 'publishpress-cart');
        $columns['product'] = __('Product', 'publishpress-cart');
        $columns['name'] = __('Name', 'publishpress-cart');
        $columns['email'] = __('Email', 'publishpress-cart');
        return $columns;
    }

    public function custom_subscription_column($column, $post_id)
    {
        include __DIR__ . '/templates/order-list-subscription-column.php';
    }

    public function order_sortable_columns($columns)
    {
        $columns['order'] = 'ID';
        $columns['order_date'] = 'date';
        $columns['start_date'] = 'date';
        $columns['email'] = 'email';
        $columns['amount'] = 'amount';

        return $columns;
    }

    public function order_sortable_columns_orderby($query)
    {
        if (! is_admin()) {
            return;
        }

        $orderby = $query->get('orderby');

        if ('email' == $orderby) {
            $query->set('meta_key', ppcart_meta_key('email'));
            $query->set('orderby', 'meta_value');
        }

        if ('amount' == $orderby) {
            $query->set('meta_key', ppcart_meta_key('amount'));
            $query->set('orderby', 'meta_value_num');
            $query->set('meta_type', 'numeric');
        }
    }

    public function order_bulk_action($bulk_array)
    {
        $bulk_array['ppcart_make_paid'] = __('Update status to paid', 'publishpress-cart');
        $bulk_array['ppcart_make_failed'] = __('Update status to failed', 'publishpress-cart');
        $bulk_array['ppcart_make_pending'] = __('Update status to pending', 'publishpress-cart');
        $bulk_array['ppcart_make_completed'] = __('Update status to completed', 'publishpress-cart');
        return $bulk_array;
    }

    public function subscription_bulk_action($bulk_array)
    {
        $bulk_array['ppcart_make_active'] = __('Resume in Stripe / update active', 'publishpress-cart');
        $bulk_array['ppcart_make_canceled'] = __('Update status to canceled', 'publishpress-cart');
        $bulk_array['ppcart_make_completed'] = __('Update status to completed', 'publishpress-cart');
        $bulk_array['ppcart_sync_stripe'] = __('Sync with Stripe', 'publishpress-cart');
        return $bulk_array;
    }

    public function bulk_action_handler($redirect, $doaction, $object_ids)
    {
        return include __DIR__ . '/templates/order-list-bulk-action-handler.php';
    }

    /**
     * Report the outcome of a bulk Stripe sync.
     *
     * The batch is capped, so a selection larger than the cap is only partly
     * synced. Saying nothing would leave the rest silently untouched.
     *
     * @return void
     */
    public function sync_stripe_bulk_notice()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only notice routing after a completed admin action.
        if (! isset($_GET['bulk_ppcart_sync_stripe'])) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only notice routing after a completed admin action.
        $synced = absint(wp_unslash($_GET['bulk_ppcart_sync_stripe']));
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only notice routing after a completed admin action.
        $failed = isset($_GET['bulk_ppcart_sync_stripe_failed']) ? absint(wp_unslash($_GET['bulk_ppcart_sync_stripe_failed'])) : 0;

        $message = sprintf(
            /* translators: %s: number of subscriptions synced. */
            _n(
                '%s subscription synced from Stripe.',
                '%s subscriptions synced from Stripe.',
                $synced,
                'publishpress-cart'
            ),
            number_format_i18n($synced)
        );

        if ($failed) {
            $message .= ' ' . sprintf(
                /* translators: %s: number of subscriptions that failed to sync. */
                _n(
                    '%s failed; see its subscription log for the reason.',
                    '%s failed; see their subscription logs for the reason.',
                    $failed,
                    'publishpress-cart'
                ),
                number_format_i18n($failed)
            );
        }

        $message .= ' ' . __('Each run syncs a limited number of subscriptions. Select the rest and run it again to continue.', 'publishpress-cart');

        printf(
            '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
            $failed ? 'warning' : 'success',
            esc_html($message)
        );
    }

    public function order_custom_status()
    {
        include __DIR__ . '/templates/order-list-custom-status.php';
    }

    public function order_remove_statuses($views)
    {
        $remove_views = ['mine', 'publish', 'future', 'sticky', 'draft', 'pending'];

        foreach ((array) $remove_views as $view) {
            if (isset($views[$view])) {
                unset($views[$view]);
            }
        }
        return $views;
    }
}
