<?php

declare(strict_types=1);

namespace Tests\Support\Integration;

use lucatume\WPBrowser\TestCase\WPTestCase;

abstract class CheckoutBlockTestCase extends WPTestCase
{
    protected $temporaryUserId = 0;

    protected $temporaryProductId = 0;

    protected $temporaryRecurringProductId = 0;

    protected $temporaryFilteredProductId = 0;

    protected $temporaryFeaturedAttachmentId = 0;

    protected $temporaryAccountPageId = 0;

    protected $temporaryAccountComponentPageId = 0;

    protected $temporaryAccountOrderId = 0;

    protected $temporaryAccountSubscriptionId = 0;

    protected $temporaryAccountPaymentPlanId = 0;

    protected $temporaryOtherUserId = 0;

    protected $temporaryOtherOrderId = 0;

    protected $temporaryOtherSubscriptionId = 0;

    protected $temporaryNavigationAdminId = 0;

    protected function setUp(): void
    {
        parent::setUp();

        if (function_exists('ppcart_checkout_reset_request_render_guard')) {
            ppcart_checkout_reset_request_render_guard();
        }

        if (! did_action('init')) {
            do_action('init');
        }
    }

    protected function tearDown(): void
    {
        $this->cleanupCheckoutBlockFixtures();
        parent::tearDown();
    }

    protected function ensureRestApiInit(): void
    {
        if (! did_action('rest_api_init')) {
            do_action('rest_api_init');
        }
    }

    protected function getRegisteredStyleSrc(string $handle): string
    {
        $styles = wp_styles();

        if (! $styles || empty($styles->registered[ $handle ])) {
            return '';
        }

        return (string) $styles->registered[ $handle ]->src;
    }

    protected function getRegisteredScriptSrc(string $handle): string
    {
        $scripts = wp_scripts();

        if (! $scripts || empty($scripts->registered[ $handle ])) {
            return '';
        }

        return (string) $scripts->registered[ $handle ]->src;
    }

    protected function getEnqueuedInlineCss(string $handle): string
    {
        $styles = wp_styles();

        if (! $styles) {
            return '';
        }

        $css = '';

        foreach (array($handle, $handle . '-inline') as $styleHandle) {
            $after = $styles->get_data($styleHandle, 'after');

            if (is_array($after)) {
                $css .= implode('', $after);
            }
        }

        return $css;
    }

    /**
     * @param array<int, string>|mixed $postTypes
     * @return array<int, string>
     */
    protected function includeFilteredProductType($postTypes)
    {
        $postTypes   = (array) $postTypes;
        $postTypes[] = 'ppcart_filter_prod';

        return array_values(array_unique($postTypes));
    }

    protected function renderBlocks(string $content): string
    {
        if (function_exists('ppcart_checkout_reset_request_render_guard')) {
            ppcart_checkout_reset_request_render_guard();
        }

        return do_blocks($content);
    }

    protected function renderBlocksWithProductContext(int $productId, string $content): string
    {
        return $this->withGlobalPost(
            $productId,
            function () use ($content) {
                return $this->renderBlocks($content);
            }
        );
    }

    protected function renderPostContent(string $content): string
    {
        if (function_exists('ppcart_checkout_reset_request_render_guard')) {
            ppcart_checkout_reset_request_render_guard();
        }

        return do_shortcode(do_blocks($content));
    }

    /**
     * @return array<string, mixed>
     */
    protected function metaDefaults(int $userId, string $productName): array
    {
        $user = get_userdata($userId);

        return array(
            'product_id'         => 0,
            'product_name'       => $productName,
            'item_name'          => 'Account Block Plan',
            'option_id'          => 'account_block_plan',
            'plan'               => (object) array(
                'name'      => 'Account Block Plan',
                'price'     => 25,
                'type'      => 'recurring',
                'stripe_id' => 'account_block_plan',
            ),
            'amount'             => 25,
            'main_offer_amt'     => 25,
            'pre_tax_amount'     => 25,
            'invoice_total'      => 25,
            'invoice_subtotal'   => 25,
            'sub_amount'         => 25,
            'sub_item_name'      => 'Account Block Plan',
            'sub_interval'       => 'month',
            'sub_frequency'      => 1,
            'sub_next_bill_date' => strtotime('+1 month'),
            'first_name'         => 'Account',
            'last_name'          => 'Customer',
            'customer_name'      => 'Account Customer',
            'email'              => $user ? $user->user_email : 'account-block@example.invalid',
            'user_account'       => $userId,
            'pay_method'         => 'cod',
            'currency'           => 'USD',
            'quantity'           => 1,
        );
    }

