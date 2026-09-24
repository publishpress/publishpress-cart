<?php

if (! defined('ABSPATH')) {
    exit;
}


if (! current_user_can('manage_options')) {
    return '';
}

$migration_status       = self::get_migration_status();
$maintenance  = admin_url('admin.php?page=ppcart-settings#maintenance');
$toggle_nonce = wp_create_nonce('ppcart_set_encrypt_secrets');
$migration_notice = self::get_plaintext_migration_notice($migration_status);

ob_start();
?>
<div class="ppcart-getting-started__encryption postbox" data-ppcart-secrets-getting-started>
    <div class="ppcart-getting-started__content ppcart-getting-started__content--narrow">
        <h3><?php esc_html_e('Security', 'publishpress-cart'); ?></h3>
        <?php if ($migration_status['encryption_enabled']) : ?>
            <p>
                <span class="ppcart-getting-started__encryption-badge is-enabled"><?php esc_html_e('Encryption enabled', 'publishpress-cart'); ?></span>
            </p>
            <?php if ($migration_status['needs_migration']) : ?>
                <p><?php echo esc_html($migration_notice); ?></p>
            <?php elseif ((int) $migration_status['plaintext_count'] === 0 && (int) $migration_status['encrypted_count'] > 0) : ?>
                <p><?php esc_html_e('Payment and integration credentials are encrypted at rest.', 'publishpress-cart'); ?></p>
            <?php else : ?>
                <p><?php esc_html_e('New credentials will be encrypted when saved.', 'publishpress-cart'); ?></p>
            <?php endif; ?>
        <?php else : ?>
            <p>
                <span class="ppcart-getting-started__encryption-badge is-disabled"><?php esc_html_e('Encryption disabled', 'publishpress-cart'); ?></span>
            </p>
            <p><?php esc_html_e('Payment and integration keys are stored in plaintext by default. Enable encryption to reduce risk if your database is exposed.', 'publishpress-cart'); ?></p>
            <?php if ('' !== $migration_notice) : ?>
                <p><?php echo esc_html($migration_notice); ?></p>
            <?php endif; ?>
            <?php if (! $migration_status['constant_defined']) : ?>
                <p>
                    <label>
                        <input
                            type="checkbox"
                            data-ppcart-toggle-encrypt-secrets
                            data-nonce="<?php echo esc_attr($toggle_nonce); ?>"
                            data-plaintext-count="<?php echo esc_attr((string) (int) $migration_status['plaintext_count']); ?>" />
                        <?php esc_html_e('Enable encryption', 'publishpress-cart'); ?>
                    </label>
                </p>
            <?php else : ?>
                <p class="description">
                    <?php
                    echo wp_kses(
                        sprintf(
                            /* translators: %s: wp-config.php constant snippet. */
                            __('Add %s to wp-config.php to enable encryption.', 'publishpress-cart'),
                            self::format_encrypt_secrets_constant_html(true)
                        ),
                        ['code' => []]
                    );
                ?>
                </p>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($migration_status['constant_defined']) : ?>
            <p class="description">
                <?php esc_html_e('Encryption is controlled by PPCART_ENCRYPT_SECRETS in wp-config.php.', 'publishpress-cart'); ?>
            </p>
        <?php else : ?>
            <p class="description">
                <?php
                echo wp_kses(
                    sprintf(
                        /* translators: %s: wp-config.php constant snippet. */
                        __('Optional: lock encryption on in wp-config.php with %s', 'publishpress-cart'),
                        self::format_encrypt_secrets_constant_html(true)
                    ),
                    ['code' => []]
                );
            ?>
            </p>
        <?php endif; ?>

        <p>
            <a class="button button-secondary" href="<?php echo esc_url($maintenance); ?>">
                <?php esc_html_e('Open Security settings', 'publishpress-cart'); ?>
            </a>
        </p>
    </div>
</div>
<?php

return (string) ob_get_clean();
