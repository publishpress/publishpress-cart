<?php

if (! defined('ABSPATH')) {
    exit;
}


$installments = $order->sub_installments;
if ($installments == '-1') {
    $installments = '&infin;';
}
$line_total = $sub ? $sub['plan']->price : $order->sub_amount;
?>
<tr class="item">

    <?php
    if (has_post_thumbnail($order->product_id)) {
        echo '<td class="thumb">';
        echo '<div class="ppcart-order-item-thumbnail">';
        echo get_the_post_thumbnail($order->product_id, 'thumbnail');
        echo '</div>';
        echo '</td>';
    } else {
        echo '<td style="padding:0"></td>';
    }
?>

    <td class="name">
        <span style="opacity: 75%"><?php esc_html_e('Product ID: ', 'publishpress-cart'); ?><span id="product_ID"><?php echo esc_html($order->product_id); ?></span></span><br>
        <a href="<?php echo esc_url(ppcart_get_edit_post_url($order->product_id)); ?>" class="ppcart-order-item-name"><?php echo esc_html((string) $order->product_name); ?></a>
        (<?php echo esc_html(($sub) ? $sub['plan']->name : $order->sub_item_name); ?>)
        <?php if ($order->coupon_id && !in_array($order->coupon['type'], ['cart-percent', 'cart-fixed'])) {
            echo '<br><span class="badge">' . esc_html__('Coupon: ', 'publishpress-cart') . esc_html($order->coupon_id) . '<span>';
        } ?>

    </td>
    <td class="item_cost"><?php echo esc_html($order->quantity); ?></td>
    <td class="item_cost"><?php echo esc_html($installments); ?></td>
    <?php if ($sub) : ?>
        <td class="item_cost">
            <div class="view">
                <span class="ppcart-Price-amount amount">
                    <?php ppcart_formatted_price($sub['plan']->price); ?>
                </span> /
                <?php
                if ($sub['plan']->frequency > 1) {
                    echo esc_html($sub['plan']->frequency . ' ' . ppcart_pluralize_interval($sub['plan']->interval));
                } else {
                    echo esc_html($sub['plan']->interval);
                }
?>

            </div>
        </td>
    <?php else : ?>
        <td class="item_cost">
            <div class="view"><?php echo wp_kses_post(wp_specialchars_decode($order->sub_payment, 'ENT_QUOTES')); ?></div>
        </td>
    <?php endif; ?>
    <td class="item_cost">
        <div class="view">
            <span class="ppcart-Price-amount amount">
                <?php ppcart_formatted_price($line_total); ?>
            </span>
        </div>
    </td>
</tr>

<?php
