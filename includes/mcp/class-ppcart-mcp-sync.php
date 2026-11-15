<?php

/**
 * Native manual Stripe refresh with bounded affected-record authorization.
 *
 * @package PPCart
 * @since 1.1.0
 */

defined('ABSPATH') || exit;

class PPCart_Mcp_Sync
{
    private $products;

    /** @since 1.1.0 @param PPCart_Product_Metaboxes $products Native provider. */
    public function __construct($products)
    {
        $this->products = $products;
    }

    /** @since 1.1.0 @return bool */
    public function permitted()
    {
        if (! PPCart_Mcp_Payments::can_manage() || ! class_exists('PPCart_Stripe_Sync')) {
            return false;
        }
        foreach (['order', 'subscription'] as $kind) {
            $type = get_post_type_object(ppcart_live_post_type($kind));
            if (
                ! $type || ! current_user_can($type->cap->edit_posts) || ! current_user_can($type->cap->edit_others_posts)
                || ! current_user_can($type->cap->read_private_posts)
                || ('order' === $kind && (! current_user_can($type->cap->create_posts) || ! current_user_can($type->cap->publish_posts)))
            ) {
                return false;
            }
        }
        return true;
    }

    /** @since 1.1.0 @param bool $write Execute refresh. @return array */
    public function schema($write)
    {
        $fields = ['kind' => ['type' => 'string', 'enum' => ['order', 'subscription']], 'id' => ['type' => 'integer', 'minimum' => 1]];
        $required = ['kind', 'id'];
        if ($write) {
            $fields['revision'] = ['type' => 'string', 'minLength' => 64, 'maxLength' => 64];
            $fields['dry_run'] = ['type' => 'boolean'];
            $required[] = 'revision';
        }
        return PPCart_Mcp_Input::schema($fields, $required);
    }

    /** @since 1.1.0 @param mixed $input Input. @return array|WP_Error */
    public function read($input)
    {
        $state = $this->state($input);
        if (is_wp_error($state)) {
            return $state;
        }
        unset($state['ids']);
        return $state;
    }

    /** @since 1.1.0 @param mixed $input Input. @return array|WP_Error */
    private function state($input)
    {
        $input = PPCart_Mcp_Input::validate($input, $this->schema(false));
        if (is_wp_error($input)) {
            return $input;
        }
        if (! $this->permitted()) {
            return $this->forbidden();
        }
        $types = (new PPCart_Mcp_Records())->types();
        if (! in_array(get_post_type($input['id']), $types[$input['kind']], true)) {
            return $this->forbidden();
        }
        $model = 'order' === $input['kind'] ? new PPCart_Order($input['id']) : new PPCart_Subscription($input['id']);
        $mode = $model->gateway_mode ?: ($model->stripe_mode ?? '');
        if (! PPCart_Stripe_Sync::is_stripe_backed_record($model) || ! in_array($mode, ['live', 'test'], true)) {
            return new WP_Error('ppcart_mcp_unsupported', __('Native Stripe synchronization requires a stored gateway identity and mode.', 'publishpress-cart'));
        }
        $keys = ['transaction_id', 'stripe_charge_id', 'subscription_id'];
        $where = ['relation' => 'OR'];
        foreach ($keys as $key) {
            if (! empty($model->$key)) {
                foreach ($keys as $target) {
                    $where[] = ['key' => ppcart_meta_key($target), 'value' => (string) $model->$key];
                }
            }
        }
        if ('subscription' === $input['kind']) {
            $where[] = ['key' => ppcart_meta_key('subscription_id'), 'value' => (string) $input['id']];
        }
        if (count($where) < 2) {
            return PPCart_Mcp_Input::error();
        }
        $ids = get_posts(['post_type' => array_merge($types['order'], $types['subscription']), 'post_status' => array_keys(get_post_stati()),
        // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page,WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- ID-only authorization scan has a strict 1,000-record ceiling and detects one overflow row.
            'posts_per_page' => 1001, 'fields' => 'ids', 'meta_query' => $where, 'orderby' => 'ID', 'order' => 'ASC']);
        $ids[] = $input['id'];
        $ids = array_values(array_unique($ids));
        if (count($ids) > 1000) {
            return new WP_Error('ppcart_mcp_scope_limit', __('The Stripe synchronization scope exceeds 1,000 records.', 'publishpress-cart'));
        }
        $snapshots = [];
        foreach ($ids as $id) {
            $valid = $this->record_access($id);
            if (is_wp_error($valid)) {
                return $valid;
            }
            $snapshots[$id] = [get_post($id), get_post_meta($id)];
        }
        return ['id' => $input['id'], 'kind' => $input['kind'], 'gateway_mode' => $mode, 'affected_records' => count($ids), 'ids' => $ids,
            'revision' => hash_hmac('sha256', wp_json_encode($snapshots), wp_salt('nonce'))];
    }

    /** @since 1.1.0 @param int $id Native record. @return true|WP_Error */
    private function record_access($id)
    {
        if (! current_user_can('read_post', $id) || ! current_user_can('edit_post', $id)) {
            return $this->forbidden();
        }
        $record = ppcart_is_order_post_type(get_post_type($id)) ? new PPCart_Order($id) : new PPCart_Subscription($id);
        if ($record->user_account && (! current_user_can('list_users') || ! current_user_can('edit_user', (int) $record->user_account))) {
            return $this->forbidden();
        }
        if ($record->product_id) {
            $product = (int) $record->product_id;
            if (! current_user_can('read_post', $product) || ! current_user_can('edit_post', $product)) {
                return $this->forbidden();
            }
            $check = (new PPCart_Mcp_Order_Admin($this->products))->integrations($product);
            if (is_wp_error($check)) {
                return $check;
            }
        }
        return true;
    }

    /** @since 1.1.0 @param mixed $input Input. @return array|WP_Error */
    public function update($input)
    {
        $input = PPCart_Mcp_Input::validate($input, $this->schema(true));
        if (is_wp_error($input)) {
            return $input;
        }
        $before = $this->state(['id' => $input['id'], 'kind' => $input['kind']]);
        if (is_wp_error($before)) {
            return $before;
        }
        if (! hash_equals($before['revision'], $input['revision'])) {
            return new WP_Error('ppcart_mcp_stale_revision', __('Related Stripe records changed. Read the synchronization scope again.', 'publishpress-cart'));
        }
        if (! empty($input['dry_run'])) {
            unset($before['ids']);
            return $before + ['dry_run' => true];
        }
        $guard = new PPCart_Mcp_Sync_Guard($before['ids'], $this->products);
        $guard->register();
        try {
            if ('order' === $input['kind']) {
                PPCart_Stripe_Sync::sync_order_by_local(new PPCart_Order($input['id']));
            } else {
                PPCart_Stripe_Sync::sync_subscription_by_local(new PPCart_Subscription($input['id']));
            }
        } catch (Throwable $error) {
            return new WP_Error('ppcart_mcp_sync_unconfirmed', __('Native Stripe synchronization could not be confirmed. Inspect Cart and the provider before retrying.', 'publishpress-cart'));
        } finally {
            $guard->unregister();
        }
        return ['synced' => true, 'id' => $input['id'], 'kind' => $input['kind']];
    }

    /** @since 1.1.0 @return WP_Error */
    private function forbidden()
    {
        return new WP_Error('ppcart_mcp_forbidden', __('You cannot synchronize this record or a related object.', 'publishpress-cart'));
    }
}
