<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * The one projection of a priced cart. REST responses and cart templates read this shape.
 */
final class PPCart_Shopping_Cart_View
{
    public function __construct(private PPCart_Shopping_Cart_Settings $settings = new PPCart_Shopping_Cart_Settings())
    {
    }

    public function response(PPCart_Shopping_Cart_Priced $cart, WP_REST_Request $request): array
    {
        return $this->data($cart) + ['nonce' => wp_create_nonce('wp_rest')];
    }

    public function data(PPCart_Shopping_Cart_Priced $cart): array
    {
        $totals = $cart->get_totals();
        return [
            'revision'  => $cart->revision,
            'count'     => $cart->count(),
            'currency'  => $cart->currency,
            'items'     => array_map([$this, 'item'], $cart->lines),
            'totals'    => [
                'subtotal' => PPCart_Shopping_Cart_Priced::decimal((float) ($totals['subtotal'] ?? 0)),
                'total'    => PPCart_Shopping_Cart_Priced::decimal((float) ($totals['total'] ?? 0)),
                'rows'     => array_values(array_map([$this, 'total_row'], array_filter((array) ($totals['rows'] ?? []), 'is_array'))),
            ],
            'notices'   => array_map(static fn (PPCart_Shopping_Cart_Notice $notice): array => get_object_vars($notice), $cart->notices),
            'after_add' => $this->settings->after_add(),
            'links'     => $this->settings->urls(),
        ];
    }

    private function item(PPCart_Shopping_Cart_Priced_Line $line): array
    {
        $image_url = $line->image_id ? wp_get_attachment_image_url($line->image_id, 'thumbnail') : false;
        return [
            'key'          => $line->key,
            'product_id'   => $line->product_id,
            'plan_id'      => $line->plan_id,
            'product_name' => $line->product_name,
            'plan_name'    => $line->plan_name,
            'quantity'     => $line->quantity,
            'max_quantity' => $line->max_quantity,
            'unit_price'   => PPCart_Shopping_Cart_Priced::decimal($line->unit_price),
            'subtotal'     => PPCart_Shopping_Cart_Priced::decimal($line->subtotal),
            'price_html'   => $this->price_html($line->unit_price),
            'image'        => $image_url ? ['url' => $image_url, 'alt' => $line->product_name] : null,
            'permalink'    => $line->permalink,
        ];
    }

    private function total_row(array $row): array
    {
        $amount = (float) ($row['amount'] ?? 0);
        return [
            'type'   => sanitize_key($row['type'] ?? ''),
            'label'  => wp_strip_all_tags((string) ($row['label'] ?? '')),
            'amount' => PPCart_Shopping_Cart_Priced::decimal($amount),
            'html'   => $this->price_html($amount),
        ];
    }

    private function price_html(float $amount): string
    {
        return wp_kses_post(ppcart_format_price(PPCart_Shopping_Cart_Priced::decimal($amount)));
    }
}
