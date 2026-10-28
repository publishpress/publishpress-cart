# Embedded checkout

The embedded checkout shows a product checkout on a website that is not this
WordPress site, inline or in a popup. The merchant copies a snippet from the
product **Embed** tab. The buyer pays inside an iframe that this site serves.
The user guide is [Embed your checkout on another website](../public/products/embed-checkout-other-website.md).

Three parts run it:

| Part | File | Runs on |
|------|------|---------|
| Loader | `public/js/ppcart-embed.js` | The host page, the merchant's other website |
| Embed shell | `public/controllers/class-ppcart-public-embed-controller.php`, `public/templates/embed/checkout.php`, `public/css/ppcart-embed.css`, `includes/embed/` | This WordPress site, for `?ppcart-embed=` requests |
| Frame bridge | `public/js/ppcart-embed-frame.js` | Inside the iframe |

The admin side is `admin/class-ppcart-admin-embed-tab.php`, `admin/partials/ppcart-admin-embed-panel.php`, and `admin/js/ppcart-admin-embed.js`.

## Request flow

1. The host page loads the loader. For each `[data-ppcart-checkout="<product URL>"]`, the loader inserts an iframe. The iframe `src` is the product URL with `ppcart-embed=1`. The iframe has `title="Checkout"`, `allow="payment *"`, and a start height of 480px.
2. For a popup, the loader listens for clicks on `[data-ppcart-popup]` and on links whose `href` contains `ppcart-embed=popup`. A click opens a dialog and creates the iframe. Closing the dialog removes the iframe.
3. WordPress receives the frame request. `ppcart_is_embed_request()` is true for a singular front-end request with a non-empty `ppcart-embed` query arg, when `ppcart_embed_allows_post()` allows the queried post: a product, a page with `[ppcart_form]`, `[ppcart_receipt]` or the checkout form block, or any page opened with `ppcart-order` and a valid order access token (a confirmation page the merchant picked).
4. `PPCart_Public_Embed_Controller` handles the request. `includes/bootstrap/class-ppcart-public-hook-registrar.php` registers its hooks:
   - `template_redirect` at priority 1 calls `send_frame_headers()`. It removes `X-Frame-Options`, keeps each Content-Security-Policy the site already sent without its `frame-ancestors` directive, and adds `Content-Security-Policy: frame-ancestors <sources>`.
   - `wp_redirect` at priority 999 calls `bounce_external_redirect()`. On an embed request, a redirect to another origin becomes a 200 bounce page (`ppcart_embed_bounce_page()`) that loads the frame bridge and calls `ppcartNavigate()`, so the top window opens the target. A 302 would load that site inside the frame. This covers a closed cart with a redirect URL and a subscription whose confirmation redirects.
   - `template_include` at priority 999 returns the shell template.
   - `show_admin_bar` at priority 999 returns false.
   - `wp_enqueue_scripts` at priority 20 fires `ppcart_enqueue_frontend_assets`, enqueues `ppcart-embed.css` with the appearance CSS variables, the web font stylesheet, and `ppcart-embed-frame.js`.
   - `body_class` adds `ppcart-embed` and one class for each set appearance value.
5. The shell prints the content. For a product, the content is `[ppcart_form id="<ID>"]`. The shortcode prints the receipt when `ppcart-order` is in the URL. For any other allowed post, the content is the post content.
6. The frame bridge runs inside the iframe. It reports the height to the loader and routes navigations (see the next sections).

A request whose post `ppcart_embed_allows_post()` rejects keeps the theme template and the site framing headers. So the flag cannot make, for example, an account page frameable.

## The shell

`public/templates/embed/checkout.php` is a bare document: `wp_head()`, a
`<main class="ppcart-embed__main">` with the content, and `wp_footer()`. It
renders the content before `wp_head()`, as block themes do, so that inline CSS
from the checkout attaches to its stylesheet in the head.

The shell is one column, up to 1100px wide. The `html` and `body` backgrounds are
transparent unless the product sets a background color.

`PPCart_Embed_Appearance` (`includes/embed/class-ppcart-embed-appearance.php`) is
pure PHP. `sanitize()` returns all eight appearance keys and makes each invalid
value `''`. `body_classes()` and `css_variables()` emit a class and a variable
only for a set value. Every rule in `public/css/ppcart-embed.css` is scoped by
its body class, so an empty value writes no rule. The admin live preview reads
the same class and variable names from `style_map()`.

## Message protocol

All messages go through `postMessage`. Frame messages carry `source: 'ppcart-embed'`. Host messages carry `source: 'ppcart-embed-host'`.

