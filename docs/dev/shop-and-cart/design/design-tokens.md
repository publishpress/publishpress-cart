# Shop and cart: design tokens

This file lists the real styles of the PublishPress Cart front end and admin. The Shop, Mini Cart, Cart and Cart Checkout mockups in this folder use these values. Each value has its source as `file:line`. All paths are relative to the plugin root.

Short names used below:

- `STY` = `includes/integrations/gutenberg/lib/checkout-renderer/trait-ppcart-checkout-renderer-styles.php`
- `UTIL` = `includes/integrations/gutenberg/lib/checkout-renderer/trait-ppcart-checkout-renderer-style-utilities.php`
- `PUB` = `public/css/ppcart-public.css`
- `ACC` = `includes/integrations/gutenberg/css/account-block.css`
- `SET` = `admin/css/ppcart-settings.css`
- `MBX` = `admin/css/ppcart-product-metabox.css`
- `CMP` = `admin/css/ppcart-components.css`

## 1. Two layers in the checkout block

The checkout block has two style layers. The mockups show both.

1. **Base layer.** The block's `styleSettings` default is `{}` (`includes/integrations/gutenberg/blocks/checkout-form/block.json:36-39`). With an empty object, `build_design_styles()` returns nothing (`STY:253-255`). Only `PUB` styles the form. This is the look of a new site: a black pay button, a gray total box, uppercase section titles.
2. **Token layer.** When `preset` or `accentColor` is set, the resolver adds scoped rules for the accent, radius, density and surface (`STY:235-291`). The accent default is `#2271b1` (`STY:260`).

The product template sets only `{"density":"compact"}` (`includes/integrations/gutenberg/templates/templates/product-template-get-template-content.php:29`). So the product page uses compact density with the base black button.

In the mockups, **Default** means the base layer. The preset bar at the top of each front-end page switches to the four presets.

## 2. styleSettings tokens

| Token | Values | Source |
|-------|--------|--------|
| `preset` | `minimal`, `carded`, `compact`, `contrast` | `STY:171` |
| `surfaceStyle` | `card`, `boxed` | `STY:172` |
| `density` | `compact`, `spacious` (none = default) | `STY:173` |
| `cornerRadius` | `square`, `rounded` (none = default) | `STY:174` |
| `accentColor` | any hex | `STY:179-186` |

### Preset defaults (`UTIL:11-30`)

| Preset | Accent | Surface | Density | Radius |
|--------|--------|---------|---------|--------|
| Default (no preset) | base `#000` button | none | default | base mix 0 to 4px |
| minimal | `#2271b1` | none | compact | square (0) |
| carded | `#2271b1` | card | default | rounded (10px) |
| compact | `#2271b1` | boxed | compact | square (0) |
| contrast | `#111827` | boxed | default | square (0) |

### Accent shades

The resolver mixes the accent (`STY:261-262`, `UTIL:146-164`):

- hover = accent mixed with 12% black
- soft = accent mixed with 88% white

| Accent | Hover | Soft |
|--------|-------|------|
| `#2271b1` | `#1e639c` | `#e4eef6` |
| `#111827` | `#0f1522` | `#e2e3e5` |

These values were computed with the same formula. The fallbacks in the code (`#135e96`, `#eef6fc`) are used only for an invalid color.

The accent colors the button background and border (`STY:398-406`), the hover (`STY:407-415`), the radio dot (`STY:388-392`), the checkbox mark (`STY:393-397`) and the current step (`STY:374-387`).

### Radius (`UTIL:90-101`)

`square` = 0, default = 4px, `rounded` = 10px. It applies to fields, plan options, the total box and buttons (`STY:333-365`), and to the surface (`STY:306`, `STY:320`).

### Density (`UTIL:58-127`)

| Value | compact | default | spacious |
|-------|---------|---------|----------|
| wrapper padding | 16px | 24px | 32px |
| section gap | 10px | 14px | 20px |
| field padding | 8px 10px | 10px 12px | 14px 16px |
| field height | 38px | 44px | 52px |
| option padding | 10px | 12px | 16px |
| summary padding | 10px 12px | 12px 14px | 16px 18px |
| button padding | 10px 14px | 12px 18px | 15px 20px |

### Surface (`STY:293-327`)

- `card`: white, `1px solid #e5e7eb`, shadow `0 16px 40px rgba(17,24,39,.08)`, padding = wrapper padding.
- `boxed`: `#f8fafc`, `1px solid #dcdcde`, padding = wrapper padding.

## 3. Base checkout styles (`PUB`)

