# MCP integration

Cart registers private WordPress abilities in the `publishpress-cart/` namespace. Install the WordPress MCP Adapter and use PublishPress Hub to enable the connection and select the actions available to the connected WordPress account. The account must also have the native permissions for each operation. Cart remains usable without the Abilities API or Adapter.

Call `list-features` to find native screen scopes, related tools, edition boundaries and capability requirements. See [feature coverage](mcp-feature-coverage.md) and [request examples](mcp-prompt-examples.md).

## Available operations

| Area | Operations |
| --- | --- |
| Store settings | Discover accepted fields, choices, defaults, effective values and stored values; preview and update allowed settings. |
| Catalog | Find and read products; create and update catalog titles, content, excerpts and publication status. |
| Product notifications | Discover, preview and replace native Additional Purchase Notifications without sending mail. |
| Product configuration | Discover and update reviewed native product controls; read and replace payment plans. |
| Secure downloads | Configure product file rows using existing secure-upload attachments; manage authorized order limits, expiry and revocation without access keys or URLs. |
| Integrations | Discover active native providers and rule fields; preview/save product rules without executing them. Authentication stays in native settings. |
| Pro checkout fields | Patch default/address labels, string-valued sizes and boolean required/disabled flags, preserving other fields and groups. |
| Product taxonomy | List, create, update and delete categories/tags; read and replace a product's term assignments. |
| Orders and subscriptions | Read permitted records; create manual orders and patch customer details; request native refunds, subscription transitions and Stripe synchronization. |
| Reports | Read the native monthly overview with the required reporting and record permissions. |
| Native configuration | Invoice and tax/VAT settings, native checkout presentation, and edition-specific transactional email, shipping and quantity controls. |
| Pro custom tax rates | List, preview, create, patch and delete native custom rates; preserve internal gateway metadata. |
| Pro product rows | Coupons, custom checkout fields, additional bumps, conditional confirmations and affiliate commission rules. |
| Pro catalog | Collections, upsell/downsell paths and subscription upgrade paths, including native product and plan references. |

Pro actions require the active Pro edition and its native modules. Pro uses its bundled Free implementation. Do not activate a separate Free copy alongside it.

## Reading and updating values

Read the relevant settings or record before updating it. Discovery returns the accepted JSON schema and a revision. Send that revision with a write. A stale revision is rejected; read the current values before proposing another change. `dry_run: true` validates and previews supported writes without saving or contacting payment gateways.

Use exact JSON types from discovery. Checkboxes accept booleans, not strings such as `"false"`. Native identifier controls may require numeric strings rather than integers. Empty strings, nulls and zero are distinct; only send values allowed by that field's schema. Settings expose `exists` and `stored_value` separately from the effective `value`, because native email defaults can supply a value for an absent or empty option.

Safe native gateway enable/mode switches are available in store settings; connection credentials remain in wp-admin. Product notification setters replace complete positional rows, using native empty values and checkbox storage. Object patches preserve omitted fields. Ordered list setters replace the complete list, so omitted rows are removed. Payment plans, coupons and custom fields retain omitted attributes for retained identities. Additional bumps, conditional confirmations and affiliate commission rules use complete positional row configurations and native defaults for omitted attributes. Array-valued catalog controls replace their full lists. Malformed types, unknown properties and invalid choices are rejected before writes.

Product and page references are checked against current objects rather than a list frozen when the client connected. References must satisfy the native publication, plan-type and permission requirements. Collection items use one-time or free plans; upgrade paths use recurring plans.

## Permissions and sensitive information

Store settings require `manage_options`. Catalog operations check the registered post type's native capabilities and the individual object's permissions. Publishing requires the native publication capability. Taxonomy operations check native taxonomy and term permissions. Financial actions require the native financial permission and access to the target record. Reports require access to the underlying reporting records. Denied capabilities remain denied even for an Administrator account.