| Direction | Message | Target origin | Sent when |
|-----------|---------|---------------|-----------|
| Frame to host | `{type: 'hello', height}` | `*` | The frame document is ready. It carries no private data. |
| Host to frame | `{type: 'init', id}` | The origin of the iframe `src` | The loader gets `hello`, and on each iframe `load` event. |
| Frame to host | `{type: 'resize', id, height}` | The recorded host origin | After `init`, when a `ResizeObserver` or `MutationObserver` fires, at most once per 16ms. |
| Frame to host | `{type: 'navigate', id, url}` | The recorded host origin | `ppcartNavigate()` gets a URL on another origin. |
| Frame to host | `{type: 'escape', id}` | The recorded host origin | The buyer presses Esc inside the frame. |

Trust rules:

- The loader matches a message to its iframe by `event.source === iframe.contentWindow` and by `event.origin`. It never trusts the claimed `id` alone.
- The frame accepts `init` only from `window.parent`. It records `event.origin` of `init` as the host origin.
- The loader follows `navigate` only for `http:` and `https:` URLs, with `window.location.assign()`.
- The loader closes the popup on `escape` only when the message comes from the popup iframe.

The height is `Math.ceil(document.documentElement.getBoundingClientRect().height)`.
`scrollHeight` is never smaller than the viewport, so a frame sized from it could
grow but never shrink.

Chrome pauses rendering of an off-screen cross-origin frame, and with it
`ResizeObserver` and `requestAnimationFrame`. So the bridge sends heights from a
16ms timer and also listens with a `MutationObserver`, which both keep running.
Without them, an inline frame below the fold kept its first height (797px for
678px of content) until it scrolled into view. RT-121 covers this.

## Navigation contract

`window.ppcartNavigate(url)` is the one entry point for script navigations in
the checkout. `public/js/ppcart-public.js` defines it as
`window.location.href = url`. Inside an embed request, the frame bridge replaces
it with this rule:

| URL | Action |
|-----|--------|
| Same origin as the frame | Navigate the frame. Add `ppcart-embed=1` when it is missing. |
| Another origin, after the handshake | Send `navigate` to the loader. The top window goes to the URL. |
| Another origin, before the handshake | Wait for the loader's `init` (it comes on load), then send `navigate`. After 3 seconds without it, try `window.top.location.href`, and if the browser blocks that, navigate the frame. |
| Not `http:` or `https:` | Do nothing. |

`window.PPCartEmbedFrame.route(url, frameOrigin)` is the pure form of the rule.

Checkout also completes with form posts. The frame bridge applies the same rule
at submit time, with a capture-phase `submit` listener and a wrapper on
`HTMLFormElement.prototype.submit`, because a programmatic `submit()` fires no
event. A same-origin `action` keeps `ppcart-embed=1`. A form that posts to
another origin gets `target="_top"`. The bridge reads the `action` attribute,
because checkout forms have an input named `action`.

Pro and any other checkout code must call `window.ppcartNavigate(url)` instead of
assigning `window.location`. A direct assignment inside the frame loads an
external payment page in the small frame, or loads a checkout page without the
embed shell.

URLs that come back in the top window must not carry the flag. The PayPal
`cancel_url` drops `ppcart-embed` in `ppcart-public.js`. The Stripe Checkout
return URL drops it on the server, in
`public/controllers/templates/hosted-checkout-controller-create-checkout-session.php`.

Payment Element methods that redirect to a bank page use Stripe `return_url` in
the current window, so they navigate the frame. Cards, wallets, and Link do not
redirect.

## Extension points

These names are a public contract. Pro may depend on them.

| Name | Type | Arguments | Use |
|------|------|-----------|-----|
| `ppcart_embed_frame_ancestors` | Filter | `array $sources`, default `['*']` | The sources in `Content-Security-Policy: frame-ancestors`. `ppcart_embed_frame_header_lines()` drops a source that is not a plain CSP source expression. With no valid source, the header is `frame-ancestors 'none'`. |
| `ppcart_embed_allows_post` | Filter | `bool $allowed`, `WP_Post $post` | Whether an embed request may frame the post. The default allows products and posts that contain `[ppcart_form]`, `[ppcart_receipt]`, or the `publishpress-cart/checkout-form` block. |
| `ppcart_product_settings_panel_after_fields` | Action | `string $tab_id`, `WP_Post $post` | Display-only output after the fields of a product settings tab. Nothing printed here is saved. The Embed tab prints its preview and code boxes here. |

To let only the merchant's own website frame the checkout:

```php
add_filter('ppcart_embed_frame_ancestors', function () {
	return [ 'https://www.merchant.example' ];
});
```

To frame an upsell page that has no checkout content:

```php
add_filter('ppcart_embed_allows_post', function ($allowed, $post) {
	return $allowed || 'special-offer' === $post->post_name;
}, 10, 2);
```

PHP helpers: `ppcart_is_embed_request()`, `ppcart_embed_request_mode()`,
`ppcart_embed_allows_post()`, `ppcart_embed_appearance($product_id)`, and
`ppcart_embed_frame_header_lines($sources)` in
`includes/embed/ppcart-embed-functions.php`.

