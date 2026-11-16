---
title: "MasterStudy LMS"
order: 31
doc_category: publishpress-cart
---

# MasterStudy LMS

Table of Contents

- [Before you start](#0-toc-title)
- [Add the integration to a product](#1-toc-title)
- [What Cart does in MasterStudy LMS](#2-toc-title)
- [Cart does not remove students](#3-toc-title)
- [MasterStudy LMS does not show in the Service list](#4-toc-title)
- [Check that it works](#5-toc-title)

Use the MasterStudy LMS integration to enroll customers in a MasterStudy LMS course when they buy a product.

MasterStudy LMS is available in [PublishPress Cart](https://publishpress.com/links/cart) Pro.

## Before you start [#](#0-toc-title)

You need:

- The MasterStudy LMS plugin, installed and active on the same WordPress site as Cart.
- At least one course in MasterStudy LMS.

MasterStudy LMS shows in the product **Service** list only when the MasterStudy LMS plugin is active. You do not need an API key. MasterStudy LMS has no card in **Cart → Settings → Integrations**.

## Add the integration to a product [#](#1-toc-title)

1. Go to **Cart → Products** and open a product.
2. Open the **Integrations** tab.
3. Click **+ Add integration**.
4. In **Service**, select **MasterStudy LMS**.
5. In **Trigger**, select the event that runs the integration, for example **Product purchased**.
6. In **Course**, select the MasterStudy LMS course.
7. Click **Publish** or **Update**.

The integration also shows **Order Status**. Cart does not use this field. Cart sets the status of the MasterStudy LMS order from the Cart order. See the next section.

For the full list of triggers and the **Restrict by payment plan / purchase type** field, see [Integrations Overview](https://publishpress.com/knowledge-base/integrations-overview/).

Cart loads your courses when it shows the product **Integrations** tab. If you add a course in MasterStudy LMS, reload the product page to see it.

## What Cart does in MasterStudy LMS [#](#2-toc-title)

Each time the integration runs, Cart does these steps:

1. Cart finds the WordPress user with the customer email. If there is no user, Cart creates one with the Subscriber role. The order notes show “New user created (ID: …)”. Cart sends its **New User Welcome** email when that email is on in the Cart email settings.
2. Cart creates a new MasterStudy LMS order for the course.
3. Cart adds the customer to the course as a student.

The MasterStudy LMS order has these values:

| MasterStudy LMS field | Value |
| --- | --- |
| User | The customer |
| Course | The **Course** you selected, with the course price from MasterStudy LMS |
| Status | **Completed** when the Cart order status is **Paid**. **Pending** in all other cases. |
| Payment method | The Cart payment method |
| Total | The Cart order amount |
| Currency | The Cart order currency |

Cart adds the student to the course for both statuses.

## Cart does not remove students [#](#3-toc-title)

The integration has no remove action. Cart enrolls the customer each time a selected trigger occurs. This is also true for **Product refunded** and **Subscription canceled**.

Select only triggers for a purchase, for example **Product purchased**. To remove a student from a course, do it in MasterStudy LMS.

## MasterStudy LMS does not show in the Service list [#](#4-toc-title)

1. Go to **Plugins** in WordPress.
2. Check that MasterStudy LMS is active.
3. Reload the product page.

If **Course** shows **— None Found —**, create a course in MasterStudy LMS first.

## Check that it works [#](#5-toc-title)

1. Buy the product with a test email address.
2. In MasterStudy LMS, find the new order for the test user.
3. Check that the test user is a student of the course.

Cart does not write an order note for the enrollment. If Cart created a new WordPress user, the **Order Notes** box on the order in **Cart → Orders** shows “New user created (ID: …)”.