    protected function createAccountOrder(int $userId, string $productName): int
    {
        $orderId = wp_insert_post(
            array(
                'post_type'   => ppcart_live_post_type('order'),
                'post_status' => 'paid',
                'post_title'  => $productName . ' Order',
            )
        );

        if (! $orderId || is_wp_error($orderId)) {
            return 0;
        }

        foreach ($this->metaDefaults($userId, $productName) as $key => $value) {
            ppcart_update_post_meta($orderId, $key, $value);
        }
        ppcart_update_post_meta($orderId, 'status', 'paid');

        return (int) $orderId;
    }

    protected function createAccountSubscription(int $userId, string $productName, string $installments = '-1'): int
    {
        $subscriptionId = wp_insert_post(
            array(
                'post_type'   => ppcart_live_post_type('subscription'),
                'post_status' => 'active',
                'post_title'  => $productName . ' Subscription',
            )
        );

        if (! $subscriptionId || is_wp_error($subscriptionId)) {
            return 0;
        }

        foreach ($this->metaDefaults($userId, $productName) as $key => $value) {
            ppcart_update_post_meta($subscriptionId, $key, $value);
        }
        ppcart_update_post_meta($subscriptionId, 'status', 'active');
        ppcart_update_post_meta($subscriptionId, 'sub_status', 'active');
        ppcart_update_post_meta($subscriptionId, 'subscription_id', 'sub_account_block_' . $subscriptionId);
        ppcart_update_post_meta($subscriptionId, 'sub_installments', $installments);

        return (int) $subscriptionId;
    }

    protected function createStandardProduct(): int
    {
        $productId = wp_insert_post(
            array(
                'post_type'   => ppcart_live_post_type('product'),
                'post_status' => 'publish',
                'post_title'  => 'PublishPress Cart Block Test Product ' . wp_generate_uuid4(),
            )
        );

        if (! $productId || is_wp_error($productId)) {
            return 0;
        }

        update_post_meta(
            $productId,
            '_ppcart_pay_options',
            array(
                array(
                    'option_id'         => 'e2e_plan',
                    'option_name'       => 'E2E Plan',
                    'price'             => '100',
                    'frequency'         => '1',
                    'sale_frequency'    => '1',
                    'interval'          => 'day',
                    'sale_interval'     => 'day',
                    'installments'      => '-1',
                    'sale_installments' => '-1',
                    'stripe_plan_id'    => 'e2e_plan',
                ),
                array(
                    'option_id'         => 'e2e_plan_200',
                    'option_name'       => 'E2E Plan 200',
                    'price'             => '200',
                    'frequency'         => '1',
                    'sale_frequency'    => '1',
                    'interval'          => 'day',
                    'sale_interval'     => 'day',
                    'installments'      => '-1',
                    'sale_installments' => '-1',
                    'stripe_plan_id'    => 'e2e_plan_200',
                ),
            )
        );
        $this->seedProductCheckoutMeta((int) $productId, true);

        $this->temporaryProductId = (int) $productId;

        return (int) $productId;
    }

    protected function createRecurringProduct(): int
    {
        $productId = wp_insert_post(
            array(
                'post_type'   => ppcart_live_post_type('product'),
                'post_status' => 'publish',
                'post_title'  => 'PublishPress Cart Block Recurring Test Product ' . wp_generate_uuid4(),
            )
        );

        if (! $productId || is_wp_error($productId)) {
            return 0;
        }

        update_post_meta(
            $productId,
            '_ppcart_pay_options',
            array(
                array(
                    'option_id'         => 'recurring_plan',
                    'option_name'       => 'Recurring Plan',
                    'product_type'      => 'recurring',
                    'price'             => '100',
                    'frequency'         => '1',
                    'sale_frequency'    => '1',
                    'interval'          => 'month',
                    'sale_interval'     => 'month',
                    'installments'      => '-1',
                    'sale_installments' => '-1',
                    'stripe_plan_id'    => 'recurring_plan',
                ),
            )
        );
        $this->seedProductCheckoutMeta((int) $productId);

        $this->temporaryRecurringProductId = (int) $productId;

        return (int) $productId;
    }

