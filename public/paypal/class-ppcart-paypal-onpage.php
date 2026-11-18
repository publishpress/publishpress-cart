<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * On-page PayPal checkout: the buyer approves in a PayPal popup and stays on
 * the checkout page. Owns the switch, the storefront script, and the two AJAX
 * handlers the script calls.
 *
 * @package PPCart
 * @subpackage PPCart/public
 */
class PPCart_Paypal_Onpage
{
    public const OPTION_EXPERIENCE = '_ppcart_paypal_checkout_experience';
    public const OPTION_PLANS      = '_ppcart_paypal_plans';
    public const SDK_HANDLE        = 'ppcart-paypal-sdk';
    public const SCRIPT_HANDLE     = 'ppcart-paypal-onpage';
    public const APPROVE_RETRY_MAX = 8;
    public const CRON_HOOK         = 'ppcart_paypal_onpage_reconcile';
    private const RECONCILE_LIMIT  = 20;

    /**
     * @var PPCart_Paypal
     */
    private $paypal;

    /**
     * @var string
     */
    private $version;

    /**
     * @var bool
     */
    private $enqueued = false;

    public function __construct(PPCart_Paypal $paypal, $version)
    {
        $this->paypal  = $paypal;
        $this->version = (string) $version;

        add_action('wp_enqueue_scripts', [ $this, 'maybe_enqueue_scripts' ], 20);
        add_action('ppcart_enqueue_frontend_assets', [ $this, 'enqueue_scripts' ], 20);
        add_action('wp_ajax_ppcart_paypal_onpage_create', [ $this, 'ajax_create' ]);
        add_action('wp_ajax_nopriv_ppcart_paypal_onpage_create', [ $this, 'ajax_create' ]);
        add_action('wp_ajax_ppcart_paypal_onpage_approve', [ $this, 'ajax_approve' ]);
        add_action('wp_ajax_nopriv_ppcart_paypal_onpage_approve', [ $this, 'ajax_approve' ]);
        add_action('admin_init', [ $this, 'sync_schedule' ]);
        add_action(self::CRON_HOOK, [ $this, 'reconcile' ]);
    }

    /**
     * Keep the hourly reconcile job scheduled only while on-page checkout is active.
     *
     * @return void
     */
    public function sync_schedule()
    {
        $next = wp_next_scheduled(self::CRON_HOOK);
        if (self::is_active()) {
            if (! $next) {
                wp_schedule_event(time() + HOUR_IN_SECONDS, 'hourly', self::CRON_HOOK);
            }
        } elseif ($next) {
            wp_clear_scheduled_hook(self::CRON_HOOK);
        }
    }

    /**
     * Converge records that a lost webhook or a lost capture response left behind.
     *
     * @return void
     */
    public function reconcile()
    {
        $sync = new PPCart_Paypal_Sync();

        $orders = get_posts([
            'post_type'      => ppcart_query_post_types('order'),
            'post_status'    => PPCart_Order::$pending_str,
            'posts_per_page' => self::RECONCILE_LIMIT,
            'fields'         => 'ids',
            'orderby'        => 'date',
            'order'          => 'ASC',
            'date_query'     => [ [ 'after' => '48 hours ago' ] ],
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Bounded hourly lookup of on-page orders.
            'meta_query'     => [
                [
                    'key'   => ppcart_meta_key('paypal_onpage'),
                    'value' => '1',
                ],
            ],
        ]);
        foreach ($orders as $order_id) {
            $sync->sync_order((int) $order_id);
        }

        $subscriptions = get_posts([
            'post_type'      => ppcart_query_post_types('subscription'),
            'post_status'    => [ PPCart_Subscription::$active_str, PPCart_Subscription::$trial_str, PPCart_Subscription::$past_due_str ],
            'posts_per_page' => self::RECONCILE_LIMIT,
            'fields'         => 'ids',
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Bounded hourly lookup of PayPal subscriptions past their bill date.
            'meta_query'     => [
                [
                    'key'   => ppcart_meta_key('pay_method'),
                    'value' => 'paypal',
                ],
                [
                    'key'     => ppcart_meta_key('sub_next_bill_date'),
                    'value'   => time(),
                    'type'    => 'NUMERIC',
                    'compare' => '<',
                ],
            ],
        ]);
        foreach ($subscriptions as $sub_id) {
            $sync->sync_subscription((int) $sub_id);
        }
    }

    /**
     * Whether the on-page experience is selected and usable for the current mode.
     *
     * @return bool
     */
    public static function is_active()
    {
        if ('onpage' !== get_option(self::OPTION_EXPERIENCE, 'redirect')) {
            return false;
        }

        if (! get_option('_ppcart_paypal_enable')) {
            return false;
        }

        return PPCart_Paypal_Rest_Client::from_settings()->has_credentials();
    }

    /**
     * The cart stylesheet is enqueued only where checkout assets are needed.
     *
     * @return void
     */
    public function maybe_enqueue_scripts()
    {
        if (wp_style_is('ppcart', 'enqueued')) {
            $this->enqueue_scripts();
        }
    }

