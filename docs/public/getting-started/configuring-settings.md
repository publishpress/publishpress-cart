---
edition: both
nav_order: 2
features: [store-settings, transactional-emails, taxes-vat, secure-downloads]
---

# Configuring settings

Open **Cart → Settings**.

## General

Currency, Default Country, Currency Position, Thousand Separator, Decimal
Separator, and Number of Decimals control how prices are formatted and which
country defaults apply at checkout.

## Pages

My Account Page, terms URL, and privacy URL — linked from checkout and emails.

## Branding

Company name and address, plus Logo image for invoices and customer-facing
documents.

## Payment Methods

Configure **Stripe** and **PayPal** in the Payment Methods tab. **Mollie**,
**Square**, and **Razorpay** are available via add-ons. **Cash on Delivery** may
appear in the free plugin when enabled for your store.

Stripe **API** is **Test** or **Live**. After you switch to **Live**, click
**Update** on each subscription product. See
[Using Stripe with recurring payment plans](../subscriptions/using-stripe-with-recurring-payment-plans.md).

### PayPal

PayPal has two **Checkout experience** options.

- **Redirect to PayPal** is the default. The buyer leaves your site, pays on
  PayPal, and comes back to the confirmation page. This option needs the
  **PayPal Email** of the selected mode. Add the **PDT Token** so the
  confirmation page can verify the payment at once.
- **On this page (PayPal popup)** keeps the buyer on your checkout page. PayPal
  opens in a popup. After approval the buyer lands on the confirmation page.
  This option works for one-time payments and subscriptions.

On-page checkout needs the **Client ID** and **Secret** of the selected mode
(**Sandbox** or **Live**). Create them in the PayPal Developer Dashboard under
your app. Without them, the checkout uses the redirect flow.

When you save the PayPal settings with on-page checkout and credentials, Cart
registers a webhook in your PayPal app. The webhook URL is
`https://your-site.example/ppcart-webhook/paypal-rest`. The field shows
"registered" when this worked. PayPal refuses local and non-HTTPS URLs. In that
case the field shows the URL and the events, so you can add the webhook
yourself. On-page checkout still works without the webhook. An hourly check
completes payments that PayPal could not report.

## Downloads

**Logged in user downloads only** restricts file downloads to signed-in customers.
**Download URL slug** sets the custom slug for secure download links. **Auto-expire
download links** may be a Pro or Advanced capability depending on your edition.

## Email

Customize order confirmation and notification templates (the tab is labeled
**Email**, not Emails).

## Invoices

Invoice numbering and layout options.

## Integrations, Reports, Debug, Maintenance, Advanced

**Integrations** — marketing, analytics, and automation connectors. **Reports**
— sales report recipients and email schedules. **Debug** — enable debug logging
when troubleshooting checkout. **Maintenance** — credential encryption and
related tools. **Advanced** — API key and **Disable product template** (API key
is Pro-locked where applicable).

## Tax

The **Tax** tab is part of **PublishPress Cart Pro**. In the free plugin the tab
appears blurred or locked until Pro is active.

Click **Save changes** after editing.
