<?php

class PPCart_Stripe_Sync
{
    /**
     * @var array<int, string>
     */
    public static $synced_refund_ids = array();

    /**
     * @param mixed $refund
     * @return void
     */
    public static function sync_refund($refund)
    {
        self::$synced_refund_ids[] = is_object($refund) && isset($refund->id)
            ? $refund->id
            : (string) $refund;
    }
}
