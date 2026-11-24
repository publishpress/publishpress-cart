# Shop and Cart — Implementation Spec (v1)

Status: approved for build
Issue: publishpress/publishpress-cart#1309
Edition: Free (`publishpress-cart`)
Design: [design/](design/) (open the `.html` files in a browser)

## 1. Summary

Customers can browse products on a Shop page. They can add several one-time products to a cart and pay for all of them in one checkout.

The current single-product checkout stays as it is. This spec calls it **Buy now checkout**. The product template, fields, bumps, upsells and redirect of each product still apply there.

The cart has its own checkout. This spec calls it **Cart checkout**. Store-wide **Cart settings** control its form, its confirmation and its emails. No product template applies to it.

## 2. Decisions already taken

| # | Decision |
|---|----------|
| D1 | v1 accepts **one-time plans only**: plan `product_type` `''` (one-time) and `free`. Subscriptions, installment plans and pay-what-you-want are not in the cart. Those plans stay in Buy now checkout. |
| D2 | Shop and cart are in the **Free plugin**. Pro extends them through hooks (coupons, tax, quantity, collections). |
| D3 | Each product gets the setting **Show in Shop Page**. The default is **on**. The merchant can turn it off. |
| D4 | Buy now checkout does not change. |
| D5 | One cart checkout makes **one order** with one line item for each product. One payment pays for the order. |
| D6 | Cart checkout does **not** support order bumps or upsell offers (after-checkout offers). This is a product decision, not a v1 limit. Those features stay in Buy now checkout only. |
| D7 | **Add to cart exists only on the Shop.** There is no Add to Cart button on the product page. The product page keeps its Buy now checkout. |
| D8 | Quantity is 1 for each line item in Free. Pro can allow more through a filter. |
| D9 | Cart page and checkout page are separate pages (Shop → Cart → Checkout), like WooCommerce. |
| D10 | The Shop lists Pro collections with a **View** button. Collections cannot go in the cart in v1. |
| D11 | A product with several one-time plans shows a plan dropdown on its Shop card, above **Add to cart**. |
| D12 | **Delete `[ppcart_store]`.** No one is known to use it. The new Shop block and the new shortcode `[ppcart_shop]` replace it. There is no alias. |
| D13 | Free ships without coupons and tax in the cart. Pro cart support follows in a later Pro release. |
| D14 | All work happens on one branch, `feature/shop-cart`, with one PR into `development`. One GitHub issue tracks it: publishpress/publishpress-cart#1309. |

## 3. Terms

Use these words in code, UI and docs. Do not use other words for the same thing.

| Term | Meaning |
|------|---------|
| Shop | The page and block that list products. |
| Shopping cart (code) / Cart (UI) | The list of line items that one visitor wants to buy. In code, use `shopping_cart`. The plugin already uses "cart" for other things (see §15). |
| Line item | One entry in the shopping cart: `product_id`, `plan_id` (the plan `option_id`), `quantity`. |
| Cart-eligible product | A product that can go in the shopping cart (rules in §5.1). |
| Buy now checkout | The existing checkout for one product. |
| Cart checkout | The checkout block with source **Cart**. It pays for the shopping cart. |
| Cart settings | The global settings for the shop, the shopping cart and cart checkout (§7). |

## 4. User flows

1. **Shop → cart → pay.** The visitor opens the Shop page. They click **Add to cart** on two products. The mini cart opens and shows both. They click **Checkout**. They pay one time. They get one order with two line items, one receipt and one purchase email.
2. **Product with several one-time plans.** The Shop card shows a plan dropdown above **Add to cart**. The visitor picks a plan and adds it.
3. **Subscription product.** The Shop card shows **Subscribe**, which links to the product page. Buy now checkout runs as it does today.
4. **Mixed product (one-time + installment plans).** The Shop card offers only the one-time plans. The installment plans stay on the product page (Buy now checkout).
5. **Price changed or product removed.** The cart page and cart checkout show a notice. The system updates or removes the line item before the visitor pays.

## 5. Cart rules

### 5.1 Cart-eligible product

A product is cart-eligible when all of these are true:

