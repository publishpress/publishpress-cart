<?php

/**
 * Guarded native payment actions with local previews and explicit failure state.
 *
 * @package PPCart
 * @since 1.1.0
 */

defined('ABSPATH') || exit;

class PPCart_Mcp_Payments
{
    private $records;

    /**
     * @since 1.1.0
     */
    public function __construct()
    {
        $this->records = new PPCart_Mcp_Records();
    }

    /**
     * Match the native refund/controller capability alternatives, without role checks.
     *
     * @since 1.1.0
     * @return bool
     */
    public static function can_manage()
    {
        return current_user_can('manage_options') || current_user_can(ppcart_live_cap('manage_orders'));
    }

    /**
     * @since 1.1.0
     * @param bool $refund Refund or cancellation.
     * @return array
     */
    public function schema($refund)
    {
        $fields = [
            'id' => ['type' => 'integer', 'minimum' => 1],
            'revision' => ['type' => 'string', 'minLength' => 64, 'maxLength' => 64],
            'dry_run' => ['type' => 'boolean'],
        ];
        if ($refund) {
            $fields['amount'] = ['type' => 'string', 'minLength' => 1, 'maxLength' => 30];
            $fields['restock'] = ['type' => 'boolean'];
        } else {
            $fields['timing'] = ['type' => 'string', 'enum' => ['immediate', 'period_end']];
        }
        return PPCart_Mcp_Input::schema($fields, $refund ? ['id', 'revision', 'amount'] : ['id', 'revision', 'timing']);
    }

    /**
     * Refund using the persisted order's gateway/mode, never caller-supplied credentials.
     *
     * @since 1.1.0
     * @param mixed $input Decoded JSON input.
     * @return array|WP_Error
     */
    public function refund($input)
    {
        if (! self::can_manage()) {
            return new WP_Error('ppcart_mcp_forbidden', __('You cannot manage Cart payments.', 'publishpress-cart'));
        }
        $input = PPCart_Mcp_Input::validate($input, $this->schema(true));
        if (is_wp_error($input)) {
            return $input;
        }
        $before = $this->state('order', $input);
        if (is_wp_error($before)) {
            return $before;
        }
        if (! preg_match('/^\d+(?:\.\d{1,8})?$/D', $input['amount'])) {
            return PPCart_Mcp_Input::error();
        }
        $amount = (float) $input['amount'];
        if (! is_finite($amount) || $amount <= 0 || $amount > (float) $before['net_amount']) {
            return PPCart_Mcp_Input::error();
        }
        $currency = (string) ($before['currency'] ?? '');
        $unit = ppcart_price_in_cents(1, $currency);
        $minor = ppcart_price_in_cents($amount, $currency);
        if (
            $currency !== (string) get_option('_ppcart_currency', 'USD') || $unit <= 0 || $minor <= 0
            || abs(($minor / $unit) - $amount) > 0.00000001
        ) {
            return PPCart_Mcp_Input::error();
        }
        $gateway = $before['pay_method'] ?? '';
        if (! in_array($gateway, ['free', 'cod', 'stripe'], true) && ! has_action('ppcart_order_refund_' . $gateway)) {
            return $this->unsupported();
        }
        if (! in_array($before['status'], [PPCart_Order::$paid_str, PPCart_Order::$completed_str], true)) {
            return $this->unsupported();
        }
        if (! empty($input['restock']) && (! $before['product_id'] || ! current_user_can('edit_post', (int) $before['product_id']))) {
            return new WP_Error('ppcart_mcp_forbidden', __('You cannot restock this product.', 'publishpress-cart'));
        }
        if (! empty($input['dry_run'])) {
            return ['dry_run' => true, 'order' => $before, 'amount' => $input['amount'], 'restock' => ! empty($input['restock'])];
        }
        return $this->locked('order', $input, function () use ($input, $before, $amount) {
            return $this->perform_refund($input, $before, $amount);
        });
    }

