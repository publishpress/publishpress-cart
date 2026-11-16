---
title: "Kajabi"
order: 23
doc_category: publishpress-cart
---

# Kajabi

Table of Contents

- [Before you start](#0-toc-title)
- [Step 1: Get the offer URL from Kajabi](#1-toc-title)
- [Step 2: Turn on Kajabi in Cart](#2-toc-title)
- [Step 3: Add the integration to a product](#3-toc-title)
- [What Cart sends to Kajabi](#4-toc-title)
- [Cart does not remove access](#5-toc-title)
- [The order notes show an error](#6-toc-title)
- [Check that it works](#7-toc-title)

Use the Kajabi integration to give customers access to a Kajabi offer when they buy a product. Cart sends the customer details to the webhook URL of the offer in Kajabi. Kajabi then grants the offer to the customer.

Kajabi is available in [PublishPress Cart](https://publishpress.com/links/cart) Pro.

## Before you start [#](#0-toc-title)

You need:

- A Kajabi account.
- A Kajabi offer for each product that you sell.
- The webhook activation URL of each offer.

You do not need an API key. Each product integration uses the URL of one offer.

## Step 1: Get the offer URL from Kajabi [#](#1-toc-title)

1. Log in to Kajabi.
2. Go to **Sales → Offers**.
3. Open the offer that you want to give.
4. Open **More → Webhooks**.
5. Copy the activation URL.

## Step 2: Turn on Kajabi in Cart [#](#2-toc-title)

1. In WordPress, go to **Cart → Settings → Integrations**.
2. On the **Kajabi** card, click **Configure**.
3. Turn on **Enable Kajabi**.
4. Click **Save changes**.

![Kajabi settings window in Cart → Settings → Integrations](https://docs-media.publishpress.com/publishpress-cart/kajabi-settings-592a6f157e.webp)

Kajabi shows in the product **Service** list only when **Enable Kajabi** is on. If you turn it off, Cart stops all Kajabi integrations on all products.

## Step 3: Add the integration to a product [#](#3-toc-title)

1. Go to **Cart → Products** and open a product.
2. Open the **Integrations** tab.
3. Click **+ Add integration**.
4. In **Service**, select **Kajabi**.
5. In **Trigger**, select the event that runs the integration, for example **Product purchased**.
6. In **Webhook URL**, paste the activation URL from Kajabi.
7. Optional: turn on **Kajabi Confirmation Email**.
8. Click **Publish** or **Update**.

![Kajabi integration on the product Integrations tab](https://docs-media.publishpress.com/publishpress-cart/kajabi-product-355f2021c7.webp)

For the full list of triggers and the **Restrict by payment plan / purchase type** field, see [Integrations Overview](https://publishpress.com/knowledge-base/integrations-overview/).

## What Cart sends to Kajabi [#](#4-toc-title)

Cart sends a `POST` request with a JSON body to the **Webhook URL**:

| Kajabi field | Value from the order |
| --- | --- |
| `email` | Customer email |
| `name` | Customer first name and last name |
| `external_user_id` | Customer email |

When **Kajabi Confirmation Email** is on, Cart adds `?send_offer_grant_email=true` to the URL. This tells Kajabi to send its offer email to the customer.

## Cart does not remove access [#](#5-toc-title)

The integration has no remove action. Cart sends the same request each time a selected trigger occurs. This is also true for **Product refunded** and **Subscription canceled**.

Select only triggers for a purchase, for example **Product purchased**. To remove access to an offer, do it in Kajabi.

## The order notes show an error [#](#6-toc-title)

Cart writes the result of each request in the **Order Notes** box. The note starts with “Kajabi webhook response:”. Then it shows the reply from Kajabi, or the error message when the request fails.

If you see an error:

1. Check that **Webhook URL** has the full activation URL of the offer.
2. Check that the offer exists in Kajabi.
3. Buy the product again with a test email address.

## Check that it works [#](#7-toc-title)

1. Buy the product with a test email address.
2. Open the order in **Cart → Orders**. The **Order Notes** box shows a line that starts with “Kajabi webhook response:”.
3. In Kajabi, check that the test email has access to the offer.
