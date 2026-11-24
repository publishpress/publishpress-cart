# Shop and cart architecture

Status: synthesized design, ready for build
Spec: [spec.md](spec.md) (decisions D1 to D14)
Issue: publishpress/publishpress-cart#1309
Branch: `feature/shop-cart`

This file tells an implementer what to build for each phase of spec §16. It starts with the code that callers write. Then it gives the data shapes, the files, and the rules for completion, concurrency, HTTP, and hooks. The spec stays the source for product behavior. This file is the source for structure.

All `file:line` references were checked on `feature/shop-cart` at commit `bbe2e59`.

## 1. Caller usage

### 1.1 Shop card and add to cart

The Shop block and `[ppcart_shop]` call one render function.

```php
echo ppcart_render_shop( $attributes );
```

Inside its loop, `PPCart_Shop` asks the Catalog for one card per product and prints `shopping-cart/shop-card.php`. The template decides nothing. It prints the card array.

```php
$card = $catalog->shop_card( $product_id, $priced_cart_or_null );
ppcart_get_template( 'shopping-cart/shop-card.php', [ 'card' => $card ] );
```

The card form posts `ppcart_add_to_cart` (product ID), `plan_id`, and a form nonce. `ppcart-shopping-cart.js` catches the submit and sends `POST /ppcart/v1/cart/items`. Without JavaScript, the `template_redirect` handler runs the same Store operation and redirects per `_ppcart_after_add_to_cart`. The no-JS path sends `mini_cart` to the Cart page, because a redirect cannot open a drawer.

The Shop reads a cart only when the request already has a `ppcart_cart` cookie or a signed-in user. Browsing creates no row and no cookie.

### 1.2 REST controller

```php
$product_id = absint( $request['product_id'] );
$plan_id    = sanitize_text_field( $request['plan_id'] ); // option_id text, never absint
$cart = $store->update(
	static fn ( PPCart_Shopping_Cart $cart ) => $cart->add_item( $product_id, $plan_id ),
	true // create the row and cookies when the visitor has none
);
if ( is_wp_error( $cart ) ) {
	return $rest->error_response( $cart ); // adds HTTP status and data.cart
}
return rest_ensure_response( $view->response( $cart, $request ) );
```

`update()` loads the row, applies the one operation, prices the result, and writes with a compare-and-swap on `revision`. On a revision miss it reloads and replays the operation, up to 3 tries. A controller never saves a cart object that it read earlier.

### 1.3 Mini Cart and Cart

```php
echo ppcart_render_mini_cart( $attributes ); // icon + count shell from the ppcart_cart_count cookie
echo ppcart_render_shopping_cart( $attributes ); // Cart page, reads $store->read()
```

The Mini Cart does no request on page load. The drawer calls `GET /cart?fragments=drawer` when it opens and inserts the server HTML. The Cart page and the drawer print the same row and summary templates.

### 1.4 Cart checkout controller builds the order

```php
$input = PPCart_Shopping_Cart_Checkout_Input::from_post( $settings->checkout_fields() );
if ( is_wp_error( $input ) ) {
	return $input;
}

// Read, re-price, run ready_to_pay(), and claim this revision in one CAS write.
$cart = $store->claim_checkout( $input->seen_revision(), $input->customer() );
if ( is_wp_error( $cart ) ) {
	return $cart; // ppcart_cart_changed carries data.cart; the form redraws, no charge
}

$order = new PPCart_Order();
$order->load_from_shopping_cart( $cart, $input->customer() );
// persist the pending order through the existing persistence path, then pay
$result = $controller->pay( $order, $input->payment_method() );
```

The order builder receives only `PPCart_Shopping_Cart_Priced`. It never reads the request for items or amounts. The gateway amount is the persisted `$order->amount`.

### 1.5 Pro add-on (outside Free)

```php
add_filter( 'ppcart_shopping_cart_item_max_quantity', static function ( $max, $product_id, $plan_id ) {
	return get_post_meta( $product_id, '_ppcart_qty_field', true ) ? 20 : $max;
}, 10, 3 );

add_action( 'ppcart_shopping_cart_after_totals', [ Pro_Cart_Coupons::class, 'render_field' ] );
add_filter( 'ppcart_shopping_cart_totals', [ Pro_Cart_Coupons::class, 'preview' ], 10, 2 );
add_filter( 'ppcart_shopping_cart_order_loaded', static function ( $order, $cart ) {
	Pro_Cart_Coupons::apply( $order, $cart->meta( 'pro_coupon_code' ) ); // edits item amounts
	return $order;
}, 10, 2 );

// Pro's own endpoint stores the code through the public Free helper.
ppcart_shopping_cart_set_meta( 'pro_coupon_code', $validated_code );
```

### 1.6 Completion consumer

```php
public function do_order_complete_functions( $status, $order_data, $order_type = 'main' ) {
	if ( ppcart_order_is_shopping_cart( $order_data ) ) {
		foreach ( ppcart_shopping_cart_order_contexts( $order_data ) as $context ) {
			ppcart_do_integrations( $context['product_id'], $context );
			ppcart_maybe_update_stock( $context['product_id'], 'decrease', $context['quantity'] );
			ppcart_do_notifications( $context );
		}
		return;
	}
	// Buy now path, unchanged
}
```

`ppcart_order_complete` fires once for a cart order. Free's own callbacks loop the item contexts. The purchase email stays one send on `ppcart_run_after_integrations`.

## 2. Data shapes

Each shape has one owner. Other code reads it and does not build its own copy.

| Shape | Owner | Lives in |
|-------|-------|----------|
| Line item | `PPCart_Shopping_Cart` | `items` JSON column |
| Stored row | `PPCart_Shopping_Cart_Store` | `{prefix}ppcart_shopping_carts` |
| Priced cart | `PPCart_Shopping_Cart::validate()` | memory only |
| REST cart response | `PPCart_Shopping_Cart_View::response()` | HTTP |
| Shop card | `PPCart_Shopping_Cart_Catalog::shop_card()` | template input |
| Order built from a cart | `PPCart_Order::load_from_shopping_cart()` | order post, `ppcart_order_items` |
| Item context | `ppcart_shopping_cart_order_contexts()` | completion callbacks |

### 2.1 Line item

```json
{ "product_id": 101, "plan_id": "standard:personal", "quantity": 1, "seen_price": "29.00", "added_at": "2026-11-24T08:10:00.123456Z" }
```

- The key is `product_id . ':' . plan_id`. It is derived, not stored. Split a key at the first colon only, because an `option_id` can contain a colon.
- `seen_price` is the server price the visitor last saw. It drives the price-changed notice only. It is never charged.
- `added_at` is UTC with microseconds. It marks the line generation. A duplicate add does not change it. Completion uses key plus `added_at` to remove only the lines it paid for.

### 2.2 Stored row

| Column | Type | Note |
|--------|------|------|
| `id` | bigint unsigned PK AI | Order meta stores this ID, not the bearer key |
| `cart_key` | char(32) UNIQUE | `wp_generate_password(32, false)`, the `ppcart_cart` cookie value |
| `user_id` | bigint unsigned, KEY | 0 for guests |
| `items` | longtext | JSON list of line items, in insertion order |
| `meta` | longtext | JSON object. Reserved keys start with `_` (`_notices`, `_checkout_claim`). Extension keys do not |
| `revision` | bigint unsigned NOT NULL DEFAULT 0 | Compare-and-swap token. Each write adds 1 |
| `created_at`, `updated_at` | datetime (GMT) | `updated_at` stays a real time for later abandonment reports |
| `expires_at` | datetime (GMT), KEY | `updated_at` plus `_ppcart_shopping_cart_lifetime` days |

The write is one statement:

