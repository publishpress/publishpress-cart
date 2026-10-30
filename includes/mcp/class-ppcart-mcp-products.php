<?php

/**
 * Product configuration through native definitions, sanitizers and meta helpers.
 *
 * @package PPCart
 * @since 1.1.0
 */

defined('ABSPATH') || exit;

class PPCart_Mcp_Products
{
    private $products;

    /**
     * @since 1.1.0
     * @param PPCart_Product_Metaboxes $products Native field provider.
     */
    public function __construct($products)
    {
        $this->products = $products;
    }

    /**
     * Expose only reviewed scalar fields. Repeaters have separate lifecycles.
     *
     * @since 1.1.0
     * @param int $id Product context.
     * @return array
     */
    public function definitions($id = 0)
    {
        $allowed = [
            '_ppcart_show_address_fields', '_ppcart_button_color', '_ppcart_button_text', '_ppcart_terms_setting', '_ppcart_privacy_setting',
            '_ppcart_disable_cod', '_ppcart_disable_stripe', '_ppcart_disable_paypal',
            '_ppcart_product_name', '_ppcart_hide_title', '_ppcart_header_color', '_ppcart_header_image',
            '_ppcart_manage_stock', '_ppcart_limit', '_ppcart_customer_purchase_limit', '_ppcart_customer_limit',
            '_ppcart_customer_limit_message', '_ppcart_checkout_starts', '_ppcart_checkout_ends',
            '_ppcart_checkout_ended_action', '_ppcart_checkout_ended_redirect', '_ppcart_checkout_ended_message',
            '_ppcart_on_sale', '_ppcart_schedule_sale', '_ppcart_sale_start', '_ppcart_sale_end', '_ppcart_show_full_price',
            '_ppcart_hide_plans', '_ppcart_plan_heading', '_ppcart_confirmation', '_ppcart_confirmation_message',
            '_ppcart_confirmation_page', '_ppcart_redirect', '_ppcart_disable_pending_email',
            '_ppcart_disable_welcome_email', '_ppcart_disable_purchase_email',
        ];
        $allowed = apply_filters('ppcart_mcp_product_field_ids', $allowed);
        $references = apply_filters('ppcart_mcp_product_references', ['_ppcart_confirmation_page' => 'page']);
        $definitions = [];
        foreach (apply_filters('ppcart_mcp_product_definitions', $this->products->get_local_field_definitions($id), $id, $this->products) as $key => $definition) {
            if (! in_array($key, $allowed, true)) {
                continue;
            }
            $field = $definition['field'];
            if (isset($references[$key]) && 'plan' !== $references[$key] && is_array($field['selections'] ?? null)) {
                foreach ($field['selections'] as $choice => $label) {
                    if (ctype_digit((string) $choice) && ! current_user_can('read_post', (int) $choice)) {
                        unset($field['selections'][$choice]);
                    }
                }
                $definition['field'] = $field;
            }
            if (! empty($field['readonly']) || ! empty($field['disabled']) || isset($field['force_value'])) {
                continue;
            }
            $type = $field['field-type'] ?? $field['type'];
            if (isset($references[$key])) {
                if ('html' === $type) {
                    $field['value'] = '';
                    $field['label'] = ucfirst(str_replace('_', ' ', ppcart_meta_key_suffix($key)));
                }
                $type = 'select';
                $field['type'] = 'select';
                $definition['field'] = $field;
                $schema = 'plan' === $references[$key] ? ['type' => 'string', 'maxLength' => 100]
                    : ['type' => 'string', 'pattern' => '^(?:|[1-9][0-9]*)$', 'maxLength' => 20];
            } elseif ('checkbox' === $type) {
                $schema = ['type' => 'boolean'];
            } elseif ('number' === $type) {
                $schema = ['type' => 'integer', 'minimum' => 0, 'maximum' => 2147483647];
            } elseif (in_array($type, ['select', 'radio'], true) && isset($field['selections'])) {
                if (! is_array($field['selections']) || count(array_filter($field['selections'], 'is_array'))) {
                    continue;
                }
                $schema = ['type' => 'string', 'enum' => array_map('strval', array_keys($field['selections']))];
            } elseif (in_array($type, ['text', 'textarea', 'editor', 'email_editor', 'url', 'price', 'email', 'color', 'date', 'datetime', 'datetime-local'], true)) {
                $schema = ['type' => 'string', 'maxLength' => 10000];
            } else {
                continue;
            }
            $definitions[$key] = $definition + ['schema' => $schema, 'native_type' => $type];
        }
        return $definitions;
    }

    /**
     * @since 1.1.0
     * @param int $id Product context.
     * @return array
     */
    public function patch_schema($id = 0)
    {
        $fields = [];
        foreach ($this->definitions($id) as $key => $definition) {
            $fields[$key] = $definition['schema'];
        }
        return PPCart_Mcp_Input::schema([
            'id' => ['type' => 'integer', 'minimum' => 1],
            'changes' => PPCart_Mcp_Input::schema($fields),
            'revision' => ['type' => 'string', 'minLength' => 64, 'maxLength' => 64],
            'dry_run' => ['type' => 'boolean'],
        ], ['id', 'changes', 'revision']);
    }

    /**
     * @since 1.1.0
     * @param int $id Product identifier.
     * @return bool
     */
    public function can_edit($id)
    {
        $post = get_post($id);
        $types = array_unique(array_merge(
            ppcart_query_post_types('product'),
            (array) apply_filters('ppcart_product_metabox_post_type', ppcart_live_post_type('product'))
        ));
        return $post && in_array($post->post_type, $types, true)
            && current_user_can('edit_post', $id) && current_user_can('read_post', $id);
    }

