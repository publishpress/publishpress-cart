---
title: "Heartbeat"
order: 18
doc_category: publishpress-cart
section: Integrations
---

# Heartbeat

Table of Contents

- [Before you start](#0-toc-title)
- [Step 1: Get your Heartbeat API key](#1-toc-title)
- [Step 2: Connect Heartbeat to Cart](#2-toc-title)
- [Step 3: Add the integration to a product](#3-toc-title)
- [What each action does](#4-toc-title)[Add User To Community](#5-toc-title)[Invite User To Community](#6-toc-title)[Remove User From Community](#7-toc-title)[Remove User From Group](#8-toc-title)
- [Roles, groups, or invitation codes do not show](#9-toc-title)
- [The order notes show an error](#10-toc-title)
- [Check that it works](#11-toc-title)

Use the Heartbeat integration to add customers to your Heartbeat community when they buy a product. You can also invite customers, remove them from the community, or remove them from a group.

Heartbeat is available in [PublishPress Cart](https://publishpress.com/links/cart) Pro.

## Before you start [#](#0-toc-title)

You need:

- A Heartbeat community.
- A Heartbeat API key.
- The roles, groups, or invitation codes that you want to use. Create them in Heartbeat first.

## Step 1: Get your Heartbeat API key [#](#1-toc-title)

1. Log in to Heartbeat.
2. Go to **Admin Settings → API Keys**.
3. Create a new API key.
4. Copy the key.

## Step 2: Connect Heartbeat to Cart [#](#2-toc-title)

1. In WordPress, go to **Cart → Settings → Integrations**.
2. On the **Heartbeat** card, click **Configure**.
3. In **API Key**, paste the key.
4. Click **Save changes**.

![Heartbeat settings window in Cart → Settings → Integrations](https://docs-media.publishpress.com/publishpress-cart/heartbeat-settings-9d2af867bd.webp)

Heartbeat shows in the product **Service** list after you save the **API Key**.

## Step 3: Add the integration to a product [#](#3-toc-title)

1. Go to **Cart → Products** and open a product.
2. Open the **Integrations** tab.
3. Click **+ Add integration**.
4. In **Service**, select **Heartbeat**.
5. In **Trigger**, select the event that runs the integration, for example **Product purchased**.
6. In **Action**, select what Cart does in Heartbeat. The table below shows the fields for each action.
7. Fill in the fields for the action.
8. Click **Publish** or **Update**.

![Heartbeat integration on the product Integrations tab](https://docs-media.publishpress.com/publishpress-cart/heartbeat-product-a72e5c76e9.webp)

| Action | Fields to fill in |
| --- | --- |
| **Add User To Community** | **Select User Role** and **Group** |
| **Invite User To Community** | **Select Invitation Code** |
| **Remove User From Community** | No other fields |
| **Remove User From Group** | **Group** |

For the full list of triggers and the **Restrict by payment plan / purchase type** field, see [Integrations Overview](https://publishpress.com/knowledge-base/integrations-overview/).

Cart loads your roles, groups, and invitation codes from Heartbeat when it shows the product **Integrations** tab. If you add one in Heartbeat, reload the product page to see it.

## What each action does [#](#4-toc-title)

For each action, Cart first checks if Heartbeat has a user with the customer email.

### Add User To Community [#](#5-toc-title)

If the customer is not in Heartbeat, Cart creates the user with:

- The customer email.
- The customer first name and last name.
- The role from **Select User Role**.
- The group from **Group**.

The order notes show “User added to Heartbeat Community”. If the integration has no role, Cart does not create the user. The order notes show “Unable to add user to Heartbeat Community, role not found”.

If the customer is already in Heartbeat, Cart does not change the role. Cart adds the user to the selected **Group** only. The order notes show “User already added to Heartbeat Community”, then “User added to Heartbeat group”.

### Invite User To Community [#](#6-toc-title)

Cart adds the customer email to the selected invitation in Heartbeat. Cart asks Heartbeat to send the invitation email to the customer. Cart sends the invitation even when the customer is already in Heartbeat.

The order notes show “User invited to Heartbeat”. If the integration has no invitation code, the order notes show “No invite code selected”.

### Remove User From Community [#](#7-toc-title)

If the customer is in Heartbeat, Cart deletes the user from the community. The order notes show “User deleted from Heartbeat Community”.

If the customer is not in Heartbeat, Cart does nothing and writes no note.

### Remove User From Group [#](#8-toc-title)

If the customer is in Heartbeat, Cart removes the user from the selected **Group**. The order notes show “User has been removed from Heartbeat group”.

If the customer is not in Heartbeat, or **Group** is empty, Cart does nothing and writes no note.

## Roles, groups, or invitation codes do not show [#](#9-toc-title)

1. Go to **Cart → Settings → Integrations** and open the **Heartbeat** card.
2. Check that the **API Key** is correct.
3. Check that the roles, groups, or invitation codes exist in Heartbeat.
4. Reload the product page.

## The order notes show an error [#](#10-toc-title)

When Heartbeat does not accept a request, Cart writes the error from Heartbeat in the **Order Notes** box. The note has this form: the error type, a dash, then the error message.

If a remove action writes no note, Heartbeat has no user with the customer email. For **Remove User From Group**, also check that **Group** has a value.

## Check that it works [#](#11-toc-title)

1. Buy the product with a test email address.
2. Open the order in **Cart → Orders**. The **Order Notes** box shows a line for the action, for example “User added to Heartbeat Community”.
3. In Heartbeat, find the test user in the community or group.
