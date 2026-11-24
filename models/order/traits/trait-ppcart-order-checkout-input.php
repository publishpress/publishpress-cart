<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Order_Checkout_Input
{
    private $explicit_checkout_data = null;

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
        return null !== $this->explicit_checkout_data ? $this->explicit_checkout_data : ppcart_parse_checkout_request();
    }

    /**
     * Run native order construction with an explicit payload, without changing request globals.
     *
     * @param array $data Native checkout field names.
     * @return void
     */
    public function load_from_data(array $data)
    {
        $previous = $this->explicit_checkout_data;
        $this->explicit_checkout_data = ppcart_parse_checkout_request($data);
        try {
            $this->load_from_post();
        } finally {
            $this->explicit_checkout_data = $previous;
        }
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

    /**
     * Compute the order totals from its items. Buy now and cart checkout share this tail.
     *
     * @param object|null $product The checkout product. Null for a cart order.
     */
    public function finalize_amounts(?object $product): void
    {
        do_action('ppcart_order_pre_calculate_tax', $this);

        $this->calculate_pre_tax_amount_from_items();
        $this->calculate_tax_amount_from_items();
        $this->calculate_total_amount_from_items();

        $this->invoice_subtotal = $this->pre_tax_amount;

        $this->amount = apply_filters('ppcart_charge_amount', $this->amount, $product);
        $this->non_formatted_amount = $this->amount;
        $this->invoice_total = $this->amount;

        $this->calculate_final_amounts();
    }

    private function apply_customer_fields(array $posted): void
    {
        global $ppcart_currency;

        $this->email = (isset($posted['email'])) ? strtolower(sanitize_email($posted['email'])) : null;
        $curr_user_id = get_current_user_id();

        if (!$curr_user_id) {
            $user = get_user_by('email', $this->email);
            if ($user) {
                $curr_user_id = $user->ID;
            }
        }

        $this->first_name        = sanitize_text_field($posted['first_name'] ?? '');
        $this->last_name         = sanitize_text_field($posted['last_name'] ?? '');
        $this->firstname         = $this->first_name; // backwards compatibility
        $this->lastname          = $this->last_name; // backwards compatibility
        $this->customer_name     = $this->first_name . ' ' . $this->last_name;
        $this->customer_id       = sanitize_text_field($posted['customerId'] ?? '');
        $this->phone             = sanitize_text_field($posted['phone'] ?? '');
        $this->company           = sanitize_text_field($posted['company'] ?? '');
        $this->pay_method        = sanitize_text_field($posted['pay-method'] ?? '');
        $this->currency          = $ppcart_currency;
        $this->accept_terms      = (isset($posted['ppcart_accept_terms'])) ? sanitize_text_field(__('accepted', "publishpress-cart")) : null;
        $this->accept_privacy    = (isset($posted['ppcart_accept_privacy'])) ? sanitize_text_field(__('accepted', "publishpress-cart")) : null;
        $this->ip_address        = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : ''; // phpcs:ignore WordPressVIPMinimum.Variables.ServerVariables.UserControlledHeaders,WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___SERVER__REMOTE_ADDR__ -- IP is stored for fraud/audit metadata only.
        $this->user_account      = $curr_user_id;
        $this->vat_number        = sanitize_text_field($posted['vat-number'] ?? "");
    }

    private function apply_address_fields(array $posted): void
    {
        foreach (['address1', 'address2', 'city', 'state', 'zip', 'country'] as $info) {
            if (isset($posted[$info])) {
                $this->$info = sanitize_text_field($posted[$info]);
            }
        }
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