Credentials, password defaults, gateway-generated identifiers, receipt tokens, secure download links and raw gateway payloads are excluded from discovery and record output. Generated payment identifiers are retained internally where native plan and coupon synchronization needs them. MCP actions do not replace WordPress authentication or Hub's application-password restrictions.

## Payments and failures

Refunds, cancellations and subscription transitions use Cart's native payment implementation and the record's stored payment identity. A refund must fit the remaining balance and currency precision. Refund inventory changes require permission to edit the related product. Pause/resume also enforces native owner-or-settings-manager authorization and uses the subscription's stored gateway mode. Only gateways with a native transition implementation are supported. Local validation and preview do not prove that a remote gateway will accept a request.

Gateway operations can succeed remotely before a later local failure. An unconfirmed result must be checked in Cart and the payment provider before retrying. Do not automatically retry a failed refund, cancellation, plan synchronization or coupon synchronization. Local operation locks prevent simultaneous MCP requests for the same payment or plan; they are not distributed transactions with the provider.

## Boundaries

The exposed schemas are the supported interface; this is not an arbitrary options, metadata, SQL or PHP execution API. Credentials, license activation, secret-storage maintenance, raw diagnostic logs and customer checkout/authentication remain in their native screens. Use `get-service-setup` for credential authorization destinations. MCP does not provide arbitrary options, metadata, SQL or PHP execution, or impersonate customer checkout.

## Example requests

- Show the available store settings and their current values.
- Preview changing the store currency and company name, then save the approved values.
- Find my product and show its payment plans before changing its price.
- Create a draft product and assign an existing product category.
- With Pro, create a collection using two existing products and their one-time plans.
- With Pro, preview a coupon and a custom checkout field for a product.
- Show the remaining refundable balance of this order and preview a partial refund.

## Integration tests

Tests live in the existing Codeception Unit and Integration suites under `Mcp`. Set `MCP_ADAPTER_TEST_BOOTSTRAP` to the released Adapter's `mcp-adapter.php` when running Integration tests. Adapter tests execute registered abilities through `WP\MCP\Domain\Tools\McpTool`. Without that bootstrap, adapter-dependent cases are skipped. Run the suite with the actual Pro plugin bootstrap as well as Free to exercise edition-specific controls. Use isolated WordPress databases and test gateway accounts; financial operations can have external effects.

## Checkout grids, downloads and integrations

Default/address grid editing is Pro-only, matching the native editor. Patches retain all unmentioned rows and properties. Checkbox inputs are booleans, but native storage uses presence: false removes the flag rather than storing `"0"`. Labels are strings, and column sizes are native string choices such as `"6"` and `"12"`.

`set-product-files` replaces the ordered file list. Retained `file_id` rows preserve their hidden source and omitted properties. New sources require an `attachment_id` that the account can edit, the `upload_files` capability, and a file under Cart's native allowed secure-download roots. `upload-product-file` accepts a plain filename, strict base64 content and a lowercase SHA-256 checksum. Files must fit both the 2 MiB tool limit and WordPress's upload limit. WordPress validates the MIME type and saves the attachment in Cart's secure directory. Assign the returned attachment ID using `set-product-files`. Uploads are not automatically retried. Reads/previews return source availability, not file paths, source URLs, access keys or signed links. Pro exposes native expiration text and hidden-download settings when active.

Order access uses `get-order-downloads`, `update-order-download` and `revoke-order-download`. A write must name an access row belonging to that order and use its latest revision. Limits accept nonnegative integers or null for unlimited. Expiry accepts the native site-local `YYYY-MM-DD HH:MM:SS` string or null to remove expiry. Existing purchasers' access is separate from the product's configuration.

Integration rules require `manage_options` and edit access to the product. Reads provide the current native service/trigger choices and field schemas. Setters replace the ordered list; retain `row_id` to patch a saved row while preserving hidden credentials and omitted fields. Create a separate row when changing services. All rows are validated before saving. Pro services appear only through their active native providers and dependencies. WordPress-backed course/product choices respect read permission, and assigning them requires object edit permission. No enrollments, subscriptions or webhooks execute during configuration or preview.

