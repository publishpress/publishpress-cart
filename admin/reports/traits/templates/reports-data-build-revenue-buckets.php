<?php

if (! defined('ABSPATH')) {
    exit;
}


$timestamps = [];

foreach ($events as $event) {
    $timestamp = isset($event['timestamp']) ? $this->get_report_timestamp($event['timestamp']) : $this->get_report_timestamp($event['date']);
    if (false !== $timestamp) {
        $timestamps[] = $timestamp;
    }
}

if (! empty($period['is_all_time'])) {
    $start = ! empty($timestamps) ? min($timestamps) : strtotime('-6 days');
    $end   = ! empty($timestamps) ? max($timestamps) : time();
} else {
    $start = $period['from']->getTimestamp();
    $end   = $period['to']->getTimestamp();
}

if ($start > $end) {
    $end = $start;
}

$days     = max(1, (int) ceil(($end - $start) / DAY_IN_SECONDS) + 1);
$interval = 'day';

if ($days > 120) {
    $interval = 'month';
} elseif ($days > 31) {
    $interval = 'week';
}

$buckets = $this->initialize_revenue_buckets($start, $end, $interval);

foreach ($events as $event) {
    $timestamp = isset($event['timestamp']) ? $this->get_report_timestamp($event['timestamp']) : $this->get_report_timestamp($event['date']);

    if (false === $timestamp) {
        continue;
    }

    $key = $this->get_bucket_key($timestamp, $interval);

    if (! isset($buckets[ $key ])) {
        $buckets[ $key ] = [
            'label' => $this->get_bucket_label($key, $interval),
            'value' => 0,
        ];
    }

    $buckets[ $key ]['value'] += (float) $event['amount'];
}

return $buckets;
