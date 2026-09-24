<?php
if (! defined('ABSPATH')) {
    exit;
}

$payment_key       = str_replace($plugin_name . '-', '', $section['id']);
$rendered_payment_keys[] = $payment_key;
$payment_info      = $payment_method_meta[ $payment_key ] ?? [];
$method_title      = $payment_info['title'] ?? $section['title'];
$method_panel_title = $payment_info['panel_title'] ?? $method_title;
$method_desc       = $payment_info['description'] ?? '';
$method_logo_label = $payment_info['logo_label'] ?? mb_substr(wp_strip_all_tags($method_title), 0, 2);
$method_logo_url   = ! empty($payment_info['logo_file']) ? $get_admin_asset_url($payment_info['logo_file']) : '';
$method_manage     = $payment_info['manage_label'] ?? __('Manage', 'publishpress-cart');
$enable_field      = null;
$stripe_mode_field = null;
$paypal_mode_field = null;
$panel_fields      = [];

foreach ($section_fields as $field_id => $field) {
    $field_callback = $field['callback'] ?? null;
    $field_args     = isset($field['args']) && is_array($field['args']) ? $field['args'] : [];
    $option_id      = isset($field_args['id']) ? (string) $field_args['id'] : '';
    $field_title    = isset($field['title']) ? wp_strip_all_tags((string) $field['title']) : '';
    $is_checkbox    = is_array($field_callback) && isset($field_callback[1]) && 'field_checkbox' === $field_callback[1];
    $is_enable      = $is_checkbox && (false !== stripos($field_title, 'enable') || false !== strpos($option_id, '_enable'));

    if ($is_enable && null === $enable_field) {
        $enable_field = $field;
    }

    $is_stripe_mode_field = 'stripe' === $payment_key
        && (
            '_ppcart_stripe_api' === $option_id
            || 'stripe-api' === (string) $field_id
        );

    if ($is_stripe_mode_field) {
        $stripe_mode_field = $field;
    }

    $is_paypal_mode_field = 'paypal' === $payment_key
        && (
            '_ppcart_paypal_enable_sandbox' === $option_id
            || 'paypal-sandbox-enable' === (string) $field_id
            || (false !== stripos($field_title, 'sandbox') && false !== stripos($field_title, 'enable'))
        );

    if ($is_paypal_mode_field) {
        $paypal_mode_field = $field;
    }

    $panel_fields[ $field_id ] = $field;
}

$enable_args      = isset($enable_field['args']) && is_array($enable_field['args']) ? $enable_field['args'] : [];
$enable_option_id = isset($enable_args['id']) ? (string) $enable_args['id'] : '';
$stripe_mode_args = isset($stripe_mode_field['args']) && is_array($stripe_mode_field['args']) ? $stripe_mode_field['args'] : [];
$stripe_mode_id   = isset($stripe_mode_args['id']) ? (string) $stripe_mode_args['id'] : '_ppcart_stripe_api';
$paypal_mode_args = isset($paypal_mode_field['args']) && is_array($paypal_mode_field['args']) ? $paypal_mode_field['args'] : [];
$paypal_mode_id   = isset($paypal_mode_args['id']) ? (string) $paypal_mode_args['id'] : '_ppcart_paypal_enable_sandbox';
$panel_header_option_ids = array_filter([ $enable_option_id ]);

if ('stripe' === $payment_key && $stripe_mode_field) {
    $panel_header_option_ids[] = $stripe_mode_id;
}

if ('paypal' === $payment_key && $paypal_mode_field) {
    $panel_header_option_ids[] = $paypal_mode_id;
}

