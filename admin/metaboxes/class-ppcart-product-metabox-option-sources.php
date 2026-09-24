<?php

if (! defined('ABSPATH')) {
    exit;
}


require_once __DIR__ . '/traits/trait-ppcart-product-metabox-integration-options.php';

require_once __DIR__ . '/traits/trait-ppcart-product-metabox-plan-options.php';

/**
 * Product metabox option sources.
 *
 * @package PPCart
 * @subpackage PPCart/admin
 */

/**
 * Provides select-list options used by product metabox fields.
 */
class PPCart_Product_Metabox_Option_Sources
{
    use PPCart_Product_Metabox_Integration_Options_Trait;
    use PPCart_Product_Metabox_Plan_Options_Trait;

    public function get_pages($noblank = false)
    {
        $pages = get_pages();

        $options = ['' => __('Select Page', 'publishpress-cart')];
        if ($noblank) {
            $options = [];
        }

        foreach ($pages as $page) {
            $options[$page->ID] = $page->post_title . ' (ID: ' . $page->ID . ')';
        }

        return $options;
    }

    public function get_user_roles()
    {
        if (!function_exists('get_editable_roles')) {
            echo 'nope';
            die();
            return;
        }
        $options = [];
        foreach (get_editable_roles() as $role_name => $role_info) {
            $options[$role_name] = $role_info['name'];
        }
        return $options;
    }
}
