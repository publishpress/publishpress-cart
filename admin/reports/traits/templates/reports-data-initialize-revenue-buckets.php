<?php

if (! defined('ABSPATH')) {
    exit;
}


$buckets = [];
$cursor  = new DateTime('@' . $start);
$last    = new DateTime('@' . $end);
$timezone = $this->get_report_timezone();

$cursor->setTimezone($timezone);
$last->setTimezone($timezone);

if ('month' === $interval) {
    $cursor->modify('first day of this month midnight');
    $last->modify('first day of this month midnight');
} elseif ('week' === $interval) {
    $cursor->modify('monday this week midnight');
    $last->modify('monday this week midnight');
} else {
    $cursor->setTime(0, 0, 0);
    $last->setTime(0, 0, 0);
}

while ($cursor <= $last) {
    $timestamp = $cursor->getTimestamp();
    $key       = $this->get_bucket_key($timestamp, $interval);

    $buckets[ $key ] = [
        'label' => $this->get_bucket_label($key, $interval),
        'value' => 0,
    ];

    if ('month' === $interval) {
        $cursor->modify('+1 month');
    } elseif ('week' === $interval) {
        $cursor->modify('+1 week');
    } else {
        $cursor->modify('+1 day');
    }
}

return $buckets;
