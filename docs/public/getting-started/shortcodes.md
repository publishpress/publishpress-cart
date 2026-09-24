---
edition: both
nav_order: 4
features: [checkout-display, secure-downloads, payment-plans, coupons]
---

# Available shortcodes

PublishPress Cart provides shortcodes to embed checkout forms, receipts, and
order details on pages and thank-you screens.

## Order form

```text
[ppcart_form id="123" hide_labels="hide" template="2-step" coupon="vip20"]
```

Displays an order form.

| Parameter | Description |
|-----------|-------------|
| `id` | Product ID (required unless used in a global product template) |
| `hide_labels` | `hide` to hide field labels (default: show labels) |
| `template` | `2-step` for the two-step checkout layout |
| `coupon` | Pre-apply a coupon code |

## Receipt

```text
[ppcart_receipt]
```

Lists purchased products and total amount on a thank-you page.

When using **Perform Redirect URL** for confirmation, add `ppcart-order={order_id}`
to the redirect URL. The thank-you page must be on the same site.

## Order detail

```text
[ppcart_order_detail field="customer_name"]
```

Shows a single order field on upsell or thank-you pages.

Available `field` values:

- `order_id`, `customer_firstname`, `customer_lastname`, `customer_name`
- `customer_email`, `customer_phone`
- `order_list`, `order_inline_list`, `order_amount`, `product_amount`

## Payment plan

```text
[ppcart_plan product_id=XX plan_id=XX field=price]
```

Displays the price or name of one payment plan.

| Parameter | Description |
|-----------|-------------|
| `product_id` | Product ID |
| `plan_id` | Payment plan ID |
| `field` | `price` or `name` |

## Product info

```text
[ppcart_product field=name]
[ppcart_product field=limit]
```

Shows the product name or remaining inventory on the checkout page.

## Product store

```text
[ppcart_store]
```

Displays a product archive.

| Parameter | Default | Description |
|-----------|---------|-------------|
| `button_text` | Purchased | Label on the purchase button |
| `purchased_text` | Already Purchased | Label when already owned |
| `posts_per_page` | 12 | Products per page |
| `cols` | 3 | Columns (`3` or `4`) |

## Order downloads

```text
[ppcart_order_downloads]
```

Shows file download links on thank-you pages when confirmation is set to
**Display Page**.

| Parameter | Description |
|-----------|-------------|
| `show-title=0` | Hide the download file title |

StudioCart leftover tags such as `[studiocart-form]` still render while
**StudioCart Compatibility Mode** is on. Compat (not Cart admin) owns leftover
tag rewrite: **Settings → StudioCart → Data migration**. Cart admin is
**Cart → …**.
