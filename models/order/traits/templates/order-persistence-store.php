<?php

if (! defined('ABSPATH')) {
    exit;
}


global $ppcart_debug_logger;

$ppcart_debug_logger->log_event(
    'order.save.started',
    $this->id ? "Order #{$this->id} save started with status {$this->status}." : "New order save started with status {$this->status}.",
    [
    'order_id'   => (int) $this->id,
    'status'     => $this->status,
    'product_id' => (int) $this->product_id,
    'gateway'    => $this->pay_method,
    'transaction_id' => $this->transaction_id,
      ],
    0
);

$og_order = new self($this->id);
if ($og_order->id) {
    $ppcart_debug_logger->log_event(
        'order.save.existing_loaded',
        "Existing order #{$og_order->id} loaded before save.",
        [
        'order_id'        => (int) $og_order->id,
        'previous_status' => $og_order->status,
        'next_status'     => $this->status,
        'previous_transaction_id' => $og_order->transaction_id,
        'next_transaction_id' => $this->transaction_id,
        ],
        0
    );
}

if ($this->subscription_id) {
    $this->check_first_order();
    if (isset($this->renewal)) {
        $this->main_offer_amt = $this->amount;
        $this->set_invoice_number(); // Generate new invoice number for renewal orders.
    }
}

if (isset($this->id) && $this->id) {
    $this->id = self::update($this);
    /* translators: %1$s: previous order status, %2$s: new order status */
    $log_entry = sprintf(__('Order status updated from %1$s to %2$s', 'publishpress-cart'), $og_order->status, $this->status);
    $ppcart_debug_logger->log_event(
        'order.save.updated',
        "Order #{$this->id} updated in WordPress.",
        [
        'order_id'        => (int) $this->id,
        'previous_status' => $og_order->status,
        'status'          => $this->status,
        'previous_transaction_id' => $og_order->transaction_id,
        'transaction_id' => $this->transaction_id,
        ],
        0
    );
} else {
    if (!$this->invoice_number) {
        $this->set_invoice_number();
    }
    $this->id = self::create($this);
    $log_entry = __('Creating order.', 'publishpress-cart');
    $ppcart_debug_logger->log_event(
        'order.save.created',
        "Order #{$this->id} created in WordPress.",
        [
        'order_id' => (int) $this->id,
        'status'   => $this->status,
        ],
        0
    );
}

if (isset($this->renewal)) {
    ppcart_update_post_meta($this->id, 'renewal_order', 1);
}

if (!$og_order->id && $this->id || ($og_order->id && $og_order->status != $this->status)) {
    $ppcart_debug_logger->log_event(
        'order.integrations.triggering',
        "Running integrations for order #{$this->id} after status changed to {$this->status}.",
        [
        'order_id'        => (int) $this->id,
        'previous_status' => $og_order->status,
        'status'          => $this->status,
        ],
        0
    );

    ppcart_log_entry($this->id, $log_entry);

    $trigger_integrations = apply_filters('ppcart_trigger_order_integrations', $trigger_integrations, $this);
    if ($trigger_integrations) {
        $this->trigger_integrations();
    }
}

$ppcart_debug_logger->log_event(
    'order.save.completed',
    "Order #{$this->id} save completed.",
    [
    'order_id' => (int) $this->id,
    'status'   => $this->status,
      ],
    0
);

return $this->id;