```sql
UPDATE {table} SET items = %s, meta = %s, revision = revision + 1, updated_at = %s, expires_at = %s
WHERE id = %d AND revision = %d
```

Zero affected rows means another request wrote first. The Store reloads and replays the operation. If the new encoded `items` and `meta` equal the loaded values, the Store skips the write and returns the loaded state.

The table name is `$wpdb->prefix . 'ppcart_shopping_carts'`, built in one Store method. The live-table map is not used: it exists to point at leftover StudioCart tables, and this table has no leftover name. So Compat needs no change.

### 2.3 Priced cart

The priced cart is the immutable boundary between the cart and everything that reads prices. Shop cards, the View, the checkout gate, and the order builder read it. Only `PPCart_Shopping_Cart::validate()` makes it, and it holds validated server prices only.

```php
final readonly class PPCart_Shopping_Cart_Priced_Line {
	public function __construct(
		public string $key,
		public int $product_id,
		public string $plan_id,
		public int $quantity,      // already within [1, max_quantity]
		public int $max_quantity,
		public float $unit_price,  // ppcart_plan( $plan_id, 'current', $product_id )
		public float $subtotal,    // unit_price * quantity, computed once here
		public string $product_name,
		public string $plan_name,
		public int $image_id,
		public string $permalink,
		public string $added_at,
	) {}
}

final readonly class PPCart_Shopping_Cart_Notice {
	public function __construct(
		public string $code,       // 'price_changed' | 'removed'
		public string $key,
		public string $message,
		public ?string $old_price,
		public ?string $new_price,
	) {}
}

final readonly class PPCart_Shopping_Cart_Priced {
	/** @param list<PPCart_Shopping_Cart_Priced_Line> $lines @param list<PPCart_Shopping_Cart_Notice> $notices */
	public function __construct(
		public int $cart_id,       // 0 = no row yet
		public int $revision,      // the revision these prices belong to
		public array $lines,
		public array $notices,
		public array $meta,        // extension keys only
		public bool $changed,      // validate() repaired lines in this request
		public string $currency,
	) {}

	public static function empty(): self;
	public function count(): int;                  // number of lines (badge contract)
	public function is_empty(): bool;
	public function has_line( string $key ): bool;
	public function subtotal(): float;
	public function meta( string $key ): mixed;
	/** @return list<array{key:string,added_at:string}> lines this order pays for */
	public function paid_lines(): array;
	/** Display totals. Output of ppcart_shopping_cart_totals. Never charged. */
	public function get_totals(): array;
	/** Checkout gate (section 6.3). */
	public function ready_to_pay( int $seen_revision, array $customer ): true|WP_Error;
}
```

`ready_to_pay()` refuses in this order:

1. The cart is empty. Code `ppcart_cart_empty`.
2. `$this->changed` is true, so this request repaired the cart. Code `ppcart_cart_changed`.
3. `$seen_revision !== $this->revision`, so the cart changed after the form rendered. Code `ppcart_cart_changed`.
4. A purchase limit fails for a line, through `ppcart_check_product_purchase_limit()`. The spec code for that limit.
5. Stock fails. The check sums the quantities of all lines of one product before it compares with that product's stock, through `ppcart_check_product_stock()`.

Notices that are stored in `_notices` from an earlier repair do not block payment. They are for display.

### 2.4 REST cart response

`GET /cart` and every mutating route return this shape.

```json
{
	"revision": 7,
	"count": 2,
	"currency": "USD",
	"items": [
		{
			"key": "101:standard:personal",
			"product_id": 101,
			"plan_id": "standard:personal",
			"product_name": "Writer Toolkit",
			"plan_name": "Personal",
			"quantity": 1,
			"max_quantity": 1,
			"unit_price": "29.00",
			"subtotal": "29.00",
			"price_html": "$29.00",
			"image": { "url": "https://shop.example/toolkit-150x150.webp", "alt": "Writer Toolkit" },
			"permalink": "https://shop.example/products/toolkit/"
		}
	],
	"totals": {
		"subtotal": "68.00",
		"total": "68.00",
		"rows": [
			{ "type": "subtotal", "label": "Subtotal", "amount": "68.00", "html": "$68.00" },
			{ "type": "total", "label": "Total", "amount": "68.00", "html": "$68.00" }
		]
	},
	"notices": [
		{ "code": "price_changed", "key": "101:standard:personal", "message": "The price of Writer Toolkit changed from $19.00 to $29.00.", "old_price": "19.00", "new_price": "29.00" }
	],
	"after_add": "mini_cart",
	"links": { "shop": "/shop/", "cart": "/cart/", "checkout": "/checkout/" },
	"nonce": "<fresh wp_rest nonce>",
	"fragments": { "drawer": "<div data-ppcart-drawer>…</div>" }
}
```

- Money at the HTTP boundary is a decimal string in store decimals.
- `fragments` appears only when the request asks with `?fragments=drawer,cart`. Each fragment is the output of a theme-overridable template, printed from this same data.
- `totals` is the output of `ppcart_shopping_cart_totals`. Pro adds rows with `type` `discount` or `tax`.
- The response never holds `cart_key`, `user_id`, or `seen_price`.
- A cart error is a `WP_Error` with `data.status` and `data.cart` in this shape, so the JavaScript can redraw without a second request.
- The checkout form prints `revision` into a hidden field `ppcart_cart_revision`. That value is the `seen_revision`.

### 2.5 Order built from a cart

Order attributes:

| Attribute | Value | Persisted |
|-----------|-------|-----------|
| `checkout_type` | `'cart'` | Yes, as `_ppcart_checkout_type`. This is the only flag that identifies a cart order |
| `product_id` | `0` in memory | **No.** `order-persistence-create.php:22` writes only truthy attributes. On load the value is the constructor default `null` (`models/templates/ppcart-order---construct.php:20`) or `''` from `ppcart_setup_order()` (`includes/functions/product-and-order-setup.php:310`) |
| `option_id`, `plan_id` | `'cart'` | Yes |
| `plan` | `(object) [ 'type' => 'one-time', 'option_id' => 'cart', 'name' => item_name, 'stripe_id' => '', 'price' => total, 'initial_payment' => total ]` | Yes, flattened by `prepare_meta_value()`. Rehydrated on load (below) |
| `product_name`, `item_name` | "{first product} + N more", or the first product name for one line | Yes |
| `quantity` | `1`. Items carry their own quantities | Yes |
| `shopping_cart_id` | Cart row `id` | Yes |
| `shopping_cart_lines` | `$cart->paid_lines()` | Yes |
| `order_bumps`, `custom_fields`, `coupon`, `us_offer` | not set (D6) | No |
| amount fields | from `finalize_amounts()` | Yes |

`checkout_type`, `shopping_cart_id`, and `shopping_cart_lines` join the constructor defaults with the default `null`. So they are in `attrs` and `get_data()` returns them. A Buy now order keeps `null`, writes no new meta, and reads as a product order.

**Rule.** Code identifies a cart order only with `ppcart_order_is_shopping_cart( $order_or_data )`, which checks `checkout_type === 'cart'`. Code never treats an empty `product_id` as a cart order.

**Plan rehydration.** `ppcart_setup_order()` overwrites `plan` at `includes/functions/product-and-order-setup.php:315` and `:364` with `ppcart_plan( $option_id, … )`. For `option_id = 'cart'` that returns no plan. Both sites call one helper, `ppcart_shopping_cart_order_plan( array $arr ): ?object`, that returns the synthetic plan when `checkout_type === 'cart'`. `PPCart_Order` itself reads `plan` from meta (`models/templates/ppcart-order-initialize.php:27-39`) and needs no change for it.

Items in `ppcart_order_items`, one per line:

