<?php
if (! defined('ABSPATH')) {
    exit;
}
?>
    <header class="ppcart-settings__topbar">
        <div class="ppcart-settings__brand">
            <?php if (apply_filters('ppcart_show_admin_branding', true)) : ?>
                <img class="ppcart-settings__brand-logo" src="<?php echo esc_url($brand_logo_url); ?>" alt="<?php esc_attr_e('PublishPress', 'publishpress-cart'); ?>" />
            <?php endif; ?>
            <span class="ppcart-settings__brand-name"><?php echo esc_html($brand_title); ?></span>
            <span class="ppcart-settings__breadcrumb">/ <strong><?php esc_html_e('General', 'publishpress-cart'); ?></strong></span>
        </div>

        <div class="ppcart-settings__topbar-spacer"></div>

        <label class="ppcart-settings__search" aria-label="<?php esc_attr_e('Search settings', 'publishpress-cart'); ?>">
            <input type="search" placeholder="<?php esc_attr_e('Search settings...', 'publishpress-cart'); ?>" autocomplete="off" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-settings-search')); ?>" />
        </label>

    </header>