Authentication headers remain in native administration and are preserved internally for retained rows. Field maps accept arrays of `{ "destination": "EMAIL", "source": "customer_email" }` pairs using the discovered source choices. Arbitrary metadata names, literals and authentication values are not accepted. Saving a supplied map replaces that map; omitting it retains the native map. Reads return only reviewed field selectors, excluding any existing literals or unsupported selectors. Webhook endpoint inputs require public HTTPS without user information, queries or fragments. User-creation/role rules additionally require user-administration capabilities and reject administrator-level roles, including custom roles with privileged capabilities. Configuration success does not guarantee that an external provider will later accept a rule.

## Catalog lifecycle and service setup

Read `get-catalog-lifecycle` before calling `update-catalog-lifecycle` with its revision. `duplicate` creates a draft product using the native duplicator and clears generated payment identities. It requires product creation/editing, settings management, taxonomy assignment and permissions for copied integration grants. `trash`, `restore` and `delete` apply only to supported catalog kinds, never orders or subscriptions. Permanent deletion requires an already trashed record; restoring also requires publication permission. Previews do not change records.

`get-checkout-experience` and `set-checkout-experience` expose the native `classic`, `element` and `hosted` choices as one setting. The setter requires the current revision and honors native configuration locks. Hosted checkout does not support order bumps or one-click offers.

`get-service-setup` returns authenticated WordPress settings destinations for payment and integration authorization. Enter credentials directly in those native screens; MCP does not accept or return them.

## Pro branding and CSV configuration

White-label controls are available through store settings discovery, including the native Dashicons choices and the switch that hides the settings tab. They remain accessible to authorized settings managers when the tab is hidden.

`export-pro-csv` and `import-pro-csv` support `tax-rates` and product `coupons`. Coupon requests require the product ID. Read the export revision before importing. Imports append rows and preserve existing configuration; coupon codes must be unique. CSV headers and values follow the native controls, with `0`/`1` for checkboxes and comma-separated quoted lists. Tax CSV headers are `Country,State,Postcode,City,Rate,Name,Priority`. Inputs are limited to 100,000 bytes and 100 rows. Every row is validated before saving; `dry_run` makes no writes or provider calls.

Tax imports save through native tax synchronization. If processing fails, the response identifies completed rate IDs and the failed row. Inspect both native rates and Stripe before retrying an uncertain result. CSV output escapes spreadsheet formulas and excludes credentials and generated payment identifiers.

## Customer report CSV

`export-records-csv` exports `order`, `subscription` or `contacts` reports. Order rows include customer names, email, phone, postal address and purchase amounts. The connected account needs native export/report capabilities and read/edit access to each included record. Contact aggregates additionally check every contributing order, not just the most recent one. A denied record rejects the whole response.

Order and subscription filters accept an exact `email`, a primary `product_id`, and inclusive site-local `from`/`to` dates in `YYYY-MM-DD` format. Contact exports accept email only. Follow `next_page` until null; pages default to 50 rows and accept at most 100. Concurrent record changes can affect page membership. Responses are limited to 1 MiB, with at most 20,000 bytes per field; use smaller pages or native administration for larger reports. Contact pages support at most 1,000 contributing orders in total; request a smaller page or use native administration for larger aggregates.

CSV output uses native spreadsheet-formula escaping. Credentials, gateway identifiers, invoice/access tokens, source URLs and arbitrary custom-field values are excluded. Exporting reads records without changing them or contacting payment providers. The action is private and must be selected in Hub before an AI client can use it.

## Manual orders and native refresh

`get-order-details` returns editable customer/address/account fields and a revision. `update-order-details` patches supplied fields only. Linked users and related products/subscriptions need their own permissions. Stripe-owned statuses are locked. Administrative status changes are bookkeeping; use `refund-order` for an actual refund.