- The post is published, and its post type is a Free product type (`ppcart_query_post_types('product')`).
- **Show in Shop Page** is on (§6).
- The sales window is open (`ppcart_is_cart_closed()` is false), and the product is in stock.
- At least one plan is one-time or free (`ppcart_plan()` → `type` is `one-time` or `free`).
- The filter `ppcart_shopping_cart_product_is_eligible` returns true. Pro uses this filter to add collections later.

A plan is cart-eligible when its type is `one-time` or `free`. Filter: `ppcart_shopping_cart_plan_is_eligible`.

### 5.2 Line items

- The key of a line item is `product_id:plan_id`. When the visitor adds the same product with the same plan again, the button shows "Already in cart". It does not add a second line.
- The same product with two different plans makes two line items. This is permitted.
- **Quantity is 1 in Free.** The per-product quantity field (`_ppcart_qty_field`) is a Pro feature. Pro can raise the limit with the filter `ppcart_shopping_cart_item_max_quantity`. (D8)
- The cart holds at most 50 line items. Filter: `ppcart_shopping_cart_max_items`.

### 5.3 Prices

- The shopping cart stores IDs only. It never stores a price that the browser sent.
- The server calculates each price from `ppcart_plan($plan_id, 'current', $product_id)` when it renders the cart, and again when it creates the order. The `'current'` argument uses the sale price when a sale is active.
- If a price changes after the visitor adds the item, the cart shows the notice "The price of {product} changed from {old} to {new}." The cart stores the price that the visitor saw (`seen_price`), but only to show this notice.
- All line items use the store currency.

### 5.4 Limits and stock

- Cart checkout runs the existing purchase-limit and stock checks **for each line item**. Those checks are hooked on `ppcart_before_create_main_order` today (`public/controllers/class-ppcart-public-checkout-controller.php:40-41`). Move each check into a function that takes a product ID, and call it from both paths.
- When the order is paid, stock decreases for each line item by its quantity. (Today `ppcart_maybe_update_stock()` is called without a quantity: `public/class-ppcart-public.php:131`.)

## 6. Product setting: Show in Shop Page

| Item | Value |
|------|-------|
| Location | Product edit → **General** tab → **Page header** card, next to "Hide page title" (`_ppcart_hide_title`; `admin/metaboxes/traits/templates/product-metabox-render-tab-sections.php:22-26`) |
| Label | Show in Shop Page |
| Description | List this product on the Shop page. The product checkout page still works when this is off. |
| Meta key | `_ppcart_show_in_shop` |
| Values | `yes` / `no`. When the meta is missing, the value is `yes`. |
| Helper | `ppcart_product_shows_in_shop( $product_id ): bool` |

**Save rule (important).** The metabox save code deletes the meta of an unchecked checkbox (`product-metabox-save-validate-meta.php:201-208`). A missing meta means `yes`, so an unchecked box would turn back on. To prevent this, write `no` explicitly on `ppcart_after_validate_meta` when the box is unchecked. Write `yes` when it is checked.

When the setting is off:

- The Shop block and `[ppcart_shop]` do not list the product.
- The product cannot be added to the cart (there is no other place to add it).
- The REST endpoint rejects the product.
- Buy now checkout still works.

## 7. Cart settings

There is a new settings tab: **Settings → Shop & Cart** (tab key `shop_cart`). Add it through `$default_setting_tabs` and a new file `admin/settings/traits/options/shop-cart-fields.php`, merged in `get_options_list()`.

| Section | Field | ID | Default |
|---------|-------|----|---------|
| General | Enable shopping cart | `_ppcart_shopping_cart_enable` | on |
| Pages | Shop page | `_ppcart_shop_page_id` | none |
| Pages | Cart page | `_ppcart_cart_page_id` | none |
| Pages | Cart checkout page | `_ppcart_cart_checkout_page_id` | none |
| Pages | Button: **Create missing pages** | — | — |
| Add to cart | After add to cart | `_ppcart_after_add_to_cart` | `mini_cart` (other values: `cart_page`, `checkout`) |
| Checkout form | Show phone field | `_ppcart_cart_checkout_phone` | off |
| Checkout form | Show address fields | `_ppcart_cart_checkout_address` | off (on when Pro tax uses the address) |
| Checkout form | Terms checkbox + terms URL | `_ppcart_cart_checkout_terms`, `_ppcart_cart_checkout_terms_url` | off |
| Checkout form | Pay button text | `_ppcart_cart_checkout_button_text` | "Pay {total}" |
| After payment | Confirmation: message / page / URL | `_ppcart_cart_confirmation`, `_ppcart_cart_confirmation_page`, `_ppcart_cart_redirect` | message |
| After payment | Confirmation message | `_ppcart_cart_confirmation_message` | "Thank you. Check your email for your receipt." |
| Storage | Keep carts for (days) | `_ppcart_shopping_cart_lifetime` | 7 |

