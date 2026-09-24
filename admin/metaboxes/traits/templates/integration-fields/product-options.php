<?php

if (! defined('ABSPATH')) {
    exit;
}


return [
    [
        'class_size'    => '',
        'description'   => __("Add an opt-in checkbox that a customer must check before their information can be added to a mailing list.", 'publishpress-cart'),
        'id'            => '_ppcart_show_optin_cb',
        'label'         => __('Enable Opt-in Checkbox', 'publishpress-cart'),
        'placeholder'   => '',
        'type'          => 'checkbox',
        'value'         => '',
    ],
    [
        'class_size'    => 'one-half first',
        'class'         => 'widefat',
        'description'   => '',
        'id'            => '_ppcart_optin_checkbox_text',
        'label'         => __('Opt-in Checkbox Label', 'publishpress-cart'),
        'placeholder'   => '',
        'type'          => 'text',
        'value'         => __('Sign me up for the newsletter', 'publishpress-cart'),
        'conditional_logic' => [
            [
                'field' => ppcart_meta_key('show_optin_cb'),
                'value' => true,
            ],
        ],
    ],
    [
        'class_size'    => 'one-half ',
        'description'   => '',
        'id'            => '_ppcart_optin_required',
        'label'         => __('Make Opt-in Checkbox Required', 'publishpress-cart'),
        'placeholder'   => '',
        'type'          => 'checkbox',
        'value'         => '',
        'conditional_logic' => [
            [
                'field' => ppcart_meta_key('show_optin_cb'),
                'value' => true,
            ],
        ],
    ],
    [
        'type'          => 'html',
        'value'         => '<div id="rid_ppcart_twostep_heading" class="ppcart-field ppcart-row"><p style="display: block; margin: 20px 0 0;padding: 0 0 5px;border-bottom: 1px solid #d5d5d5;flex-basis: 100%;">' . sprintf(
            /* translators: %s: product name. */
            __('Integrations for %s – integrations added here will apply to this product only!', 'publishpress-cart'),
            $name
        ) . '</p></div>
    ',
    ],
];
