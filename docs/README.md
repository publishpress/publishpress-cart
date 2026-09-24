# PublishPress Cart documentation

## Feature catalog

Sellable capabilities and customer-facing product areas. The table’s
**Historical source** column is a legacy map to the original Studiocart
documentation, not the current product brand.

**Features** describe what the plugin can do for store owners and customers.
Canonical definitions live in the `features:` block of
[`.scribe.config.yaml`](../.scribe.config.yaml). Field rules:
[dev/schemas/features.md](dev/schemas/features.md). Long-form user and
developer detail lives under `docs/public/` and `docs/dev/`.

**Modules** (in `.scribe.config.yaml` and test-case frontmatter) group tests by
code area — they are not the same as features.

| ID | Name | Edition | Historical source |
|----|------|---------|-------------------|
| core-checkout | Core checkout & payments | both | Getting Started |
| payment-plans | Payment plans | both | Getting Started, Products |
| subscriptions | Subscriptions | both | Subscriptions |
| coupons | Coupons & discounts | pro | Products |
| order-bumps-upsells | Order bumps & upsells | pro | Products (Upsell Paths) |
| secure-downloads | Secure download delivery | both | Products |
| checkout-display | Checkout display & embedding | both | Products, Templates, Getting Started |
| gutenberg-blocks | Gutenberg checkout & customer account | both | Templates |
| quantity-pricing | Quantity and bulk pricing | pro | Products |
| product-collections | Product collections & bundles | pro | Products |
| shipping | Shipping | pro | Products, General |
| custom-fields | Custom checkout fields | pro | Products, General |
| taxes-vat | Taxes & VAT | pro | Taxes |
| order-management | Order management | both | Orders, Getting Started |
| integrations | Integrations and automation triggers | pro | Integrations |
| webhooks | Webhooks | pro | Integrations, Developers |
| transactional-emails | Transactional emails | both | General |
| store-settings | Store settings | both | General, Getting Started |
| developer-hooks | Developer hooks and filters | both | Developers |
| developer-rest-api | Developer REST API | pro | Developers |

## Testing

Catalog conventions and suite runbooks live under
[dev/testing/](dev/testing/README.md). Case markdown follows Scribe v2
([testing/schema.md](testing/schema.md)); feature field rules are in
[dev/schemas/features.md](dev/schemas/features.md). Run
`composer scribe:doctor` to check catalog conformance.
