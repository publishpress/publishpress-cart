---
edition: both
nav_order: 3
features: [order-management]
---

# Managing orders

Orders are created when a customer completes checkout. They are visible to
administrators only. Each order receives a unique order ID.

## Order statuses

| Status | Meaning |
|--------|---------|
| **Pending** | Order received; awaiting payment or gateway confirmation |
| **Unpaid** | Payment not started or still outstanding |
| **Past Due** | Payment failed, declined, or requires authentication (SCA) |
| **Paid** | Payment received; stock reduced; awaiting fulfillment |
| **Failed** | Payment attempt failed and did not complete |
| **Completed** | Order fulfilled and closed |
| **Uncollectible** | Payment could not be collected after retries |
| **Refunded** | Refunded by admin; no further action required |

Past Due orders may briefly appear as Unpaid until the gateway confirms the
failure.

**Canceled** applies to subscriptions (`ppcart_subscription`), not to orders.

## Manually add an order

Use a manual order when you want to grant access or run new-customer
automations without collecting payment through a checkout form.

1. Go to **Cart → Orders**.
2. Click **Add New**.
3. Enter customer details, choose a product and payment option.
4. Set the status — choose **Paid** if integrations should run as for a
   completed purchase.
5. Click **Publish**.

## Refund an order

1. Go to **Cart → Orders** and open the order.
2. In the **Item** section, click **Issue refund**.
3. Confirm the refund in the dialog.

The order status changes to **Refunded** in the orders list.
