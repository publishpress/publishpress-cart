<?php

defined('ABSPATH') || exit;

$shortcodes = [
    [
        'group' => 'checkout',
        'tag' => 'ppcart_checkout_form',
        'title' => __('Checkout form', 'publishpress-cart'),
        'example' => '[ppcart_checkout_form id="123"]',
        'description' => __('Embed checkout for a product. Replace 123 with the product ID.', 'publishpress-cart'),
    ],
    [
        'group' => 'checkout',
        'tag' => 'ppcart_form',
        'title' => __('Product form', 'publishpress-cart'),
        'example' => '[ppcart_form id="123"]',
        'description' => __('Display a product checkout using its configured template.', 'publishpress-cart'),
    ],
    [
        'group' => 'store',
        'tag' => 'ppcart_shop',
        'title' => __('Shop', 'publishpress-cart'),
        'example' => '[ppcart_shop]',
        'description' => __('Put this on the Shop page. It lists the products that have Show in Shop Page turned on.', 'publishpress-cart'),
    ],
    [
        'group' => 'store',
        'tag' => 'ppcart_mini_cart',
        'title' => __('Mini cart', 'publishpress-cart'),
        'example' => '[ppcart_mini_cart]',
        'description' => __('Put this in the site header. It shows a cart icon with the item count and opens the cart drawer.', 'publishpress-cart'),
    ],
    [
        'group' => 'store',
        'tag' => 'ppcart_cart',
        'title' => __('Cart', 'publishpress-cart'),
        'example' => '[ppcart_cart]',
        'description' => __('Put this on the Cart page. It shows the items in the cart and the order summary.', 'publishpress-cart'),
    ],
    [
        'group' => 'store',
        'tag' => 'ppcart_cart_checkout',
        'title' => __('Cart checkout', 'publishpress-cart'),
        'example' => '[ppcart_cart_checkout]',
        'description' => __('Put this on the Checkout page. It shows the checkout form that pays for the whole cart.', 'publishpress-cart'),
    ],
    [
        'group' => 'checkout',
        'tag' => 'ppcart_receipt',
        'title' => __('Receipt', 'publishpress-cart'),
        'example' => '[ppcart_receipt]',
        'description' => __('Display the receipt for the current order on the confirmation page.', 'publishpress-cart'),
    ],
    [
        'group' => 'account',
        'tag' => 'ppcart_account',
        'title' => __('Account', 'publishpress-cart'),
        'example' => '[ppcart_account]',
        'description' => __('Display the standard customer account page.', 'publishpress-cart'),
    ],
    [
        'group' => 'account',
        'tag' => 'ppcart_account_page_builder',
        'title' => __('Account page builder', 'publishpress-cart'),
        'example' => '[ppcart_account_page_builder]',
        'description' => __('Display the account layout with navigation and its sections.', 'publishpress-cart'),
    ],
    [
        'group' => 'account',
        'tag' => 'ppcart_account_navigation',
        'title' => __('Account navigation', 'publishpress-cart'),
        'example' => '[ppcart_account_navigation]',
        'description' => __('Display tab navigation and its account sections.', 'publishpress-cart'),
    ],
    [
        'group' => 'account',
        'tag' => 'ppcart_account_tab',
        'title' => __('Account tab', 'publishpress-cart'),
        'example' => '[ppcart_account_tab component="account-orders"]',
        'description' => __('Display one account section. Use account-orders, account-subscriptions, account-payment-plans, account-profile, or account-downloads.', 'publishpress-cart'),
    ],
    [
        'group' => 'account',
        'tag' => 'ppcart_account_orders',
        'title' => __('Account orders', 'publishpress-cart'),
        'example' => '[ppcart_account_orders]',
        'description' => __('Display the account orders section.', 'publishpress-cart'),
    ],
    [
        'group' => 'account',
        'tag' => 'ppcart_account_subscriptions',
        'title' => __('Account subscriptions', 'publishpress-cart'),
        'example' => '[ppcart_account_subscriptions]',
        'description' => __('Display the account subscriptions section.', 'publishpress-cart'),
    ],
    [
        'group' => 'account',
        'tag' => 'ppcart_account_payment_plans',
        'title' => __('Account payment plans', 'publishpress-cart'),
        'example' => '[ppcart_account_payment_plans]',
        'description' => __('Display the account payment plans section.', 'publishpress-cart'),
    ],
    [
        'group' => 'account',
        'tag' => 'ppcart_account_profile',
        'title' => __('Account profile', 'publishpress-cart'),
        'example' => '[ppcart_account_profile]',
        'description' => __('Display the account profile section.', 'publishpress-cart'),
    ],
    [
        'group' => 'account',
        'tag' => 'ppcart_account_login',
        'title' => __('Account login', 'publishpress-cart'),
        'example' => '[ppcart_account_login]',
        'description' => __('Display the account login section.', 'publishpress-cart'),
    ],
    [
        'group' => 'account',
        'tag' => 'ppcart_account_downloads',
        'title' => __('Account downloads', 'publishpress-cart'),
        'example' => '[ppcart_account_downloads]',
        'description' => __('Display the account downloads section.', 'publishpress-cart'),
    ],
    [
        'group' => 'account',
        'tag' => 'ppcart_account_link',
        'title' => __('Account link', 'publishpress-cart'),
        'example' => '[ppcart_account_link]',
        'description' => __('Link to the customer account page.', 'publishpress-cart'),
    ],
    [
        'group' => 'account',
        'tag' => 'ppcart_account_order_detail',
        'title' => __('Order details', 'publishpress-cart'),
        'example' => '[ppcart_account_order_detail]',
        'description' => __('Display order details on the account page.', 'publishpress-cart'),
    ],
    [
        'group' => 'account',
        'tag' => 'ppcart_account_subscription_detail',
        'title' => __('Subscription details', 'publishpress-cart'),
        'example' => '[ppcart_account_subscription_detail]',
        'description' => __('Display subscription details on the account page.', 'publishpress-cart'),
    ],
    [
        'group' => 'account',
        'tag' => 'ppcart_order_downloads',
        'title' => __('Order downloads', 'publishpress-cart'),
        'example' => '[ppcart_order_downloads id="123"]',
        'description' => __('Display downloads for an order. Replace 123 with the order ID.', 'publishpress-cart'),
    ],
];