```php
[
	'item_type'    => 'main',
	'product_id'   => 101,
	'price_id'     => 'standard:personal',
	'product_name' => 'Writer Toolkit',
	'price_name'   => 'Personal',
	'unit_price'   => 29.0,
	'quantity'     => 1,
	'subtotal'     => 29.0,
	'total_amount' => 29.0,
	// add_item() adds tax fields through maybe_apply_tax_to_item() when Pro tax applies
]
```

### 2.6 Item context

`ppcart_shopping_cart_order_contexts( array $order_data ): array` returns one array per stored main item. Each array is the parent `$order_data` with these keys replaced:

```php
array_merge( $order_data, [
	'product_id'            => (int) $item->product_id,
	'option_id'             => $item->price_id,
	'plan_id'               => $item->price_id,
	'amount'                => (float) $item->total_amount,
	'quantity'              => (int) $item->quantity,
	'product_name'          => $item->product_name,
	'item_name'             => $item->price_name,
	'order_type'            => 'main',
	'checkout_type'         => 'cart',
	'shopping_cart_item_id' => (int) $item->id,
] );
```

A context keeps the parent order `ID`. Code never stores a context as an order and never fires an order status hook with it. Zero-priced items are included. Two plans of one product are two contexts.

### 2.7 Shop card

```php
[
	'product_id'  => 101,
	'title'       => 'Writer Toolkit',
	'permalink'   => '…',
	'image_id'    => 456,
	'excerpt'     => '…',
	'price_label' => 'From $39',            // spec §8.1 rules, all plans
	'purchased'   => false,                 // this product, current user
	'action'      => 'add',                 // add | subscribe | view
	'plans'       => [ [ 'plan_id' => 'opt_1', 'label' => 'Personal, $39', 'in_cart' => false ] ],
	'selected'    => 'opt_1',               // first eligible plan
	'form'        => [ 'nonce' => '…', 'product_id' => 101 ],
]
```

The template shows "✓ In cart" when the selected plan has `in_cart`. A cached Shop page can show stale `in_cart` values. The JavaScript corrects them after an add or when the drawer opens.

## 3. Module map

Paths are relative to the plugin root. `N` is a new file, `C` a changed file, `D` a deleted file. Each new PHP file holds one concern and stays under 500 lines. No Free file names a Pro class.

Delivery order on the branch: **1, 7, 2, 3, 4, 5a, 5, 6, 8.** Phase 7 lands before phase 2, because phases 2 to 5 read the Cart settings. Phase 5a is a separate commit.

### Phase 1. Show in Shop Page, Shop block, `[ppcart_shop]`, delete `[ppcart_store]`

| Path | | Responsibility |
|------|-|----------------|
| `admin/metaboxes/traits/trait-ppcart-product-metaboxes-general-fields.php` | C | Add the `_ppcart_show_in_shop` checkbox after `_ppcart_hide_title` (line 36). A missing meta shows checked |
| `admin/metaboxes/traits/templates/product-metabox-render-tab-sections.php` | C | Add `_ppcart_show_in_shop` to the `header` ("Page header") field list at line 25 |
| `includes/shopping-cart/loader.php` | N | Require the module files and register every module hook. The only file that wires the module into WordPress |
| `includes/shopping-cart/functions-shopping-cart.php` | N | Public helpers: `ppcart_product_shows_in_shop`, `ppcart_render_shop`, `ppcart_render_mini_cart`, `ppcart_render_shopping_cart`, `ppcart_get_shopping_cart`, `ppcart_shopping_cart_set_meta`, `ppcart_order_is_shopping_cart`, `ppcart_shopping_cart_order_contexts`, `ppcart_shopping_cart_order_plan`, `ppcart_shopping_cart_no_cache` |
| `includes/shopping-cart/class-ppcart-shopping-cart-catalog.php` | N | The only owner of eligibility, plan choice, current price, and Shop card data |
| `includes/shopping-cart/class-ppcart-shop.php` | N | Shop attribute normalization, query, `ppcart_shop_query_args`, pagination, render |
| `includes/shopping-cart/class-ppcart-shopping-cart-product-settings.php` | N | On `ppcart_after_validate_meta` (`product-metabox-save-validate-meta.php:218`), write `yes` or `no` explicitly when the General tab was submitted |
| `includes/block-styles/functions-block-styles.php` | N | `ppcart_block_style_tokens()`, the shared token resolver (section 9) |
| `includes/integrations/gutenberg/lib/checkout-renderer/trait-ppcart-checkout-renderer-styles.php` | C | `build_design_styles()` reads its resolved values from the shared resolver. Its selector rules stay here |
| `includes/integrations/gutenberg/lib/checkout-renderer/trait-ppcart-checkout-renderer-style-utilities.php` | C | Move preset defaults, normalization, and color mixing to the shared file |
| `includes/integrations/gutenberg/blocks/shop/block.json`, `render.php`, `edit.js` | N | Shop block. `render.php` is `echo ppcart_render_shop( $attributes );` |
| `includes/integrations/gutenberg/lib/class-ppcart-gutenberg-bootstrap.php` | C | Register the new blocks by metadata |
| `includes/integrations/gutenberg/lib/class-ppcart-gutenberg-assets.php` | C | Register the editor entry handles |
| `public/templates/shopping-cart/shop.php` | N | Shop grid and pagination |
| `public/templates/shopping-cart/shop-card.php` | N | One card. Prints the card array |
| `public/controllers/class-ppcart-public-page-controller.php` | C | Delete `add_shortcode('ppcart_store', …)` at line 62 |
| `public/controllers/page/traits/trait-ppcart-public-page-shortcodes.php` | C | Delete `store_shortcode()` |
| `public/controllers/class-ppcart-public-asset-controller.php` | C | Delete the `ppcart_store` entry (line 122). Add the new blocks and shortcodes to `frontend_assets_needed()` (line 68) |
| `public/css/ppcart-shopping-cart.css` | N | Scoped Shop, drawer, Cart, and cart checkout styles on `--ppcart-*` custom properties |
| `public/css/ppcart-public.css`, `public/scss/ppcart-public.scss` | C | Delete `.ppcart-archive .cards…` |
| `public/templates/shortcodes/archive.php`, `public/templates/archive/item.php`, `item-wrapper-start.php`, `item-wrapper-end.php`, `navigation.php` | D | D12. No override alias for the old card |
| `includes/bootstrap/class-ppcart-dependency-loader.php` | C | Require `includes/shopping-cart/loader.php` and the block-styles file |
| `package.json` | C | New entries in `build:gutenberg` and `start:gutenberg` |
| `docs/hooks-manifest.json`, `docs/hooks-manifest.md` | C | Delete `ppcart_product_archive_args`, `ppcart_before_product_list`, `ppcart_after_product_list`. Add `ppcart_shop_query_args` |
| `readme.txt` | C | "Removed: `[ppcart_store]` shortcode. Use the Shop block or `[ppcart_shop]`." |

Phase 1 check: an integration test proves that an unchecked box stores `no` and the Shop query skips the product. The Buy now page for that product still renders.

### Phase 7. Shop & Cart settings tab and Create missing pages

