<?php
if (! defined('ABSPATH')) {
    exit;
}

add_action('ppcart_payment_method_fields', 'ppcart_do_payment_methods', 10);
function ppcart_payment_method_label_allowed_html()
{
    $allowed_html = wp_kses_allowed_html('post');
    $allowed_html['svg'] = [
        'aria-hidden' => true,
        'class' => true,
        'data-icon' => true,
        'data-prefix' => true,
        'fill' => true,
        'focusable' => true,
        'height' => true,
        'role' => true,
        'style' => true,
        'viewbox' => true,
        'width' => true,
        'xmlns' => true,
    ];
    $allowed_html['path'] = [
        'd' => true,
        'fill' => true,
    ];
    $allowed_html['img'] = [
        'alt' => true,
        'class' => true,
        'decoding' => true,
        'height' => true,
        'loading' => true,
        'referrerpolicy' => true,
        'src' => true,
        'style' => true,
        'width' => true,
    ];

    return $allowed_html;
}

function ppcart_payment_method_label_html($label)
{
    return wp_kses($label, ppcart_payment_method_label_allowed_html());
}

function ppcart_do_payment_methods($post_id)
{

    global $ppcart_stripe;

    $payment_methods = [];
    $stripe_icon = '<img src="' . esc_url(PPCART_BASE_URL . 'public/images/cc/stripe-logo.png') . '" alt="" aria-hidden="true" loading="lazy" decoding="async" width="16" height="16" style="width:16px;height:16px;object-fit:contain;margin:0 6px -3px 0;">';

    // Stripe
    if (apply_filters('ppcart_checkout_payment_method_enabled', true, 'stripe', $post_id)) {
        if ($option_val = get_option('_ppcart_stripe_enable') == '1') {
            if (is_array($ppcart_stripe)) {
                $payment_methods['stripe'] = [
                    'value' => 'stripe',
                    'label' => $stripe_icon . ' ' . esc_html__('Credit Card', 'publishpress-cart'),
                    'single_label' => false,
                ];
            }
        }
    }

    // COD
    if (apply_filters('ppcart_checkout_payment_method_enabled', true, 'cashondelivery', $post_id)) {
        if ($option_val = get_option('_ppcart_cashondelivery_enable') == '1') {
            $payment_methods['cashondelivery'] = [
                'value' => 'cod',
                'label' => esc_html__('Cash on Delivery', 'publishpress-cart'),
            ];
        }
    }

    $i = 0;

    $payment_methods = apply_filters('ppcart_payment_methods', $payment_methods, $post_id);

    ?>

    <h3 class="title"><?php echo esc_html(ppcart_checkout_text_setting('paymentInfoHeading', esc_html__("Payment Info", "publishpress-cart"))); ?></h3>
    <div class="pay-methods">
        <?php if (!empty($payment_methods)) : ?>
            <?php foreach ($payment_methods as $k => $method) : ?>
                <?php $method_testid = 'ppcart-payment-method-' . ppcart_checkout_testid_suffix($method['value']); ?>
                <label data-testid="<?php echo esc_attr(ppcart_data_testid_attribute($method_testid)); ?>">
                <?php if (count($payment_methods) > 1) :
                    echo '<input id="method-' . esc_attr($method['value']) . '" type="radio" name="pay-method" value="' . esc_attr($method['value']) . '" ' . checked(0, $i, false) . ' data-testid="' . esc_attr(ppcart_data_testid_attribute($method_testid . '-input')) . '">';
                    echo '<span class="item-name" data-testid="' . esc_attr(ppcart_data_testid_attribute($method_testid . '-label')) . '">' . wp_kses($method['label'], ppcart_payment_method_label_allowed_html()) . '</span>'; ?>
                <?php elseif (count($payment_methods) == 1) : ?>
                    <input id="method-<?php echo esc_attr($method['value']); ?>" checked="checked" type="radio" name="pay-method" value="<?php echo esc_attr($method['value']); ?>" data-testid="<?php echo esc_attr(ppcart_data_testid_attribute($method_testid . '-input')); ?>">
                    <?php
                        $label = (isset($method['single_label']) && $method['single_label'] !== false) ? $method['single_label'] : $method['label'];
                    echo '<span class="item-name" data-testid="' . esc_attr(ppcart_data_testid_attribute($method_testid . '-label')) . '">' . wp_kses($label, ppcart_payment_method_label_allowed_html()) . '</span>';
                    ?>
                <?php endif;
                $i++; ?>
                </label>
            <?php endforeach; ?>
        <?php else : ?>
            <?php esc_html_e('Sorry, it seems that there are no payment methods available. Please contact us for assistance.', 'publishpress-cart'); ?>
        <?php endif; ?>
    </div>
    <?php
}

