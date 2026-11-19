The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and this project adheres to [Semantic Versioning](https://semver.org/).

[1.3.0] - 19 Nov, 2026

- Added : Offer an optional PayPal popup checkout for one-time payments and subscriptions, so buyers can reach the confirmation page without leaving your site, #1161, #1168
- Update : Require PHP 8.2 or later, #1141
- Update : Use clearer names for purchase types in integration settings, #1179
- Update : Use the same Cart menu order in Free and Pro, #1181
- Update : Make Cart admin lists more consistent, #1180
- Update : Use yearly billing by default when creating a subscription plan, #1166
- Update : Explain how to use payment plan slugs in checkout links, #1153
- Update : Show all dates by default in Reports and Customer Activity, #1151, #1109
- Update : Remove the duplicate heading from the Reports screen, #1150
- Added : Show a checkmark when a checkout password meets the requirements, #1163
- Fixed : Include Cart products in WordPress category and tag archives, #1105
- Fixed : Wrap long payment plan names in order details, #1106
- Fixed : Show applied coupons on order confirmation receipts, #1111
- Fixed : Prevent a Collections warning when saving an order, #1149
- Fixed : Allow global coupons to discount the full order amount in Pro, #1099
- Fixed : Complete fully discounted checkouts as free orders without charging Stripe, #1077, #1080
- Fixed : Apply coupons to Stripe subscriptions that use flexible billing, #1154, #1155
- Fixed : Import all Stripe subscription invoices when syncing and recover missing renewal orders automatically, #1170, #1171, #626, #1172
- Fixed : Import renewals when a known Stripe subscription still references a previous site URL, #1175
- Fixed : Use the payment time and site timezone for Stripe-synced orders, #1157, #1160
- Fixed : Import refunded Stripe payments as refunded orders and run refund integrations when renewal repair detects a refund, #1192, #1193, #1194, #1195
- Fixed : Let connected Stripe accounts update the webhook signing secret, #1093
- Fixed : Display the Stripe payment form in the site's language, #1086, #1089
- Fixed : Load the Stripe payment form when Google reCAPTCHA is enabled, #1045, #1048
- Fixed : Let administrators copy the REST API key from Cart settings, #1188
- Fixed : Restore the View Log and Clear Log buttons in Cart settings, #644
- Fixed : Recheck a renewed Pro license even when the key stays the same, #1114
- Fixed : Prevent a fatal error when saving an order for a product connected to MemberPress, #1113
- Fixed : Let signed-in users check out when the product asks for a username, #1112
- Fixed : Sign buyers in automatically when checkout creates their account, #1084
- Fixed : Restore missing translations and make Pro messages and more checkout and admin prompts translatable, #1088, #1091, #1094, #1128
- Fixed : Display currency symbols correctly in checkout subscription text, #1087, #1090
- Fixed : Keep the checkout total inside the Amount Due box, #1079, #1082
- Fixed : Make the Show password checkbox reveal the password at checkout, #1078, #1081
- Fixed : Group payment options, identify required fields and controls, and announce field errors to screen readers at checkout, #1131, #1127, #1120
- Fixed : Improve checkout contrast and make keyboard focus visible on checkboxes and radio buttons, #1124, #1122
- Fixed : Improve admin contrast and control sizes, and keep keyboard focus visible above the settings save bar, #1129, #1119
- Fixed : Make Reports search suggestions keyboard-accessible and provide a table for the revenue chart, #1130, #1126
- Fixed : Let keyboard users dismiss help tooltips and reorder repeatable fields, #1125, #1123
- Fixed : Announce the selected customer account tab, shortcode copy results, and email notification settings to screen readers, #1121, #1118, #1117
- Fixed : Set the document language and title for emails, PDFs, and standalone pages, #1132

[1.2.0] - 11 Nov, 2026

- Added : MCP integration with Hub, #1022
- Added : Cart welcome screen after plugin activation, #949
- Fixed : Improve the layout of the Cart dashboard widget, #1069
- Added : Copy product shortcodes with one click, #1035
- Added : Preview Pro features in the Cart Free menu, #1028
- Update : Clarify labels for checkout offers, #1016
- Update : Make the split checkout layout optional and clarify the design settings, #1017
- Update : Prefix Gutenberg block titles with Cart, #1037
- Update : Show progress and result messages when uploading files, #1043
- Update : Show PublishPress Cart Free on the Plugins screen, #1027
- Update : Remove the duplicate navigation menu from the Affiliates screen, #1030
- Fixed : Hide the related-product box when it has no products and support post revisions, #737
- Fixed : Rate-limit Stripe checkout and card setup requests, #830
- Fixed : Improve checkout layout in narrow containers, #1034
- Fixed : Keep checkout shortcodes within the page content width, #1036
- Fixed : Hide Cart branding throughout the admin interface when White Label is enabled, #1038
- Added : Add Cart layout shortcodes and Elementor widgets, #1055, #1056
- Added : Manage global coupons from their own screen while keeping product-specific coupons, #699, #985
- Added : Install Meta Pixel optionally and track the InitiateCheckout event, #999
- Added : Add customers to PublishPress Permissions groups after purchase, #182
- Update : Clarify Sale metabox instructions and scheduling help, #1033
- Fixed : Identify missing required fields and prevent publishing incomplete products, #556, #567
- Fixed : Return native Dashicons choices for white-label settings.
- Fixed : Preserve the first custom tax rate ID returned by the native insert helper.
- Fixed : Allow new affiliates to be added, #1029
- Fixed : Populate order bump choices for new products, #1031
- Fixed : Align the synchronized start date controls and metabox display, #1032

[1.1.0] - 3 Nov, 2026

- Added : Add Flodesk to the integrations catalogue, #1021
- Update : Redesign the product settings box with grouped tabs, sections, and visible help under each field, #560, #561, #562, #563, #568, #571
- Update : Show payment plans first on the Pricing tab, and keep line breaks in the confirmation message, #561, #562
- Update : Explain the key product tabs and Cart screens, #979, #982
- Update : Clarify the Files tab, optional file validation, product integrations, and the mailing list refresh, #983, #984
- Update : Clarify subscriptions in the recurring payment option, #989
- Update : Show pricing, payment types, and sales in the product list, #988
- Update : Move extension controls from the Extensions page into the Features settings, #978
- Update : Remove the Shipping preview tab from Free, #986
- Update : Use WordPress-style pagination on the Customers screen, and stop its page-length dropdown from being squashed, #980, #981
- Update : Give the Maintenance settings tab its own icon, #564
- Update : Tint Settings notices by type so they are easier to read, #950
- Update : Show icons in icon dropdowns and make empty product lists stand out, #1014, #1015
- Update : Show the Related Product box only where it is useful, #1012
- Update : Improve accessibility for WCAG 2.2 AA: field labels, list semantics, dialog focus, checkout step progress, visible keyboard focus, and link text
- Update : Fire the corrected hook names `ppcart_after_order_created`, `ppcart_transaction_id`, `ppcart_checkout_form_validation_messages`, and `ppcart_default_fields_html`. The old misspelled names still fire as deprecated, #1019
- Update : Point the plugin homepage and product links to publishpress.com/cart/, #771
- Update : Rewrite the WordPress.org readme
- Fixed : Fire `ppcart_renewal_uncollectible` for uncollectible renewals, and stop refunded renewals from firing `ppcart_renewal_failed`, #1019
- Fixed : Cache the live Stripe key check so slow Stripe responses do not slow down Cart admin screens, #1009
- Fixed : Stop the false Stripe "required permissions" error for stores connected with Stripe Connect, #971
- Fixed : Show the full Cart menu after you switch from Studiocart to Cart, #970
- Fixed : Add missing direct-access guards to index files

[1.0.1] - 14 Oct, 2026

- Update : Rename Contacts to Customers and link order customer names to customer reports, #569
- Update : Match product editor buttons to the blue Settings buttons, #574
- Update : Use traceable PublishPress links for settings and extension upgrade prompts, #645
- Update : Rename the unlimited subscription option from "Never expires" to "Until canceled", #573
- Update : Replace WordPress.org banners with PublishPress Cart branding, #902

[1.0.0] - 21 Sep, 2026

Initial release of **PublishPress Cart**, forked from [Studiocart](https://studiocart.co/) v2.9.0. This version carries forward the Studiocart v2.9.0 feature set under the PublishPress brand, with updated plugin identity, packaging, and ongoing maintenance by PublishPress.

Prior Studiocart release history (v2.9.0 and earlier) is not included here; see the upstream Studiocart project for historical changelog entries.