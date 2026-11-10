The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and this project adheres to [Semantic Versioning](https://semver.org/).

[1.2.0] - 10 Nov, 2026

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