<?php
if (! defined('ABSPATH')) {
    exit;
}

foreach ($setting_tabs as $tab_slug => $tab_label) :
    $description = $tab_meta[$tab_slug]['description'] ?? '';
    $page_slug   = $get_tab_page_slug($tab_slug);
    ?>
    <section class="ppcart-settings__panel" id="content_tab_<?php echo esc_attr($tab_slug); ?>" data-pp-tab="<?php echo esc_attr($tab_slug); ?>" role="region" aria-label="<?php echo esc_attr($tab_label); ?>">

        <div class="ppcart-settings__panel-header">
            <div class="ppcart-settings__panel-title" role="heading" aria-level="2"><?php echo esc_html($tab_label); ?></div>
            <?php if ($description) : ?>
                <p class="ppcart-settings__panel-desc"><?php echo esc_html($description); ?></p>
            <?php endif; ?>
        </div>

        <?php
            // Render each WP settings section as its own modern card.

            // Locked tabs come in two flavours: complex tabs (Tax) blur
            // their content behind one overlay; simpler tabs (White Label)
            // show each field with a lock beside it.
            $is_pro_locked_tab = isset($pro_locked_tabs[$tab_slug]);
    $is_blurred_tab    = $is_pro_locked_tab && 'blur' === $pro_locked_tabs[$tab_slug];

    if ($is_blurred_tab) {
        echo '<div class="ppcart-settings__pro-tab-lock">';
        echo wp_kses_post(ppcart_pro_panel_lock_overlay(['context' => 'tab-' . $tab_slug]));
        echo '<div class="ppcart-settings__pro-tab-lock-content">';
    }

    if (isset($wp_settings_sections[$page_slug])) {
        $email_template_sections = [];
        $integration_panels_html = '';
        $payment_panels_html     = '';

        // Track real sections so locked Pro previews never duplicate
        // a gateway/integration that is already available.
        $rendered_payment_keys     = [];
        $rendered_integration_keys = [];

        if ('payment_methods' === $tab_slug) {
            ?>
                <div class="ppcart-settings__payment-layout">
                    <div class="ppcart-settings__payment-list" role="list" aria-label="<?php esc_attr_e('Payment methods', 'publishpress-cart'); ?>">
                    <?php
        }

        if ('integrations' === $tab_slug) {
            ?>
                        <div class="ppcart-settings__integrations-layout ppcart-settings__payment-layout">
                            <div class="ppcart-settings__integrations-list">
                                <div class="ppcart-settings__integrations-toolbar">
                                    <label class="ppcart-settings__integration-search" aria-label="<?php esc_attr_e('Search integrations', 'publishpress-cart'); ?>">
                                        <input type="search" placeholder="<?php esc_attr_e('Search integrations', 'publishpress-cart'); ?>" autocomplete="off" data-pp-integration-search data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-integrations-search')); ?>" />
                                    </label>
                                    <div class="ppcart-settings__integration-filters" role="tablist" aria-label="<?php esc_attr_e('Integration categories', 'publishpress-cart'); ?>">
                                        <?php foreach ($integration_categories as $category_slug => $category_label) : ?>
                                            <button type="button" class="ppcart-settings__integration-filter<?php echo 'all' === $category_slug ? ' is-active' : ''; ?>" data-pp-integration-filter="<?php echo esc_attr($category_slug); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-integrations-filter-' . $category_slug)); ?>" aria-selected="<?php echo 'all' === $category_slug ? 'true' : 'false'; ?>" role="tab">
                                                <?php echo esc_html($category_label); ?>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="ppcart-settings__integrations-listbox ppcart-settings__payment-list" role="list" aria-label="<?php esc_attr_e('Integrations', 'publishpress-cart'); ?>">
                                    <?php
        }

        foreach ((array) $wp_settings_sections[$page_slug] as $section) {
            if (empty($section['id']) || ! $section_belongs_to_tab($tab_slug, $section['id'])) {
                continue;
            }

            $has_fields = isset($wp_settings_fields[$page_slug][$section['id']]);

            // Skip empty sections (no fields). Our default
            // section_settings() callback is a no-op so an
            // empty section just renders an orphan card.
            if (! $has_fields) {
                continue;
            }

            if ('emails' === $tab_slug && 0 === strpos($section['id'], $plugin_name . '-emailtemplate_')) {
                $email_template_sections[] = $section;
                continue;
            }

            if ('maintenance' === $tab_slug && $plugin_name . '-maintenance-secrets' === $section['id']) {
                ?>
                                        <div class="ppcart-settings__card ppcart-settings__card--plain ppcart-settings__maintenance-secrets" data-section-id="<?php echo esc_attr($section['id']); ?>">
                                            <?php if (! empty($section['title'])) : ?>
                                                <h2 class="ppcart-settings__card-title"><?php echo esc_html($section['title']); ?></h2>
                                            <?php endif; ?>
                                            <?php
                        if (class_exists('PPCart_Secrets')) {
                            echo wp_kses(PPCart_Secrets::render_maintenance_secret_storage_html(), ppcart_admin_allowed_html());
                        }
                ?>
                                        </div>
                                    <?php
                                        continue;
            }

            if ('maintenance' === $tab_slug && $plugin_name . '-maintenance-db-schema' === $section['id']) {
                ?>
                                        <div class="ppcart-settings__card ppcart-settings__card--plain ppcart-settings__maintenance-db-schema" data-section-id="<?php echo esc_attr($section['id']); ?>">
                                            <?php if (! empty($section['title'])) : ?>
                                                <h2 class="ppcart-settings__card-title"><?php echo esc_html($section['title']); ?></h2>
                                            <?php endif; ?>
                                            <?php
                        if (class_exists('PPCart_DB_Schema')) {
                            echo wp_kses(PPCart_DB_Schema::admin()->render_maintenance_html(), ppcart_admin_allowed_html());
                        }
                ?>
                                        </div>
                                    <?php
                                        continue;
            }

            if ('debug' === $tab_slug && $plugin_name . '-debug' === $section['id']) {
                $debug_enabled          = (bool) get_option('_ppcart_enable_debug');
                $debug_log              = $get_debug_log_data();
                $stripe_webhook_enabled = (bool) get_option(PPCart_Stripe_Webhook_Logger::ENABLE_OPTION, 1);
                $stripe_webhook_log     = $get_stripe_webhook_log_data();
                ?>
                                        <div class="ppcart-settings__card ppcart-settings__card--plain ppcart-settings__debug-card" data-section-id="<?php echo esc_attr($section['id']); ?>">
                                            <div class="ppcart-settings__debug-card-main">
                                                <table class="form-table" role="presentation">
                                                    <?php do_settings_fields($page_slug, $section['id']); ?>
                                                </table>
                                            </div>
                                            <div class="ppcart-settings__debug-meta" <?php echo $debug_enabled ? '' : ' hidden'; ?> data-pp-debug-meta>
                                                <span>
                                                    <strong><?php esc_html_e('Log file:', 'publishpress-cart'); ?></strong>
                                                    <?php echo esc_html($debug_log['file_name']); ?>
                                                </span>
                                                <span>
                                                    <strong><?php esc_html_e('Size:', 'publishpress-cart'); ?></strong>
                                                    <?php echo esc_html($debug_log['size_label']); ?>
                                                </span>
                                                <span>
                                                    <strong><?php esc_html_e('Last written:', 'publishpress-cart'); ?></strong>
                                                    <?php echo esc_html($debug_log['modified_label']); ?>
                                                </span>
                                            </div>
                                            <div class="ppcart-settings__debug-meta" <?php echo $stripe_webhook_enabled ? '' : ' hidden'; ?> data-pp-stripe-webhook-log-meta>
                                                <span>
                                                    <strong><?php esc_html_e('Stripe webhook file:', 'publishpress-cart'); ?></strong>
                                                    <?php echo esc_html($stripe_webhook_log['file_name']); ?>
                                                </span>
                                                <span>
                                                    <strong><?php esc_html_e('Size:', 'publishpress-cart'); ?></strong>
                                                    <?php echo esc_html($stripe_webhook_log['size_label']); ?>
                                                </span>
                                                <span>
                                                    <strong><?php esc_html_e('Last written:', 'publishpress-cart'); ?></strong>
                                                    <?php echo esc_html($stripe_webhook_log['modified_label']); ?>
                                                </span>
                                            </div>
                                        </div>

                                        <div class="ppcart-settings__card ppcart-settings__card--plain ppcart-settings__debug-log-card" <?php echo $debug_enabled ? '' : ' hidden'; ?> data-pp-debug-log-card>
                                            <div class="ppcart-settings__debug-log-header">
                                                <h2><?php esc_html_e('Debug log', 'publishpress-cart'); ?></h2>
                                                <div class="ppcart-settings__debug-actions" <?php echo $debug_enabled ? '' : ' hidden'; ?> data-pp-debug-actions>
                                                    <a class="button button-secondary" href="<?php echo esc_url($debug_log['view_url']); ?>" target="_blank" rel="noopener noreferrer" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-debug-log-view')); ?>"><?php esc_html_e('View log', 'publishpress-cart'); ?></a>
                                                    <a class="button button-secondary" href="<?php echo esc_url($debug_log['download_url']); ?>" download="<?php echo esc_attr($debug_log['file_name']); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-debug-log-download')); ?>"><?php esc_html_e('Download', 'publishpress-cart'); ?></a>
                                                    <a class="button button-secondary ppcart-settings__debug-clear" href="<?php echo esc_url($debug_log['clear_url']); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-debug-log-clear')); ?>"><?php esc_html_e('Clear log', 'publishpress-cart'); ?></a>
                                                </div>
                                            </div>
                                            <pre class="ppcart-settings__debug-log-output"><?php
                                                                            if ('' !== trim($debug_log['preview'])) {
                                                                                if ($debug_log['truncated']) {
                                                                                    echo esc_html__('Showing the latest log entries.', 'publishpress-cart') . "\n\n";
                                                                                }

                                                                                echo esc_html($debug_log['preview']);
                                                                            } else {
                                                                                esc_html_e('No debug log entries yet.', 'publishpress-cart');
                                                                            }
                ?></pre>
                                        </div>
                                        <div class="ppcart-settings__card ppcart-settings__card--plain ppcart-settings__debug-log-card" <?php echo $stripe_webhook_enabled ? '' : ' hidden'; ?> data-pp-stripe-webhook-log-card>
                                            <div class="ppcart-settings__debug-log-header">
                                                <h2><?php esc_html_e('Stripe webhook log', 'publishpress-cart'); ?></h2>
                                                <div class="ppcart-settings__debug-actions" <?php echo $stripe_webhook_enabled ? '' : ' hidden'; ?> data-pp-stripe-webhook-log-actions>
                                                    <a class="button button-secondary" href="<?php echo esc_url($stripe_webhook_log['view_url']); ?>" target="_blank" rel="noopener noreferrer" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-stripe-webhook-log-view')); ?>"><?php esc_html_e('View log', 'publishpress-cart'); ?></a>
                                                    <a class="button button-secondary" href="<?php echo esc_url($stripe_webhook_log['download_url']); ?>" download="<?php echo esc_attr($stripe_webhook_log['file_name']); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-stripe-webhook-log-download')); ?>"><?php esc_html_e('Download', 'publishpress-cart'); ?></a>
                                                    <a class="button button-secondary ppcart-settings__debug-clear" href="<?php echo esc_url($stripe_webhook_log['clear_url']); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-stripe-webhook-log-clear')); ?>"><?php esc_html_e('Clear log', 'publishpress-cart'); ?></a>
                                                </div>
                                            </div>
                                            <pre class="ppcart-settings__debug-log-output"><?php
                if ('' !== trim($stripe_webhook_log['preview'])) {
                    echo esc_html($stripe_webhook_log['preview']);
                } else {
                    esc_html_e('No Stripe webhook log entries yet.', 'publishpress-cart');
                }
                ?></pre>
                                        </div>
                                    <?php
                                        continue;
            }

            $integration_key       = '';
            $integration_info      = [];
            $card_classes         = 'integrations' === $tab_slug ? [] : ['ppcart-settings__card'];
            $section_fields       = isset($wp_settings_fields[$page_slug][$section['id']]) ? (array) $wp_settings_fields[$page_slug][$section['id']] : [];
            $card_toggle_field    = null;
            $card_toggle_field_ids = [];
            $is_plain_general_card = in_array($section['id'], $general_plain_section_ids, true) && in_array($tab_slug, ['general', 'pages', 'branding', 'downloads', 'debug', 'advanced'], true);
            $is_plain_tax_card    = 'tax' === $tab_slug && $plugin_name . '-tax-setting' === $section['id'];
            $is_plain_invoice_card = 'invoice' === $tab_slug && $plugin_name . '-invoice-setting' === $section['id'];
            $is_plain_email_card  = in_array($tab_slug, ['emails', 'reports'], true)
                && in_array($section['id'], [$plugin_name . '-email_settings', $plugin_name . '-email_reports'], true);
            $is_plain_custom_card = isset($custom_tab_page_map[$tab_slug])
                && $plugin_name . '-' . $tab_slug . '-setting' === $section['id'];
            $is_headingless_card  = $is_plain_general_card
                || $is_plain_tax_card
                || $is_plain_invoice_card
                || $is_plain_email_card
                || $is_plain_custom_card;

            if ($is_headingless_card) {
                $card_classes[] = 'ppcart-settings__card--plain';
            }

            if ('integrations' === $tab_slug) {
                $integration_key  = str_replace($plugin_name . '-', '', $section['id']);
                $rendered_integration_keys[] = $integration_key;
                $integration_lookup_key = strtolower($integration_key);
                $integration_info = $integration_meta[$integration_key] ?? [];

                if (empty($integration_info) && isset($integration_meta[$integration_lookup_key])) {
                    $integration_info = $integration_meta[$integration_lookup_key];
                }

                $card_classes[]  = 'ppcart-settings__integration-card';
                $card_classes[]  = 'ppcart-settings__payment-method';
                $card_classes[]  = 'ppcart-settings__integration-card--' . sanitize_html_class($integration_key);
                $integration_enable_options = isset($integration_info['enable_options']) && is_array($integration_info['enable_options'])
                    ? array_map('strval', $integration_info['enable_options'])
                    : [];

                foreach ($section_fields as $field_id => $field) {
                    $field_callback = $field['callback'] ?? null;
                    $field_args     = isset($field['args']) && is_array($field['args']) ? $field['args'] : [];
                    $option_id      = isset($field_args['id']) ? (string) $field_args['id'] : '';
                    $field_title    = isset($field['title']) ? wp_strip_all_tags((string) $field['title']) : '';
                    $is_checkbox    = is_array($field_callback) && isset($field_callback[1]) && 'field_checkbox' === $field_callback[1];
                    $is_enable      = ! empty($integration_enable_options)
                        ? in_array($option_id, $integration_enable_options, true)
                        : (false !== stripos($field_title, 'enable') || false !== strpos($option_id, '_enable'));

                    if ($is_checkbox && $is_enable) {
                        if (null === $card_toggle_field) {
                            $card_toggle_field = $field;
                        }

                        $card_toggle_field_ids[] = (string) $field_id;
                    }
                }
            }

            if ('payment_methods' === $tab_slug) {
                require PPCART_BASE_DIR . 'admin/partials/settings-page/sections/payment-method.php';

                continue;
            }

            if ('integrations' === $tab_slug) {
                $integration_title = $integration_info['title'] ?? $section['title'];
                $logo_label        = $integration_info['logo_label'] ?? mb_substr(wp_strip_all_tags($integration_title), 0, 2);
                $logo_url          = ! empty($integration_info['logo_file']) ? $get_admin_asset_url($integration_info['logo_file']) : '';
                $description       = $integration_info['description'] ?? '';
                $category          = $integration_info['category'] ?? 'automation';
                $panel_id          = 'ppcart-integration-panel-' . sanitize_html_class($integration_key);
                $toggle_args       = isset($card_toggle_field['args']) && is_array($card_toggle_field['args']) ? $card_toggle_field['args'] : [];
                $toggle_option_id  = isset($toggle_args['id']) ? (string) $toggle_args['id'] : '';
                $enable_options    = isset($integration_info['enable_options']) && is_array($integration_info['enable_options']) ? $integration_info['enable_options'] : [];

                if (empty($enable_options) && $toggle_option_id) {
                    $enable_options = [$toggle_option_id];
                }

                $configured_options = isset($integration_info['configured_options']) && is_array($integration_info['configured_options']) ? $integration_info['configured_options'] : [];
                $is_configured      = false;
                $is_enabled_status  = false;

                if (! empty($enable_options)) {
                    foreach ($enable_options as $option_name) {
                        if ($has_meaningful_option_value($option_name)) {
                            $is_enabled_status = true;
                            break;
                        }
                    }
                } elseif (! empty($configured_options)) {
                    $is_configured = true;

                    foreach ($configured_options as $option_name) {
                        if (! $has_meaningful_option_value($option_name)) {
                            $is_configured = false;
                            break;
                        }
                    }
                }

                // Card state classes only — integration status badges are intentionally not rendered.
                if (! empty($enable_options)) {
                    $card_classes[] = $is_enabled_status ? 'is-enabled' : 'is-disabled';
                } else {
                    $card_classes[] = $is_configured ? 'is-configured' : 'is-not-configured';
                }
                ?>
                                        <div class="<?php echo esc_attr(implode(' ', $card_classes)); ?>"
                                            data-section-id="<?php echo esc_attr($section['id']); ?>"
                                            data-pp-integration-card="<?php echo esc_attr($integration_key); ?>"
                                            data-pp-integration-category="<?php echo esc_attr($category); ?>"
                                            data-pp-integration-panel="<?php echo esc_attr($panel_id); ?>"
                                            role="listitem"
                                            aria-controls="<?php echo esc_attr($panel_id); ?>">
                                            <span class="ppcart-settings__integration-logo ppcart-settings__payment-logo" aria-hidden="true">
                                                <?php if ($logo_url) : ?>
                                                    <img src="<?php echo esc_url($logo_url); ?>" alt="" loading="lazy" />
                                                <?php else : ?>
                                                    <?php echo esc_html($logo_label); ?>
                                                <?php endif; ?>
                                            </span>
                                            <span class="ppcart-settings__integration-summary ppcart-settings__payment-summary">
                                                <span class="ppcart-settings__integration-title ppcart-settings__payment-title"><?php echo esc_html($integration_title); ?></span>
                                                <?php if ($description) : ?>
                                                    <span class="ppcart-settings__integration-desc ppcart-settings__payment-desc"><?php echo esc_html($description); ?></span>
                                                <?php endif; ?>
                                            </span>
                                            <span class="ppcart-settings__integration-action ppcart-settings__payment-action">
                                                <button type="button"
                                                    class="button ppcart-settings__integration-manage ppcart-settings__payment-manage"
                                                    data-pp-integration-manage="<?php echo esc_attr($integration_key); ?>"
                                                    data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-integration-' . $integration_key . '-manage')); ?>"
                                                    aria-controls="<?php echo esc_attr($panel_id); ?>"
                                                    aria-expanded="false">
                                                    <?php esc_html_e('Configure', 'publishpress-cart'); ?>
                                                </button>
                                            </span>
                                        </div>
                                        <?php
                    ob_start();
                ?>
                                        <aside class="ppcart-settings__integration-panel"
                                            id="<?php echo esc_attr($panel_id); ?>"
                                            data-pp-integration-detail="<?php echo esc_attr($integration_key); ?>"
                                            role="dialog"
                                            aria-modal="true"
                                            aria-labelledby="<?php echo esc_attr($panel_id . '-title'); ?>"
                                            hidden>
                                            <div class="ppcart-settings__integration-panel-scroll">
                                                <div class="ppcart-settings__integration-panel-header">
                                                    <button type="button" class="ppcart-settings__integration-panel-close" data-pp-integration-close data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-integration-' . $integration_key . '-close')); ?>" aria-label="<?php esc_attr_e('Close integration settings', 'publishpress-cart'); ?>">&times;</button>
                                                    <div class="ppcart-settings__integration-panel-title-row">
                                                        <span class="ppcart-settings__integration-logo ppcart-settings__payment-logo" aria-hidden="true">
                                                            <?php if ($logo_url) : ?>
                                                                <img src="<?php echo esc_url($logo_url); ?>" alt="" loading="lazy" />
                                                            <?php else : ?>
                                                                <?php echo esc_html($logo_label); ?>
                                                            <?php endif; ?>
                                                        </span>
                                                        <span>
                                                            <span class="ppcart-settings__integration-panel-title" id="<?php echo esc_attr($panel_id . '-title'); ?>"><?php echo esc_html($integration_title); ?></span>
                                                        </span>
                                                    </div>
                                                    <?php if ($description) : ?>
                                                        <p><?php echo esc_html($description); ?></p>
                                                    <?php endif; ?>
                                                    <?php if (! empty($integration_info['learn_more'])) : ?>
                                                        <a href="<?php echo esc_url($integration_info['learn_more']); ?>" target="_blank" rel="noreferrer noopener" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-integration-' . $integration_key . '-learn-more')); ?>">
                                                            <?php
                                    printf(
                                        /* translators: %s is the integration name. */
                                        esc_html__('Learn more about %s', 'publishpress-cart'),
                                        esc_html($integration_title)
                                    );
                                                        ?>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="ppcart-settings__integration-panel-body">
                                                    <h3><?php esc_html_e('Configuration', 'publishpress-cart'); ?></h3>
                                        <?php
            } else {
                echo '<div class="' . esc_attr(implode(' ', $card_classes)) . '" data-section-id="' . esc_attr($section['id']) . '">';
            }

            if (! empty($section['title'])) {
                // Use <h2> as a DIRECT child of the card so the legacy
                // email-accordion JS (`.email_title_trigger.next('table')`)
                // continues to find the form-table as its next sibling.
                if ('integrations' !== $tab_slug && ! $is_headingless_card) {
                    echo '<h2 class="ppcart-settings__card-title">' . esc_html($section['title']) . '</h2>';
                }
            }

            if (! empty($section['callback']) && is_callable($section['callback'])) {
                call_user_func($section['callback'], $section);
            }

            echo '<table class="form-table" role="presentation">';
            if ('integrations' === $tab_slug) {
                foreach ($section_fields as $field_id => $field) {
                    if (in_array((string) $field_id, $card_toggle_field_ids, true)) {
                        $field_args = isset($field['args']) && is_array($field['args']) ? $field['args'] : [];
                        $field_args['data'] = isset($field_args['data']) && is_array($field_args['data']) ? $field_args['data'] : [];
                        $field_args['data']['pp-integration-toggle'] = $integration_key;
                        $field['args'] = $field_args;
                    }

                    $render_setting_field_row($field);
                }
            } else {
                do_settings_fields($page_slug, $section['id']);

                // Append locked Pro field previews to their target free card.
                if (function_exists('ppcart_pro_locked_settings_field_sections') && function_exists('ppcart_pro_locked_field_rows_html')) {
                    $locked_field_sections = ppcart_pro_locked_settings_field_sections();
                    if (isset($locked_field_sections[$tab_slug]) && $plugin_name . '-' . $locked_field_sections[$tab_slug] === $section['id']) {
                        echo wp_kses(ppcart_pro_locked_field_rows_html($tab_slug), ppcart_admin_allowed_html());
                    }
                }
            }
            echo '</table>';

            if ('integrations' === $tab_slug) {
                echo '</div>';
                echo '</div>';
                echo '<div class="ppcart-settings__integration-panel-footer">';
                echo '<button type="button" class="button button-secondary" data-pp-integration-close data-testid="' . esc_attr(ppcart_testid('ppcart-admin-integration-' . $integration_key . '-cancel')) . '">' . esc_html__('Cancel', 'publishpress-cart') . '</button>';
                echo '<button type="button" class="button button-primary ppcart-settings__save-button" data-pp-save data-testid="' . esc_attr(ppcart_testid('ppcart-admin-integration-' . $integration_key . '-save')) . '"><span class="ppcart-settings__save-button-label">' . esc_html__('Save changes', 'publishpress-cart') . '</span><span class="ppcart-settings__save-button-spinner" aria-hidden="true"></span></button>';
                echo '</div>';
                echo '</aside>';
                $integration_panel_html = ob_get_clean();
                $integration_panels_html .= $integration_panel_html;
            } else {
                echo '</div>';
            }
        }

        if ('payment_methods' === $tab_slug) {
            // Locked previews of premium gateways, inside the same list.
            $locked_payment_methods = function_exists('ppcart_pro_locked_payment_methods') ? ppcart_pro_locked_payment_methods() : [];
            foreach ($locked_payment_methods as $locked_key => $locked_method) {
                if (in_array($locked_key, $rendered_payment_keys, true)) {
                    continue;
                }

                $locked_logo_url = ! empty($locked_method['logo_file']) ? $get_admin_asset_url($locked_method['logo_file']) : '';

                $render_locked_pro_card([
                    'type'        => 'payment',
                    'key'         => $locked_key,
                    'title'       => $locked_method['title'] ?? $locked_key,
                    'description' => $locked_method['description'] ?? '',
                    'logo_url'    => $locked_logo_url,
                    'logo_label'  => $locked_method['logo_label'] ?? '',
                    'context'     => 'payment-' . $locked_key,
                ]);
            }

            echo '</div>';
            echo '<div class="ppcart-settings__payment-detail">';
            echo wp_kses($payment_panels_html, ppcart_admin_allowed_html());
            echo '</div>';
            echo '</div>';
        }

        require __DIR__ . '/integration-footer.php';

        if ('emails' === $tab_slug && ! empty($email_template_sections)) {
            require PPCART_BASE_DIR . 'admin/partials/settings-page/sections/email-notifications.php';
        }
    }

    require __DIR__ . '/extra-cards.php';

    if (! empty($is_blurred_tab)) {
        echo '</div>'; // .ppcart-settings__pro-tab-lock-content
        echo '</div>'; // .ppcart-settings__pro-tab-lock
    }
    ?>

    </section>
<?php endforeach; ?>
