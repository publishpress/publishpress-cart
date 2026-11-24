<?php

if (! defined('ABSPATH')) {
    exit;
}

$ppcart_autocomplete = [
    'first_name' => 'given-name',
    'last_name'  => 'family-name',
    'email'      => 'email',
    'phone'      => 'tel',
    'country'    => 'country-name',
    'address1'   => 'address-line1',
    'address2'   => 'address-line2',
    'city'       => 'address-level2',
    'state'      => 'address-level1',
    'zip'        => 'postal-code',
];
$ppcart_total = '';
foreach ($attr['cart']['totals']['rows'] as $ppcart_row) {
    if ('total' === $ppcart_row['type']) {
        $ppcart_total = $ppcart_row['html'];
    }
}
$ppcart_button = explode('{total}', $attr['button'], 2);
$ppcart_method = array_key_first($attr['methods']);
?>
<div class="ppcart-shopping-cart ppcart-shopping-cart-checkout" data-ppcart-cart-checkout style="<?php echo esc_attr($attr['style']); ?>">
    <?php if (! $attr['cart']['count'] && ! $attr['preview']) : ?>
        <div class="ppcart-shopping-cart-empty">
            <h2><?php esc_html_e('Your cart is empty.', 'publishpress-cart'); ?></h2>
            <p><?php esc_html_e('Add a product from the shop to start.', 'publishpress-cart'); ?></p>
            <?php if ($attr['cart']['links']['shop']) : ?>
                <a class="ppcart-shopping-cart-button" href="<?php echo esc_url($attr['cart']['links']['shop']); ?>"><?php esc_html_e('Browse the shop', 'publishpress-cart'); ?></a>
            <?php endif; ?>
        </div>
    <?php else : ?>
        <div class="ppcart-shopping-cart-checkout-columns">
            <form
                class="ppcart-shopping-cart-checkout-form"
                method="post"
                data-ppcart-checkout-form
                <?php if ($attr['stripe']) : ?>
                    data-ppcart-stripe-key="<?php echo esc_attr($attr['stripe']['key']); ?>"
                    data-ppcart-stripe-locale="<?php echo esc_attr($attr['stripe']['locale']); ?>"
                    data-ppcart-currency="<?php echo esc_attr($attr['stripe']['currency']); ?>"
                <?php endif; ?>
            >
                <input type="hidden" name="ppcart_cart_revision" value="<?php echo esc_attr($attr['cart']['revision']); ?>">
                <input type="hidden" name="ppcart_page_id" value="<?php echo esc_attr($attr['page_id']); ?>">

                <h2 class="ppcart-shopping-cart-title"><?php esc_html_e('Contact Info', 'publishpress-cart'); ?></h2>
                <div class="ppcart-shopping-cart-fields">
                    <?php foreach ($attr['fields'] as $ppcart_name => $ppcart_field) : ?>
                        <?php
                        if ('checkbox' === $ppcart_field['type']) {
                            continue;
                        }
                        $ppcart_id = 'ppcart-shopping-cart-' . $ppcart_name;
                        ?>
                        <div class="ppcart-shopping-cart-field" data-ppcart-field="<?php echo esc_attr($ppcart_name); ?>">
                            <label for="<?php echo esc_attr($ppcart_id); ?>">
                                <?php echo esc_html($ppcart_field['label']); ?>
                                <?php if (! empty($ppcart_field['required'])) : ?>
                                    <span class="ppcart-shopping-cart-required" aria-hidden="true">*</span>
                                <?php endif; ?>
                            </label>
                            <input
                                type="<?php echo esc_attr('email' === $ppcart_field['type'] ? 'email' : 'text'); ?>"
                                id="<?php echo esc_attr($ppcart_id); ?>"
                                name="<?php echo esc_attr($ppcart_name); ?>"
                                value="<?php echo esc_attr($attr['values'][$ppcart_name] ?? ''); ?>"
                                autocomplete="<?php echo esc_attr($ppcart_autocomplete[$ppcart_name] ?? 'on'); ?>"
                                <?php echo ! empty($ppcart_field['required']) ? 'required' : ''; ?>
                            >
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="ppcart-shopping-cart-payment" data-ppcart-checkout-payment<?php echo $attr['cart']['totals']['total'] > 0 ? '' : ' hidden'; ?>>
                    <h2 class="ppcart-shopping-cart-title"><?php esc_html_e('Payment Info', 'publishpress-cart'); ?></h2>
                    <?php if (! $attr['methods']) : ?>
                        <p class="ppcart-shopping-cart-notice"><?php esc_html_e('No payment method is available. Please contact the site administrator.', 'publishpress-cart'); ?></p>
                    <?php elseif (1 === count($attr['methods'])) : ?>
                        <input type="hidden" name="pay-method" value="<?php echo esc_attr($ppcart_method); ?>">
                    <?php else : ?>
                        <div class="ppcart-shopping-cart-methods" role="radiogroup" aria-label="<?php esc_attr_e('Payment method', 'publishpress-cart'); ?>">
                            <?php foreach ($attr['methods'] as $ppcart_value => $ppcart_label) : ?>
                                <label class="ppcart-shopping-cart-method">
                                    <input type="radio" name="pay-method" value="<?php echo esc_attr($ppcart_value); ?>" <?php checked($ppcart_value, $ppcart_method); ?>>
                                    <span><?php echo esc_html($ppcart_label); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($attr['methods']['stripe'])) : ?>
                        <div class="ppcart-shopping-cart-method-details" data-ppcart-pay-method="stripe">
                            <div data-ppcart-payment-element></div>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($attr['methods']['cod'])) : ?>
                        <p class="ppcart-shopping-cart-method-details" data-ppcart-pay-method="cod"<?php echo 'cod' === $ppcart_method ? '' : ' hidden'; ?>><?php esc_html_e('You pay when you receive your order.', 'publishpress-cart'); ?></p>
                    <?php endif; ?>
                </div>

                <?php foreach ($attr['fields'] as $ppcart_name => $ppcart_field) : ?>
                    <?php if ('checkbox' === $ppcart_field['type']) : ?>
                        <label class="ppcart-shopping-cart-consent" data-ppcart-field="<?php echo esc_attr($ppcart_name); ?>">
                            <input type="checkbox" name="<?php echo esc_attr($ppcart_name); ?>" value="1" <?php echo ! empty($ppcart_field['required']) ? 'required' : ''; ?>>
                            <span>
                                <?php if (! empty($ppcart_field['url'])) : ?>
                                    <a href="<?php echo esc_url($ppcart_field['url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($ppcart_field['label']); ?></a>
                                <?php else : ?>
                                    <?php echo esc_html($ppcart_field['label']); ?>
                                <?php endif; ?>
                            </span>
                        </label>
                    <?php endif; ?>
                <?php endforeach; ?>

                <p class="ppcart-shopping-cart-checkout-error" data-ppcart-checkout-error role="alert" hidden></p>
                <button type="submit" class="ppcart-shopping-cart-button ppcart-shopping-cart-pay"<?php disabled($attr['preview']); ?>>
                    <?php echo esc_html($ppcart_button[0]); ?>
                    <?php if (isset($ppcart_button[1])) : ?>
                        <span data-ppcart-cart-total><?php echo wp_kses_post($ppcart_total); ?></span>
                        <?php echo esc_html($ppcart_button[1]); ?>
                    <?php endif; ?>
                </button>
                <noscript><p class="ppcart-shopping-cart-notice"><?php esc_html_e('Turn on JavaScript in your browser to pay for your cart.', 'publishpress-cart'); ?></p></noscript>
            </form>
            <?php echo ppcart_get_template('shopping-cart/summary', '', ['checkout' => true] + $attr); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The template escapes its output. ?>
        </div>
    <?php endif; ?>
</div>
