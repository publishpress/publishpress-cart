# Cart MCP request examples

Start with feature discovery, then read field choices or the target object. Resolve ambiguous product names, plans and prices before writing. Use the latest returned revision and read back native state after saving.

| Request | Expected tools and checks |
| --- | --- |
| What can you manage in Cart? | `list-features`; distinguish Free/Pro scopes and permissions. |
| Disable the welcome email for this product. | Find the exact product, `get-product-settings`, preview and patch `_ppcart_disable_welcome_email` as a boolean. Do not confuse this with the global transactional email switch. |
| Enable manual payments and switch Stripe to test mode. | `get-settings`, preview/update the native enable boolean and `test` choice. Do not request gateway credentials. |
| Add a purchase notification to this product. | `get-product-notifications`; retain existing rows, add the required label/recipient/subject/message, preview, save and verify. Configuration sends no email immediately. |
| Charge 20 for this product. | Find the product and read plans; clarify currency, selected plan, sale pricing and recurring interval. Retain other plan rows and hidden identifiers. |
| Hide the title and change the checkout header color. | Product settings discovery, exact checkbox/color types, patch only supplied fields and read back. |
| Create a collection from these products. | Pro feature discovery, create a draft collection, read native collection fields, validate published products and one-time/free plan identifiers. |
| Offer this product as an upsell after purchase. | Distinguish the path definition from its product assignment. Validate product, plan, offer position and published path before applying it. |
| Add a 7.5 percent state tax. | Pro `list-tax-rates`, preview `save-tax-rate` with decimal string `"7.5"` and integer priority; save and check native formatted `"7.50"`. Keep other rates and gateway metadata unchanged. |
| Refund part of this order. | Read the permitted order and remaining balance, preview a decimal-string amount, use the persisted payment identity. Inspect an unconfirmed result before retrying. |
| Make Phone optional and full-width on this product. | Pro checkout-field discovery, patch `phone` with boolean required=false and string size `"12"`; preserve other fields. |
| Add an uploaded PDF download. | Discover product files, retain existing rows, choose an editable secure-upload attachment, preview/save; never request a customer access link. |
| Extend this order's download allowance. | Read order download access, identify its row, preview/update its integer limit or native expiry with the latest revision. |
| Subscribe buyers to a mailing list. | Read active integration choices, confirm service/list/trigger, retain existing rows by `row_id`, preview/save. No remote enrollment occurs during setup. |
| Pause this subscription, then resume it later. | Read the subscription, preview the permitted native transition, then use its current revision. Do not automatically retry uncertain gateway outcomes. |
| Duplicate this product as a draft. | Read its catalog lifecycle revision; preview duplication, check creation/taxonomy/integration permissions, then save. |
| Move this product to trash. | Read catalog lifecycle, preview trash and use the current revision. Permanent deletion is a separate operation on a trashed record. |
| Use Stripe Payment Element. | Read checkout experience and preview/save `element`; leave credentials and other gateway settings unchanged. |
| Upload this product manual. | Send a permitted file as bounded base64 with SHA-256 checksum; assign the resulting attachment using product-file actions. |
| Map buyer email to EMAIL in ActiveCampaign. | Read integrations, retain the correct row ID, replace its map with a destination/source pair using `customer_email`; preserve authentication. |
| Import these coupon codes. | Pro CSV export for the product revision; validate native headers, preview append, then save and read back. |
| Export customer orders for October. | `export-records-csv` with kind `order` and inclusive dates; follow returned pages. Native report and per-record permissions apply to personal and purchase data. |
| Export my custom tax rates. | Pro CSV export with feature `tax-rates`; native seven-column format without gateway IDs. |
| Change the store branding. | Read settings and native choices, preview only the requested white-label controls, then save. |
| Try that operation as a restricted account. | Expect denial when its native capability or object permission is denied, even if the account has the Administrator role. Do not substitute browser automation to bypass that denial. |

| Map the expertise checkout field to Kit. | Discover current integration fields and mapping_sources; select custom.expertise, preview/save the destination/source pair and retain other rows. |
| Create an offline order for this customer. | Read native product/plan choices; preview create-manual-order with explicit customer/account details, then save. No card is charged. |
| Refresh this Stripe subscription. | Read get-stripe-sync-scope; preview and synchronize using its revision. Inspect unconfirmed results before retrying. |
| Send a test receipt. | Preview send-test-email to discover templates; send synthetic data only to configured test/admin recipients. |
| Configure affiliate commissions. | Read Pro affiliate commissions and live plan choices; preview complete ordered rows with the current revision. |
| Mark this affiliate payout paid. | Read its payment revision and related scope; preview/update bookkeeping. This does not transfer money. |

For unsupported operations, use the native admin screen identified in [feature coverage](mcp-feature-coverage.md). Starting a new chat can refresh an AI client's cached schemas; it does not change permissions or fix malformed input.