JavaScript globals: `window.PPCartEmbed` (`scan`, `open`, `close`) on the host
page, and `window.PPCartEmbedFrame.route` and `window.ppcartNavigate` in the
frame.

## Meta keys

The Embed tab saves one meta key per field through the product metabox.
`ppcart_embed_appearance()` reads them and validates them with
`PPCart_Embed_Appearance::sanitize()`. An empty value deletes the meta and keeps
the default look.

| Meta key | Value | Invalid or empty means |
|----------|-------|------------------------|
| `_ppcart_embed_layout` | `standard` or `2-step` | The product display setting. Free has no Layout field. |
| `_ppcart_embed_accent` | `#rrggbb` | The product **Primary Color**. |
| `_ppcart_embed_text` | `#rrggbb` | The default text color. |
| `_ppcart_embed_background` | `#rrggbb` | A transparent background. |
| `_ppcart_embed_font_family` | Letters, digits, spaces, commas, hyphens, and quotes, up to 200 characters | The default font. |
| `_ppcart_embed_font_url` | An `https` URL with no spaces, quotes, `<`, or `>` | No web font. |
| `_ppcart_embed_radius` | An integer from 0 to 24 (pixels) | The default corners. |
| `_ppcart_embed_button_text` | Text up to 60 characters, with tags removed | The product **Submit Button Text**, then "Buy now". |

For a user who can edit the product, the query arg `ppcart-embed-layout=standard|2-step` overrides the saved layout, so the admin preview can show a layout before it is saved.

## Tests

| Suite | IDs | File |
|-------|-----|------|
| Unit | UT-370 to UT-372 | `tests/codeception/Unit/Embed/EmbedAppearanceTest.php` |
| Unit | UT-373, UT-374 | `tests/codeception/Unit/Embed/EmbedFrameHeadersTest.php` |
| Unit | UT-375, UT-376 | `tests/codeception/Unit/Embed/EmbedRedirectBounceTest.php` |
| Integration | IT-394 to IT-399, IT-403 | `tests/codeception/Integration/Embed/EmbedCheckoutTest.php` |
| Integration | IT-400 to IT-402 | `tests/codeception/Integration/Embed/EmbedTabSaveTest.php` |
| Harness | none | `tests/playwright/unit/embed-frame-route.spec.ts` (the `route()` rule) |
| Admin | AD-212 | `tests/playwright/admin/ad212-product-embed-tab-previews-and-saves-accent.spec.ts` |
| Regression | RT-110 to RT-122 | `tests/playwright/regression/rt110-*.spec.ts` to `rt122-*.spec.ts` |

The regression specs cover these buyer flows:

| ID | Flow |
|----|------|
| RT-110 | The inline frame follows its content height: the form, a validation message, the receipt. |
| RT-111 | A free order inside the inline frame shows the receipt in the frame. The top URL does not change. |
| RT-112 | The popup button opens an accessible dialog. Esc on the host, Esc inside the frame, and a backdrop click close it. |
| RT-113 | The popup link opens the dialog on a page with the loader. |
| RT-114 | The popup link without the loader opens a full-page embed checkout that completes a free order. |
| RT-115 | A product that redirects to an external thank-you page navigates the top window. |
| RT-116 | The saved appearance styles the form and the receipt in the frame. |
| RT-117 | A mocked Stripe one-time order completes inside the inline frame. |
| RT-118 | On a 375 x 812 viewport, the popup fills the screen and the inline form has no horizontal overflow. |
| RT-119 | Two inline embeds on one page render and size independently. |
| RT-120 | The flag on a page without checkout content keeps the theme template and the framing headers. |
| RT-121 | An inline embed below the fold fits its content before it is scrolled into view. |
| RT-122 | A server redirect to another site from an embed (a closed cart) navigates the top window. |

The regression specs serve the host page from `https://merchant.example` with
`page.route()` (`tests/playwright/support/embed-host.ts`). The page loads the
loader from the WordPress site. Chrome Local Network Access blocks a public page
from loading a loopback site, so `playwright.config.ts` starts Chromium with
`--disable-features=LocalNetworkAccessChecks`. Real host pages and shops are
public, so production does not need the flag.

The fixtures in `tests/ppcart-fixtures/ppcart-regression-fixtures.php` create the
products and the plain page. `composer test:regression:setup:mocked` writes their
paths to `.env` as `URL_EMBED_PRODUCT_FREE`, `URL_EMBED_PRODUCT_STRIPE`,
`URL_EMBED_PRODUCT_REDIRECT`, `URL_EMBED_PRODUCT_STYLED`, and
`URL_EMBED_PLAIN_PAGE`. Each spec skips when its key is missing.

To run only the embed specs:

```bash
composer test:regression:setup:mocked
REGRESSION_MOCKED_GATEWAYS=1 npx playwright test tests/playwright/regression --grep @embed
```
