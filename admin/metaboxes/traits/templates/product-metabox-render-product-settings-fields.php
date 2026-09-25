<?php

if (! defined('ABSPATH')) {
    exit;
}



if (! is_admin()) {
    return;
}

$product_metabox_post_types = (array) apply_filters('ppcart_product_metabox_post_type', ppcart_live_post_type('product'));
if ([] === $product_metabox_post_types) {
    $product_metabox_post_types = ppcart_query_post_types('product');
}

if (!in_array($post->post_type, $product_metabox_post_types)) {
    return;
}

$this->set_field_groups();

$product_setting_tabs = $this->get_product_setting_tabs();

$pro_tabs = function_exists('ppcart_pro_locked_product_tabs') ? ppcart_pro_locked_product_tabs() : [];

echo '<div class="ppcart-settings-tabs">';

wp_nonce_field($this->plugin_name, 'ppcart_fields_nonce');

echo '<div class="ppcart-left-col">';
$i = 0;
foreach ($product_setting_tabs as $tab_id => $label) {
    $active = ($i == 0) ? 'active' : '';
    $badge  = (isset($pro_tabs[$tab_id]) && function_exists('ppcart_pro_nav_badge')) ? ppcart_pro_nav_badge() : '';
    echo '<div class="ppcart-tab-nav ' . esc_attr($active) . '"><a href="#ppcart-tab-' . esc_attr($tab_id) . '" data-testid="' . esc_attr(ppcart_testid('ppcart-admin-product-tab-' . $tab_id)) . '">' . esc_html($label) . wp_kses_post($badge) . '</a></div>';
    $i++;
}
echo '</div>';

echo '<div class="ppcart-right-col">';
$i = 0;
foreach ($product_setting_tabs as $tab_id => $label) {
    $active = ($i == 0) ? 'active' : '';
    echo '<div id="ppcart-tab-' . esc_attr($tab_id) . '" class="ppcart-tab ' . esc_attr($active) . '">';

    if (isset($pro_tabs[$tab_id])) {
        if (function_exists('ppcart_pro_render_locked_product_tab')) {
            echo wp_kses(ppcart_pro_render_locked_product_tab($tab_id), ppcart_admin_allowed_html());
        }
    } else {
        $fields = $this->$tab_id ?? [];
        $fields = $this->filter_product_setting_tab_fields($tab_id, $fields, 'render');
        $this->metabox_fields($fields);

        // Append the extra Pro-only fields to the free General tab.
        if ('general' === $tab_id && function_exists('ppcart_pro_locked_product_field_rows_html')) {
            echo wp_kses(ppcart_pro_locked_product_field_rows_html('general'), ppcart_admin_allowed_html());
        }
    }

    echo '</div>';
    $i++;
};
echo '</div>
</div>';

$this->render_product_notification_modal();

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only context value for field script generation.
$current_post_id = isset($_GET['post']) ? absint(wp_unslash($_GET['post'])) : 0;
$this->scripts = apply_filters('ppcart_product_field_scripts', $this->scripts, $current_post_id);