**Create missing pages** creates these pages and fills the empty page settings:

- **Shop**, with the Shop block
- **Cart**, with the Cart block
- **Checkout**, with the Cart Checkout block, source Cart

The plugin does not create the pages by itself on update, so existing sites see no change. The new-install onboarding can call the same action.

When **Enable shopping cart** is off, the Mini Cart block renders nothing. The Shop block cards show a **View** button that links to the product page.

## 8. Blocks

All new blocks use the same registration as the existing blocks (`lib/class-ppcart-gutenberg-bootstrap.php`): `block.json` + `render.php` + an editor script built by `wp-scripts`. They render on the server, so the shop and cart work without JavaScript for viewing. JavaScript adds the AJAX actions.

All new blocks accept the `styleSettings` object of the checkout block, but read only `preset` and `accentColor` from it. The Shop, Mini Cart, and Shopping Cart blocks do not offer `surfaceStyle`, `density`, or `cornerRadius`, and ignore saved values for them (see `trait-ppcart-checkout-renderer-styles.php:170-291` for the full set). Move the style resolver into a shared helper so that all blocks look the same. Output the values as CSS custom properties (`--ppcart-accent`, `--ppcart-radius`, …) scoped to the block wrapper.

Each block also has a shortcode for classic themes. Both call the same render function.

### 8.1 Shop — `publishpress-cart/shop`

Shortcode: `[ppcart_shop]` (new). It calls the same render function as the block.

**Remove `[ppcart_store]` (D12):**

- `store_shortcode()` in `public/controllers/page/traits/trait-ppcart-public-page-shortcodes.php` and its `add_shortcode` in `public/controllers/class-ppcart-public-page-controller.php:62`
- the `ppcart_store` entry in the asset list (`public/controllers/class-ppcart-public-asset-controller.php`)
- the templates `public/templates/shortcodes/archive.php` and `public/templates/archive/` (`item.php`, `item-wrapper-start.php`, `item-wrapper-end.php`, `navigation.php`), and their CSS (`.ppcart-archive .cards…` in `public/css/ppcart-public.css` and the SCSS source)
- the hooks `ppcart_product_archive_args`, `ppcart_before_product_list` and `ppcart_after_product_list`. Pro, Compat and the embed plugins do not use them. Remove them from `docs/hooks-manifest.json` and `docs/hooks-manifest.md`.
- the "Product store" section of `docs/public/getting-started/shortcodes.md`
- the changelog gets the line "Removed: `[ppcart_store]` shortcode. Use the Shop block or `[ppcart_shop]`."

The Shop block query gets its own filter, `ppcart_shop_query_args`.

| Attribute | Type | Default |
|-----------|------|---------|
| `perPage` | number | 12 |
| `columns` | 1–4 | 3 |
| `orderBy` | `date`, `title`, `price`, `menu_order` | `date` |
| `categories`, `tags` | term IDs of `ppcart_product_cat` / `ppcart_product_tag` (use `ppcart_live_taxonomy()`) | all |
| `showImage`, `showExcerpt`, `showPrice` | bool | true |
| `includeCollections` | bool | true (when Pro is active) |
| `styleSettings` | object | checkout defaults |

Card contents: image, title, excerpt, price, button, and a **Purchased** badge.

Price label:

- One plan: "$29".
- Several plans: "From $39".
- Recurring plan: "$19 / month".
- Free: "Free".

Card button rule:

| Product state | Button | Action |
|---------------|--------|--------|
| Exactly one eligible plan | Add to cart | Adds the line item (AJAX). Without JS, the button posts a form. |
| Line item already in cart | ✓ In cart | Opens the mini cart |
| Several eligible plans | Plan dropdown + Add to cart | The dropdown lists the eligible plans with prices. The first one is selected. Add to cart adds the selected plan. The "✓ In cart" state applies to the selected plan. |
| No eligible plan (subscription only) | Subscribe | Links to the product page |
| Collection (Pro), or shopping cart disabled | View | Links to the product page |

