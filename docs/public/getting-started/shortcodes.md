---
edition: both
nav_order: 4
features: [checkout-display, secure-downloads, payment-plans, coupons, shopping-cart]
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

## Checkout and account layouts

| Shortcode | Displays |
|-----------|----------|
| `[ppcart_checkout_form id="123" plan="monthly" coupon="vip20"]` | Checkout for the selected product. The plan and coupon are optional. Omit the ID on a product template to use the current product. |
| `[ppcart_account_page_builder]` | A customer account page with login and tabbed account content. |
| `[ppcart_account_navigation]` | Account navigation with its corresponding tab panels. |
| `[ppcart_account_tab component="account-orders"]` | One account panel. Choose `account-orders`, `account-subscriptions`, `account-payment-plans`, `account-profile`, or `account-downloads`. |
| `[ppcart_account_orders]` | Customer order history. |
| `[ppcart_account_subscriptions]` | Customer subscriptions. |
| `[ppcart_account_payment_plans]` | Customer installment plans. |
| `[ppcart_account_profile]` | Customer profile. |
| `[ppcart_account_login]` | Customer login form. |
| `[ppcart_account_downloads]` | Customer downloads. |

In Elementor, search the widget panel for **Cart Checkout** or **Cart Account** to find the corresponding widgets.

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

## Shop and cart

These shortcodes show the same output as the Shop and Cart blocks. To set up
the pages, read [Set up the Shop and the Cart](../shop-and-cart.md).

| Shortcode | Displays |
|-----------|----------|
| `[ppcart_shop]` | Products with **Show in Shop Page** turned on, with an **Add to cart** button on each card. |
| `[ppcart_mini_cart]` | A cart icon with the item count. A click opens the cart drawer. Shows nothing when **Enable shopping cart** is off. |
| `[ppcart_cart]` | The items in the cart and the order summary. |
| `[ppcart_cart_checkout]` | The checkout form that pays for the cart. After payment, it shows the confirmation and the receipt. |

`[ppcart_shop]` accepts these parameters:

| Parameter | Default | Description |
|-----------|---------|-------------|
| `perpage` | `12` | Products on each page, from 1 to 100 |
| `columns` | `3` | Columns, from 1 to 4 |
| `orderby` | `date` | `date`, `title`, `price`, or `menu_order` |
| `pagination` | `numbers` | `numbers` (page numbers), `prev_next` (Previous and Next links), `load_more` (a **Load more products** button), or `none` (all products on one page) |
| `categories` | All | Product category IDs, separated by commas |
| `tags` | All | Product tag IDs, separated by commas |
| `showimage` | `true` | `false` hides the product image |
| `showexcerpt` | `true` | `false` hides the product excerpt |
| `showprice` | `true` | `false` hides the price |
| `includecollections` | `true` | `false` hides Pro collections |
| `includesubscriptions` | `true` | `false` hides products that have no one-time or free plan |

```text
[ppcart_shop perpage="8" columns="4" orderby="price" categories="12,15"]
```

`[ppcart_mini_cart]` accepts these parameters:

| Parameter | Default | Description |
|-----------|---------|-------------|
| `showcount` | `true` | `false` hides the item count |
| `showtotal` | `false` | `true` shows the cart total next to the icon |
| `opensdrawer` | `true` | `false` makes the icon a link to the Cart page |

`[ppcart_cart]` and `[ppcart_cart_checkout]` take no parameters.

The `[ppcart_store]` shortcode was removed. Use `[ppcart_shop]` or the Shop block.

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
