<?php

if (! defined('ABSPATH')) {
    exit;
}


$max = 0;

foreach ($rows as $row) {
    $max = max($max, (float) $row['value']);
}

if (empty($rows) || $max <= 0) {
    $this->render_empty_state(
        __('No data for this period', 'publishpress-cart'),
        $empty_message
    );
    return;
}

echo '<div class="ppcart-report-bars">';

foreach ($rows as $row) {
    $width = max(4, round(((float) $row['value'] / $max) * 100));
    ?>
    <div class="ppcart-report-bars__row">
        <div class="ppcart-report-bars__meta">
            <span><?php echo esc_html($row['label']); ?></span>
            <strong><?php echo wp_kses_post($row['display']); ?></strong>
        </div>
        <div class="ppcart-report-bars__track">
            <span style="--ppcart-report-bar-width: <?php echo esc_attr($width); ?>%;"></span>
        </div>
    </div>
    <?php
}

echo '</div>';
