---
title: "Refund Order"
order: 36
doc_category: publishpress-cart
section: Subscription
---

# Refund Order

Table of Contents

- [How Cart finds the order](#0-toc-title)
- [Cart refunds the full order](#1-toc-title)
- [Example: refund the first purchase after an upsell](#2-toc-title)
- [Before you start](#3-toc-title)
- [Add the integration to a product](#4-toc-title)
- [What Cart does with the refunded order](#5-toc-title)
- [Cart refunds once for each order](#6-toc-title)
- [Do not select a product from the same order](#7-toc-title)
- [The order notes show an error](#8-toc-title)
- [Check that it works](#9-toc-title)

Use the Refund Order integration to refund a customer’s earlier order when an event occurs on a different product. For example, a customer buys a course. Then the customer accepts an upsell for a bundle that includes the same course. Cart refunds the first order.

Refund Order is available in [PublishPress Cart](https://publishpress.com/links/cart) Pro.

## How Cart finds the order [#](#0-toc-title)

Refund Order does not refund the order that runs it. Cart looks for a different order. The order must match all of these:

- **Same customer.** The order has the same WordPress user account or the same email address as the order that ran the trigger.
- **Same product.** The order contains the product in **Select Product**, as the main product or as an order bump.
- **Status.** The order status is **Paid** or **Completed**. A cash on delivery order with the status **Pending** also matches.

If more than one order matches, Cart refunds the most recent one. Cart refunds one order each time the integration runs.

## Cart refunds the full order [#](#1-toc-title)

The **Select Product** field tells you: “Automatically refund the entire amount of the customer’s most recent order containing this product (use wisely.)”

- Cart refunds the full order amount. You cannot set a partial amount.
- If the product was an order bump, Cart refunds the whole order. This includes the main product of that order.
- Refund Order does not cancel a subscription. To stop a subscription too, add a Cancel Subscription integration.

## Example: refund the first purchase after an upsell [#](#2-toc-title)

You sell two products:

- **Course Basic**.
- **Course Bundle**, which includes Course Basic. You offer it as an upsell after Course Basic.

A customer buys Course Basic and then accepts the Course Bundle upsell. You want Cart to refund Course Basic. On the **Course Bundle** product, add a Refund Order integration:

| Field | Value |
| --- | --- |
| **Service** | **Refund Order** |
| **Trigger** | **Product purchased** |
| **Restrict by payment plan / purchase type** | **Purchased as an upsell** |
| **Select Product** | Course Basic |

When the customer pays for the upsell, Cart finds the Course Basic order of that customer and refunds it.

## Before you start [#](#3-toc-title)

You need:

- [PublishPress Cart](https://publishpress.com/links/cart) Pro.
- The product to refund, and a different product that runs the refund.
- A payment gateway that can refund in Cart, for example Stripe or PayPal.

Refund Order has no settings in **Cart → Settings → Integrations**. You set it up on each product.

**Select Product** lists only published products that have a payment plan. The list is empty on a new product that you have not saved yet. Save the product as a draft first, then add the integration.

## Add the integration to a product [#](#4-toc-title)

1. Go to **Cart → Products** and open the product that starts the refund, for example the upsell product.
2. Open the **Integrations** tab.
3. Click **+ Add integration**.
4. In **Service**, select **Refund Order**.
5. In **Trigger**, select the event that runs the integration, for example **Product purchased**.
6. Recommended: in **Restrict by payment plan / purchase type**, select **Purchased as an upsell**. Then the refund runs only when the customer buys this product as an upsell.
7. In **Select Product**, select the product to refund. This field is required.
8. Click **Publish** or **Update**.

![Refund Order integration on the product Integrations tab](https://docs-media.publishpress.com/publishpress-cart/refund-order-product-2d03c560a4.webp)

For the full list of triggers and the **Restrict by payment plan / purchase type** field, see [Integrations Overview](https://publishpress.com/knowledge-base/integrations-overview/).

## What Cart does with the refunded order [#](#5-toc-title)

| Payment method of the order | What Cart does |
| --- | --- |
| Stripe | Refunds the full amount in Stripe. Cart uses the Stripe mode (**Test** or **Live**) of that order. |
| PayPal | Refunds the full amount in PayPal. |
| Cash on delivery or free | Records a manual refund. Cart does not move money. Return the money to the customer yourself. |
| Other payment gateways | Runs the refund step of that gateway. |

After the refund, Cart:

- Sets the order status to **Refunded**.
- Adds the stock back, if the product uses stock.
- Runs the integrations of the refunded product that use the **Product refunded** trigger.

## Cart refunds once for each order [#](#6-toc-title)

After a successful refund, Cart saves a record on the order that ran the trigger. If the same order runs the trigger again, for example after you change the order status, Cart does not refund again for the same product.

If the refund fails, Cart tries again the next time the trigger runs.

## Do not select a product from the same order [#](#7-toc-title)

Cart refunds the most recent order that matches. If **Select Product** is a product that the customer buys in the same order, for example as an order bump, Cart can refund the new order. Select a product that the customer bought in an earlier order.

## The order notes show an error [#](#8-toc-title)

Cart writes each step to the **Order Notes** box of the order that ran the trigger.

| Order note | What to do |
| --- | --- |
| “No paid orders found for product ID: …” | Cart found no order that matches. Check that the customer used the same email address or account. Check **Select Product**. Check that the old order is **Paid** or **Completed**. |
| “INVALID CHARGE ID” | The old order has no payment transaction ID. Cart cannot refund it at the payment gateway. Refund it manually. |
| “Oops, Stripe … key missing!” | Cart has no Stripe secret key for the mode of the old order. Add the key in **Cart → Settings → Payment Methods**. |
| Another message | The payment gateway did not refund the order. Read the message. |

## Check that it works [#](#9-toc-title)

1. Buy the product to refund with a test email address.
2. Buy the product that has the integration. Use the same email address.
3. Open the new order in **Cart → Orders**. The **Order Notes** box shows “Order refund integration called by product ID: …” and “Order ID: … has been refunded.”
4. Open the old order. The status is **Refunded**.
5. For Stripe or PayPal, find the refund in your payment gateway account.
