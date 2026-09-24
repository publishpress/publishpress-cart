<?php

if (! defined('ABSPATH')) {
    exit;
}


/**
 * Resolve a theme override for a plugin template relative path.
 *
 * Canonical theme copies live in yourtheme/publishpress-cart/. A missing
 * canonical file may be replaced via ppcart_theme_template_path.
 *
 * @param string $relative Path under public/templates/, including .php.
 * @return string Absolute theme file path, or empty when none exists.
 */
function ppcart_locate_theme_template($relative)
{
    $canonical = get_stylesheet_directory() . '/publishpress-cart/' . $relative;
    if (file_exists($canonical)) {
        return $canonical;
    }

    $filtered = apply_filters('ppcart_theme_template_path', $canonical, $relative);
    if (is_string($filtered) && '' !== $filtered && $filtered !== $canonical && file_exists($filtered)) {
        return $filtered;
    }

    return '';
}

/**
 * Return the template path
 * @param $template_name Name of the template
 * @param $slug path to the directory of the template location
 */
function ppcart_get_template_path($slug, $name = '')
{

    $template_path = $slug;

    if ($name) {
        $template_path .= '-' . $name;
    }

    $template_path .= '.php';

    $theme = ppcart_locate_theme_template($template_path);
    if ('' !== $theme) {
        return $theme;
    }

    $template = PPCART_BASE_DIR . 'public/templates/' . $template_path;
    if (!file_exists($template)) {
        return false;
    }

    return $template;
}

/**
 * Returns the template
 * @param $name|String Name of the template
 * @param $path|String path to the directory of the template location
 * @param $attr|array Data variables
 * @param $canOverride|boolean To determine if template can be overridden in theme
 */
function ppcart_get_template($slug, $name = '', $attr = [])
{

    ob_start();
    do_action('ppcart_template_before_' . $slug);

    $template = ppcart_get_template_path($slug, $name);

    do_action('ppcart_template_after_' . $slug);

    if ($template) {
        require($template);
    }

    $html = ob_get_contents();
    ob_end_clean();
    return $html;
}

/**
 * Includes the template
 * @param $template_name|String Name of the template
 * @param $path|String path to the directory of the template location
 */
function ppcart_template($slug, $name = '', $attr = [])
{
    $template = ppcart_get_template_path($slug, $name, $attr);
    require($template);
}


/**
 * Display tabs on my account page
 */
function ppcart_account_tabs()
{

    $tabs = [

        [
            'id' => 'tab-orders',
            'title' => __('Orders', 'publishpress-cart'),
            'content' => 'my-account/tabs/order-history',
            'active' => 1,
        ],

        [
            'id' => 'tab-subscriptions',
            'title' => __('Subscriptions', 'publishpress-cart'),
            'content' => 'my-account/tabs/subscriptions',
        ],

        [
            'id' => 'tab-plans',
            'title' => __('Installment Plans', 'publishpress-cart'),
            'content' => 'my-account/tabs/plans',
        ],

        [
            'id' => 'tab-profile',
            'title' => __('My Profile', 'publishpress-cart'),
            'content' => 'my-account/tabs/user-profile',
        ],
    ];


    $filteredTabs = apply_filters('ppcart_account_tabs', $tabs);

    foreach ($filteredTabs as $key => $tab) {
        if (isset($tab['is_active'])) {
            unset($filteredTabs[$key]['is_active']);
            unset($filteredTabs[0]['active']);
            $filteredTabs[$key]['active'] = 1;
        }

        if (isset($tab['order'])) {
            $filteredTabs[$key]['order'] = $tab['order'] - 1;
        } else {
            $filteredTabs[$key]['order'] = $key + 1;
        }
    }

    $keys = array_column($filteredTabs, 'order');
    array_multisort($keys, SORT_ASC, $filteredTabs);
    return $filteredTabs;
}
