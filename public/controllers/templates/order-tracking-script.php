<?php

if (! defined('ABSPATH')) {
    exit;
}



global $ppcart_product;

$oto_get         = ppcart_filter_input(INPUT_GET, 'ppcart-oto', FILTER_VALIDATE_INT);
$oto2_get        = ppcart_filter_input(INPUT_GET, 'ppcart-oto-2', FILTER_VALIDATE_INT);
$step_get        = filter_input(INPUT_GET, 'step', FILTER_VALIDATE_INT);
$ppcart_order_get    = filter_input(INPUT_GET, 'ppcart-order', FILTER_VALIDATE_INT);
$ppcart_order_id_post = ppcart_filter_input(INPUT_POST, 'ppcart_order_id', FILTER_VALIDATE_INT);

$oto             = (false !== $oto_get && null !== $oto_get) ? absint($oto_get) : 0;
$oto2            = (false !== $oto2_get && null !== $oto2_get) ? absint($oto2_get) : 0;
$step            = (false !== $step_get && null !== $step_get) ? absint($step_get) : 1;
$ppcart_order_get    = (false !== $ppcart_order_get && null !== $ppcart_order_get) ? absint($ppcart_order_get) : 0;
$ppcart_order_id_post = (false !== $ppcart_order_id_post && null !== $ppcart_order_id_post) ? absint($ppcart_order_id_post) : 0;

$ppcart_order = false;

// main order
if ($ppcart_order_id_post || ($ppcart_order_get && ! $oto)) {
    $order_id = $ppcart_order_id_post ? $ppcart_order_id_post : $ppcart_order_get;
    $ppcart_order = new PPCart_Order($order_id);
    // downsell
} elseif ($oto2) {
    $ppcart_order = new PPCart_Order($oto2);
} elseif (null !== $oto_get && false !== $oto_get && ! $oto && $step > 1) {
    $order_id = $ppcart_order_id_post ? $ppcart_order_id_post : $ppcart_order_get;
    $ppcart_order = new PPCart_Order($order_id);
    $downsell = $ppcart_order->get_downsell($step);
    if ($downsell) {
        $ppcart_order = new PPCart_Order($downsell['id']);
    }
    // upsell
} elseif ($oto) {
    $ppcart_order = new PPCart_Order($oto);
}

if ($ppcart_order && $ppcart_order->id) {
    do_action('ppcart_js_purchase_tracking', $ppcart_order);
}

ob_start();
?>
jQuery('document').ready(function($){
    if ( typeof fbq !== "undefined") {

        <?php if ($ppcart_order && $ppcart_order->id) : ?>
        if('undefined' !== typeof ppcart.fb_purchase_event && 'enabled' == ppcart.fb_purchase_event) {
            fbq('track', 'Purchase', {
                currency: '<?php echo esc_js($ppcart_order->currency); ?>',
                value: '<?php echo esc_js($ppcart_order->amount); ?>'
            });
        }
        <?php endif; ?>

        if( 'undefined' !== typeof ppcart.fb_add_payment_info && 'undefined' !== typeof ppcart.content_id &&
        'enabled' == ppcart.fb_add_payment_info ) {
            var tracked = false;
            if (!tracked) {
                $('#ppcart-payment-form input').focus(function(){
                    fbq('track', 'AddPaymentInfo', {
                        content_ids: [ppcart.content_id],
                        eventref: '' // or set to empty string
                    });
                });
                tracked = true;
            }
        }

        <?php if (is_object($ppcart_product) && get_option('_ppcart_fb_lead')) : ?>
        if( 'undefined' !== typeof ppcart.fb_lead_event && 'undefined' !== typeof ppcart.content_id &&
        'enabled' == ppcart.fb_lead_event ) {
            $('#ppcart-payment-form').on("ppcart/orderform/lead_captured", function() {
                fbq('track', 'Lead', {
                    content_ids: [ppcart.content_id],
                    currency: '<?php echo esc_js($ppcart_product->currency ?? ''); ?>',
                    value: '<?php echo esc_js($ppcart_product->price ?? ''); ?>'
                });
            });
        }
        <?php endif; ?>
    }

    <?php if (is_object($ppcart_product) && get_option('_ppcart_ga_lead')) : ?>
    $('#ppcart-payment-form').on("ppcart/orderform/lead_captured", function() {
        <?php $ga_type = get_option('_ppcart_ga_type'); ?>
        <?php if ('datalayer_ga4' === $ga_type) : ?>
        if ( typeof dataLayer !== "undefined") {
            dataLayer.push({ ecommerce: null });
            dataLayer.push({
                event: "generate_lead",
                ecommerce: {
                    currency: '<?php echo esc_js($ppcart_product->currency ?? ''); ?>',
                    value: '<?php echo esc_js($ppcart_product->price ?? ''); ?>',
                    items: [
                        {
                            item_id: '<?php echo esc_js($ppcart_product->ID ?? ''); ?>',
                            item_name: '<?php echo esc_js(get_the_title($ppcart_product->ID ?? 0)); ?>'
                        }
                    ]
                }
            });
        }
        <?php elseif ('datalayer_enhanced' === $ga_type) : ?>
        if ( typeof dataLayer !== "undefined") {
            dataLayer.push({
                event: "generate_lead",
                ecommerce: {
                    add: {
                        products: [
                            {
                                id: '<?php echo esc_js($ppcart_product->ID ?? ''); ?>',
                                name: '<?php echo esc_js(get_the_title($ppcart_product->ID ?? 0)); ?>',
                                price: '<?php echo esc_js($ppcart_product->price ?? ''); ?>',
                                quantity: 1
                            }
                        ]
                    }
                }
            });
        }
        <?php else : ?>
        if ( typeof gtag !== "undefined") {
            gtag('event', 'generate_lead', {
                currency: '<?php echo esc_js($ppcart_product->currency ?? ''); ?>',
                value: '<?php echo esc_js($ppcart_product->price ?? ''); ?>',
                items: [
                    {
                        item_id: '<?php echo esc_js($ppcart_product->ID ?? ''); ?>',
                        item_name: '<?php echo esc_js(get_the_title($ppcart_product->ID ?? 0)); ?>'
                    }
                ]
            });
        }
        <?php endif; ?>
    });
    <?php endif; ?>
});
<?php
$script = trim(ob_get_clean());

if ('' !== $script) {
    wp_add_inline_script('ppcart', $script);
}
