<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Register custom post type
 *
 * @link https://publishpress.com/
 * @since 1.0.0
 *
 * @package PPCart
 * @subpackage PPCart/includes
 */

class PPCart_Post_Types
{
    /**
         * Creates a new custom post type
         *
         * @since 1.0.0
         * @access public
         * @uses    register_post_type()
         */

    /**
     * Create post types
     */

    public static function register_single_post_type($cap_type, $plural, $single, $cpt_name, $supports = false, $public = false, $show_ui = true)
    {
        include __DIR__ . '/templates/post-types-register-single-post-type.php';
    }

    private function register_single_post_type_taxonomy($plural, $single, $tax_name)
    {
        include __DIR__ . '/templates/post-types-register-single-taxonomy.php';
    }


    public function create_custom_post_type()
    {
        include __DIR__ . '/templates/post-types-create-custom-post-type.php';
    }
}