The Purchased badge must check the product on its own card.

Query: product post types (plus collections), and `_ppcart_show_in_shop` not `no` (a missing meta counts as yes). Filter: `ppcart_shop_query_args`. Pagination uses `the_posts_pagination`.

### 8.2 Mini Cart — `publishpress-cart/mini-cart`

Shortcode: `[ppcart_mini_cart]`

- It shows a cart icon with a count badge, for the site header.
- A click opens a drawer from the right. The drawer shows the line items (remove link), the subtotal, a **Checkout** button and a **View cart** link.
- The count comes from the readable cookie `ppcart_cart_count` (§9). So the block works on cached pages, with no request on page load.
- The drawer loads its content from `GET /cart` when it opens.
- Attributes: `showCount` (true), `showTotal` (false), `opensDrawer` (true; false links to the cart page), `styleSettings`.

### 8.3 Cart — `publishpress-cart/cart`

Shortcode: `[ppcart_cart]`

- Left column: line item rows (thumbnail, product name, plan name, price, **Remove**).
- Right column: summary card (Subtotal, then the Pro slot for discount and tax, then Total), a **Checkout** button and a **Continue shopping** link.
- Notices: price changed, item removed (not eligible any more).
- Empty state: "Your cart is empty." and a **Browse the shop** button (links to the Shop page).
- Hooks for Pro: `ppcart_shopping_cart_after_totals` (the coupon field goes here) and the filter `ppcart_shopping_cart_totals`.

### 8.4 Cart Checkout — existing block `publishpress-cart/checkout-form`, new source "Cart"

- The product picker gets a third option: *Dynamic product* / *Specific product* / **Cart**. Store it as `pid = 'cart'`. This keeps the attribute shape. Pro reads `pid`, so the value `cart` must not reach code that expects a numeric ID. Resolve it before `resolve_product_id()`.
- The block title "Cart Checkout" already fits. Do not rename it.
- Layout: one fixed layout, the form beside the order summary. The style presets and the accent color apply; form skins (product templates) do not. The Checkout page that **Create missing pages** creates holds the block with `pid = 'cart'` only.
- The left column holds: contact fields (first name, last name, email), the optional phone and address fields, the payment method, the terms checkbox and the Pay button.
- The right column is the order summary with an **Edit cart** link.
- Product-level hooks do not run in this mode: order bumps, upsells, custom fields and the product's `template`. That is the reason Pro features attached to those hooks do not leak into cart checkout.

## 9. Cart storage

**Table** `{$wpdb->prefix}ppcart_shopping_carts`, created by `dbDelta` in `PPCart_Upgrade`:

| Column | Type | Note |
|--------|------|------|
| `id` | bigint PK | |
| `cart_key` | char(32), unique | random, from `wp_generate_password(32, false)` |
| `user_id` | bigint, index | 0 for guests |
| `items` | longtext | JSON list of `{product_id, plan_id, quantity, seen_price, added_at}` |
| `meta` | longtext | JSON, for extensions (Pro coupon code) |
| `revision` | bigint unsigned, default 0 | Compare-and-swap token. Each write adds 1 and requires the revision it read. See [architecture.md](architecture.md) §5 |
| `created_at`, `updated_at`, `expires_at` | datetime, index on `expires_at` | |

Why a table, not transients: an object cache can evict a transient before it expires. A table also makes cart abandonment possible later.

**Cookies:**

- `ppcart_cart`: holds the `cart_key`. HttpOnly, Secure (on HTTPS), SameSite=Lax, lifetime = cart lifetime.
- `ppcart_cart_count`: holds the number of items. Not HttpOnly. It is for display only, and the server never reads it.

The system sets both cookies only when the visitor adds the first item. Shop and product pages stay cacheable for visitors with no cart.

**Logged-in users:** the cart row gets the `user_id`. At login (`wp_login`), merge the guest cart into the user's cart, without duplicate keys, and delete the guest row.

**Cleanup:** a daily cron `ppcart_shopping_cart_cleanup` deletes rows where `expires_at < now`. Each update moves `expires_at` forward.