| Path | | Responsibility |
|------|-|----------------|
| `admin/settings/traits/options/shop-cart-fields.php` | N | The one field and default list for spec §7. Pure data, safe to load on the front end |
| `includes/shopping-cart/class-ppcart-shopping-cart-settings.php` | N | Typed reads of the list: `enabled()`, `urls()`, `after_add()`, `lifetime_days()`, `checkout_fields()`, `confirmation( PPCart_Order )`, `create_missing_pages()` |
| `admin/settings/traits/trait-ppcart-admin-settings-core-options.php` | C | Merge the list in `get_options_list()` |
| `admin/settings/traits/trait-ppcart-admin-settings-sections.php` | C | Register the tab sections from the list |
| `admin/partials/ppcart-admin-page-settings.php` | C | Add `shop_cart` to `$default_setting_tabs` |
| `admin/partials/settings-page/metadata.php` | C | Tab icon, description, group |
| `admin/settings/traits/trait-ppcart-admin-settings-fields.php` | C | Sanitizers for the enum, page, and lifetime fields |
| `admin/settings/class-ppcart-admin-shopping-cart-pages.php` | N | `admin-post` action with capability and nonce checks. Calls `create_missing_pages()` |
| `includes/bootstrap/class-ppcart-admin-hook-registrar.php` | C | Wire the action |

`create_missing_pages()` fills only the page settings that do not point to a published page. A second click creates nothing new. The Checkout page holds the checkout block with `pid = 'cart'` and `template = 'cart-checkout-split'`.

### Phase 2. Shopping cart core

| Path | | Responsibility |
|------|-|----------------|
| `includes/shopping-cart/class-ppcart-shopping-cart.php` | N | Lines and meta in memory. `add_item`, `remove_item`, `set_quantity`, `set_meta`, `validate()`. No SQL, no HTTP |
| `includes/shopping-cart/class-ppcart-shopping-cart-priced.php` | N | `PPCart_Shopping_Cart_Priced`, `_Priced_Line`, `_Notice`. `get_totals()`, `ready_to_pay()` |
| `includes/shopping-cart/class-ppcart-shopping-cart-store.php` | N | The only writer of the table and both cookies. `read`, `update`, `claim_checkout`, `merge_guest_into_user`, `remove_paid_lines`, `delete_expired`, `install` |
| `includes/shopping-cart/class-ppcart-shopping-cart-rest.php` | N | Routes, schemas, nonce rules, the GET authentication adapter, error statuses |
| `includes/shopping-cart/class-ppcart-shopping-cart-view.php` | N | One projection for REST data and for template rendering |
| `includes/class-ppcart-upgrade.php` | C | `setup_shopping_cart_table()` beside `setup_tax_table()` (line 55). Schedule the daily cleanup |
| `includes/class-ppcart-activator.php` | C | Same install and schedule on activation |
| `includes/class-ppcart-deactivator.php` | C | Unschedule `ppcart_shopping_cart_cleanup` |

Signatures:

```php
final class PPCart_Shopping_Cart {
	public function __construct( private PPCart_Shopping_Cart_Catalog $catalog, array $lines = [], array $meta = [], private int $cart_id = 0, private int $revision = 0 );
	/** ppcart_cart_disabled | ppcart_cart_not_eligible | ppcart_cart_plan_not_eligible | ppcart_cart_already_in_cart | ppcart_cart_full */
	public function add_item( int $product_id, string $plan_id ): true|WP_Error;
	/** A missing key is success. Returns whether a line was removed. */
	public function remove_item( string $key ): bool;
	/** ppcart_cart_item_missing | ppcart_cart_quantity_invalid. Never clamps input. */
	public function set_quantity( string $key, int $quantity ): true|WP_Error;
	/** Extension keys only. A key that starts with '_' is refused. */
	public function set_meta( string $key, mixed $value ): true|WP_Error;
	/** Re-price every line, remove ineligible lines, record notices in _notices. */
	public function validate(): PPCart_Shopping_Cart_Priced;
}

final class PPCart_Shopping_Cart_Store {
	public function read(): PPCart_Shopping_Cart_Priced;
	/** @param callable(PPCart_Shopping_Cart):(true|bool|WP_Error) $operation */
	public function update( callable $operation, bool $create = false ): PPCart_Shopping_Cart_Priced|WP_Error;
	public function claim_checkout( int $seen_revision, array $customer ): PPCart_Shopping_Cart_Priced|WP_Error;
	public function merge_guest_into_user( string $guest_cart_key, int $user_id ): void;
	/** @param list<array{key:string,added_at:string}> $lines */
	public function remove_paid_lines( int $cart_id, array $lines ): void;
	public static function delete_expired(): int;
	public static function install(): void;
}

final class PPCart_Shopping_Cart_Catalog {
	public function shop_card( int $product_id, ?PPCart_Shopping_Cart_Priced $cart = null ): array;
	/** @return array{product_id:int,plan_id:string,unit_price:float,product_name:string,plan_name:string,image_id:int,permalink:string}|WP_Error */
	public function resolve_line( int $product_id, string $plan_id ): array|WP_Error;
	public function product_is_eligible( int $product_id ): bool;
	public function plan_is_eligible( object $plan, int $product_id ): bool;
	public function max_quantity( int $product_id, string $plan_id ): int;
}
```

`resolve_line()` calls `ppcart_plan( $plan_id, 'current', $product_id )` and requires the returned `option_id` to equal `$plan_id`. A plan alias never becomes a cart key. Published status, Show in Shop Page, the sales window, and stock are hard checks. The default "one-time or free plan" result goes through `ppcart_shopping_cart_product_is_eligible`, so Pro can admit a new product kind later.

`validate()` handles a line that is no longer eligible in one place. It removes the line and adds a `removed` notice. For a changed price it adds a `price_changed` notice and moves `seen_price` to the new price. Notices go into `_notices`, de-duplicated by code, key, and prices, at most 50. A notice stays until its line is removed or the order that pays for it completes. `read()` writes a repair back through `update()`, so two readers cannot bring back a line that another tab removed.

Phase 2 check: integration tests on the real table for eligibility, duplicate keys including a colon in `plan_id`, price notices, CAS replay with two interleaved writes, login merge and its re-run, expiry, and cross-owner access.

### Phase 3. Mini Cart and live Add to cart

| Path | | Responsibility |
|------|-|----------------|
| `includes/integrations/gutenberg/blocks/mini-cart/block.json`, `render.php`, `edit.js` | N | Mini Cart block |
| `public/templates/shopping-cart/mini-cart.php` | N | Icon, count, and link shell. Cache-safe |
| `public/templates/shopping-cart/drawer.php` | N | Drawer body. Also the `drawer` fragment |
| `public/templates/shopping-cart/cart-item.php` | N | One line row, shared by drawer and Cart page |
| `public/templates/shopping-cart/summary.php` | N | Totals rows and the `ppcart_shopping_cart_after_totals` slot. Shared by drawer, Cart page, and cart checkout |
| `public/templates/shopping-cart/notices.php` | N | Notice markup with a polite live region |
| `public/controllers/class-ppcart-public-shopping-cart-controller.php` | N | No-JS add and remove POST handlers, early no-cache detection, the four shortcodes (`[ppcart_shop]`, `[ppcart_mini_cart]`, `[ppcart_cart]`, `[ppcart_cart_checkout]`) |
| `public/js/ppcart-shopping-cart.js` | N | Vanilla JS. Form capture, REST calls, nonce retry, drawer and focus, count display, cross-tab refresh |
| `includes/bootstrap/class-ppcart-public-hook-registrar.php` | C | Construct the public controller |

JavaScript module surface:

```js
export function mountShoppingCart( root = document ) {}
export async function requestShoppingCart( method, path = '', data = undefined ) {}
export function applyShoppingCartResponse( response, root = document ) {}
export function setShoppingCartDrawerOpen( open, trigger = null ) {}
export function mountShoppingCartCheckout( form, paymentAdapter ) {}
```

`requestShoppingCart()` sends `credentials: 'same-origin'`. On `rest_cookie_invalid_nonce` it calls `GET /cart` without the stale header, takes the fresh `nonce`, and retries the request once. On `ppcart_cart_already_in_cart` it opens the drawer with the current cart. It never increments a quantity. A `BroadcastChannel` sends only "changed" to other tabs, and they refetch when a cart surface opens. The script never writes either cookie.