$is_enabled       = $enable_option_id ? (bool) get_option($enable_option_id) : false;
$payment_status   = $get_payment_method_status($payment_key, $is_enabled);
$has_panel        = ! empty($panel_fields);
$panel_id         = 'ppcart-payment-panel-' . sanitize_html_class($payment_key);
?>
                                    <div class="ppcart-settings__payment-method ppcart-settings__payment-method--<?php echo esc_attr(sanitize_html_class($payment_key)); ?><?php echo $is_enabled ? ' is-enabled' : ' is-disabled'; ?>"
                                         data-pp-payment-method="<?php echo esc_attr($payment_key); ?>"
                                        <?php if ($has_panel) : ?>
                                            data-pp-payment-panel="<?php echo esc_attr($panel_id); ?>"
                                        <?php endif; ?>
                                         role="listitem">
                                        <span class="ppcart-settings__payment-logo" aria-hidden="true">
                                            <?php if ($method_logo_url) : ?>
                                                <img src="<?php echo esc_url($method_logo_url); ?>" alt="" loading="lazy" />
                                            <?php else : ?>
                                                <?php echo esc_html($method_logo_label); ?>
                                            <?php endif; ?>
                                        </span>
                                        <span class="ppcart-settings__payment-summary">
                                            <span class="ppcart-settings__payment-title"><?php echo esc_html($method_title); ?></span>
                                            <?php if ($method_desc) : ?>
                                                <span class="ppcart-settings__payment-desc"><?php echo esc_html($method_desc); ?></span>
                                            <?php endif; ?>
                                        </span>
                                        <span class="ppcart-settings__payment-status <?php echo esc_attr($payment_status['class']); ?>"
                                              data-pp-payment-status="<?php echo esc_attr($payment_key); ?>"
                                              data-enabled-label="<?php echo esc_attr($payment_status['enabled_label']); ?>"
                                              data-disabled-label="<?php echo esc_attr($payment_status['disabled_label']); ?>">
                                            <?php echo esc_html($payment_status['label']); ?>
                                        </span>
                                        <?php
    if ($enable_field) {
        $render_payment_toggle_control($enable_field, $payment_key, $method_title, 'row');
    }
?>
                                        <span class="ppcart-settings__payment-action">
                                            <?php if ($has_panel) : ?>
                                                <button type="button"
                                                        class="button ppcart-settings__payment-manage"
                                                        data-pp-payment-manage="<?php echo esc_attr($payment_key); ?>"
                                                        data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-payment-' . $payment_key . '-manage')); ?>"
                                                        aria-controls="<?php echo esc_attr($panel_id); ?>"
                                                        aria-expanded="false">
                                                    <?php echo esc_html($method_manage); ?>
                                                </button>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <?php

