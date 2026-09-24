<?php

if (! defined('ABSPATH')) {
    exit;
}


$attrs = isset($block['attrs']) && is_array($block['attrs']) ? $block['attrs'] : [];

if (empty($attrs['useFeaturedImage'])) {
    return $block_content;
}

$class_name = isset($attrs['className']) ? (string) $attrs['className'] : '';
if (false === strpos($class_name, 'publishpress-cart-product-template')) {
    return $block_content;
}

$current_post_id = get_the_ID();
if (! $current_post_id) {
    return $block_content;
}

$post_types = self::get_product_post_types();
if (! in_array(get_post_type($current_post_id), $post_types, true)) {
    return $block_content;
}

if (has_post_thumbnail($current_post_id)) {
    return $block_content;
}

if (false !== strpos($block_content, 'publishpress-cart-product-template__fallback-image')) {
    return $block_content;
}

$fallback_image_url = self::get_fallback_image_url();
if ('' === $fallback_image_url) {
    return $block_content;
}

$object_position = '50% 50%';
if (! empty($attrs['focalPoint']) && is_array($attrs['focalPoint'])) {
    $x               = isset($attrs['focalPoint']['x']) ? round((float) $attrs['focalPoint']['x'] * 100) : 50;
    $y               = isset($attrs['focalPoint']['y']) ? round((float) $attrs['focalPoint']['y'] * 100) : 50;
    $object_position = $x . '% ' . $y . '%';
}

$image = sprintf(
    '<img class="wp-block-cover__image-background publishpress-cart-product-template__fallback-image" alt="" src="%1$s" data-object-fit="cover" data-object-position="%2$s" style="object-position:%2$s" />',
    esc_url($fallback_image_url),
    esc_attr($object_position)
);

$inner_container_start = '/<div\b[^>]+\bwp-block-cover__inner-container(?:\s|")[^>]*>/U';
if (1 === preg_match($inner_container_start, $block_content, $matches, PREG_OFFSET_CAPTURE)) {
    $offset = $matches[0][1];

    return substr($block_content, 0, $offset) . $image . substr($block_content, $offset);
}

return $block_content;
