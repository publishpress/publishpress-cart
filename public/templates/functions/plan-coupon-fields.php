<?php
if (! defined('ABSPATH')) {
    exit;
}

function ppcart_order_summary_shortcode()
{
    global $ppcart_product;
    $prod_id = $ppcart_product->ID;

    ob_start();
    ppcart_render_order_summary_items($prod_id);
    return ob_get_clean();
}

function ppcart_render_site_info()
{
    $site_heading = ppcart_checkout_text_setting('splitSiteHeading', get_bloginfo('name'));
    $has_site_icon = has_site_icon();

    if ('' === $site_heading && !$has_site_icon) {
        return '';
    }

    ob_start();
    ?>
    <div class="image-box-wrapper">
        <?php if ($has_site_icon) : ?>
        <figure class="image-box-img">
            <a href="<?php echo esc_url(home_url()); ?>" data-testid="<?php echo esc_attr(ppcart_data_testid_attribute('ppcart-checkout-site-icon-link')); ?>">
                <?php echo '<img src="' . esc_url(get_site_icon_url(120)) . '" alt="' . esc_attr(get_bloginfo('name')) . '" width="120" height="120">'; ?>
            </a>
        </figure>
        <?php endif; ?>
        <?php if ('' !== $site_heading) : ?>
         <div class="image-box-content">
            <h3 class="image-box-title">
                <a href="<?php echo esc_url(home_url()); ?>" data-testid="<?php echo esc_attr(ppcart_data_testid_attribute('ppcart-checkout-site-title-link')); ?>">
                    <?php echo esc_html($site_heading); ?>
                </a>
            </h3>
        </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

function ppcart_order_summary_info($post_id, $plan = false)
{
    global $ppcart_product;
    global $ppcart_uid;

    $ppcart_uid = wp_unique_id('ppcart_');

    echo '<div id="ppcart-order-summary-form-' . esc_attr($ppcart_product->ID) . '-' . esc_attr($ppcart_uid) . '" class="order-summary-form-wrap">';
    echo wp_kses_post(ppcart_render_site_info());
    echo '<div class="checkout-order-summary">';
    $content = get_post_field('post_content', $post_id);
    $post_content = apply_filters('the_content', $content);
    $class = '';
    $skip_default = function_exists('has_shortcode') && has_shortcode($content, 'ppcart_order_summary_items_view');
    $skip_default = (bool) apply_filters('ppcart_skip_default_order_summary', $skip_default, $content);
    if (!$skip_default) {
        ppcart_render_order_summary_items($post_id, $plan);
        $class = 'ppcart-content';
    }
    if ($post_content) {
        echo '<div class="' . esc_attr($class) . '">' . wp_kses_post($post_content) . '</div>';
    }
    echo '</div>';
    echo '</div>';
}

/**
 * Render checkout payment-plan radio options and optional coupon field.
 *
 * @param int          $post_id        Product post ID.
 * @param bool         $hide_labels    Whether field labels are hidden.
 * @param object|false $plan           Selected plan, or false.
 * @param bool         $include_coupon Whether to render the coupon field.
 * @return void
 */
function ppcart_payment_plan_options($post_id, $hide_labels, $plan = false, $include_coupon = true)
{
    global $ppcart_product;
    $hide_class = (isset($ppcart_product->hide_plans)) ? 'hidden' : '';

    if (has_action('ppcart_coupon_fields') && isset($ppcart_product->show_coupon_field)) {
        $hide_class .= ' ppcart-show-coupon';
    }

    do_action('ppcart_orderform_before_payment_plans', $post_id);
    ?>

    <div class="ppcart-section products <?php echo esc_attr($hide_class); ?>" data-testid="<?php echo esc_attr(ppcart_data_testid_attribute('ppcart-payment-plans')); ?>">

        <?php
        $on_sale = ppcart_is_prod_on_sale();

    $name = (!$on_sale) ? 'option_name' : 'sale_option_name';
    $price = (!$on_sale) ? 'price' : 'sale_price';
    $items = isset($ppcart_product->pay_options) && is_array($ppcart_product->pay_options)
        ? $ppcart_product->pay_options
        : [];
    $items = apply_filters('ppcart_checkout_form_pay_options', $items, $ppcart_product);
    if (! is_array($items)) {
        $items = [];
    }
    $installments = (!$on_sale) ? 'installments' : 'sale_installments';
    $interval = (!$on_sale) ? 'interval' : 'sale_interval';
    $fee = (!$on_sale) ? 'sign_up_fee' : 'sale_sign_up_fee';
    $tax_data = [];
    $i = 0;

    if (!$plan && get_query_var('ppcart-pay-plan')) {
        $plan = sanitize_text_field(get_query_var('ppcart-pay-plan'));
    }

    $selected_plan_id = '';
    if ($plan) {
        $selected_plan = ppcart_plan($plan, $on_sale, $post_id);
        if ($selected_plan && !empty($selected_plan->option_id)) {
            $selected_plan_id = (string) $selected_plan->option_id;
        }
    }

    $plan_heading = (isset($ppcart_product->plan_heading) && $ppcart_product->plan_heading) ? $ppcart_product->plan_heading : esc_html__("Payment Plan", "publishpress-cart");
    $plan_heading = apply_filters('ppcart_plan_heading', $plan_heading, $ppcart_product->ID);
    $plan_heading = ppcart_checkout_text_setting('paymentPlanHeading', $plan_heading);
    ?>

        <h3 class="title"><?php echo esc_html($plan_heading); ?></h3>

        <?php if ($include_coupon) {
            do_action('ppcart_coupon_fields', $post_id);
        } ?>

        <?php foreach ($items as $item) :
            $item['product_type'] ??= false;
            $item[$price] ??= 0;

            if (isset($item['is_hidden'])) {
                continue;
            }

            if ($item['product_type'] == 'free') {
                $item[$price] = 0;
            } elseif (isset($ppcart_product->show_optin)) {
                continue;
            }
            if ($item['product_type'] != 'recurring') {
                unset($item[$fee], $item['trial_days']);
            }

            $checked = '';
            if ($selected_plan_id) {
                $checked = (isset($item['option_id']) && (string) $item['option_id'] === $selected_plan_id) ? 'checked' : '';
            } elseif ($plan) {
                $checked = (isset($item['url_slug']) && $item['url_slug'] == $plan) ? 'checked' : '';
            } elseif ($i == 0) {
                $checked = 'checked';
            }

            $int = $item[$interval];
            if ($item['frequency'] > 1) {
                $int = ppcart_pluralize_interval($int);
            }

            $checked = apply_filters('ppcart_checkout_form_price_checked', $checked, $item);
            $plan_index = $i + 1;
            $plan_testid = 'ppcart-payment-plan-' . $plan_index;
            ?>

<div class="item <?php if ($item['product_type'] == 'pwyw' || ($item['product_type'] == 'recurring' && isset($item['recurring_pwyw']) && $item['recurring_pwyw'] == '1' && isset($item['name_your_own_price_text_recurring']))) {
    echo 'flex-wrap';
                 } ?>" data-testid="<?php echo esc_attr(ppcart_data_testid_attribute($plan_testid)); ?>">
                <label data-testid="<?php echo esc_attr(ppcart_data_testid_attribute($plan_testid . '-label')); ?>">
                    <input id="option-<?php echo esc_attr($item['option_id']); ?>" <?php echo esc_attr($checked); ?> type="radio" name="ppcart_product_option" data-val="<?php echo esc_attr($item['product_type']); ?>" data-price="<?php echo esc_attr(floatval($item[$price])); ?>"
                    <?php if ($item['product_type'] == 'recurring') : ?>
                    data-installments="<?php echo esc_attr($item[$installments]); ?>" data-interval="<?php echo esc_attr($int); ?>"
                        <?php if (isset($item['frequency'])) : ?>
                        data-frequency="<?php echo esc_attr($item['frequency']); ?>"
                        <?php endif; ?>
                        <?php if (isset($item['trial_days'])) : ?>
                        data-trial-days="<?php echo esc_attr($item['trial_days']); ?>"
                        <?php endif; ?>
                        <?php if (isset($item[$fee])) : ?>
                        data-signup-fee="<?php echo esc_attr($item[$fee]); ?>"
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if ($ppcart_product->product_taxable) : ?>
                        data-taxable="yes"
                        data-tax-type="<?php echo esc_attr($ppcart_product->tax_type); ?>"
                        data-tax-price-format="<?php echo esc_attr($ppcart_product->price_show_with_tax); ?>"
                    <?php else : ?>
                        data-taxable="no"
                    <?php endif; ?>
                    value="<?php echo esc_attr($item['option_id']); ?>" data-testid="<?php echo esc_attr(ppcart_data_testid_attribute($plan_testid . '-input')); ?>">
                    <span class="item-name" data-testid="<?php echo esc_attr(ppcart_data_testid_attribute($plan_testid . '-name')); ?>">
                        <?php echo esc_html(($item[$name]) ?? $item['option_name'] ?? ''); ?>
                        <?php if ($item['product_type'] == 'pwyw' || ($item['product_type'] == 'recurring' && isset($item['recurring_pwyw']) && $item['recurring_pwyw'] == '1' && isset($item['name_your_own_price_text_recurring']))) : ?>
                            <span class="pwyw-suggested" data-testid="<?php echo esc_attr(ppcart_data_testid_attribute($plan_testid . '-suggested-text')); ?>">
                                <?php echo $item['product_type'] == 'pwyw' ? esc_html($item['name_your_own_price_text']) : esc_html($item['name_your_own_price_text_recurring']); ?>
                            </span>
                        <?php endif; ?>
                    </span>
                </label>

                <?php if (!isset($ppcart_product->hide_plan_price) && !($item['product_type'] == 'pwyw' || ($item['product_type'] == 'recurring' && isset($item['recurring_pwyw']) && $item['recurring_pwyw'] == '1' && isset($item['name_your_own_price_text_recurring'])))) : ?>
                <span class="price" data-testid="<?php echo esc_attr(ppcart_data_testid_attribute($plan_testid . '-price')); ?>">
                    <?php
   if ($on_sale && isset($ppcart_product->show_full_price) && $item['price'] > 0) {
       echo '<s data-testid="' . esc_attr(ppcart_data_testid_attribute($plan_testid . '-original-price')) . '">' . wp_kses_post(ppcart_format_price($item['price'])) . '</s> ';
   }
                    if ($item['product_type'] != 'free') {
                        ppcart_formatted_price($item[$price]);
                    } elseif (!isset($ppcart_product->show_optin)) {
                        echo '<span class="price">' . esc_html__("Free", "publishpress-cart") . '</span>';
                    } ?>
                </span>
                <?php endif; ?>

                <?php if ($item['product_type'] == 'pwyw' || ($item['product_type'] == 'recurring' && isset($item['recurring_pwyw']) && $item['recurring_pwyw'] == '1' && isset($item['name_your_own_price_text_recurring']))) : ?>
                    <div class="w-100 my-4 pwyw-input" id="pwyw-input-block-<?php echo esc_attr($item['option_id']); ?>" style="display: none;">
                        <?php
                        $pwyw_class = 'ppcart-form-group ppcart-mb-1';
                    $right_currency = (in_array(get_option('_ppcart_currency_position'), ['right', 'right-space'])) ? true : false;
                    if ($right_currency) {
                        $class .= ' right-currency';
                    }
                    ?>
                        <div class="<?php echo esc_attr($pwyw_class); ?>">
                            <span class="ppcart-currency"><?php echo esc_html(ppcart_get_currency_symbol()); ?></span>
                            <input id="pwyw-amount-input-<?php echo esc_attr($item['option_id']); ?>" name="pwyw_amount[<?php echo esc_attr($item['option_id']); ?>]" type="number" min="<?php echo esc_attr(floatval($item[$price])); ?>" class="ppcart-form-control ppcart-mb-0 required" placeholder="Amount" data-testid="<?php echo esc_attr(ppcart_data_testid_attribute('ppcart-custom-price-input')); ?>">
                        </div>
                    </div>
                <?php endif;?>
            </div>
        <?php $i++;
        endforeach; ?>

        <?php if ($include_coupon) {
            do_action('ppcart_coupon_status', $post_id);
        } ?>

    </div>
    <?php
}

