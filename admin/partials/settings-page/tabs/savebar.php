<?php
if (! defined('ABSPATH')) {
    exit;
}
?>
    <div class="ppcart-settings__savebar" role="status" aria-live="polite">
        <span class="ppcart-settings__savebar-message"><?php esc_html_e('You have unsaved changes', 'publishpress-cart'); ?></span>
        <span class="ppcart-settings__savebar-spacer"></span>
        <button type="button" class="button button-secondary" data-pp-discard data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-settings-discard')); ?>"><?php esc_html_e('Discard', 'publishpress-cart'); ?></button>
        <button type="button" class="button button-primary ppcart-settings__save-button" data-pp-save data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-settings-save-sticky')); ?>">
            <span class="ppcart-settings__save-button-label"><?php esc_html_e('Save changes', 'publishpress-cart'); ?></span>
            <span class="ppcart-settings__save-button-spinner" aria-hidden="true"></span>
        </button>
    </div>
