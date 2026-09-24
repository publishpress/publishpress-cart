<?php

if (! defined('ABSPATH')) {
    exit;
}


require_once __DIR__ . '/scheduled-reports/report-data.php';
require_once __DIR__ . '/scheduled-reports/report-email-template.php';

function ppcart_schedule_email_temp()
{
    return ppcart_schedule_report_email_html(ppcart_schedule_report_data());
}

function ppcart_schedule_email_function()
{
    $to_email = function_exists('ppcart_get_admin_notification_recipients') ? ppcart_get_admin_notification_recipients() : get_option('ppcart_admin_email');
    if (empty($to_email)) {
        $to_email = get_option('admin_email');
    }

    $email_list = array_filter(array_map('trim', explode(',', $to_email)), function ($email) {
        return ! empty($email) && is_email($email);
    });

    /* translators: %s: plugin title. */
    $subject = sprintf(esc_html__('Your %s Summary Report', 'publishpress-cart'), apply_filters('ppcart_plugin_title', 'PublishPress Cart'));
    $body    = ppcart_schedule_email_temp();
    $headers = function_exists('ppcart_get_email_headers') ? ppcart_get_email_headers() : [ 'Content-Type: text/html; charset=UTF-8' ];

    foreach ($email_list as $recipient) {
        // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_mail_wp_mail -- Scheduled summary emails are intentional single-recipient transactional notifications.
        wp_mail($recipient, $subject, $body, $headers);
    }
}

add_filter('cron_schedules', 'ppcart_email_schedule_hook');
function ppcart_email_schedule_hook($schedules)
{
    $daily        = 'Daily';
    $weekly       = 'Weekly';
    $semi_monthly = 'Semi Monthly';

    if (did_action('init')) {
        $daily        = __('Daily', 'publishpress-cart');
        $weekly       = __('Weekly', 'publishpress-cart');
        $semi_monthly = __('Semi Monthly', 'publishpress-cart');
    }

    $schedules['ppcart_daily']        = [ 'interval' => 86400,   'display' => $daily ];
    $schedules['ppcart_weekly']       = [ 'interval' => 604800,  'display' => $weekly ];
    $schedules['ppcart_semi_monthly'] = [ 'interval' => 1209600, 'display' => $semi_monthly ];

    return $schedules;
}

/**
 * Rewrite leftover two-letter cron/option values to the canonical prefix.
 *
 * @param mixed $schedule Stored schedule slug.
 * @return mixed
 */
function ppcart_canonical_report_schedule($schedule)
{
    if (! is_string($schedule) || '' === $schedule) {
        return $schedule;
    }

    if (0 === strpos($schedule, 'mt_')) {
        $suffix = substr($schedule, 3);
        if (in_array($suffix, [ 'none', 'daily', 'weekly', 'semi_monthly' ], true)) {
            return 'ppcart_' . $suffix;
        }
    }

    return $schedule;
}

function ppcart_migrate_report_schedule_keys()
{
    $schedule     = get_option('ppcart_report_schedule');
    $current      = get_option('ppcart_current_schedule_val');
    $new_schedule = ppcart_canonical_report_schedule($schedule);
    $new_current  = ppcart_canonical_report_schedule($current);
    $rewrote      = false;

    if (is_string($schedule) && $new_schedule !== $schedule) {
        update_option('ppcart_report_schedule', $new_schedule);
        $rewrote = true;
    }

    if (is_string($current) && '' !== $current && $new_current !== $current) {
        update_option('ppcart_current_schedule_val', $new_current);
        $rewrote = true;
    }

    if ($rewrote) {
        wp_clear_scheduled_hook('ppcart_email_schedule_hook');
    }
}

function ppcart_get_report_schedule()
{
    ppcart_migrate_report_schedule_keys();

    return get_option('ppcart_report_schedule');
}

function ppcart_register_report_schedule_event()
{
    $schedule = ppcart_get_report_schedule();
    if (empty($schedule)) {
        return;
    }

    if ('ppcart_none' === $schedule) {
        wp_clear_scheduled_hook('ppcart_email_schedule_hook');
        update_option('ppcart_current_schedule_val', '');
        return;
    }

    $current_schedule = get_option('ppcart_current_schedule_val');
    if ($current_schedule !== $schedule) {
        wp_clear_scheduled_hook('ppcart_email_schedule_hook');
    }

    if (! wp_next_scheduled('ppcart_email_schedule_hook')) {
        wp_schedule_event(time(), $schedule, 'ppcart_email_schedule_hook');
        update_option('ppcart_current_schedule_val', $schedule);
    }
}

add_action('init', 'ppcart_register_report_schedule_event');

add_action('ppcart_email_schedule_hook', 'ppcart_schedule_function');
function ppcart_schedule_function()
{
    ppcart_schedule_email_function();
}
