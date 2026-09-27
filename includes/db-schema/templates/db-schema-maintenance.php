<?php

if (! defined('ABSPATH')) {
    exit;
}

$healthy_class = ! empty($data['healthy']) ? 'is-passed' : 'is-failed';
$status_label  = ! empty($data['healthy'])
    ? __('Passed', 'publishpress-cart')
    : __('Failed', 'publishpress-cart');

ob_start();
?>
<div class="ppcart-db-schema-maintenance" data-ppcart-db-schema-maintenance>
    <table class="form-table" role="presentation">
        <tr>
            <th scope="row"><?php esc_html_e('Status', 'publishpress-cart'); ?></th>
            <td>
                <p class="ppcart-settings__db-schema-status <?php echo esc_attr($healthy_class); ?>" data-ppcart-db-schema-status data-testid="<?php echo esc_attr(ppcart_testid('db-schema-status')); ?>">
                    <?php echo esc_html($status_label); ?>
                </p>
                <p class="description">
                    <?php esc_html_e('Checks that Cart database tables, columns, and indexes match what this version expects.', 'publishpress-cart'); ?>
                </p>
                <?php if (! empty($data['tables']) && is_array($data['tables'])) : ?>
                    <ul class="ppcart-settings__db-schema-issues">
                        <?php foreach ($data['tables'] as $table_name => $table_report) : ?>
                            <?php
                            $issues     = isset($table_report['issues']) && is_array($table_report['issues']) ? $table_report['issues'] : [];
                            $fix_errors = isset($table_report['fix_errors']) && is_array($table_report['fix_errors']) ? $table_report['fix_errors'] : [];
                            if (empty($issues) && empty($fix_errors)) {
                                continue;
                            }
                            $label = isset($table_report['label']) ? (string) $table_report['label'] : (string) $table_name;
                            ?>
                            <li>
                                <strong><?php echo esc_html($label); ?></strong>
                                <?php if (! empty($issues)) : ?>
                                    <ul>
                                        <?php foreach ($issues as $issue) : ?>
                                            <li><?php echo esc_html(isset($issue['message']) ? (string) $issue['message'] : ''); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                                <?php if (! empty($fix_errors)) : ?>
                                    <ul class="ppcart-settings__db-schema-fix-errors">
                                        <?php foreach ($fix_errors as $fix_error) : ?>
                                            <li><?php echo esc_html((string) $fix_error); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </td>
        </tr>
        <?php if (empty($data['healthy'])) : ?>
        <tr>
            <th scope="row"><?php esc_html_e('Repair', 'publishpress-cart'); ?></th>
            <td>
                <p>
                    <button
                        type="button"
                        class="button button-secondary"
                        data-ppcart-fix-db-schema
                        data-nonce="<?php echo esc_attr($nonce); ?>"
                        data-testid="<?php echo esc_attr(ppcart_testid('fix-db-schema')); ?>"
                    >
                        <?php esc_html_e('Fix database schema', 'publishpress-cart'); ?>
                    </button>
                </p>
                <p class="description">
                    <?php esc_html_e('Creates missing tables, columns, and indexes, and rebuilds mismatched indexes. Existing data is never deleted.', 'publishpress-cart'); ?>
                </p>
                <p class="description" data-ppcart-fix-db-schema-result hidden></p>
            </td>
        </tr>
        <?php endif; ?>
    </table>
</div>
<?php
return ob_get_clean();
