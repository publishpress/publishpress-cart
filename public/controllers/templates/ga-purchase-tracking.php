<?php

if (! defined('ABSPATH')) {
    exit;
}



if (get_option('_ppcart_ga_purchase')) {
    $order_data = $order->get_data();
    $order_data = (object) $order_data;
    $ga_type = get_option('_ppcart_ga_type');
    ob_start();
    ?>
    <?php if (!$ga_type) : ?>
        if ( typeof ga !== "undefined") {
            ga( 'send', 'event', 'ecommerce', 'purchase', '<?php echo esc_js(get_the_title($order_data->product_id)); ?>' );
            ga( 'require', 'ecommerce' );

            ga( 'ecommerce:addTransaction', {
              'id': '<?php echo esc_js($order_data->id); ?>', // Transaction ID. Required.
              'affiliation': '<?php echo esc_js(get_bloginfo('name')); ?>',              // Affiliation or store name.
              'revenue': '<?php echo esc_js((float) $order_data->amount); ?>',                      // Grand Total.
              'shipping': '0',                       // Shipping.
              'tax': '<?php echo esc_js((float) $order_data->tax_amount); ?>'                             // Tax.
            } );

            ga( 'ecommerce:addItem', {
              'id': '<?php echo esc_js($order_data->id); ?>',                // Transaction ID. Required.
              'name': '<?php echo esc_js(get_the_title($order_data->product_id)); ?>',                                  // Product name. Required.
              'sku': '<?php echo esc_js($order_data->product_id); ?>',          // SKU/code.
              'category': '<?php echo esc_js($order_data->item_name); ?>',  // Category or variation.
              'price': '<?php echo esc_js($order_data->main_offer_amt); ?>', // Unit price.
              'quantity': '1' // Quantity.
            } );

            <?php if (isset($order_data->custom_prices)) : ?>
                <?php foreach ($order_data->custom_prices as $custom_price_id => $price) : ?>
                ga( 'ecommerce:addItem', {
                  'id': '<?php echo esc_js($order_data->id); ?>',                // Transaction ID. Required.
                  'name': '<?php echo esc_js($price['label']); ?>',                                  // Product name. Required.
                  'sku': '<?php echo esc_js($custom_price_id); ?>',          // SKU/code.
                  'category': '',                                 // Category or variation.
                  'price': '<?php echo esc_js($price['price']); ?>', // Unit price.
                  'quantity': '<?php echo esc_js($price['qty']); ?>' // Quantity.
                } );
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!empty($order_data->order_bumps) && is_array($order_data->order_bumps)) : ?>
                <?php foreach ($order_data->order_bumps as $k => $order_bump) : ?>
                <?php $category = $order_bump['plan']->name ?? __('Order Bump', 'publishpress-cart'); ?>
                ga( 'ecommerce:addItem', {
                  'id': '<?php echo esc_js($order_data->id); ?>',                // Transaction ID. Required.
                  'name': '<?php echo esc_js($order_bump['name']); ?>',                                  // Product name. Required.
                  'sku': '<?php echo esc_js('bump_' . $k . '_' . $order_bump['id']); ?>',          // SKU/code.
                  'category': '<?php echo esc_js($category); ?>',                                 // Category or variation.
                  'price': '<?php echo esc_js($order_bump['amount']); ?>', // Unit price.
                  'quantity': '1' // Quantity.
                } );
                <?php endforeach; ?>
            <?php endif; ?>
        }
    <?php elseif (in_array($ga_type, ['universal', 'ga4'])) : ?>
        <?php
        //universal
        $id_label = 'id';
        $name_label = 'name';
        $variant_label = 'variant';

        // ga4
        if ($ga_type == 'ga4') {
            $id_label = 'item_' . $id_label;
            $name_label = 'item_' . $name_label;
            $variant_label = 'item_' . $variant_label;
        }
        ?>
        if ( typeof gtag !== "undefined") {
            gtag("event", "purchase", {
              "transaction_id": '<?php echo esc_js($order_data->id); ?>',                          // Transaction ID. Required.
              "affiliation": '<?php echo esc_js(get_bloginfo('name')); ?>',                   // Affiliation or store name.
              "value": '<?php echo esc_js((float) $order_data->amount); ?>',                       // Grand Total.
              "currency": '<?php echo esc_js($order_data->currency); ?>',
              "shipping": '0',                                                        // Shipping.
              "tax": '<?php echo esc_js((float) $order_data->tax_amount); ?>',                     // Tax.
              "items": [
                {
                  "<?php echo esc_js($id_label); ?>": '<?php echo esc_js($order_data->product_id); ?>',                     // Transaction ID. Required.
                  "<?php echo esc_js($name_label); ?>": '<?php echo esc_js(get_the_title($order_data->product_id)); ?>',    // Product name. Required.
                  "affiliation": '<?php echo esc_js(get_bloginfo('name')); ?>',               // Affiliation or store name
                  "currency": '<?php echo esc_js($order_data->currency); ?>',
                  "<?php echo esc_js($variant_label); ?>": '<?php echo esc_js($order_data->item_name); ?>',// Variation.
                  "price": '<?php echo esc_js($order_data->main_offer_amt); ?>',                   // Unit price.
                  "quantity": 1                                                       // Quantity.
                },
                <?php if (isset($order_data->custom_prices)) : ?>
                    <?php foreach ($order_data->custom_prices as $custom_price_id => $price) : ?>
                    {
                      "<?php echo esc_js($id_label); ?>": '<?php echo esc_js($custom_price_id); ?>',                 // Transaction ID. Required.
                      "<?php echo esc_js($name_label); ?>": '<?php echo esc_js($price['label']); ?>',   // Product name. Required.
                      "affiliation": '<?php echo esc_js(get_bloginfo('name')); ?>',             // Affiliation or store name
                      "currency": '<?php echo esc_js($order_data->currency); ?>',
                                                    "<?php echo esc_js($variant_label); ?>": '<?php echo esc_js(__('Custom add-on', 'publishpress-cart')); ?>',
                      "price": '<?php echo esc_js($price['price']); ?>', // Unit price.
                      "quantity": <?php echo intval($price['qty']); ?>, // Quantity.
                    },
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if (!empty($order_data->order_bumps) && is_array($order_data->order_bumps)) :?>
                    <?php foreach ($order_data->order_bumps as $k => $order_bump) : ?>
                    <?php $category = $order_bump['plan']->name ?? __('Order Bump', 'publishpress-cart'); ?>
                    {
                      "<?php echo esc_js($id_label); ?>": '<?php echo esc_js($order_bump['id']); ?>',   // Transaction ID. Required.
                      "<?php echo esc_js($name_label); ?>": '<?php echo esc_js($order_bump['name']); ?>',    // Product name. Required.
                      "affiliation": '<?php echo esc_js(get_bloginfo('name')); ?>',             // Affiliation or store name
                      "currency": '<?php echo esc_js($order_data->currency); ?>',
                      "<?php echo esc_js($variant_label); ?>": '<?php echo esc_js($category); ?>',
                      "price": '<?php echo esc_js($order_bump['amount']); ?>', // Unit price.
                      "quantity": 1 // Quantity.
                    },
                    <?php endforeach; ?>
                <?php endif; ?>
                ]
            });
        }
    <?php elseif ($ga_type == 'datalayer_ga4') : ?>
        if ( typeof dataLayer !== "undefined") {
            dataLayer.push({ ecommerce: null });  // Clear the previous ecommerce object.
            dataLayer.push({
                event: "purchase",
                ecommerce: {
                    "transaction_id": '<?php echo esc_js($order_data->id); ?>',                          // Transaction ID. Required.
                    "affiliation": '<?php echo esc_js(get_bloginfo('name')); ?>',                   // Affiliation or store name.
                    "value": '<?php echo esc_js((float) $order_data->amount); ?>',                       // Grand Total.
                    "currency": '<?php echo esc_js($order_data->currency); ?>',
                    "shipping": '0',                                                        // Shipping.
                    "tax": '<?php echo esc_js((float) $order_data->tax_amount); ?>',                     // Tax.
                    "items": [
                    {
                      "item_id": '<?php echo esc_js($order_data->product_id); ?>',                     // Transaction ID. Required.
                      "item_name": '<?php echo esc_js(get_the_title($order_data->product_id)); ?>',    // Product name. Required.
                      "affiliation": '<?php echo esc_js(get_bloginfo('name')); ?>',               // Affiliation or store name
                      "currency": '<?php echo esc_js($order_data->currency); ?>',
                      "item_variant": '<?php echo esc_js($order_data->item_name); ?>',// Variation.
                      "price": '<?php echo esc_js($order_data->main_offer_amt); ?>',                   // Unit price.
                      "quantity": 1                                                       // Quantity.
                    },
                    <?php if (isset($order_data->custom_prices)) : ?>
                        <?php foreach ($order_data->custom_prices as $custom_price_id => $price) : ?>
                        {
                          "item_id": '<?php echo esc_js($custom_price_id); ?>',                 // Transaction ID. Required.
                          "item_name": '<?php echo esc_js($price['label']); ?>',   // Product name. Required.
                          "affiliation": '<?php echo esc_js(get_bloginfo('name')); ?>',             // Affiliation or store name
                          "currency": '<?php echo esc_js($order_data->currency); ?>',
                                                            "item_variant": '<?php echo esc_js(__('Custom add-on', 'publishpress-cart')); ?>',
                          "price": '<?php echo esc_js($price['price']); ?>', // Unit price.
                          "quantity": <?php echo intval($price['qty']); ?>, // Quantity.
                        },
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if (!empty($order_data->order_bumps) && is_array($order_data->order_bumps)) :?>
                        <?php foreach ($order_data->order_bumps as $k => $order_bump) : ?>
                        <?php $category = $order_bump['plan']->name ?? __('Order Bump', 'publishpress-cart'); ?>
                        {
                          "item_id": '<?php echo esc_js($order_bump['id']); ?>',   // Transaction ID. Required.
                          "item_name": '<?php echo esc_js($order_bump['name']); ?>',    // Product name. Required.
                          "affiliation": '<?php echo esc_js(get_bloginfo('name')); ?>',             // Affiliation or store name
                          "currency": '<?php echo esc_js($order_data->currency); ?>',
                          "item_variant": '<?php echo esc_js($category); ?>',
                          "price": '<?php echo esc_js($order_bump['amount']); ?>', // Unit price.
                          "quantity": 1 // Quantity.
                        },
                        <?php endforeach; ?>
                    <?php endif; ?>
                    ]
                }
            });
        }
    <?php elseif ($ga_type == 'datalayer_enhanced') : ?>
        dataLayer.push({ ecommerce: null });  // Clear the previous ecommerce object.
        dataLayer.push({
          'ecommerce': {
            'purchase': {
              'actionField': {
                'id': '<?php echo esc_js($order_data->id); ?>',                      // Transaction ID. Required for purchases and refunds.
                'affiliation': '<?php echo esc_js(get_bloginfo('name')); ?>',
                'revenue': '<?php echo esc_js((float) $order_data->amount); ?>',     // Total transaction value (incl. tax and shipping)
                'tax':'<?php echo esc_js((float) $order_data->tax_amount); ?>',
                'shipping': '0',
                'coupon': ''
              },
              'products': [
                  {                                                    // List of productFieldObjects.
                    'name': '<?php echo esc_js(get_the_title($order_data->product_id)); ?>',     // Name or ID is required.
                    'id': '<?php echo esc_js($order_data->product_id); ?>',
                    'price': '<?php echo esc_js($order_data->main_offer_amt); ?>',
                    'category': '<?php echo esc_js($order_data->item_name); ?>',
                    'variant': '<?php echo esc_js($order_data->item_name); ?>',
                    'quantity': 1,
                    'coupon': ''                                            // Optional fields may be omitted or set to empty string.
                },

                <?php if (isset($order_data->custom_prices)) : ?>
                    <?php foreach ($order_data->custom_prices as $custom_price_id => $price) : ?>
                    {
                        'name': '<?php echo esc_js($price['label']); ?>',
                        'id': '<?php echo esc_js($custom_price_id); ?>',
                        'price': '<?php echo esc_js($price['price']); ?>',
                        'category': '<?php echo esc_js(__('Custom add-on', 'publishpress-cart')); ?>',
                        'variant': '<?php echo esc_js(__('Custom add-on', 'publishpress-cart')); ?>',
                        'quantity': '<?php echo intval($price['qty']); ?>'
                    },
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if (!empty($order_data->order_bumps) && is_array($order_data->order_bumps)) :?>
                    <?php foreach ($order_data->order_bumps as $k => $order_bump) : ?>
                    <?php $category = $order_bump['plan']->name ?? __('Order Bump', 'publishpress-cart'); ?>
                    {

                        'name': '<?php echo esc_js($order_bump['name']); ?>',
                        'id': '<?php echo esc_js($order_bump['id']); ?>',
                        'price': '<?php echo esc_js($order_bump['amount']); ?>',
                        'category': '<?php echo esc_js($category); ?>',
                        'variant': '<?php echo esc_js($category); ?>',
                        'quantity': 1
                    },
                    <?php endforeach; ?>
                <?php endif; ?>
               ]
            }
          }
        });
    <?php endif; ?>
<?php
    $script = trim(ob_get_clean());

    if ('' !== $script) {
        wp_add_inline_script('ppcart', $script);
    }
}
