<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Admin_Reports_Render_Trait
{
    /**
     * Render one metric card.
     *
     * @param string $label Metric label.
     * @param string $value Metric value.
     * @param string $context Metric context.
     * @param string $class Additional class.
     * @return void
     */
    private function render_metric_card($label, $value, $context = '', $class = '')
    {
        ?>
        <article class="ppcart-report-card <?php echo esc_attr($class); ?>">
            <span><?php echo esc_html($label); ?></span>
            <strong><?php echo wp_kses_post($value); ?></strong>
            <?php if ('' !== $context) : ?>
                <small><?php echo esc_html($context); ?></small>
            <?php endif; ?>
        </article>
        <?php
    }

    /**
     * Render an empty state with context.
     *
     * @param string $title Empty state title.
     * @param string $details Empty state details.
     * @return void
     */
    private function render_empty_state($title, $details = '')
    {
        ?>
        <div class="ppcart-report-empty">
            <strong><?php echo esc_html($title); ?></strong>
            <?php if ('' !== $details) : ?>
                <span><?php echo esc_html($details); ?></span>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render a responsive revenue area chart.
     *
     * @param array $buckets Revenue buckets.
     * @return void
     */
    private function render_revenue_chart($buckets)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/reports-render-render-revenue-chart.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Render horizontal metric bars.
     *
     * @param array  $rows Bar rows.
     * @param string $empty_message Empty state message.
     * @return void
     */
    private function render_bar_rows($rows, $empty_message)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/reports-render-render-bar-rows.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
