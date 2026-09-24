<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Admin_Reports_Date_Trait
{
    /**
     * Parse the date filter used by the reports page.
     *
     * @param string $date Date filter value from the request.
     * @return array
     */
    private function get_report_period($date)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/reports-date-get-report-period.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Return the site timezone for report date calculations.
     *
     * @return DateTimeZone
     */
    private function get_report_timezone()
    {
        return function_exists('wp_timezone') ? wp_timezone() : new DateTimeZone('UTC');
    }

    /**
     * Format a report timestamp in the site timezone.
     *
     * @param string $format Date format.
     * @param int    $timestamp Unix timestamp.
     * @return string
     */
    private function format_report_date($format, $timestamp)
    {
        if (function_exists('wp_date')) {
            return wp_date($format, $timestamp, $this->get_report_timezone());
        }

        return date_i18n($format, $timestamp);
    }

    /**
     * Parse a report date or timestamp in the site timezone.
     *
     * @param mixed $date Date, DateTime, or timestamp.
     * @return int|false
     */
    private function get_report_timestamp($date)
    {
        if ($date instanceof DateTimeInterface) {
            return $date->getTimestamp();
        }

        if (is_numeric($date)) {
            return (int) $date;
        }

        $date = trim((string) $date);

        if ('' === $date) {
            return false;
        }

        try {
            $datetime = new DateTime($date, $this->get_report_timezone());
        } catch (Exception $exception) {
            return false;
        }

        return $datetime->getTimestamp();
    }

    /**
     * Build a WP_Query date query from the parsed report period.
     *
     * @param array $period Parsed report period.
     * @return array
     */
    private function get_report_date_query($period)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/reports-date-get-report-date-query.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Check whether a date string sits inside the active report period.
     *
     * @param string $date Date string.
     * @param array  $period Parsed report period.
     * @return bool
     */
    private function is_date_in_period($date, $period)
    {
        if (! empty($period['is_all_time'])) {
            return true;
        }

        if (empty($date)) {
            return false;
        }

        $timestamp = $this->get_report_timestamp($date);

        if (false === $timestamp) {
            return false;
        }

        return $timestamp >= $period['from']->getTimestamp() && $timestamp <= $period['to']->getTimestamp();
    }
}
