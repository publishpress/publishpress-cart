<?php

/**
 * Native product downloads configuration without exposing source URLs or access keys.
 *
 * @package PPCart
 * @since 1.1.0
 */

defined('ABSPATH') || exit;

class PPCart_Mcp_Files
{
    private $products;
    private $permissions;

    /** @since 1.1.0 @param PPCart_Product_Metaboxes $products Native fields. */
    public function __construct($products)
    {
        $this->products = $products;
        $this->permissions = new PPCart_Mcp_Products($products);
    }

    /** @since 1.1.0 @return array */
    public function definitions()
    {
        $allowed = ['file_id', 'file_name', 'file_plan', 'file_limit', 'file_redirect'];
        $allowed = apply_filters('ppcart_mcp_download_field_ids', $allowed);
        $result = [];
        foreach ($this->products->get_local_group_definitions('files') as $group) {
            foreach ($group['fields'] ?? [] as $row) {
                foreach ($row as $field) {
                    $id = $field['id'] ?? '';
                    if (! in_array($id, $allowed, true) || ! empty($field['disabled']) || ! empty($field['readonly'])) {
                        continue;
                    }
                    if ('checkbox' === $field['type']) {
                        $schema = ['type' => 'boolean'];
                    } elseif ('number' === $field['type']) {
                        $schema = ['type' => ['integer', 'null'], 'minimum' => 0, 'maximum' => 2147483647];
                    } elseif ('file_plan' === $id) {
                        $schema = ['type' => 'array', 'maxItems' => 100, 'items' => ['type' => 'string', 'maxLength' => 100]];
                    } else {
                        $schema = ['type' => ['string', 'null'], 'maxLength' => 1000];
                    }
                    $result[$id] = ['schema' => $schema, 'field' => $field];
                }
            }
        }
        return $result;
    }

    /** @since 1.1.0 @param bool $write Write. @return array */
    public function schema($write)
    {
        $properties = ['id' => ['type' => 'integer', 'minimum' => 1]];
        if ($write) {
            $fields = ['attachment_id' => ['type' => 'integer', 'minimum' => 1]];
            foreach ($this->definitions() as $id => $definition) {
                $fields[$id] = $definition['schema'];
            }
            $fields['file_id'] = ['type' => 'string', 'pattern' => '^[A-Za-z0-9_-]+$', 'minLength' => 1, 'maxLength' => 100];
            $properties['rows'] = ['type' => 'array', 'maxItems' => 100, 'items' => PPCart_Mcp_Input::schema($fields, ['file_id'])];
            $properties['revision'] = ['type' => 'string', 'minLength' => 64, 'maxLength' => 64];
            $properties['dry_run'] = ['type' => 'boolean'];
        }
        return PPCart_Mcp_Input::schema($properties, $write ? ['id', 'rows', 'revision'] : ['id']);
    }

