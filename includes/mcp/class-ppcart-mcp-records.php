<?php

/**
 * Bounded native Cart record discovery without credentials or bearer URLs.
 *
 * @package PPCart
 * @since 1.1.0
 */

defined('ABSPATH') || exit;

class PPCart_Mcp_Records
{
    /**
     * @since 1.1.0
     * @return array
     */
    public function types()
    {
        return apply_filters('ppcart_mcp_record_types', [
            'product' => ppcart_query_post_types('product'),
            'order' => ppcart_query_post_types('order'),
            'subscription' => ppcart_query_post_types('subscription'),
        ]);
    }

    /**
     * @since 1.1.0
     * @param bool $single Single-record request.
     * @return array
     */
    public function schema($single = false)
    {
        $properties = ['kind' => ['type' => 'string', 'enum' => array_keys($this->types())]];
        if ($single) {
            $properties['id'] = ['type' => 'integer', 'minimum' => 1];
        } else {
            $properties['search'] = ['type' => 'string', 'maxLength' => 200];
            $properties['page'] = ['type' => 'integer', 'minimum' => 1, 'maximum' => 10000];
            $properties['per_page'] = ['type' => 'integer', 'minimum' => 1, 'maximum' => 50];
        }
        return PPCart_Mcp_Input::schema($properties, $single ? ['kind', 'id'] : ['kind']);
    }

    /**
     * Recheck native operation capabilities even for Administrators.
     *
     * @since 1.1.0
     * @param string $kind Record family.
     * @return bool
     */
    public function permitted($kind)
    {
        $types = $this->types();
        if (! isset($types[$kind]) || ! $types[$kind]) {
            return false;
        }
        foreach ($types[$kind] as $name) {
            $type = get_post_type_object($name);
            if ($type && current_user_can($type->cap->edit_posts)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @since 1.1.0
     * @param mixed $input Decoded JSON.
     * @return array|WP_Error
     */
    public function listing($input)
    {
        $input = PPCart_Mcp_Input::validate($input, $this->schema());
        if (is_wp_error($input)) {
            return $input;
        }
        if (! $this->permitted($input['kind'])) {
            return $this->forbidden();
        }
        $query = new WP_Query([
            'post_type' => $this->types()[$input['kind']], 'post_status' => 'any',
            's' => $input['search'] ?? '', 'paged' => $input['page'] ?? 1,
            'posts_per_page' => $input['per_page'] ?? 20,
            'orderby' => 'ID', 'order' => 'ASC', 'fields' => 'ids',
            'no_found_rows' => true,
        ]);
        $records = [];
        foreach ($query->posts as $id) {
            if (! current_user_can('edit_post', $id) || ! current_user_can('read_post', $id)) {
                continue;
            }
            $post = get_post($id);
            $records[] = ['id' => $id, 'title' => $post->post_title, 'status' => $post->post_status];
        }
        // Do not expose counts for records the caller is not allowed to access.
        return ['kind' => $input['kind'], 'page' => $input['page'] ?? 1, 'records' => $records];
    }

    /**
     * @since 1.1.0
     * @param mixed $input Decoded JSON.
     * @return array|WP_Error
     */
    public function read($input)
    {
        $input = PPCart_Mcp_Input::validate($input, $this->schema(true));
        if (is_wp_error($input)) {
            return $input;
        }
        $post = get_post($input['id']);
        if (
            ! $post || ! $this->permitted($input['kind']) || ! current_user_can('edit_post', $post->ID) || ! current_user_can('read_post', $post->ID)
            || ! in_array($post->post_type, $this->types()[$input['kind']], true)
        ) {
            return $this->forbidden();
        }
        $record = ['id' => $post->ID, 'kind' => $input['kind'], 'title' => $post->post_title, 'status' => $post->post_status];
        if (in_array($input['kind'], ['order', 'subscription'], true)) {
            $native = 'order' === $input['kind'] ? new PPCart_Order($post->ID) : new PPCart_Subscription($post->ID);
            // Never serialize entire models: they contain receipt tokens, addresses and gateway identifiers.
            foreach (
                [
                'status', 'product_id', 'product_name', 'item_name', 'quantity', 'amount', 'currency',
                'tax_amount', 'shipping_amount', 'pay_method', 'gateway_mode', 'user_account',
                'first_name', 'last_name', 'email', 'option_id', 'plan_id',
                'sub_status', 'sub_amount', 'sub_interval', 'sub_frequency', 'sub_installments',
                'sub_next_bill_date', 'sub_end_date', 'cancel_at',
                ] as $key
            ) {
                $value = $native->$key ?? null;
                if (is_scalar($value) || null === $value) {
                    $record[$key] = $value;
                }
            }
            if ('order' === $input['kind']) {
                $record['refund_total'] = PPCart_Order_Refunds::get_refund_total($post->ID);
                $record['net_amount'] = PPCart_Order_Refunds::get_net_amount($post->ID);
            }
        } else {
            $record['content'] = $post->post_content;
            $record['excerpt'] = $post->post_excerpt;
            $record['slug'] = $post->post_name;
            $image = (int) get_post_thumbnail_id($post->ID);
            $record['featured_image_id'] = $image && ! current_user_can('read_post', $image) ? null : $image;
        }
        $record['revision'] = hash_hmac('sha256', wp_json_encode([$record, get_post_thumbnail_id($post->ID)]), wp_salt('nonce'));
        return $record;
    }

    /**
     * @since 1.1.0
     * @return WP_Error
     */
    private function forbidden()
    {
        return new WP_Error('ppcart_mcp_forbidden', __('You cannot access these Cart records.', 'publishpress-cart'));
    }
}