**Code:** `includes/shopping-cart/`

- `class-ppcart-shopping-cart.php`: the domain object. Methods: `add_item`, `remove_item`, `get_items`, `get_totals`, `validate`. It must not know about HTTP.
- `class-ppcart-shopping-cart-store.php`: table and cookie I/O.
- `class-ppcart-shopping-cart-rest.php`: the REST controller.
- `functions-shopping-cart.php`: helpers such as `ppcart_get_shopping_cart()`.

Keep each file under 500 lines.

## 10. REST API

Namespace `ppcart/v1`:

| Method | Route | Body | Returns |
|--------|-------|------|---------|
| GET | `/cart` | — | cart (items with resolved name, plan name, price, image), totals, notices, `nonce` |
| POST | `/cart/items` | `product_id`, `plan_id` | cart |
| DELETE | `/cart/items/{key}` | — | cart |
| PATCH | `/cart/items/{key}` | `quantity` (Pro only; Free rejects values other than 1) | cart |

- Mutating routes need the `X-WP-Nonce` header (`wp_rest`). A cached page can hold an expired nonce, so `GET /cart` returns a fresh one. The JS retries once with it after a `rest_cookie_invalid_nonce` error.
- Errors use `WP_Error` codes: `ppcart_cart_not_eligible`, `ppcart_cart_plan_not_eligible`, `ppcart_cart_full`, `ppcart_cart_disabled`, `ppcart_cart_already_in_cart`.
- Without JavaScript: the Add to cart button posts to the current URL with `ppcart_add_to_cart`, `plan_id` and a nonce. The handler on `template_redirect` adds the item and redirects according to `_ppcart_after_add_to_cart`.
- The cart and cart checkout pages call `nocache_headers()` and define `DONOTCACHEPAGE`.

## 11. Cart checkout and order data

**New controller:** `PPCart_Public_Cart_Checkout_Controller` in `public/controllers/`. Do not reuse `payment-intent-create-payment-intent.php`. That file reads one `ppcart_product_id`, and it dereferences `$order->plan->type` (line 142).

Steps:

1. Validate the nonce and the shopping cart (§5). Run the purchase-limit and stock checks for each line item.
2. Build a `PPCart_Order` with `load_from_shopping_cart( PPCart_Shopping_Cart $cart )`. This is a new method next to `load_from_post()`. It sets the customer fields from POST, which are sanitized against the Cart settings field list (not against one product).
3. Add one item for each line item through `add_item()`:
   - `item_type = 'main'`, `product_id`, `price_id = plan_id`, `product_name`, `price_name`
   - `unit_price`, `quantity`, `subtotal`, `total_amount`
   - `maybe_apply_tax_to_item()` runs for each item. Pro tax already reads `_ppcart_product_taxable` from `$item['product_id']`.
4. Fire `ppcart_shopping_cart_order_loaded` (filter, for Pro coupons). This is the same place where Buy now applies its coupons.
5. Run `finalize_amounts()` (the shared `calculate_*_from_items()` and `calculate_final_amounts()` tail; see architecture.md).
6. Create the payment (§12). Store the pending order. Return `formAction` from the Cart settings.
7. When the order is complete, empty the shopping cart.

**Order-level values for a cart order:**

| Attribute | Value | Reason |
|-----------|-------|--------|
| `product_id` | `0` | Code that reads product meta from `product_id` (bumps, upsells, coupons, notifications) then finds nothing and does nothing. If we set the first item's ID instead, its product rules would apply to the whole order. |
| `product_name` / `item_name` | "{first product} + N more" | Payment descriptions, admin list |
| `plan` | synthetic `{ type: 'one-time', option_id: 'cart' }` | Code that reads `->plan->type` does not crash |
| meta `_ppcart_checkout_type` | `cart` (missing means `product`) | The switch for all consumers below |

**I am not sure** that every item consumer accepts several `main` items. Some code may look for "the" main item. Phase 5 starts with an audit of `item_type === 'main'` reads.

**Consumers to update** (they read the single `product_id` today):

