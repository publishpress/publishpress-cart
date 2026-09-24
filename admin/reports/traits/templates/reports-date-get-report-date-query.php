<?php

if (! defined('ABSPATH')) {
    exit;
}


if (! empty($period['is_all_time']) || ! $period['from'] instanceof DateTime || ! $period['to'] instanceof DateTime) {
    return [];
}

if ($period['from']->format('Y-m-d') === $period['to']->format('Y-m-d')) {
    return [
        [
            'year'  => $period['from']->format('Y'),
            'month' => $period['from']->format('m'),
            'day'   => $period['from']->format('d'),
        ],
    ];
}

return [
    [
        'after'     => $period['from']->format('Y-m-d'),
        'before'    => [
            'year'  => $period['to']->format('Y'),
            'month' => $period['to']->format('m'),
            'day'   => $period['to']->format('d'),
        ],
        'inclusive' => true,
    ],
];
