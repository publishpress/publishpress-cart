<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Admin_Reports_Data_Trait
{
    /**
     * Calculate the captured refund amount and count for an order.
     *
     * @param int $order_id Order post ID.
     * @return array
     */
    private function get_refund_data($order_id)
    {
        $refund_entries = ppcart_get_post_meta($order_id, 'refund_log', true);
        $refund_amount  = 0;
        $refund_count   = 1;

        if (is_array($refund_entries) && ! empty($refund_entries)) {
            $refund_count  = count($refund_entries);
            $amount_values = array_map(
                'floatval',
                array_column($refund_entries, 'amount')
            );
            $refund_amount = array_sum($amount_values);
        }

        if (! $refund_amount) {
            $refund_amount = (float) ppcart_get_post_meta($order_id, 'amount', true);
        }

        return [
            'amount' => (float) $refund_amount,
            'count'  => (int) $refund_count,
        ];
    }

    /**
     * Build revenue chart buckets from paid order events.
     *
     * @param array $events Revenue events.
     * @param array $period Parsed report period.
     * @return array
     */
    private function build_revenue_buckets($events, $period)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/reports-data-build-revenue-buckets.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Initialize zero-value revenue buckets.
     *
     * @param int    $start Start timestamp.
     * @param int    $end End timestamp.
     * @param string $interval Bucket interval.
     * @return array
     */
    private function initialize_revenue_buckets($start, $end, $interval)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/reports-data-initialize-revenue-buckets.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Return a bucket key for a timestamp.
     *
     * @param int    $timestamp Timestamp.
     * @param string $interval Bucket interval.
     * @return string
     */
    private function get_bucket_key($timestamp, $interval)
    {
        if ('month' === $interval) {
            return $this->format_report_date('Y-m', $timestamp);
        }

        if ('week' === $interval) {
            return $this->format_report_date('o-\WW', $timestamp);
        }

        return $this->format_report_date('Y-m-d', $timestamp);
    }

    /**
     * Return a readable chart label for a bucket.
     *
     * @param string $key Bucket key.
     * @param string $interval Bucket interval.
     * @return string
     */
    private function get_bucket_label($key, $interval)
    {
        $timezone = $this->get_report_timezone();

        if ('month' === $interval) {
            $date = DateTime::createFromFormat('Y-m-d H:i:s', $key . '-01 00:00:00', $timezone);

            return $date instanceof DateTime ? $this->format_report_date('M Y', $date->getTimestamp()) : $key;
        }

        if ('week' === $interval) {
            $parts = explode('-W', $key);
            $date  = new DateTime('now', $timezone);
            $date->setISODate((int) $parts[0], (int) $parts[1]);
            $date->setTime(0, 0, 0);

            return $this->format_report_date('M j', $date->getTimestamp());
        }

        $date = DateTime::createFromFormat('Y-m-d H:i:s', $key . ' 00:00:00', $timezone);

        return $date instanceof DateTime ? $this->format_report_date('M j', $date->getTimestamp()) : $key;
    }

    /**
     * Prepare product revenue rows for visual breakdowns.
     *
     * @param array $product_revenue Product revenue map.
     * @return array
     */
    private function get_product_revenue_rows($product_revenue)
    {
        arsort($product_revenue);
        $rows = [];

        foreach (array_slice($product_revenue, 0, 6, true) as $product_id => $value) {
            $rows[] = [
                'label'   => get_the_title($product_id) ? get_the_title($product_id) : __('Unknown product', 'publishpress-cart'),
                'value'   => (float) $value,
                'display' => ppcart_format_price($value),
            ];
        }

        return $rows;
    }

    /**
     * Prepare gateway rows for visual breakdowns.
     *
     * @param array $gateways Gateway counts.
     * @param array $gateway_labels Gateway labels.
     * @return array
     */
    private function get_gateway_rows($gateways, $gateway_labels)
    {
        arsort($gateways);
        $rows = [];

        foreach ($gateways as $gateway => $value) {
            $rows[] = [
                'label'   => $gateway_labels[ $gateway ] ?? $gateway,
                'value'   => (float) $value,
                'display' => number_format_i18n($value),
            ];
        }

        return $rows;
    }

    /**
     * Calculate all-time customer lifetime value from local orders.
     *
     * @param array $query_args Optional extra WP_Query args such as meta_query.
     * @return array
     */
    private function get_customer_lifetime_value($query_args = [])
    {
        $__ppcart_template_result = include __DIR__ . '/templates/reports-data-get-customer-lifetime-value.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Calculate subscription health metrics from local subscriptions.
     *
     * @param array $period Parsed report period.
     * @param array $meta_query Optional identity meta_query fragment.
     * @return array
     */
    private function get_subscription_metrics($period, $meta_query = [])
    {
        $__ppcart_template_result = include __DIR__ . '/templates/reports-data-get-subscription-metrics.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Normalize one active recurring subscription into monthly revenue.
     *
     * @param int $subscription_id Subscription post ID.
     * @return float
     */
    private function normalize_subscription_mrr($subscription_id)
    {
        $amount    = (float) ppcart_get_post_meta($subscription_id, 'sub_amount', true);
        $interval  = (string) ppcart_get_post_meta($subscription_id, 'sub_interval', true);
        $frequency = (float) ppcart_get_post_meta($subscription_id, 'sub_frequency', true);

        if (! $frequency) {
            $frequency = (float) ppcart_get_post_meta($subscription_id, 'frequency', true);
        }

        $frequency = $frequency ? $frequency : 1;

        switch ($interval) {
            case 'day':
                return $amount * (30.4375 / $frequency);
            case 'week':
                return $amount * (4.34524 / $frequency);
            case 'year':
                return $amount / (12 * $frequency);
            case 'month':
                return $amount / $frequency;
            default:
                return $amount;
        }
    }
}
