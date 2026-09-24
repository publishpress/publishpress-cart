<?php
if (! defined('ABSPATH')) {
    exit;
}

function ppcart_data_testid_attribute($value)
{
    if (function_exists('ppcart_testid')) {
        return ppcart_testid($value);
    }

    if (!is_string($value) || '' === $value) {
        return '';
    }

    $normalized = preg_replace('/[^A-Za-z0-9_-]+/', '-', $value);
    $normalized = trim((string) $normalized, '-');

    if ('' === $normalized) {
        return '';
    }

    return $normalized;
}

function ppcart_checkout_testid_suffix($value)
{
    $value = strtolower((string) $value);
    $value = preg_replace('/[^a-z0-9]+/', '-', $value);

    return trim((string) $value, '-');
}

function ppcart_checkout_content_sections()
{
    return [
        'payment_plan',
        'coupon',
        'contact_info',
        'payment_method',
        'payment_details',
        'order_bumps',
        'order_summary',
        'terms_consent',
        'express_payment',
        'submit_button',
    ];
}

function ppcart_checkout_default_content_order()
{
    return ppcart_checkout_content_sections();
}
function ppcart_checkout_product_context($post_id = 0)
{
    global $ppcart_product;

    if ((! is_object($ppcart_product) || ! isset($ppcart_product->ID)) && $post_id && function_exists('ppcart_setup_product')) {
        $ppcart_product = ppcart_setup_product($post_id);
    }

    return $ppcart_product;
}

function ppcart_checkout_normalize_content_order($content_order)
{
    if (is_string($content_order)) {
        $decoded = json_decode(wp_unslash($content_order), true);
        $content_order = is_array($decoded) ? $decoded : [];
    }

    if (!is_array($content_order)) {
        $content_order = [];
    }

    $known_sections = ppcart_checkout_content_sections();
    $normalized = [];

    foreach ($content_order as $section_id) {
        $section_id = sanitize_key($section_id);

        if (in_array($section_id, $known_sections, true) && !in_array($section_id, $normalized, true)) {
            $normalized[] = $section_id;
        }
    }

    foreach ($known_sections as $section_id) {
        if (!in_array($section_id, $normalized, true)) {
            $normalized[] = $section_id;
        }
    }

    return $normalized;
}

function ppcart_checkout_get_arrangement_context()
{
    global $ppcart_checkout_block_arrangement;

    return is_array($ppcart_checkout_block_arrangement) ? $ppcart_checkout_block_arrangement : [];
}

function ppcart_checkout_get_text_settings()
{
    $context = ppcart_checkout_get_arrangement_context();

    return isset($context['textSettings']) && is_array($context['textSettings']) ? $context['textSettings'] : [];
}

function ppcart_checkout_has_text_setting($key)
{
    $settings = ppcart_checkout_get_text_settings();

    return is_array($settings) && array_key_exists($key, $settings);
}

function ppcart_checkout_text_setting($key, $default)
{
    $settings = ppcart_checkout_get_text_settings();

    if (!array_key_exists($key, $settings) || !is_scalar($settings[$key])) {
        return $default;
    }

    $value = trim(sanitize_text_field($settings[$key]));

    return $value;
}

function ppcart_checkout_selected_plan($post_id, $plan = false)
{
    $ppcart_product = ppcart_checkout_product_context($post_id);

    $on_sale = ppcart_is_prod_on_sale();

    if (!$plan && get_query_var('ppcart-pay-plan')) {
        $plan = sanitize_text_field(get_query_var('ppcart-pay-plan'));
    }

    if ($plan) {
        $selected_plan = ppcart_plan($plan, $on_sale, $post_id);

        if ($selected_plan) {
            return $selected_plan;
        }
    }

    $items = isset($ppcart_product->pay_options) && is_array($ppcart_product->pay_options) ? $ppcart_product->pay_options : [];
    $items = apply_filters('ppcart_checkout_form_pay_options', $items, $ppcart_product);
    if (! is_array($items)) {
        $items = [];
    }

    foreach ($items as $item) {
        if (!is_array($item) || empty($item['option_id']) || isset($item['is_hidden'])) {
            continue;
        }

        $item['product_type'] ??= false;

        if (isset($ppcart_product->show_optin) && 'free' !== $item['product_type']) {
            continue;
        }

        $selected_plan = ppcart_plan($item['option_id'], $on_sale, $post_id);

        if ($selected_plan) {
            return $selected_plan;
        }
    }

    return false;
}

