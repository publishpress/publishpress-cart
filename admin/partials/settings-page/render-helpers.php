<?php
if (! defined('ABSPATH')) {
    exit;
}

$get_payment_method_status = static function ($payment_key, $is_enabled) {
    $disabled_label = __('Disabled', 'publishpress-cart');
    $enabled_label  = __('Enabled', 'publishpress-cart');
    $status_class   = $is_enabled ? 'is-enabled' : 'is-disabled';

    if ('stripe' === $payment_key) {
        $mode          = sanitize_text_field((string) get_option('_ppcart_stripe_api', 'test'));
        $enabled_label = 'live' === $mode ? __('Live mode', 'publishpress-cart') : __('Test mode', 'publishpress-cart');
        $status_class  = $is_enabled ? ('live' === $mode ? 'is-live' : 'is-test') : 'is-disabled';
    }

    if ('paypal' === $payment_key) {
        $sandbox       = sanitize_text_field((string) get_option('_ppcart_paypal_enable_sandbox', 'enable'));
        $enabled_label = 'disable' === $sandbox ? __('Live mode', 'publishpress-cart') : __('Sandbox', 'publishpress-cart');
        $status_class  = $is_enabled ? ('disable' === $sandbox ? 'is-live' : 'is-test') : 'is-disabled';
    }

    return [
        'label'          => $is_enabled ? $enabled_label : $disabled_label,
        'class'          => $status_class,
        'enabled_label'  => $enabled_label,
        'disabled_label' => $disabled_label,
    ];
};

$has_meaningful_option_value = static function ($option_name) {
    $option_value = get_option($option_name, '');

    if (is_array($option_value)) {
        return ! empty($option_value);
    }

    $option_value = trim((string) $option_value);

    return '' !== $option_value && '0' !== $option_value;
};

$render_payment_toggle_control = static function ($field, $payment_key, $method_title, $context) {
    $field_args       = isset($field['args']) && is_array($field['args']) ? $field['args'] : [];
    $option_id        = isset($field_args['id']) ? (string) $field_args['id'] : '';
    $field_id         = isset($field_args['rid']) ? (string) $field_args['rid'] : $option_id;
    $field_id         = $field_id ? $field_id . '-' . $context : 'ppcart-payment-' . $payment_key . '-' . $context;
    $field_name       = isset($field_args['name']) ? (string) $field_args['name'] : $option_id;
    $field_class      = isset($field_args['class']) ? (string) $field_args['class'] : '';
    $is_enabled       = $option_id ? (bool) get_option($option_id) : false;
    $toggle_label     = sprintf(
        /* translators: %s: payment method name. */
        __('Enable %s', 'publishpress-cart'),
        $method_title
    );
    ?>
    <span class="ppcart-settings__payment-toggle-wrap">
        <?php
        // Row and panel toggles deliberately share one option name; JS keeps their checked states identical.
    ?>
        <input type="checkbox"
               id="<?php echo esc_attr($field_id); ?>"
               class="<?php echo esc_attr($field_class); ?>"
               value="1"
               data-pp-payment-toggle="<?php echo esc_attr($payment_key); ?>"
               data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-payment-' . $payment_key . '-' . $context . '-toggle')); ?>"
               name="<?php echo esc_attr($field_name); ?>"
            <?php checked(true, $is_enabled); ?> />
        <label for="<?php echo esc_attr($field_id); ?>">
            <span aria-hidden="true"></span>
            <span class="screen-reader-text"><?php echo esc_html($toggle_label); ?></span>
        </label>
    </span>
    <?php
};

$render_setting_field_row = static function ($field, $extra_attrs = []) {
    $field_args = isset($field['args']) && is_array($field['args']) ? $field['args'] : [];
    $classes    = [];

    if (isset($field_args['show_in_ui']) && false === $field_args['show_in_ui']) {
        return;
    }

    if (! empty($field_args['class'])) {
        $classes[] = $field_args['class'];
    }

    if (! empty($extra_attrs['class'])) {
        $classes[] = $extra_attrs['class'];
    }

    echo '<tr';
    if (! empty($classes)) {
        echo ' class="' . esc_attr(trim(implode(' ', $classes))) . '"';
    }

    foreach ($extra_attrs as $attr => $value) {
        if ('class' === $attr || '' === (string) $value || ! preg_match('/^[a-z0-9_-]+$/', $attr)) {
            continue;
        }

        echo ' ' . esc_attr($attr) . '="' . esc_attr($value) . '"';
    }

    echo '>';

    if (! empty($field_args['label_for'])) {
        echo '<th scope="row"><label for="' . esc_attr($field_args['label_for']) . '">' . esc_html($field['title']) . '</label></th>';
    } else {
        echo '<th scope="row">' . esc_html($field['title']) . '</th>';
    }

    echo '<td>';
    if (isset($field['callback']) && is_callable($field['callback'])) {
        call_user_func($field['callback'], $field_args);
    }
    echo '</td>';
    echo '</tr>';
};

/**
 * Renders a single locked "Pro feature" preview card.
 *
 * Reuses the payment/integration card markup so locked cards line up with the
 * real ones, then dims the content and drops in a "Pro feature" lock button in
 * place of the toggle/manage controls.
 *
 * @param array $args {
 *     @type string $type        'payment' or 'integration'.
 *     @type string $key         Card slug (used for CSS + integration search).
 *     @type string $title       Card title.
 *     @type string $description Card description.
 *     @type string $logo_url    Resolved logo image URL (optional).
 *     @type string $logo_label  Fallback initials when no logo image.
 *     @type string $category    Integration category slug (integration cards).
 *     @type string $context     Placement context passed to the upgrade URL.
 * }
 */
