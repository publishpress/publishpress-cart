<?php

if (! defined('ABSPATH')) {
    exit;
}


if (! self::is_supported_block_theme()) {
    return;
}

if (get_option('_ppcart_disable_template')) {
    return;
}

$post_types = self::get_product_post_types();

foreach ($post_types as $product_post_type) {
    $template_name = self::get_template_name($product_post_type);

    if (class_exists('WP_Block_Templates_Registry')) {
        $registry = WP_Block_Templates_Registry::get_instance();

        if ($registry->is_registered($template_name)) {
            continue;
        }
    }

    register_block_template(
        $template_name,
        [
            'title'       => self::get_template_title($product_post_type),
            'description' => esc_html__('Controls PublishPress Cart product pages. Edit this template to move product content and the checkout form.', 'publishpress-cart'),
            'content'     => self::get_template_content(),
            'post_types'  => [ $product_post_type ],
            'plugin'      => self::PLUGIN_SLUG,
        ]
    );
}
