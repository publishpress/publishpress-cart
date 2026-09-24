<?php

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="ppcart-settings__card ppcart-settings__card--plain ppcart-settings__email-notifications" data-section-id="<?php echo esc_attr($plugin_name . '-email_notifications'); ?>">
    <div class="ppcart-settings__email-notifications-list" role="table" aria-label="<?php esc_attr_e('Email notifications', 'publishpress-cart'); ?>">
        <div class="ppcart-settings__email-notifications-head" role="row">
            <div role="columnheader"><?php esc_html_e('Email', 'publishpress-cart'); ?></div>
            <div role="columnheader"><?php esc_html_e('Recipient', 'publishpress-cart'); ?></div>
            <div role="columnheader" class="screen-reader-text"><?php esc_html_e('Action', 'publishpress-cart'); ?></div>
        </div>
        <?php
        $rendered_email_titles = [];
foreach ($email_template_sections as $email_section) {
    $rendered_email_titles[] = $email_section['title'];
    $template_key = str_replace($plugin_name . '-', '', $email_section['id']);
    $template_id  = sanitize_html_class($template_key);
    $modal_id     = 'ppcart-settings-email-modal-' . $template_id;
    $fields       = isset($wp_settings_fields[$page_slug][$email_section['id']]) ? (array) $wp_settings_fields[$page_slug][$email_section['id']] : [];
    $enable_id    = '';
    $admin_id     = '';

    foreach ($fields as $field) {
        $field_id = $field['args']['id'] ?? '';
        if (! $enable_id && false !== strpos($field_id, '_enable')) {
            $enable_id = $field_id;
        }
        if (! $admin_id && false !== strpos($field_id, '_admin')) {
            $admin_id = $field_id;
        }
    }

    $is_enabled = $enable_id ? (bool) get_option($enable_id) : false;
    $recipient  = $email_template_meta[$template_key]['recipient'] ?? __('Customer', 'publishpress-cart');
    if ($admin_id && get_option($admin_id)) {
        $recipient = __('Customer + Admin', 'publishpress-cart');
    }
    $description = $email_template_meta[$template_key]['description'] ?? __('Configure this automated email notification.', 'publishpress-cart');
    ?>
            <div class="ppcart-settings__email-notifications-row" role="row">
                <div class="ppcart-settings__email-notifications-email" role="cell">
                    <span class="ppcart-settings__email-status <?php echo $is_enabled ? 'is-enabled' : 'is-disabled'; ?>" aria-hidden="true"></span>
                    <span class="ppcart-settings__email-summary">
                        <span class="ppcart-settings__email-title"><?php echo esc_html($email_section['title']); ?></span>
                        <span class="ppcart-settings__email-desc"><?php echo esc_html($description); ?></span>
                    </span>
                </div>
                <div class="ppcart-settings__email-recipient" role="cell"><?php echo esc_html($recipient); ?></div>
                <div class="ppcart-settings__email-action" role="cell">
                    <button type="button" class="button ppcart-settings__email-manage" data-pp-email-modal-open="<?php echo esc_attr($modal_id); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-email-' . $template_key . '-manage')); ?>">
                        <?php esc_html_e('Manage', 'publishpress-cart'); ?>
                    </button>
                </div>
            </div>
        <?php
}

// Locked Pro email notifications, deduped against the rendered ones.
if (function_exists('ppcart_pro_locked_email_rows_html')) {
    echo ppcart_pro_locked_email_rows_html($rendered_email_titles); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in helper.
}
?>
    </div>
</div>

<?php foreach ($email_template_sections as $email_section) :
    $template_key = str_replace($plugin_name . '-', '', $email_section['id']);
    $template_id  = sanitize_html_class($template_key);
    $modal_id     = 'ppcart-settings-email-modal-' . $template_id;
    $fields       = isset($wp_settings_fields[$page_slug][$email_section['id']]) ? (array) $wp_settings_fields[$page_slug][$email_section['id']] : [];
    $template_type = $email_template_meta[$template_key]['template'] ?? '';
    $has_template_fields = false;
    $preview_field_ids = [
        'subject'  => '',
        'headline' => '',
        'body'     => '',
    ];
    foreach ($fields as $field) {
        if (! empty($field['args']['email_template_key'])) {
            $has_template_fields = true;
            break;
        }
    }
    foreach (array_keys($preview_field_ids) as $preview_field_key) {
        if ($template_type && function_exists('ppcart_get_email_template_option_id')) {
            $preview_field_ids[$preview_field_key] = ppcart_get_email_template_option_id($template_type, $preview_field_key);
        }
    }
    ?>
    <div class="ppcart-settings__modal" id="<?php echo esc_attr($modal_id); ?>" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr($modal_id . '-title'); ?>" data-pp-email-template="<?php echo esc_attr($template_type); ?>" data-pp-email-subject-field="<?php echo esc_attr($preview_field_ids['subject']); ?>" data-pp-email-headline-field="<?php echo esc_attr($preview_field_ids['headline']); ?>" data-pp-email-body-field="<?php echo esc_attr($preview_field_ids['body']); ?>" hidden>
        <div class="ppcart-settings__modal-backdrop" data-pp-email-modal-close></div>
        <div class="ppcart-settings__modal-dialog">
            <div class="ppcart-settings__modal-header">
                <div>
                    <div class="ppcart-settings__modal-eyebrow"><?php esc_html_e('Email notification', 'publishpress-cart'); ?></div>
                    <h3 id="<?php echo esc_attr($modal_id . '-title'); ?>"><?php echo esc_html($email_section['title']); ?></h3>
                    <?php if ($template_type && $has_template_fields) : ?>
                        <div class="ppcart-settings__email-template-meta">
                            <button type="button" class="button-link ppcart-settings__email-reset" data-pp-email-reset-template="<?php echo esc_attr($template_type); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-email-' . $template_type . '-reset')); ?>">
                                <?php esc_html_e('Reset to default', 'publishpress-cart'); ?>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
                <button type="button" class="ppcart-settings__modal-close" aria-label="<?php esc_attr_e('Close', 'publishpress-cart'); ?>" data-pp-email-modal-close data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-email-' . $template_id . '-close')); ?>">&times;</button>
            </div>
            <div class="ppcart-settings__modal-body">
                <table class="form-table" role="presentation">
                    <?php do_settings_fields($page_slug, $email_section['id']); ?>
                </table>
            </div>
            <div class="ppcart-settings__modal-footer">
                <?php if ($template_type && $has_template_fields) : ?>
                    <button type="button" class="button button-secondary ppcart-settings__email-preview" data-pp-email-preview data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-email-' . $template_type . '-preview')); ?>">
                        <?php esc_html_e('Preview', 'publishpress-cart'); ?>
                    </button>
                <?php endif; ?>
                <button type="button" class="button button-secondary" data-pp-email-modal-close data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-email-' . $template_id . '-done')); ?>"><?php esc_html_e('Done', 'publishpress-cart'); ?></button>
                <button type="submit" name="submit" class="button button-primary ppcart-settings__save-button" value="<?php esc_attr_e('Save changes', 'publishpress-cart'); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-email-' . $template_id . '-save')); ?>">
                    <span class="ppcart-settings__save-button-label"><?php esc_html_e('Save changes', 'publishpress-cart'); ?></span>
                    <span class="ppcart-settings__save-button-spinner" aria-hidden="true"></span>
                </button>
            </div>
        </div>
    </div>
<?php endforeach; ?>
<?php
