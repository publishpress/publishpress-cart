<?php
if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="ppcart-settings-page">
    <h1 class="screen-reader-text"><?php echo esc_html($settings_page_title); ?></h1>

    <?php require __DIR__ . '/tabs/topbar.php'; ?>

    <div class="ppcart-settings__body">

        <?php require __DIR__ . '/tabs/sidebar.php'; ?>

        <main class="ppcart-settings__main">

            <?php if ('' !== $settings_notice_output) : ?>
                <div class="ppcart-settings__notice-stack">
                    <?php
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Buffered notices are escaped when built or by WordPress settings_errors().
                    echo wp_kses_post($settings_notice_output);
                    ?>
                </div>
            <?php endif; ?>

            <form method="post" action="options.php" class="settings-options-form" id="ppcart-settings-form">
                <?php settings_fields($plugin_name . '-settings'); ?>

                <?php require __DIR__ . '/tabs/panels.php'; ?>

                <?php require __DIR__ . '/tabs/form-actions.php'; ?>
            </form>

            <?php $this->render_plugin_admin_footer_markup('ppcart-settings__footer'); ?>

        </main>

    </div>

    <?php require __DIR__ . '/tabs/savebar.php'; ?>
</div>
</div>
