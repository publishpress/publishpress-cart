<?php

if (! defined('ABSPATH')) {
    exit;
}


/**
 * The Template for displaying the product archive wrapper start
 * This template can be overridden by copying it to <active-theme-folder>/publishpress-cart/archive/item-wrapper-start.php.
 */

?>

    <?php do_action('ppcart_before_product_list'); ?>
    <ul class="cards col-<?php echo esc_attr($attr['cols']); ?>">
