<?php

if (! defined('ABSPATH')) {
    exit;
}


/**
 * The Template for displaying customer's card details
 * This template can be overridden by copying it to yourtheme/publishpress-cart/my-account/card-details.php.
 */

?>
<p>
    <?php
    $card = $args->card->card;
if (file_exists(PPCART_BASE_DIR . 'public/images/cc/' . $card->brand . '.svg')) {
    $card_image = $card->brand;
} else {
    $card_image = 'generic';
}
$ppcart_plan = ppcart_filter_input_request('ppcart-plan', FILTER_VALIDATE_INT);

esc_html_e("Payment Method", 'publishpress-cart'); ?><br>
    <img class="ppcart-card-icon" src="<?php echo esc_url(PPCART_BASE_URL . 'public/images/cc/' . $card_image . '.svg'); ?>"> xxxx xxxx xxxx <?php echo esc_html($card->last4); ?> (<?php esc_html_e("Expires", 'publishpress-cart'); ?> <?php echo esc_html($card->exp_month . '/' . $card->exp_year); ?>) | <a id="ppcart-update-card-open" class="openmodal update_card" href="#" data-id="<?php echo esc_attr((false !== $ppcart_plan && null !== $ppcart_plan) ? absint($ppcart_plan) : 0); ?>" data-item-id="<?php echo esc_attr(apply_filters('ppcart_sub_item_id', $args->option_id, $args)); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-update-card-open')); ?>"><?php esc_html_e('Update Card', 'publishpress-cart'); ?></a>
</p>