    /**
     * @since 1.1.0
     * @param array $input Validated request.
     * @param array $before Safe record snapshot.
     * @param float $amount Refund amount.
     * @return array|WP_Error
     */
    private function perform_refund(array $input, array $before, $amount)
    {
        // Gateway operations cannot safely be rolled back or retried automatically.
        try {
            $result = ppcart_order_refund([
                'id' => $input['id'], 'refund_amount' => $input['amount'],
                'idempotency_key' => 'ppcart-mcp-refund-' . (get_option('_ppcart_mcp_payment_lock_' . $input['id'])['token'] ?? ''),
                'restock' => ! empty($input['restock']) ? 'YES' : 'NO',
            ]);
        } catch (Throwable $error) {
            return $this->uncertain('order', $input['id']);
        }
        $after = $this->records->read(['kind' => 'order', 'id' => $input['id']]);
        if (
            'OK' !== $result || is_wp_error($after)
            || (float) $after['refund_total'] + 0.00000001 < (float) $before['refund_total'] + $amount
        ) {
            return $this->uncertain('order', $input['id']);
        }
        return ['refunded' => true, 'order' => $after];
    }

    /**
     * Cancel via the native service; combined cancellation/refund is intentionally separate.
     *
     * @since 1.1.0
     * @param mixed $input Decoded JSON input.
     * @return array|WP_Error
     */
    public function cancel($input)
    {
        if (! self::can_manage()) {
            return new WP_Error('ppcart_mcp_forbidden', __('You cannot manage Cart payments.', 'publishpress-cart'));
        }
        $input = PPCart_Mcp_Input::validate($input, $this->schema(false));
        if (is_wp_error($input)) {
            return $input;
        }
        $before = $this->state('subscription', $input);
        if (is_wp_error($before)) {
            return $before;
        }
        if (! in_array($before['status'], ['active', 'trialing', 'past_due', 'paused'], true)) {
            return $this->unsupported();
        }
        if ('stripe' !== ($before['pay_method'] ?? '') && ! has_filter('ppcart_cancel_subscription')) {
            return $this->unsupported();
        }
        if (! empty($input['dry_run'])) {
            return ['dry_run' => true, 'subscription' => $before, 'timing' => $input['timing']];
        }
        return $this->locked('subscription', $input, function () use ($input) {
            return $this->perform_cancel($input);
        });
    }

    /**
     * @since 1.1.0
     * @param array $input Validated request.
     * @return array|WP_Error
     */
    private function perform_cancel(array $input)
    {
        try {
            ppcart_do_cancel_subscription(
                new PPCart_Subscription($input['id']),
                false,
                'immediate' === $input['timing'],
                false,
                ['refund_action' => 'no_refund']
            );
        } catch (Throwable $error) {
            return $this->uncertain('subscription', $input['id']);
        }
        $after = $this->records->read(['kind' => 'subscription', 'id' => $input['id']]);
        $confirmed = ! is_wp_error($after) && ('immediate' === $input['timing']
            ? in_array($after['status'], ['canceled', 'cancelled'], true) : ! empty($after['cancel_at']));
        return $confirmed ? ['cancelled' => true, 'subscription' => $after] : $this->uncertain('subscription', $input['id']);
    }

    /** @since 1.1.0 @return array */
    public function transition_schema()
    {
        return PPCart_Mcp_Input::schema(['id' => ['type' => 'integer', 'minimum' => 1],
            'revision' => ['type' => 'string', 'minLength' => 64, 'maxLength' => 64], 'dry_run' => ['type' => 'boolean']], ['id', 'revision']);
    }

    /** @since 1.1.0 @param mixed $input Input. @return array|WP_Error */
    public function pause($input)
    {
        return $this->transition($input, 'paused');
    }

    /** @since 1.1.0 @param mixed $input Input. @return array|WP_Error */
    public function resume($input)
    {
        return $this->transition($input, 'started');
    }