    /**
     * @return void
     */
    public function enqueue_scripts()
    {
        if ($this->enqueued || ! self::is_active()) {
            return;
        }
        $this->enqueued = true;

        $client  = PPCart_Paypal_Rest_Client::from_settings();
        $sdk_url = 'live' === $client->mode()
            ? 'https://www.paypal.com/web-sdk/v6/core'
            : 'https://www.sandbox.paypal.com/web-sdk/v6/core';

        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- PayPal controls this remote script URL.
        wp_enqueue_script(self::SDK_HANDLE, $sdk_url, [], null, true);
        wp_enqueue_script(
            self::SCRIPT_HANDLE,
            PPCART_BASE_URL . 'public/js/ppcart-paypal-onpage.js',
            [ 'jquery', 'ppcart', self::SDK_HANDLE ],
            $this->version,
            true
        );
        wp_localize_script(self::SCRIPT_HANDLE, 'ppcart_paypal_onpage', [
            'clientId' => $client->client_id(),
            'ajax'     => admin_url('admin-ajax.php'),
            'retryMax' => self::APPROVE_RETRY_MAX,
            'i18n'     => [
                'processing' => __('Your PayPal payment is being processed. You will receive a confirmation email when it completes.', 'publishpress-cart'),
                'error'      => __('We could not complete your PayPal payment. Please try again.', 'publishpress-cart'),
                'cancelled'  => __('PayPal checkout was cancelled.', 'publishpress-cart'),
                'retry'      => __('PayPal could not open on this page. Please click the button again.', 'publishpress-cart'),
            ],
        ]);
    }

    /**
     * Create the pending Cart record and the PayPal order or subscription.
     *
     * @return void
     */
    public function ajax_create()
    {
        $nonce = $this->posted_nonce();
        if ('' === $nonce || ! ppcart_verify_nonce($nonce, 'ppcart_purchase_nonce')) {
            $this->respond([ 'error' => __('An error occurred, please refresh the page and try again.', 'publishpress-cart') ]);
        }

        if (! self::is_active()) {
            $this->respond([ 'fallback' => 'redirect' ]);
        }

        $kind       = isset($_POST['kind']) ? sanitize_key(wp_unslash($_POST['kind'])) : '';
        $cancel_url = filter_input(INPUT_POST, 'cancel_url', FILTER_SANITIZE_URL);
        $cancel_url = esc_url_raw((string) $cancel_url);
        $page_url   = '' !== $cancel_url ? $cancel_url : home_url('/');

        list($order, $sub, $product_id) = $this->paypal->paypal_build_checkout_order();

        if ($kind !== ($sub ? 'subscription' : 'order')) {
            $this->respond([ 'fallback' => 'redirect' ]);
        }

        $currency = PPCart_Paypal_Sync::record_currency($order);
        $shape    = null;
        if ($sub) {
            $shape = PPCart_Paypal_Billing_Shape::build($sub, $order, $currency);
            if (null === $shape) {
                $this->respond([ 'fallback' => 'redirect' ]);
            }
        }

        $order_id   = $this->paypal->paypal_store_checkout_order($order, $sub, $product_id, $cancel_url);
        $request_id = wp_generate_uuid4();
        PPCart_Paypal_Sync::mark_onpage($order_id);
        ppcart_update_post_meta($order_id, 'paypal_request_id', $request_id);
        ppcart_update_post_meta($order_id, 'paypal_return_url', esc_url_raw((string) $order->return_url));

        $access = PPCart_Order::access_arg($order_id);
        $reply  = [
            'ppcart_order_id' => $order_id,
            'access'          => $access['ppcart-access'] ?? '',
        ];
        $client = PPCart_Paypal_Rest_Client::from_settings();

        if ($sub) {
            $paypal_sub_id = $this->create_subscription($client, $sub, $order, $shape, $request_id, $page_url);
            if ('' === $paypal_sub_id) {
                $this->respond([ 'error' => __('PayPal could not start this subscription. Please try again.', 'publishpress-cart') ]);
            }
            $sub->subscription_id = $paypal_sub_id;
            $sub->store();
            $reply['subscriptionId'] = $paypal_sub_id;
            $this->respond($reply);
        }

        $paypal_order_id = $this->create_order($client, $order, $currency, $request_id, $page_url);
        if ('' === $paypal_order_id) {
            $this->respond([ 'error' => __('PayPal could not start this payment. Please try again.', 'publishpress-cart') ]);
        }
        ppcart_update_post_meta($order_id, 'paypal_order_id', $paypal_order_id);
        $reply['orderId'] = $paypal_order_id;
        $this->respond($reply);
    }

