<?php

if (! defined('ABSPATH')) {
    exit;
}


/**
 * The Template for displaying a product archive navigation
 * This template can be overridden by copying it to <active-theme-folder>/publishpress-cart/archive/navigation.php.
 */

?>

<?php $the_query = $attr['query']; ?>

<div class="ppcart-archive-navigation"> 
    <?php
    $GLOBALS['wp_query']->max_num_pages = $the_query->max_num_pages;
the_posts_pagination([
    'mid_size' => 3,
    // Icon-only arrows: the SVG is hidden from assistive technology and the link is named by text.
    'prev_text' => '<svg aria-hidden="true" focusable="false" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg><span class="screen-reader-text">' . esc_html__('Previous page', 'publishpress-cart') . '</span>',
    'next_text' => '<svg aria-hidden="true" focusable="false" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg><span class="screen-reader-text">' . esc_html__('Next page', 'publishpress-cart') . '</span>',
]); ?>
</div>
