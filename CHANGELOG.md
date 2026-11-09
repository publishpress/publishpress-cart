The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and this project adheres to [Semantic Versioning](https://semver.org/).

[Unreleased]

- Added : Permission-checked MCP category/tag deletion, manual order administration, synthetic test emails, Stripe record synchronization and declared checkout-field mappings.
- Added : MCP actions for store settings, products, payment plans, product categories and tags, purchase notifications, secure downloads, integration rules, feature discovery, reports, refunds and subscription transitions with native permissions.

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