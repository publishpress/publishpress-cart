<?php

if (! defined('ABSPATH')) {
    exit;
}


$payment_element = (bool) get_option('_ppcart_stripe_payment_element_enable', '1');
$hosted_checkout = (bool) get_option('_ppcart_stripe_hosted_checkout_enable');
// Hosted wins when both are stored, mirroring ppcart_setup_stripe().
$selected = $hosted_checkout ? 'hosted' : ($payment_element ? 'element' : 'classic');

$options = [
    'classic' => [
        'label'       => esc_html__('Classic card form', 'publishpress-cart'),
        'badge'       => esc_html__('Legacy', 'publishpress-cart'),
        'description' => esc_html__('Single card field on your checkout page. Supports order bumps and one-click upsells.', 'publishpress-cart'),
    ],
    'element' => [
        'label'       => esc_html__('Payment Element', 'publishpress-cart'),
        'badge'       => esc_html__('Recommended', 'publishpress-cart'),
        'description' => esc_html__('Cards, Apple/Google Pay, and Link embedded in your checkout page. Supports order bumps and one-click upsells.', 'publishpress-cart'),
    ],
    'hosted'  => [
        'label'       => esc_html__('Stripe-hosted page', 'publishpress-cart'),
        'description' => esc_html__('Redirects customers to a Stripe-hosted payment page. Most payment methods available (Klarna, Cash App Pay, and more via your Stripe dashboard).', 'publishpress-cart'),
        'warning'     => esc_html__('Order bumps and one-click upsells/downsells are not available in this mode. Coupons continue to work.', 'publishpress-cart'),
    ],
];

$express_note_element = esc_attr__('Built into Payment Element', 'publishpress-cart');
$express_note_hosted  = esc_attr__('Built into the Stripe-hosted page', 'publishpress-cart');
?>
<fieldset class="ppcart-settings__checkout-experience"
          data-pp-stripe-experience
          data-note-element="<?php echo esc_attr($express_note_element); ?>"
          data-note-hosted="<?php echo esc_attr($express_note_hosted); ?>">
    <?php foreach ($options as $key => $option) : ?>
        <label class="ppcart-settings__checkout-experience-option">
            <input type="radio"
                   name="ppcart_checkout_experience"
                   value="<?php echo esc_attr($key); ?>"
                   <?php checked($selected, $key); ?> />
            <span class="ppcart-settings__checkout-experience-text">
                <span class="ppcart-settings__checkout-experience-label">
                    <?php echo esc_html($option['label']); ?>
                    <?php if (! empty($option['badge'])) : ?>
                        <span class="ppcart-settings__checkout-experience-badge"><?php echo esc_html($option['badge']); ?></span>
                    <?php endif; ?>
                </span>
                <span class="ppcart-settings__checkout-experience-desc"><?php echo esc_html($option['description']); ?></span>
                <?php if (! empty($option['warning'])) : ?>
                    <span class="ppcart-settings__checkout-experience-warning"><?php echo esc_html($option['warning']); ?></span>
                <?php endif; ?>
            </span>
        </label>
    <?php endforeach; ?>
    <input type="hidden" id="_ppcart_stripe_payment_element_enable" name="_ppcart_stripe_payment_element_enable" value="<?php echo $payment_element ? '1' : '0'; ?>" />
    <input type="hidden" id="_ppcart_stripe_hosted_checkout_enable" name="_ppcart_stripe_hosted_checkout_enable" value="<?php echo $hosted_checkout ? '1' : '0'; ?>" />
</fieldset>
<?php