    /** @since 1.1.0 @param mixed $input Input. @param string $type Transition. @return array|WP_Error */
    private function transition($input, $type)
    {
        if (! self::can_manage()) {
            return new WP_Error('ppcart_mcp_forbidden', __('You cannot manage Cart payments.', 'publishpress-cart'));
        }
        $input = PPCart_Mcp_Input::validate($input, $this->transition_schema());
        if (is_wp_error($input)) {
            return $input;
        }
        $before = $this->state('subscription', $input);
        if (is_wp_error($before)) {
            return $before;
        }
        $sub = new PPCart_Subscription($input['id']);
        if (! PPCart_Subscription_Actions::permitted($sub)) {
            return new WP_Error('ppcart_mcp_forbidden', __('You cannot change this subscription.', 'publishpress-cart'));
        }
        if (
            ('paused' === $type ? ! in_array($before['status'], ['active', 'trialing', 'past_due'], true) : 'paused' !== $before['status'])
            || ('stripe' !== $sub->pay_method && ! has_filter('ppcart_subscription_pause_restart'))
        ) {
            return $this->unsupported();
        }
        if (! empty($input['dry_run'])) {
            return ['dry_run' => true, 'subscription' => $before, 'status' => 'started' === $type ? 'active' : 'paused'];
        }
        return $this->locked('subscription', $input + ['transition' => $type], function () use ($input, $type) {
            $result = PPCart_Subscription_Actions::change($input['id'], $type);
            $after = $this->records->read(['kind' => 'subscription', 'id' => $input['id']]);
            if (is_wp_error($result) || is_wp_error($after) || ('paused' === $type ? 'paused' !== $after['status'] : ! in_array($after['status'], ['active', 'trialing'], true))) {
                return $this->uncertain('subscription', $input['id']);
            }
            return ['subscription' => $after];
        });
    }

    /**
     * Serialize payment actions and recheck the revision after acquiring the lock.
     * Locks are not automatically stolen: a stopped gateway call needs manual review.
     *
     * @since 1.1.0
     * @param string $kind Record kind.
     * @param array $input Validated request.
     * @param callable $operation Native action.
     * @return array|WP_Error
     */
    private function locked($kind, array $input, $operation)
    {
        $key = '_ppcart_mcp_payment_lock_' . $input['id'];
        $token = wp_generate_uuid4();
        $record = $this->records->read(['kind' => $kind, 'id' => $input['id']]);
        if (is_wp_error($record)) {
            return $record;
        }
        $operation_state = ['token' => $token, 'kind' => $kind, 'state' => 'running', 'created' => time(),
            'amount' => $input['amount'] ?? null, 'refund_total' => $record['refund_total'] ?? null,
            'transition' => $input['transition'] ?? null, 'timing' => $input['timing'] ?? null];
        if (! add_option($key, $operation_state, '', false)) {
            return new WP_Error('ppcart_mcp_payment_locked', __('A payment action is already in progress or needs review. Reconcile it before another action.', 'publishpress-cart'));
        }
        $release = true;
        try {
            $current = $this->state($kind, $input);
            $result = is_wp_error($current) ? $current : call_user_func($operation);
            $release = ! (is_wp_error($result) && 'ppcart_mcp_payment_unconfirmed' === $result->get_error_code());
            return $result;
        } catch (Throwable $error) {
            $release = false;
            return $this->uncertain($kind, $input['id']);
        } finally {
            $held = get_option($key);
            if (is_array($held) && ($held['token'] ?? '') === $token) {
                if ($release) {
                    delete_option($key);
                } else {
                    $held['state'] = 'unconfirmed';
                    update_option($key, $held, false);
                }
            }
        }
    }

    /**
     * @since 1.1.0
     * @param string $kind Record kind.
     * @param array $input Validated request.
     * @return array|WP_Error
     */
    private function state($kind, array $input)
    {
        $before = $this->records->read(['kind' => $kind, 'id' => $input['id']]);
        if (is_wp_error($before)) {
            return $before;
        }
        if (! hash_equals($before['revision'], $input['revision'])) {
            return new WP_Error('ppcart_mcp_stale_revision', __('The record changed. Read it again before continuing.', 'publishpress-cart'));
        }
        return $before;
    }

    /**
     * @since 1.1.0
     * @return WP_Error
     */
    private function unsupported()
    {
        return new WP_Error('ppcart_mcp_unsupported_state', __('This action is not supported for the current record state or gateway.', 'publishpress-cart'));
    }

    /**
     * @since 1.1.0
     * @param string $kind Record kind.
     * @param int $id Record identifier.
     * @return WP_Error
     */
    private function uncertain($kind, $id)
    {
        return new WP_Error(
            'ppcart_mcp_payment_unconfirmed',
            __('Payment action could not be confirmed. The gateway may have changed. Check the gateway and read the record before retrying.', 'publishpress-cart'),
            ['kind' => $kind, 'id' => $id]
        );
    }
}