    /**
     * @since 1.1.0
     * @param int $id Product identifier.
     * @return array|WP_Error
     */
    public function read($id)
    {
        if (! $this->can_edit($id)) {
            return $this->forbidden();
        }
        $post = get_post($id);
        $fields = [];
        foreach ($this->definitions($id) as $key => $definition) {
            $fields[$key] = [
                'label' => wp_strip_all_tags($definition['field']['label'] ?? $key),
                'group' => $definition['group'],
                'schema' => $definition['schema'],
                'choices' => (object) ($definition['field']['selections'] ?? []),
                'default' => $definition['field']['value'] ?? '',
                'value' => ppcart_get_post_meta($id, $key, true),
            ];
        }
        $result = ['id' => $id, 'title' => $post->post_title, 'status' => $post->post_status, 'fields' => (object) $fields];
        $result['revision'] = hash('sha256', wp_json_encode($result));
        return $result;
    }

    /**
     * @since 1.1.0
     * @param mixed $input Decoded JSON input.
     * @return array|WP_Error
     */
    public function update($input)
    {
        $input = PPCart_Mcp_Input::object($input);
        if (is_wp_error($input) || ! isset($input['id']) || ! is_int($input['id'])) {
            return PPCart_Mcp_Input::error();
        }
        if (! $this->can_edit($input['id'])) {
            return $this->forbidden();
        }
        $id = $input['id'];
        $input = PPCart_Mcp_Input::validate($input, $this->patch_schema($id));
        if (is_wp_error($input)) {
            return $input;
        }
        if (! $input['changes']) {
            return PPCart_Mcp_Input::error();
        }
        $before = $this->read($id);
        if (! hash_equals($before['revision'], $input['revision'])) {
            return new WP_Error('ppcart_mcp_stale_revision', __('Product settings changed. Read them again before saving.', 'publishpress-cart'));
        }
        $definitions = $this->definitions($id);
        $changes = [];
        $snapshots = [];
        foreach ($input['changes'] as $key => $value) {
            $type = $definitions[$key]['native_type'];
            if (
                '_ppcart_confirmation_page' === $key && '' !== $value
                && (! array_key_exists($value, $definitions[$key]['field']['selections']) || ! current_user_can('read_post', (int) $value))
            ) {
                return PPCart_Mcp_Input::error();
            }
            if ('price' === $type && '' !== $value && ! preg_match('/^\d+(?:[.,]\d{1,8})?$/D', $value)) {
                return PPCart_Mcp_Input::error();
            }
            $clean = $this->products->sanitize_local_field($type, is_int($value) ? (string) $value : $value);
            $clean = null === $clean ? '' : $clean;
            if (! is_scalar($clean) || (is_string($value) && '' !== trim($value) && '' === $clean)) {
                return PPCart_Mcp_Input::error();
            }
            $changes[$key] = $clean;
            $snapshots[$key] = [
                'exists' => metadata_exists('post', $id, ppcart_meta_key($key)),
                'value' => get_post_meta($id, ppcart_meta_key($key), true),
            ];
        }
        $validation = apply_filters('ppcart_mcp_validate_product_changes', null, $id, $changes, $before);
        if (is_wp_error($validation)) {
            return $validation;
        }
        if (! empty($input['dry_run'])) {
            return ['id' => $id, 'dry_run' => true, 'changes' => (object) $changes, 'revision' => $before['revision']];
        }
        $written = [];
        foreach ($changes as $key => $value) {
            $written[] = $key;
            // Native forms remove empty scalar fields, but preserve explicit zero.
            if ('' === $value) {
                ppcart_delete_post_meta($id, $key);
            } else {
                ppcart_update_post_meta($id, $key, wp_slash($value));
            }
            if ((string) ppcart_get_post_meta($id, $key, true) !== (string) $value) {
                $restored = true;
                foreach (array_reverse($written) as $restore_key) {
                    $snapshot = $snapshots[$restore_key];
                    $current = get_post_meta($id, ppcart_meta_key($restore_key), true);
                    $exists = metadata_exists('post', $id, ppcart_meta_key($restore_key));
                    if ($exists === $snapshot['exists'] && $current === $snapshot['value']) {
                        continue;
                    }
                    if ((string) $current !== (string) $changes[$restore_key]) {
                        $restored = false;
                        continue;
                    }
                    if ($snapshot['exists']) {
                        ppcart_update_post_meta($id, $restore_key, wp_slash($snapshot['value']));
                    } else {
                        ppcart_delete_post_meta($id, $restore_key);
                    }
                    $actual = get_post_meta($id, ppcart_meta_key($restore_key), true);
                    $restored = $restored && ($snapshot['exists'] ? $actual === $snapshot['value']
                        : ! metadata_exists('post', $id, ppcart_meta_key($restore_key)));
                }
                return new WP_Error('ppcart_mcp_save_failed', $restored
                    ? __('Product changes failed. Previous values were restored.', 'publishpress-cart')
                    : __('Product changes failed and could not be fully restored. Read the product before retrying.', 'publishpress-cart'));
            }
        }
        clean_post_cache($id);
        return $this->read($id);
    }

    /**
     * @since 1.1.0
     * @return WP_Error
     */
    private function forbidden()
    {
        return new WP_Error('ppcart_mcp_forbidden', __('You cannot edit this Cart product.', 'publishpress-cart'));
    }
}
