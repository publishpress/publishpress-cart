<?php
if (! defined('ABSPATH')) {
    exit;
}

function ppcart_do_order_summary($post_id, $plan = false)
{
    echo '<div class="ppcart-section ppcart-order-summary">';
    ppcart_do_order_summary_content($post_id, $plan);

    echo '<div class="ppcart-row"><div class="ppcart-form-group ppcart-col-sm-12">';
    ppcart_do_terms_consent_content($post_id);
    do_action('ppcart_express_payment_method_fields', $post_id);
    ppcart_do_submit_button_content();
    echo '</div></div>';
    echo '</div>';
}

function ppcart_do_order_summary_section($post_id, $plan = false)
{
    ob_start();
    ppcart_do_order_summary_content($post_id, $plan);
    $summary = trim(ob_get_clean());

    if ('' === $summary) {
        return;
    }

    echo '<div class="ppcart-section ppcart-order-summary">' . wp_kses($summary, ppcart_frontend_allowed_html()) . '</div>';
}

function ppcart_do_order_summary_content($post_id, $plan = false)
{
    global $ppcart_product;

    if (!isset($ppcart_product->show_splitin)) {
        ppcart_render_order_summary_items($post_id, $plan);
    }
}

function ppcart_do_terms_consent_section($post_id)
{
    ob_start();
    ppcart_do_terms_consent_content($post_id);
    $terms = trim(ob_get_clean());

    if ('' === $terms) {
        return;
    }

    echo '<div class="ppcart-section ppcart-terms-consent-section"><div class="ppcart-row"><div class="ppcart-form-group ppcart-col-sm-12">' . wp_kses($terms, ppcart_frontend_allowed_html()) . '</div></div></div>';
}