### Phase 4. Cart page

| Path | | Responsibility |
|------|-|----------------|
| `includes/integrations/gutenberg/blocks/cart/block.json`, `render.php`, `edit.js` | N | Cart block |
| `public/templates/shopping-cart/cart.php` | N | Two columns, empty state, rows, summary, notices |

Remove has a nonce-protected POST fallback on the same page. A GET link never changes a cart.

### Phase 5a. Extraction commit (behavior-preserving)

This commit changes no behavior. Buy now tests must pass after it, before any cart order code lands. Run `composer test:integration` and `composer test:smoke`.

| Path | | Change |
|------|-|--------|
| `models/order/traits/trait-ppcart-order-checkout-input.php` | C | Add `finalize_amounts()`, `apply_customer_fields()`, `apply_address_fields()` |
| `models/order/traits/templates/order-checkout-input-load-from-post.php` | C | Lines 38-52 become `$this->finalize_amounts( $ppcart_product );` |
| `models/order/traits/templates/order-checkout-input-setup-atts-from-post.php` | C | Lines 12, 17-40, and 49 become `$this->apply_customer_fields( $posted );` at the position of line 27. Lines 65-70 become `$this->apply_address_fields( $posted );` at the same position |

```php
/** Moved from order-checkout-input-load-from-post.php:38-52. */
public function finalize_amounts( ?object $product ): void;
// do_action( 'ppcart_order_pre_calculate_tax', $this );
// calculate_pre_tax_amount_from_items(); calculate_tax_amount_from_items(); calculate_total_amount_from_items();
// invoice_subtotal = pre_tax_amount;
// amount = apply_filters( 'ppcart_charge_amount', amount, $product ); // null for a cart order
// non_formatted_amount = invoice_total = amount; calculate_final_amounts();

/** email, user lookup by email, names, customer_name, customer_id, phone, company, pay_method, currency, terms, privacy, ip, user_account, vat. */
private function apply_customer_fields( array $posted ): void;

/** address1, address2, city, state, zip, country. */
private function apply_address_fields( array $posted ): void;
```

Two methods keep the call order exact. In `setup-atts-from-post.php` the address loop (lines 65-70) runs after the `ppcart_order_setup_purchase_note` filter (line 51), and the other customer fields run before it. Product-only lines stay where they are: 11, 14-16, 41-48, 51, 53-63, 72. The only line that moves is the email assignment (line 12). It moves below `ppcart_setup_product()` (lines 14-16), which does not read it.

### Phase 5. Cart checkout, order, Stripe on-page, completion

| Path | | Responsibility |
|------|-|----------------|
| `models/order/traits/trait-ppcart-order-shopping-cart.php` | N | `load_from_shopping_cart()` |
| `models/class-ppcart-order.php` | C | `use PPCart_Order_Shopping_Cart` |
| `models/templates/ppcart-order---construct.php` | C | Add defaults `checkout_type`, `shopping_cart_id`, `shopping_cart_lines`, each `null` |
| `includes/functions/product-and-order-setup.php` | C | Lines 315 and 364 use `ppcart_shopping_cart_order_plan()` for a cart order |
| `public/controllers/class-ppcart-public-cart-checkout-controller.php` | N | Nonce, input, `claim_checkout`, order build, payment, confirmation |
| `includes/shopping-cart/class-ppcart-shopping-cart-checkout-input.php` | N | Parse only the fields from `checkout_fields()` plus `ppcart_cart_revision`, pay method, gateway token. No amounts, no product IDs |
| `includes/shopping-cart/class-ppcart-shopping-cart-completion.php` | N | Listener on `ppcart_order_after_primary_integration_action`. For `$order_type === 'cart'` on `ppcart_order_complete`, or `ppcart_order_pending` with COD, it calls `remove_paid_lines()` |
| `includes/integrations/gutenberg/blocks/checkout-form/order-form.js` | C | Third source option "Cart", stored as `pid: 'cart'`. Hides product, plan, and coupon controls |
| `includes/integrations/gutenberg/lib/checkout-renderer/trait-ppcart-checkout-renderer-request.php` | C | Branch on the exact string `'cart'` at the top of `render()` (line 97) and `render_preview_html()` (line 212), before `resolve_product_id()` (line 172) and the `absint()` at lines 215 and 224 |
| `includes/integrations/gutenberg/lib/class-ppcart-checkout-preview-controller.php` | C | The `pid` arg accepts `'cart'`. `can_preview_checkout_product()` and `get_preview()` branch before `absint()` (request trait lines 16 and 69) |
| `public/templates/shopping-cart/checkout.php` | N | Contact, optional fields, payment, terms, Pay button, `summary.php` with an Edit cart link |
| `public/templates/shopping-cart/confirmation.php` | N | Cart settings confirmation message |
| `public/controllers/checkout/traits/templates/checkout-validation-check-purchase-limit.php` | C | Keep the nonce and JSON response. Call `ppcart_check_product_purchase_limit()` |
| `public/controllers/checkout/traits/templates/checkout-validation-validate-order-form.php` | C | Call `ppcart_check_product_stock()` |
| `includes/functions/merge-tags-and-dates.php` | C | Extract the stock rule from `ppcart_is_cart_closed()` (stock check near line 318) without a signature change. `order_list` and `product_list` (lines 150, 154) list every item for a cart order |
| `models/order/traits/templates/order-lookups-get-current-user-orders.php` | C | Delegate to `ppcart_get_customer_product_orders()`, which also matches item `product_id` and returns distinct parent orders |
| `includes/functions/integrations-and-stock.php` | C | In `ppcart_trigger_integrations()`, pass `$order_type = 'cart'` to `ppcart_order_after_primary_integration_action` (line 142) for a cart order. The status action at line 140 keeps `'main'` |
| `public/class-ppcart-public.php` | C | Cart branches in the three callbacks (section 4) |
| `includes/functions/order-items-and-details.php` | C | `ppcart_get_items_from_legacy_order()` (line 15) returns an empty list for a cart order. It never builds an item for product 0 |
| `includes/files/traits/trait-ppcart-files-order.php` | C | Refund revoke (line 17) matches any item `product_id` of a cart order |
| `includes/functions/payment-actions-and-refunds.php` | C | Restock (line 565) loops items with their quantities for a cart order |
| `public/controllers/page/traits/templates/page-customer-customer-bought-product.php` | C | Add an item `product_id` EXISTS clause to the order query. Keep the subscription branch |
| `public/controllers/order/traits/templates/order-save-save-order-to-db.php` | C | A cart order uses `confirmation()` from the Cart settings before any product upsell or form action code |
| `public/controllers/checkout/traits/templates/checkout-payment-ppcart-process-payment.php` | C | Detect a cart order before product setup and upsell filters |
| `public/templates/functions/checkout-core.php` | C | Cart confirmation from the Cart settings |
| `includes/stripe-sync/traits/templates/stripe-sync-refunds-charge-belongs-to-site.php` | C | Line 17 accepts a charge by truthy `ppcart_product_id`. Also accept `ppcart_checkout_type = cart` with a known local order and intent |
| `includes/stripe-sync/traits/templates/stripe-sync-refunds-find-order-for-charge.php` | C | Bind the metadata order ID to its known local intent before a status change |
| `admin/reports/templates/admin-reports-page.php`, `customer-reports-page.php`, `admin/reports/traits/trait-ppcart-admin-reports-data.php` | C | Order and customer counts stay once per order. Product revenue for a cart order comes from item totals |
| `admin/controllers/order/templates/product-info-metabox.php` | C | Show "Cart order" in place of the product link |
| `admin/order-metaboxes/traits/templates/order-metabox-edit-fields-get-order-edit-fields.php` | C | Show all main items. No single product picker for a cart order |
| `public/js/ppcart-shopping-cart.js` | C | Cart checkout submit with the Stripe Payment Element |