$shortcode_groups = [
    'checkout' => __('Checkout and receipts', 'publishpress-cart'),
    'store' => __('Shop and cart', 'publishpress-cart'),
    'account' => __('Customer account', 'publishpress-cart'),
    'affiliates' => __('Affiliates', 'publishpress-cart'),
    'additional' => __('Additional shortcodes', 'publishpress-cart'),
];

$examples_by_group = [];
foreach (apply_filters('ppcart_settings_shortcode_examples', $shortcodes) as $shortcode_example) {
    if (! shortcode_exists($shortcode_example['tag'])) {
        continue;
    }

    $group = $shortcode_example['group'] ?? 'additional';
    if (! isset($shortcode_groups[$group])) {
        $group = 'additional';
    }
    $examples_by_group[$group][] = $shortcode_example;
}

foreach ($shortcode_groups as $group => $group_label) :
    if (empty($examples_by_group[$group])) {
        continue;
    }
    ?>
    <section class="ppcart-settings__card ppcart-settings__shortcode">
        <h3 class="ppcart-settings__card-title"><?php echo esc_html($group_label); ?></h3>
        <?php foreach ($examples_by_group[$group] as $shortcode_example) : ?>
            <div class="ppcart-settings__shortcode-row">
                <h4><?php echo esc_html($shortcode_example['title']); ?></h4>
                <p><?php echo esc_html($shortcode_example['description']); ?></p>
                <div class="ppcart-settings__shortcode-example">
                    <input type="text" readonly aria-label="<?php echo esc_attr($shortcode_example['title']); ?>"
                        value="<?php echo esc_attr($shortcode_example['example']); ?>">
                    <button type="button" class="button" data-pp-copy-shortcode>
                        <?php esc_html_e('Copy', 'publishpress-cart'); ?>
                        <span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </section>
<?php endforeach; ?>
