<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Order_Shopping_Cart
{
    /**
     * Build the order from a priced cart. Items and amounts come from the cart only.
     *
     * @param PPCart_Shopping_Cart_Priced $cart     The cart the buyer claimed.
     * @param array                       $customer Checkout fields, with the keys Buy now posts.
     */
    public function load_from_shopping_cart(PPCart_Shopping_Cart_Priced $cart, array $customer): void
    {
        $this->items = [];
        $this->checkout_type = PPCART_CHECKOUT_TYPE_CART;
        $this->product_id = 0;
        $this->option_id = 'cart';
        $this->plan_id = 'cart';
        $this->quantity = 1;

        $name = $cart->lines ? $cart->lines[0]->product_name : '';
        if ($cart->count() > 1) {
            /* translators: 1: name of the first product in the order, 2: number of other products. */
            $name = sprintf(__('%1$s + %2$d more', 'publishpress-cart'), $name, $cart->count() - 1);
        }
        $this->product_name = $name;
        $this->item_name = $name;
        $this->plan = ppcart_shopping_cart_order_plan(['checkout_type' => PPCART_CHECKOUT_TYPE_CART, 'item_name' => $name, 'amount' => $cart->subtotal()]);
        $this->shopping_cart_id = $cart->cart_id;
        $this->shopping_cart_lines = $cart->paid_lines();

        $this->apply_customer_fields($customer);
        $this->apply_address_fields($customer);

        foreach ($cart->lines as $line) {
            $this->add_item([
                'item_type'    => 'main',
                'product_id'   => $line->product_id,
                'price_id'     => $line->plan_id,
                'product_name' => $line->product_name,
                'price_name'   => $line->plan_name,
                'unit_price'   => $line->unit_price,
                'quantity'     => $line->quantity,
                'subtotal'     => $line->subtotal,
                'total_amount' => $line->subtotal,
            ]);
        }

        if (apply_filters('ppcart_shopping_cart_order_loaded', $this, $cart) !== $this) {
            _doing_it_wrong('PPCart_Order::load_from_shopping_cart', esc_html__('The ppcart_shopping_cart_order_loaded filter must return the order it received.', 'publishpress-cart'), '1.4.0');
        }

        $this->finalize_amounts(null);
        $this->main_offer_amt = $this->pre_tax_amount;
        $this->plan->price = $this->amount;
        $this->plan->initial_payment = $this->amount;
    }
}