function ppcart_do_terms_consent_content($post_id)
{
    global $ppcart_product;

    $terms = $ppcart_product->terms_url;
    $privacy = $ppcart_product->privacy_url;
    $posted_ppcart_errors = ppcart_filter_input(INPUT_POST, 'ppcart_errors', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY);

    if (!$terms && !$privacy && !$ppcart_product->show_optin_cb) {
        return;
    }

    $class = (is_array($posted_ppcart_errors) && isset($posted_ppcart_errors['ppcart_accept_terms'])) ? 'invalid' : '';
    ?>
    <div id="ppcart-terms">
        <?php if ($terms) : ?>
            <?php
            $terms_text = sprintf(
                /* translators: %s: terms and conditions link. */
                esc_html_x('I have read and I accept the %s', 'terms and conditions', 'publishpress-cart'),
                '<a href="' . esc_url($terms) . '" target="_blank" rel="noopener noreferrer" data-testid="' . esc_attr(ppcart_data_testid_attribute('ppcart-checkout-terms-link')) . '">' . esc_html__('terms and conditions', 'publishpress-cart') . '</a> <span class="req">*</span>'
            ); ?>

            <div class="checkbox-wrap <?php echo esc_attr($class); ?>">
                <label>
                    <input type="checkbox" class="required" id="ppcart_accept_terms" name="ppcart_accept_terms" value="yes" data-testid="<?php echo esc_attr(ppcart_data_testid_attribute('ppcart-checkout-terms-checkbox')); ?>">
                    <span class="item-name"><?php echo wp_kses_post(apply_filters('ppcart_checkout_page_terms_text', $terms_text, $ppcart_product)); ?></span>
                </label>
            </div>
            <?php if (is_array($posted_ppcart_errors) && isset($posted_ppcart_errors['ppcart_accept_terms'])) : ?>
                <div class="error"><?php echo esc_html(sanitize_text_field($posted_ppcart_errors['ppcart_accept_terms'])); ?></div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($privacy) : ?>
            <?php
            $privacy_text = sprintf(
                /* translators: %s: privacy policy link. */
                esc_html_x('I have read and I accept the %s', 'privacy policy', 'publishpress-cart'),
                '<a href="' . esc_url($privacy) . '" target="_blank" rel="noopener noreferrer" data-testid="' . esc_attr(ppcart_data_testid_attribute('ppcart-checkout-privacy-link')) . '">' . esc_html__('privacy policy', 'publishpress-cart') . '</a> <span class="req">*</span>'
            ); ?>
            <div class="checkbox-wrap <?php echo esc_attr($class); ?>">
                <label>
                    <input type="checkbox" class="required" id="ppcart_accept_privacy" name="ppcart_accept_privacy" value="yes" data-testid="<?php echo esc_attr(ppcart_data_testid_attribute('ppcart-checkout-privacy-checkbox')); ?>">
                    <span class="item-name"><?php echo wp_kses_post(apply_filters('ppcart_checkout_page_privacy_text', $privacy_text, $ppcart_product)); ?></span>
                </label>
            </div>
            <?php if (is_array($posted_ppcart_errors) && isset($posted_ppcart_errors['ppcart_accept_privacy'])) : ?>
                <div class="error"><?php echo esc_html(sanitize_text_field($posted_ppcart_errors['ppcart_accept_privacy'])); ?></div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($ppcart_product->show_optin_cb) : ?>
            <?php
            $ppcart_product->optin_required = isset($ppcart_product->optin_required);
            $required = apply_filters('ppcart_consent_required', $ppcart_product->optin_required, $ppcart_product); ?>
            <div class="checkbox-wrap <?php echo esc_attr($class); ?>">
                <label>
                    <input type="checkbox" id="ppcart_consent" name="ppcart_consent" value="yes" data-testid="<?php echo esc_attr(ppcart_data_testid_attribute('ppcart-checkout-consent-checkbox')); ?>" <?php if ($required) {
                        echo 'class="required"';
                                                                                                              } ?> >
                    <span class="item-name"><?php echo wp_kses_post(wp_specialchars_decode($ppcart_product->optin_checkbox_text, 'ENT_QUOTES')); ?>
                    <?php if ($required) {
                        echo '<span class="req">*</span>';
                    } ?>
                    </span>
                </label>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

function ppcart_do_express_payment_section($post_id)
{
    ob_start();
    do_action('ppcart_express_payment_method_fields', $post_id);
    $express_payment = trim(ob_get_clean());

    if ('' === $express_payment) {
        return;
    }

    echo '<div class="ppcart-section ppcart-express-payment-section">' . wp_kses($express_payment, ppcart_frontend_allowed_html()) . '</div>';
}

function ppcart_do_submit_button_section()
{
    echo '<div class="ppcart-section ppcart-submit-button-section"><div class="ppcart-row"><div class="ppcart-form-group ppcart-col-sm-12">';
    ppcart_do_submit_button_content();
    echo '</div></div></div>';
}

function ppcart_do_submit_button_content()
{
    global $ppcart_product, $ppcart_uid;

    ppcart_helper()->renderTemplate('order-form/submit-button', ['ppcart_product' => $ppcart_product, 'id' => 'ppcart_card_button', 'ppcart_uid' => $ppcart_uid]);
}

/**
 * Render the checkout order-summary line items for a product and selected plan.
 *
 * @param int          $post_id Product post ID.
 * @param object|false $plan    Selected plan, or false.
 * @return void
 */
function ppcart_render_order_summary_items($post_id, $plan = false)
{
    global $ppcart_product;
    ?>
    <div class="order-summary-wrap">
        <?php
        ob_start();
        do_action('ppcart_order_summary_items', $ppcart_product, $post_id, $plan);
        $ppcart_summary_items = trim(ob_get_clean());

        if ('' !== $ppcart_summary_items) {
            echo wp_kses($ppcart_summary_items, ppcart_frontend_allowed_html());
        } else { ?>
            <h3 class="title"><?php echo esc_html(ppcart_checkout_text_setting('orderTotalHeading', esc_html__("Order Total", "publishpress-cart"))); ?></h3>
        <?php } ?>

        <?php do_action('ppcart_after_summary_items', $ppcart_product); ?>

        <div class="ppcart-row">
            <div class="ppcart-form-group ppcart-col-sm-12">
                <div class="total">
                    <span class="ppcart-total-label"><?php echo esc_html(ppcart_checkout_total_label($post_id, $plan)); ?></span>
                    <div class="total-rhs">
                        <span class="price"></span>
                        <small></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function ppcart_do_2step_checkout_form_open($post_id)
{
    global $ppcart_product;
    global $ppcart_uid;

    $ppcart_uid = wp_unique_id('ppcart_');

    if (!$ppcart_product || !isset($ppcart_product->ID)) {
        $ppcart_product = ppcart_setup_product($post_id);
    }
    $action = esc_attr($ppcart_product->form_action);
    $step_one_heading = ppcart_checkout_text_setting('twoStepTabOneHeading', $ppcart_product->twostep_heading_1);
    $step_two_heading = ppcart_checkout_text_setting('twoStepTabTwoHeading', $ppcart_product->twostep_heading_2);
    $step_one_subheading = ppcart_checkout_text_setting('twoStepTabOneSubheading', $ppcart_product->twostep_subhead_1);
    $step_two_subheading = ppcart_checkout_text_setting('twoStepTabTwoSubheading', $ppcart_product->twostep_subhead_2);
    ?>
    <div id="ppcart-payment-form-<?php echo esc_attr($ppcart_product->ID . '-' . $ppcart_uid); ?>" class="ppcart-form-wrap">
        <div class="ppcart-embed-checkout-form-nav ppcart-border-none">
            <ul class="ppcart-checkout-form-steps">
                <div class="steps step-one ppcart-current">
                    <a href="#" data-testid="<?php echo esc_attr(ppcart_data_testid_attribute('ppcart-checkout-step-one-tab')); ?>">
                        <div class="step-number">1</div>
                        <div class="step-heading">
                            <div class="step-name"><?php echo esc_html($step_one_heading); ?></div>
                            <div class="step-sub-name"><?php echo esc_html($step_one_subheading); ?></div>
                        </div>
                    </a>
                </div>
                <div class="steps step-two">
                    <a href="#" data-testid="<?php echo esc_attr(ppcart_data_testid_attribute('ppcart-checkout-step-two-tab')); ?>">
                        <div class="step-number">2</div>
                        <div class="step-heading">
                            <div class="step-name"><?php echo esc_html($step_two_heading); ?></div>
                            <div class="step-sub-name"><?php echo esc_html($step_two_subheading); ?></div>
                        </div>
                    </a>
                </div>
            </ul>
        </div>

        <form id="ppcart-payment-form" class="ppcart-2step-wrapper step-1" action="<?php echo esc_url($action); ?>" method="post">

<?php
}

function ppcart_step_wrappers_1()
{
    echo '<div id="customer-details" class="ppcart-section ppcart-checkout-step">';
}

function ppcart_step_wrappers_2()
{
    global $ppcart_product, $ppcart_uid; ?>
    <div class="ppcart-row">
      <div class="ppcart-form-group ppcart-col-sm-12">
        <button type="button" class="ppcart-btn ppcart-btn-primary ppcart-btn-block ppcart-next-btn" data-form-wrapper="ppcart-payment-form-<?php echo esc_attr($ppcart_product->ID); ?>-<?php echo esc_attr($ppcart_uid); ?>" data-testid="<?php echo esc_attr(ppcart_data_testid_attribute('ppcart-checkout-next-step')); ?>">
            <span class="text">
                <?php do_action('ppcart_step_1_button_icon', $ppcart_product, 'left'); ?>

                <?php echo esc_html($ppcart_product->step1_button_label); ?>

                <?php do_action('ppcart_step_1_button_icon', $ppcart_product, 'right'); ?>
            </span>

            <?php do_action('ppcart_step_1_button_subtext', $ppcart_product); ?>
        </button>
        <?php do_action('ppcart_after_step_1_button'); ?>
      </div>
    </div>
    </div><div id="billing-details" class="ppcart-checkout-step">
<?php
}

function ppcart_step_wrappers_3()
{
    echo '</div>';
}

function ppcart_do_checkout_form_scripts($prod_id, $coupon = false)
{
    $coupon = apply_filters('ppcart_checkout_coupon', $coupon, $prod_id);
    if (! $coupon) {
        return;
    }

    wp_add_inline_script(
        'ppcart',
        'window.ppcart_coupon = ' . wp_json_encode([ sanitize_text_field($coupon) ]) . ';',
        'before'
    );
}