function ppcart_checkout_total_label($post_id, $plan = false)
{
    $ppcart_product = ppcart_checkout_product_context($post_id);

    $selected_plan = ppcart_checkout_selected_plan($post_id, $plan);

    if ($selected_plan && isset($selected_plan->type)) {
        if ('recurring' === $selected_plan->type) {
            return ppcart_checkout_text_setting('dueTodayLabel', esc_html__("Due Today", "publishpress-cart"));
        }

        return ppcart_checkout_text_setting('amountDueLabel', esc_html__("Amount Due", "publishpress-cart"));
    }

    if (isset($ppcart_product->single_plan) && !$ppcart_product->single_plan) {
        return ppcart_checkout_text_setting('dueTodayLabel', esc_html__("Due Today", "publishpress-cart"));
    }

    return ppcart_checkout_text_setting('amountDueLabel', esc_html__("Amount Due", "publishpress-cart"));
}

function ppcart_checkout_has_arranged_content()
{
    $context = ppcart_checkout_get_arrangement_context();

    return !empty($context['contentOrder']) && is_array($context['contentOrder']);
}

function ppcart_checkout_card_details_callback($callback, $priority)
{
    global $wp_filter;

    if (empty($wp_filter['ppcart_card_details_fields'])) {
        return false;
    }

    $callbacks = is_object($wp_filter['ppcart_card_details_fields']) && isset($wp_filter['ppcart_card_details_fields']->callbacks)
        ? $wp_filter['ppcart_card_details_fields']->callbacks
        : (array) $wp_filter['ppcart_card_details_fields'];

    return $callbacks[$priority][$callback] ?? false;
}

function ppcart_checkout_has_card_details_callback($callback)
{
    return false !== has_action('ppcart_card_details_fields', $callback);
}

function ppcart_checkout_arranged_core_card_details_callbacks()
{
    $callbacks = [
        ['callback' => 'ppcart_step_wrappers_1', 'priority' => 1],
        ['callback' => 'ppcart_do_2step_checkoutform_fields', 'priority' => 1],
        ['callback' => 'ppcart_address_fields', 'priority' => 1],
        ['callback' => 'ppcart_step_wrappers_2', 'priority' => 1],
        ['callback' => 'ppcart_payment_plan_options', 'priority' => 1],
        ['callback' => 'ppcart_payment_plan_options', 'priority' => 5],
        ['callback' => 'ppcart_do_checkoutform_fields', 'priority' => 5],
        ['callback' => 'ppcart_do_card_details_fields', 'priority' => 10],
        ['callback' => 'ppcart_orderbumps', 'priority' => 15],
    ];

    return apply_filters('ppcart_checkout_arranged_core_card_details_callbacks', $callbacks);
}

function ppcart_do_remaining_card_details_fields($post_id, $hide_labels, $plan = false)
{
    $removed_callbacks = [];

    foreach (ppcart_checkout_arranged_core_card_details_callbacks() as $callback) {
        if (empty($callback['callback']) || !isset($callback['priority'])) {
            continue;
        }

        $registered_callback = ppcart_checkout_card_details_callback($callback['callback'], $callback['priority']);

        if (!$registered_callback) {
            continue;
        }

        $removed_callbacks[] = [
            'callback'      => $registered_callback['function'],
            'priority'      => $callback['priority'],
            'accepted_args' => $registered_callback['accepted_args'] ?? 1,
        ];

        remove_action('ppcart_card_details_fields', $registered_callback['function'], $callback['priority']);
    }

    ob_start();
    do_action('ppcart_card_details_fields', $post_id, $hide_labels, $plan);
    $fields = trim(ob_get_clean());

    foreach ($removed_callbacks as $callback) {
        add_action('ppcart_card_details_fields', $callback['callback'], $callback['priority'], $callback['accepted_args']);
    }

    if ('' === $fields) {
        return;
    }

    echo wp_kses($fields, ppcart_frontend_allowed_html());
}

$product_post_types = (array) apply_filters('ppcart_product_post_type', ppcart_live_post_type('product'));
if (in_array(get_post_type(), $product_post_types)) {
    $product_id = $post instanceof WP_Post ? $post->ID : get_the_ID();
    $ppcart_product = ppcart_checkout_product_context($product_id);
    do_action('ppcart_after_product_setup', $ppcart_product);
}

