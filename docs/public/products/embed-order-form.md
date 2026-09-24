---
edition: both
nav_order: 1
features: [checkout-display, coupons]
---

# Embed an order form anywhere

Place a checkout form on any WordPress page or post.

Alternatively, use the Gutenberg **Checkout** block instead of a shortcode.

## Steps

1. Go to **Cart → Products**.
2. Open the product you want to sell.
3. Copy the shortcode shown on the product screen.
4. Paste the shortcode into a page or post and publish.

The form renders inline wherever you place the shortcode.

## Shortcode

```text
[ppcart_form id="123"]
```

## Parameters

| Parameter | Required | Description |
|-----------|----------|-------------|
| `id` | Yes | Product ID to embed |
| `hide_labels` | No | `hide` to hide form field labels (default: show labels) |
| `template` | No | `2-step`, `opt-in`, or `split-in` for alternate layouts |
| `coupon` | No | Coupon code applied by default |

## Example

```text
[ppcart_form id="42" hide_labels="hide"]
```

For the full list of shortcodes, see
[Available shortcodes](../getting-started/shortcodes.md).
