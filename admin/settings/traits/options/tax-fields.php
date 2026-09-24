<?php

if (! defined('ABSPATH')) {
    exit;
}

$tax_fields = ['tax-setting' => [
    'tax-enable' => [
        'type'          => 'checkbox',
        'label'         => esc_html__('Enable Tax', 'publishpress-cart'),
        'settings'      => [
            'id'            => '_ppcart_tax_enable',
            'value'         => '',
            'class'         => 'ppcart-settings__tax-enable-row',
            'description'   => '',
        ],
        'tab' => 'tax',
    ],
    'tax-type' => [
        'type'          => 'select',
        'label'         => esc_html__('Price Entered', 'publishpress-cart'),
        'settings'      => [
            'id'            => '_ppcart_tax_type',
            'value'         => 'inclusive_tax',
            'class'         => 'ppcart-settings__tax-dependent',
            'selections'    => [
                'inclusive_tax' => 'prices inclusive of tax',
                'exclusive_tax' => 'prices exclusive of tax',
            ],
        ],
        'tab' => 'tax',
    ],
    'tax-price-row' => [
        'type'          => 'select',
        'label'         => esc_html__('Show Price', 'publishpress-cart'),
        'settings'      => [
            'id'            => '_ppcart_price_show_with_tax',
            'value'         => 'exclude_tax',
            'class'         => 'ppcart-settings__tax-dependent',
            'selections'    => [
                'exclude_tax' => 'Show Price and Tax Indvidually',
                'include_tax' => 'Show Price and Tax Together',
            ],
        ],
        'tab' => 'tax',
    ],
    'vat-enable' => [
        'type'          => 'checkbox',
        'label'         => esc_html__('Enable VAT', 'publishpress-cart'),
        'settings'      => [
            'id'            => '_ppcart_vat_enable',
            'value'         => '',
            'description'   => '',
        ],
        'tab' => 'tax',
    ],
    'merchant-vat-country' => [
        'type'          => 'select',
        'label'         => esc_html__('Merchant VAT Country', 'publishpress-cart'),
        'settings'      => [
            'id'            => '_ppcart_vat_merchant_state',
            'value'         => 'AL',
            'selections'    => ppcart_vat_countries_list(),
        ],
        'tab' => 'tax',
    ],
    'vat-all-eu-businesses' => [
        'type'          => 'checkbox',
        'label'         => esc_html__('Tax all EU Businesses', 'publishpress-cart'),
        'settings'      => [
            'id'            => '_ppcart_vat_all_eu_businesses',
            'value'         => '',
            'description'   => '',

        ],
        'tab' => 'tax',
    ],
    'vat-disable-vies-database-lookup' => [
        'type'          => 'checkbox',
        'label'         => esc_html__('Disable VAT VIES database lookup', 'publishpress-cart'),
        'settings'      => [
            'id'            => '_ppcart_vat_disable_vies_database_lookup',
            'value'         => '',
            'description'   => '',
        ],
        'tab' => 'tax',
    ],
    'vat-reverse-charge' => [
        'type'          => 'text',
        'label'         => esc_html__('Reverse Charge Description', 'publishpress-cart'),
        'settings'      => [
            'id'            => '_ppcart_vat_reverse_charge',
            'value'         => esc_html__('VAT Reversal', 'publishpress-cart'),
            'description'   => '',
        ],
        'tab' => 'tax',
    ],
]];

return $tax_fields;
