<?php

if (! defined('ABSPATH')) {
    exit;
}


$migration_status       = self::get_migration_status();
$nonce        = wp_create_nonce('ppcart_migrate_secrets');
$toggle_nonce = wp_create_nonce('ppcart_set_encrypt_secrets');
$migration_notice = self::get_plaintext_migration_notice($migration_status);

ob_start();
?>
<div class="ppcart-secrets-maintenance" data-ppcart-secrets-maintenance>
    <table class="form-table" role="presentation">
        <tr>
            <th scope="row"><?php esc_html_e('Encryption', 'publishpress-cart'); ?></th>
            <td>
                <?php if ($migration_status['constant_defined']) : ?>
                    <p class="description">
                        <?php esc_html_e('Controlled by PPCART_ENCRYPT_SECRETS in wp-config.php. The database setting is ignored.', 'publishpress-cart'); ?>
                    </p>
                    <p class="description"><?php echo wp_kses_post(self::format_encrypt_secrets_constant_html($migration_status['encryption_enabled'])); ?></p>
                <?php else : ?>
                    <p class="description">
                        <label>
                            <input
                                type="checkbox"
                                data-ppcart-toggle-encrypt-secrets
                                data-nonce="<?php echo esc_attr($toggle_nonce); ?>"
                                data-plaintext-count="<?php echo esc_attr((string) (int) $migration_status['plaintext_count']); ?>"
                                <?php checked($migration_status['db_encryption_enabled']); ?>
                            />
                            <?php esc_html_e('Encrypt payment and integration credentials in the database', 'publishpress-cart'); ?>
                        </label>
                    </p>
                    <p class="description">
                        <?php esc_html_e('For production sites, you can lock this in wp-config.php instead:', 'publishpress-cart'); ?>
                    </p>
                    <p class="description"><?php echo wp_kses_post(self::format_encrypt_secrets_constant_html(true)); ?></p>
                    <p class="description">
                        <?php esc_html_e('When that constant is defined, it takes precedence and this checkbox is ignored.', 'publishpress-cart'); ?>
                    </p>
                <?php endif; ?>

                <?php if ('' !== $migration_notice && ! $migration_status['encryption_enabled']) : ?>
                    <p class="description ppcart-secrets-maintenance__migration-notice">
                        <?php echo esc_html($migration_notice); ?>
                    </p>
                <?php endif; ?>

                <?php if ($migration_status['encryption_enabled']) : ?>
                    <p class="description">
                        <?php
                        echo esc_html(
                            sprintf(
                                /* translators: %s: encryption key source label. */
                                __('Key source: %s', 'publishpress-cart'),
                                $migration_status['encryption_key_source']
                            )
                        );
                    ?>
                    </p>
                    <?php if (! $migration_status['encryption_available']) : ?>
                        <p class="description" style="color:#b32d2e;">
                            <?php esc_html_e('Encryption cannot run until AUTH_KEY or PPCART_SECRETS_KEY is configured in wp-config.php.', 'publishpress-cart'); ?>
                        </p>
                    <?php endif; ?>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('Stored credentials', 'publishpress-cart'); ?></th>
            <td>
                <ul>
                    <li><?php echo esc_html(sprintf(/* translators: %d: count */ __('Encrypted: %d', 'publishpress-cart'), (int) $migration_status['encrypted_count'])); ?></li>
                    <li><?php echo esc_html(sprintf(/* translators: %d: count */ __('Plaintext: %d', 'publishpress-cart'), (int) $migration_status['plaintext_count'])); ?></li>
                </ul>
                <?php if (! $migration_status['encryption_enabled']) : ?>
                    <p class="description">
                        <?php esc_html_e('While encryption is disabled, new and updated credentials are stored in plaintext. Existing encrypted values are not rewritten automatically; Cart continues to read them normally.', 'publishpress-cart'); ?>
                    </p>
                    <?php if ((int) $migration_status['encrypted_count'] > 0) : ?>
                        <p class="description">
                            <?php
                        echo esc_html(
                            sprintf(
                                /* translators: %d: number of encrypted credentials. */
                                _n(
                                    '%d credential remains encrypted in the database until you re-save it or enable encryption again.',
                                    '%d credentials remain encrypted in the database until you re-save them or enable encryption again.',
                                    (int) $migration_status['encrypted_count'],
                                    'publishpress-cart'
                                ),
                                (int) $migration_status['encrypted_count']
                            )
                        );
                        ?>
                        </p>
                    <?php endif; ?>
                    <p class="description">
                        <?php esc_html_e('When you enable encryption, any plaintext credentials are encrypted automatically.', 'publishpress-cart'); ?>
                    </p>
                <?php endif; ?>
                <?php if ($migration_status['last_migration'] > 0) : ?>
                    <p class="description">
                        <?php
                        echo esc_html(
                            sprintf(
                                /* translators: %s: formatted datetime. */
                                __('Last migration: %s', 'publishpress-cart'),
                                wp_date(get_option('date_format') . ' ' . get_option('time_format'), (int) $migration_status['last_migration'])
                            )
                        );
                    ?>
                    </p>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('Encrypt stored credentials', 'publishpress-cart'); ?></th>
            <td>
                <?php if (! $migration_status['encryption_enabled']) : ?>
                    <button type="button" class="button button-secondary" disabled>
                        <?php esc_html_e('Encrypt stored credentials', 'publishpress-cart'); ?>
                    </button>
                    <p class="description">
                        <?php esc_html_e('Enable encryption above or in wp-config.php before running migration.', 'publishpress-cart'); ?>
                    </p>
                <?php elseif (! $migration_status['encryption_available']) : ?>
                    <button type="button" class="button button-secondary" disabled>
                        <?php esc_html_e('Encrypt stored credentials', 'publishpress-cart'); ?>
                    </button>
                <?php else : ?>
                    <button
                        type="button"
                        class="button button-secondary"
                        data-ppcart-migrate-secrets
                        data-nonce="<?php echo esc_attr($nonce); ?>"
                        data-plaintext-count="<?php echo esc_attr((string) (int) $migration_status['plaintext_count']); ?>"
                        <?php disabled(0 === (int) $migration_status['plaintext_count']); ?>
                    >
                        <?php esc_html_e('Encrypt stored credentials', 'publishpress-cart'); ?>
                    </button>
                    <p class="description">
                        <?php esc_html_e('Rewrites existing plaintext payment and integration credentials in the database. Run during a maintenance window after taking a backup.', 'publishpress-cart'); ?>
                    </p>
                    <?php if ($migration_status['needs_migration']) : ?>
                        <p class="description ppcart-secrets-maintenance__migration-notice">
                            <?php echo esc_html($migration_notice); ?>
                        </p>
                    <?php endif; ?>
                    <div class="ppcart-secrets-maintenance__result" data-ppcart-migrate-secrets-result hidden></div>
                <?php endif; ?>
            </td>
        </tr>
    </table>
</div>
<?php

return (string) ob_get_clean();