`load_from_shopping_cart()`:

```php
public function load_from_shopping_cart( PPCart_Shopping_Cart_Priced $cart, array $customer ): void;
// items = []; checkout_type = 'cart'; product_id = 0; option_id = plan_id = 'cart'; quantity = 1;
// product_name = item_name = "{first} + N more"; plan = synthetic one-time plan;
// shopping_cart_id = $cart->cart_id; shopping_cart_lines = $cart->paid_lines();
// apply_customer_fields( $customer ); apply_address_fields( $customer );
// foreach line: add_item( main item from the priced line )   // add_item() runs maybe_apply_tax_to_item()
// $filtered = apply_filters( 'ppcart_shopping_cart_order_loaded', $this, $cart );
//   a return value other than $this triggers _doing_it_wrong() and is ignored
// finalize_amounts( null );
// Not run: coupons from POST, bumps, custom fields, purchase note, product template (D6).
```

The `ppcart_shopping_cart_order_loaded` filter runs before `finalize_amounts()`. Buy now applies its coupons at the same point, before the amount calculation (`order-checkout-input-load-from-post.php:32-42`). So Pro edits item amounts and Free computes the totals once.

`$customer` comes from `PPCart_Shopping_Cart_Checkout_Input::customer()` and uses the keys that `apply_customer_fields()` reads (`email`, `first_name`, `last_name`, `phone`, `company`, `pay-method`, `ppcart_accept_terms`, address keys).

Stripe for a cart order uses one PaymentIntent for `$order->amount`, metadata `ppcart_checkout_type=cart`, `ppcart_order_id`, `ppcart_item_count`, and the description "Order #{id} ({n} items)". The controller does not call `payment-intent-create-payment-intent.php`. Cart checkout uses the on-page element even when hosted checkout is on.

Phase 5 check: an integration test builds an order from a two-line priced cart, reloads it, and asserts `checkout_type`, two main items, totals, and the synthetic plan. A regression test pays with a Stripe test card and asserts one order, two download grants, and one purchase email. The Buy now smoke test still passes.

### Phase 6. PayPal

| Path | | Responsibility |
|------|-|----------------|
| `public/class-ppcart-paypal.php` | C | A branch that takes an order the cart controller built. No `load_from_post()` in that branch |
| `public/paypal/class-ppcart-paypal-onpage.php` | C | One purchase unit with the order total. `description` is the order name |
| `public/paypal/traits/trait-ppcart-paypal-requests.php` | C | `item_name` is the order name. Return and cancel URLs come from the Cart settings |
| `public/paypal/traits/templates/paypal-requests-ppcart-build-paypal-url.php` | C | Same settings branch. No read of product 0 |
| `public/js/ppcart-paypal-onpage.js` | C | Accept the cart order response without a numeric product ID |

### Phase 8. Docs and catalog

| Path | | Responsibility |
|------|-|----------------|
| `docs/public/getting-started/shortcodes.md` | C | Delete "Product store". Add `[ppcart_shop]`, `[ppcart_mini_cart]`, `[ppcart_cart]`, `[ppcart_cart_checkout]` |
| `docs/public/shop-and-cart.md` | N | Merchant guide |
| `docs/dev/architecture.md` | C | Add the module and the cart order consumer rule |
| `docs/hooks-manifest.json`, `docs/hooks-manifest.md` | C | Section 8 hooks and the changed meanings |
| `.scribe.config.yaml` | C | Feature record through `.agents/skills/plugin-features/` |
| `docs/testing/cases/` | C | Catalog entries through `.agents/skills/test-authoring/` |

Tests land in the phase whose behavior they check. Phase 8 completes docs and the catalog only.

## 4. Completion consumers

Public status hooks (`ppcart_order_pending`, `ppcart_order_complete`, `ppcart_order_refunded`, `ppcart_order_marked_complete`) fire once per cart order with `$order_type = 'main'`, as today. Free's own consumers loop item contexts. One purchase email goes out for the parent order.

| Consumer | Where | Cart order behavior |
|----------|-------|---------------------|
| Integrations on purchase | `public/class-ppcart-public.php:130` | Loop contexts. `ppcart_do_integrations( $context['product_id'], $context )` |
| Integrations on pending, refund | `public/class-ppcart-public.php:106` | Loop contexts with `$status` |
| Stock | `public/class-ppcart-public.php:131` | Loop contexts. Pass `$context['quantity']` as the third argument of `ppcart_maybe_update_stock()` (`includes/functions/prices-and-marketing.php:363`). The Buy now call stays unchanged |
| Per-product notifications | `public/class-ppcart-public.php:109`, `:132` | Loop contexts. `ppcart_do_notifications( $context )`. COD pending also loops |
| Purchase email | `public/class-ppcart-public.php:118` | One send with the parent data. The cart branch skips the per-product `disable_{type}_email` lookup |
| `order_list`, `product_list` merge tags | `includes/functions/merge-tags-and-dates.php:150`, `:154` | Parent data lists all item names. A context lists its own item |
| Download grants | existing item loop in the files module | No change. Verify two mains, two plans of one product, and a free item |
| Download revoke on refund | `includes/files/traits/trait-ppcart-files-order.php:17` | Revoke each grant whose product is any item of the order |
| Refund restock | `includes/functions/payment-actions-and-refunds.php:565` | Loop items with quantities |
| Reports | `admin/reports/…` (3 files) | Counts once per order. Product revenue from item totals |
| Customer bought product | `page-customer-customer-bought-product.php` | Match item `product_id` (and plan when asked) on the same item. Distinct parent orders |
| Purchase limit | `checkout-validation-check-purchase-limit.php`, `ready_to_pay()` | One rule, `ppcart_check_product_purchase_limit( int $product_id, array $customer )`, called by both paths |
| Confirmation and redirect | `order-save-save-order-to-db.php`, `checkout-payment-ppcart-process-payment.php`, `checkout-core.php` | Cart settings. No upsell flow |
| Legacy items fallback | `includes/functions/order-items-and-details.php:15` | Empty list. A cart order without items is corrupt data |
| Product and bump after-primary dispatch | `includes/functions/integrations-and-stock.php:142` | Free passes `$order_type = 'cart'`. Pro's `run_bump_integrations` returns at its own guard `$order_type != 'main'` (`publishpress-cart-pro/includes/order-behavior/traits/templates/ppcart-pro-order-bump-trait-run-bump-integrations.php:3`). Free removes no Pro callback |
| Cart clear | `class-ppcart-shopping-cart-completion.php` | On the after-primary action with `'cart'`, `remove_paid_lines( shopping_cart_id, shopping_cart_lines )`. Idempotent |
| Stripe refund sync | `stripe-sync-refunds-charge-belongs-to-site.php:17` | Recognize a cart charge by checkout type and known local order |
| Admin order screen | `product-info-metabox.php`, order edit fields | "Cart order", all items |

Without the after-primary change, Pro's replay loop (Pro template lines 8-20) fires `ppcart_order_complete` again for every item whose `product_id` differs from the parent. For a cart order that is every item, so each integration and stock change would run twice.

## 5. Concurrency

The Store is the only writer. Every write is a CAS on `revision`, with reload and replay up to 3 tries. There are no locks.

