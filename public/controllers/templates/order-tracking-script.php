<?php

if (! defined('ABSPATH')) {
    exit;
}



global $ppcart_product, $ppcart_currency;

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

$ppcart_meta_pixel_id = trim((string) get_option('_ppcart_fb_pixel_id'));
$ppcart_meta_paid_confirmation = ($ppcart_meta_pixel_id !== '' || get_option('_ppcart_fb_purchase'))
    && $ppcart_order && $ppcart_order->id
    && in_array(get_post_status($ppcart_order->id), ['paid', 'completed'], true)
    && PPCart_Order::visitor_can_view($ppcart_order->id);

$ppcart_order_tracking_buffer_level = ob_get_level();
$ppcart_order_tracking_buffer_active = true;
$ppcart_order_tracking_buffer_error = null;
ob_start(static function ($buffer, $phase) use (&$ppcart_order_tracking_buffer_active) {
    if ($phase & PHP_OUTPUT_HANDLER_FINAL) {
        $ppcart_order_tracking_buffer_active = false;
    }
    return $buffer;
});
try {
    ?>
jQuery('document').ready(function($){
    <?php if (ctype_digit($ppcart_meta_pixel_id)) : ?>
    if (typeof window.fbq === 'undefined' && !document.querySelector('script[src*="connect.facebook.net/"][src*="/fbevents.js"]')
        && (<?php echo $ppcart_meta_paid_confirmation ? 'true' : 'false'; ?> || $('form[id="ppcart-payment-form"] [name="ppcart_product_id"]').length)) {
        var pixel = function() {
            if (pixel.callMethod) {
                pixel.callMethod.apply(pixel, arguments);
            } else {
                pixel.queue.push(arguments);
            }
        };
        window.fbq = pixel;
        window._fbq = window._fbq || pixel;
        pixel.push = pixel;
        pixel.loaded = true;
        pixel.version = '2.0';
        pixel.queue = [];
        pixel('init', '<?php echo esc_js($ppcart_meta_pixel_id); ?>');
        pixel('track', 'PageView');

        var pixelScript = document.createElement('script');
        pixelScript.async = true;
        pixelScript.src = 'https://connect.facebook.net/en_US/fbevents.js';
        document.head.appendChild(pixelScript);
    }
    <?php endif; ?>

    if ( typeof fbq !== "undefined") {

        <?php if (get_option('_ppcart_fb_initiate_checkout')) : ?>
        function trackCheckout() {
            var form = $(this);
            var product = form.find('[name="ppcart_product_id"]').val();
            var price = form.find('[name="ppcart_product_option"]:checked').attr('data-price');
            if (!product || price === undefined || !Number.isFinite(Number(price)) || form.data('ppcartMetaCheckout')) {
                return;
            }

            fbq('track', 'InitiateCheckout', {
                content_ids: [product],
                content_type: 'product',
                currency: '<?php echo esc_js(strtoupper((string) $ppcart_currency)); ?>',
                value: Number(price)
            });
            form.data('ppcartMetaCheckout', true);
        }

        $(document).on('ppcart/orderform/ready', '#ppcart-payment-form', trackCheckout);
        $('form[id="ppcart-payment-form"]:visible').each(trackCheckout);
        <?php endif; ?>

        <?php if ($ppcart_meta_paid_confirmation) : ?>
        if (ppcart.fb_purchase_event === 'enabled') {
            var purchaseId = 'ppcart-meta-purchase-<?php echo esc_js((string) $ppcart_order->id); ?>';
            var sent = false;
            try {
                sent = window.localStorage.getItem(purchaseId) === '1';
            } catch (error) {
                // Storage may be unavailable in privacy mode.
            }

            if (!sent) {
                fbq('track', 'Purchase', {
                    currency: '<?php echo esc_js(strtoupper((string) $ppcart_order->currency)); ?>',
                    value: Number('<?php echo esc_js((string) $ppcart_order->amount); ?>')
                }, { eventID: purchaseId });
                try {
                    window.localStorage.setItem(purchaseId, '1');
                } catch (error) {
                    // Keep the event usable when storage is blocked.
                }
            }
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
} catch (Throwable $ppcart_order_tracking_buffer_exception) {
    $ppcart_order_tracking_buffer_error = $ppcart_order_tracking_buffer_exception;
} finally {
    $ppcart_order_tracking_buffer_output = '';
    // Flush nested buffers into ours; never close a caller's or replacement buffer.
    while ($ppcart_order_tracking_buffer_active && ob_get_level() > $ppcart_order_tracking_buffer_level + 1) {
        $ppcart_order_tracking_buffer_nested_level = ob_get_level();
        try {
            if (! ob_end_flush()) {
                break;
            }
        } catch (Throwable $ppcart_order_tracking_buffer_exception) {
            $ppcart_order_tracking_buffer_error = $ppcart_order_tracking_buffer_error ?? $ppcart_order_tracking_buffer_exception;
            if (ob_get_level() >= $ppcart_order_tracking_buffer_nested_level) {
                break;
            }
        }
    }
    if ($ppcart_order_tracking_buffer_active && ob_get_level() === $ppcart_order_tracking_buffer_level + 1) {
        $ppcart_order_tracking_buffer_output = (string) ob_get_clean();
    }
}
if (null !== $ppcart_order_tracking_buffer_error) {
    throw $ppcart_order_tracking_buffer_error;
}
$script = trim($ppcart_order_tracking_buffer_output);

if ('' !== $script) {
    wp_add_inline_script('ppcart', $script);
}