function ppcart_do_test_mode_message()
{
    global $ppcart_stripe;
    if (!$ppcart_stripe || !isset($ppcart_stripe['mode'])) {
        return;
    }
    if ($ppcart_stripe['mode'] == 'test') {
        /* translators: %s: URL to Stripe test card documentation. */
        echo '<p class="ppcart-stripe" id="test-mode-message">' . wp_kses_post(sprintf(__('TEST MODE ENABLED: To make a test (US) purchase, use Credit Card Number "4242424242424242" with any CVC and a valid expiration date. <a href="%s" target="_blank" rel="noopener noreferrer" data-testid="ppcart-checkout-stripe-test-card-link">Find a test card for another country</a>.', 'publishpress-cart'), esc_url('https://stripe.com/docs/testing#international-cards'))) . '</p>';
    }
}
function ppcart_do_payment_confirmation($prod_id)
{

    $requested_order_id = filter_input(INPUT_GET, 'ppcart-order', FILTER_SANITIZE_NUMBER_INT);
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only payment confirmation routing; this does not create, update, or delete stored data.
    if (! $requested_order_id && isset($_GET['ppcart-order'])) {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only payment confirmation routing; this does not create, update, or delete stored data.
        $requested_order_id = absint(wp_unslash($_GET['ppcart-order']));
    }
    $show_confirm = ($requested_order_id && $requested_order_id > 0) ? true : false;
    $orderID      = ($show_confirm) ? intval($requested_order_id) : false;
    if ($show_confirm && ! PPCart_Order::visitor_can_view($orderID)) {
        $show_confirm = false;
        $orderID      = false;
    }
    ?>

    <section class="ppcart pay-confirm">
         <div class="ppcart-container">
            <div id="ppcart-payment-form" class="pay-confirmation">
                <div class="ppcart-section products">
                    <?php if ($show_confirm) : ?>
                        <div>
                            <img src="<?php echo esc_url(PPCART_BASE_URL . 'public/images/checkmark.png'); ?>" style="display: inline-block;width: 80px;margin-left: 12px;">
                            <?php
                                ppcart_do_confirmation_message();
                        ppcart_template('shortcodes/receipt', '', ppcart_get_item_list($orderID));
                        ?>
                        </div>
                    <?php else : ?>
                        <?php ppcart_do_cart_closed_message(); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    <?php
}

function ppcart_do_cart_closed_message()
{
    $ppcart_product = ppcart_checkout_product_context();
    $closed_msg = $ppcart_product->checkout_ended_message ?? '';
    $closed_msg = $closed_msg ? $closed_msg : __("Sorry, this product is no longer for sale.", "publishpress-cart");
    echo '<h4 class="closed" style="margin:0" data-testid="' . esc_attr(ppcart_data_testid_attribute('ppcart-closed-message')) . '">';
    echo wp_kses_post(wp_specialchars_decode($closed_msg, 'ENT_QUOTES'));
    echo '</h4>';
}

function ppcart_do_confirmation_message()
{
    $ppcart_product = ppcart_checkout_product_context();
    $closed_msg = $ppcart_product->confirmation_message ?? '';
    $closed_msg = $closed_msg ? $closed_msg : __("Thank you. We've received your order.", "publishpress-cart");
    echo '<h4 style="margin:0 0 20px" data-testid="' . esc_attr(ppcart_data_testid_attribute('ppcart-order-confirmation-message')) . '">';
    echo wp_kses_post(wp_specialchars_decode($closed_msg, 'ENT_QUOTES'));
    echo '</h4>';
}

function ppcart_do_error_messages()
{
    $errors = [];
    $posted_ppcart_errors = ppcart_filter_input(INPUT_POST, 'ppcart_errors', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY);
    $posted_ppcart_nonce = ppcart_filter_input(INPUT_POST, 'ppcart-nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    if (is_array($posted_ppcart_errors) && isset($posted_ppcart_errors['messages']) && $posted_ppcart_nonce && ppcart_verify_nonce($posted_ppcart_nonce, 'ppcart_purchase_nonce')) {
        $errors = array_map('sanitize_text_field', (array) $posted_ppcart_errors['messages']);
    }
    $errors = apply_filters('ppcart_checkout_page_error', $errors);
    if (!empty($errors)) {
        echo '<ul class="form-errors">';
        foreach ($errors as $msg) {
            echo '<li>' . esc_html($msg) . '</li>';
        }
        echo '</ul>';
    }
}

