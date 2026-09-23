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
    <p class="ppcart-settings__db-schema-status <?php echo esc_attr($healthy_class); ?>" data-ppcart-db-schema-status data-testid="<?php echo esc_attr(ppcart_testid('db-schema-status')); ?>">
        <?php echo esc_html($status_label); ?>
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
                            <?php foreach ($fix_errors as $error) : ?>
                                <li><?php echo esc_html((string) $error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
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
    <p class="description" data-ppcart-fix-db-schema-result hidden></p>
</div>
<?php
return ob_get_clean();
