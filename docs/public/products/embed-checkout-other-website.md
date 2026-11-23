---
edition: both
nav_order: 2
features: [embedded-checkout, checkout-display]
---

# Embed your checkout on another website

Sell a product on a website that does not run on WordPress, such as a Showit,
Squarespace, or Webflow site, or a plain HTML page. You paste a short code into
that website. The buyer pays on that page, and the order goes to your
PublishPress Cart store like any other order.

To show the checkout on a page of your WordPress site, use the shortcode or the
Checkout block instead. See [Embed an order form anywhere](embed-order-form.md).

## Get the embed code

1. Go to **Cart → Products** and open the product.
2. Publish the product. The **Embed** tab shows the code only for a published product.
3. Open the **Embed** tab.
4. Choose how the checkout shows: **Inline checkout**, **Popup button**, or **Popup link**.
5. Click **Copy**, and paste the code into your website.

The checkout keeps the look of the product, such as its **Primary Color**. To
see the checkout alone, click **Open the embedded checkout in a new tab**.

## Pick a code

The **Embed** tab gives three codes.

| Code | What the buyer sees | Use it when |
|------|---------------------|-------------|
| **Inline checkout** | The checkout form in your page. The form area grows with its content. | You have room for the form on the page. |
| **Popup button** | A plain button that your website styles. Its label is the **Submit Button Text** of the product, or "Buy now". A click opens the checkout in a popup over the page. | You want a buy button in a sales section. |
| **Popup link** | A plain link. On a page with the loader script, it opens the popup. On other pages, it opens the checkout page of the product. | Your site builder button accepts only a link. |

The inline checkout and the popup button codes end with the loader script, a
`<script>` tag that loads `ppcart-embed.js` from your WordPress site. One loader
on a page runs every inline checkout, popup button, and popup link on that page.
A second loader on the same page does nothing, so you can leave it.

The inline checkout code looks like this. Your code has your own product address.

```html
<div data-ppcart-checkout="https://shop.example/products/my-course/"></div>
<script src="https://shop.example/wp-content/plugins/publishpress-cart/public/js/ppcart-embed.js?ver=1.0.1" async></script>
```

## Add the code in Showit

To show the checkout in the page:

1. Add an **Embed Code** element to a section.
2. Paste the **Inline checkout** code into the element.
3. Set the type of that section to **Grow with Content**. The page then grows with the form.
4. Make the element as wide as the section, on desktop and on mobile. A wider element cuts off the right side of the form on phones.

To show the checkout in a popup, use one of these:

- Paste the **Popup button** code into an **Embed Code** element.
- Set the link of a Showit button to the **Popup link**. Add the loader script to the same page too, for example in an **Embed Code** element. Without the loader, the link opens the checkout page of the product.

## Add the code in other site builders

Paste the code into an HTML block, an embed block, or a custom code block. In
Squarespace, use a **Code** block. In Webflow, use a **Code Embed** element. On a
plain HTML page, paste the code where the checkout or the button goes.

## What the buyer sees

- The inline checkout shows in your page. It grows when a message shows and shrinks to the receipt after the order.
- The popup opens over your page and locks the page scroll. The buyer closes it with the close button, the Esc key, or a click outside the popup. On a phone, the popup fills the screen.
- After the order, the receipt shows in the same place as the form. The buyer stays on your page.
- If the product sends the buyer to a thank-you page on another website, that page opens in the whole browser window.
- A buyer who pays with PayPal or with Stripe Checkout (the Stripe payment page) goes to that payment page in the whole browser window.

## Known limits

- Some payment methods of the Stripe Payment Element send the buyer to a bank page to confirm the payment. That bank page opens inside the checkout area, not in the whole window. Cards, wallets, and Link do not do this.
- PayPal checkout with the **On this page (PayPal popup)** setting is not supported inside the embedded checkout. After the payment, the next page can open inside the checkout area without the embedded layout. If you embed the checkout, use the PayPal setting that sends the buyer to PayPal.
- A buyer who is logged in to your WordPress site is usually not logged in inside the embedded checkout. Browsers block the login cookie of your site on another website.
- If your web server or CDN blocks framing for all pages (an `X-Frame-Options` header or a `frame-ancestors` policy), the checkout cannot show on another website. Allow framing for your product pages, or ask your host to do it. A security plugin that sets these headers through WordPress is handled: the embedded checkout lifts only its framing rule.
- Some site builders, such as Wix, put custom code in a box of a fixed height on another domain. There, the inline checkout works but scrolls inside that box. The popup also works, but it stays inside the box and does not cover the whole page. On these builders, put the inline code in a box that is tall enough for the form, or link a button to the normal checkout page of the product.