    protected function ensureFilteredProductPostType(): void
    {
        if (! post_type_exists('ppcart_filter_prod')) {
            register_post_type(
                'ppcart_filter_prod',
                array(
                    'public'          => true,
                    'show_ui'         => false,
                    'capability_type' => 'post',
                    'supports'        => array('title', 'editor', 'thumbnail'),
                )
            );
        }
    }

    protected function createFilteredProduct(): int
    {
        $this->ensureFilteredProductPostType();

        $productId = wp_insert_post(
            array(
                'post_type'   => 'ppcart_filter_prod',
                'post_status' => 'publish',
                'post_title'  => 'PublishPress Cart Filtered Product ' . wp_generate_uuid4(),
            )
        );

        if (! $productId || is_wp_error($productId)) {
            return 0;
        }

        update_post_meta(
            $productId,
            '_ppcart_pay_options',
            array(
                array(
                    'option_id'      => 'filtered_plan',
                    'option_name'    => 'Filtered Plan',
                    'price'          => '100',
                    'frequency'      => '1',
                    'sale_frequency' => '1',
                    'interval'       => 'day',
                    'sale_interval'  => 'day',
                    'installments'   => '-1',
                ),
            )
        );
        $this->seedProductCheckoutMeta((int) $productId);

        $this->temporaryFilteredProductId = (int) $productId;

        return (int) $productId;
    }

    protected function seedProductCheckoutMeta(int $productId, bool $withStep1 = false): void
    {
        ppcart_update_post_meta($productId, 'plan_heading', 'Payment Plan');
        ppcart_update_post_meta($productId, 'button_color', '#000000');
        ppcart_update_post_meta($productId, 'button_text', 'Order Now');
        ppcart_update_post_meta($productId, 'checkout_ended_action', 'message');
        ppcart_update_post_meta($productId, 'checkout_ended_message', 'Sorry, this product is no longer for sale.');

        if ($withStep1) {
            ppcart_update_post_meta($productId, 'step1_button_label', 'Continue');
        }
    }

    protected function getProductIdString(): string
    {
        return $this->temporaryProductId ? (string) $this->temporaryProductId : '';
    }

    protected function getRecurringProductIdString(): string
    {
        return $this->temporaryRecurringProductId ? (string) $this->temporaryRecurringProductId : '';
    }

    protected function getFilteredProductIdString(): string
    {
        return $this->temporaryFilteredProductId ? (string) $this->temporaryFilteredProductId : '';
    }

    protected function withGlobalPost(int $postId, callable $callback)
    {
        $previousPost = isset($GLOBALS['post']) ? $GLOBALS['post'] : null;
        $GLOBALS['post'] = get_post($postId);
        setup_postdata($GLOBALS['post']);

        try {
            return $callback();
        } finally {
            wp_reset_postdata();
            if ($previousPost) {
                $GLOBALS['post'] = $previousPost;
            } else {
                unset($GLOBALS['post']);
            }
        }
    }

    /**
     * @param array<string, string> $queryParams
     * @return mixed
     */
    protected function withRequestContext(array $queryParams, string $requestUri, callable $callback)
    {
        $previousRequestUri = isset($_SERVER['REQUEST_URI'])
            ? sanitize_text_field(wp_unslash((string) $_SERVER['REQUEST_URI']))
            : null;
        $previousQuery = array();

        foreach ($queryParams as $key => $value) {
            $previousQuery[ $key ] = isset($_REQUEST[ $key ]) ? $_REQUEST[ $key ] : null;
            $_REQUEST[ $key ]        = $value;
        }

        $_SERVER['REQUEST_URI'] = $requestUri;

        try {
            return $callback();
        } finally {
            foreach ($queryParams as $key => $value) {
                if (null === $previousQuery[ $key ]) {
                    unset($_REQUEST[ $key ]);
                } else {
                    $_REQUEST[ $key ] = $previousQuery[ $key ];
                }
            }

            if (null === $previousRequestUri) {
                unset($_SERVER['REQUEST_URI']);
            } else {
                $_SERVER['REQUEST_URI'] = $previousRequestUri;
            }
        }
    }

