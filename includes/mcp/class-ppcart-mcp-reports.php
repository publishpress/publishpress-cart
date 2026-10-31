<?php

/**
 * Native monthly dashboard metrics with report-level permissions.
 *
 * @package PPCart
 * @since 1.1.0
 */

defined('ABSPATH') || exit;

class PPCart_Mcp_Reports
{
    /**
     * Require access to the native report surface and all record families it aggregates.
     *
     * @since 1.1.0
     * @return bool
     */
    public static function permitted()
    {
        if (! current_user_can(ppcart_live_cap('manager_option'))) {
            return false;
        }
        foreach (['product', 'order', 'subscription'] as $kind) {
            $type = get_post_type_object(ppcart_live_post_type($kind));
            if (
                ! $type || ! current_user_can($type->cap->edit_others_posts)
                || ! current_user_can($type->cap->read_private_posts)
            ) {
                return false;
            }
        }
        return true;
    }

    /**
     * @since 1.1.0
     * @param mixed $input JSON input.
     * @return array|WP_Error
     */
    public function read($input)
    {
        $input = PPCart_Mcp_Input::validate($input, PPCart_Mcp_Input::schema([]));
        if (is_wp_error($input)) {
            return $input;
        }
        if (! self::permitted()) {
            return new WP_Error('ppcart_mcp_forbidden', __('You cannot view Cart reports.', 'publishpress-cart'));
        }
        $types = [];
        foreach (['product', 'order', 'subscription'] as $kind) {
            $types = array_merge($types, ppcart_query_post_types($kind)); 
        }
        // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- Native summaries can include historical records; every contributor must be authorized within a bounded scope.
        $ids = get_posts(['post_type' => array_unique($types), 'post_status' => array_keys(get_post_stati()), 'posts_per_page' => 1001, 'fields' => 'ids']);
        if (count($ids) > 1000) {
            return new WP_Error('ppcart_mcp_scope_limit', __('Use native reports for a summary exceeding 1,000 permission-checked records.', 'publishpress-cart')); 
        }
        foreach ($ids as $id) {
            if (! current_user_can('read_post', $id) || ! current_user_can('edit_post', $id)) {
                return new WP_Error('ppcart_mcp_forbidden', __('You cannot read every contributing record.', 'publishpress-cart'));
            }
        }
        require_once dirname(__DIR__, 2) . '/admin/dashboard/class-ppcart-dashboard-data.php';
        $data = new PPCart_Dashboard_Data();
        return ['month' => wp_date('Y-m'), 'summary' => $data->get_summary()];
    }
}
