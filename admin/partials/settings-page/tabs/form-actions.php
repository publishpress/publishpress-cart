<?php
if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="ppcart-settings__form-actions">
    <button type="submit" name="submit" id="submit" class="button button-primary ppcart-settings__save-button ppcart-settings__bottom-save" value="<?php esc_attr_e('Save changes', 'publishpress-cart'); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-settings-save-bottom')); ?>">
        <span class="ppcart-settings__save-button-label"><?php esc_html_e('Save changes', 'publishpress-cart'); ?></span>
        <span class="ppcart-settings__save-button-spinner" aria-hidden="true"></span>
    </button>
</div>
