---
edition: both
nav_order: 0
features: [core-checkout, store-settings, checkout-display]
---

# Introduction

PublishPress Cart sells digital products, programs, events, and services from
WordPress without WooCommerce. Create products, embed checkout forms, and
accept payments through Stripe and PayPal.

This knowledge base is for store administrators. You need access to **Cart**
in WP Admin.

## What you can do

- Create products with one-time, free, or recurring payment plans
- Embed a checkout form on any page with a shortcode or the Gutenberg
  **Checkout** block
- Take test payments, then switch Stripe to **Live** when you are ready
- Manage orders, refunds, and subscriptions from **Cart → Orders** and
  **Cart → Subscriptions**

## Start here

1. Install and activate **PublishPress Cart**.
2. Follow [Getting started](overview.md) to create your first product.
3. Set currency and gateways in [Configuring settings](configuring-settings.md).

## Going live with Stripe subscriptions

Stripe Test and Live objects are separate. After you switch **API** from
**Test** to **Live**, update each subscription product so Cart can create Live
prices. See
[Using Stripe with recurring payment plans](../subscriptions/using-stripe-with-recurring-payment-plans.md).
