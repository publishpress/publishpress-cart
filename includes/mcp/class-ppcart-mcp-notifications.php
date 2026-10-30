<?php

/**
 * Native per-product purchase notification configuration, without sending mail.
 *
 * @package PPCart
 * @since 1.1.0
 */

defined('ABSPATH') || exit;

class PPCart_Mcp_Notifications
{
    private $products;
    private $permissions;

    /** @since 1.1.0 @param PPCart_Product_Metaboxes $products Native controls. */
    public function __construct($products)
    {
        $this->products = $products;
        $this->permissions = new PPCart_Mcp_Products($products);
    }

    /** @since 1.1.0 @return array */
    public function definitions()
    {
        $allowed = ['notification_name', 'enabled', 'send_to', 'send_to_email', 'from_name', 'from_email', 'reply_to', 'bcc', 'subject', 'message'];
        $native = $this->products->get_local_field_definitions();
        $fields = [];
        foreach ($native['_ppcart_notifications']['field']['fields'] ?? [] as $row) {
            foreach ($row as $field) {
                $id = $field['id'] ?? '';
                if (! in_array($id, $allowed, true) || ! empty($field['disabled']) || ! empty($field['readonly'])) {
                    continue;
                }
                $schema = 'checkbox' === $field['type'] ? ['type' => 'boolean'] : ['type' => ['string', 'null'], 'maxLength' => 100000];
                if ('select' === $field['type']) {
                    $schema['enum'] = array_merge([null], array_map('strval', array_keys($field['selections'])));
                }
                $fields[$id] = ['schema' => $schema, 'field' => $field];
            }
        }
        return apply_filters('ppcart_mcp_notification_definitions', $fields, $native);
    }

    /** @since 1.1.0 @param bool $write Write schema. @return array */
    public function schema($write)
    {
        $properties = ['id' => ['type' => 'integer', 'minimum' => 1]];
        $required = ['id'];
        if ($write) {
            $fields = [];
            foreach ($this->definitions() as $id => $definition) {
                $fields[$id] = $definition['schema'];
            }
            $properties['rows'] = ['type' => 'array', 'maxItems' => 100,
                'items' => PPCart_Mcp_Input::schema($fields, ['notification_name', 'subject', 'message', 'send_to'])];
            $properties['revision'] = ['type' => 'string', 'minLength' => 64, 'maxLength' => 64];
            $properties['dry_run'] = ['type' => 'boolean'];
            $required = ['id', 'rows', 'revision'];
        }
        return PPCart_Mcp_Input::schema($properties, $required);
    }

    /** @since 1.1.0 @param mixed $input Input. @return array|WP_Error */
    public function read($input)
    {
        $input = PPCart_Mcp_Input::validate($input, $this->schema(false));
        if (is_wp_error($input)) {
            return $input;
        }
        if (! $this->permissions->can_edit($input['id'])) {
            return new WP_Error('ppcart_mcp_forbidden', __('You cannot edit this product.', 'publishpress-cart'));
        }
        $stored = ppcart_get_post_meta($input['id'], 'notifications', true);
        $stored = '' === $stored ? [] : $stored;
        if (! is_array($stored)) {
            return PPCart_Mcp_Input::error();
        }
        $definitions = $this->definitions();
        $rows = [];
        foreach ($stored as $row) {
            if (! is_array($row)) {
                return PPCart_Mcp_Input::error();
            }
            $safe = PPCart_Mcp_Input::native_row($row, $definitions);
            if (is_wp_error($safe)) {
                return $safe;
            }
            $rows[] = $safe;
        }
        $fields = [];
        foreach ($definitions as $id => $definition) {
            $fields[$id] = ['label' => wp_strip_all_tags($definition['field']['label']), 'schema' => $definition['schema']];
        }
        return ['id' => $input['id'], 'rows' => $rows, 'fields' => (object) $fields,
            'revision' => hash('sha256', wp_json_encode($stored))];
    }

    /** @since 1.1.0 @param mixed $input Input. @return array|WP_Error */
    public function update($input)
    {
        $input = PPCart_Mcp_Input::validate($input, $this->schema(true));
        if (is_wp_error($input)) {
            return $input;
        }
        $before = $this->read(['id' => $input['id']]);
        if (is_wp_error($before)) {
            return $before;
        }
        if (! hash_equals($before['revision'], $input['revision'])) {
            return new WP_Error('ppcart_mcp_stale_revision', __('Notifications changed. Read them again.', 'publishpress-cart'));
        }
        $definitions = $this->definitions();
        $rows = [];
        foreach ($input['rows'] as $patch) {
            $row = [];
            foreach ($definitions as $key => $definition) {
                $type = $definition['field']['type'];
                $value = $patch[$key] ?? $definition['field']['value'] ?? null;
                if ('conditions' === $type) {
                    $row[$key] = apply_filters('ppcart_mcp_notification_conditions', PPCart_Mcp_Input::error(), $value, $definition['field']);
                    if (is_wp_error($row[$key])) {
                        return $row[$key];
                    }
                } elseif ('checkbox' === $type) {
                    $row[$key] = $value ? 1 : 0;
                } elseif (null === $value || '' === $value) {
                    $row[$key] = null;
                } elseif ('0' === $value) {
                    $row[$key] = 0;
                } else {
                    $sanitizer = new PPCart_Sanitize();
                    $sanitizer->set_type($type);
                    $sanitizer->set_data($value);
                    $row[$key] = $sanitizer->clean();
                }
            }
            if (
                empty($row['notification_name']) || empty($row['subject']) || empty($row['message'])
                || ! in_array($row['send_to'], ['enter', 'purchaser', 'admin'], true)
                || ('enter' === $row['send_to'] && empty($row['send_to_email']))
            ) {
                return PPCart_Mcp_Input::error();
            }
            $valid = apply_filters('ppcart_mcp_validate_notification_row', true, $row, $input['id']);
            if (is_wp_error($valid)) {
                return $valid;
            }
            $rows[] = $row;
        }
        if (! empty($input['dry_run'])) {
            return ['dry_run' => true, 'rows' => $rows, 'revision' => $before['revision']];
        }
        $old = ppcart_get_post_meta($input['id'], 'notifications', true);
        if (! hash_equals($input['revision'], hash('sha256', wp_json_encode('' === $old ? [] : $old)))) {
            return new WP_Error('ppcart_mcp_stale_revision', __('Notifications changed before saving.', 'publishpress-cart'));
        }
        ppcart_update_post_meta($input['id'], 'notifications', wp_slash($rows), $old);
        if (ppcart_get_post_meta($input['id'], 'notifications', true) !== $rows) {
            return new WP_Error('ppcart_mcp_save_failed', __('Notification changes could not be confirmed. Read them before retrying.', 'publishpress-cart'));
        }
        return $this->read(['id' => $input['id']]);
    }
}
