<?php

/**
 * Typed store settings with native sanitization and checked persistence.
 *
 * @package PPCart
 * @since 1.1.0
 */

defined('ABSPATH') || exit;

class PPCart_Mcp_Settings
{
    private $settings;

    private $definitions_cache;

    /**
     * @since 1.1.0
     * @param PPCart_Admin_Settings $settings Existing native settings instance.
     */
    public function __construct($settings)
    {
        $this->settings = $settings;
    }

    /**
     * Return an explicit allowlist, never an arbitrary option dispatcher.
     *
     * @since 1.1.0
     * @return array
     */
    public function definitions()
    {
        if (null !== $this->definitions_cache) {
            return $this->definitions_cache;
        }
        $ids = [
            '_ppcart_tax_enable', '_ppcart_tax_type', '_ppcart_price_show_with_tax', '_ppcart_vat_enable', '_ppcart_vat_merchant_state', '_ppcart_vat_all_eu_businesses', '_ppcart_vat_disable_vies_database_lookup', '_ppcart_vat_reverse_charge', '_ppcart_enable_invoice_number', '_ppcart_invoice_attach_pending_email', '_ppcart_invoice_attach_confirmation_email', '_ppcart_invoice_attach_renewal_email', '_ppcart_invoice_prefix', '_ppcart_invoice_sufix', '_ppcart_invoice_length', '_ppcart_invoice_format', '_ppcart_invoice_start_number',
            '_ppcart_enable_debug', '_ppcart_enable_stripe_webhook_log', '_ppcart_stripe_webhook_log_include_ignored',
            '_ppcart_fb_add_payment_info', '_ppcart_fb_purchase', '_ppcart_fb_lead', '_ppcart_ga_purchase', '_ppcart_ga_lead', '_ppcart_ga_type',
            '_ppcart_googlerecaptchav2_captcha_type', '_ppcart_googlerecaptcha_v3rating',
            '_ppcart_email_failed_body', '_ppcart_email_canceled_body',
            '_ppcart_email_completed_enable', '_ppcart_email_completed_subject', '_ppcart_email_completed_admin',
            '_ppcart_email_completed_headline', '_ppcart_email_completed_body',
            'ppcart_download_slug', 'ppcart_login_to_download',
            PPCart_Product_Duplicator::OPTION_NAME,
            '_ppcart_cashondelivery_enable', '_ppcart_stripe_enable', '_ppcart_stripe_api',
            '_ppcart_stripe_express_payment_enable', '_ppcart_stripe_customer_portal_enable',
            '_ppcart_stripe_descriptor', '_ppcart_stripe_invoice_webhook_updates_only',
            '_ppcart_paypal_enable', '_ppcart_paypal_enable_sandbox',
            '_ppcart_currency', '_ppcart_country', '_ppcart_currency_position',
            '_ppcart_thousand_separator', '_ppcart_decimal_separator', '_ppcart_decimal_number',
            '_ppcart_myaccount_page_id', '_ppcart_myaccount_hide_free', '_ppcart_terms_url', '_ppcart_privacy_url',
            '_ppcart_company_name', '_ppcart_company_address', '_ppcart_company_logo',
            'ppcart_admin_email', 'ppcart_report_schedule',
            '_ppcart_email_from_name', '_ppcart_email_from_email', '_ppcart_email_reply_to', '_ppcart_email_footer_text',
            '_ppcart_email_pending_enable', '_ppcart_email_pending_subject', '_ppcart_email_pending_admin',
            '_ppcart_email_pending_headline', '_ppcart_email_pending_body',
            '_ppcart_email_confirmation_enable', '_ppcart_email_confirmation_subject', '_ppcart_email_confirmation_admin',
            '_ppcart_email_confirmation_headline', '_ppcart_email_confirmation_body',
            '_ppcart_email_registration_enable', '_ppcart_use_wp_notification', '_ppcart_registration_subject',
            '_ppcart_registration_email_admin', '_ppcart_registration_email_body',
            '_ppcart_email_refunded_enable', '_ppcart_email_refunded_subject', '_ppcart_email_refunded_admin', '_ppcart_email_refunded_body',
            '_ppcart_email_renewal_enable', '_ppcart_email_renewal_subject', '_ppcart_email_renewal_admin', '_ppcart_email_renewal_body',
            '_ppcart_email_failed_enable', '_ppcart_email_failed_subject', '_ppcart_email_failed_admin',
            '_ppcart_email_canceled_enable', '_ppcart_email_canceled_subject', '_ppcart_email_canceled_admin',
        ];
        $ids = apply_filters('ppcart_mcp_setting_ids', $ids);
        $definitions = [];
        foreach ($this->settings->get_options_list(true) as $section => $fields) {
            foreach ($fields as $field) {
                $options = $field['settings'] ?? [];
                $id = $options['id'] ?? '';
                if (! in_array($id, $ids, true) || ! empty($options['secret']) || ! empty($options['readonly']) || ! empty($options['disabled']) || isset($options['force_value']) || ! empty($options['skip_register'])) {
                    continue;
                }
                $schema = $this->field_schema($field);
                if (! $schema) {
                    continue;
                }
                $definitions[$id] = ['field' => $field, 'section' => $section, 'schema' => $schema];
            }
        }
        $this->definitions_cache = $definitions;
        return $definitions;
    }