function ppcart_do_coupon_section($post_id)
{
    global $ppcart_product;

    if (!isset($ppcart_product->show_coupon_field)) {
        return;
    }

    ob_start();
    do_action('ppcart_coupon_fields', $post_id);
    do_action('ppcart_coupon_status', $post_id);
    $coupon = trim(ob_get_clean());

    if ('' === $coupon) {
        return;
    }

    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Internal checkout templates render sanitized form controls.
    echo '<div class="ppcart-section ppcart-coupon-section">' . $coupon . '</div>';
}

function ppcart_do_checkout_form_close()
{
    echo '</form></div>';
}

/**
 * Render a single checkout form field from a field-args array.
 *
 * @param array $args Field arguments (id, type, required, hide_labels, cols, …).
 * @return void
 */
function ppcart_do_field($args)
{

    $defaults =  [
        'id' => false,
        'required' => false,
        'type' => 'text',
        'hide_labels' => 'false',
        'cols' => 6,
        'description' => '',
        'class' => '',
        'value' => '',
        'div_class' => '',
        'qty_price' => false,
        'testid' => '',
    ];

    $args = wp_parse_args($args, $defaults);
    extract($args);
    $posted_ppcart_errors = ppcart_filter_input(INPUT_POST, 'ppcart_errors', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY);

    if (!$id) {
        $id = $name;
    }

    $testid = $testid ? (string) $testid : 'ppcart-checkout-field-' . ppcart_checkout_testid_suffix($name);
    $group_testid = 'ppcart-checkout-field-group-' . ppcart_checkout_testid_suffix($name);

    if ($description) {
        $description = '<div class="ppcart-field-description">' . $description . '</div>';
    }

    $class .= (!$required) ? '' : ' required';
    $class .= (!is_array($posted_ppcart_errors) || !isset($posted_ppcart_errors[$name])) ? '' : ' invalid';
    $class .= ($type == 'password') ? ' ppcart-password' : '';
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        switch ($name) {
            case 'first_name':
                $value = $current_user->user_firstname;
                break;
            case 'last_name':
                $value = $current_user->user_lastname;
                break;
            case 'email':
                $value = $current_user->user_email;
                break;
        }
    }
    $posted_value = ppcart_filter_input(INPUT_POST, $name, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    if (null !== $posted_value && false !== $posted_value) {
        $value = sanitize_text_field($posted_value);
    }
    $get_value = ppcart_filter_input(INPUT_GET, $name, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    if (null !== $get_value && false !== $get_value) {
        $value = sanitize_text_field($get_value);
    } elseif (strpos($name, 'ppcart_custom_fields') !== false) {
        $cfname = str_replace(['ppcart_custom_fields[', ']'], ['', ''], $name);
        $custom_field_value = filter_input(INPUT_GET, 'custom_' . $cfname, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if (null !== $custom_field_value && false !== $custom_field_value) {
            $value = sanitize_text_field($custom_field_value);
        }
    } ?>

    <?php if ($type != 'hidden') : ?>
    <div class="ppcart-form-group ppcart-col-sm-<?php echo esc_attr($cols); ?> <?php echo esc_attr($div_class); ?>" data-testid="<?php echo esc_attr(ppcart_data_testid_attribute($group_testid)); ?>">
        <?php if ($hide_labels != 'hide') : ?>
        <label for="<?php echo esc_attr($id); ?>"><?php echo esc_html($label); ?><?php echo (!$required) ? '' : ' <span class="req">*</span>'; ?></label>
        <?php endif; ?>
    <?php endif; ?>
        <?php if ($type == 'select') : ?>
            <select id="<?php echo esc_attr($name); ?>" name="<?php echo esc_attr($name); ?>" class="ppcart-form-control <?php echo esc_attr($class); ?>" data-testid="<?php echo esc_attr(ppcart_data_testid_attribute($testid)); ?>">
                <?php foreach ($choices as $k => $v) {
                    echo '<option value="' . esc_attr($k) . '" ' . selected($k, $value, false) . '>' . esc_html($v) . '</option>';
                } ?>
            </select>
            <?php echo wp_kses_post($description); ?>
        <?php elseif ($type == 'radio' || $type == 'checkbox') : ?>
            <?php if ($type == 'checkbox') {
                $name .= '[]';
            } ?>
            <br>
            <?php foreach ($choices as $k => $v) : ?>
                <?php $checked = (!empty($value) && $k == $value) ? 'checked' : ''; ?>
                <label>
                    <input type="<?php echo esc_attr($type); ?>" name="<?php echo esc_attr($name); ?>" <?php echo esc_attr($checked); ?> class="ppcart-form-control <?php echo esc_attr($class); ?>" value="<?php echo esc_attr($k); ?>" data-testid="<?php echo esc_attr(ppcart_data_testid_attribute($testid . '-' . ppcart_checkout_testid_suffix($k))); ?>">
                    <span class="item-name" data-testid="<?php echo esc_attr(ppcart_data_testid_attribute($testid . '-label-' . ppcart_checkout_testid_suffix($k))); ?>"><?php echo esc_html($v); ?></span>
                </label>
                <br>
            <?php endforeach; ?>
            <?php echo wp_kses_post($description); ?>
        <?php elseif ($type == 'quantity') : ?>
            <input id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>" type="number" class="ppcart-form-control <?php echo esc_attr($class); ?>" step="1" min="1" max="" placeholder="<?php esc_attr_e('Qty', 'publishpress-cart'); ?>" value="<?php echo esc_attr($value); ?>" aria-label="<?php echo esc_attr($label); ?>" inputmode="numeric" pattern="[0-9]*" data-testid="<?php echo esc_attr(ppcart_data_testid_attribute($testid)); ?>"
            <?php if ($qty_price) :
                ?> data-ppcart-qty-price="<?php echo esc_attr($qty_price); ?>"<?php
            endif; ?>>
            <?php echo wp_kses_post($description); ?>
              <?php if (is_array($posted_ppcart_errors) && isset($posted_ppcart_errors[$name])) : ?>
                  <div class="error"><?php echo esc_html(sanitize_text_field($posted_ppcart_errors[$name])); ?></div>
              <?php endif; ?>
        <?php else : ?>
            <input id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>" type="<?php echo esc_attr($type); ?>" class="ppcart-form-control <?php echo esc_attr($class); ?>" placeholder="<?php echo esc_attr($label); ?>" value="<?php echo esc_attr($value); ?>" aria-label="<?php echo esc_attr($label); ?>" data-testid="<?php echo esc_attr(ppcart_data_testid_attribute($testid)); ?>"
            <?php if ($type == 'password') :
                ?>pattern="(?=.*\d)(?=.*[a-z]).{8,}"<?php
            endif; ?>
            >
            <?php if ($type == 'password') : ?>
                <div class="password-toggle">
                    <input type="checkbox" name="ppcart-show-password" id="ppcart-show-password" class="ppcart-password-toggle" data-testid="<?php echo esc_attr(ppcart_data_testid_attribute('ppcart-checkout-show-password')); ?>">
                    <label for="ppcart-show-password"><?php esc_html_e('Show password', 'publishpress-cart'); ?></label>
                </div>
            <?php endif; ?>
              <?php echo wp_kses_post($description); ?>
              <?php if (is_array($posted_ppcart_errors) && isset($posted_ppcart_errors[$name])) : ?>
                  <div class="error"><?php echo esc_html(sanitize_text_field($posted_ppcart_errors[$name])); ?></div>
              <?php endif; ?>
        <?php endif; ?>
    <?php if ($type != 'hidden') : ?>
    </div>
    <?php endif;
}

function ppcart_do_checkoutform_fields($post_id, $hide_labels, $twostep = false)
{
    global $ppcart_product;
    $class = (!$twostep) ? 'ppcart-section card-details' : 'card-details';
    $fields_heading = (isset($ppcart_product->fields_heading) && $ppcart_product->fields_heading) ? $ppcart_product->fields_heading : esc_html__("Contact Info", "publishpress-cart");
    $fields_heading = ppcart_checkout_text_setting('contactInfoHeading', $fields_heading);
    ?>
    <div class="<?php echo esc_attr($class); ?>">
        <h3 class="title"><?php echo esc_html($fields_heading); ?></h3>
        <div class="ppcart-row checkout-contact-info">
        <?php
        $fields = [
            'firstname' => ['name' => 'first_name','label' => esc_html__('First Name', 'publishpress-cart'),'required' => true, 'hide_labels' => $hide_labels],
            'lastname' => ['name' => 'last_name','label' => esc_html__('Last Name', 'publishpress-cart'),'required' => true, 'hide_labels' => $hide_labels],

        ];
    if (!isset($ppcart_product->hide_phone_field) || !$ppcart_product->hide_phone_field) {
        $fields['email'] = ['name' => 'email','label' => esc_html__('Email', 'publishpress-cart'),'type' => 'email','required' => true, 'hide_labels' => $hide_labels];
        $fields['phone'] = ['name' => 'phone','label' => esc_html__('Phone Number', 'publishpress-cart'),'type' => 'tel','hide_labels' => $hide_labels];
    } else {
        $fields['email'] = ['name' => 'email','label' => esc_html__('Email', 'publishpress-cart'),'type' => 'email','required' => true, 'hide_labels' => $hide_labels,'cols' => 12];
    }

    $fields['company'] = ['name' => 'company','label' => esc_html__('Company Name', 'publishpress-cart'),'hide_labels' => $hide_labels];

    if (!isset($ppcart_product->default_fields)) {
        foreach ($fields as $k => $field) { // deprecated
            if (isset($ppcart_product->show_optin) || ($twostep && !isset($ppcart_product->show_address_fields))) {
                $field['cols'] = 12;
            }
            if (isset($ppcart_product->hide_fields) && isset($ppcart_product->hide_fields[$k])) {
                unset($field[$k]);
            }
        }
    }

    $fields = apply_filters('ppcart_order_form_fields', $fields, $ppcart_product);
    foreach ($fields as $k => $field) {
        ppcart_do_field($field);
    }
    ?>
        </div>

        <?php do_action('ppcart_checkout_form_fields', $post_id, $hide_labels); ?>

    </div>
<?php
}

function ppcart_do_2step_checkoutform_fields($post_id, $hide_labels)
{
    ppcart_do_checkoutform_fields($post_id, $hide_labels, $twostep = true);
}
