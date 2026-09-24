=== PublishPress Cart - eCommerce for Digital Products ===

Contributors: andergmartins, rizaardiyanto, ojopaul, stevejburge, publishpress
Tags: eCommerce, shopping cart, sales funnel, elementor
Requires at least: 6.7
Tested up to: 7.0
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create order and thank you pages for digital products, programs, and services.

== Description ==

PublishPress Cart helps you sell digital products, programs, events, or services from your WordPress website. It provides checkout pages, payment settings, customer account pages, order records, and integration settings for common payment and marketing workflows.

### Features ###

* **Thank You Pages and Redirects:**
Display a custom thank you page or redirect customers to another website after they complete their purchase.

* **Embed an order form anywhere:**
Use the included shortcode to turn any page on your website into a checkout page.

* **Product launch settings:**
Schedule cart open dates, manually turn a checkout page on or off, close a checkout after a configured number of sales, and redirect closed-cart visitors to a selected page.

* **One-time payments, installment plans and subscriptions:**
Set up multiple payment options for a product so customers can choose from the payment plans you configure.

* **Mailing list integrations:**
Choose which mailing list or tag to add a buyer to on a per-product basis when a supported marketing service is configured.

How to Use PublishPress Cart
[youtube https://www.youtube.com/watch?v=uLDxhqXMhLY]

**Want product updates and support resources?** Visit [PublishPress Cart](https://publishpress.com/publishpress-cart/).

== External services ==

PublishPress Cart can connect to third-party services when you enable and configure the related feature. These connections are optional unless you choose the related payment gateway, spam-protection feature, analytics feature, or marketing integration.

= Stripe =

Stripe is used to process card payments when Stripe is enabled. During checkout, PublishPress Cart sends order, customer, amount, currency, and payment metadata needed to create and confirm Stripe payments. Stripe may return payment, charge, refund, subscription, and webhook event data to the site. When a Stripe publishable key is saved, checkout and product pages also load Stripe.js from https://js.stripe.com/v3/.

Service: Stripe, Inc. Terms: https://stripe.com/legal. Privacy: https://stripe.com/privacy.

= Stripe Connect (PublishPress) =

The optional Stripe Connect button uses a PublishPress connection service at https://publishpress.com to complete Stripe OAuth. When you start Connect from plugin settings, the plugin sends the site URL, return URL, connection state, and an encrypted site credential needed to finish account linking. PublishPress returns Stripe account identifiers used to process payments. This happens only when you choose Connect Stripe.

When Stripe Connect is used without PublishPress Cart Pro, Stripe applies a 2% application fee on those connected payments. That fee is not applied when PublishPress Cart Pro is active.

Service: PublishPress LLC. Terms: https://publishpress.com/terms-of-service/. Privacy: https://publishpress.com/privacy-policy/.

= PayPal =

PayPal is used to process PayPal payments and subscriptions when PayPal is enabled. During checkout and subscription management, PublishPress Cart sends order, customer, amount, currency, subscription, and transaction data needed to create, verify, refund, pause, resume, or cancel PayPal transactions.

Service: PayPal, Inc. Terms: https://www.paypal.com/legalhub/useragreement-full. Privacy: https://www.paypal.com/privacy.

= Google reCAPTCHA =

Google reCAPTCHA is used to help validate checkout form submissions when reCAPTCHA is enabled. PublishPress Cart sends the reCAPTCHA response token and the visitor IP address to Google for verification. When a reCAPTCHA site key is saved, checkout forms also load https://www.google.com/recaptcha/api.js from Google.

Service: Google LLC. Terms: https://policies.google.com/terms. Privacy: https://policies.google.com/privacy.

= SendFox =

SendFox is used to add buyers to configured mailing lists when the SendFox integration is enabled. PublishPress Cart sends subscriber and order-related data required by the configured integration, such as email address, name, and selected list information.

Service: SendFox / Sumo Group, Inc. Terms: https://sendfox.com/tos. Privacy: https://sendfox.com/privacy.

= ActiveCampaign =

ActiveCampaign is used to sync buyers to configured lists and tags when the ActiveCampaign integration is enabled. PublishPress Cart sends subscriber and order-related data required by the configured integration, such as email address, name, and selected list or tag information.

Service: ActiveCampaign, LLC. Terms: https://www.activecampaign.com/legal/terms-of-service. Privacy: https://www.activecampaign.com/legal/privacy-policy.

= Mailchimp =

Mailchimp is used to sync buyers to configured audiences, groups, or tags when the Mailchimp integration is enabled. PublishPress Cart sends subscriber and order-related data required by the configured integration, such as email address, name, audience, group, and tag information.

Service: The Rocket Science Group LLC d/b/a Mailchimp. Terms: https://mailchimp.com/legal/terms/. Privacy: https://www.intuit.com/privacy/statement/.

= Kit =

Kit is used to sync buyers to configured forms and tags when the Kit integration is enabled. PublishPress Cart sends subscriber and order-related data required by the configured integration, such as email address, name, selected form, selected tag, and mapped custom field information.

Service: ConvertKit LLC d/b/a Kit. Terms: https://kit.com/terms. Privacy: https://kit.com/privacy.

= MemberVault =

MemberVault is used to grant buyer access to configured products when the MemberVault integration is enabled. PublishPress Cart sends customer and product access data required by the configured integration, such as email address, name, and selected MemberVault product information.

Service: MemberVault. Terms: https://membervault.co/terms. Privacy: https://membervault.co/privacy.

= Google Analytics and Facebook Ads =

When analytics or ads tracking integrations are enabled, PublishPress Cart can output the tracking data needed for purchase and lead events, such as order amount, currency, product information, and transaction identifiers. These services are only used when you configure the related tracking IDs or settings.

Google terms: https://policies.google.com/terms. Google privacy: https://policies.google.com/privacy.
Meta terms: https://www.facebook.com/legal/terms. Meta privacy: https://www.facebook.com/privacy/policy/.

== Development and source code ==

Human-readable Gutenberg sources ship in this plugin. The files in `includes/integrations/gutenberg/build/` are minified with npm and `@wordpress/scripts` 35.0.0, which runs webpack.

To rebuild them, use Node.js 20.19 or newer on the 20 line, or Node.js 22.13 or newer. From the plugin directory:

1. `npm install @wordpress/scripts@35.0.0`
2. `npx wp-scripts build includes/integrations/gutenberg/blocks/checkout-form/order-form.js includes/integrations/gutenberg/blocks/account-page-builder/account-blocks.js includes/integrations/gutenberg/blocks/account-page-builder/account-page-view.js --output-path=includes/integrations/gutenberg/build`

A development checkout that includes `package.json` can run `npm install` and then `npm run build:gutenberg` for the same output. `npm run start:gutenberg` rebuilds those files while you edit.

* `includes/integrations/gutenberg/blocks/checkout-form/order-form.js` builds `includes/integrations/gutenberg/build/order-form.js`.
* `includes/integrations/gutenberg/blocks/account-page-builder/account-blocks.js` builds `includes/integrations/gutenberg/build/account-blocks.js`.
* `includes/integrations/gutenberg/blocks/account-page-builder/account-page-view.js` builds `includes/integrations/gutenberg/build/account-page-view.js`.

Shared helpers are under `includes/integrations/gutenberg/blocks/_shared/`.

== Third-party assets ==

* Montserrat by The Montserrat Project Authors, SIL Open Font License 1.1. License: `public/fonts/OFL.txt`. https://github.com/JulietaUla/Montserrat
* balloon.css 1.2.0 by Claudio Holanda, MIT. License: `admin/assets/libs/balloon.LICENSE.txt`. https://github.com/kazzkiq/balloon.css

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the Plugins screen in WordPress.
3. Use the Settings -> PublishPress Cart screen to configure the plugin.

== Frequently Asked Questions ==

= What payment processors can I use with PublishPress Cart? =
PublishPress Cart supports Stripe and PayPal. [PublishPress Cart Pro](https://publishpress.com/publishpress-cart/) is a separate plugin with additional payment gateway options.

= Does PublishPress Cart work in my language? =
PublishPress Cart can be translated with WordPress translation files or a translation plugin such as [Loco Translate](https://wordpress.org/plugins/loco-translate/).

= What integrations are available? =
PublishPress Cart works with ActiveCampaign, Mailchimp, MemberVault, SendFox, MailPoet, Facebook Ads events, and Google Analytics. [PublishPress Cart Pro](https://publishpress.com/publishpress-cart/) is a separate plugin with additional integrations.

= I have a question not listed here. =
Contact us via [PublishPress support](https://publishpress.com/publishpress-cart/).

== Screenshots ==

1. Configure checkout pages for products and services.
2. Embed an order form on a WordPress page.
3. Schedule when an order page is accessible and select what visitors see when the cart is closed.
4. Configure payment options including one-time payments, installment plans, and subscriptions.
5. Configure what happens after an order is placed.

== Changelog ==

= 1.0.0 =

Initial release of **PublishPress Cart**, forked from [Studiocart](https://studiocart.co/) v2.9.0. This version carries forward the Studiocart v2.9.0 feature set under the PublishPress brand, with updated plugin identity, packaging, and ongoing maintenance by PublishPress.

Prior Studiocart release history (v2.9.0 and earlier) is not included here; see the upstream Studiocart project for historical changelog entries.
