<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Order_Checkout_Input
{
    /**
     * Read the checkout payload, keeping only the fields declared for this form.
     *
     * Deliberately not cached: the payment controllers write $_POST['customerId'] back
     * mid-flow, and a retry can rewrite it again, so each read must see current values.
     *
     * @return array
     */
    private function get_posted_data()
    {
        return ppcart_parse_checkout_request();
    }

    public function setup_atts_from_post()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/order-checkout-input-setup-atts-from-post.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function load_from_post()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/order-checkout-input-load-from-post.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function add_main_item_from_post()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/order-checkout-input-add-main-item-from-post.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function add_line_item_from_post()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/order-checkout-input-add-line-item-from-post.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function load_coupon_from_post()
    {
        $posted = $this->get_posted_data();
        if (isset($posted['coupon_id']) && $posted['coupon_id'] != '') {
            $user_info = [
                'email' => $this->email,
                'ip' => $this->ip_address,
            ];
            $coupon_id = sanitize_text_field($posted['coupon_id']);
            $coupon = apply_filters('ppcart_order_load_coupon_from_post', false, $coupon_id, $this->product_id, $user_info, $this);
            if (is_array($coupon) && !isset($coupon['error']) && (empty($coupon['plan']) || in_array($this->option_id, $coupon['plan']))) {
                $this->coupon_id = $coupon_id;
                $this->coupon = $coupon;

                return true;
            }
        }
        return false;
    }

    public function apply_plan_coupon_to_items()
    {
        return apply_filters('ppcart_order_apply_plan_coupon', null, $this);
    }

    public function add_bump_items_from_post($bumps)
    {
        do_action('ppcart_order_add_bump_items_from_post', $this, $bumps, $this->get_posted_data());
    }

    public function apply_cart_coupon_to_items()
    {
        return apply_filters('ppcart_order_apply_cart_coupon', null, $this);
    }

    public function add_item($arr)
    {
        $this->items ??= [];
        $this->items[] = $this->maybe_apply_tax_to_item($arr);
    }
}