| Overlap | Result |
|---------|--------|
| Two tabs add different products | The first CAS wins. The second replays its add on the new row. Both lines stay |
| Two tabs add the same key | One inserts the line. The other's replay returns `ppcart_cart_already_in_cart`. The JS opens the drawer |
| Remove races an add | Operations commit in CAS order. Remove of a missing key succeeds |
| PATCH races PATCH | The last committed absolute value wins. Free accepts only 1 |
| GET repair races a remove | The repair is an `update()` operation. It replays on the new row and cannot bring the line back |
| Three revision misses | `ppcart_cart_conflict` (409). The JS refetches and shows the cart |
| Checkout submit races an add in another tab | `claim_checkout()` fails `ready_to_pay()` on the revision. `ppcart_cart_changed`, no charge. The buyer sees the new cart |
| Two submits of one form | `claim_checkout()` writes `_checkout_claim` with one CAS try at `seen_revision`. One wins. The other gets `ppcart_cart_changed` |
| Payment completes after another tab added a line | `remove_paid_lines()` removes only matching key plus `added_at`. The new line stays. A repeated callback finds nothing to remove |
| Login merge | The user line wins on a shared key. Guest lines with other keys are appended, up to `max_items`. The Store commits the user row, then deletes the guest row, then points `ppcart_cart` at the user row's key. A crash between commit and delete is safe. The next run merges again without duplicates |
| User has no row at login | `UPDATE … SET user_id = %d WHERE id = %d AND user_id = 0` claims the guest row. Zero rows means another request claimed it, so the Store reloads and merges |
| Signed-in user with a guest cookie, without `wp_login` (SSO, REST login) | `locate()` runs the same merge |
| Two first adds of a signed-in user with no row | Two user rows can appear. `locate()` merges newer user rows into the oldest with the same merge |
| Two first adds of a guest with no cookie | Two guest rows can appear. The last `Set-Cookie` wins. The other row expires. Accepted limitation, see section 10 |
| Cleanup cron during a write | The cron deletes only `expires_at < now`. Each write moves `expires_at` forward |

A guest request never reads a row whose `user_id` is not 0, even with a matching cookie.

## 6. HTTP and cache rules

### 6.1 Routes

Namespace `ppcart/v1`. Routes and bodies are those of spec §10.

| Code | HTTP status |
|------|-------------|
| malformed input | 400 |
| `ppcart_cart_disabled`, invalid nonce, wrong owner | 403 |
| `ppcart_cart_item_missing` (PATCH only) | 404 |
| `ppcart_cart_already_in_cart`, `ppcart_cart_full`, `ppcart_cart_conflict`, `ppcart_cart_changed` | 409 |
| `ppcart_cart_not_eligible`, `ppcart_cart_plan_not_eligible`, `ppcart_cart_quantity_invalid`, `ppcart_cart_empty` | 422 |

`DELETE` of a missing key returns 200 with the cart. The codes `ppcart_cart_conflict`, `ppcart_cart_changed`, `ppcart_cart_empty`, `ppcart_cart_item_missing`, and `ppcart_cart_quantity_invalid` add to the five codes in spec §10.

### 6.2 Authentication

- Mutating routes need `X-WP-Nonce` with action `wp_rest`.
- `GET /cart` is the nonce-refresh route. WordPress sets the current user to 0 when a REST request has no nonce, and rejects an expired nonce before the route callback runs. So a signed-in visitor on a cached page with an old nonce would read as a guest. A route-specific adapter on `rest_authentication_errors`, after core cookie authentication, handles only `GET /ppcart/v1/cart`. It validates the signed-in auth cookie, restores that user, and clears only the missing or invalid nonce error. Mutations are untouched. An invalid login cookie stays anonymous and reads only its guest cart.
- **This adapter needs a real HTTP test**, through actual REST requests with and without a nonce, signed in and signed out. A unit test that calls the controller cannot prove it.

### 6.3 Checkout gate

The cart checkout form carries `ppcart_cart_revision`. `claim_checkout()` reads the row, prices it, runs `ready_to_pay( $seen_revision, $customer )`, and writes `_checkout_claim` with `WHERE revision = $seen_revision` in one try. Any failure returns `ppcart_cart_changed` or the gate code with `data.cart`. The form redraws the summary and the new revision. The buyer must submit again. No gateway call happens on a refused request. The buyer pays only for the cart they saw.

### 6.4 Cache

- Cookies are set only on the first successful add. `ppcart_cart` is HttpOnly, Secure on HTTPS, SameSite=Lax, path `COOKIEPATH`, lifetime from the Cart settings. `ppcart_cart_count` has the same flags without HttpOnly. PHP never reads `ppcart_cart_count`.
- When the last line goes, the Store deletes both cookies and keeps the row until expiry. The visitor's pages are cacheable again.
- REST responses send `Cache-Control: private, no-store`.
- The Cart page, the cart checkout page, and a page with a checkout block on source Cart are detected on `template_redirect`. The handler defines `DONOTCACHEPAGE` and calls `nocache_headers()` before output. The render helpers repeat the guard for embedded use.
- Page caches that run before WordPress must exclude the configured Cart and Checkout URLs. The merchant guide says so.
- The Shop and Mini Cart shells stay cacheable. The Purchased badge depends on the site's logged-in cache bypass.
- No-JS add and remove are POST with a nonce. No GET request changes a cart.

## 7. Hooks

Ship a hook only when it has a named caller. Each hook is a public contract from the first release.

| Hook | Type | Arguments | Fired in | Named caller |
|------|------|-----------|----------|--------------|
| `ppcart_shop_query_args` | filter | `(array $args, array $attributes): array` | `PPCart_Shop`. Visibility and type limits are applied again after the filter | Merchants (spec §8.1) |
| `ppcart_shopping_cart_product_is_eligible` | filter | `(bool $eligible, int $product_id): bool` | Catalog | Pro collections |
| `ppcart_shopping_cart_plan_is_eligible` | filter | `(bool $eligible, object $plan, int $product_id): bool` | Catalog | Later: pay-what-you-want, subscriptions |
| `ppcart_shopping_cart_item_max_quantity` | filter | `(int $max, int $product_id, string $plan_id): int`, minimum 1 | Catalog | Pro quantity |
| `ppcart_shopping_cart_max_items` | filter | `(int $max): int`, default 50, minimum 1 | `PPCart_Shopping_Cart` | Merchants (spec §5.2) |
| `ppcart_shopping_cart_totals` | filter | `(array $totals, PPCart_Shopping_Cart_Priced $cart): array` | `Priced::get_totals()` | Pro coupon and tax preview |
| `ppcart_shopping_cart_after_totals` | action | `(PPCart_Shopping_Cart_Priced $cart)` | `summary.php` | Pro coupon field |
| `ppcart_shopping_cart_order_loaded` | filter | `(PPCart_Order $order, PPCart_Shopping_Cart_Priced $cart): PPCart_Order` | `load_from_shopping_cart()`, before `finalize_amounts()` | Pro coupons on the order |
| `ppcart_shopping_cart_checkout_fields` | filter | `(array $fields): array` | `Settings::checkout_fields()` | Pro tax (address) |

Public functions for extensions, not hooks: `ppcart_shopping_cart_set_meta( string $key, mixed $value ): PPCart_Shopping_Cart_Priced|WP_Error` and `ppcart_order_is_shopping_cart( array|PPCart_Order $order ): bool`.

Not shipped in v1. Each can be added later without a break:

| Hook | Reason | What covers the need |
|------|--------|----------------------|
| `ppcart_shopping_cart_item_added`, `_item_removed`, `_emptied` | No named caller | Add inside `Store::update()` after the winning write, when a caller exists |
| `ppcart_shopping_cart_before_totals` | The coupon field uses `after_totals`. A discount row uses the `totals` filter | `ppcart_shopping_cart_totals` |
| `ppcart_shopping_cart_order_created` | `ppcart_order_created` already fires with the order (`order-persistence-create.php:38`) | `ppcart_order_created` plus `ppcart_order_is_shopping_cart()` |