    /**
     * After PayPal approval: reconcile from PayPal and answer with the return URL.
     *
     * @return void
     */
    public function ajax_approve()
    {
        $nonce = $this->posted_nonce();
        if ('' === $nonce || ! ppcart_verify_nonce($nonce, 'ppcart_purchase_nonce')) {
            $this->respond([ 'error' => __('An error occurred, please refresh the page and try again.', 'publishpress-cart') ]);
        }

        $order_id = isset($_POST['ppcart_order_id']) ? absint(wp_unslash($_POST['ppcart_order_id'])) : 0;
        $access   = isset($_POST['access']) ? sanitize_text_field(wp_unslash($_POST['access'])) : '';

        if (! $order_id || ! PPCart_Paypal_Sync::is_onpage_order($order_id) || ! PPCart_Order::token_matches($order_id, $access)) {
            $this->respond([ 'error' => __('An error occurred, please refresh the page and try again.', 'publishpress-cart') ]);
        }

        $sync   = new PPCart_Paypal_Sync();
        $result = $sync->sync_order($order_id);

        switch ($result) {
            case PPCart_Paypal_Sync::RESULT_COMPLETE:
                $url = esc_url_raw((string) ppcart_get_post_meta($order_id, 'paypal_return_url'));
                if ('' === $url) {
                    $this->respond([ 'error' => __('An error occurred, please refresh the page and try again.', 'publishpress-cart') ]);
                }
                $this->respond([ 'url' => $url ]);
                break;
            case PPCart_Paypal_Sync::RESULT_PROCESSING:
                $this->respond([ 'processing' => true ]);
                break;
            case PPCart_Paypal_Sync::RESULT_FAILED:
                $this->respond([ 'error' => __('PayPal did not complete this payment. Please try again.', 'publishpress-cart') ]);
                break;
        }

        $this->respond([ 'retry' => true ]);
    }

    private function create_order(PPCart_Paypal_Rest_Client $client, PPCart_Order $order, $currency, $request_id, $page_url)
    {
        $unit = [
            'custom_id' => 'ppcart:' . $order->id,
            'amount'    => [
                'currency_code' => $currency,
                'value'         => PPCart_Paypal_Billing_Shape::money($order->amount),
            ],
        ];
        $description = trim((string) $order->product_name);
        if ('' !== $description) {
            $unit['description'] = mb_substr($description, 0, 127);
        }

        $res = $client->request('POST', '/v2/checkout/orders', [
            'intent'         => 'CAPTURE',
            'purchase_units' => [ $unit ],
            'payment_source' => [
                'paypal' => [
                    'experience_context' => [
                        'shipping_preference' => 'NO_SHIPPING',
                        'user_action'         => 'PAY_NOW',
                        'return_url'          => $page_url,
                        'cancel_url'          => $page_url,
                    ],
                ],
            ],
        ], [ 'PayPal-Request-Id' => $request_id ]);

        return PPCart_Paypal_Sync::ok($res) && ! empty($res['body']['id']) ? sanitize_text_field((string) $res['body']['id']) : '';
    }

    private function create_subscription(PPCart_Paypal_Rest_Client $client, PPCart_Subscription $sub, PPCart_Order $order, $shape, $request_id, $page_url)
    {
        $plans = new PPCart_Paypal_Plans($client);

        for ($attempt = 0; $attempt < 2; $attempt++) {
            $plan_id = $plans->ensure($shape, (int) $sub->product_id, (string) $sub->product_name);
            if ('' === $plan_id) {
                return '';
            }

            $res = $client->request('POST', '/v1/billing/subscriptions', [
                'plan_id'             => $plan_id,
                'custom_id'           => 'ppcart:' . $order->id . ':' . $sub->id,
                'plan'                => [ 'billing_cycles' => PPCart_Paypal_Billing_Shape::subscription_billing_cycles($shape) ],
                'subscriber'          => [
                    'name'          => [
                        'given_name' => mb_substr((string) $order->first_name, 0, 140),
                        'surname'    => mb_substr((string) $order->last_name, 0, 140),
                    ],
                    'email_address' => (string) $order->email,
                ],
                'application_context' => [
                    'shipping_preference' => 'NO_SHIPPING',
                    'user_action'         => 'SUBSCRIBE_NOW',
                    'return_url'          => $page_url,
                    'cancel_url'          => $page_url,
                ],
            ], [ 'PayPal-Request-Id' => $request_id ]);

            if (PPCart_Paypal_Sync::ok($res) && ! empty($res['body']['id'])) {
                return sanitize_text_field((string) $res['body']['id']);
            }

            if (! $plans->rejected_plan($res)) {
                return '';
            }
            $plans->forget($shape, (int) $sub->product_id);
        }

        return '';
    }

    private function posted_nonce()
    {
        return isset($_POST['ppcart-nonce']) && is_string($_POST['ppcart-nonce']) // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reading the nonce field for immediate verification by the caller.
            ? sanitize_text_field(wp_unslash($_POST['ppcart-nonce'])) // phpcs:ignore WordPress.Security.NonceVerification.Missing -- The caller verifies the value at once.
            : '';
    }

    private function respond($data)
    {
        wp_send_json($data);
    }
}