function ppcart_do_payment_method_section($post_id)
{
    global $ppcart_product;

    if (isset($ppcart_product->show_optin)) {
        return;
    }

    echo '<div class="ppcart-section pay-info ppcart-payment-method-section">';
    do_action('ppcart_payment_method_fields', $post_id);
    echo '</div>';
}

add_action('ppcart_express_payment_method_fields', 'ppcart_express_payment_method', 10);
function ppcart_express_payment_method($post_id)
{

    global $ppcart_stripe;

    if ($ppcart_stripe && isset($ppcart_stripe['is_express_payment']) &&  $ppcart_stripe['is_express_payment'] == 1) : ?>
        <div class="ppcart-stripe-express">
            <div id="express-pay-element" class="express-pay-element">
            <!-- A Stripe Google pay Element will be inserted here. -->
            </div>
        </div>
    <?php endif;
}


function ppcart_do_vat_info()
{
    ?>
    <div class="vat_container" style='display:none;'>
        <div class="ppcart-row">
            <div class="ppcart-form-group ppcart-col-sm-12">
                <input id="method-vat-number-available" type="checkbox" name="vat-number-available" value="Yes" data-testid="<?php echo esc_attr(ppcart_data_testid_attribute('ppcart-checkout-vat-toggle')); ?>">
                <label for="method-vat-number-available"><?php esc_html_e('Enter VAT number', "publishpress-cart"); ?></label>
            </div>
            <div class="ppcart-form-group ppcart-col-sm-12 vat_number_field" style="display:none;">
                <label for="vat_number"><?php esc_html_e('VAT Number', "publishpress-cart"); ?><span class="req">*</span></label>
                <input id="vat_number" type="text" name="vat-number" placeholder="<?php esc_attr_e('VAT Number', "publishpress-cart"); ?>" class="ppcart-form-control required" aria-label='VAT Number' data-testid="<?php echo esc_attr(ppcart_data_testid_attribute('ppcart-checkout-vat-number')); ?>">
            </div>
        </div>
    </div>
<?php
}

function ppcart_do_card_details_fields($post_id, $hide_labels)
{
    global $ppcart_product;

    echo '<div class="ppcart-section pay-info">';
    ppcart_do_checkout_hidden_fields($post_id);

    if (!isset($ppcart_product->show_optin)) {
        do_action('ppcart_payment_method_fields', $post_id);
        ppcart_do_payment_details_content($post_id, $hide_labels);
    }

    echo '</div>';
}

