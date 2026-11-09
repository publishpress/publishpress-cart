<?php

/**
 * Typed native product integration rules; never executes rules or exposes credentials.
 *
 * @package PPCart
 * @since 1.1.0
 */

defined('ABSPATH') || exit;

class PPCart_Mcp_Integrations
{
    private $products;
    private $permissions;

    /** @since 1.1.0 @param PPCart_Product_Metaboxes $products Native provider. */
    public function __construct($products)
    {
        $this->products = $products;
        $this->permissions = new PPCart_Mcp_Products($products);
    }

    /** @since 1.1.0 @param bool $choices Current native choices. @return array */
    private function definitions($choices = false, $product_id = 0, $local_only = false)
    {
        if ($local_only) {
            $cached = PPCart_Mcp_Choice_Cache::get($product_id);
            if (is_wp_error($cached)) {
                return $cached;
            }
            $current = $this->definitions(false, $product_id);
            foreach ($cached as $key => $definition) {
                if (! isset($current[$key])) {
                    unset($cached[$key]);
                    continue;
                }
                $cached[$key]['schema'] = $current[$key]['schema'];
                $cached[$key]['field'] = array_merge($current[$key]['field'], ['selections' => $definition['field']['selections'] ?? []]);
                foreach (apply_filters('ppcart_mcp_integration_object_fields', ['ppcart_sub_prod_id' => 'product']) as $object_field => $type) {
                    if ($object_field !== $key || ! is_array($cached[$key]['field']['selections'])) {
                        continue;
                    }
                    foreach ($cached[$key]['field']['selections'] as $choice => $label) {
                        if ('' !== (string) $choice && ! current_user_can('read_post', (int) $choice)) {
                            unset($cached[$key]['field']['selections'][$choice]);
                        }
                    }
                }
            }
            return $cached;
        }
        $allowed = ['convertkit_forms', 'convertkit_tags', 'convertkit_field_map', 'services', 'service_trigger', 'int_plan', 'webhook_method', 'webhook_url', 'field_map', 'service_action',
            'mail_list', 'mail_tags', 'mail_groups', 'mc_phone_tag', 'activecampaign_lists', 'activecampaign_tags', 'activecampaign_field_map',
            'sendfox_list', 'mailpoet_list', 'mp_schedule_welcome', 'mp_admin_email', 'membervault_action', 'member_vault_course_id', 'previous_user_role', 'user_role', 'tags', 'groups', 'require_optin', 'ppcart_sub_prod_id', 'ppcart_sub_plan_id', 'ppcart_sub_cancel'];
        $allowed = apply_filters('ppcart_mcp_integration_field_ids', $allowed);
        $object_fields = apply_filters('ppcart_mcp_integration_object_fields', ['ppcart_sub_prod_id' => 'product']);
        $result = [];
        foreach ($this->products->get_local_group_definitions('integrations', ! $choices, $product_id) as $group) {
            foreach ($group['fields'] ?? [] as $row) {
                foreach ($row as $field) {
                    $id = $field['id'] ?? '';
                    if (! apply_filters('ppcart_mcp_integration_field_allowed', in_array($id, $allowed, true), $id) || ! empty($field['secret']) || ! empty($field['disabled']) || ! empty($field['readonly'])) {
                        continue;
                    }
                    $type = $field['field-type'] ?? $field['type'];
                    if ('checkbox' === $type) {
                        $schema = ['type' => 'boolean'];
                    } elseif ('number' === $type) {
                        $schema = ['type' => ['integer', 'null'], 'minimum' => 0];
                    } elseif (false !== strpos($field['class'] ?? '', 'multiple')) {
                        $schema = ['type' => 'array', 'maxItems' => 100, 'items' => ['type' => 'string', 'maxLength' => 200]];
                    } elseif (in_array($type, ['text', 'select', 'textarea', 'url', 'email'], true)) {
                        $schema = ['type' => ['string', 'null'], 'maxLength' => 10000];
                    } else {
                        continue;
                    }
                    if (isset($object_fields[$id]) && is_array($field['selections'] ?? null)) {
                        foreach ($field['selections'] as $choice => $label) {
                            if ('' !== (string) $choice && (! ctype_digit((string) $choice) || ! current_user_can('read_post', (int) $choice))) {
                                unset($field['selections'][$choice]);
                            }
                        }
                    }
                    if (in_array($id, PPCart_Mcp_Integration_Mapping::fields(), true)) {
                        $schema = PPCart_Mcp_Integration_Mapping::schema($product_id);
                    }
                    if ('ppcart_sub_plan_id' === $id) {
                        $field['selections'] = [];
                    }
                    if ('heartbeat_invite_id' === $id && is_array($field['selections'] ?? null)) {
                        foreach ($field['selections'] as $choice => $label) {
                            $field['selections'][$choice] = sprintf(__('Invitation %s', 'publishpress-cart'), $choice);
                        }
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
            $fields = ['row_id' => ['type' => 'integer', 'minimum' => 0]];
            foreach ($this->definitions() as $id => $definition) {
                $fields[$id] = $definition['schema'];
            }
            $properties['rows'] = ['type' => 'array', 'maxItems' => 100, 'items' => PPCart_Mcp_Input::schema($fields)];
            $properties['revision'] = ['type' => 'string', 'minLength' => 64, 'maxLength' => 64];
            $properties['dry_run'] = ['type' => 'boolean'];
        }
        return PPCart_Mcp_Input::schema($properties, $write ? ['id', 'rows', 'revision'] : ['id']);
    }

    /** @since 1.1.0 @param int $id Product. @return bool */
    public function permitted($id)
    {
        return current_user_can('manage_options') && $this->permissions->can_edit($id);
    }

    /** @since 1.1.0 @param mixed $input Input. @return array|WP_Error */
    public function read($input, $local_choices = false)
    {
        $input = PPCart_Mcp_Input::validate($input, $this->schema(false));
        if (is_wp_error($input)) {
            return $input;
        }
        if (! $this->permitted($input['id'])) {
            return new WP_Error('ppcart_mcp_forbidden', __('You cannot configure these integrations.', 'publishpress-cart'));
        }
        $native = $this->products->get_integration_choices();
        $definitions = $this->definitions(true, $input['id'], $local_choices);
        if (is_wp_error($definitions)) {
            return $definitions;
        }
        if (! $local_choices) {
            PPCart_Mcp_Choice_Cache::put($input['id'], $definitions);
        }
        $stored = ppcart_get_post_meta($input['id'], 'integrations', true);
        $stored = '' === $stored ? [] : $stored;
        if (! is_array($stored)) {
            return PPCart_Mcp_Input::error();
        }
        $rows = [];
        foreach ($stored as $index => $row) {
            if (! is_array($row)) {
                return PPCart_Mcp_Input::error();
            }
            $visible = $this->visible_row($row, $definitions, $input['id']);
            if (is_wp_error($visible)) {
                return $visible;
            }
            $rows[] = ['row_id' => (int) $index] + $visible;
        }
        $fields = [];
        foreach ($definitions as $id => $definition) {
            $fields[$id] = ['label' => wp_strip_all_tags($definition['field']['label']), 'schema' => $definition['schema'],
                'writable' => true,
                'choices' => (object) (is_array($definition['field']['selections'] ?? null) ? $definition['field']['selections'] : []),
                'conditions' => $definition['field']['conditional_logic'] ?? []];
        }
        return ['id' => $input['id'], 'mapping_sources' => PPCart_Mcp_Integration_Mapping::sources($input['id']), 'rows' => $rows, 'fields' => (object) $fields,
            'services' => (object) $native['services'], 'triggers' => (object) $native['triggers'],
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
        $before = $this->read(['id' => $input['id']], true);
        if (is_wp_error($before)) {
            return $before;
        }
        if (! hash_equals($before['revision'], $input['revision'])) {
            return new WP_Error('ppcart_mcp_stale_revision', __('Integration rules changed. Read them again.', 'publishpress-cart'));
        }
        $old = ppcart_get_post_meta($input['id'], 'integrations', true);
        $stored = is_array($old) ? $old : [];
        $definitions = $this->definitions(true, $input['id'], true);
        $rows = [];
        $seen = [];
        foreach ($input['rows'] as $patch) {
            $index = $patch['row_id'] ?? null;
            if (null !== $index && (! isset($stored[$index]) || isset($seen[$index]))) {
                return PPCart_Mcp_Input::error();
            }
            if (null !== $index) {
                $seen[$index] = true;
            }
            $row = null === $index ? [] : $stored[$index];
            if (isset($patch['webhook_url']) && ! empty($row['hook_headers_on']) && ! empty($row['hook_headers'])) {
                $old_origin = wp_parse_url($row['webhook_url'] ?? '');
                $new_origin = wp_parse_url($patch['webhook_url']);
                foreach (['scheme', 'host', 'port'] as $part) {
                    if (($old_origin[$part] ?? null) !== ($new_origin[$part] ?? null)) {
                        return new WP_Error('ppcart_mcp_credential_origin', __('Configure the new endpoint and authentication in native administration.', 'publishpress-cart'));
                    }
                }
            }

            if (isset($patch['services']) && isset($row['services']) && $patch['services'] !== $row['services']) {
                return new WP_Error('ppcart_mcp_service_change', __('Remove the old row and create a new rule to change services.', 'publishpress-cart'));
            }
            foreach ($patch as $key => $value) {
                if ('row_id' === $key) {
                    continue;
                }
                if (! isset($definitions[$key])) {
                    return PPCart_Mcp_Input::error();
                }
                if (in_array($key, ['webhook_url', 'kajabi_url'], true)) {
                    $url = wp_parse_url((string) $value);
                    if (! wp_http_validate_url((string) $value) || 'https' !== ($url['scheme'] ?? '') || isset($url['user']) || isset($url['pass']) || isset($url['query']) || isset($url['fragment'])) {
                        return PPCart_Mcp_Input::error();
                    }
                    $row[$key] = esc_url_raw($value);
                } elseif (in_array($key, PPCart_Mcp_Integration_Mapping::fields(), true)) {
                    $row[$key] = PPCart_Mcp_Integration_Mapping::encode($value, $input['id']);
                    if (is_wp_error($row[$key])) {
                        return $row[$key];
                    }
                } elseif (is_array($value)) {
                    $row[$key] = array_map('sanitize_text_field', $value);
                } elseif (is_bool($value)) {
                    $row[$key] = $value ? 1 : 0;
                } elseif (null === $value || '' === $value) {
                    $row[$key] = null;
                } else {
                    $sanitizer = new PPCart_Sanitize();
                    $sanitizer->set_type($definitions[$key]['field']['type']);
                    $sanitizer->set_data($value);
                    $row[$key] = '0' === $value ? 0 : $sanitizer->clean();
                }
                $choices = $definitions[$key]['field']['selections'] ?? [];
                if (is_array($choices) && $choices && 'select' === $definitions[$key]['field']['type'] && null !== $row[$key]) {
                    foreach (is_array($row[$key]) ? $row[$key] : [$row[$key]] as $choice) {
                        if ('' !== (string) $choice && ! array_key_exists((string) $choice, $choices)) {
                            return PPCart_Mcp_Input::error();
                        }
                    }
                }
            }
            $service = $row['services'] ?? '';
            if (! $service || ! array_key_exists($service, (array) $before['services']) || empty($row['service_trigger']) || ! is_array($row['service_trigger'])) {
                return PPCart_Mcp_Input::error();
            }
            foreach ($row['service_trigger'] as $trigger) {
                if (! array_key_exists($trigger, (array) $before['triggers'])) {
                    return PPCart_Mcp_Input::error();
                }
            }
            foreach ($row['int_plan'] ?? [] as $plan) {
                if (! array_key_exists($plan, (array) $before['plan_choices'])) {
                    return PPCart_Mcp_Input::error();
                }
            }
            if ('webhook' === $service) {
                $url = wp_parse_url($row['webhook_url'] ?? '');
                if (! wp_http_validate_url($row['webhook_url'] ?? '') || 'https' !== ($url['scheme'] ?? '') || isset($url['user']) || isset($url['pass'])) {
                    return PPCart_Mcp_Input::error();
                }
                $row['ppcart_safe_http'] = true;
            }
            if ('webhook' === $service && (empty($row['webhook_url']) || ! in_array($row['webhook_method'] ?? '', ['get', 'post'], true))) {
                return PPCart_Mcp_Input::error();
            }
            foreach (apply_filters('ppcart_mcp_integration_object_fields', ['ppcart_sub_prod_id' => 'product']) as $field_id => $object_type) {
                foreach ((array) ($row[$field_id] ?? []) as $object_id) {
                    if (
                        null !== $object_id && '' !== (string) $object_id
                        && (! ctype_digit((string) $object_id) || ! get_post((int) $object_id) || ! current_user_can('edit_post', (int) $object_id))
                    ) {
                        return new WP_Error('ppcart_mcp_forbidden', __('You cannot assign this integration object.', 'publishpress-cart'));
                    }
                }
            }
            if (! empty($row['ppcart_sub_prod_id'])) {
                $target = (int) $row['ppcart_sub_prod_id'];
                $plans = PPCart_Product_Metabox_Option_Sources::get_payment_plans(false, $target);
                if (! ppcart_is_product_post_type(get_post_type($target)) || ! array_key_exists((string) ($row['ppcart_sub_plan_id'] ?? ''), $plans) || ! PPCart_Mcp_Payments::can_manage()) {
                    return PPCart_Mcp_Input::error();
                }
            }
            if (isset($row['user_role']) && ! self::safe_role($row['user_role'])) {
                return new WP_Error('ppcart_mcp_privileged_role', __('MCP cannot configure administrator-level user grants.', 'publishpress-cart'));
            }
            $user_services = apply_filters('ppcart_create_user_integrations', ['create user', 'update user', 'tutor']);
            if (
                in_array($service, $user_services, true) && (empty($row['user_role'])
                || ! current_user_can('promote_users') || ('update user' !== $service && ! current_user_can('create_users')))
            ) {
                return new WP_Error('ppcart_mcp_forbidden', __('You cannot configure user administration rules.', 'publishpress-cart'));
            }
            $valid = apply_filters('ppcart_mcp_validate_integration_row', true, $row, $input['id']);
            if (is_wp_error($valid)) {
                return $valid;
            }
            $rows[] = $row;
        }
        if (! empty($input['dry_run'])) {
            $safe = [];
            foreach ($rows as $row) {
                $visible = $this->visible_row($row, $definitions, $input['id']);
                if (is_wp_error($visible)) {
                    return $visible;
                }
                $safe[] = $visible;
            }
            return ['dry_run' => true, 'rows' => $safe, 'revision' => $before['revision']];
        }
        $current = ppcart_get_post_meta($input['id'], 'integrations', true);
        if (! hash_equals($input['revision'], hash_hmac('sha256', wp_json_encode('' === $current ? [] : $current), wp_salt('nonce')))) {
            return new WP_Error('ppcart_mcp_stale_revision', __('Integration rules changed before saving.', 'publishpress-cart'));
        }
        ppcart_update_post_meta($input['id'], 'integrations', wp_slash($rows), $old);
        if (ppcart_get_post_meta($input['id'], 'integrations', true) !== $rows) {
            return new WP_Error('ppcart_mcp_save_failed', __('Integration changes could not be confirmed. Read before retrying.', 'publishpress-cart'));
        }
        return $this->read(['id' => $input['id']]);
    }

    /** @since 1.1.0 @param array $row Native row. @param array $definitions Reviewed fields. @return array */
    private function visible_row(array $row, array $definitions, $product_id)
    {
        $safe = array_intersect_key($row, $definitions);
        unset($safe['webhook_url'], $safe['kajabi_url']);
        foreach (PPCart_Mcp_Integration_Mapping::fields() as $field) {
            if (array_key_exists($field, $safe)) {
                $safe[$field] = PPCart_Mcp_Integration_Mapping::decode($safe[$field], $product_id);
            }
        }
        foreach (apply_filters('ppcart_mcp_integration_object_fields', ['ppcart_sub_prod_id' => 'product']) as $id => $type) {
            foreach ((array) ($safe[$id] ?? []) as $value) {
                if (null !== $value && '' !== (string) $value && ! current_user_can('read_post', (int) $value)) {
                    unset($safe[$id]);
                    break;
                }
            }
        }
        return PPCart_Mcp_Input::native_row($safe, $definitions);
    }

    /** @since 1.1.0 @param mixed $name Role. @return bool */
    public static function safe_role($name)
    {
        if (! is_string($name) || 'administrator' === $name || ppcart_is_privileged_role($name) || ! $role = get_role($name)) {
            return false;
        }
        foreach ([$name => true] + $role->capabilities as $capability => $granted) {
            if (
                $granted && (0 === strpos($capability, 'manage_capabilities') || 0 === strpos($capability, 'manage_network')
                || in_array($capability, ['manage_options', 'edit_users', 'promote_users', 'create_users', 'delete_users', 'remove_users',
                    'activate_plugins', 'install_plugins', 'update_plugins', 'delete_plugins', 'edit_plugins', 'edit_themes', 'switch_themes',
                    'install_themes', 'update_themes', 'delete_themes', 'update_core', 'edit_files', 'unfiltered_html', 'unfiltered_upload',
                    'resume_plugins', 'resume_themes', 'manage_sites', 'create_sites', 'delete_sites', 'upgrade_network', 'setup_network',
                    'add_users', 'promote_user', 'edit_user', 'delete_user', 'deactivate_plugins', 'activate_plugin', 'deactivate_plugin',
                    'resume_plugin', 'resume_theme', 'upload_plugins', 'upload_themes', 'install_languages', 'update_languages',
                    'edit_css', 'customize', 'delete_site', 'update_php', 'update_https', 'manage_privacy_options',
                    'export_others_personal_data', 'erase_others_personal_data'], true))
            ) {
                return false;
            }
        }
        return true;
    }
}
