<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Public_Account_Shortcodes_Trait
{
    private function verify_user_access($id, $type = 'subscription')
    {
        if (!is_user_logged_in()) {
            return false;
        }

        // Get the appropriate object based on type
        $object = ($type === 'subscription') ? new PPCart_Subscription($id) : new PPCart_Order($id);
        $data = $object->get_data();

        // Get user_id from user_account field
        $user_id = null;
        if (is_array($data) && isset($data['user_account'])) {
            $user_id = $data['user_account'];
        } elseif (is_object($data) && isset($data->user_account)) {
            $user_id = $data->user_account;
        }

        return !empty($user_id) && get_current_user_id() == intval($user_id);
    }

    /**
     * Resolve a detail record ID the same way the detail templates do:
     * the request parameter first, then the shortcode attribute.
     *
     * @param string       $request_key Request parameter name.
     * @param string       $attr_key    Shortcode attribute name.
     * @param array|string $attr        Shortcode attributes.
     * @return int
     */
    private function resolve_account_detail_id($request_key, $attr_key, $attr)
    {
        $request_id = ppcart_filter_input_request($request_key, FILTER_VALIDATE_INT);
        if (false !== $request_id && null !== $request_id) {
            return absint($request_id);
        }

        if (is_array($attr) && ! empty($attr[ $attr_key ])) {
            return absint($attr[ $attr_key ]);
        }

        return 0;
    }

    public function order_detail_shortcode($attr, $content = null)
    {
        if (! is_user_logged_in()) {
            return;
        }

        $order_id = $this->resolve_account_detail_id('ppcart-order', 'order', $attr);
        if (! $order_id || ! $this->verify_user_access($order_id, 'order')) {
            return;
        }

        $attr          = is_array($attr) ? $attr : [];
        $attr['order'] = $order_id;

        do_action('ppcart_enqueue_frontend_assets');
        return ppcart_kses_frontend_html(ppcart_get_template('my-account/order', 'detail', $attr));
    }

    public function subscription_detail_shortcode($attr, $content = null)
    {
        if (! is_user_logged_in()) {
            return;
        }

        $plan_id = $this->resolve_account_detail_id('ppcart-plan', 'plan', $attr);
        if (! $plan_id || ! $this->verify_user_access($plan_id, 'subscription')) {
            return;
        }

        $attr         = is_array($attr) ? $attr : [];
        $attr['plan'] = $plan_id;

        do_action('ppcart_enqueue_frontend_assets');
        return ppcart_kses_frontend_html(ppcart_get_template('my-account/subscription', 'detail', $attr));
    }

    public function my_account_page_link_shortcode($attr, $content = null)
    {
        $defaults = ['label' => __('My Account', 'publishpress-cart')];
        $attr     = shortcode_atts($defaults, $attr);
        $label    = $attr['label'];

        if (
            get_the_ID() != get_option('_ppcart_myaccount_page_id')
            && is_string($this->my_account_url)
            && '' !== $this->my_account_url
        ) {
            return '<a href="' . esc_url($this->my_account_url) . '">' . esc_html($label) . '</a>';
        }

        return false;
    }

    public function add_shortcode_specific_body_class($classes)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/account-shortcodes-add-shortcode-specific-body-class.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function my_account_page_shortcode($attr, $content = null)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/account-shortcodes-my-account-page-shortcode.php';
        return 1 === $__ppcart_template_result ? null : ppcart_kses_frontend_html($__ppcart_template_result);
    }
}
