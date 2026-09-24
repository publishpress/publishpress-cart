<?php
if (! defined('ABSPATH')) {
    exit;
}
?>
        <aside class="ppcart-settings__sidebar" role="navigation" aria-label="<?php esc_attr_e('Settings sections', 'publishpress-cart'); ?>">
            <?php
            $tabs_by_group = [];
foreach ($setting_tabs as $tab_slug => $tab_label) {
    $group = $tab_meta[ $tab_slug ]['group'] ?? 'other';
    $tabs_by_group[ $group ][ $tab_slug ] = $tab_label;
}

foreach ($nav_groups as $group_slug => $group_label) {
    if (empty($tabs_by_group[ $group_slug ])) {
        continue;
    }
    ?>
                <div class="ppcart-settings__nav-group">
                    <div class="ppcart-settings__nav-label"><?php echo esc_html($group_label); ?></div>
                    <?php foreach ($tabs_by_group[ $group_slug ] as $tab_slug => $tab_label) :
                        $icon_key = $tab_meta[ $tab_slug ]['icon'] ?? 'default';
                        $icon     = $icon_svgs[ $icon_key ] ?? $icon_svgs['default'];
                        ?>
                        <button type="button" class="ppcart-settings__nav-item"
                                id="settings_tab_<?php echo esc_attr($tab_slug); ?>"
                                data-pp-tab-target="<?php echo esc_attr($tab_slug); ?>"
                                data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-settings-tab-' . $tab_slug)); ?>"
                                aria-current="false">
                            <span class="ppcart-settings__nav-icon" aria-hidden="true"><?php echo wp_kses($icon, ppcart_admin_allowed_html()); ?></span>
                            <span class="ppcart-settings__nav-label-text"><?php echo esc_html($tab_label); ?></span>
                            <?php if (isset($pro_locked_tabs[$tab_slug]) && function_exists('ppcart_pro_nav_badge')) {
                                echo wp_kses_post(ppcart_pro_nav_badge());
                            } ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <?php
}
?>
        </aside>