| Part | Value | Source |
|------|-------|--------|
| Text color | `#232323` | `PUB:374-380` |
| Font | Helvetica Neue on `.ppcart-page`, inputs and buttons (see section 7) | `PUB:377`, `PUB:1484` |
| Base size | 14px | `PUB:378` |
| Section title (`.title`) | 12px, 600, uppercase, letter-spacing `.2em`, `#232323` | `PUB:1096-1104` |
| Required mark | `#e12c07`, bold | `PUB:1125-1128` |
| Input | `1px solid #929292`, radius 3px, padding `9px 12px`, white | `PUB:1465-1478` |
| Input focus | border `#0ca2aa` | `PUB:1490-1494` |
| Error | `#e12c07`, 1px border, radius 3px | `PUB:1502-1520` |
| Radio and checkbox | 15px box, `1px solid #929292`, black dot or check | `PUB:912-940`, `PUB:987-1000` |
| Pay button (`.ppcart-btn-block`) | `#000`, white, bold, padding 15px, full width | `PUB:1681-1693` |
| Pay button on product pages | radius 2px, 18px | `PUB:422-426` |
| Plan option row | flex, 8px gap, price 600 at the right | `PUB:1151-1154`, `PUB:1201-1207` |
| Summary rows | flex, `5px 0` | `PUB:854-863` |
| Total box (`.total`) | `#f3f5f6`, padding 15px, bold 18px, radius 4px | `PUB:1294-1304` |
| Total sub-line | 13px, normal weight, right | `PUB:1310-1320` |
| Badge | pill, `#eee` on `#7c7c7c`, uppercase, `.05em` | `PUB:836-846` |
| Order bump frame | `1px dashed #000`, radius 3px, padding 20px | `PUB:795-799` |
| Coupon button | `1px solid #666`, transparent, 12px 600 | `PUB:1257-1268` |
| Footer text | 11px, opacity .85 | `PUB:608-613` |

### Split layout

- The registered template `cart-checkout-split` uses a cover block with 60/40 columns and a white checkout card with radius 4px (`includes/integrations/gutenberg/templates/templates/ppcart-product-template-register.php:41-53`, `.../product-template-get-template-content.php:8-38`).
- The older split form puts the summary on `.bg-light` `#f2f5f8` (`PUB:3074-3076`).
- Its total line has no box: 20px, 600, letter-spacing `-0.6px` (`PUB:3223-3234`).

## 4. Account blocks (`ACC`), the newest front end

| Part | Value | Source |
|------|-------|--------|
| Card | white, `1px solid #e5e7eb`, radius 12px, shadow `0 1px 2px rgba(0,0,0,.04)` | `ACC:1057-1064`, `ACC:1212-1220` |
| Action button | `#f1f5f9` on `#0f172a`, pill, 44px tall, 15px 500 | `ACC:858-884` |
| Status pill | `#f1f5f9`, 12px, uppercase, `.04em`, pill | `ACC:1258-1275` |
| Status pill, success | `#dcfce7` on `#166534` | `ACC:1287-1293` |
| Detail backdrop | `rgba(15,23,42,.58)` | `ACC:675-679` |
| Detail panel | white, radius 18px, shadow `0 24px 80px rgba(15,23,42,.28)` | `ACC:687-697` |
| Slide-right panel | radius 0, full height, `min(760px, 92vw)` | `ACC:757-763` |
| Close button | 36px circle, `#17201b`, white `×`, hover `#0f766e` | `ACC:723-744` |
| Muted text | `#707070` | `ACC:97`, `ACC:158` |

## 5. Admin (`SET`, `MBX`, `CMP`)

| Part | Value | Source |
|------|-------|--------|
| Tokens | text `#1a1d24`, muted `#5b6473`, primary `#0a47c2`, hover `#0838a0`, soft `#e8efff`, border `#e3e5ea` / `#d4d7de` | `SET:11-45`, `CMP:11-41` |
| Top bar | 64px, white, brand mark gradient `#0a47c2` to `#6244ff` | `SET:125-193` |
| Sidebar | 212px, `#f8fafc`, groups Store / Communication & Tools / Advanced | `SET:264-413`, `admin/partials/settings-page/metadata.php:108-113` |
| Active nav item | `#3858e9`, 600, 3px bar | `SET:355-368` |
| Panel title | 24px, 700 | `SET:456-464` |
| Card | white, `rgba(226,232,240,.82)` border, radius 8px | `SET:526-533` |
| Card title bar | `#655997`, white 15px 600, border `#554a82` | `SET:535-546`, `MBX:254-266` |
| Field row | label 280px, 500 13.5px, padding `18px 24px`, row rule `rgba(226,232,240,.58)` | `SET:1560-1595` |
| Help text | 12.5px, muted | `SET:1632-1638` |
| Input | `1px solid #d4d7de` (settings) or `#868e9b` (metabox), radius 6px, min 38px | `SET:1661-1681`, `CMP:130-153` |
| Select | chevron in a 40px `#f8fafc` box with a rule | `SET:1698-1706` |
| Toggle | 40x22, off `#7d8796`, on `#0a47c2` | `SET:1803-1853`, `CMP:279-320` |
| Primary button | `#0a47c2`, radius 8px, 38px, 600 | `SET:3354-3376` |
| Save bar | warning dot `#b95b00` on `#fdf2dc` | `SET:3245-3261` |
| Warning notice | `#fdf2dc`, border `#f0d9a8`, left edge `#dba617` | `SET:492-496` |
| Metabox tab list | 212px, groups Sell / After purchase / Grow sales, horizontal under 720px | `MBX:52-176`, `MBX:866`, `admin/metaboxes/traits/templates/product-metabox-render-get-product-setting-tab-groups.php:15-39` |
| Metabox panel | `#fafbfd`, padding `28px 32px 40px`, label above control | `MBX:186-206`, `MBX:288-307` |
| General tab sections | Product details; Page header (with `_ppcart_hide_title`, label "Hide page title") | `admin/metaboxes/traits/templates/product-metabox-render-tab-sections.php:16-26` |