$render_locked_pro_card = static function ($args) {
    if (! function_exists('ppcart_pro_feature_lock')) {
        return;
    }

    $args = wp_parse_args(
        $args,
        [
            'type'        => 'payment',
            'key'         => '',
            'title'       => '',
            'description' => '',
            'logo_url'    => '',
            'logo_label'  => '',
            'category'    => '',
            'context'     => '',
        ]
    );

    $is_integration = ('integration' === $args['type']);
    $classes        = [ 'ppcart-settings__payment-method', 'ppcart-settings__payment-method--locked' ];

    if ($is_integration) {
        $classes[] = 'ppcart-settings__integration-card';
        $classes[] = 'ppcart-settings__integration-card--' . sanitize_html_class($args['key']);
    } else {
        $classes[] = 'ppcart-settings__payment-method--' . sanitize_html_class($args['key']);
    }

    // Integration cards stay searchable/filterable in the directory; they carry
    // no panel, so the card click handler simply no-ops for them.
    ?>
    <div class="<?php echo esc_attr(implode(' ', $classes)); ?>"<?php
    if ($is_integration) {
        echo ' data-pp-integration-card="' . esc_attr($args['key']) . '"';
        echo ' data-pp-integration-category="' . esc_attr($args['category']) . '"';
        echo ' data-pp-integration-locked="1"';
    }
    ?> role="listitem">
        <span class="ppcart-settings__payment-logo<?php echo $is_integration ? ' ppcart-settings__integration-logo' : ''; ?>" aria-hidden="true">
            <?php if (! empty($args['logo_url'])) : ?>
                <img src="<?php echo esc_url($args['logo_url']); ?>" alt="" loading="lazy" />
            <?php else : ?>
                <?php echo esc_html($args['logo_label']); ?>
            <?php endif; ?>
        </span>
        <span class="ppcart-settings__payment-summary">
            <span class="ppcart-settings__payment-title"><?php echo esc_html($args['title']); ?></span>
            <?php if (! empty($args['description'])) : ?>
                <span class="ppcart-settings__payment-desc"><?php echo esc_html($args['description']); ?></span>
            <?php endif; ?>
        </span>
        <span class="ppcart-settings__pro-lock-action">
            <?php
            echo wp_kses_post(ppcart_pro_feature_lock([ 'context' => $args['context'] ]));
    ?>
        </span>
    </div>
    <?php
};

$get_debug_log_data = static function () {
    $empty = [
        'file_name'       => '',
        'exists'          => false,
        'size'            => 0,
        'size_label'      => '0 B',
        'modified'        => 0,
        'modified_label'  => __('Not written yet', 'publishpress-cart'),
        'preview'         => '',
        'truncated'       => false,
        'view_url'        => '#',
        'download_url'    => '#',
        'clear_url'       => '#',
    ];

    if (! class_exists('PPCart_Debug_Logger')) {
        return $empty;
    }

    $data = PPCart_Debug_Logger::get_admin_log_data();

    return is_array($data) ? $data : $empty;
};

$get_stripe_webhook_log_data = static function () {
    if (class_exists('PPCart_Stripe_Webhook_Logger')) {
        return PPCart_Stripe_Webhook_Logger::get_admin_log_data();
    }

    return [
        'file_name'      => __('Not available', 'publishpress-cart'),
        'exists'         => false,
        'size'           => 0,
        'size_label'     => '0 B',
        'modified'       => 0,
        'modified_label' => __('Not written yet', 'publishpress-cart'),
        'entries'        => [],
        'preview'        => '',
        'view_url'       => '#',
        'download_url'   => '#',
        'clear_url'      => '#',
    ];
};

$get_tab_page_slug = static function ($tab_slug) use ($plugin_name, $tab_page_map) {
    $sec_name = $tab_page_map[ $tab_slug ] ?? '';

    return ('' === $sec_name) ? $plugin_name : $plugin_name . '-' . strtolower($sec_name);
};

$section_belongs_to_tab = static function ($tab_slug, $section_id) use ($plugin_name, $general_split_sections, $general_known_section_ids) {
    if (isset($general_split_sections[ $tab_slug ])) {
        return in_array($section_id, $general_split_sections[ $tab_slug ], true);
    }

    if ('advanced' === $tab_slug) {
        return ! in_array($section_id, $general_known_section_ids, true);
    }

    if ('emails' === $tab_slug) {
        return $plugin_name . '-email_settings' === $section_id
            || 0 === strpos($section_id, $plugin_name . '-emailtemplate_');
    }

    if ('reports' === $tab_slug) {
        return $plugin_name . '-email_reports' === $section_id;
    }

    return true;
};

global $wp_settings_sections, $wp_settings_fields;

$tab_has_registered_fields = static function ($tab_slug) use ($get_tab_page_slug, $section_belongs_to_tab, $wp_settings_sections, $wp_settings_fields) {
    $page_slug = $get_tab_page_slug($tab_slug);

    if (! isset($wp_settings_sections[ $page_slug ])) {
        return false;
    }

    foreach ((array) $wp_settings_sections[ $page_slug ] as $section) {
        if (empty($section['id']) || ! $section_belongs_to_tab($tab_slug, $section['id'])) {
            continue;
        }

        if (! empty($wp_settings_fields[ $page_slug ][ $section['id'] ])) {
            return true;
        }
    }

    return false;
};

foreach ([ 'pages', 'branding', 'downloads', 'reports', 'debug', 'advanced' ] as $optional_tab_slug) {
    if (isset($setting_tabs[ $optional_tab_slug ]) && ! $tab_has_registered_fields($optional_tab_slug)) {
        unset($setting_tabs[ $optional_tab_slug ]);
    }
}
