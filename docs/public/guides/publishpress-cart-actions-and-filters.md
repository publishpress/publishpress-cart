---
title: "PublishPress Cart Actions and Filters"
order: 35
doc_category: publishpress-cart
section: Developers
---

# PublishPress Cart Actions and Filters

Table of Contents

- [Naming](#0-toc-title)
- [How to use a hook](#1-toc-title)[Example: run code after checkout](#2-toc-title)[Example: run code when an order is paid](#3-toc-title)[Example: change the terms text on the checkout form](#4-toc-title)[Check that PublishPress Cart is active](#5-toc-title)
- [Checkout and order form](#6-toc-title)
- [Order and subscription status](#7-toc-title)
- [Orders and refunds](#8-toc-title)
- [Subscriptions and payment plans](#9-toc-title)
- [Payment gateways](#10-toc-title)
- [Prices, currency, and tax](#11-toc-title)
- [Coupons](#12-toc-title)
- [Upsells, downsells, and order bumps](#13-toc-title)
- [Products](#14-toc-title)
- [Customer account](#15-toc-title)
- [Emails and notifications](#16-toc-title)
- [Integrations](#17-toc-title)
- [Templates and shortcodes](#18-toc-title)
- [Admin and settings](#19-toc-title)
- [General](#20-toc-title)
- [Moving from StudioCart](#21-toc-title)

[PublishPress Cart](https://publishpress.com/links/cart) has actions and filters that let you change how the plugin works. You can use them in your own plugin, or in your theme’s `functions.php` file.

- An **action** lets you run your own code at a specific time. For example, after a customer completes checkout.
- A **filter** lets you change a value before [PublishPress Cart](https://publishpress.com/links/cart) uses it. For example, the text of the terms checkbox.

This page lists the hooks that PublishPress Cart fires. Hooks marked **Pro** fire only when PublishPress Cart Pro is active.

## Naming [#](#0-toc-title)

All PublishPress Cart hooks start with `ppcart_`. A few admin filters use other forms, such as `ppcart-settings-page-title` and `ppcartsection-title-{$key}`.

Some hook names have a variable part, shown in braces. For example, `ppcart_order_refund_{$pay_method}` fires as `ppcart_order_refund_stripe` for a Stripe order.

## How to use a hook [#](#1-toc-title)

Use `add_action()` for an action and `add_filter()` for a filter. Set the last argument to the number of parameters that your function needs.

### Example: run code after checkout [#](#2-toc-title)

```
add_action( 'ppcart_checkout_complete', 'myplugin_checkout_complete', 10, 2 );

function myplugin_checkout_complete( $order_id, $product ) {    
// Your code here. For example, send the order to an external service.
}
```

### Example: run code when an order is paid [#](#3-toc-title)

```
add_action( 'ppcart_order_complete', 'myplugin_order_paid', 10, 3 );

function myplugin_order_paid( $status, $order_info, $order_type ) {    
// $order_info is an array with the order details.
}
```

### Example: change the terms text on the checkout form [#](#4-toc-title)

```
add_filter( 'ppcart_checkout_page_terms_text', 'myplugin_terms_text', 10, 2 );

function myplugin_terms_text( $terms_text, $product ) {    
return 'I agree to the terms of sale.';
}
```

A filter function must always return a value. If you do not want to change the value, return it unchanged.

### Check that PublishPress Cart is active [#](#5-toc-title)

```
if ( defined( 'PPCART_VERSION' ) ) {    
add_action( 'ppcart_checkout_complete', 'myplugin_checkout_complete', 10, 2 );
}
```

## Checkout and order form [#](#6-toc-title)

| Hook | Type | Version | Description | Parameters |
| --- | --- | --- | --- | --- |
| `ppcart_after_buy_button` | action | Free | Fires after the order form submit (buy) button. | `$ppcart_product` (object) Product being checked out |
| `ppcart_after_load_from_post` | action | Free | Fires after the order object finishes loading all data from the posted checkout form. Note: The second parameter is `false` or the `$_POST` data, depending on the order type. | `$order` (PPCart_Order) Order being built  
`$posted` (array) Posted checkout data |
| `ppcart_after_payment_info` | action | Free | Fires after the payment details and address fields on the checkout form. | `$post_id` (int) Product post ID  
`$hide_labels` (bool) Whether to hide field labels |
| `ppcart_after_setup_atts_from_post` | action | Free | Fires after the order object reads its basic attributes from the posted checkout form. | `$order` (PPCart_Order) Order being built  
`$posted` (array) Posted checkout data |
| `ppcart_after_step_1_button` | action | Free | Fires after the step 1 ‘next’ button on the multi-step checkout form. | — |
| `ppcart_after_summary_items` | action | Free | Fires after the order summary items, before the order total row. | `$ppcart_product` (object) Product being checked out |
| `ppcart_before_buy_button` | action | Free | Fires before the order form submit (buy) button. | `$ppcart_product` (object) Product being checked out |
| `ppcart_before_payment_info` | action | Free | Fires before the payment details and address fields on the checkout form. | `$post_id` (int) Product post ID |
| `ppcart_buy_button_icon` | action | Free | Fires on each side of the buy button label so you can print an icon. Note: Fires two times: once for `left` and once for `right`. | `$ppcart_product` (object) Product being checked out  
`$position` (string) ‘left’ or ‘right’ |
| `ppcart_buy_button_subtext` | action | Free | Fires inside the buy button, after the label, so you can print subtext. | `$ppcart_product` (object) Product being checked out |
| `ppcart_card_details_fields` | action | Free | Fires where the checkout renders the card details and related fields. | `$post_id` (int) Product post ID  
`$hide_labels` (bool) Whether to hide field labels  
`$plan` (object\|null) Selected plan |
| `ppcart_checkout_arranged_core_card_details_callbacks` | filter | Free | Filters the list of core callbacks, with priorities, that render the card details section of the checkout. Return: array — the callbacks list. | `$callbacks` (array) List of items with ‘callback’ and ‘priority’ keys |
| `ppcart_checkout_complete` | action | Free | Fires once per order when checkout completes and the customer reaches the confirmation step. | `$order_id` (int) Order post ID  
`$product` (object\|null) Product object |
| `ppcart_checkout_form` | action | Free | Fires where the checkout form body renders, after the form opens. Note: Some checkout templates pass only the first two parameters. | `$product_id` (int) Product ID  
`$hide_labels` (bool) Whether to hide field labels  
`$plan` (object\|null) Selected plan |
| `ppcart_checkout_form_close` | action | Free | Fires where the checkout form closes, after the form body. | — |
| `ppcart_checkout_form_fields` | action | Free | Fires at the end of the plan and coupon section of the checkout form so you can add extra fields. | `$post_id` (int) Product ID being checked out  
`$hide_labels` (bool) Whether the form hides field labels |
| `ppcart_checkout_form_open` | action | Free | Fires where the checkout form opens, before the form body. | `$product_id` (int) Product ID |
| `ppcart_checkout_form_price_checked` | filter | Free | Filters whether a payment plan radio option renders as selected on the checkout form. Return: string — the checked attribute value (for example ‘checked’ or an empty string). | `$checked` (string) Checked attribute value for the plan option  
`$item` (mixed) Plan or price item being rendered |
| `ppcart_checkout_form_scripts` | action | Free | Fires after the checkout form renders, so code can print inline scripts for the form. Note: Some checkout templates pass only `$product_id`. | `$product_id` (int) Product ID  
`$coupon` (mixed) Coupon value from the request |
| `ppcart_checkout_form_validation_messafes` | filter | Free | Filters the list of validation error messages for the checkout form. Return: array — error messages; a non-empty array fails validation. Note: The name is spelled `messafes`. | `$messages` (array) Validation error messages |
| `ppcart_checkout_hide_labels` | filter | Free | Filters whether the checkout form hides its field labels. Return: bool — true to hide field labels. | `$hide_labels` (bool) False by default  
`$ppcart_product` (object) Product object |
| `ppcart_checkout_page_error` | filter | Free | Filters the list of error messages the checkout page shows. Return: array — the error messages. | `$errors` (array) Error messages |
| `ppcart_checkout_page_heading` | action | Free | Fires where the checkout page heading renders, before the form opens. | `$product_id` (int) Product ID |
| `ppcart_checkout_page_privacy_text` | filter | Free | Filters the privacy policy checkbox label on the checkout form. Return: string — the label HTML. | `$privacy_text` (string) Label text (HTML allowed by wp_kses_post)  
`$ppcart_product` (object) Product being checked out |
| `ppcart_checkout_page_terms_text` | filter | Free | Filters the terms and conditions checkbox label on the checkout form. Return: string — the label HTML. | `$terms_text` (string) Label text (HTML allowed by wp_kses_post)  
`$ppcart_product` (object) Product being checked out |
| `ppcart_checkout_should_guard_duplicate_render` | filter | Free | Filters whether the plugin blocks a second render of the same product checkout in one request. Return: bool — false to allow duplicate renders. | `$guard` (bool) Whether to guard against duplicate render; default true  
`$context` (array) Render context, such as product ID |
| `ppcart_checkout_step_viewed` | action | Free | Fires when a customer views a funnel step after checkout has already been claimed as complete. | `$order_id` (int) Order post ID  
`$product` (object\|null) Product object  
`$step` (int) Funnel step number from the request |
| `ppcart_closed_message` | action | Free | Fires where the checkout shows the closed-cart message, when the cart window is closed. | `$product_id` (int) Product ID |
| `ppcart_consent_required` | filter | Free | Filters whether the marketing consent checkbox is required, on the form and in server-side validation. Return: bool — true if the consent checkbox is required. | `$optin_required` (bool) Whether consent is required for the product  
`$ppcart_product` (object) Product being checked out |
| `ppcart_expected_request_fields` | filter | Free | Filters the fields a request payload may contain. Undeclared fields are dropped, so extensions register their own fields here. Return: array — the field schema. | `$schema` (array) Map of field name to sanitization type  
`$context` (string) Payload context, for example checkout or paypal_ipn  
`$source` (array) Raw request array |
| `ppcart_host_purchase_url` | filter | Free | Filters the URL where a customer goes after a successful hosted checkout purchase. Return: string — the URL. | `$url` (string) Confirmation or redirect URL  
`$order_id` (int) Order ID  
`$product_id` (int) Product ID |
| `ppcart_js_purchase_tracking` | action | Free | Fires in the order tracking script output, so code can print purchase tracking JavaScript for an order. | `$ppcart_order` (PPCart_Order) Order that was placed |
| `ppcart_order_form_address_fields` | filter | Free | Filters the address fields of the checkout form, for rendering and for validation. Return: array — the address fields. | `$address_fields` (array) Address field definitions  
`$product` (object) Product object |
| `ppcart_order_form_fields` | filter | Free | Filters the default fields of the checkout form, for rendering and for validation. Return: array — the form fields. | `$fields` (array) Field definitions  
`$product` (object) Product object |
| `ppcart_order_summary` | action | Free | Fires where the checkout renders the order summary after the card details fields. | `$post_id` (int) Product post ID  
`$plan` (object\|null) Selected plan |
| `ppcart_order_summary_item_name` | filter | Free | Filters the product name shown for an item in the order summary. Return: string — the item name to display. | `$name` (string) Item product name  
`$item` (array) Order item |
| `ppcart_order_summary_items` | action | Free | Fires where the checkout page shows the order summary items. | `$product_id` (int) Product ID |
| `ppcart_orderform_before_payment_plans` | action | Free | Fires before the payment plans section of the order form. | `$post_id` (int) Product ID being checked out |
| `ppcart_orderform_before_product_list` | action | Pro | Fires before the product list in the quantity checkout form. | `$post_id` (int) Product ID being checked out |
| `ppcart_payment_confirmation` | action | Free | Fires where the checkout page shows the payment confirmation, or the closed state. | `$product_id` (int) Product ID |
| `ppcart_payment_method_change` | action | Free | Fires after the checkout form so code can add the payment method change section. | `$product_id` (int) Product ID |
| `ppcart_plan_heading` | filter | Free | Filters the heading text shown above the payment plans on the checkout form. Return: string — the heading text. | `$plan_heading` (string) Heading text; defaults to ‘Payment Plan’  
`$product_id` (int) Product ID ($ppcart_product->ID) |
| `ppcart_plan_text_loading_processing` | filter | Free | Filters the button text shown while checkout loads after the customer clicks Order Now. Return: string — the button text. | `$text` (string) Button text; default ‘Order Now’ |
| `ppcart_skip_default_order_summary` | filter | Free | Filters whether the checkout skips its default order summary items block. Return: bool — true to skip the default order summary. | `$skip_default` (bool) True when the content already holds the order summary shortcode  
`$content` (string) Post content being checked |
| `ppcart_step_1_button_icon` | action | Free | Fires on each side of the step 1 button label so you can print an icon. Note: Fires two times: once for `left` and once for `right`. | `$ppcart_product` (object) Product being checked out  
`$position` (string) ‘left’ or ‘right’ |
| `ppcart_step_1_button_subtext` | action | Free | Fires inside the step 1 button, after the label, so you can print subtext. | `$ppcart_product` (object) Product being checked out |
| `ppcart_validate_custom_fields` | filter | Free | Filters the list of required custom fields that checkout validation checks. Return: array — the required fields. | `$required` (array) Required field definitions  
`$ppcart_product` (object) Product being purchased |

## Order and subscription status [#](#7-toc-title)

| Hook | Type | Version | Description | Parameters |
| --- | --- | --- | --- | --- |
| `ppcart_order_complete` | action | Free | Fires when an order is paid. | `$status` (string) Order or subscription status  
`$order_info` (array) Order data  
`$order_type` (string) Order type, for example ‘main’, ‘upsell’, ‘downsell’, or ‘bump’ |
| `ppcart_order_marked_complete` | action | Free | Fires when an order is marked as completed. | `$status` (string) Order or subscription status  
`$order_info` (array) Order data  
`$order_type` (string) Order type, for example ‘main’, ‘upsell’, ‘downsell’, or ‘bump’ |
| `ppcart_order_pending` | action | Free | Fires when an order is pending, for example a cash-on-delivery order. | `$status` (string) Order or subscription status  
`$order_info` (array) Order data  
`$order_type` (string) Order type, for example ‘main’, ‘upsell’, ‘downsell’, or ‘bump’ |
| `ppcart_order_refunded` | action | Free | Fires when an order is refunded. | `$status` (string) Order or subscription status  
`$order_info` (array) Order data  
`$order_type` (string) Order type, for example ‘main’, ‘upsell’, ‘downsell’, or ‘bump’ |
| `ppcart_renewal_failed` | action | Free | Fires when a subscription renewal payment fails. | `$status` (string) Order or subscription status  
`$order_info` (array) Order data  
`$order_type` (string) Order type, for example ‘main’, ‘upsell’, ‘downsell’, or ‘bump’ |
| `ppcart_renewal_payment` | action | Free | Fires when a subscription renewal payment succeeds. | `$status` (string) Order or subscription status  
`$order_info` (array) Order data  
`$order_type` (string) Order type, for example ‘main’, ‘upsell’, ‘downsell’, or ‘bump’ |
| `ppcart_subscription_active` | action | Free | Fires when a subscription becomes active. | `$status` (string) Order or subscription status  
`$order_info` (array) Order data  
`$order_type` (string) Order type, for example ‘main’, ‘upsell’, ‘downsell’, or ‘bump’ |
| `ppcart_subscription_canceled` | action | Free | Fires when a subscription is canceled. | `$status` (string) Order or subscription status  
`$order_info` (array) Order data  
`$order_type` (string) Order type, for example ‘main’, ‘upsell’, ‘downsell’, or ‘bump’ |
| `ppcart_subscription_completed` | action | Free | Fires when a subscription is marked as completed. | `$status` (string) Order or subscription status  
`$order_info` (array) Order data  
`$order_type` (string) Order type, for example ‘main’, ‘upsell’, ‘downsell’, or ‘bump’ |
| `ppcart_subscription_past_due` | action | Free | Fires when a subscription payment is past due. | `$status` (string) Order or subscription status  
`$order_info` (array) Order data  
`$order_type` (string) Order type, for example ‘main’, ‘upsell’, ‘downsell’, or ‘bump’ |
| `ppcart_subscription_paused` | action | Free | Fires when a subscription is paused. | `$status` (string) Order or subscription status  
`$order_info` (array) Order data  
`$order_type` (string) Order type, for example ‘main’, ‘upsell’, ‘downsell’, or ‘bump’ |
| `ppcart_subscription_trialing` | action | Free | Fires when a subscription starts a free trial. | `$status` (string) Order or subscription status  
`$order_info` (array) Order data  
`$order_type` (string) Order type, for example ‘main’, ‘upsell’, ‘downsell’, or ‘bump’ |

## Orders and refunds [#](#8-toc-title)

| Hook | Type | Version | Description | Parameters |
| --- | --- | --- | --- | --- |
| `ppcart-metabox-title-order-details` | filter | Free | Filters the title of the Customer and Billing Details meta box on order and subscription edit screens. Return: string – the meta box title. | `$title` (string) Default meta box title |
| `ppcart-metabox-title-order-notes` | filter | Free | Filters the title of the Order Notes meta box on order and subscription edit screens. Return: string – the meta box title. | `$title` (string) Default meta box title |
| `ppcart_admin_order_child_order_label` | filter | Free | Filters the label for a child order in the related orders table on the order edit screen. Return: string – the label. | `$label` (string) Default label, “Child Order”  
`$child_order` (object) Child order object  
`$order_data` (object) Current order data |
| `ppcart_admin_order_item_type_labels` | filter | Free | Filters the map of item type slugs to display labels for line items in the order edit screen. Return: array – the item type labels. | `$item_type_labels` (array) Map of item type slug to label  
`$order_data` (object) Current order data  
`$item` (array) Current line item |
| `ppcart_admin_order_related_context` | filter | Free | Filters the related-order context (whether to show it, item name, parent IDs) in the order edit screen line items. Return: array – the context array. | `$related_order_context` (array) Array with show_related, item_name, parent_id, and parent_prod_id  
`$order_data` (object) Current order data |
| `ppcart_after_downloads_attached_to_order` | action | Free | Fires after the plugin inserts a download access row for a purchased file. | `$file_id` (int) ID of the inserted download row  
`$args` (array) Data inserted for the download row |
| `ppcart_after_order_load_from_post` | filter | Free | Filters the order object right after it is loaded from the posted data. Return: PPCart_Order – the order. | `$order` (PPCart_Order) Order loaded from the request |
| `ppcart_after_order_paid` | action | Free | Fires when integrations run after an order or subscription changes status, such as paid. Note: Fires for every order status, not only `paid`. Check `$status` in your function. | `$status` (string) New status, for example ‘paid’  
`$order_data` (array) Order data  
`$event_type` (string) Event type, default ‘order’ |
| `ppcart_before_create_main_order` | action | Free | Fires before the plugin creates the main order during checkout, for Stripe, PayPal, and subscriptions. | — |
| `ppcart_before_order_refund` | action | Free | Fires before the plugin processes a refund for an order. | `$data` (array) Refund data, including id and refund_amount |
| `ppcart_before_order_save` | filter | Free | Filters the order data array before the plugin saves the order to the database. Return: array — the order data. | `$order_info` (array) Order data  
`$subscription` (mixed) Subscription context for this order  
`$controller` (object) Order controller ($this) |
| `ppcart_customer_defaults` | filter | Free | Filters the default customer field values used when the order object initializes. Return: array — the default customer fields. | `$customer_defaults` (array) Default customer fields |
| `ppcart_file_download_db_args` | filter | Free | Filters the data saved in the download row when the plugin attaches a file to an order. Return: array — the download row data. | `$args` (array) Download row data  
`$file` (array) Product file definition  
`$order` (PPCart_Order) Order the file is attached to |
| `ppcart_fter_order_created` | action | Free | Fires after an order or subscription record is created during checkout. Note: The name is spelled `fter`, without the first `a`. | `$post_id` (int) Order or subscription post ID  
`$status` (string) Post status of the record  
`$order` (array) Order data used to create the record |
| `ppcart_invoice_format` | filter | Free | Filters the invoice number format used when the plugin creates invoices. Return: string — the invoice format. | `$format` (string) Invoice format; default from the _ppcart_invoice_format option |
| `ppcart_is_order_complete` | filter | Free | Filters whether a saved order counts as complete, which decides if the order-complete actions run. Return: bool — true to run the order-complete flow. | `$status` (bool\|string) Current completion status  
`$post_id` (int) Order post ID |
| `ppcart_order` | filter | Free | Filters the PPCart_Order object after the plugin loads it. Return: PPCart_Order — the order object. | `$order` (PPCart_Order) Order object |
| `ppcart_order_after_primary_integration_action` | action | Free | Fires after the plugin runs the primary order status action (such as order complete or refunded). | `$action` (string) Name of the action that just ran  
`$status` (string) Order status  
`$order_info` (array) Order data  
`$order_type` (string) Order type  
`$event_type` (string) Event type, for example order  
`$order` (PPCart_Order\|PPCart_Subscription) Order or subscription object  
`$renewal_types` (string[]) Action names that count as renewals |
| `ppcart_order_before_complete_integrations` | action | Free | Fires before the order-complete action runs for a non-renewal order. | `$order_id` (int) Order post ID  
`$order_info` (array) Order data |
| `ppcart_order_created` | action | Free | Fires after a new order post and its items are saved. | `$order` (PPCart_Order) Order that was created |
| `ppcart_order_details` | action | Free | Fires inside the order details section of the order info meta box so you can add extra detail rows. | `$order_data` (object) Current order data |
| `ppcart_order_details_link` | filter | Free | Filters the payment details link shown in the order info meta box. Return: string – the link URL. | `$order_details_link` (string) Default link to the payment details  
`$order_data` (object) Current order data |
| `ppcart_order_item_meta` | filter | Free | Filters the meta definitions of an order item when the order item object initializes. Return: array — the meta definitions. | `$meta` (array) Order item meta definitions |
| `ppcart_order_lead` | action | Free | Fires when an order has the lead status, instead of the normal order status actions. | `$status` (string) Order status; lead  
`$order_info` (array) Order data  
`$order_type` (string) Order type; main |
| `ppcart_order_related_orders` | action | Free | Fires after the line items on the order edit screen so you can output related order details. | `$order_data` (object) Current order data |
| `ppcart_order_setup_purchase_note` | filter | Free | Filters the purchase note that the order stores when it loads from the posted form. Return: string\|null — the purchase note text. | `$purchase_note` (string\|null) Purchase note, null by default  
`$ppcart_product` (object) Product being purchased  
`$order` (PPCart_Order) Order being built |
| `ppcart_order_updated` | action | Free | Fires after an existing order post and its items are updated. | `$order` (PPCart_Order) Order that was updated |
| `ppcart_payment_intent` | filter | Free | Filters the payment intent ID shown for an order on the order edit screen. Return: string – the payment intent ID. | `$intent_id` (string) Payment intent ID stored on the order  
`$order_data` (object) Current order data |
| `ppcart_payment_method` | filter | Free | Filters the payment method name shown for an order on the order edit screen. Return: string – the payment method. | `$pay_method` (string) Payment method stored on the order  
`$order_data` (object) Current order data |
| `ppcart_ransaction_id` | filter | Free | Filters the transaction ID the plugin finds for an order, before it saves it to order meta. Return: string\|false — the transaction ID. Note: The name is spelled `ransaction`, without the first `t`. | `$txnid` (string\|false) Transaction ID  
`$method` (string) Payment method  
`$order_id` (int) Order ID |

## Subscriptions and payment plans [#](#9-toc-title)

| Hook | Type | Version | Description | Parameters |
| --- | --- | --- | --- | --- |
| `ppcart_after_subscription_load_from_order` | action | Free | Fires after a subscription object finishes loading its data from an order. | `$sub` (PPCart_Subscription) Subscription object  
`$order` (PPCart_Order) Source order |
| `ppcart_bulk_sync_subscription_limit` | filter | Free | Filters the maximum number of subscriptions that the bulk sync action syncs from Stripe in one request. Return: int – the maximum number of subscriptions. | `$sync_limit` (int) Default limit, 20 |
| `ppcart_cancel_subscription` | filter | Free | Filters whether a subscription was canceled, after the built-in Stripe cancel logic ran. Gateways can handle the cancel here. Return: bool — true if the subscription is canceled. | `$canceled` (bool) Whether the subscription was canceled  
`$sub` (PPCart_Subscription) Subscription object  
`$sub_id` (string\|int) Subscription ID  
`$now` (bool) Whether to cancel immediately  
`$options` (array) Cancel options, such as cancel_timing and refund_action |
| `ppcart_checkout_form_pay_options` | filter | Free | Filters the payment options (plans) the checkout form lists for a product. Return: array — the payment options; a non-array becomes an empty list. | `$items` (array) Payment options  
`$ppcart_product` (object) Product object |
| `ppcart_checkout_stripe_subscription_args` | filter | Free | Filters the arguments the plugin sends to Stripe to create a subscription at checkout. Return: array — the Stripe subscription arguments; a non-array stops checkout with an error. | `$args` (array) Stripe subscription create arguments  
`$order` (PPCart_Order) Order object  
`$sub` (PPCart_Subscription) Subscription object |
| `ppcart_format_subscription_order_detail` | filter | Free | Filters the subscription summary text shown for an order item. Return: string — the subscription summary. | `$summary` (string) Plain payment terms of the subscription  
`$terms` (string) Same plain payment terms (repeated)  
`$plan` (mixed) Plan of the order  
`$free_trial_days` (int\|string) Free trial days  
`$sign_up_fee` (float\|string) Sign-up fee  
`$sub_discount` (mixed) Subscription discount  
`$sub_discount_duration` (mixed) Subscription discount duration |
| `ppcart_get_sub_args` | filter | Free | Filters the get_posts arguments used to find a subscription by its ID. Return: array — the get_posts arguments. | `$args` (array) get_posts arguments  
`$sub_id` (string) Subscription ID |
| `ppcart_integration_plan_targets` | filter | Free | Filters the list of payment plans that integrations can target in the product settings. Return: array – the map of plan ID to plan name. | `$integrations` (array) Map of plan ID to plan name  
`$plansOnly` (string\|bool) Restricts the list: false for all, ‘coupons’ for paid plans, or a truthy value for recurring plans only |
| `ppcart_is_sub_type_valid_for_cancel` | filter | Free | Filters whether a subscription type can be cancelled by the customer. Return: bool — true to allow cancellation. | `$is_cancellable` (bool) Default is true when sub_installments equals ‘-1’ (ongoing)  
`$subscription_order` (object) Subscription order data |
| `ppcart_is_sub_type_valid_for_pause_restart` | filter | Free | Filters whether a subscription type can be paused and resumed by the customer. Return: bool — true to allow pause and resume. | `$is_pausable` (bool) Default is true when sub_installments equals ‘-1’ (ongoing)  
`$subscription_order` (object) Subscription order data |
| `ppcart_pay_plan_field_sections` | filter | Free | Filters the section layout for the payment plan fields in the product plan editor. Return: array – the sections. | `$sections` (array) Sections, each with a title, type, and list of field keys |
| `ppcart_pay_plan_fields` | filter | Free | Filters the field definitions for a payment plan in the product plan editor. Return: array – the field definitions. | `$fields` (array) Field definitions |
| `ppcart_pay_plan_recurring_fields` | filter | Free | Filters the field definitions for recurring payment plans in the product plan editor. Return: array – the field definitions (non-array values are ignored). | `$fields` (array) Field definitions |
| `ppcart_plan` | filter | Free | Filters the raw plan option data before the plugin builds the plan array. Return: array — the plan option data. | `$option` (array) Plan option data  
`$sale` (string) ‘sale_’ when the product is on sale, otherwise an empty string |
| `ppcart_plan_at_checkout` | filter | Free | Filters the payment plan that the order uses at checkout. Return: object\|false — the plan to use. | `$plan` (object\|false) Plan object from ppcart_plan()  
`$product_id` (int) Product ID |
| `ppcart_plan_data` | filter | Free | Filters the final plan data array. Return: array — the plan data. | `$plan` (array) Plan data  
`$option` (array) Plan option data  
`$sale` (string) ‘sale_’ when on sale, otherwise an empty string |
| `ppcart_plan_subscription_features` | filter | Free | Filters the plan array for recurring plans, so extensions can add subscription features such as trial days. Return: array — the plan data. | `$plan` (array) Plan data  
`$option` (array) Plan option data  
`$sale` (string) ‘sale_’ when on sale, otherwise an empty string |
| `ppcart_plan_text_and_a` | filter | Free | Filters the ‘and a’ text used in plan terms in front-end script messages. Return: string — the text. | `$text` (string) Translated text; default ‘and a’ |
| `ppcart_plan_text_day_free_trial` | filter | Free | Filters the free-trial wording used in plan terms. Return: string — the wording. | `$text` (string) Wording |
| `ppcart_plan_text_sign_up_fee` | filter | Free | Filters the sign-up fee wording used in plan terms. Return: string — the wording. | `$text` (string) Wording |
| `ppcart_plan_text_with_a` | filter | Free | Filters the ‘with a’ text used in plan terms in front-end script messages. Return: string — the text. | `$text` (string) Translated text; default ‘with a’ |
| `ppcart_show_{$pay_method}_payment_method` | action | Free | Fires on the My Account subscription page so the gateway can show its payment method update form. Note: {$pay_method} is the subscription’s pay_method value, such as ‘stripe’. Fires only when the subscription status allows updates. | `$subscription_order` (object) Subscription order data |
| `ppcart_stripe_subscription_invoice_args` | filter | Free | Filters the arguments for the Stripe invoice that the plugin creates with a subscription. Return: array — the Stripe invoice arguments. | `$args` (array) Stripe invoice arguments  
`$context` (array) Keys ‘order’, ‘sub’, ‘addon’, ‘tax_rates’, and ‘currency’ |
| `ppcart_sub_details` | action | Free | Fires inside the subscription details section of the subscription info meta box so you can add extra detail rows. | `$cart_subscription` (object) Current subscription object |
| `ppcart_subscription_cancel_credit_note_args` | filter | Free | Filters the Stripe credit note arguments used when a subscription cancel refunds the last invoice. Return: array — the credit note arguments. | `$credit_note_args` (array) Stripe credit note create arguments  
`$sub` (PPCart_Subscription) Subscription object  
`$invoice` (object) Stripe invoice  
`$stripe_sub` (object) Stripe subscription |
| `ppcart_subscription_cancel_refund_amount` | filter | Free | Filters the refund amount, in cents, for a subscription cancel. The plugin caps it at the maximum refundable amount. Return: int — the refund amount in cents. | `$max_refund_amount` (int) Maximum refund amount in cents  
`$sub` (PPCart_Subscription) Subscription object  
`$invoice` (object) Stripe invoice  
`$stripe_sub` (object) Stripe subscription |
| `ppcart_subscription_created` | action | Free | Fires after a new subscription post is created and its meta is saved. | `$sub` (PPCart_Subscription) Subscription that was created |
| `ppcart_subscription_details_link` | filter | Free | Filters the payment details link shown in the subscription info meta box. Return: string – the link URL. | `$details_link` (string) Default link to the payment details  
`$cart_subscription` (object) Current subscription object |
| `ppcart_subscription_pause_restart` | filter | Free | Filters the result of pausing or restarting a subscription that does not use Stripe. Gateways handle it here. Return: mixed — the response; a falsy value means failure. | `$response` (mixed) Response value, such as the period end date  
`$sub` (PPCart_Subscription) Subscription object  
`$type` (string) Action type, for example started |
| `ppcart_subscription_related_orders` | action | Free | Fires after the line items on the subscription edit screen so you can output related order details. | `$order_obj` (object) Subscription order object |
| `ppcart_subscription_store_address_fields` | action | Free | Fires while a subscription is saved, so code can store address fields on the subscription post. | `$post_id` (int) Subscription post ID  
`$order` (array) Order data used to build the subscription |
| `ppcart_subscription_transaction_id` | filter | Free | Filters the subscription ID the plugin finds for an order, before it saves it to order meta. Return: string\|false — the subscription ID. | `$txnid` (string\|false) Subscription ID  
`$method` (string) Payment method  
`$order_id` (int) Order ID |
| `ppcart_subscription_updated` | action | Free | Fires after an existing subscription post is updated. | `$sub` (PPCart_Subscription) Subscription that was updated |
| `ppcart_update_stripe_invoice_during_checkout` | filter | Free | Filters whether the plugin updates the Stripe invoice and runs integrations during checkout. Return: bool — true to update during checkout. | `$update_invoice` (bool) False when the ‘webhook updates only’ setting is on  
`$ppcart_order` (PPCart_Order) Order object  
`$sub` (PPCart_Subscription) Subscription object |
| `ppcart_valid_sub_statuses_for_cancel` | filter | Free | Filters the subscription statuses for which the customer can cancel. Return: array — the allowed status slugs. | `$statuses` (array) Statuses; default [‘trialing’,’active’,’paused’]  
`$subscription_order` (object) Subscription order data |
| `ppcart_valid_sub_statuses_for_pause_restart` | filter | Free | Filters the subscription statuses for which the customer can pause or resume. Return: array — the allowed status slugs. | `$statuses` (array) Statuses; default [‘trialing’,’active’,’paused’]  
`$subscription_order` (object) Subscription order data |
| `ppcart_valid_sub_statuses_for_update` | filter | Free | Filters the subscription statuses for which the customer can update the payment method. Return: array — the allowed status slugs. | `$statuses` (array) Statuses; default [‘trialing’,’active’,’paused’,’past_due’,’unpaid’,’incomplete’]  
`$subscription_order` (object) Subscription order data |

## Payment gateways [#](#10-toc-title)

| Hook | Type | Version | Description | Parameters |
| --- | --- | --- | --- | --- |
| `ppcart_before_create_stripe_payment_intent` | action | Free | Fires before the plugin creates a Stripe payment intent for a checkout. | — |
| `ppcart_checkout_payment_method_enabled` | filter | Free | Filters whether a payment method (Stripe or cash on delivery) shows in the checkout payment method list. Return: bool — true to show the method, false to hide it. | `$enabled` (bool) Whether the method is enabled; defaults to true  
`$method` (string) Payment method ID: ‘stripe’ or ‘cashondelivery’  
`$post_id` (int) Product ID being checked out |
| `ppcart_create_stripe_intent` | filter | Free | Filters whether the plugin creates a Stripe payment intent for this order. Return: bool — true to create a payment intent. | `$create_intent` (bool) True for free, one-time, or pay-what-you-want plans without a bump plan  
`$ppcart_order` (PPCart_Order) Order object |
| `ppcart_currency_countries_code` | filter | Free | Filters the list of country codes used for Stripe express checkout support. Return: array — the country codes. | `$country_codes` (array) Country codes |
| `ppcart_enabled_payment_gateways` | filter | Free | Filters the map of payment methods enabled on the site. Return: array — the enabled payment methods. | `$payment_methods` (array) Map of payment method slug to label |
| `ppcart_enabled_processors` | filter | Free | Filters the list of enabled payment processors. Return: string[] — the enabled processors. | `$processors` (string[]) Processor names, such as Stripe or PayPal |
| `ppcart_express_payment_method_fields` | action | Free | Fires where the checkout shows express payment buttons (such as wallet buttons), before the submit button. | `$post_id` (int) Product ID being checked out |
| `ppcart_gateway_webhook` | action | Free | Fires for a gateway webhook request when the plugin has no built-in handler file for that gateway. | — |
| `ppcart_order_refund_{$pay_method}` | action | Free | Fires to process a refund for an order paid with a payment method that is not free, COD or Stripe. Note: the suffix is the order’s pay_method. The handler can throw an exception to return an error message. | `$data` (array) Refund data, including id and refund_amount  
`$order` (PPCart_Order) Order to refund |
| `ppcart_payment_field_option_list` | filter | Free | Filters the field definitions of the payment settings (Stripe and PayPal). Return: array – the field definitions. | `$payment_fields` (array) Field definitions |
| `ppcart_payment_gateway_tab_section` | filter | Free | Filters the gateway sections (Stripe, PayPal) on the Payment settings tab. Return: array – the gateway sections. | `$payment_gateways` (array) Map of gateway key to title |
| `ppcart_payment_method_fields` | action | Free | Fires inside the payment method section of the checkout form so gateways can print their own fields. | `$post_id` (int) Product ID being checked out |
| `ppcart_payment_methods` | filter | Free | Filters the list of payment methods that appear on the checkout form. Return: array — the payment methods to display. | `$payment_methods` (array) Enabled payment methods, keyed by method ID  
`$post_id` (int) Product ID being checked out |
| `ppcart_paypal_checkout_url` | filter | Free | Filters the PayPal checkout URL that the plugin returns to the browser. Return: string — the PayPal checkout URL. | `$paypalUrl` (string) PayPal checkout URL  
`$ppcart_order` (PPCart_Order) Order object  
`$sub` (PPCart_Subscription\|false) Subscription object, if any |
| `ppcart_paypal_class` | filter | Free | Filters the class name that the plugin uses for the PayPal handler. Return: string – the class name (an invalid or missing class falls back to PPCart_Paypal). | `$class` (string) Class name, PPCart_Paypal by default |
| `ppcart_paypal_custom_payment_vars` | filter | Free | Filters the custom data sent to PayPal in the ‘custom’ field of a payment request. Return: array — the custom data. Note: Subscription payments pass more data than one-time payments. | `$custom` (array) Custom data; ‘order_id’, plus ‘subscription_id’ for subscriptions  
`$order_data` (array) Order data  
`$sub_data` (array\|false) Subscription data, or false for one-time payments |
| `ppcart_paypal_onpage_reconcile` | action | Free | WP-Cron hook that fires hourly while on-page PayPal checkout is active. The plugin reconciles pending on-page orders of the last 48 hours and PayPal subscriptions past their next bill date with what PayPal reports. | — |
| `ppcart_paypal_payment_vars` | filter | Free | Filters the PayPal payment variables before the plugin builds the PayPal URL query string. Return: array — the PayPal payment variables. | `$data` (array) PayPal payment variables  
`$order` (PPCart_Order) Order object  
`$sub` (PPCart_Subscription\|false) Subscription object, if any |
| `ppcart_paypal_recurring_payment_data` | action | Free | Fires when the PayPal webhook receives a subscription event, before PPCart handles the subscription. | `$request_data` (array) Raw request data from the webhook  
`$data` (array) Parsed webhook data, including txn_type and subscription_id |
| `ppcart_preloaded_intent_cleanup_force_archive_attempts` | filter | Free | Filters how many cleanup attempts the plugin makes before it force-archives a preloaded intent order. Return: int — the number of attempts; the minimum is 1. | `$attempts` (int) Maximum attempts, default 3 |
| `ppcart_preloaded_intent_ttl` | filter | Free | Filters how long a preloaded Stripe intent order lives, in seconds, before cleanup. Return: int — lifetime in seconds; the plugin enforces a 15 minute minimum. | `$ttl` (int) Lifetime in seconds, default HOUR_IN_SECONDS |
| `ppcart_pro_webhook_rate_limit_count` | filter | Pro | Filters the number of incoming webhook requests allowed per gateway in each rate limit window. Return: int — the maximum requests per window; a value below 1 disables limiting. | `$limit` (int) Default is the limiter’s DEFAULT_LIMIT constant  
`$gateway` (string) Gateway slug |
| `ppcart_pro_webhook_rate_limit_window` | filter | Pro | Filters the length in seconds of the webhook rate limit window for a gateway. Return: int — the window length in seconds; a value below 1 disables limiting. | `$window` (int) Default is the limiter’s DEFAULT_WINDOW constant  
`$gateway` (string) Gateway slug |
| `ppcart_process_stripe_products` | filter | Free | Filters whether the plugin creates or updates Stripe products and prices when a product is saved. Return: bool – false to skip the Stripe sync. | `$process` (bool) True by default |
| `ppcart_product_paypal_enabled` | filter | Free | Filters whether PayPal appears as a payment method for a product. Return: bool — false to hide PayPal for the product. | `$enabled` (bool) True by default when PayPal is configured  
`$post_id` (int) Product post ID |
| `ppcart_product_save_stripe_meta` | action | Free | Fires after the plugin saves the Stripe product and price IDs to the product meta. | `$post_id` (int) Product post ID  
`$objects` (array) Stripe objects saved for the product  
`$stripe` (object) Stripe client  
`$ppcart_currency` (string) Currency code |
| `ppcart_register_gateways` | action | Free | Fires after the core gateway sections are registered so you can add your own gateway settings section. | `$settings` (object) Admin settings object  
`$page` (string) Settings page slug for the payment tab |
| `ppcart_stripe_checkout_session_args` | filter | Free | Filters the arguments the plugin sends to Stripe to create a Hosted Checkout session. Return: array — the Stripe session arguments. | `$args` (array) Stripe session create arguments  
`$ppcart_order` (PPCart_Order) Order object |
| `ppcart_stripe_checkout_session_completed` | action | Free | Fires after the plugin syncs a completed Stripe Checkout session into an order. | `$order_data` (array) Order data from the cart order object  
`$session` (object) Stripe Checkout session object |
| `ppcart_stripe_connect_credentials_http_args` | filter | Free | Filters the HTTP request arguments that the plugin sends to the Stripe Connect server to get credentials. Return: array – the request arguments. | `$credentials_request_args` (array) Arguments for wp_remote_post, including the body  
`$connect_server_url` (string) Connect server URL  
`$stripe_mode` (string) Stripe mode, test or live |
| `ppcart_stripe_invoice_response` | action | Free | Fires after the plugin syncs a Stripe invoice into an order. | `$order_data` (array) Order data from the order object |
| `ppcart_stripe_subscriptions_documentation_url` | filter | Free | Filters the URL of the Stripe subscriptions documentation link in the payment settings. Return: string – the URL. | `$url` (string) Default documentation URL |
| `ppcart_webhook_url_type` | filter | Free | Filters the webhook URL format. Return ‘plain’ to use the ?ppcart-api= query URL instead of the pretty URL. Return: string — ‘plain’ or an empty string. | `$webhook_url_type` (string) URL type; default is empty string |
| `ppcartsection-title-{$payment_gateway_key}` | filter | Free | Filters the title of a gateway section on the Payment settings tab, where the gateway key is part of the hook name. Return: string – the title. Note: Legacy hook name without an underscore separator. It stays for backward compatibility. | `$title` (string) Section title |

## Prices, currency, and tax [#](#11-toc-title)

| Hook | Type | Version | Description | Parameters |
| --- | --- | --- | --- | --- |
| `ppcart_charge_amount` | filter | Free | Filters the amount the order will charge, after items and tax are calculated from the posted form. Return: float — the amount to charge. | `$amount` (float) Order charge amount  
`$ppcart_product` (object) Product being purchased |
| `ppcart_currencies` | filter | Free | Filters the list of currencies the plugin supports. Return: array — the currency code to name map. | `$currencies` (array) Map of currency code to currency name |
| `ppcart_currency_symbols` | filter | Free | Filters the map of currency codes to currency symbols. Return: array — the currency code to symbol map. | `$symbols` (array) Map of currency code to symbol (HTML entities) |
| `ppcart_enqueue_admin_tax_settings` | action | Free | Fires after the admin tax settings scripts are enqueued so you can enqueue related assets. | `$plugin_name` (string) Plugin name  
`$version` (string) Plugin version |
| `ppcart_enqueue_public_tax_settings` | action | Free | Fires while the plugin enqueues public scripts, so tax add-ons can load their frontend tax settings. | `$plugin_name` (string) Plugin name slug |
| `ppcart_format_price` | filter | Free | Filters the formatted price string. Return: string — the formatted price. | `$price` (string) Formatted price  
`$formatted_amount` (string) Formatted number without symbol  
`$currency_symbol` (string) Currency symbol  
`$html` (bool) Whether the output is HTML |
| `ppcart_invoice_option_list` | filter | Free | Filters the field definitions of the Invoice settings tab. Return: array – the field definitions. | `$invoice_fields` (array) Field definitions |
| `ppcart_invoice_tab_section` | filter | Free | Filters the sections on the Invoice settings tab. Return: array – the sections. | `$invoices` (array) Map of section key to title |
| `ppcart_order_apply_tax_to_item` | filter | Free | Filters an order item after the order tries to apply tax to it. Return: array — the order item. Note: By default, the item is returned unchanged. | `$item` (array) Order item  
`$order` (PPCart_Order) Order object |
| `ppcart_order_calculate_tax` | filter | Free | Filters the tax result for an amount, as an array with tax and total values. Return: array — keys ‘tax’ and ‘total’. Note: The default is zero tax. | `$result` (array) Default [‘tax’ => 0, ‘total’ => $amount]  
`$_amount` (float) Amount to calculate tax on  
`$product_id` (int) Product ID  
`$order` (PPCart_Order) Order object |
| `ppcart_order_pre_calculate_tax` | action | Free | Fires just before the order calculates tax and its pre-tax amount from the items. | `$order` (PPCart_Order) Order being built |
| `ppcart_order_setup_tax` | action | Free | Fires when the order sets up tax data for a product. | `$order` (PPCart_Order) Order object  
`$ppcart_product` (object) Product being purchased |
| `ppcart_product_price` | filter | Free | Filters the lowest price shown for a product that has several prices. Return: float\|string — the price. | `$price` (float\|string) Minimum price of the product prices |
| `_ppcart_tax_rate_added` | action | Pro | Fires after a new tax rate is inserted into the tax rate table. | `$tax_rate_id` (int) ID of the new tax rate  
`$tax_rate` (array) Tax rate data |
| `_ppcart_tax_rate_deleted` | action | Pro | Fires after a tax rate row is deleted from the tax rate table. | `$tax_rate_id` (int) ID of the deleted tax rate |
| `_ppcart_tax_rate_updated` | action | Pro | Fires after a tax rate in the tax rate table is updated. | `$tax_rate_id` (int) ID of the updated tax rate  
`$tax_rate` (array) Updated tax rate data |
| `ppcart_tax_rates` | filter | Free | Filters the saved tax rates option data. Return: array — the tax rate data. | `$tax_rate` (array) Tax rate data from the _ppcart_tax_rates option |
| `ppcart_taxes_tab_section` | filter | Free | Filters the sections on the Tax settings tab. Return: array – the sections. | `$taxes` (array) Map of section key to title |
| `ppcart_vat_title` | filter | Free | Filters the ‘VAT Number’ label shown on PDF invoices and order emails. Return: string — the label text. | `$title` (string) Label text; default is the translated ‘VAT Number’ |
| `ppcart_zero_decimal_currency` | filter | Free | Filters the list of zero-decimal currency codes, which have no minor unit. Return: string[] — the zero-decimal currency codes. | `$currencies` (string[]) Zero-decimal currency codes |
| `ppcartsection-title-currency` | filter | Free | Filters the title of the Currency and Pricing section on the settings page. Return: string – the title. | `$title` (string) Section title |
| `ppcartsection-title-{$invoice_key}` | filter | Free | Filters the title of a section on the Invoice settings tab, where the section key is part of the hook name. Return: string – the title. Note: Legacy hook name without an underscore separator. It stays for backward compatibility. | `$title` (string) Section title |
| `ppcartsection-title-{$tax_key}` | filter | Free | Filters the title of a section on the Tax settings tab, where the section key is part of the hook name. Return: string – the title. Note: Legacy hook name without an underscore separator. It stays for backward compatibility. | `$title` (string) Section title |

## Coupons [#](#12-toc-title)

| Hook | Type | Version | Description | Parameters |
| --- | --- | --- | --- | --- |
| `ppcart_checkout_coupon` | filter | Free | Filters the coupon value used to output the checkout form scripts. Return: mixed — coupon data, or false to skip the coupon scripts. | `$coupon` (mixed) Coupon data, or false when none  
`$prod_id` (int) Product ID |
| `ppcart_coupon_fields` | action | Free | Fires where the checkout form shows the coupon input field. | `$post_id` (int) Product ID being checked out |
| `ppcart_coupon_status` | action | Free | Fires where the checkout form shows the applied-coupon status message. | `$post_id` (int) Product ID being checked out |
| `ppcart_get_coupon_code` | filter | Pro | Filters the coupon code before the plugin looks it up in a product’s coupons. Return: string — the coupon code to look up. | `$coupon_code` (string) Coupon code entered  
`$product_id` (int) Product ID  
`$coupons` (array\|mixed) Coupons saved on the product  
`$user_info` (array) Customer information |
| `ppcart_order_coupon_limit_update` | action | Pro | Fires when an order uses a coupon so the coupon’s usage limit can change. | `$coupon` (array) Coupon data used on the order  
`$product_id` (int) Product ID  
`$operation` (string) Always ‘decrease’ at current sites  
`$amount` (int) Always 1 at current sites  
`$order_data` (array) Order data |
| `ppcart_order_summary_coupon_text` | filter | Free | Filters the label for the coupon line in the order summary. Return: string — the coupon line label. | `$text` (string) Label, for example ‘Coupon CODE’  
`$coupon` (array) Coupon data |
| `ppcart_validate_coupon` | filter | Pro | Filters the result of coupon validation, which holds the coupon or an error. Return: array — the validation result. | `$ret` (array) Validation result, with the coupon data or an ‘error’ message  
`$coupon_code` (string) Coupon code  
`$product_id` (int) Product ID  
`$user_info` (array) Customer information |

## Upsells, downsells, and order bumps [#](#13-toc-title)

| Hook | Type | Version | Description | Parameters |
| --- | --- | --- | --- | --- |
| `ppcart_before_load_downsell` | action | Pro | Fires before the downsell page scripts load their data for the browser. | `$ppcart` (array) Script data array passed to the page  
`$ppcart_order_post` (mixed) Order post data from the request context |
| `ppcart_before_load_upsell` | action | Pro | Fires before the upsell page scripts load their data for the browser. | `$ppcart` (array) Script data array passed to the page  
`$ppcart_order_post` (mixed) Order post data from the request context |
| `ppcart_do_orderbump` | action | Pro | Fires to render one order bump on the checkout form. | `$args` (array) Order bump display arguments, already filtered by ppcart_orderbump_args |
| `ppcart_downsell_accept_url` | filter | Pro | Filters the URL a customer goes to after accepting a downsell offer. Return: string — the URL. | `$accept_url` (string) Accept redirect URL  
`$order_id` (int) Order ID  
`$ppcart_product` (object) Offer product |
| `ppcart_downsell_decline_url` | filter | Pro | Filters the URL a customer goes to after declining a downsell offer. Return: string — the URL. | `$decline_url` (string) Decline redirect URL  
`$order_id` (int) Order ID  
`$ppcart_product` (object) Offer product |
| `ppcart_downsell_urls` | filter | Pro | Filters the accept and decline URLs of a downsell offer as one pair. Return: array — [accept URL, decline URL]. | `$downsell_urls` (array) Two-item array: [accept URL, decline URL]; both default to the thank you URL  
`$order_id` (int) Order ID  
`$ppcart_product` (object) Offer product |
| `ppcart_orderbump_args` | filter | Pro | Filters the display arguments for an order bump before it renders. Return: array — the order bump arguments. | `$args` (array) Order bump arguments, such as class and inline attributes |
| `ppcart_post_purchase_url` | filter | Pro | Filters a redirect URL that is used after a purchase or an upsell or downsell decision. Return: string — the URL. | `$url` (string) URL: thank you URL, upsell accept URL or upsell decline URL  
`$order_id` (int) Order ID  
`$product_id` (int) Product ID |
| `ppcart_select_page_post_types` | filter | Pro | Filters the post types listed in the page selector of upsell offer settings. Return: array — post type slugs. | `$types` (array) Post type slugs; default [‘page’,’e-landing-page’] |
| `ppcart_setup_product_upsell_path` | filter | Free | Filters the product data after the plugin sets the upsell path defaults. Return: array — the product data. | `$arr` (array) Product data  
`$id` (int) Product ID |
| `ppcart_show_orderbump` | filter | Free | Filters whether the checkout shows order bump options for the product. Return: bool — true to show order bumps. | `$show_bump` (bool) True when the product has order bump options  
`$product_id` (int) Product ID |
| `ppcart_show_orderbumps` | filter | Pro | Filters whether the checkout shows the list of order bumps. Return: bool — true to show the order bumps. | `$show_bump` (bool) Whether the product has order bump options  
`$post_id` (int) Product ID |
| `ppcart_upsell_accept_url` | filter | Pro | Filters the URL a customer goes to after accepting an upsell offer. Return: string — the URL. | `$accept_url` (string) Accept redirect URL  
`$order_id` (int) Order ID  
`$ppcart_product` (object) Offer product |
| `ppcart_upsell_decline_url` | filter | Pro | Filters the URL a customer goes to after declining an upsell offer. Return: string — the URL. | `$decline_url` (string) Decline redirect URL  
`$order_id` (int) Order ID  
`$ppcart_product` (object) Offer product |
| `ppcart_upsell_flow_setting_tabs` | filter | Pro | Filters the settings tabs shown in the upsell and downsell offer metabox. Return: array — the tab definitions. | `$tabs` (array) Tab definitions |
| `ppcart_upsell_path` | filter | Pro | Filters the upsell path array built for a product during upsell setup. Return: array — the upsell path data. | `$arr` (array) Upsell path data |
| `ppcart_upsell_setting_tab_{$id}_{$i}_fields` | filter | Pro | Filters the metabox field definitions of one upsell or downsell settings tab. Return: array — the field definitions. Note: {$id} is the tab ID and {$i} is the offer index. | `$fields` (array) Field definitions for the tab |
| `ppcart_upsell_urls` | filter | Pro | Filters the accept and decline URLs of an upsell offer as one pair. Return: array — [accept URL, decline URL]. | `$upsell_urls` (array) Two-item array: [accept URL, decline URL]  
`$order_id` (int) Order ID  
`$ppcart_product` (object) Offer product |

## Products [#](#14-toc-title)

| Hook | Type | Version | Description | Parameters |
| --- | --- | --- | --- | --- |
| `ppcart-metabox-title-product-settings` | filter | Free | Filters the title of the main Cart meta box on the product edit screen. Return: string – the meta box title. | `$title` (string) Default meta box title |
| `ppcart_after_product_setup` | action | Free | Fires after the plugin sets up the global product object for a product page. | `$ppcart_product` (object) Product context object |
| `ppcart_after_update_stock` | action | Free | Fires after the plugin updates the stock limit of a product. | `$product_id` (int) Product ID |
| `ppcart_after_validate_meta` | action | Free | Fires after the product meta fields are validated and saved, and after the Stripe objects are saved. | `$post_id` (int) Product post ID  
`$stripe_objects` (array) Stripe objects created or updated for the product |
| `ppcart_before_show_download` | action | Free | Fires before the plugin logs and serves a file download. | `$download` (object) Download record, including file path and order |
| `ppcart_before_validate_meta` | action | Free | Fires before the product meta fields are validated and saved. | `$post_id` (int) Product post ID |
| `ppcart_confirmation_fields` | filter | Free | Filters the confirmation (thank you page) field definitions in the product settings meta box. Return: array – the field definitions. | `$confirmation` (array) Field definitions  
`$save` (bool) Whether the fields are built for saving |
| `ppcart_default_field_settings_attributes` | filter | Free | Filters the row attributes of every default checkout field in the default fields repeater. Return: array – the row attributes. | `$setatts` (array) Row attributes, such as the CSS class |
| `ppcart_default_fields` | filter | Free | Filters the default checkout fields data that the product plan options use for the current product. Return: array – the default fields data. | `$default_fields` (array) Array with the default_fields post meta of the current product |
| `ppcart_default_fields_ids` | filter | Free | Filters the list of meta field names that are saved as default fields repeaters. Return: array – the meta keys. | `$default_fields_ids` (array) Meta keys treated as default fields |
| `ppcart_default_{$field_name}_field_settings_attributes` | filter | Free | Filters the row attributes of one default checkout field in the default fields repeater, where the field name is part of the hook name. Return: array – the row attributes. | `$setatts` (array) Row attributes, such as the CSS class |
| `ppcart_download` | filter | Free | Filters the download record after the plugin builds it, before the download URL is checked. Return: object — the download record. | `$download` (object) Download record with name, path, URL and limits |
| `ppcart_download_allowed_redirect_hosts` | filter | Free | Filters the hosts a file download may redirect to when the file is an external URL. Return: string[] — the allowed host names. | `$allowed_hosts` (string[]) Allowed host names; default is the site host |
| `ppcart_download_allowed_roots` | filter | Free | Filters the server directories from which the plugin may serve local file downloads. Return: string[] — the allowed root directories. | `$roots` (string[]) Allowed absolute directory paths |
| `ppcart_download_slug` | filter | Free | Filters the URL slug used for file download links. Return: string — the download slug. | `$slug` (string) Download slug; default is the class default |
| `ppcart_low_stock_threshold` | filter | Free | Filters the stock level at or below which the plugin sends a low stock email. Return: int — the threshold. | `$threshold` (int) Low stock threshold; default 5  
`$product_id` (int) Product ID |
| `ppcart_page_metabox` | action | Free | Fires at the top of the Cart meta box on pages and posts so you can output extra content. | `$post` (WP_Post) Current post |
| `ppcart_pricing_fields` | filter | Free | Filters the Pricing tab field definitions in the product settings meta box. Return: array – the field definitions. | `$pricing` (array) Field definitions  
`$save` (bool) Whether the fields are built for saving |
| `ppcart_product` | filter | Free | Filters the product data array after the plugin sets it up from product meta. Return: array — the product data. | `$arr` (array) Product data |
| `ppcart_product_duplicate_excluded_meta_keys` | filter | Free | Filters the list of meta keys that product duplication does not copy to the new product. Return: array — the meta keys to exclude. | `$excluded_meta` (array) Meta keys to skip when duplicating |
| `ppcart_product_duplicate_meta_key` | filter | Free | Filters the meta key written to the duplicate product for each copied meta entry. Return: string — the meta key to write on the duplicate. | `$meta_key` (string) Meta key being copied  
`$all_meta` (array) All meta of the source product  
`$source_id` (int) Source product ID  
`$duplicate_id` (int) New product ID |
| `ppcart_product_field_groups` | filter | Free | Filters the ordered list of product setting field groups used for rendering or saving. Return: array – the group slugs. | `$groups` (array) Group slugs  
`$context` (string) Use context, ‘render’ or ‘save’ |
| `ppcart_product_field_scripts` | filter | Free | Filters the inline JavaScript that the product settings meta box prints for its fields. Return: string – the JavaScript. | `$scripts` (string) JavaScript for the product fields  
`$current_post_id` (int) ID of the product being edited |
| `ppcart_product_general_fields` | filter | Free | Filters the General tab field definitions in the product settings meta box. Return: array – the field definitions. | `$general` (array) Field definitions  
`$post_id` (int) Product post ID |
| `ppcart_product_metabox_post_type` | filter | Free | Filters the list of post types that show the product meta box. Return: array – the post types. | `$post_types` (array\|string) Product post type or post types |
| `ppcart_product_payments_fields` | filter | Free | Filters the Payments tab field definitions in the product settings meta box. The default list is empty. Return: array – the field definitions. | `$payments` (array) Field definitions, empty by default  
`$post_id` (int) Product post ID |
| `ppcart_product_post_type` | filter | Free | Filters the product post type or types the plugin queries and recognizes. Return: string\|string[] — the product post types. | `$post_types` (string\|string[]) Product post type; default is the live product post type |
| `ppcart_product_print_field_scripts` | action | Free | Fires after the product settings meta box prints its field scripts. | `$current_post_id` (int) ID of the product being edited |
| `ppcart_product_setting_tab_fields` | filter | Free | Filters the fields of any product settings tab, with the tab ID passed as an argument. Return: array – the field definitions. | `$fields` (array) Field definitions of the tab  
`$tab_id` (string) Product settings tab ID  
`$context` (string) Use context, ‘render’ or ‘save’ |
| `ppcart_product_setting_tab_{$tab_id}_fields` | filter | Free | Filters the fields of one product settings tab, where the tab ID is part of the hook name. Return: array – the field definitions. Note: Fires for every tab ID, for example general, access, payments, pricing, fields, confirmation, notifications, integrations. | `$fields` (array) Field definitions of the tab |
| `ppcart_product_setting_tabs` | filter | Free | Filters the list of tabs in the product settings meta box. Return: array – the tabs (an empty or non-array value resets to the default). | `$default_product_setting_tabs` (array) Map of tab ID to tab label |
| `ppcart_product_tab_sections` | filter | Free | Filters the sections inside each tab of the product settings meta box. Return: array – the sections by tab. | `$default_sections` (array) Map of tab ID to list of sections |
| `ppcart_product_{$tab_id}_fields` | filter | Free | Filters the fields of one product settings tab, where the tab ID is part of the hook name. Return: array – the field definitions. Note: Shorter alias of ppcart_product_setting_tab_{$tab_id}_fields. Both fire for every tab. | `$fields` (array) Field definitions of the tab |
| `ppcart_register_importers` | action | Free | Fires when WordPress loads importers so you can register your own importer. | — |
| `ppcart_setup_product_display_mode` | filter | Free | Filters the product data after the plugin sets the checkout display mode. Return: array — the product data. | `$arr` (array) Product data  
`$id` (int) Product ID |
| `ppcart_setup_product_post_type` | filter | Free | Filters the post types the plugin accepts when it sets up a product. Return: string\|string[] — the product post types. | `$post_types` (string\|string[]) Product post type; default is the live product post type |

## Customer account [#](#15-toc-title)

| Hook | Type | Version | Description | Parameters |
| --- | --- | --- | --- | --- |
| `ppcart_account_before_subscription_details` | action | Free | Fires on the My Account subscription page, where upgrade and downgrade plan options appear. | `$subscription_order` (object) Subscription order data |
| `ppcart_account_subscription_action_links` | action | Free | Fires after the pause, resume and cancel links on the My Account subscription page so you can add links. | `$subscription_order` (object) Subscription order data |
| `ppcart_account_tabs` | filter | Free | Filters the tabs shown in the customer account page. Return: array — the account tabs. | `$tabs` (array) Tab definitions |
| `ppcart_after_user_is_created` | action | Free | Fires after the plugin creates a WordPress user for an order. | `$user_id` (int) New user ID  
`$order_id` (int) Order ID |
| `ppcart_allow_privileged_customer_role` | filter | Free | Filters whether the plugin may assign a privileged role to a customer. By default it blocks them. Return: bool — true to allow the privileged role. | `$allow` (bool) Whether to allow the role; default false  
`$user_role` (string) Role requested  
`$order_id` (int) Order ID |
| `ppcart_current_user_orders_meta_query_args` | filter | Free | Filters the meta query used to find the current user’s orders for a product. Return: array — the meta query. | `$meta_query` (array) WP_Query meta query  
`$product_id` (int) Product ID |
| `ppcart_download_tab_name` | filter | Free | Filters the title of the Downloads tab in the customer account. Return: string — the tab title. | `$title` (string) Tab title; default is Downloads |
| `ppcart_login_after_{$template_name}` | action | Free | Fires after the plugin loads a login or account template, named by the template. Note: $template_name is the name of the template file that was loaded. | — |
| `ppcart_login_before_{$template_name}` | action | Free | Fires before the plugin loads a login or account template, named by the template. Note: $template_name is the name of the template file about to load. | — |
| `ppcart_my_account_show_order_detail_link` | filter | Free | Filters whether the My Account order history table shows a link to each order’s detail page. Return: bool — true to show the order detail link. | `$show_link` (bool) Defaults to false |
| `ppcart_my_account_subscription_detail_after_details` | action | Free | Fires after the details table on the My Account subscription page. | `$subscription_order` (object) Subscription order data  
`$sub` (object) Subscription model object |
| `ppcart_my_account_tab_content` | filter | Free | Filters the HTML of the current tab on the My Account page. Return: string — the tab HTML; an empty value falls back to the default output. | `$content` (string) Tab HTML |
| `ppcart_privileged_role_capabilities` | filter | Free | Filters the capabilities that make a role privileged, so the plugin does not assign it to customers. Return: string[] — the capability names. | `$caps` (string[]) Capability names |
| `ppcart_send_new_user_email` | filter | Free | Filters whether the plugin sends the new-user email after it creates a user for an order. Return: bool — true to send the email. | `$send_email` (bool) Whether to send the email  
`$order_id` (int) Order ID |
| `ppcart_sub_item_id` | filter | Free | Filters the subscription item ID used by the ‘Update Card’ link in My Account. Return: mixed — the item ID to put in the link’s data-item-id attribute. | `$option_id` (mixed) Item ID from $args->option_id  
`$args` (object) Subscription arguments passed to the card details template |
| `ppcart_subscription_detail_modals` | action | Free | Fires after the built-in modals on the My Account subscription page so you can print extra modals. | `$sub` (object) Subscription model object |
| `ppcart_tab_content_{$account_tab['id']}` | action | Free | Fires inside a My Account tab panel so you can print extra content for that tab. Note: the suffix is the ID of each registered account tab. | — |
| `ppcart_use_default_authentication_logic` | filter | Free | Filters whether the account page uses the plugin’s default login logic. Return: bool — false to skip the default logic. | `$use_default` (bool) True unless the request comes from the My Account page |
| `ppcartsection-title-pages` | filter | Free | Filters the title of the Pages and Customer Account section on the settings page. Return: string – the title. | `$title` (string) Section title |

## Emails and notifications [#](#16-toc-title)

| Hook | Type | Version | Description | Parameters |
| --- | --- | --- | --- | --- |
| `ppcart_admin_notification_email` | filter | Free | Filters the admin email address that receives new-order and registration notices. Return: string — the recipient email address. | `$admin_email` (string) Admin email address  
`$order_info` (array) Order data |
| `ppcart_before_email_footer` | action | Free | Fires before the footer in the main email template. | `$type` (string) Email type  
`$order_info` (array) Order data for the email |
| `ppcart_email_after_order_table` | action | Free | Fires in the email template where the order table section ends. | `$email_order` (PPCart_Order) Order shown in the email |
| `ppcart_email_template_customer_info` | filter | Free | Filters the customer info HTML in the order table of an email. Return: string — the customer info HTML; the plugin passes it through wp_kses_post. | `$customer_info` (string) Customer info HTML  
`$email_type` (string) Email type  
`$order_id` (int) Order ID |
| `ppcart_email_template_option_names` | filter | Free | Filters the list of option names that store email template content. Return: array — the option names. | `$options` (array) Email template option names |
| `ppcart_email_templates` | filter | Free | Filters the registered email template definitions. Return: array — the email templates. | `$templates` (array) Email template definitions |
| `ppcart_emails_tab_section` | filter | Free | Filters the list of email template sections on the Emails settings tab. Return: array – the email sections. | `$emails` (array) Map of email section key to title |
| `ppcart_notification_email_to` | filter | Free | Filters the recipient of a customer notification email. Return: string — the recipient email address. | `$to` (string) Customer email address  
`$type` (string) Notification type  
`$order_info` (array) Order data |
| `ppcart_order_download_shortcode_tags` | filter | Free | Filters the merge tags in a notification that the plugin replaces with the order download links. Return: string[] — the download tags. | `$download_tags` (string[]) Tags; default is [ppcart_order_downloads] |
| `ppcart_personalize_replacements` | filter | Free | Filters the map of merge tags to values when the plugin personalizes text with order data. Return: array — the merge tag replacements. | `$replacements` (array) Merge tag to replacement map  
`$order_info` (array) Order data |
| `ppcart_should_send_product_notification` | filter | Free | Filters whether the plugin sends one configured product notification for an order. Return: bool — false to skip this notification. | `$should_send` (bool) Default send decision from the notification’s enabled state  
`$notification` (array) Notification configuration  
`$order_info` (array) Order and product context  
`$notification_index` (int\|string) Notification index |

## Integrations [#](#17-toc-title)

| Hook | Type | Version | Description | Parameters |
| --- | --- | --- | --- | --- |
| `ppcart_affiliate_application_submitted` | action | Pro | Fires after a visitor submits the affiliate signup form and the application is saved. | `$data` (array) Application data, including the new affiliate ‘id’ |
| `ppcart_affiliate_commission_paid` | action | Pro | Fires after an admin marks an affiliate payout as paid. | `$payout` (array\|object) Payout record |
| `ppcart_affiliate_created` | action | Pro | Fires after a new active affiliate is created. | `$insert_data` (array) Affiliate data, including the new ‘id’ |
| `ppcart_affiliate_new_conversion` | action | Pro | Fires when an affiliate commission row records a paid order. | `$data` (array) Commission row data |
| `ppcart_affiliate_updated` | action | Pro | Fires after an affiliate’s data is updated. | `$affiliate_data` (array) New affiliate data  
`$old_data` (array) Affiliate data before the update |
| `ppcart_api_rate_limit_check` | filter | Pro | Filters whether a REST API request passes the rate limit check. Return: bool — true to allow the request, false to block it with HTTP 429. | `$allow_request` (bool) Default decision from the transient-based rate limiter  
`$remote_ip` (string) Client IP address |
| `ppcart_api_rate_limit_max` | filter | Pro | Filters the maximum number of REST API requests allowed in each rate limit window. Return: int — the maximum requests per window. | `$max_requests` (int) Default is the limiter’s DEFAULT_MAX_REQUESTS constant |
| `ppcart_api_rate_limit_window` | filter | Pro | Filters the length in seconds of the REST API rate limit window. Return: int — the window length in seconds. | `$window_seconds` (int) Default is MINUTE_IN_SECONDS (60) |
| `ppcart_create_user_integrations` | filter | Free | Filters the list of integration services for which the create user fields are shown. Return: array – the service names. | `$services` (array) Service names, default ‘create user’, ‘update user’, ‘tutor’ |
| `ppcart_integration_fields` | filter | Free | Filters the integration field definitions in the product settings meta box. Return: array – the field definitions (non-array values become an empty array). | `$integrations` (array) Field definitions  
`$save` (bool) Whether the fields are built for saving |
| `ppcart_integration_service_action_field_logic_options` | filter | Free | Filters the list of integration services for which the service action field is shown. Return: array – the service slugs. | `$services` (array) Service slugs, default activecampaign, mailchimp, mailpoet, sendfox |
| `ppcart_integration_trigger_options` | filter | Free | Filters the trigger options (such as subscription renewal failed) that an integration can run on. Return: array – the trigger options. | `$options` (array) Map of trigger slug to label |
| `ppcart_integrations` | filter | Free | Filters the list of integration services that are available in the product integration settings. Return: array – the services. | `$options` (array) Map of service slug to service label |
| `ppcart_integrations_option_list` | filter | Free | Filters the field definitions of the Integrations settings tab. Return: array – the field definitions. | `$integration_fields` (array) Field definitions |
| `ppcart_integrations_tab_section` | filter | Free | Filters the list of integration sections on the Integrations settings tab. Return: array – the integration sections. | `$integrations` (array) Map of integration key to title |
| `ppcart_mailchimp_merge_data` | filter | Free | Filters the merge field data sent to Mailchimp when the plugin subscribes a customer. Return: array — the merge fields. | `$merge_data` (array) Mailchimp merge fields  
`$order_id` (int) Order ID |
| `ppcart_register_integration_sections` | action | Free | Fires after the core integration sections are registered so you can add your own settings sections. | `$settings` (object) Admin settings object  
`$plugin_name` (string) Plugin name |
| `ppcart_renew_integrations_lists` | action | Free | Fires after the plugin refreshes the cached lists of email marketing integrations. | — |
| `ppcart_run_after_integrations` | action | Free | Fires after integrations for an order status change have run, for example emails and webhooks. | `$status` (string) Order status  
`$order_info` (array) Order data  
`$event_type` (string) Event type |
| `ppcart_show_optin_checkbox_services` | filter | Free | Filters the list of integration services that show an opt-in consent checkbox at checkout. Return: string[] — the service keys. | `$services` (string[]) Service keys; default includes activecampaign, mailchimp, mailpoet, sendfox, fluentcrm |
| `ppcart_trigger_order_integrations` | filter | Free | Filters whether the plugin runs integrations after an order is stored. Return: bool — false to skip integrations. | `$trigger_integrations` (bool) Whether to run integrations  
`$order` (PPCart_Order) Order being stored |
| `ppcart_trigger_subscription_integrations` | filter | Free | Filters whether the plugin runs integrations after a subscription is stored. Return: bool — false to skip integrations. | `$trigger_integrations` (bool) Whether to run integrations  
`$subscription` (PPCart_Subscription) Subscription being stored |
| `ppcart_wc_email_custom_field_heading` | filter | Pro | Filters the heading above custom order fields in the HTML WooCommerce email. Return: string — the heading text. | `$heading` (string) Heading text; default ‘Additional information’ |
| `ppcart_wc_email_custom_label{$meta['label']}` | filter | Pro | Filters the label of a custom order meta row in the WooCommerce email table. Return: string — the label text. Note: the meta label is appended directly to the hook name with no separator (ppcart_wc_email_custom_label + label). | `$label` (string) Custom meta label |
| `ppcart_webhook_order_data` | filter | Free | Filters the order data array sent in outgoing webhook payloads. Return: array — the webhook payload. | `$body` (array) Webhook payload |
| `ppcart_woo_email_custom_info_heading` | filter | Pro | Filters the heading above custom order fields in the plain text WooCommerce email. Return: string — the heading text. | `$heading` (string) Heading text; default ‘Additional information’ |
| `ppcart_woo_email_custom_label{$meta['label']}` | filter | Pro | Filters the label of a custom order meta line in the plain text WooCommerce email. Return: string — the label text. Note: the meta label is appended directly to the hook name with no separator. ‘woo’ and ‘wc’ prefixes differ between the HTML and plain text variants. | `$label` (string) Custom meta label |
| `ppcart_{$ppcart_services}_integrations` | action | Free | Fires for each matched integration so a third-party service handler can run its own action. Note: the suffix is the integration service name, such as a service key from the product integration settings. | `$intg` (array) Integration settings  
`$ppcart_product_id` (int) Product ID  
`$order` (PPCart_Order\|PPCart_Subscription) Order or subscription object |
| `ppcart_{$trigger}_integrations` | action | Free | Fires after all integrations for a trigger have run. The trigger is, for example, purchased, pending or lead. Note: the suffix is the integration trigger passed to ppcart_do_integrations(). | `$ppcart_product_id` (int) Product ID  
`$order` (PPCart_Order\|PPCart_Subscription) Order or subscription object |
| `ppcartsection-title-{$integration_key}` | filter | Free | Filters the title of a section on the Integrations settings tab, where the integration key is part of the hook name. Return: string – the title. Note: Legacy hook name without an underscore separator. It stays for backward compatibility. | `$title` (string) Section title |

## Templates and shortcodes [#](#18-toc-title)

| Hook | Type | Version | Description | Parameters |
| --- | --- | --- | --- | --- |
| `ppcart_after_product_list` | action | Free | Fires after the closing tag of the product list in the store archive template. | — |
| `ppcart_before_product_list` | action | Free | Fires before the opening tag of the product list in the store archive template. | — |
| `ppcart_checkout_template_path` | filter | Free | Filters the file path of the checkout template the product shortcode loads. Return: string — the template file path. | `$checkout_template` (string) Template file path  
`$template` (string) Template name  
`$context` (array) Keys ‘product_id’ and ‘atts’ |
| `ppcart_enqueue_frontend_assets` | action | Free | Fires before a shortcode renders so frontend assets load for page builders that store content in postmeta. | — |
| `ppcart_frontend_allowed_html` | filter | Free | Filters the allowed HTML tags and attributes used to escape front-end output. Return: array — the allowed HTML. | `$allowed` (array) wp_kses-style allowed HTML array |
| `ppcart_frontend_assets_needed` | filter | Free | Filters whether the plugin loads its frontend scripts and styles on the current page. Return: bool — true to force-load the frontend assets. | `$needed` (bool) False by default |
| `ppcart_frontend_message_{$k}` | filter | Free | Filters one translatable front-end script message, where the suffix is the message key. Return: string — the message text. Note: $k is a key of the front-end JS message list. | `$v` (string) Message text |
| `ppcart_product_archive_args` | filter | Free | Filters the WP_Query arguments for the product list in the store shortcode. Return: array — the WP_Query arguments. | `$args` (array) WP_Query arguments |
| `ppcart_receipt_after_order_details` | action | Free | Fires after the order details in the receipt shortcode and in the My Account license plan detail. | `$attr` (array) Receipt data, including order totals |
| `ppcart_script_vars` | filter | Free | Filters the JavaScript variables localized for the checkout script when an order ID is in the request. Return: array — the script variables. | `$ppcart` (array) Script variables  
`$order_id` (int) Order ID from the request  
`$ppcart_product` (object) Product object |
| `ppcart_slug` | filter | Pro | Filters the shortcode prefix shown in the admin shortcode column of upgrade path and collection lists. Return: string — the prefix used in ‘[prefix-form id=N]’. | `$slug` (string) Shortcode prefix |
| `ppcart_template_after_{$slug}` | action | Free | Fires after the plugin resolves the path of a template, where the suffix is the template slug. Note: $slug is the template slug passed to the template loader. Fires before the template file is included. | — |
| `ppcart_template_before_{$slug}` | action | Free | Fires before the plugin resolves a template, where the suffix is the template slug. Note: $slug is the template slug passed to the template loader. | — |
| `ppcart_theme_template_path` | filter | Free | Filters the path of a template file that the plugin loads. A valid existing path overrides the default. Return: string — the template file path. | `$canonical` (string) Default template path  
`$relative` (string) Template path relative to the plugin |
| `ppcart_use_block_product_template` | filter | Free | Filters whether the plugin uses the block (Gutenberg) template for single products. Return: bool — false to use the classic template. | `$enabled` (bool) Whether to use the block template; default true |

## Admin and settings [#](#19-toc-title)

| Hook | Type | Version | Description | Parameters |
| --- | --- | --- | --- | --- |
| `ppcart-metabox-title-access` | filter | Free | Filters the title of the Cart meta box that appears on pages and posts. Return: string – the meta box title. | `$title` (string) Default meta box title, from the ppcart_plugin_title filter |
| `ppcart-repeater-more-link-label` | filter | Free | Filters the label of the Add button on admin repeater fields. Return: string – the label. | `$label` (string) Add button label |
| `ppcart-repeater-remove-link-label` | filter | Free | Filters the label of the Remove link on admin repeater rows. Return: string – the label. | `$label` (string) Remove link label |
| `ppcart-settings-menu-title` | filter | Free | Filters the submenu label of the Customers, Extensions, and Reports admin pages in the Cart menu. Return: string – the menu label. | `$title` (string) Default menu label for that page |
| `ppcart-settings-page-title` | filter | Free | Filters the browser page title of the Customers, Extensions, and Reports admin pages in the Cart menu. Return: string – the page title. | `$title` (string) Default page title for that page |
| `ppcart_admin_ajax_dispatch` | filter | Free | Filters the result of an unknown admin AJAX action so you can handle custom actions. Return: array\|null – an array with a response key to handle the action, or null to fall back to the default handler. | `$data` (array\|null) Result, null by default  
`$ppcart_action` (string) AJAX action name  
`$request_data` (array) Sanitized request data  
`$ajax` (object) Admin AJAX handler |
| `ppcart_admin_allowed_html` | filter | Free | Filters the allowed HTML tags and attributes used to escape admin output. Return: array — the allowed HTML. | `$allowed` (array) wp_kses-style allowed HTML array |
| `ppcart_admin_bar_test_mode_processors` | filter | Free | Filters the list of payment processor names that the admin bar test mode notice shows. Return: array – the processor names (non-array values are ignored). | `$processors` (array) Names of processors that are in test mode, such as Stripe or PayPal |
| `ppcart_admin_reports_product_columns` | action | Free | Fires in the Reports page product section so you can add extra report columns. | `$data` (array) Array with bumps, upsells, downsells, and gateway_labels |
| `ppcart_admin_reports_summary_columns` | action | Free | Fires in the Reports page summary section so you can add extra report columns. | `$data` (array) Array with bumps, upsells, and downsells |
| `ppcart_admin_reports_transactions_label` | filter | Free | Filters the label of the Transactions total on the Reports page. Return: string – the label. | `$label` (string) Default label, “Transactions” |
| `ppcart_after_validate_{$post_type}_meta` | action | Pro | Fires after the plugin validates and saves a post’s metabox values on the admin edit screen. Note: {$post_type} is the metabox class post type. | `$post_id` (int) ID of the post being saved  
`$saved_values` (array) Validated meta values that were saved |
| `ppcart_backend_message_{$k}` | filter | Free | Filters one translatable admin script message, where the suffix is the message key. Return: string — the message text. Note: $k is a key of the admin JS message list. | `$v` (string) Message text |
| `ppcart_csv_import_check_file_path` | filter | Free | Filters whether the CSV import rejects file paths that contain a stream wrapper (://). Return: bool — false to skip the path check. | `$check_import_file_path` (bool) Whether to check the file path; default true  
`$file` (string) Path of the file to check |
| `ppcart_csv_import_valid_filetypes` | filter | Free | Filters the file types the CSV import accepts. Return: array — the valid file types. | `$valid_filetypes` (array) Map of extension to MIME type |
| `ppcart_custom_option_list` | filter | Free | Filters the field definitions of custom settings tabs. Return: array – the field definitions. | `$fields` (array) Field definitions keyed by tab |
| `ppcart_customer_report_admin_notices` | action | Free | Fires on the Customer report page so you can output admin notices. | — |
| `ppcart_dismissible_notice_types` | filter | Free | Filters the notice types that the dismiss AJAX action may store as dismissed. Return: string[] — the dismissible notice types. | `$types` (string[]) Allowed notice types; default is ppcart_price_formatted |
| `ppcart_export_columns` | filter | Free | Filters the column headings of the order CSV export. Return: string[] — the column headings. | `$columns` (string[]) Column heading labels |
| `ppcart_extension_action` | filter | Free | Filters the action button settings of one extension card on the Extensions admin page. Return: array – the action config (non-array values are ignored). | `$action` (array) Action config with label, URL, and CSS class  
`$product` (array) Extension card data |
| `ppcart_extensions_list` | filter | Free | Filters the list of extension cards shown on the Extensions admin page. Return: array – the extension cards. | `$extensions` (array) Extension card data |
| `ppcart_integration_validation_error` | filter | Free | Filters the list of payment integration configuration errors shown in the admin. Return: array — the error messages. | `$action_needed` (array) Map of integration slug to error message |
| `ppcart_invoice_date_documentation_url` | filter | Free | Filters the URL of the documentation link for invoice date tokens on the Invoice settings tab. Return: string – the URL. | `$url` (string) Documentation URL, empty by default |
| `ppcart_is_sensitive_option` | filter | Free | Filters whether a given option name holds a payment or integration secret that the plugin must encrypt. Return: bool — true to treat the option as a secret. | `$is_sensitive` (bool) Whether the option is sensitive  
`$option_name` (string) Option name |
| `ppcart_option_list` | filter | Free | Filters the full list of plugin option fields registered on the settings page. Return: array – the option fields (non-array values are ignored). | `$options` (array) Option field definitions |
| `ppcart_plugin_title` | filter | Free | Filters the plugin display name shown in the admin menu, settings footer text, and the summary report email subject. Return: string — the plugin title to display. | `$title` (string) Plugin title, for example ‘PublishPress Cart’ |
| `ppcart_product_setting_tab_groups` | filter | Free | Filters how the product settings tabs are grouped in the product meta box. Return: array – the tab groups (an empty or non-array value resets to the default). | `$default_groups` (array) Tab groups  
`$product_setting_tabs` (array) Current product setting tabs |
| `ppcart_register_post_type_{$cpt_name}_args` | filter | Pro | Filters the arguments used to register a Pro custom post type (upgrade paths or collections). Return: array — the post type settings. Note: {$cpt_name} is the post type’s cpt_name value. Fires for the upgrade path and collection post types. | `$post_type` (array) Post type settings: cpt_name, plural, single, cap_type, supports, public |
| `ppcart_register_sections` | action | Free | Fires after the core settings sections are registered so you can add your own settings sections. | — |
| `ppcart_repeater_saved_rows` | filter | Free | Filters the saved rows of an admin repeater field before they are rendered. Return: array – the rows. | `$repeater` (array) Saved rows  
`$field_id` (string) Repeater field ID |
| `ppcart_sensitive_option_names` | filter | Free | Filters the list of wp_options names that the plugin’s secrets manager treats as sensitive. Return: string[] — the list of sensitive option names. | `$names` (string[]) Option names |
| `ppcart_setting_tab_pages` | filter | Free | Filters the map of settings tab slugs to settings page slugs that the Settings screen renders. Return: array – the map. | `$tab_page_map` (array) Map of tab slug to settings page slug  
`$setting_tabs` (array) Settings tabs |
| `ppcart_setting_tabs` | filter | Free | Filters the list of custom settings tabs. Return: array – the tabs. | `$setting_tabs` (array) Map of tab slug to tab label |
| `ppcart_settings_admin_notices` | action | Free | Fires inside the settings page header so you can output admin notices. | — |
| `ppcart_show_reviews` | filter | Free | Filters whether the plugin shows its review request prompt. Return: bool — false to hide the review prompt. | `$show` (bool) Whether to show the review prompt; default true |
| `ppcart_show_version_notices` | filter | Free | Filters whether the admin shows the Pro upgrade notice to users who can install plugins. Return: bool — false to hide the notices. | `$show` (bool) Whether to show version notices; default true |
| `ppcart_{$tab_key}_tab_section` | filter | Free | Filters the sections of a custom settings tab, where the tab key is part of the hook name. Return: array – the sections. | `$tab_sections` (array) Map of section key to section title |
| `ppcartlabel-{$field_key}` | filter | Free | Filters the label of one settings field, where the field key is part of the hook name. Return: string – the label. Note: Legacy hook name without an underscore separator. It stays for backward compatibility. | `$label` (string) Field label |
| `ppcartsection-title-company` | filter | Free | Filters the title of the Company and Branding section on the settings page. Return: string – the title. | `$title` (string) Section title |
| `ppcartsection-title-debug` | filter | Free | Filters the title of the Debug section on the settings page. Return: string – the title. | `$title` (string) Section title |
| `ppcartsection-title-downloads` | filter | Free | Filters the title of the Downloads section on the settings page. Return: string – the title. | `$title` (string) Section title |
| `ppcartsection-title-maintenance-secrets` | filter | Free | Filters the title of the Security section on the Maintenance settings tab. Return: string – the title. | `$title` (string) Section title |
| `ppcartsection-title-settings` | filter | Free | Filters the title of the Other Settings section on the settings page. Return: string – the title. | `$title` (string) Section title |
| `ppcartsection-title-{$email_key}` | filter | Free | Filters the title of a section on the Emails settings tab, where the email section key is part of the hook name. Return: string – the title. Note: Legacy hook name without an underscore separator. It stays for backward compatibility. | `$title` (string) Section title |
| `ppcartsection-title-{$section_key}` | filter | Free | Filters the title of a section on a custom settings tab, where the section key is part of the hook name. Return: string – the title. Note: Legacy hook name without an underscore separator. It stays for backward compatibility. | `$title` (string) Section title |

## General [#](#20-toc-title)

| Hook | Type | Version | Description | Parameters |
| --- | --- | --- | --- | --- |
| `ppcart_activate` | action | Free | Fires at the end of plugin activation, after tables, capabilities and defaults are set up. | — |
| `ppcart_countries` | filter | Free | Filters the list of countries the plugin uses in forms and settings. Return: array — the countries. | `$countries` (array) Map of country code to name |
| `ppcart_cpt_options` | filter | Free | Filters the register_post_type arguments for one plugin custom post type. Return: array — the registration arguments. | `$opts` (array) Post type registration arguments  
`$cpt_name` (string) Post type name |
| `ppcart_loaded` | action | Free | Fires once after the plugin finishes loading its main run function. | — |
| `ppcart_post_sanitize` | action | Free | Fires after the default sanitization of a submitted field value, to allow extra sanitization. Note: Your function cannot change the data. | `$sanitized` (mixed) Sanitized value |
| `ppcart_post_types` | filter | Free | Filters the arguments that define the plugin’s custom post types before registration. Return: array — post type definitions; an empty or invalid value falls back to the defaults. | `$default_post_types_args` (array) Post type definitions |
| `ppcart_pre_sanitize` | action | Free | Fires before the default sanitization of a submitted field value. Note: Your function cannot change the data. | `$sanitized` (mixed) Value before sanitization |
| `ppcart_states` | filter | Free | Filters the list of states or regions by country. Return: array — the states. | `$states` (array) Map of country code to states |
| `ppcart_taxonomies` | filter | Free | Filters the definitions of the plugin’s custom taxonomies before registration. Return: array — taxonomy definitions; an empty or invalid value falls back to the defaults. | `$default_taxonomies` (array) Taxonomy definitions |
| `ppcart_taxonomy_options` | filter | Free | Filters the register_taxonomy arguments for one plugin custom taxonomy. Return: array — the registration arguments. | `$opts` (array) Taxonomy registration arguments |

## Moving from StudioCart [#](#21-toc-title)

PublishPress Cart replaces StudioCart. StudioCart hooks used the prefixes `sc_`, `_sc_`, `studiocart_`, and `ncs-cart-`. In PublishPress Cart, the same hooks use `ppcart_`. For example, `sc_order_complete` is now `ppcart_order_complete`.

Old StudioCart hook names work only when StudioCart Compatibility Mode is on. Update your code to the `ppcart_` names.