`create-manual-order` creates an offline/COD record using the selected product and native plan pricing. Supply the customer details and `user_account` explicitly; zero means an unlinked customer. It does not charge a card or create a gateway subscription. Native creation/status hooks can run configured notifications and integrations. Preview validates the proposal without calculating remote tax, sending mail or writing records.

Use `get-stripe-sync-scope` before `sync-stripe-record`. The scope includes related local records and requires native financial, object, user and invoice-creation permissions. Synchronization uses the stored gateway mode and native Stripe service; provider identifiers are not inputs or outputs. Preview makes no network calls. An unexpected remote mapping or denied write returns an unconfirmed result that needs inspection before retrying.

`send-test-email` sends a native transactional template test to configured administrator/test recipients using synthetic customer data. `dry_run: true` lists current supported template types and sends nothing. Sending is not idempotent; do not retry merely because delivery is delayed.

## Conditional rules, affiliates and bundled extensions

Pro conditional confirmations use `get-confirmations` and `set-confirmations`. Purchase-notification rows also expose native conditions. References to plans, coupons, fields, bumps and offer paths are validated before saving. Configuration does not send notifications or perform a redirect. Editable repeater rows return booleans and string-valued scalar controls in their writable JSON format while retaining native storage internally.

Pro affiliate tools manage profiles, explicit product assignments/exclusions, commission rules, commission/payout bookkeeping and aggregate statistics. Read a revision before changes. Native account links are immutable after linking; role-based affiliates use exclusions rather than explicit product assignments. `user_id: 0` creates an unlinked affiliate, not a WordPress account. Profile deletion additionally requires `delete_users` and does not delete the linked WordPress account. Statistics check contributing records and exclude IP addresses, referrer URLs and tracking links.

Affiliate coupon `affiliates` lists contain personalized affiliate identifiers returned by `get-affiliate`, not numeric profile IDs. Coupon and affiliate identifiers may contain hyphens. Affiliate commission rules use ordered native rows and current plan references. Marking a payout paid is bookkeeping, not a money transfer; it can send the native payout notification. Unconfirmed bookkeeping results require inspection before retrying.

`get-extensions` and `set-extension` read/change bundled affiliate, Mollie, Razorpay and Square modules with a revision. Activation runs required native installation; runtime hooks take effect on the next request. Disabling a gateway does not cancel existing subscriptions. Module configuration is unavailable through MCP while its extension is disabled.


## Integration field mappings

Read `get-product-integrations` before editing rules. Discovery returns the active native services, current choices and `mapping_sources`. Choice snapshots expire after 15 minutes or a relevant configuration/plugin change; rediscover before retrying an expired proposal. Previews validate locally without contacting providers.

Mappings use ordered `{destination, source}` pairs. A selector such as `custom.expertise` refers to a declared non-password checkout field on that product. Password, secret and token fields are unavailable. Removed fields and fields changed to password type resolve to no value, without reading arbitrary order metadata. Saving configuration does not execute the integration. Native checkout/status events deliver configured values to the merchant's service.

Order and subscription trash, restore and permanent deletion remain in native administration.

## Catalog images and slugs

Catalog creation and updates accept `slug` and `featured_image_id`. Use an existing readable, editable image attachment; assigning it also requires `upload_files` and native thumbnail support. Set the image ID to zero to remove it. WordPress normalizes slugs and resolves uniqueness. Preview validates access without saving.

## Order confirmation delivery and uncertain payments

`resend-order-confirmation` requires the revision from `get-order-details`, native financial permissions and access to related objects/users. Confirmation emails must be enabled in native settings. It uses the native recipient and template; preview sends nothing. Delivery is not idempotent: check delivery before retrying an unconfirmed result.

`get-payment-operation` inspects a held refund/subscription operation without exposing provider identifiers. An uncertain operation blocks further MCP payment attempts. After native provider synchronization confirms its result, `resolve-payment-operation` accepts the current operation revision to release the hold. It cannot clear an unknown outcome or retry a refund. Preview does not release the hold.

Monthly overview requires access to every contributing record and supports up to 1,000 records; use native reports for larger scopes.
