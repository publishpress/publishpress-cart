---
title: "Mailchimp"
order: 26
doc_category: publishpress-cart
section: Integrations
---

# Mailchimp

Table of Contents

- [Before you start](#0-toc-title)
- [Step 1: Get your Mailchimp API key](#1-toc-title)
- [Step 2: Connect Mailchimp to Cart](#2-toc-title)
- [Step 3: Add the integration to a product](#3-toc-title)
- [What Cart sends to Mailchimp](#4-toc-title)
- [Ask customers for consent](#5-toc-title)
- [Lists, groups, or tags do not show](#6-toc-title)
- [Check that it works](#7-toc-title)

Use the Mailchimp integration to add customers to a Mailchimp audience when they buy a product. You can also remove customers from an audience, for example when they ask for a refund.

Mailchimp is available in [PublishPress Cart](https://publishpress.com/links/cart) Free and Pro.

## Before you start [#](#0-toc-title)

You need:

- A Mailchimp account with at least one audience.
- A Mailchimp API key.

## Step 1: Get your Mailchimp API key [#](#1-toc-title)

1. Log in to Mailchimp.
2. Open your profile menu, then go to **Profile → Extras → API keys**.
3. Click **Create A Key**.
4. Copy the new API key.

## Step 2: Connect Mailchimp to Cart [#](#2-toc-title)

1. In WordPress, go to **Cart → Settings → Integrations**.
2. On the **Mailchimp** card, click **Configure**.
3. Paste the key into **Mailchimp API Key**.
4. Click **Save changes**.

![Mailchimp settings window in Cart → Settings → Integrations](https://docs-media.publishpress.com/publishpress-cart/mailchimp-settings-0bc8d18a1c.webp)

MailChimp shows in the product **Service** list after you save the key.

When you save the key, Cart loads your groups and tags from Mailchimp. Cart loads your audiences when you open a product **Integrations** tab.

## Step 3: Add the integration to a product [#](#3-toc-title)

1. Go to **Cart → Products** and open a product.
2. Open the **Integrations** tab.
3. Click **+ Add integration**.
4. In **Service**, select **MailChimp**.
5. In **Trigger**, select the event that runs the integration, for example **Product purchased**.
6. In **Action**, select **Add contact** or **Remove contact**.
7. In **Mailchimp list**, select the audience.
8. Optional: select one or more **Mailchimp tags** and **Mailchimp groups**.
9. Optional: in **Phone merge tag**, enter the merge tag of your phone field in Mailchimp, for example `PHONE`.
10. Click **Publish** or **Update**.

![Mailchimp integration on the product Integrations tab](https://docs-media.publishpress.com/publishpress-cart/mailchimp-product-1b70469023.webp)

For the full list of triggers and the **Restrict by payment plan / purchase type** field, see [Integrations Overview](https://publishpress.com/knowledge-base/integrations-overview/).

## What Cart sends to Mailchimp [#](#4-toc-title)

When the action is **Add contact**, Cart adds or updates the contact with:

| Mailchimp field | Value from the order |
| --- | --- |
| Email | Customer email |
| `FNAME` | Customer first name |
| `LNAME` | Customer last name |
| Your phone merge tag | Customer phone (only when you set **Phone merge tag**) |

Cart sets the contact status to **Subscribed**. Cart then adds the selected groups and tags.

When the action is **Remove contact**, Cart removes the contact from the selected audience. Mailchimp archives the contact.

## Ask customers for consent [#](#5-toc-title)

You can add a contact only when the customer agrees to join your list.

1. On the product **Integrations** tab, turn on **Show opt-in checkbox**.
2. Optional: change the **Opt-in checkbox label**.
3. Optional: turn on **Make opt-in checkbox required**.
4. In the Mailchimp integration, turn on **Require consent**.

When **Require consent** is on, Cart skips the integration for customers who do not tick the checkbox.

## Lists, groups, or tags do not show [#](#6-toc-title)

Cart saves your Mailchimp lists, groups, and tags. If you add new ones in Mailchimp, they do not show in Cart at once.

To load them, click **Refresh mailing lists** on the product **Integrations** tab.

If the lists still do not show, check that the API key in **Cart → Settings → Integrations** is correct.

## Check that it works [#](#7-toc-title)

1. Buy the product with a test email address.
2. Open the order in **Cart → Orders**. The **Order Notes** box shows a line for each Mailchimp step, for example “added to list ID”.
3. In Mailchimp, find the test email in the audience.
