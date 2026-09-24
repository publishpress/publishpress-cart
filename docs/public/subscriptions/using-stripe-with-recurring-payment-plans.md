---
edition: both
nav_order: 1
features: [subscriptions, core-checkout, store-settings]
---

# Using Stripe with recurring payment plans

Stripe subscriptions need a Price (plan) in the Stripe account that matches
the product. Cart creates that Price when you save a product with a
**recurring** payment plan — but only in the Stripe mode selected under
**Cart → Settings → Payment Methods → Stripe → API** (**Test** or **Live**).

Audience: store administrators who already enabled Stripe and created at
least one subscription product while **API** was **Test**.

## Why Test and Live are separate

Stripe Test and Live do not share products or prices. A Price ID stored while
**API** is **Test** is invalid after you switch to **Live**. Checkout then
fails and the customer cannot complete the purchase.

## Go from Test to Live

1. Open **Cart → Settings**.
2. Open the **Payment Methods** tab.
3. Under **Stripe**, set **API** to **Live**.
4. Click **Save changes**.
5. Open **Cart → Products**.
6. Edit each product that has a recurring payment plan created while **API**
   was **Test**.
7. Click **Update** (the WordPress product save button). Do this even if you
   did not change prices or intervals.

Cart then creates the Stripe Product and Prices in Live mode and stores the
new Price IDs on the product.

## Check that it worked

Place a Live checkout using a real payment method (or Stripe’s Live test
flow for your account). The subscription product should charge and create an
order without a Stripe “No such price” (or similar) error.

## If checkout still fails

1. Confirm **API** is **Live** and Stripe Connection Status shows a Live
   connection.
2. Open the product and click **Update** again.
3. In Stripe Dashboard → **Live** mode, confirm a Product and recurring
   Price exist for that Cart product.

One-time (non-recurring) plans do not need this Live Price recreate step.

See [Configuring settings](../getting-started/configuring-settings.md) for
the rest of the Payment Methods tab.
