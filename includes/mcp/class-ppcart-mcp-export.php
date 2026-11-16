<?php

/**
 * Bounded customer report exports with native operation and record permissions.
 *
 * @package PPCart
 * @since 1.1.0
 */

defined('ABSPATH') || exit;

class PPCart_Mcp_Export
{
    /** @since 1.1.0 @return array */
    public function schema()
    {
        return PPCart_Mcp_Input::schema([
            'kind' => ['type' => 'string', 'enum' => ['order', 'subscription', 'contacts']],
            'page' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 10000],
            'per_page' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100],
            'from' => ['type' => 'string', 'pattern' => '^[0-9]{4}-[0-9]{2}-[0-9]{2}$'],
            'to' => ['type' => 'string', 'pattern' => '^[0-9]{4}-[0-9]{2}-[0-9]{2}$'],
            'product_id' => ['type' => 'integer', 'minimum' => 1],
            'email' => ['type' => 'string', 'maxLength' => 200],
        ], ['kind']);
    }

    /** @since 1.1.0 @return bool */
    public function permitted()
    {
        return (current_user_can('manage_options') || ppcart_user_can('manage_orders'))
            && PPCart_Mcp_Reports::permitted();
    }

    /** @since 1.1.0 @param mixed $input JSON input. @return array|WP_Error */
    public function read($input)
    {
        $input = PPCart_Mcp_Input::validate($input, $this->schema());
        if (is_wp_error($input)) {
            return $input;
        }
        if (! $this->permitted()) {
            return $this->forbidden();
        }
        foreach (['from', 'to'] as $key) {
            if (isset($input[$key])) {
                $parts = array_map('intval', explode('-', $input[$key]));
                if (! checkdate($parts[1], $parts[2], $parts[0])) {
                    return PPCart_Mcp_Input::error();
                }
            }
        }
        if (
            (isset($input['from'], $input['to']) && $input['from'] > $input['to'])
            || (isset($input['email']) && ! is_email($input['email']))
        ) {
            return PPCart_Mcp_Input::error();
        }
        if (
            isset($input['product_id']) && (! ppcart_is_product_post_type(get_post_type($input['product_id']))
            || ! current_user_can('read_post', $input['product_id']))
        ) {
            return $this->forbidden();
        }
        if ('contacts' === $input['kind']) {
            return $this->contacts($input);
        }
        $page = $input['page'] ?? 1;
        $size = $input['per_page'] ?? 50;
        $args = ['post_type' => ppcart_query_post_types($input['kind']), 'post_status' => 'any',
            'posts_per_page' => $size, 'paged' => $page, 'orderby' => 'ID', 'order' => 'ASC', 'fields' => 'ids'];
        $dates = ['inclusive' => true];
        if (isset($input['from'])) {
            $dates['after'] = $input['from'] . ' 00:00:00';
        }
        if (isset($input['to'])) {
            $dates['before'] = $input['to'] . ' 23:59:59';
        }
        if (count($dates) > 1) {
            $args['date_query'] = [$dates];
        }
        $args = ppcart_apply_report_filters_to_query_args($args, ['customer' => $input['email'] ?? '', 'product_id' => $input['product_id'] ?? 0]);
        $query = new WP_Query($args);
        $header = 'order' === $input['kind']
            ? ['id', 'date', 'firstname', 'lastname', 'email', 'phone', 'address1', 'address2', 'city', 'state', 'zip', 'country',
                'amount', 'product_name', 'option_id', 'status', 'coupon_id', 'currency', 'tax_amount', 'shipping_amount', 'refund_total', 'net_amount']
            : ['id', 'date', 'product_name', 'option_id', 'sub_interval', 'sub_frequency', 'status', 'sub_amount', 'currency', 'sub_next_bill_date', 'sub_end_date'];
        $rows = [$header];
        foreach ($query->posts as $id) {
            if (! current_user_can('read_post', $id) || ! current_user_can('edit_post', $id)) {
                return $this->forbidden();
            }
            $model = 'order' === $input['kind'] ? new PPCart_Order($id) : new PPCart_Subscription($id);
            $row = [];
            foreach ($header as $field) {
                if ('date' === $field) {
                    $value = get_the_date('Y-m-d', $id);
                } elseif ('refund_total' === $field) {
                    $value = PPCart_Order_Refunds::get_refund_total($id);
                } elseif ('net_amount' === $field) {
                    $value = PPCart_Order_Refunds::get_net_amount($id);
                } else {
                    $value = $model->$field ?? '';
                }
                $row[] = is_scalar($value) ? $value : '';
            }
            $rows[] = $row;
        }
        return $this->result($input['kind'], $page, $page < $query->max_num_pages, $rows);
    }

    /** @since 1.1.0 @param array $input Filters. @return array|WP_Error */
    private function contacts(array $input)
    {
        if (isset($input['from']) || isset($input['to']) || isset($input['product_id'])) {
            return PPCart_Mcp_Input::error();
        }
        global $wpdb;
        $types = ppcart_query_post_types('order');
        if (! $types) {
            return $this->forbidden();
        }
        $placeholders = implode(',', array_fill(0, count($types), '%s'));
        $page = $input['page'] ?? 1;
        $size = $input['per_page'] ?? 50;
        $email = $input['email'] ?? '';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared -- Bounded native contact grouping; all values are prepared.
        $emails = $wpdb->get_col($wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Only generated placeholders and core table identifiers are interpolated.
            "SELECT LOWER(m.meta_value) AS email FROM {$wpdb->postmeta} m INNER JOIN {$wpdb->posts} p ON p.ID=m.post_id WHERE m.meta_key=%s AND m.meta_value<>'' AND p.post_type IN ($placeholders) AND p.post_status NOT IN ('trash','auto-draft') AND (%s='' OR m.meta_value=%s) GROUP BY LOWER(m.meta_value) ORDER BY email LIMIT %d OFFSET %d",
            array_merge([ppcart_meta_key('email')], $types, [$email, $email, $size + 1, ($page - 1) * $size])
        ));
        if ($wpdb->last_error || ! is_array($emails)) {
            return new WP_Error('ppcart_mcp_export_failed', __('Contacts could not be read.', 'publishpress-cart'));
        }
        $more = count($emails) > $size;
        $rows = [['First Name', 'Last Name', 'Email', 'Orders', 'Last Order Date']];
        $contributions = 0;
        foreach (array_slice($emails, 0, $size) as $contact) {
            if (! is_email($contact)) {
                return new WP_Error('ppcart_mcp_export_failed', __('A stored contact has an invalid email. Review the native customer report.', 'publishpress-cart'));
            }
            $query = new WP_Query(ppcart_apply_report_filters_to_query_args([
                // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- Bounded permission audit of every contributing order; reject pages over 1,000 contributing orders instead of returning unchecked aggregates.
                'post_type' => $types, 'post_status' => 'any', 'posts_per_page' => 1001 - $contributions,
                'orderby' => ['date' => 'DESC', 'ID' => 'DESC'], 'fields' => 'ids', 'no_found_rows' => true,
            ], ['customer' => $contact, 'product_id' => 0]));
            $contributions += count($query->posts);
            if ($contributions > 1000 || ! $query->posts) {
                return new WP_Error('ppcart_mcp_export_limit', __('Use smaller pages or native customer exports for pages with over 1,000 contributing orders or inconsistent records.', 'publishpress-cart'));
            }
            foreach ($query->posts as $id) {
                if (! current_user_can('read_post', $id) || ! current_user_can('edit_post', $id)) {
                    return $this->forbidden();
                }
            }
            $id = $query->posts[0];
            $rows[] = [ppcart_get_post_meta($id, 'firstname', true), ppcart_get_post_meta($id, 'lastname', true),
                $contact, count($query->posts), get_the_date('M j Y', $id)];
        }
        return $this->result('contacts', $page, $more, $rows);
    }

    /** @since 1.1.0 @param string $kind Kind. @param int $page Page. @param bool $more More pages. @param array $rows CSV rows. @return array|WP_Error */
    private function result($kind, $page, $more, array $rows)
    {
        foreach ($rows as $row) {
            foreach ($row as $value) {
                if ((! is_scalar($value) && null !== $value) || strlen((string) $value) > 20000) {
                    return new WP_Error('ppcart_mcp_export_limit', __('Use native exports for oversized report fields.', 'publishpress-cart'));
                }
            }
        }
        $csv = PPCart_Mcp_Csv::encode($rows);
        if (strlen($csv) > 1048576) {
            return new WP_Error('ppcart_mcp_export_limit', __('Use a smaller export page size.', 'publishpress-cart'));
        }
        return ['kind' => $kind, 'page' => $page, 'rows' => count($rows) - 1,
            'next_page' => $more ? $page + 1 : null, 'csv' => $csv];
    }

    /** @since 1.1.0 @return WP_Error */
    private function forbidden()
    {
        return new WP_Error('ppcart_mcp_forbidden', __('You cannot export these Cart records.', 'publishpress-cart'));
    }
}