    protected function ensureAccountFixtures(): void
    {
        if ($this->temporaryUserId) {
            return;
        }

        $userId = wp_insert_user(
            array(
                'user_login' => 'ppcart_block_limited_' . wp_generate_uuid4(),
                'user_pass'  => wp_generate_password(),
                'user_email' => 'ppcart-block-limited-' . wp_generate_uuid4() . '@example.invalid',
                'role'       => 'author',
            )
        );

        if (is_wp_error($userId)) {
            return;
        }

        $this->temporaryUserId = (int) $userId;

        $this->temporaryAccountOrderId        = $this->createAccountOrder(
            $this->temporaryUserId,
            'Account Block Order'
        );
        $this->temporaryAccountSubscriptionId = $this->createAccountSubscription(
            $this->temporaryUserId,
            'Account Block Subscription'
        );
        $this->temporaryAccountPaymentPlanId  = $this->createAccountSubscription(
            $this->temporaryUserId,
            'Account Block Payment Plan',
            '3'
        );

        $otherUserId = wp_insert_user(
            array(
                'user_login' => 'ppcart_account_other_' . wp_generate_uuid4(),
                'user_pass'  => wp_generate_password(),
                'user_email' => 'ppcart-account-other-' . wp_generate_uuid4() . '@example.invalid',
                'role'       => 'subscriber',
            )
        );

        if (! is_wp_error($otherUserId)) {
            $this->temporaryOtherUserId           = (int) $otherUserId;
            $this->temporaryOtherOrderId        = $this->createAccountOrder(
                $this->temporaryOtherUserId,
                'Other Account Block Order'
            );
            $this->temporaryOtherSubscriptionId = $this->createAccountSubscription(
                $this->temporaryOtherUserId,
                'Other Account Block Subscription'
            );
        }
    }

    protected function cleanupCheckoutBlockFixtures(): void
    {
        if ($this->temporaryUserId) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
            wp_delete_user($this->temporaryUserId);
            $this->temporaryUserId = 0;
        }

        if ($this->temporaryOtherUserId) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
            wp_delete_user($this->temporaryOtherUserId);
            $this->temporaryOtherUserId = 0;
        }

        if ($this->temporaryNavigationAdminId) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
            wp_delete_user($this->temporaryNavigationAdminId);
            $this->temporaryNavigationAdminId = 0;
        }

        if ($this->temporaryFeaturedAttachmentId) {
            wp_delete_attachment($this->temporaryFeaturedAttachmentId, true);
            $this->temporaryFeaturedAttachmentId = 0;
        }

        if ($this->temporaryProductId) {
            wp_delete_post($this->temporaryProductId, true);
            $this->temporaryProductId = 0;
        }

        if ($this->temporaryRecurringProductId) {
            wp_delete_post($this->temporaryRecurringProductId, true);
            $this->temporaryRecurringProductId = 0;
        }

        if ($this->temporaryFilteredProductId) {
            wp_delete_post($this->temporaryFilteredProductId, true);
            $this->temporaryFilteredProductId = 0;
        }

        if ($this->temporaryAccountPageId && ! is_wp_error($this->temporaryAccountPageId)) {
            wp_delete_post($this->temporaryAccountPageId, true);
            $this->temporaryAccountPageId = 0;
        }

        if ($this->temporaryAccountComponentPageId && ! is_wp_error($this->temporaryAccountComponentPageId)) {
            wp_delete_post($this->temporaryAccountComponentPageId, true);
            $this->temporaryAccountComponentPageId = 0;
        }

        if ($this->temporaryAccountOrderId) {
            wp_delete_post($this->temporaryAccountOrderId, true);
            $this->temporaryAccountOrderId = 0;
        }

        if ($this->temporaryAccountSubscriptionId) {
            wp_delete_post($this->temporaryAccountSubscriptionId, true);
            $this->temporaryAccountSubscriptionId = 0;
        }

        if ($this->temporaryAccountPaymentPlanId) {
            wp_delete_post($this->temporaryAccountPaymentPlanId, true);
            $this->temporaryAccountPaymentPlanId = 0;
        }

        if ($this->temporaryOtherOrderId) {
            wp_delete_post($this->temporaryOtherOrderId, true);
            $this->temporaryOtherOrderId = 0;
        }

        if ($this->temporaryOtherSubscriptionId) {
            wp_delete_post($this->temporaryOtherSubscriptionId, true);
            $this->temporaryOtherSubscriptionId = 0;
        }

        wp_set_current_user(0);
    }
}
