<?php

if (! defined('ABSPATH')) {
    exit;
}


/**
 * The Template for displaying a product archive
 * This template can be overridden by copying it to <active-theme-folder>/publishpress-cart/shortcodes/archive.php.
 */

?>

<div class="ppcart-archive">

    <?php ppcart_template('archive/item', 'wrapper-start', $attr);

$the_query = $attr['query'];
while ($the_query->have_posts()) {
    $the_query->the_post();

    if ($ppcart_current_user = wp_get_current_user()) {
        if (do_shortcode('[ppcart_customer_bought_product email=' . $ppcart_current_user->user_email . ' user_id=' . $ppcart_current_user->ID . ']')) {
            $attr['button_text'] = $attr['purchased_text'];
        }
    }

    ppcart_template('archive/item', '', $attr);
}

wp_reset_postdata(); ?>

    <?php ppcart_template('archive/item', 'wrapper-end', $attr); ?>
    <?php ppcart_template('archive/navigation', '', $attr); ?>

</div>