| Area | Change | Where |
|------|--------|-------|
| Integrations "on purchase" | For each item, fire the integrations with that item's `product_id`. Copy Pro's bump pattern (`ppcart-pro-order-bump-trait-run-bump-integrations.php:10-19`). | `public/class-ppcart-public.php:106,130` |
| Stock | For each item, use its quantity | `public/class-ppcart-public.php:131` |
| Emails | Send one purchase email with the global template. The `order_list` merge tag lists all items. Ignore the per-product "disable email" switch for cart orders. Run per-product notifications (`ppcart_do_notifications`) for each item. | `public/class-ppcart-public.php:118`, `includes/functions/users-and-notifications.php:615`, `includes/functions/merge-tags-and-dates.php:137-205` |
| Confirmation / redirect | Read from the Cart settings | `order-save-save-order-to-db.php:195-206`, `checkout-payment-ppcart-process-payment.php:39-81`, `public/templates/functions/checkout-core.php:379-382` |
| Receipts | Already built from items. Check the legacy fallback. | `includes/functions/order-items-and-details.php:15,125` |
| Reports | Group revenue by item `product_id` for cart orders | `admin/reports/templates/admin-reports-page.php:181,214`, `admin/reports/traits/trait-ppcart-admin-reports-data.php:123`, `customer-reports-page.php:170,233` |
| Download revoke on refund | Loop over the items | `includes/files/traits/trait-ppcart-files-order.php:17` |
| Customer bought product | Also query `ppcart_order_items` by `product_id` | `page-customer-customer-bought-product.php:19-28` |
| Admin order screen | Show all line items. Show "Cart order" where the product link was. | order metabox |

## 12. Payments

| Gateway | v1 | Note |
|---------|----|------|
| Stripe on-page (Payment Element / card) | Yes | One PaymentIntent for the total. Metadata: `ppcart_checkout_type=cart`, `ppcart_order_id`, `ppcart_item_count`. Description: "Order #{id} ({n} items)". The Stripe sync must accept `ppcart_product_id = 0`; audit `includes/class-ppcart-stripe-sync.php`. |
| Stripe hosted checkout | No (phase 2) | It sends one line item today (`hosted-checkout-controller-create-checkout-session.php:205-215`). Phase 2 sends one Checkout line item for each cart item. Until then, cart checkout uses the on-page Stripe element even when hosted checkout is on. |
| PayPal on-page | Yes | PayPal gets one purchase unit with the total (`class-ppcart-paypal-onpage.php:311-338`). That already works. Set `description` to the order name. Return and cancel URLs come from the Cart settings. |
| PayPal redirect (Standard) | Yes | Set `item_name` to the order name. Return URLs come from the Cart settings (`trait-ppcart-paypal-requests.php:85-105`). |
| Free total ($0) | Yes | The same free-order path as today, with no payment step. |
| Cash on delivery | Yes, if enabled | Same as Buy now. |

## 13. Extension points (public contract)

New hooks. Treat them as a public contract from the first release:

| Hook | Type | Purpose |
|------|------|---------|
| `ppcart_shop_query_args` | filter (array args, array attributes) | Merchants: Shop query (§8.1) |
| `ppcart_shopping_cart_product_is_eligible` | filter (bool, product_id) | Pro: collections in the cart |
| `ppcart_shopping_cart_plan_is_eligible` | filter (bool, plan, product_id) | Later: subscriptions, pay-what-you-want |
| `ppcart_shopping_cart_item_max_quantity` | filter (int, product_id, plan_id) | Pro: quantity |
| `ppcart_shopping_cart_max_items` | filter (int) | Merchants: line limit (§5.2) |
| `ppcart_shopping_cart_totals` | filter (array, priced cart) | Pro: discount, tax preview |
| `ppcart_shopping_cart_after_totals` | action (priced cart) | Pro: coupon field |
| `ppcart_shopping_cart_order_loaded` | filter (order, priced cart) | Pro: coupons on the order |
| `ppcart_shopping_cart_checkout_fields` | filter (array) | Pro tax: checkout field list |

Each hook has a named caller. `item_added`, `item_removed`, `emptied`, `before_totals` and `order_created` do not ship in v1 (see [architecture.md](architecture.md) §7).

Overridable templates (theme folder `publishpress-cart/`):

