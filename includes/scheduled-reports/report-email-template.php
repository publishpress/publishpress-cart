<?php

if (! defined('ABSPATH')) {
    exit;
}

function ppcart_schedule_report_email_html($report)
{
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="en">
        <head>
        <meta name="viewport" content="width=device-width">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="x-apple-disable-message-reformatting">
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <style type="text/css" data-premailer="ignore">
            .schedule-main.mail {padding: 15px;box-shadow: rgba(149, 157, 165, 0.2) 0px 8px 24px;width: 100%;max-width: 700px;margin: auto;margin-top: 30px;}
            .summary-text li {margin-bottom: 5px;display: flex;justify-content: space-between;padding: 5px;font-size: 16px;border-bottom: 1px solid #ededed;}
            .summary {display: flex;align-items: center;padding: 20px;background-color: #f5f5f5;font-family: Arial, sans-serif;}
            .schedule-main {text-align: center;}
            .summary-text {width: 100%;}
            .summary-text p {margin-bottom: 10px;}
            .summary-text ul {list-style: none;margin: auto;padding: 0 30px;margin-top: 30px;}
            .schedule-main.mail .logo img {height: 150px;object-fit: cover;}
            .summary-text h4 {margin: 20px 0 10px 0;font-size: 18px;text-align: left;}
            .summary-text table {width: 100%;border-collapse: collapse;margin-bottom: 20px;}
            .summary-text th, .summary-text td {padding:7px 10px;text-align: left;border: 1px solid #ddd;}
            .summary-text th {font-weight: bold;background-color: #f5f5f5;}
            span.week-date, span.next-week-date {font-weight: 600;text-decoration: underline;}
        </style>
    </head>
    <body class="body" id="body" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
        <div class="schedule-main mail">
            <div class="logo">
                <?php if (! empty($report['company_logo'])) : ?>
                    <img src="<?php echo esc_url($report['company_logo']); ?>" alt="Logo">
                <?php else : ?>
                    <span><?php echo esc_html($report['site_name']); ?></span>
                <?php endif; ?>
            </div>
            <div class="summary-text">
                <?php ppcart_schedule_report_render_intro($report); ?>
                <?php ppcart_schedule_report_render_revenue_list($report); ?>
                <?php ppcart_schedule_report_render_upcoming_period($report); ?>
                <?php ppcart_schedule_report_render_subscription_table(__('Ending Trials', 'publishpress-cart'), $report['trials'], __('End date', 'publishpress-cart'), __('Recurring amount', 'publishpress-cart'), 'ending-trials-heading', 'ending-trials-table'); ?>
                <?php ppcart_schedule_report_render_subscription_table(__('Subscription Renewals', 'publishpress-cart'), $report['renewals'], __('Next bill date', 'publishpress-cart'), __('Amount', 'publishpress-cart'), 'subscription-renewals-heading', 'subscription-renewals-table'); ?>
            </div>
        </div>
    </body>
    </html>
    <?php

    return ob_get_clean();
}

function ppcart_schedule_report_render_intro($report)
{
    ?>
    <p class="header-paragraph"><?php esc_html_e('Here is your summary for', 'publishpress-cart'); ?> <strong class="site-name"> <?php echo esc_html($report['site_name']); ?> </strong>
        <?php if ('ppcart_weekly' === $report['schedule']) : ?>
            <?php esc_html_e('for the week of', 'publishpress-cart'); ?>  <span class="week-date"><?php echo esc_html($report['date_1']); ?> - <?php echo esc_html($report['date_2']); ?></span>.
        <?php elseif ('ppcart_semi_monthly' === $report['schedule']) : ?>
            <?php esc_html_e('for', 'publishpress-cart'); ?>  <span class="week-date"><?php echo esc_html($report['date_1']); ?> - <?php echo esc_html($report['date_2']); ?></span>.
        <?php else : ?>
            <?php esc_html_e('for', 'publishpress-cart'); ?>  <span class="week-date"><?php echo esc_html($report['date_1']); ?></span>.
        <?php endif; ?>
    </p>
    <?php
}

function ppcart_schedule_report_render_revenue_list($report)
{
    ?>
    <ul class="revenue-list">
        <li><span class="revenue-label"><?php esc_html_e('Gross revenue', 'publishpress-cart'); ?>  :</span> <span class="revenue-amount"><?php ppcart_formatted_price($report['carttotal']['total']); ?></span></li>
        <li><span class="revenue-label"><?php esc_html_e('Refunds', 'publishpress-cart'); ?>  :</span> <span class="revenue-amount"><?php ppcart_formatted_price($report['refunded_amount']); ?></span></li>
        <li><span class="revenue-label"><?php esc_html_e('Net revenue', 'publishpress-cart'); ?>  :</span> <span class="revenue-amount"><?php ppcart_formatted_price($report['carttotal']['total'] - $report['refunded_amount']); ?></span></li>
        <li><span class="revenue-label"><?php esc_html_e('Completed Orders', 'publishpress-cart'); ?>  :</span> <span class="revenue-amount"><?php echo esc_html($report['completed_orders']); ?></span></li>
        <li><span class="revenue-label"><?php esc_html_e('Pending Orders', 'publishpress-cart'); ?>  :</span> <span class="revenue-amount"><?php echo esc_html($report['pending_payment']); ?></span></li>
        <li><span class="revenue-label"><?php esc_html_e('Failed Orders', 'publishpress-cart'); ?>  :</span> <span class="revenue-amount"><?php echo esc_html($report['failed_payment']); ?></span></li>
        <li><span class="revenue-label"><?php esc_html_e('Refunded Orders', 'publishpress-cart'); ?>  :</span> <span class="revenue-amount"><?php echo esc_html($report['refunded_time']); ?></span></li>
        <li><span class="revenue-label"><?php esc_html_e('Subscriptions Started', 'publishpress-cart'); ?>  :</span> <span class="revenue-amount"><?php echo esc_html($report['all_subscription']); ?></span></li>
        <li><span class="revenue-label"><?php esc_html_e('Trials Started', 'publishpress-cart'); ?>  :</span> <span class="revenue-amount"><?php echo esc_html($report['trialing_payment']); ?></span></li>
        <li><span class="revenue-label"><?php esc_html_e('Subscriptions Canceled', 'publishpress-cart'); ?>  :</span> <span class="revenue-amount"><?php echo esc_html($report['canceled_subscription']); ?></span></li>
    </ul>
    <?php
}

function ppcart_schedule_report_render_upcoming_period($report)
{
    ?>
    <p>
        <?php if ('ppcart_weekly' === $report['schedule']) : ?>
            <?php esc_html_e('Coming up for the week of', 'publishpress-cart'); ?>  <span class="next-week-date"><?php echo esc_html($report['datenxt_1']); ?>  - <?php echo esc_html($report['datenxt_2']); ?></span>
        <?php elseif ('ppcart_semi_monthly' === $report['schedule']) : ?>
            <?php esc_html_e('Coming up for', 'publishpress-cart'); ?>  <span class="next-week-date"><?php echo esc_html($report['datenxt_1']); ?>  - <?php echo esc_html($report['datenxt_2']); ?></span>
        <?php else : ?>
            <?php esc_html_e('Coming up for', 'publishpress-cart'); ?>  <span class="next-week-date"><?php echo esc_html($report['date_2']); ?></span>
        <?php endif; ?>
    </p>
    <?php
}

function ppcart_schedule_report_render_subscription_table($heading, $rows, $date_heading, $amount_heading, $heading_class, $table_class)
{
    ?>
    <h4 class="<?php echo esc_attr($heading_class); ?>"><?php echo esc_html($heading); ?>:</h4>
    <table class="<?php echo esc_attr($table_class); ?>">
        <thead>
            <tr>
                <th><?php esc_html_e('Customer', 'publishpress-cart'); ?></th>
                <th><?php esc_html_e('Product', 'publishpress-cart'); ?></th>
                <th><?php echo esc_html($date_heading); ?></th>
                <th><?php echo esc_html($amount_heading); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($rows) > 0) : ?>
                <?php foreach ($rows as $value) : ?>
                    <tr>
                        <td><?php echo esc_html($value['customer_name']); ?></td>
                        <td><?php echo esc_html($value['product_name']); ?></td>
                        <td><?php echo esc_html(ppcart_maybe_format_date($value['sub_next_bill_date'])); ?></td>
                        <td><?php ppcart_formatted_price($value['sub_amount']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr><td colspan="4"><?php esc_html_e('Nothing found for this time period', 'publishpress-cart'); ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php
}
