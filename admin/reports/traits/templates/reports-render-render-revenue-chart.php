<?php

if (! defined('ABSPATH')) {
    exit;
}


$max = 0;

foreach ($buckets as $bucket) {
    $value = (float) $bucket['value'];
    $max   = max($max, $value);
}

if (empty($buckets) || $max <= 0) {
    $this->render_empty_state(
        __('No revenue in this period', 'publishpress-cart'),
        __('Paid orders will appear in this chart after checkout activity is recorded for the selected date range.', 'publishpress-cart')
    );
    return;
}

$width       = 960;
$height      = 300;
$padding_top = 18;
$padding_right = 18;
$padding_bottom = 42;
$padding_left   = 62;
$plot_width     = $width - $padding_left - $padding_right;
$plot_height    = $height - $padding_top - $padding_bottom;
$count          = count($buckets);
$line_points    = [];
$point_data     = [];
$label_step     = max(1, (int) ceil($count / 8));

$bucket_index = 0;

foreach ($buckets as $bucket) {
    $value = (float) $bucket['value'];
    $x     = $padding_left + (1 === $count ? ($plot_width / 2) : ($plot_width * ($bucket_index / ($count - 1))));
    $y     = $padding_top + $plot_height - (($value / $max) * $plot_height);

    $line_points[] = round($x, 2) . ',' . round($y, 2);
    $point_data[]  = [
        'x'       => round($x, 2),
        'y'       => round($y, 2),
        'label'   => $bucket['label'],
        'display' => wp_strip_all_tags(ppcart_format_price($value)),
        'show'    => 0 === $bucket_index || $bucket_index === ($count - 1) || 0 === $bucket_index % $label_step,
    ];
    $bucket_index++;
}

$area_points = $padding_left . ',' . ($padding_top + $plot_height) . ' ' . implode(' ', $line_points) . ' ' . ($padding_left + $plot_width) . ',' . ($padding_top + $plot_height);

?>
<div class="ppcart-report-chart">
    <svg viewBox="0 0 <?php echo esc_attr($width); ?> <?php echo esc_attr($height); ?>" role="img" aria-label="<?php esc_attr_e('Revenue trend chart', 'publishpress-cart'); ?>">
        <rect class="ppcart-report-chart__background" x="<?php echo esc_attr($padding_left); ?>" y="<?php echo esc_attr($padding_top); ?>" width="<?php echo esc_attr($plot_width); ?>" height="<?php echo esc_attr($plot_height); ?>"></rect>

        <?php
        for ($tick = 0; $tick <= 4; $tick++) :
            $ratio      = $tick / 4;
            $y          = $padding_top + $plot_height - ($plot_height * $ratio);
            $tick_value = $max * $ratio;
            ?>
            <line class="ppcart-report-chart__grid" x1="<?php echo esc_attr($padding_left); ?>" x2="<?php echo esc_attr($padding_left + $plot_width); ?>" y1="<?php echo esc_attr(round($y, 2)); ?>" y2="<?php echo esc_attr(round($y, 2)); ?>"></line>
            <text class="ppcart-report-chart__axis-label" x="<?php echo esc_attr($padding_left - 12); ?>" y="<?php echo esc_attr(round($y + 4, 2)); ?>" text-anchor="end"><?php echo esc_html(wp_strip_all_tags(ppcart_format_price($tick_value))); ?></text>
        <?php endfor; ?>

        <polygon class="ppcart-report-chart__area" points="<?php echo esc_attr($area_points); ?>"></polygon>
        <polyline class="ppcart-report-chart__line" points="<?php echo esc_attr(implode(' ', $line_points)); ?>"></polyline>

        <?php foreach ($point_data as $point) : ?>
            <circle class="ppcart-report-chart__point" cx="<?php echo esc_attr($point['x']); ?>" cy="<?php echo esc_attr($point['y']); ?>" r="3">
                <title><?php echo esc_html($point['label'] . ': ' . $point['display']); ?></title>
            </circle>
            <?php if ($point['show']) : ?>
                <text class="ppcart-report-chart__x-label" x="<?php echo esc_attr($point['x']); ?>" y="<?php echo esc_attr($height - 12); ?>" text-anchor="middle"><?php echo esc_html($point['label']); ?></text>
            <?php endif; ?>
        <?php endforeach; ?>
    </svg>
</div>
<?php
