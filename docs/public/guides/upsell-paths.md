---
title: "Create an Upsell Paths"
order: 42
doc_category: publishpress-cart
section: Upsell Paths
---

# Create an Upsell Paths

Table of Contents

- [How the flow works](#0-toc-title)
- [Creating an upsell path](#1-toc-title)
- [Building the offer page](#2-toc-title)
- [Assigning a product to a path](#3-toc-title)
- [Things to know](#4-toc-title)

An **upsell path** is a sequence of one-click offers shown after a customer completes checkout. Because their payment method is already saved, they can accept each offer with a single click, no re-entering card details. Each upsell can also have a **downsell**: a fallback offer shown only if the upsell is declined.

A path supports up to **5 steps**, each with an upsell and an optional downsell, so you can build funnels like: purchase > upsell 1 > (downsell 1 if declined) > upsell 2 > confirmation page.

## How the flow works [#](#0-toc-title)

1. The customer completes checkout for a product assigned to a path.
2. Instead of the showing normal confirmation page, they’re redirected to the page you chose for the first offer.
3. On that page, an **accept link** charges them instantly and moves to the next step; a **decline link** either shows the downsell or moves on.
4. When the path ends, the customer lands on the product’s normal confirmation.

## Creating an upsell path [#](#1-toc-title)

![](https://docs-media.publishpress.com/publishpress-cart/upsell-paths-screen-0fc89a48a1.webp)

1. Go to **[PublishPress Cart](https://publishpress.com/links/cart) > Upsell Paths** and click **Add New**.
2. Give the path a title (internal only).
3. In the **Upsell Offers** box, configure the steps. The tabs are labeled **Upsell 1** through **Upsell 5**, each with a matching **↳ Downsell** tab.

## Building the offer page [#](#2-toc-title)

![](https://docs-media.publishpress.com/publishpress-cart/upsell-path-offering-page-e8891a8bf8.webp)

The offer page is a normal WordPress page you design however you like, headline, video, bullet points. The only requirement is two links/buttons using the URLs shown in the offer’s settings:

- **Accept Link**: `https://yoursite.com?sc-upsell-offer=yes` — charges the customer’s saved payment method for this offer and continues the path.
- **Decline Link**: `https://yoursite.com?sc-upsell-offer=no` — skips the charge; if the step has an enabled downsell, the customer is redirected to the downsell page next, otherwise the path continues.

Make the accept button prominent, and keep the decline link visible but modest (“No thanks, I’ll pass”).

## Assigning a product to a path [#](#3-toc-title)

![](https://docs-media.publishpress.com/publishpress-cart/adding-an-upsell-4d10d47170.webp)

The path itself doesn’t list which products use it, that’s set on the product:

1. Edit the product that should trigger the funnel.
2. Open its **Upsell Path** tab.
3. Choose the path under **Select Path** and update.

If you haven’t created any paths yet, this tab shows *“You haven’t created any upsell paths yet!”* with a link to create one.

## Things to know [#](#4-toc-title)

- **One-click charges require a gateway with saved payment methods.** With Stripe, the classic card form and the embedded Payment Element both support one-click upsells; the Stripe-hosted checkout page does **not**, customers checking out through the hosted page skip the upsell path.
- Upsell purchases are recorded as separate child orders linked to the original order, so refunds and reporting stay clean per offer.
- In [Confirmation Settings](https://publishpress.com/knowledge-base/product-configuration/) and per-product [Integrations](https://publishpress.com/knowledge-base/integrations-overview/), you can target customers based on which upsell or downsell they accepted.
- Track conversions per step using the offer pages’ analytics, or the **Tracking** fields for upsell purchases.