Changed meaning of existing hooks for a cart order. Document these in the hooks manifest.

| Hook | For a cart order |
|------|------------------|
| `ppcart_order_pending`, `ppcart_order_complete`, `ppcart_order_refunded`, `ppcart_order_marked_complete` | Fire once. `$order_info['checkout_type']` is `'cart'`. `product_id` is empty |
| `ppcart_order_after_primary_integration_action` | `$order_type` is `'cart'` |
| `ppcart_charge_amount` | The second argument is `null` |
| `ppcart_run_after_integrations` | Fires once with parent data |

Overridable templates (theme folder `publishpress-cart/`): every file in `public/templates/shopping-cart/`. Those are `shop.php`, `shop-card.php`, `mini-cart.php`, `drawer.php`, `cart.php`, `cart-item.php`, `summary.php`, `notices.php`, `checkout.php`, and `confirmation.php`.

## 8. Red-flag screen

| Red flag | Response |
|----------|----------|
| Shallow pass-through | `Store::update()` owns a whole committed operation: load, replay, CAS, cookies. The Catalog owns all offer rules. The View owns both the REST data and the HTML |
| Information leakage | SQL and cookies stay in the Store. The REST shape stays in the View. The order builder sees only the priced cart |
| Temporal decomposition | No separate validator, pricer, and saver objects. `validate()` re-prices and repairs in one pass |
| Split ownership | No `user_meta` copy, no transient, no browser price, no stored price for charging |
| Two ways to do one thing | Block and shortcode call one render function. REST and no-JS call one Store operation. Orders use the native `add_item()` and `finalize_amounts()` for both checkouts |
| Importable internals | PHP cannot hide the Catalog or the domain class. Callers use the public functions and `Store::update()`. A file-level docblock states the rule |
| Hand-synced lists | One settings list feeds defaults, admin controls, and runtime reads. One field list feeds checkout render and parse |
| Hidden state | The priced cart is readonly. The only mutable cart is the one inside an `update()` operation |

The longest mutation trace is REST, Store, domain cart, Catalog. That is four files, and each holds separate knowledge.

## 9. Styles

The default look of every new block is the checkout block with empty `styleSettings` (design-tokens.md section 1). With empty settings the checkout block prints only its base rules, and `ppcart-public.css` styles the form (`trait-ppcart-checkout-renderer-styles.php:9-31`, `:253-255`).

- `ppcart_block_style_tokens( array $settings ): array` is the shared resolver. It normalizes settings, merges preset defaults, mixes the accent shades, and returns a map of `--ppcart-*` custom properties. With empty token settings it returns an empty map.
- Each new block prints that map as an inline `style` on its wrapper.
- `ppcart-shopping-cart.css` reads every token with a fallback to the base look, for example `var(--ppcart-accent, #000)`. So an empty map gives the black bold button, the gray total box, and the uppercase section titles.
- The checkout block keeps its own selector rules in `build_design_styles()` and takes its resolved values from the shared resolver.
- New CSS follows the "Do not copy" list in design-tokens.md section 7. It inherits the theme font, uses an accent focus ring, and uses one panel gray and one chip gray.

## 10. Known limits and code facts

- **Compat and the live-table map (resolved).** The table does not use `ppcart_live_table()`. Compat overrides that function (`publishpress-cart-compat/includes/compat/custom-table-migration/helpers.php:20`) and reads only its own map, so the call would return `''` with Compat active. A new table has no leftover name to map.
- **First guest add in two tabs.** Without a cookie, two simultaneous first adds cannot know they belong to one visitor. Two rows can appear and the last cookie wins. A later shared session ID would fix this, but spec §9 forbids cookies before the first add.
- **Double pending orders across revisions.** A buyer who submits, sees `ppcart_cart_changed`, and submits again creates one pending order per successful claim. The older pending order stays pending, as in Buy now today.
- **Pro guard dependency.** The after-primary skip depends on Pro's guard at its template line 3. Pro must keep that guard. Record this in `docs/dev/prefix-pro.md` or the Pro follow-up issue.
- **Pro tax preview.** `ppcart_order_pre_calculate_tax` runs inside `finalize_amounts()` for a cart order too. Pro's upgrade-paths module hooks it (`publishpress-cart-pro/modules/upgrade-paths/traits/trait-ppcart-upgrade-path-request.php:27`). The Pro follow-up must confirm that the callback does nothing without an upgrade-path request.
- **Spec path.** Spec §8 names `lib/class-ppcart-gutenberg-bootstrap.php`. The file is `includes/integrations/gutenberg/lib/class-ppcart-gutenberg-bootstrap.php`.
- **Cart checkout shortcode (decided).** `[ppcart_cart_checkout]` renders the same output as the checkout block with source Cart, for page builders. Blocks already render in classic themes, so this is the only reason it exists.

## 11. Synthesis decision

**Base: candidate B** (`/tmp/arena-shop-cart/candidate-2/`). B kept the native order model and the public status hooks firing once per order, and it had the most complete consumer audit. This file keeps B's Catalog, domain cart, Store, View, settings list, checkout source branch, per-item contexts, persisted notices, cache rules, and the GET `/cart` authentication adapter.

Grafts:

| Graft | Source | Replaces in B |
|-------|--------|---------------|
| `revision` column as the CAS token, 3 tries, add of an existing key returns `ppcart_cart_already_in_cart`, remove of a missing key succeeds | A (`candidate-1/sketch/includes/shopping-cart/class-ppcart-shopping-cart-store.php`) and C (`candidate-3/sketch/class-ppcart-shopping-cart-store.php`) | B's exact-JSON compare on `items`, `meta`, `updated_at`, `expires_at`, and its 4 tries |
| Immutable priced cart that the order builder receives, and `ready_to_pay( seen_revision )` | A (`candidate-1/sketch/includes/shopping-cart/class-ppcart-shopping-cart-priced.php`) | B's resolved-line arrays and its HMAC checkout token. The revision now carries the "cart you saw" check |
| Separate extraction commit for the shared tail of `load_from_post()` and the customer fields | A (`candidate-1/sketch/models/order/traits/trait-ppcart-order-shopping-cart.php`) | B's direct calls of the amount methods inside the new loader. This file splits A's `apply_customer_fields()` in two so the extraction keeps the exact call order |
| Buy now's stock call stays unchanged, only cart completion passes quantity | C (`candidate-3/module-map.md`) | (C's live-table registration was dropped after synthesis: that map is for leftover StudioCart tables, and Compat overrides it. The plain prefixed name from B stays.) |
| Hook list with a named caller per hook | A (`candidate-1/sketch/hooks.md`) | B's 14 hooks. A's "fire `ppcart_order_complete` once per item" was not taken, because the base keeps one status event per order |

Cut from B, and why:

- **Web Locks first-add bootstrap.** It protects only browsers that support Web Locks, and it adds a cross-tab protocol for a rare race. The race is now a documented limit.
- **Per-user advisory lock.** CAS plus a conditional `UPDATE … WHERE user_id = 0` claim, plus a merge of extra user rows at `locate()`, gives the same end state without a lock.
- **Per-attempt checkout lock and attempt UUID.** `claim_checkout()` uses one CAS try on the revision. Two submits of one form produce one order without a lock.
- **Override alias `archive/item.php` for the new card.** D12 deletes the archive templates. An old theme override would receive the new card array and break. The new name `shopping-cart/shop-card.php` avoids that.
- **Hooks without a named caller.** `item_added`, `item_removed`, `emptied`, `before_totals`, and `order_created` (section 7).