    /** @since 1.1.0 @param mixed $input Input. @return array|WP_Error */
    public function read($input)
    {
        $input = PPCart_Mcp_Input::validate($input, $this->schema(false));
        if (is_wp_error($input)) {
            return $input;
        }
        if (! PPCart_Files::get_active_handler() instanceof PPCart_Files || ! $this->permissions->can_edit($input['id'])) {
            return new WP_Error('ppcart_mcp_forbidden', __('You cannot configure these downloads.', 'publishpress-cart'));
        }
        $stored = ppcart_get_post_meta($input['id'], 'files', true);
        $stored = '' === $stored ? [] : $stored;
        if (! is_array($stored)) {
            return PPCart_Mcp_Input::error();
        }
        $rows = [];
        $definitions = $this->definitions();
        foreach ($stored as $row) {
            if (! is_array($row)) {
                return PPCart_Mcp_Input::error();
            }
            $rows[] = array_intersect_key($row, $definitions) + ['has_source' => ! empty($row['file_url'])];
        }
        $fields = [];
        foreach ($definitions as $id => $definition) {
            $fields[$id] = ['label' => $definition['field']['label'], 'schema' => $definition['schema']];
        }
        return ['id' => $input['id'], 'rows' => $rows, 'fields' => (object) $fields,
            'plan_choices' => (object) PPCart_Product_Metabox_Option_Sources::get_payment_plans(false, $input['id']),
            'revision' => hash_hmac('sha256', wp_json_encode($stored), wp_salt('nonce'))];
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
            return new WP_Error('ppcart_mcp_stale_revision', __('Download settings changed. Read them again.', 'publishpress-cart'));
        }
        $old = ppcart_get_post_meta($input['id'], 'files', true);
        $old = is_array($old) ? $old : [];
        $by_id = array_column($old, null, 'file_id');
        $definitions = $this->definitions();
        $rows = [];
        $seen = [];
        foreach ($input['rows'] as $patch) {
            $id = $patch['file_id'];
            if (isset($seen[$id])) {
                return PPCart_Mcp_Input::error();
            }
            $seen[$id] = true;
            $row = $by_id[$id] ?? ['file_id' => $id, 'file_limit' => null, 'file_plan' => [], 'file_redirect' => 0];
            if (isset($patch['attachment_id'])) {
                $attachment = $patch['attachment_id'];
                if ('attachment' !== get_post_type($attachment) || ! current_user_can('upload_files') || ! current_user_can('edit_post', $attachment)) {
                    return new WP_Error('ppcart_mcp_forbidden', __('You cannot use this attachment.', 'publishpress-cart'));
                }
                $path = get_attached_file($attachment);
                $resolved = $path ? PPCart_Files::resolve_local_download_path($path) : false;
                if (! $resolved) {
                    return new WP_Error('ppcart_mcp_invalid_file', __('Upload the file through Cart secure uploads before assigning it.', 'publishpress-cart'));
                }
                $row['file_url'] = wp_get_attachment_url($attachment);
            }
            foreach ($patch as $key => $value) {
                if ('attachment_id' === $key || 'file_id' === $key) {
                    continue;
                }
                if ('file_plan' === $key) {
                    foreach ($value as $plan) {
                        if (! array_key_exists($plan, (array) $before['plan_choices'])) {
                            return PPCart_Mcp_Input::error();
                        }
                    }
                    $row[$key] = array_values($value);
                } elseif (is_bool($value)) {
                    $presence_fields = apply_filters('ppcart_mcp_download_presence_fields', []);
                    if (! $value && in_array($key, $presence_fields, true)) {
                        unset($row[$key]);
                    } else {
                        $row[$key] = $value ? 1 : 0;
                    }
                } elseif (null === $value || '' === $value) {
                    $row[$key] = null;
                } else {
                    $row[$key] = is_int($value) ? $value : sanitize_text_field($value);
                }
            }
            if (empty($row['file_url']) || empty($row['file_name'])) {
                return PPCart_Mcp_Input::error();
            }
            $validation = apply_filters('ppcart_mcp_validate_download_row', null, $row, $input['id']);
            if (is_wp_error($validation)) {
                return $validation;
            }
            $rows[] = $row;
        }
        if (! empty($input['dry_run'])) {
            $safe = [];
            foreach ($rows as $row) {
                $safe[] = array_intersect_key($row, $definitions) + ['has_source' => true];
            }
            return ['dry_run' => true, 'rows' => $safe, 'revision' => $before['revision']];
        }
        $expected = ppcart_get_post_meta($input['id'], 'files', true);
        if (! hash_equals($input['revision'], hash_hmac('sha256', wp_json_encode('' === $expected ? [] : $expected), wp_salt('nonce')))) {
            return new WP_Error('ppcart_mcp_stale_revision', __('Download settings changed before saving.', 'publishpress-cart'));
        }
        ppcart_update_post_meta($input['id'], 'files', wp_slash($rows), $expected);
        if (ppcart_get_post_meta($input['id'], 'files', true) !== $rows) {
            return new WP_Error('ppcart_mcp_save_failed', __('Download settings could not be confirmed. Read before retrying.', 'publishpress-cart'));
        }
        return $this->read(['id' => $input['id']]);
    }
}