    /** @since 1.1.0 @return bool Whether all parts of the native combined control are writable. */
    public function checkout_experience_writable()
    {
        $required = ['_ppcart_stripe_checkout_experience', '_ppcart_stripe_payment_element_enable', '_ppcart_stripe_hosted_checkout_enable'];
        $found = [];
        foreach ($this->settings->get_options_list(true) as $fields) {
            foreach ($fields as $field) {
                $options = $field['settings'] ?? [];
                $id = $options['id'] ?? '';
                if (in_array($id, $required, true)) {
                    if (! empty($options['readonly']) || ! empty($options['disabled']) || isset($options['force_value'])) {
                        return false;
                    }
                    $found[] = $id;
                }
            }
        }
        return ! array_diff($required, $found);
    }

    /**
     * Use native select keys as strings, including numeric-string page IDs.
     *
     * @since 1.1.0
     * @param array $field Native field definition.
     * @return array
     */
    private function field_schema(array $field)
    {
        $type = $field['type'] ?? '';
        if ('checkbox' === $type) {
            return ['type' => 'boolean'];
        }
        if ('number' === $type) {
            return ['type' => 'integer', 'minimum' => 0, 'maximum' => 2147483647];
        }
        if (in_array($field['settings']['id'] ?? '', apply_filters('ppcart_mcp_setting_page_ids', ['_ppcart_myaccount_page_id']), true)) {
            return ['type' => 'string', 'pattern' => '^(?:|[1-9][0-9]*)$', 'maxLength' => 20];
        }
        if ('select' === $type && false !== strpos($field['settings']['class'] ?? '', 'multiple')) {
            return ['type' => 'array', 'maxItems' => 100, 'items' => ['type' => 'string', 'maxLength' => 200]];
        }
        if ('select' === $type) {
            $choices = $field['settings']['selections'] ?? [];
            if (! is_array($choices) || ! $choices || count(array_filter($choices, 'is_array'))) {
                return [];
            }
            return ['type' => 'string', 'enum' => array_map('strval', array_keys($choices))];
        }
        if (in_array($type, ['text', 'textarea', 'editor', 'email', 'url', 'upload'], true)) {
            return ['type' => 'string', 'maxLength' => 'editor' === $type ? 100000 : 10000];
        }
        return [];
    }

    /**
     * Build the current edition's closed patch schema.
     *
     * @since 1.1.0
     * @return array
     */
    public function patch_schema()
    {
        $properties = [];
        foreach ($this->definitions() as $id => $definition) {
            $properties[$id] = $definition['schema'];
        }
        return PPCart_Mcp_Input::schema([
            'changes' => PPCart_Mcp_Input::schema($properties),
            'revision' => ['type' => 'string', 'minLength' => 64, 'maxLength' => 64],
            'dry_run' => ['type' => 'boolean'],
        ], ['changes', 'revision']);
    }

    /**
     * Read only allowlisted settings; never retrieve credentials.
     *
     * @since 1.1.0
     * @return array|WP_Error
     */
    public function read()
    {
        $this->definitions_cache = null;
        if (! current_user_can('manage_options')) {
            return $this->forbidden();
        }
        $values = [];
        $definitions = $this->definitions();
        $stored = PPCart_Mcp_Options::snapshots(array_keys($definitions));
        if (is_wp_error($stored)) {
            return $stored;
        }
        foreach ($definitions as $id => $definition) {
            if (
                $stored[$id]['exists'] && ! is_scalar($stored[$id]['value']) && null !== $stored[$id]['value']
                && ('array' !== $definition['schema']['type'] || is_wp_error(PPCart_Mcp_Input::validate($stored[$id]['value'], $definition['schema'])))
            ) {
                return PPCart_Mcp_Input::error();
            }
            $field = $definition['field'];
            $default = $field['settings']['value'] ?? '';
            $value = get_option($id, $default);
            // Keep the stored representation visible; the schema describes accepted writes.
            $values[$id] = [
                'label' => wp_strip_all_tags($field['label'] ?? $id),
                'section' => $definition['section'],
                'schema' => $definition['schema'],
                'choices' => (object) (in_array($id, apply_filters('ppcart_mcp_setting_page_ids', ['_ppcart_myaccount_page_id']), true) ? $this->page_choices() : ($field['settings']['selections'] ?? [])),
                'default' => $default,
                'value' => $value,
                'exists' => $stored[$id]['exists'],
                'stored_value' => $stored[$id]['value'],
            ];
        }
        return ['settings' => (object) $values, 'revision' => $this->revision($values)];
    }

