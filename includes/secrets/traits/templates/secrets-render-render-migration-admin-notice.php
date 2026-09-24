<?php

if (! defined('ABSPATH')) {
    exit;
}


if (! is_admin() || ! current_user_can('manage_options')) {
    return;
}

$user_id = get_current_user_id();
if ($user_id && get_user_meta($user_id, self::MIGRATION_DISMISS_META, true)) {
    return;
}

$counts = get_transient(self::MIGRATION_NOTICE_TRANSIENT);
if (! is_array($counts)) {
    return;
}

$dismiss_url = wp_nonce_url(
    add_query_arg('ppcart_dismiss_secrets_notice', '1'),
    'ppcart_dismiss_secrets_notice'
);

$failed = (int) ($counts['failed'] ?? 0);
$class  = $failed > 0 ? 'notice-error' : 'notice-warning';
?>
<div class="notice <?php echo esc_attr($class); ?> is-dismissible">
    <p>
        <strong><?php esc_html_e('PublishPress Cart security', 'publishpress-cart'); ?></strong>
    </p>
    <p>
        <?php
        echo esc_html(
            sprintf(
                /* translators: 1: migrated count, 2: skipped count, 3: failed count. */
                __('Encrypted %1$d stored credentials (%2$d skipped, %3$d failed).', 'publishpress-cart'),
                (int) ($counts['migrated'] ?? 0),
                (int) ($counts['skipped'] ?? 0),
                (int) ($counts['failed'] ?? 0)
            )
        );
?>
    </p>
    <p>
        <?php esc_html_e('Do not change AUTH_KEY or PPCART_SECRETS_KEY without re-entering payment and integration keys in Cart settings.', 'publishpress-cart'); ?>
    </p>
    <?php if ($failed > 0) : ?>
        <p>
            <?php esc_html_e('Some credentials could not be encrypted. Re-run migration from Cart → Settings → Maintenance or re-save keys manually.', 'publishpress-cart'); ?>
        </p>
    <?php endif; ?>
    <p>
        <a href="<?php echo esc_url($dismiss_url); ?>"><?php esc_html_e('Dismiss this notice', 'publishpress-cart'); ?></a>
    </p>
</div>
<?php