if ($this->scripts != '') :
    $ppcart_product_field_scripts = $this->scripts;
    ob_start();
    ?>
        jQuery('document').ready(function($){
            $("#repeater_ppcart_product_options [name^=\"prod_on_sale[\"]").each(function(index){
                if ( ($(this).closest(".ppcart-repeater-content").find("[name^=\"prod_on_sale[\"]").is(':checked')) ) {
                    $(this).closest(".ppcart-repeater-content").find(".ridprod_show_full_price").css({opacity: 0, display: "flex"}).animate({opacity: 1}, 400)
                } else {
                    $(this).closest(".ppcart-repeater-content").find(".ridprod_show_full_price").hide()
                }
            });

            $("#repeater_ppcart_product_options [name^=\"prod_on_sale[\"]").change(function(){
                if ( ($(this).closest(".ppcart-repeater-content").find("[name^=\"prod_on_sale[\"]").is(':checked')) ) {
                    $(this).closest(".ppcart-repeater-content").find(".ridprod_show_full_price").css({opacity: 0, display: "flex"}).animate({opacity: 1}, 400)
                } else {
                    $(this).closest(".ppcart-repeater-content").find(".ridprod_show_full_price").hide()
                }
            });

            if(!$('#_ppcart_on_sale').is(':checked') && !$('#_ppcart_schedule_sale').is(':checked')) {
                $('.ridsale_option_name, .ridsale_price').find('input').attr('disabled','').css({background:'#f0eeee', opacity: '0.6'}).removeClass('required error');
                $('#rid_ppcart_show_full_price').hide();
            } else {
                $('.ridsale_option_name, .ridsale_price').find('input').removeAttr('disabled').removeAttr('style').addClass('required');
                $('#rid_ppcart_show_full_price').show();
            }

            $('#_ppcart_on_sale, #_ppcart_schedule_sale').change(function(){
                if ($(this).is(':checked')) {
                    $('.ridsale_option_name, .ridsale_price').find('input').removeAttr('disabled').removeAttr('style').addClass('required');
                    $('#rid_ppcart_show_full_price').show();
                } else if(!$('#_ppcart_on_sale').is(':checked') && !$('#_ppcart_schedule_sale').is(':checked')) {
                    $('.ridsale_option_name, .ridsale_price').find('input').attr('disabled','').css({background:'#f0eeee', opacity: '0.6'}).removeClass('required error');
                    $('#rid_ppcart_show_full_price').hide();
                }
            });

            $('.riddrip_action select').on('change', function(){
                var fields = '.riddrip_tag';
                if ($(this).val() != "unsubscribe" && $(this).closest(".ppcart-repeater-content").find('.service_select').val()=='drip') {
                    $(this).closest(".ppcart-repeater-content").find(fields).css({ opacity: 0, display: "flex" }).animate({ opacity: 1 }, 400);
                } else {
                    $(this).closest(".ppcart-repeater-content").find(fields).hide();
                }
            });
            $('.riddrip_action select').each(function(){
                var fields = '.riddrip_tag';
                if ($(this).val() != "unsubscribe" && $(this).closest(".ppcart-repeater-content").find('.service_select').val()=='drip') {
                    $(this).closest(".ppcart-repeater-content").find(fields).show();
                } else {
                    $(this).closest(".ppcart-repeater-content").find(fields).hide();
                }
            });
            $('.ridtutor_action select').each(function(){
                var fields = '.riduser_role';
                if ($(this).val() == "enroll" && (
                    $(this).closest(".ppcart-repeater-content").find('.service_select').val()=='create user' ||
                    $(this).closest(".ppcart-repeater-content").find('.service_select').val()=='tutor'
                    )
                ) {
                    $(this).closest(".ppcart-repeater-content").find(fields).show();
                }
            });
            $('.ridtutor_action select').on('change', function(){
                var fields = '.riduser_role';
                if ($(this).val() == "enroll" && (
                    $(this).closest(".ppcart-repeater-content").find('.service_select').val()=='create user' ||
                    $(this).closest(".ppcart-repeater-content").find('.service_select').val()=='tutor'
                    )
                ) {
                    $(this).closest(".ppcart-repeater-content").find(fields).css({ opacity: 0, display: "flex" }).animate({ opacity: 1 }, 400);
                } else {
                    $(this).closest(".ppcart-repeater-content").find(fields).hide();
                }
            });

            $('.ridwlm_action select').each(function(){
                var fields = '.ridwlm_pending';
                if ($(this).val() == "add" && $(this).closest(".ppcart-repeater-content").find('.service_select').val()=='wishlist') {
                    $(this).closest(".ppcart-repeater-content").find(fields).show();
                } else {
                    $(this).closest(".ppcart-repeater-content").find(fields).hide();
                }
                var fields = '.ridwlm_send_email';
                if ($(this).val() == "remove" && $(this).closest(".ppcart-repeater-content").find('.service_select').val()=='wishlist') {
                    $(this).closest(".ppcart-repeater-content").find(fields).hide();
                } else if ($(this).closest(".ppcart-repeater-content").find('.service_select').val()=='wishlist') {
                    $(this).closest(".ppcart-repeater-content").find(fields).show();
                }
            });
            $('.ridwlm_action select').on('change', function(){
                var fields = '.ridwlm_pending';
                if ($(this).val() == "add" && $(this).closest(".ppcart-repeater-content").find('.service_select').val()=='wishlist') {
                    $(this).closest(".ppcart-repeater-content").find(fields).css({ opacity: 0, display: "flex" }).animate({ opacity: 1 }, 400);
                } else if($(this).closest(".ppcart-repeater-content").find('.service_select').val()=='wishlist') {
                    $(this).closest(".ppcart-repeater-content").find(fields).hide();
                }
                var fields = '.ridwlm_send_email';
                if ($(this).val() == "remove" && $(this).closest(".ppcart-repeater-content").find('.service_select').val()=='wishlist') {
                    $(this).closest(".ppcart-repeater-content").find(fields).hide();
                } else if($(this).closest(".ppcart-repeater-content").find('.service_select').val()=='wishlist') {
                    $(this).closest(".ppcart-repeater-content").find(fields).css({ opacity: 0, display: "flex" }).animate({ opacity: 1 }, 400);
                }
            });

            $('.ridservice_action select').each(function(){
                var fields = '.ridconvertkit_forms';
                if ($(this).val() == "subscribed" && $(this).closest(".ppcart-repeater-content").find('.service_select').val()=='convertkit') {
                    $(this).closest(".ppcart-repeater-content").find(fields).show();
                } else {
                    $(this).closest(".ppcart-repeater-content").find(fields).hide();
                }
            });
            $('.ridservice_action select').on('change', function(){
                var fields = '.ridconvertkit_forms';
                if ($(this).val() == "subscribed" && $(this).closest(".ppcart-repeater-content").find('.service_select').val()=='convertkit') {
                    $(this).closest(".ppcart-repeater-content").find(fields).css({ opacity: 0, display: "flex" }).animate({ opacity: 1 }, 400);
                } else {
                    $(this).closest(".ppcart-repeater-content").find(fields).hide();
                }
            });

            $('#_ppcart_show_address_fields').on('change', function(){
                if ($(this).is(':checked')) {
                    $('#repeater_ppcart_address_fields, #rid_ppcart_address_fields').fadeIn(400);
                } else {
                    $('#repeater_ppcart_address_fields, #rid_ppcart_address_fields').hide();
                }
            });

            $('#_ppcart_show_address_fields').each(function(){
                if ($(this).is(':checked')) {
                    $('#repeater_ppcart_address_fields, #rid_ppcart_address_fields').fadeIn(400);
                } else {
                    $('#repeater_ppcart_address_fields, #rid_ppcart_address_fields').hide();
                }
            });

            // recurring_pwyw
            if (($('#product_type').length && $('#product_type').val() === 'recurring' || $('[name="_ppcart_pay_options[product_type][0]"]').val() === 'recurring') && $('#_ppcart_pay_options\\[recurring_pwyw\\]\\[0\\]').is(':checked')) {
                $('.ridname_your_own_price_text_recurring').css({ opacity: 0, display: "flex" }).animate({ opacity: 1 }, 400);
            } else {
                $('.ridname_your_own_price_text_recurring').hide();
            }
            $(document).on('change', '#product_type, [name="_ppcart_pay_options[product_type][0]"], #_ppcart_pay_options\\[recurring_pwyw\\]\\[0\\]', function() {
                if (($('#product_type').length && $('#product_type').val() === 'recurring' || $('[name="_ppcart_pay_options[product_type][0]"]').val() === 'recurring') && $('#_ppcart_pay_options\\[recurring_pwyw\\]\\[0\\]').is(':checked')) {
                    $('.ridname_your_own_price_text_recurring').css({ opacity: 0, display: "flex" }).animate({ opacity: 1 }, 400);
                } else {
                    $('.ridname_your_own_price_text_recurring').hide();
                }
            });

        });
    <?php
    $product_field_script = trim(ob_get_clean());

    if ('' !== $ppcart_product_field_scripts) {
        $product_field_script = "jQuery(function($){\n" . $ppcart_product_field_scripts . "\n});\n" . $product_field_script;
    }

    if ('' !== $product_field_script) {
        wp_add_inline_script('ppcart-repeater', $product_field_script);
    }

    do_action('ppcart_product_print_field_scripts', $current_post_id);
endif;