- `shopping-cart/cart.php`
- `shopping-cart/mini-cart.php`
- `shopping-cart/drawer.php`
- `shopping-cart/shop-card.php` (new; replaces the deleted `archive/item.php`)

**Pro follow-up work** (Pro repo, after Free ships):

- **Coupons:** a cart coupon on `ppcart_shopping_cart_order_loaded`. Pro coupons are stored per product (`_ppcart_coupons`), so the cart needs a decision on which coupons are valid. Reuse `apply_cart_coupon` (it spreads the discount over all items).
- **Tax:** check that the tax is right for each item. Note a current mismatch: Pro does not tax an item when `_ppcart_product_taxable` is missing, but `ppcart_setup_product()` treats a missing value as taxable.
- **Quantity:** `ppcart_shopping_cart_item_max_quantity` when `_ppcart_qty_field` is on.
- **Collections:** `ppcart_shopping_cart_product_is_eligible`.

## 14. Assets and build

- Add the new blocks and shortcodes to `frontend_assets_needed()` (`public/controllers/class-ppcart-public-asset-controller.php:112-147`).
- Add the entry files to `build:gutenberg` and `start:gutenberg` in `package.json`.
- Put new CSS in `public/css/ppcart-shopping-cart.css`, and load it only when a new block or shortcode is on the page. Use CSS custom properties, not the hard-coded colors of `ppcart-public.css`.
- Put the front-end JS in `public/js/ppcart-shopping-cart.js` (vanilla JS, no jQuery dependency). It handles the add, remove, drawer and count-cookie update.

## 15. Naming conflicts to avoid

The plugin already uses "cart" with other meanings:

- `ppcart_is_cart_closed()` and `$ppcart_cart_closed`: the product sales window
- AJAX `ppcart_update_cart_amount`: recalculates the checkout summary
- `trait-ppcart-public-order-cart.php`, `apply_cart_coupon_to_items`, the block title "Cart Checkout"

So all new code uses `shopping_cart` / `Shopping_Cart`. Only the REST route (`/cart`), the cookies and the UI say "cart".

## 16. Delivery plan

One branch, `feature/shop-cart`, and one PR into `development` (D14). The phases set the order of work and of commits. The PR closes publishpress/publishpress-cart#1309.

| Phase | Scope | Depends on |
|-------|-------|-----------|
| 1 | Show in Shop Page setting; Shop block; `[ppcart_shop]`; delete `[ppcart_store]` and its templates and hooks | — |
| 2 | Shopping cart core: table, store, cookies, cleanup cron, eligibility, REST | 1 |
| 3 | Mini Cart block + shortcode; live Add to cart on Shop cards (AJAX + no-JS fallback) | 2 |
| 4 | Cart block + shortcode | 2 |
| 5 | Cart checkout: block source, controller, order creation, Stripe on-page, consumers in §11 | 2, 7 |
| 6 | PayPal for cart checkout | 5 |
| 7 | Shop & Cart settings tab + Create missing pages | 1 |
| 8 | Docs: public guide, `docs/public/getting-started/shortcodes.md` (`[ppcart_shop]`; remove `[ppcart_store]`), `.scribe.config.yaml` feature record, test catalog entries | 5 |

Later: Stripe hosted checkout for carts, pay-what-you-want, subscriptions, a floating cart button for classic themes, cart abandonment, the Pro items in §13.

## 17. Tests (proposal)

Behaviour tests only (see `AGENTS.md`):

- **Integration:** eligibility rules; add, remove and duplicate keys; price recalculation and the notice; the Show in Shop Page save rule (unchecked box stays off); order built from the shopping cart (items, totals, `product_id = 0`); per-item integrations and stock.
- **Regression (Playwright):**
  - Shop → add 2 products → mini cart → checkout with a Stripe test card → one order with 2 items, both downloads granted, one email.
  - A subscription product shows Subscribe and runs Buy now checkout as before.
- **Smoke:** an existing Buy now product checkout still passes.

## 18. Open questions

None. All questions have answers (D1–D14).

## 19. Out of scope for v1

- Subscriptions, installment plans and pay-what-you-want in the cart
- Coupons and quantity in Free
- Stripe hosted checkout for carts
- Cart abandonment emails
- A filter or category menu that the visitor uses on the Shop page. The block can limit to categories and tags, but the visitor cannot change them in v1.
