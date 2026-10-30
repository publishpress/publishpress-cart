<?php

/**
 * Native payment plan configuration and gateway synchronization.
 *
 * @package PPCart
 * @since 1.1.0
 */

defined('ABSPATH') || exit;

class PPCart_Mcp_Plans
{
    private $products;
    private $permissions;

    /**
     * @since 1.1.0
     * @param PPCart_Product_Metaboxes $products Native controls.
     */
    public function __construct($products)
    {
        $this->products = $products;
        $this->permissions = new PPCart_Mcp_Products($products);
    }

    /**
     * Never allow callers to replace hidden gateway identifiers.
     *
     * @since 1.1.0
     * @return array
     */
    public function definitions()
    {
        $allowed = apply_filters('ppcart_mcp_plan_field_ids', ['option_id', 'product_type', 'url_slug', 'option_name', 'sale_option_name', 'price', 'sale_price', 'frequency', 'sale_frequency', 'interval', 'sale_interval', 'installments', 'sale_installments', 'name_your_own_price_text', 'recurring_pwyw', 'name_your_own_price_text_recurring', 'is_hidden']);
        $fields = [];
        foreach ($this->products->get_payment_plan_field_definitions() as $row) {
            foreach ($row as $field) {
                $type = $field['type'] ?? '';
                $id = $field['id'] ?? '';
                if (! in_array($id, $allowed, true) || ! empty($field['secret']) || ! empty($field['readonly']) || ! empty($field['disabled']) || isset($field['force_value']) || in_array($type, ['hidden', 'html'], true)) {
                    continue;
                }
                if ('checkbox' === $type) {
                    $schema = ['type' => 'boolean'];
                } elseif ('number' === $type) {
                    $schema = ['type' => ['integer', 'null'], 'minimum' => 0, 'maximum' => 2147483647];
                } elseif ('select' === $type && ! empty($field['selections']) && is_array($field['selections'])) {
                    $schema = ['type' => ['string', 'null'], 'enum' => array_merge([null], array_map('strval', array_keys($field['selections'])))];
                } elseif (in_array($type, ['text', 'price', 'textarea'], true)) {
                    $schema = ['type' => ['string', 'null'], 'maxLength' => 10000];
                } else {
                    continue;
                }
                if ('option_id' === $id) {
                    $schema = ['type' => 'string', 'minLength' => 1, 'maxLength' => 100, 'pattern' => '^[A-Za-z0-9_-]+$'];
                }
                $fields[$id] = ['field' => $field, 'schema' => $schema];
            }
        }
        return $fields;
    }

    /**
     * @since 1.1.0
     * @return array
     */
    public function schema()
    {
        $properties = [];
        foreach ($this->definitions() as $id => $field) {
            $properties[$id] = $field['schema'];
        }
        return PPCart_Mcp_Input::schema([
            'id' => ['type' => 'integer', 'minimum' => 1],
            'revision' => ['type' => 'string', 'minLength' => 64, 'maxLength' => 64],
            'plans' => ['type' => 'array', 'minItems' => 1, 'maxItems' => 50,
                'items' => PPCart_Mcp_Input::schema($properties, ['option_id'])],
            'dry_run' => ['type' => 'boolean'],
        ], ['id', 'revision', 'plans']);
    }

    /**
     * @since 1.1.0
     * @param int $id Product identifier.
     * @return array|WP_Error
     */
    public function read($id)
    {
        if (! $this->permissions->can_edit($id)) {
            return $this->forbidden();
        }
        $stored = ppcart_get_post_meta($id, 'pay_options', true);
        if ('' === $stored) {
            $stored = [];
        }
        if (! is_array($stored)) {
            return PPCart_Mcp_Input::error();
        }
        $definitions = $this->definitions();
        $plans = [];
        foreach ($stored as $row) {
            if (! is_array($row)) {
                return PPCart_Mcp_Input::error();
            }
            $safe = PPCart_Mcp_Input::native_row($row, $definitions);
            if (is_wp_error($safe)) {
                return $safe;
            }
            $plans[] = $safe;
        }
        $fields = [];
        foreach ($definitions as $key => $definition) {
            $fields[$key] = ['label' => wp_strip_all_tags($definition['field']['label'] ?? $key),
                'schema' => $definition['schema'], 'default' => $definition['field']['value'] ?? ''];
        }
        return ['id' => $id, 'plans' => $plans, 'fields' => (object) $fields,
            'revision' => hash('sha256', wp_json_encode($stored))];
    }

