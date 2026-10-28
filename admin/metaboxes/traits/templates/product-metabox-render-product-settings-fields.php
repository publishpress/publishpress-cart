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

echo '<div class="ppcart-settings-tabs ppcart-ui">';

wp_nonce_field($this->plugin_name, 'ppcart_fields_nonce');

$first_tab_id = (string) array_key_first($product_setting_tabs);

echo '<div class="ppcart-left-col">';
foreach ($this->get_product_setting_tab_groups($product_setting_tabs) as $group_id => $group) {
    $group_label_id = 'ppcart-tab-group-' . sanitize_html_class((string) $group_id);
    echo '<div class="ppcart-tab-nav-group">';
    if ('' !== $group['label']) {
        echo '<p class="ppcart-tab-nav-group__label" id="' . esc_attr($group_label_id) . '">' . esc_html($group['label']) . '</p>';
    }
    echo '<div class="ppcart-tab-nav-group__tabs" role="tablist" aria-orientation="vertical"' . ('' !== $group['label'] ? ' aria-labelledby="' . esc_attr($group_label_id) . '"' : '') . '>';
    foreach ($group['tabs'] as $tab_id => $label) {
        $is_active = ((string) $tab_id === $first_tab_id);
        $badge     = (isset($pro_tabs[$tab_id]) && function_exists('ppcart_pro_nav_badge')) ? ppcart_pro_nav_badge() : '';
        echo '<div class="ppcart-tab-nav' . ($is_active ? ' active' : '') . (isset($pro_tabs[$tab_id]) ? ' ppcart-tab-nav--pro' : '') . '" role="presentation">';
        echo '<a href="#ppcart-tab-' . esc_attr($tab_id) . '" id="ppcart-tab-link-' . esc_attr($tab_id) . '" role="tab" aria-controls="ppcart-tab-' . esc_attr($tab_id) . '" aria-selected="' . ($is_active ? 'true' : 'false') . '" tabindex="' . ($is_active ? '0' : '-1') . '" data-testid="' . esc_attr(ppcart_testid('ppcart-admin-product-tab-' . $tab_id)) . '">';
        echo '<span class="ppcart-tab-nav__label">' . esc_html($label) . '</span>' . wp_kses_post($badge);
        echo '<span class="ppcart-tab-nav__count" hidden></span>';
        echo '</a></div>';
    }
    echo '</div></div>';
}
echo '</div>';

echo '<div class="ppcart-right-col">';
echo '<div class="ppcart-metabox-notice notice notice-error inline" role="alert" hidden></div>';
foreach ($product_setting_tabs as $tab_id => $label) {
    $is_active = ((string) $tab_id === $first_tab_id);
    echo '<div id="ppcart-tab-' . esc_attr($tab_id) . '" class="ppcart-tab' . ($is_active ? ' active' : '') . '" role="tabpanel" aria-labelledby="ppcart-tab-link-' . esc_attr($tab_id) . '" tabindex="0">';

    if (isset($pro_tabs[$tab_id])) {
        if (function_exists('ppcart_pro_render_locked_product_tab')) {
            echo wp_kses(ppcart_pro_render_locked_product_tab($tab_id), ppcart_admin_allowed_html());
        }
    } else {
        $description = function_exists('ppcart_product_tab_description') ? ppcart_product_tab_description($tab_id) : '';
        if ('' !== $description) {
            echo '<p class="ppcart-product-tab-description">' . esc_html($description) . '</p>';
        }
        $fields = $this->$tab_id ?? [];
        $fields = $this->filter_product_setting_tab_fields($tab_id, $fields, 'render');
        $this->render_product_tab_sections($tab_id, $fields);

        // Show the extra Pro-only General options as one preview card.
        if ('general' === $tab_id && function_exists('ppcart_pro_locked_product_more_card_html')) {
            echo wp_kses(ppcart_pro_locked_product_more_card_html('general'), ppcart_admin_allowed_html());
        }
    }

    echo '</div>';
}
echo '</div>
</div>';

$this->render_product_notification_modal();

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only context value for field script generation.
$current_post_id = isset($_GET['post']) ? absint(wp_unslash($_GET['post'])) : 0;
$this->scripts = apply_filters('ppcart_product_field_scripts', $this->scripts, $current_post_id);

if ($this->scripts != '') :
    $ppcart_product_field_scripts = $this->scripts;
    $ppcart_product_fields_buffer_level = ob_get_level();
    $ppcart_product_fields_buffer_active = true;
    $ppcart_product_fields_buffer_error = null;
    ob_start(static function ($buffer, $phase) use (&$ppcart_product_fields_buffer_active) {
        if ($phase & PHP_OUTPUT_HANDLER_FINAL) {
            $ppcart_product_fields_buffer_active = false;
        }
        return $buffer;
    });
    try {
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

            var saleFields = '.ridsale_option_name, .ridsale_price, .ridsale_frequency, .ridsale_interval, .ridsale_installments, .ridsale_sign_up_fee';
            var toggleSaleFields = function() {
                var onSale = $('#_ppcart_on_sale').is(':checked') || $('#_ppcart_schedule_sale').is(':checked');
                var $inputs = $(saleFields).find('input, select');
                // Plan sale sections hide by CSS; disabled inputs keep sale values out of the save.
                $('#ppcart-product-settings .ppcart-settings-tabs').toggleClass('ppcart-sale-off', !onSale);
                if (onSale) {
                    $inputs.removeAttr('disabled');
                    $('.ridsale_option_name, .ridsale_price').find('input').addClass('required');
                    $('#rid_ppcart_show_full_price').show();
                } else {
                    $inputs.attr('disabled', '').removeClass('required error');
                    $('#rid_ppcart_show_full_price').hide();
                }
            };
            toggleSaleFields();
            $('#_ppcart_on_sale, #_ppcart_schedule_sale').change(toggleSaleFields);

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
    } catch (Throwable $ppcart_product_fields_buffer_exception) {
        $ppcart_product_fields_buffer_error = $ppcart_product_fields_buffer_exception;
    } finally {
        $ppcart_product_fields_buffer_output = '';
        // Flush nested buffers into ours; never close a caller's or replacement buffer.
        while ($ppcart_product_fields_buffer_active && ob_get_level() > $ppcart_product_fields_buffer_level + 1) {
            $ppcart_product_fields_buffer_nested_level = ob_get_level();
            try {
                if (! ob_end_flush()) {
                    break;
                }
            } catch (Throwable $ppcart_product_fields_buffer_exception) {
                $ppcart_product_fields_buffer_error = $ppcart_product_fields_buffer_error ?? $ppcart_product_fields_buffer_exception;
                if (ob_get_level() >= $ppcart_product_fields_buffer_nested_level) {
                    break;
                }
            }
        }
        if ($ppcart_product_fields_buffer_active && ob_get_level() === $ppcart_product_fields_buffer_level + 1) {
            $ppcart_product_fields_buffer_output = (string) ob_get_clean();
        }
    }
    if (null !== $ppcart_product_fields_buffer_error) {
        throw $ppcart_product_fields_buffer_error;
    }
    $product_field_script = trim($ppcart_product_fields_buffer_output);

    if ('' !== $ppcart_product_field_scripts) {
        $product_field_script = "jQuery(function($){\n" . $ppcart_product_field_scripts . "\n});\n" . $product_field_script;
    }

    if ('' !== $product_field_script) {
        wp_add_inline_script('ppcart-repeater', $product_field_script);
    }

    do_action('ppcart_product_print_field_scripts', $current_post_id);
endif;