function ppcart_do_checkout_form_open($post_id)
{
    global $ppcart_uid;

    $ppcart_uid = wp_unique_id('ppcart_');
    $ppcart_product = ppcart_checkout_product_context($post_id);
    $ppcart_product->form_action = (string) $ppcart_product->form_action;
    $optin_class = isset($ppcart_product->show_optin) ? 'signup-form' : '';
    echo '<div id="ppcart-payment-form-' . esc_attr($ppcart_product->ID) . '-' . esc_attr($ppcart_uid) . '" class="ppcart-form-wrap">';
    echo '<form id="ppcart-payment-form" data-testid="' . esc_attr(ppcart_data_testid_attribute('ppcart-checkout-form')) . '"' . ($optin_class ? ' class="' . esc_attr($optin_class) . '"' : '') . ' action="' . esc_url($ppcart_product->form_action) . '" method="post">';
}

function ppcart_do_checkout_form($post_id, $hide_labels, $plan = false)
{
    if (ppcart_checkout_has_arranged_content()) {
        ppcart_do_arranged_checkout_form($post_id, $hide_labels, $plan);
        return;
    }

    do_action('ppcart_card_details_fields', $post_id, $hide_labels, $plan);
    do_action('ppcart_order_summary', $post_id, $plan);
}

function ppcart_do_arranged_checkout_form($post_id, $hide_labels, $plan = false)
{
    $context = ppcart_checkout_get_arrangement_context();
    $order = ppcart_checkout_normalize_content_order($context['contentOrder'] ?? []);
    $template = isset($context['template']) ? sanitize_key($context['template']) : '';
    $remaining_fields_rendered = false;

    ppcart_do_checkout_hidden_fields($post_id);

    if ('2-step' === $template) {
        ppcart_step_wrappers_1();
        ppcart_checkout_render_section('contact_info', $post_id, $hide_labels, $plan, $template);
        ppcart_step_wrappers_2();

        foreach ($order as $section_id) {
            if ('contact_info' === $section_id) {
                continue;
            }

            ppcart_checkout_render_section($section_id, $post_id, $hide_labels, $plan, $template);

            if (!$remaining_fields_rendered && 'payment_plan' === $section_id) {
                ppcart_do_remaining_card_details_fields($post_id, $hide_labels, $plan);
                $remaining_fields_rendered = true;
            }
        }

        if (!$remaining_fields_rendered) {
            ppcart_do_remaining_card_details_fields($post_id, $hide_labels, $plan);
        }

        return;
    }

    foreach ($order as $section_id) {
        ppcart_checkout_render_section($section_id, $post_id, $hide_labels, $plan, $template);

        if (!$remaining_fields_rendered && 'contact_info' === $section_id) {
            ppcart_do_remaining_card_details_fields($post_id, $hide_labels, $plan);
            $remaining_fields_rendered = true;
        }
    }

    if (!$remaining_fields_rendered) {
        ppcart_do_remaining_card_details_fields($post_id, $hide_labels, $plan);
    }
}

function ppcart_checkout_render_section($section_id, $post_id, $hide_labels, $plan = false, $template = '')
{
    switch ($section_id) {
        case 'payment_plan':
            if (ppcart_checkout_has_card_details_callback('ppcart_payment_plan_options')) {
                ppcart_payment_plan_options($post_id, $hide_labels, $plan, false);
            }
            break;

        case 'coupon':
            ppcart_do_coupon_section($post_id);
            break;

        case 'contact_info':
            if ('2-step' === $template) {
                if (ppcart_checkout_has_card_details_callback('ppcart_do_2step_checkoutform_fields')) {
                    ppcart_do_2step_checkoutform_fields($post_id, $hide_labels);
                }

                if (ppcart_checkout_has_card_details_callback('ppcart_address_fields')) {
                    ppcart_address_fields($post_id, $hide_labels);
                }
            } elseif (ppcart_checkout_has_card_details_callback('ppcart_do_checkoutform_fields')) {
                ppcart_do_checkoutform_fields($post_id, $hide_labels);
            }
            break;

        case 'payment_method':
            if (ppcart_checkout_has_card_details_callback('ppcart_do_card_details_fields')) {
                ppcart_do_payment_method_section($post_id);
            }
            break;

        case 'payment_details':
            if (ppcart_checkout_has_card_details_callback('ppcart_do_card_details_fields')) {
                ppcart_do_payment_details_section($post_id, $hide_labels);
            }
            break;

        case 'order_bumps':
            if (ppcart_checkout_has_card_details_callback('ppcart_orderbumps')) {
                ppcart_orderbumps($post_id);
            }
            break;

        case 'order_summary':
            ppcart_do_order_summary_section($post_id, $plan);
            break;

        case 'terms_consent':
            ppcart_do_terms_consent_section($post_id);
            break;

        case 'express_payment':
            ppcart_do_express_payment_section($post_id);
            break;

        case 'submit_button':
            ppcart_do_submit_button_section();
            break;
    }
}