if ($has_panel) {
    ob_start();
    ?>
                                        <aside class="ppcart-settings__payment-panel ppcart-settings__payment-panel--<?php echo esc_attr(sanitize_html_class($payment_key)); ?>"
                                               id="<?php echo esc_attr($panel_id); ?>"
                                               data-pp-payment-detail="<?php echo esc_attr($payment_key); ?>"
                                               role="dialog"
                                               aria-modal="true"
                                               aria-labelledby="<?php echo esc_attr($panel_id . '-title'); ?>"
                                               hidden>
                                            <div class="ppcart-settings__payment-panel-scroll">
                                                <div class="ppcart-settings__payment-panel-header">
                                                    <button type="button" class="ppcart-settings__payment-panel-close" data-pp-payment-close data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-payment-' . $payment_key . '-close')); ?>" aria-label="<?php esc_attr_e('Close payment method settings', 'publishpress-cart'); ?>">&times;</button>
                                                    <div class="ppcart-settings__payment-panel-title-row">
                                                        <span class="ppcart-settings__payment-logo" aria-hidden="true">
                                                            <?php if ($method_logo_url) : ?>
                                                                <img src="<?php echo esc_url($method_logo_url); ?>" alt="" loading="lazy" />
                                                            <?php else : ?>
                                                                <?php echo esc_html($method_logo_label); ?>
                                                            <?php endif; ?>
                                                        </span>
                                                        <span class="ppcart-settings__payment-panel-heading">
                                                            <span class="ppcart-settings__payment-panel-title" id="<?php echo esc_attr($panel_id . '-title'); ?>"><?php echo esc_html($method_panel_title); ?></span>
                                                            <span class="ppcart-settings__payment-status <?php echo esc_attr($payment_status['class']); ?>"
                                                                  data-pp-payment-status="<?php echo esc_attr($payment_key); ?>"
                                                                  data-enabled-label="<?php echo esc_attr($payment_status['enabled_label']); ?>"
                                                                  data-disabled-label="<?php echo esc_attr($payment_status['disabled_label']); ?>">
                                                                <?php echo esc_html($payment_status['label']); ?>
                                                            </span>
                                                        </span>
                                                    </div>
                                                    <?php if ($enable_field) : ?>
                                                        <div class="ppcart-settings__payment-panel-enable">
                                                            <span><?php
                                                            echo esc_html(sprintf(
                                                                /* translators: %s: payment method name. */
                                                                __('Enable %s', 'publishpress-cart'),
                                                                $method_title
                                                            )); ?></span>
                                                            <?php $render_payment_toggle_control($enable_field, $payment_key, $method_title, 'panel'); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if ($method_desc) : ?>
                                                        <p><?php echo esc_html($method_desc); ?></p>
                                                    <?php endif; ?>
                                                    <?php if ('stripe' === $payment_key && $stripe_mode_field) : ?>
                                                        <?php
                    $stripe_mode_name  = isset($stripe_mode_args['name']) ? (string) $stripe_mode_args['name'] : $stripe_mode_id;
                                                        $stripe_mode_value = (string) get_option($stripe_mode_id, 'test');
                                                        $stripe_mode_value = 'live' === $stripe_mode_value ? 'live' : 'test';
                                                        ?>
                                                        <div class="ppcart-settings__payment-mode ppcart-settings__payment-mode--stripe" data-pp-stripe-mode>
                                                            <input type="hidden"
                                                                   id="<?php echo esc_attr($stripe_mode_id); ?>"
                                                                   name="<?php echo esc_attr($stripe_mode_name); ?>"
                                                                   value="<?php echo esc_attr($stripe_mode_value); ?>"
                                                                   data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-payment-stripe-mode-value')); ?>"
                                                                   data-pp-stripe-mode-value />
                                                            <button type="button"
                                                                    class="ppcart-settings__payment-mode-option<?php echo 'test' === $stripe_mode_value ? ' is-active' : ''; ?>"
                                                                    data-pp-stripe-mode-option="test"
                                                                    data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-payment-stripe-mode-test')); ?>"
                                                                    aria-pressed="<?php echo 'test' === $stripe_mode_value ? 'true' : 'false'; ?>">
                                                                <?php esc_html_e('Test', 'publishpress-cart'); ?>
                                                            </button>
                                                            <button type="button"
                                                                    class="ppcart-settings__payment-mode-option<?php echo 'live' === $stripe_mode_value ? ' is-active' : ''; ?>"
                                                                    data-pp-stripe-mode-option="live"
                                                                    data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-payment-stripe-mode-live')); ?>"
                                                                    aria-pressed="<?php echo 'live' === $stripe_mode_value ? 'true' : 'false'; ?>">
                                                                <?php esc_html_e('Live', 'publishpress-cart'); ?>
                                                            </button>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if ('paypal' === $payment_key && $paypal_mode_field) : ?>
                                                        <?php
                                                        $paypal_mode_name  = isset($paypal_mode_args['name']) ? (string) $paypal_mode_args['name'] : $paypal_mode_id;
                                                        $paypal_mode_value = (string) get_option($paypal_mode_id, 'enable');
                                                        $paypal_mode_value = 'disable' === $paypal_mode_value ? 'disable' : 'enable';
                                                        ?>
                                                        <div class="ppcart-settings__payment-mode" data-pp-paypal-mode>
                                                            <input type="hidden"
                                                                   id="<?php echo esc_attr($paypal_mode_id); ?>"
                                                                   name="<?php echo esc_attr($paypal_mode_name); ?>"
                                                                   value="<?php echo esc_attr($paypal_mode_value); ?>"
                                                                   data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-payment-paypal-mode-value')); ?>"
                                                                   data-pp-paypal-mode-value />
                                                            <button type="button"
                                                                    class="ppcart-settings__payment-mode-option<?php echo 'enable' === $paypal_mode_value ? ' is-active' : ''; ?>"
                                                                    data-pp-paypal-mode-option="enable"
                                                                    data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-payment-paypal-mode-sandbox')); ?>"
                                                                    aria-pressed="<?php echo 'enable' === $paypal_mode_value ? 'true' : 'false'; ?>">
                                                                <?php esc_html_e('Sandbox', 'publishpress-cart'); ?>
                                                            </button>
                                                            <button type="button"
                                                                    class="ppcart-settings__payment-mode-option<?php echo 'disable' === $paypal_mode_value ? ' is-active' : ''; ?>"
                                                                    data-pp-paypal-mode-option="disable"
                                                                    data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-payment-paypal-mode-live')); ?>"
                                                                    aria-pressed="<?php echo 'disable' === $paypal_mode_value ? 'true' : 'false'; ?>">
                                                                <?php esc_html_e('Live', 'publishpress-cart'); ?>
                                                            </button>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>

                                                <div class="ppcart-settings__payment-panel-body">
                                                    <table class="form-table" role="presentation">
                                                        <?php
                                                        foreach ($panel_fields as $field) {
                                                            $field_args = isset($field['args']) && is_array($field['args']) ? $field['args'] : [];
                                                            $option_id  = isset($field_args['id']) ? (string) $field_args['id'] : '';
                                                            $row_attrs  = [];

                                                            if ($option_id && in_array($option_id, $panel_header_option_ids, true)) {
                                                                continue;
                                                            }

                                                            if ('paypal' === $payment_key) {
                                                                $paypal_live_fields = [ '_ppcart_paypal_email', '_ppcart_paypal_client_id', '_ppcart_paypal_secret', '_ppcart_paypal_pdt_token' ];
                                                                $paypal_sandbox_fields = [ '_ppcart_paypal_sandbox_email', '_ppcart_paypal_sandbox_client_id', '_ppcart_paypal_sandbox_secret', '_ppcart_paypal_sandbox_pdt_token' ];

                                                                if (in_array($option_id, $paypal_live_fields, true)) {
                                                                    $row_attrs['class'] = 'ppcart-settings__paypal-mode-field';
                                                                    $row_attrs['data-pp-paypal-mode-field'] = 'live';
                                                                } elseif (in_array($option_id, $paypal_sandbox_fields, true)) {
                                                                    $row_attrs['class'] = 'ppcart-settings__paypal-mode-field';
                                                                    $row_attrs['data-pp-paypal-mode-field'] = 'sandbox';
                                                                }
                                                            }

                                                            $render_setting_field_row($field, $row_attrs);
                                                        }
    ?>
                                                    </table>
                                                </div>
                                            </div>
                                            <div class="ppcart-settings__payment-panel-footer">
                                                <button type="button" class="button button-secondary" data-pp-payment-close data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-payment-' . $payment_key . '-cancel')); ?>"><?php esc_html_e('Cancel', 'publishpress-cart'); ?></button>
                                                <button type="submit" name="submit" class="button button-primary ppcart-settings__save-button" value="<?php esc_attr_e('Save changes', 'publishpress-cart'); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-payment-' . $payment_key . '-save')); ?>">
                                                    <span class="ppcart-settings__save-button-label">
                                                        <?php
    printf(
        /* translators: %s is the payment method name. */
        esc_html__('Save %s', 'publishpress-cart'),
        esc_html($method_title)
    );
    ?>
                                                    </span>
                                                    <span class="ppcart-settings__save-button-spinner" aria-hidden="true"></span>
                                                </button>
                                            </div>
                                        </aside>
                                        <?php
    $payment_panels_html .= ob_get_clean();
}
