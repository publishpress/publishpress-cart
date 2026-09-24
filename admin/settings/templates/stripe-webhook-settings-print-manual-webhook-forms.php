<?php

if (! defined('ABSPATH')) {
    exit;
}


if (! current_user_can('manage_options')) {
    return;
}

$screen = function_exists('get_current_screen') ? get_current_screen() : null;
if (! $screen || false === strpos((string) $screen->id, PPCart_Admin_Screens::PAGE_SETTINGS)) {
    return;
}

wp_add_inline_script(
    'ppcart-settings',
    '
    document.addEventListener("click", function (event) {
        var trigger = event.target.closest("[data-ppcart-toggle]");
        if (!trigger) {
            return;
        }

        var panel = document.getElementById(trigger.getAttribute("data-ppcart-toggle"));
        if (!panel) {
            return;
        }

        event.preventDefault();
        panel.hidden = !panel.hidden;
    });
    '
);

foreach ([ 'test', 'live' ] as $form_mode) {
    printf(
        '<form id="%1$s" method="post" action="%2$s" class="ppcart-stripe-webhook-form">',
        esc_attr('ppcart-stripe-webhook-form-' . $form_mode),
        esc_url(admin_url('admin-post.php'))
    );
    echo '<input type="hidden" name="action" value="ppcart_stripe_webhook_manual_setup" />';
    echo '<input type="hidden" name="ppcart_stripe_mode" value="' . esc_attr($form_mode) . '" />';
    wp_nonce_field('ppcart_stripe_webhook_manual_setup', 'ppcart_stripe_webhook_nonce');
    echo '</form>';
}
