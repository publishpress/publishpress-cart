<?php

/**
 * Shared native subscription pause/resume without AJAX output or request mutation.
 *
 * @package PPCart
 * @since 1.1.0
 */

defined('ABSPATH') || exit;

class PPCart_Subscription_Actions
{
    /** @since 1.1.0 @param PPCart_Subscription $sub Subscription. @return bool */
    public static function permitted($sub)
    {
        return current_user_can('manage_options') || (get_current_user_id() > 0
            && (int) $sub->user_account === get_current_user_id());
    }

    /** @since 1.1.0 @param int $id Subscription ID. @param string $type Native transition. @return bool|WP_Error */
    public static function change($id, $type)
    {
        if (
            ! in_array(get_post_type($id), ppcart_query_post_types('subscription'), true)
            || ! in_array($type, ['paused', 'started'], true)
        ) {
            return new WP_Error('ppcart_subscription_invalid', __('Invalid subscription action.', 'publishpress-cart'));
        }
        $sub = new PPCart_Subscription($id);
        if (! self::permitted($sub)) {
            return new WP_Error('ppcart_subscription_forbidden', __('Permission denied.', 'publishpress-cart'));
        }
        if ('paused' === $type ? ! in_array($sub->status, ['active', 'trialing', 'past_due'], true) : 'paused' !== $sub->status) {
            return new WP_Error('ppcart_subscription_state', __('The subscription cannot make this transition.', 'publishpress-cart'));
        }
        try {
            if ('stripe' === $sub->pay_method) {
                $response = 'started' === $type ? PPCart_Stripe_Sync::resume_subscription($sub) : PPCart_Stripe_Sync::pause_subscription($sub);
            } else {
                $response = apply_filters('ppcart_subscription_pause_restart', false, $sub, $type);
                if ($response) {
                    $sub->status = 'started' === $type ? 'active' : 'paused';
                    $sub->sub_status = $sub->status;
                    $sub->store();
                }
            }
            if (! $response) {
                return new WP_Error('ppcart_subscription_unconfirmed', __('The gateway did not confirm the subscription action. Inspect it before retrying.', 'publishpress-cart'));
            }
            $user = wp_get_current_user();
            $message = 'paused' === $type ? __('Subscription paused by %s', 'publishpress-cart') : __('Subscription started by %s', 'publishpress-cart');
            ppcart_log_entry($id, sprintf($message, $user->user_login));
            return true;
        } catch (Throwable $error) {
            return new WP_Error('ppcart_subscription_unconfirmed', __('The gateway outcome is unconfirmed. Inspect the subscription before retrying.', 'publishpress-cart'));
        }
    }
}
