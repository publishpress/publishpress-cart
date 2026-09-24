<?php

if (! defined('ABSPATH')) {
    exit;
}


$timezone = $this->get_report_timezone();
$date     = trim((string) $date);

if ('' === $date || 'all time' === strtolower($date)) {
    return [
        'raw'         => '',
        'from'        => null,
        'to'          => null,
        'is_all_time' => true,
        'label'       => __('All time', 'publishpress-cart'),
    ];
}

$normalized = strtolower(preg_replace('/\s+/', ' ', $date));
$from       = false;
$to         = false;

switch ($normalized) {
    case 'today':
        $from = new DateTime('today', $timezone);
        $to   = clone $from;
        $to->setTime(23, 59, 59);
        break;

    case 'yesterday':
        $from = new DateTime('yesterday', $timezone);
        $from->setTime(0, 0, 0);
        $to = clone $from;
        $to->setTime(23, 59, 59);
        break;

    case 'last 7 days':
        $from = new DateTime('-7 days', $timezone);
        $from->setTime(0, 0, 0);
        $to = new DateTime('today', $timezone);
        $to->setTime(23, 59, 59);
        break;

    case 'last 30 days':
        $from = new DateTime('-30 days', $timezone);
        $from->setTime(0, 0, 0);
        $to = new DateTime('today', $timezone);
        $to->setTime(23, 59, 59);
        break;

    case 'this month':
        $from = new DateTime('first day of this month', $timezone);
        $from->setTime(0, 0, 0);
        $to = new DateTime('today', $timezone);
        $to->setTime(23, 59, 59);
        break;

    case 'last month':
        $from = new DateTime('first day of last month', $timezone);
        $from->setTime(0, 0, 0);
        $to = new DateTime('last day of last month', $timezone);
        $to->setTime(23, 59, 59);
        break;

    default:
        $dates = array_map('trim', explode(' to ', $date));
        $from  = DateTime::createFromFormat('Y-m-d H:i:s', $dates[0] . ' 00:00:00', $timezone);
        $to    = isset($dates[1]) ? DateTime::createFromFormat('Y-m-d H:i:s', $dates[1] . ' 23:59:59', $timezone) : false;
        break;
}

if (! $from instanceof DateTime) {
    $from = new DateTime('today', $timezone);
}

if (! $to instanceof DateTime) {
    $to = clone $from;
    $to->setTime(23, 59, 59);
}

if ($from > $to) {
    $swap = $from;
    $from = $to;
    $to   = $swap;
}

$date_format = get_option('date_format');
$label       = $this->format_report_date($date_format, $from->getTimestamp());
$raw         = $from->format('Y-m-d');

if ($from->format('Y-m-d') !== $to->format('Y-m-d')) {
    $label .= ' - ' . $this->format_report_date($date_format, $to->getTimestamp());
    $raw   .= ' to ' . $to->format('Y-m-d');
}

return [
    'raw'         => $raw,
    'from'        => $from,
    'to'          => $to,
    'is_all_time' => false,
    'label'       => $label,
];
