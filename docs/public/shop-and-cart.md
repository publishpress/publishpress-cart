---
edition: both
nav_order: 5
features: [shopping-cart]
---

# Set up the Shop and the Cart

The Shop lists your products. Visitors add products to a cart and pay for all of
them with one checkout. They get one order, one receipt, and one purchase email.

Buy now checkout on each product page does not change. A product can sell
through both.

## Choose which products appear in the Shop

Each product has the setting **Show in Shop Page**. New and existing products
have it turned on.

To hide a product from the Shop, do these steps:

1. Open the product in **Cart → Products**.
2. On the **General** tab, clear **Show in Shop Page**.
3. Click **Update**.

The Shop no longer lists the product, and visitors cannot add it to the cart.
The product page and its Buy now checkout still work.

## Create the Shop, Cart, and Checkout pages

1. Go to **Cart → Settings → Shop & Cart**.
2. Make sure that **Enable shopping cart** is on.
3. In the **Pages** section, click **Create missing pages**.

The plugin creates these pages and selects them in the settings:

| Page | Block on the page |
|------|-------------------|
| Shop | **Cart Shop** |
| Cart | **Cart Shopping Cart** |
| Checkout | **Cart Checkout**, with **Product** set to **Cart** |

**Create missing pages** fills only the empty page settings. If you already
selected your own page for a setting, the plugin keeps it and does not change
its content. A second click creates nothing new.

To use your own pages, select them in **Shop page**, **Cart page**, and
**Cart checkout page** instead.

## Add a Mini Cart to your header

The **Cart Mini Cart** block shows a cart icon with the number of items. A click
opens the cart in a drawer. Visitors can go to checkout from the drawer.

1. Go to **Appearance → Editor** and open your header template part.
2. Add the **Cart Mini Cart** block.
3. Save the template part.

In a classic theme, put the `[ppcart_mini_cart]` shortcode in a widget or in
your theme header.

## Use the four blocks

| Block | Shortcode | What it shows |
|-------|-----------|---------------|
| **Cart Shop** | `[ppcart_shop]` | A grid of products with an **Add to cart** button on each card |
| **Cart Mini Cart** | `[ppcart_mini_cart]` | A cart icon with an item count that opens the cart drawer |
| **Cart Shopping Cart** | `[ppcart_cart]` | The items in the cart and the order summary |
| **Cart Checkout**, Product **Cart** | `[ppcart_cart_checkout]` | The checkout form that pays for the whole cart |

The block and its shortcode show the same output. The shortcode options are in
[Available shortcodes](getting-started/shortcodes.md#shop-and-cart).

All four blocks use the same **Design** settings as the checkout block: style
preset, accent color, surface, density, and corners.

## Know what the Shop card button does

| Product | Button on the Shop card |
|---------|-------------------------|
| One one-time or free payment plan | **Add to cart** |
| Several one-time or free payment plans | A **Payment plan** list and **Add to cart** |
| The selected plan is already in the cart | **✓ In cart** |
| Subscription plans only | **Subscribe**, which opens the product page |
| A collection, or the shopping cart is off | **View**, which opens the product page |

The cart keeps one of each product and plan. To buy one product with two plans,
the visitor adds each plan.

## Set the cart checkout options

Go to **Cart → Settings → Shop & Cart**.

| Section | Setting | What it does |
|---------|---------|--------------|
| Add to cart | **After add to cart** | Opens the mini cart, goes to the Cart page, or goes to checkout |
| Checkout form | **Show phone field**, **Show address fields** | Adds these fields to the cart checkout form |
| Checkout form | **Terms checkbox**, **Terms URL** | Adds a required terms checkbox. Both settings are necessary |
| Checkout form | **Pay button text** | The text on the pay button. `{total}` shows the order total |
| After payment | **Confirmation** | Shows a message, shows a page, or redirects to a URL |
| After payment | **Confirmation message** | The message on the checkout page after payment |
| Storage | **Keep carts for (days)** | How long the site keeps a cart that the visitor did not pay for |

The cart checkout takes payment with Stripe (card and Link) and PayPal. An order
with a total of zero needs no payment.

## Know what the cart does not support

The cart sells one-time and free payment plans only. These features stay in Buy
now checkout on the product page:

- Subscriptions
- Installment payment plans
- Pay what you want
- Order bumps
- Upsells

A product that has one-time and installment plans shows only its one-time plans
on the Shop card. The installment plans stay on the product page.

## When the price changes

The cart always charges the current price. If a price changed after the visitor
added the product, the Cart page and the checkout show the old and the new
price. If a product can no longer be sold, the cart removes it and shows a
notice.
