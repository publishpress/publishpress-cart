<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Product_Metaboxes_Options_Trait
{
    /**
     * Returns the count of the largest arrays
     *
     * @param array         $array      An array of arrays to count
     * @return      int                     The count of the largest array
     */
    public static function get_max($array)
    {

        if (empty($array)) {
            return 0;
        }

        $count = [];

        foreach ($array as $name => $field) {
            $count[$name] = count($field);
        }

        $count = max($count);

        return $count;
    }

    //get_activecampaign_lists
    //get_activecampaign_tags
    public static function get_plans($key)
    {
        return PPCart_Product_Metabox_Option_Sources::get_plans($key);
    }

    public static function get_plan_data($product_id)
    {
        return PPCart_Product_Metabox_Option_Sources::get_plan_data($product_id);
    }

    public static function get_payment_plans($plansOnly = false)
    {
        return PPCart_Product_Metabox_Option_Sources::get_payment_plans($plansOnly);
    }

    public static function product_options()
    {
        return PPCart_Product_Metabox_Option_Sources::product_options();
    }

    public static function upsell_paths()
    {
        return PPCart_Product_Metabox_Option_Sources::upsell_paths();
    }

    public static function fa_icons()
    {
        return PPCart_Product_Metabox_Option_Sources::fa_icons();
    }
}