    /**
     * Validate the complete patch before invoking any native option write.
     *
     * @since 1.1.0
     * @param mixed $input Decoded JSON.
     * @return array|WP_Error
     */
    public function update($input)
    {
        $this->definitions_cache = null;
        if (! current_user_can('manage_options')) {
            return $this->forbidden();
        }
        $input = PPCart_Mcp_Input::validate($input, $this->patch_schema());
        if (is_wp_error($input)) {
            return $input;
        }
        if (! $input['changes'] || count($input['changes']) > 50) {
            return PPCart_Mcp_Input::error();
        }
        $before = $this->read();
        if (is_wp_error($before)) {
            return $before;
        }
        if (! hash_equals($before['revision'], $input['revision'])) {
            return new WP_Error('ppcart_mcp_stale_revision', __('Settings changed. Read them again before saving.', 'publishpress-cart'));
        }
        $definitions = $this->definitions();
        $changes = [];
        $snapshots = PPCart_Mcp_Options::snapshots(array_keys($input['changes']));
        if (is_wp_error($snapshots)) {
            return $snapshots;
        }
        foreach ($input['changes'] as $id => $value) {
            $field = $definitions[$id]['field'];
            if (in_array($id, apply_filters('ppcart_mcp_setting_page_ids', ['_ppcart_myaccount_page_id']), true) && ! array_key_exists($value, $this->page_choices())) {
                return PPCart_Mcp_Input::error();
            }
            if ('array' === $definitions[$id]['schema']['type']) {
                foreach ($value as $choice) {
                    if (! array_key_exists($choice, $field['settings']['selections'] ?? [])) {
                        return PPCart_Mcp_Input::error();
                    }
                }
            }
            $clean = call_user_func($this->settings->get_field_sanitizer($field), $value);
            if (is_wp_error($clean) || (! is_scalar($clean) && ! ('array' === $definitions[$id]['schema']['type'] && is_array($clean))) || (is_string($value) && '' !== trim($value) && '' === $clean)) {
                return PPCart_Mcp_Input::error();
            }
            $changes[$id] = $clean;
        }
        $validation = apply_filters('ppcart_mcp_validate_setting_changes', null, $changes, $before);
        if (is_wp_error($validation)) {
            return $validation;
        }
        if (! empty($input['dry_run'])) {
            return ['dry_run' => true, 'changes' => (object) $changes, 'revision' => $before['revision']];
        }
        $written = [];
        foreach ($changes as $id => $value) {
            $written[] = $id;
            update_option($id, $value);
            $actual_snapshots = PPCart_Mcp_Options::snapshots([$id]);
            $actual_snapshot = is_wp_error($actual_snapshots) ? ['exists' => false, 'value' => null] : $actual_snapshots[$id];
            if (
                ! $actual_snapshot['exists'] || ! PPCart_Mcp_Options::same_value($actual_snapshot['value'], $value)
            ) {
                $restored = true;
                foreach (array_reverse($written) as $key) {
                    $current = PPCart_Mcp_Options::snapshots([$key]);
                    if (is_wp_error($current)) {
                        $restored = false;
                        continue;
                    }
                    if ($current[$key] === $snapshots[$key]) {
                        continue;
                    }
                    if (
                        ! $current[$key]['exists'] || ! PPCart_Mcp_Options::same_value($current[$key]['value'], $changes[$key])
                    ) {
                        $restored = false;
                        continue;
                    }
                    if ($snapshots[$key]['exists']) {
                        update_option($key, $snapshots[$key]['value']);
                    } else {
                        delete_option($key);
                    }
                    $actual_snapshots = PPCart_Mcp_Options::snapshots([$key]);
                    if (is_wp_error($actual_snapshots)) {
                        $restored = false;
                        continue;
                    }
                    $actual = $actual_snapshots[$key];
                    $restored = $restored && ($snapshots[$key]['exists']
                        ? $actual['exists'] && PPCart_Mcp_Options::same_value($actual['value'], $snapshots[$key]['value'])
                        : ! $actual['exists']);
                }
                return new WP_Error('ppcart_mcp_save_failed', $restored
                    ? __('Settings could not be saved. Previous values were restored.', 'publishpress-cart')
                    : __('Settings could not be fully restored. Read current settings before retrying.', 'publishpress-cart'));
            }
        }
        $this->definitions_cache = null;
        return $this->read();
    }

    /**
     * Native page selector choices, refreshed at execution rather than frozen in client schemas.
     *
     * @since 1.1.0
     * @return array
     */
    private function page_choices()
    {
        $choices = ['' => __('Select Page', 'publishpress-cart')];
        foreach (get_pages() as $page) {
            if (current_user_can('read_post', $page->ID)) {
                $choices[(string) $page->ID] = $page->post_title . ' (ID: ' . $page->ID . ')';
            }
        }
        return $choices;
    }

    /**
     * @since 1.1.0
     * @param array $values Safe state.
     * @return string
     */
    private function revision(array $values)
    {
        return hash('sha256', wp_json_encode($values));
    }

    /**
     * @since 1.1.0
     * @return WP_Error
     */
    private function forbidden()
    {
        return new WP_Error('ppcart_mcp_forbidden', __('You cannot manage Cart settings.', 'publishpress-cart'));
    }
}