function ppcart_do_checkout_hidden_fields($post_id)
{
    global $ppcart_product;

    if (!defined('DONOTCACHEPAGE')) {
        define('DONOTCACHEPAGE', true);
    }
    $on_sale = ppcart_is_prod_on_sale();
    $name = (!$on_sale) ? 'name' : 'sale_name';
    $price = (!$on_sale) ? 'price' : 'sale_price';
    $nonce = wp_create_nonce("ppcart_purchase_nonce");
    $product_name = get_the_title($post_id);
    $allowed_codes = ppcart_currency_countries_code_list();
    $currency_code =  in_array(get_option('_ppcart_currency'), $allowed_codes['currencies']) ? get_option('_ppcart_currency') : '';
    $country_code = in_array(get_option('_ppcart_country'), $allowed_codes['countries']) ? get_option('_ppcart_country') : '';
    ?>

    <input type="hidden" name="ppcart_currency_code" id="ppcart_currency_code" value="<?php echo esc_attr($currency_code); ?>">
    <input type="hidden" name="ppcart_currency_country_code" id="ppcart_currency_country_code" value="<?php echo esc_attr($country_code); ?>">
    <input type="hidden" name="<?php echo esc_attr($name); ?>" id="item_name">
    <input type="hidden" name="<?php echo esc_attr($price); ?>" id="item_price">
    <input type="hidden" name="ppcart_process_payment" value="1">
    <input type="hidden" name="ppcart_amount" value="">
    <input type="hidden" name="ppcart_product_id" id="ppcart_product_id" value="<?php echo esc_attr($post_id); ?>">
    <input type="hidden" name="ppcart-nonce" value="<?php echo esc_attr($nonce); ?>">
    <input type="hidden" name="ppcart_amount_due_label" value="<?php echo esc_attr(ppcart_checkout_text_setting('amountDueLabel', esc_html__("Amount Due", "publishpress-cart"))); ?>">
    <input type="hidden" name="ppcart_due_today_label" value="<?php echo esc_attr(ppcart_checkout_text_setting('dueTodayLabel', esc_html__("Due Today", "publishpress-cart"))); ?>">
    <input type="hidden" name="action" value="ppcart_save_order_to_db">
    <?php echo ($on_sale) ? '<input type="hidden" name="on-sale" value="1">' : ''; ?>
    <?php // checkout source metadata?>
    <input type="hidden" name="ppcart_page_id" value="<?php echo esc_attr(get_the_ID()); ?>">
    <input type="hidden" name="ppcart_page_url" value="<?php echo esc_attr(esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'] ?? ''))); ?>">
    <input type="hidden" name="ppcart_product_name" value="<?php echo esc_attr($product_name); ?>">
    <?php
}

function ppcart_do_payment_details_section($post_id, $hide_labels)
{
    global $ppcart_product;

    if (isset($ppcart_product->show_optin)) {
        return;
    }

    echo '<div class="ppcart-section pay-info ppcart-payment-details-section">';
    ppcart_do_payment_details_content($post_id, $hide_labels);
    echo '</div>';
}

function ppcart_do_payment_details_content($post_id, $hide_labels)
{
    global $ppcart_stripe, $ppcart_product;
    do_action('ppcart_before_payment_info', $post_id);

    // Hosted Checkout collects card details on Stripe's page; hide the inline card field.
    if ($ppcart_stripe && empty($ppcart_stripe['is_hosted_checkout'])) : ?>
    <div class="ppcart-row ppcart-stripe">
      <div class="ppcart-form-group ppcart-col-sm-12">
        <?php if ($hide_labels != 'hide') : ?>
        <label for="card-element">
           <?php esc_html_e("Credit or debit card", "publishpress-cart"); ?> <span class="req">*</span>
        </label>
        <?php endif; ?>

        <div id="card-element" data-form-wrapper="<?php echo esc_attr($ppcart_product->ID); ?>" aria-label="<?php esc_attr_e("Credit or debit card", "publishpress-cart"); ?>">
          <!-- A Stripe Element will be inserted here. -->
        </div>
        <!-- Used to display Element errors. -->
        <div id="card-errors" role="alert"></div>

      </div>
    </div>
    <?php endif; ?>

    <?php do_action('ppcart_after_payment_info', $post_id, $hide_labels);
}

/**
 * Render checkout billing/shipping address fields for a product.
 *
 * @param int  $post_id     Product post ID.
 * @param bool $hide_labels Whether field labels are hidden.
 * @return void
 */
