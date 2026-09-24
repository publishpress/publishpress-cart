<?php

if (! defined('ABSPATH')) {
    exit;
}


// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.SelfOutsideClass -- Included from PPCart_Subscription_Storage::store().

$og_sub = new self($this->id);

// set first order ID if missing
if (!$this->first_order) {
    $this->first_order();
}

if (isset($this->id) && !is_null($this->id) && (int)$this->id > 0) {
    $this->id = self::update($this);
} else {
    $this->id = self::create($this);
}

//do actions now
if (!$og_sub->id && $this->id || ($og_sub->status != $this->status)) {
    /* translators: %s: subscription status */
    ppcart_log_entry($this->id, sprintf(__('Subscription status updated to %s', 'publishpress-cart'), $this->status));

    $trigger_integrations = apply_filters('ppcart_trigger_subscription_integrations', $trigger_integrations, $this);
    if ($trigger_integrations && $this->status != self::$pending_str) {
        ppcart_trigger_integrations($this->status, $this->id);
    }
}

return $this->id;