## 6. How the new blocks reuse the tokens

Spec section 8 says that all new blocks accept the same `styleSettings` object, and that the resolver moves into a shared helper. The mockups follow this mapping:

| New part | Token or base style |
|----------|---------------------|
| Primary button (Add to cart, Checkout, Pay) | accent, button padding, radius; Default = base `#000` bold button |
| Secondary button (Subscribe, View, Buy now) | coupon-button shape: `1px solid #666`, transparent |
| ✓ In cart button, Purchased badge, added toast | account success pill `#dcfce7` / `#166534` |
| Other badges (Subscription, Buy now only) | account status pill `#f1f5f9` / `#0f172a` |
| Section titles (Order summary, Your cart) | `.title`: 12px, 600, uppercase, `.2em` |
| Plan dropdown, form fields | input: `#929292` border, field height and padding, radius |
| Selected plan or payment method | accent border + accent soft |
| Product card, cart lines, cart summary | account card; `surfaceStyle` sets its background, border and shadow |
| Subtotal (mini cart) and Total (cart) | `.total` box `#f3f5f6`, summary padding |
| Cart checkout summary column | split `.bg-light` `#f2f5f8` with the 20px total line |
| Mini cart drawer | account slide-right panel, backdrop and close button; width 380px |
| Price-changed notice | admin warning notice colors with `#232323` text, radius token |
| Pro slot | order bump dashed frame `1px dashed #000` |
| Muted text | `#707070` |
| Font | inherit the theme |

**Shop and surface.** In the checkout, `surfaceStyle` styles the whole form wrapper. In the Shop, the mockup applies it to each product card. The grid itself has no frame.

**CSS custom properties.** The mockups use `--pc-accent`, `--pc-accent-hover`, `--pc-accent-soft`, `--pc-radius`, `--pc-field-h`, `--pc-field-pad`, `--pc-btn-pad`, `--pc-gap`, `--pc-wrap-pad`, `--pc-option-pad`, `--pc-summary-pad`, `--pc-surface-*` and `--pc-card-*` on the block wrapper. The real CSS should use the `--ppcart-*` names from spec section 8.

## 7. Do not copy

| Existing style | Reason |
|----------------|--------|
| Fixed fonts: Helvetica Neue (`PUB:377`, `PUB:1484`), Montserrat (`PUB:3020`, `ACC:540`), PT Serif and Rubik in the split form (`PUB:3091`, `PUB:3130`, `PUB:3202-3207`) | They fight the theme font. One page can show four type families. Inherit the theme font. |
| Teal focus `#0ca2aa` (`PUB:1490-1494`) | It ignores the accent. Use an accent focus ring. |
| Badge `#7c7c7c` on `#eee` (`PUB:836-846`) | Contrast is 3.6:1, below the 4.5:1 that small text needs. Use the account pill `#0f172a` on `#f1f5f9`. |
| `#b95b00` warning text on `#fdf2dc` (`SET:3245-3261`) | Contrast is 4.15:1. In the notice, use `#232323` text and keep the color in the border. |
| Hover = accent + 12% black for dark accents (`STY:261`) | For `#111827` the hover is `#0f1522`. Nobody can see that change. Lighten dark accents instead. |
| Account login green palette `#26352e`, `#2f6f68`, radius 12 to 18px (`ACC:387-470`) | It is a separate theme for the login form. It does not follow `accentColor`. |
| Fixed account button width `120px` (`ACC:883`) | Longer labels and translations get clipped. |
| Order bump hover that changes margins (`PUB:508-513`) | It moves the layout on hover. |
| Split form `height: 100vh`, `90px 130px` padding and `:has()` resets of theme padding (`PUB:3107-3110`, `PUB:3148-3156`, `PUB:3345-3373`) | It breaks inside theme layouts. The cart blocks must sit in normal page flow. |
| `.ppcart * { box-sizing: border-box !important }` (`PUB:601-603`) | `!important` on `*` overrides theme and Pro styles inside the block. |
| Several near-identical grays: `#eee`, `#f3f5f6`, `#f2f5f8`, `#f1f5f9`, `#f8fafc` | Pick one panel gray and one chip gray for the new CSS. |
| Two default looks: base `#000` vs resolver `#2271b1` | The shared resolver needs one default. The mockups use the base look, because the cart checkout is the existing block with empty settings. |
