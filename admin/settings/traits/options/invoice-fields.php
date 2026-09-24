<?php

if (! defined('ABSPATH')) {
    exit;
}

$invoice_date_docs_url = apply_filters('ppcart_invoice_date_documentation_url', '');

/* translators: Invoice prefix/suffix help: date tokens. Do not translate {D}, {DD}, {M}, {MM}, {YY}, {YYYY}, {H}, {HH}, {N}, {S}, or the example INV-{YYYY}{MM}{DD}. */
$invoice_prefix_suffix_note = esc_html__(
    'Use date tokens {D}, {DD}, {M}, {MM}, {YY}, {YYYY}, {H}, {HH}, {N}, {S}, and custom alphanumeric characters. Example: INV-{YYYY}{MM}{DD}.',
    'publishpress-cart'
);

if ('' !== $invoice_date_docs_url) {
    $invoice_prefix_suffix_note .= ' ' . sprintf(
        '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
        esc_url($invoice_date_docs_url),
        esc_html__('Learn more', 'publishpress-cart')
    );
}

$invoice_fields = ['invoice-setting' => [
    'enable_invoice_number' => [
        'type'          => 'checkbox',
        'label'         => esc_html__('Custom Invoice Numbering', 'publishpress-cart'),
        'settings'      => [
            'id'            => '_ppcart_enable_invoice_number',
            'value'         => '',
            'class'         => 'ppcart-settings__invoice-numbering-toggle',
            'description'   => '',
        ],
        'tab' => 'invoice',
    ],
    'invoice_attach_pending_email' => [
        'type'          => 'checkbox',
        'label'         => esc_html__('Attach invoice to order received emails', 'publishpress-cart'),
        'settings'      => [
            'id'            => '_ppcart_invoice_attach_pending_email',
            'value'         => '',
            'description'   => '',
        ],
        'tab' => 'invoice',
    ],
    'invoice_attach_confirmation_email' => [
        'type'          => 'checkbox',
        'label'         => esc_html__('Attach invoice to purchase confirmation emails', 'publishpress-cart'),
        'settings'      => [
            'id'            => '_ppcart_invoice_attach_confirmation_email',
            'value'         => '',
            'description'   => '',
        ],
        'tab' => 'invoice',
    ],
    'invoice_attach_renewal_email' => [
        'type'          => 'checkbox',
        'label'         => esc_html__('Attach invoice to subscription renewal emails', 'publishpress-cart'),
        'settings'      => [
            'id'            => '_ppcart_invoice_attach_renewal_email',
            'value'         => '',
            'description'   => '',
        ],
        'tab' => 'invoice',
    ],
    'invoice_prefix' => [
        'type'          => 'text',
        'label'         => esc_html__('Invoice Prefix', 'publishpress-cart'),
        'settings'      => [
            'id'            => '_ppcart_invoice_prefix',
            'value'         => '',
            'class'         => 'regular-text ppcart-settings__invoice-numbering-dependent',
            'note'          => $invoice_prefix_suffix_note,
        ],
        'tab' => 'invoice',
    ],
    'invoice_sufix' => [
        'type'          => 'text',
        'label'         => esc_html__('Invoice Suffix', 'publishpress-cart'),
        'settings'      => [
            'id'            => '_ppcart_invoice_sufix',
            'value'         => '',
            'class'         => 'regular-text ppcart-settings__invoice-numbering-dependent',
            'note'          => $invoice_prefix_suffix_note,
        ],
        'tab' => 'invoice',
    ],
    'invoice_length' => [
        'type'          => 'text',
        'label'         => esc_html__('Invoice Length', 'publishpress-cart'),
        'settings'      => [
            'id'            => '_ppcart_invoice_length',
            'value'         => '0',
            'class'         => 'regular-text ppcart-settings__invoice-numbering-dependent',
            'note'   => 'Indicate total length of the invoice number, excluding the prefix and suffix',
        ],
        'tab' => 'invoice',
    ],
    'invoice_format' => [
        'type'          => 'select',
        'label'         => esc_html__('Invoice Format', 'publishpress-cart'),
        'settings'      => [
            'id'            => '_ppcart_invoice_format',
            'value'         => 'ppcart_pns',
            'class'         => 'ppcart-settings__invoice-numbering-dependent',
            'selections'    => [
                'ppcart_pns' => '[prefix][number][suffix]',
                'ppcart_pn' => '[prefix][number]',
                'ppcart_ns' => '[number][suffix]',
                'ppcart_n' => '[number]',
            ],
            'description'   => '',
        ],
        'tab' => 'invoice',
    ],
    'invoice_start_number' => [
        'type'          => 'text',
        'label'         => esc_html__('Current Invoice Number', 'publishpress-cart'),
        'settings'      => [
            'id'            => '_ppcart_invoice_start_number',
            'value'         => '1',
            'class'         => 'regular-text ppcart-settings__invoice-numbering-dependent',
            'description'   => '',
            'readonly'      => true,
        ],
        'tab' => 'invoice',
    ],
]];

return $invoice_fields;