    /**
     * Validate all plan rows before local or remote writes.
     *
     * @since 1.1.0
     * @param mixed $input Decoded JSON.
     * @return array|WP_Error
     */
    public function update($input)
    {
        $input = PPCart_Mcp_Input::validate($input, $this->schema());
        if (is_wp_error($input)) {
            return $input;
        }
        $before = $this->read($input['id']);
        if (is_wp_error($before)) {
            return $before;
        }
        if (! hash_equals($before['revision'], $input['revision'])) {
            return $this->stale();
        }
        $existing = ppcart_get_post_meta($input['id'], 'pay_options', true);
        $existing = is_array($existing) ? $existing : [];
        $by_id = [];
        foreach ($existing as $row) {
            if (isset($row['option_id'])) {
                $by_id[$row['option_id']] = $row;
            }
        }
        $definitions = $this->definitions();
        $plans = [];
        $seen = [];
        $visible = false;
        foreach ($input['plans'] as $patch) {
            $id = $patch['option_id'];
            if (! preg_match('/^[A-Za-z0-9_-]{1,100}$/D', $id) || isset($seen[$id])) {
                return PPCart_Mcp_Input::error();
            }
            $seen[$id] = true;
            $plan = $by_id[$id] ?? [];
            foreach ($definitions as $key => $definition) {
                if (! array_key_exists($key, $plan)) {
                    $default = $definition['field']['value'] ?? '';
                    $plan[$key] = 'checkbox' === $definition['field']['type'] ? 0 : ('' === $default ? null : $default);
                }
            }
            foreach ($patch as $key => $value) {
                $type = $definitions[$key]['field']['type'];
                if ('checkbox' === $type) {
                    $plan[$key] = $value ? 1 : 0;
                    continue;
                }
                if (null === $value || '' === $value) {
                    $plan[$key] = null;
                    continue;
                }
                if ('option_id' !== $key && ('0' === $value || 0 === $value)) {
                    $plan[$key] = 0;
                    continue;
                }
                if ('price' === $type && ! preg_match('/^\d+(?:[.,]\d{1,8})?$/D', $value)) {
                    return PPCart_Mcp_Input::error();
                }
                $sanitizer = new PPCart_Sanitize();
                $sanitizer->set_type($type);
                $sanitizer->set_data(is_int($value) ? (string) $value : $value);
                $plan[$key] = $sanitizer->clean();
                if (! is_scalar($plan[$key])) {
                    return PPCart_Mcp_Input::error();
                }
            }
            if ('free' !== $plan['product_type'] && (! is_numeric($plan['price']) || (float) $plan['price'] < 0)) {
                return PPCart_Mcp_Input::error();
            }
            if ('recurring' === $plan['product_type'] && (empty($plan['interval']) || empty($plan['frequency']) || null === $plan['installments'] || '' === $plan['installments'])) {
                return PPCart_Mcp_Input::error();
            }
            if ('free' === $plan['product_type']) {
                $plan['price'] = 0;
            }
            $plan['stripe_plan_id'] = $plan['stripe_plan_id'] ?? '';
            $plan['sale_stripe_plan_id'] = $plan['sale_stripe_plan_id'] ?? '';
            $visible = $visible || empty($plan['is_hidden']);
            $plans[] = $plan;
        }
        if (! $visible) {
            return PPCart_Mcp_Input::error();
        }
        $validation = apply_filters('ppcart_mcp_validate_payment_plans', null, $input['id'], $plans);
        if (is_wp_error($validation)) {
            return $validation;
        }
        $safe = [];
        foreach ($plans as $plan) {
            $safe[] = array_intersect_key($plan, $definitions);
        }
        if (! empty($input['dry_run'])) {
            return ['id' => $input['id'], 'dry_run' => true, 'plans' => $safe, 'revision' => $before['revision']];
        }
        $lock = '_ppcart_mcp_plan_lock_' . $input['id'];
        $token = wp_generate_uuid4();
        if (! add_option($lock, $token, '', false)) {
            return new WP_Error('ppcart_mcp_plan_locked', __('A plan save is already in progress or needs review.', 'publishpress-cart'));
        }
        try {
            $current = $this->read($input['id']);
            if (is_wp_error($current) || ! hash_equals($current['revision'], $input['revision'])) {
                return $this->stale();
            }
            $admin = new PPCart_Product_Admin();
            if (apply_filters('ppcart_process_stripe_products', true)) {
                $admin->save_objects_checked($input['id'], [ppcart_meta_key('pay_options') => $plans], [ppcart_meta_key('pay_options') => $existing]);
            } else {
                ppcart_update_post_meta($input['id'], 'pay_options', $plans, $existing);
            }
            $after = $this->read($input['id']);
            if (is_wp_error($after) || $after['plans'] != $safe) {
                return $this->uncertain();
            }
            return $after;
        } catch (Throwable $error) {
            return $this->uncertain();
        } finally {
            if (get_option($lock) === $token) {
                delete_option($lock);
            }
        }
    }

    /** @since 1.1.0 @return WP_Error */
    private function forbidden()
    {
        return new WP_Error('ppcart_mcp_forbidden', __('You cannot edit this product payment plan.', 'publishpress-cart'));
    }

    /** @since 1.1.0 @return WP_Error */
    private function stale()
    {
        return new WP_Error('ppcart_mcp_stale_revision', __('Payment plans changed. Read them again before saving.', 'publishpress-cart'));
    }

    /** @since 1.1.0 @return WP_Error */
    private function uncertain()
    {
        return new WP_Error('ppcart_mcp_plan_unconfirmed', __('Plan save could not be confirmed. Local or gateway data may have changed. Read the product and check the gateway before retrying.', 'publishpress-cart'));
    }
}