function ppcart_address_fields($post_id, $hide_labels = false)
{
    global $ppcart_product;
    ?>
    <div class="address-info">
        <hr>
        <?php if (isset($ppcart_product->address_title)) : ?>
        <h3 class="title"><?php echo esc_html($ppcart_product->address_title); ?></h3>
        <?php endif; ?>
        <div class="ppcart-row">
            <?php

            $defaults = [
                'address_1' => '',
                'address_2' => '',
                'city'      => '',
                'state'     => '',
                'zip'       => '',
                'country'   => '',
            ];

    $state_choices = ['' => esc_html__('State / County', 'publishpress-cart')];

    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        if ($address = ppcart_get_user_address($current_user->ID)) {
            $defaults = $address;
        }
        if (isset($defaults['state'])) {
            $state_choices[$defaults['state']] = $defaults['state'];
        }
    }

    if (!$defaults['country']) {
        $defaults['country'] = get_option('_ppcart_country', 'US');
    }

    $fields = [
        'country' => [
            'name' => 'country',
            'label' => esc_html__('Country', 'publishpress-cart'),
            'type' => 'select',
            'choices' => ppcart_countries_list(),
            'value' => $defaults['country'],
            'required' => true,
            'hide_labels' => $hide_labels,
            'cols' => 12,
        ],
        'address1' => [
            'name' => 'address1',
            'label' => esc_html__('Address', 'publishpress-cart'),
            'required' => true,
            'hide_labels' => $hide_labels,
            'value' => $defaults['address_1'],
            'cols' => 12,
            'div_class' => 'ppcart-address-1',
        ],
        'address2' => [
            'name' => 'address2',
            'label' => esc_html__('Address Line 2', 'publishpress-cart'),
            'hide_labels' => true,
            'value' => $defaults['address_2'],
            'cols' => 12,
            'div_class' => 'ppcart-address-2',
        ],
        'city' => [
            'name' => 'city',
            'label' => esc_html__('Town / City', 'publishpress-cart'),
            'required' => true,
            'value' => $defaults['city'],
            'hide_labels' => $hide_labels,
            'cols' => 12,
        ],

        'state' => [
            'name' => 'state',
            'label' => esc_html__('State / County', 'publishpress-cart'),
            'type' => 'select',
            'choices' => $state_choices,
            'value' => $defaults['state'],
            'required' => true,
            'hide_labels' => $hide_labels,
        ],
        'zip' => [
            'name' => 'zip',
            'label' => esc_html__('Postcode / Zip', 'publishpress-cart'),
            'value' => $defaults['zip'],
            'required' => true,
            'hide_labels' => $hide_labels,
        ],
    ];

    if (!isset($ppcart_product->default_fields)) {
        foreach ($fields as $k => $field) { // deprecated
            if (isset($ppcart_product->hide_fields) && isset($ppcart_product->hide_fields[$k])) {
                unset($fields[$k]);
            }
        }
    }

    $fields = apply_filters('ppcart_order_form_address_fields', $fields, $ppcart_product);

    foreach ($fields as $k => $field) {
        ppcart_do_field($field);
    }
    ?>
        </div>
    </div>
    <?php
    $vat_enable = get_option('_ppcart_vat_enable', false);
    if ($vat_enable) {
        ppcart_do_vat_info();
    }
}

function ppcart_personalize_product_info($ppcart_product, $order_info)
{

    $filter_fields = [
        ['field' => 'us_alert', 'callback' => 'esc_html'],
        ['field' => 'us_headline', 'callback' => 'esc_html'],
        ['field' => 'us_description', 'callback' => 'esc_html'],
        ['field' => 'us_proceed', 'callback' => 'esc_html'],
        ['field' => 'us_decline', 'callback' => 'esc_html'],
        ['field' => 'thanks_url', 'callback' => 'urlencode'],
    ];

    foreach ($filter_fields as $filter) {
        $field = $filter['field'];
        $ppcart_product->$field = ppcart_personalize($ppcart_product->$field, $order_info, $filter['callback']);
    }

    return $ppcart_product;
}
