<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Empties a cart of the lines an order paid for.
 */
final class PPCart_Shopping_Cart_Completion
{
    /**
     * Runs after the status hook of an order. A paid cart order, or a cash on
     * delivery cart order that waits for payment, takes its lines out of the cart.
     * A line the buyer added after the checkout stays.
     */
    public static function remove_paid_lines(string $action, string $status, array $order_info, string $order_type): void
    {
        if ('cart' !== $order_type) {
            return;
        }
        $cash_on_delivery = 'ppcart_order_pending' === $action && 'cod' === ($order_info['pay_method'] ?? '');
        if ('ppcart_order_complete' !== $action && ! $cash_on_delivery) {
            return;
        }
        (new PPCart_Shopping_Cart_Store())->remove_paid_lines(
            absint($order_info['shopping_cart_id'] ?? 0),
            is_array($order_info['shopping_cart_lines'] ?? null) ? $order_info['shopping_cart_lines'] : []
        );
    }
}
