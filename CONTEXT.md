# PublishPress Cart

Checkout, orders, and file delivery for digital products. Terms here are for
domain conversation, not implementation names.

## Language

**Order**:
A completed or in-progress purchase record for one checkout.
_Avoid_: Purchase, transaction, receipt (the receipt is a view of an Order)

**Customer**:
The person who placed the Order. May have no WordPress login (a guest).
_Avoid_: Client, buyer, account

**User**:
A WordPress account. An Order may record a User id in `user_account`, or 0 for a guest Customer.
_Avoid_: Account, customer (those are not the same thing)

**Download**:
A file entitlement row attached to an Order after payment.
_Avoid_: Attachment, media file (those are the product file, not the entitlement)

**Download key**:
The unguessable bearer slug in `/file/{key}` that fetches a Download.
_Avoid_: Token, invoice token, Order access token

**Order access token**:
The unguessable secret stored as `_ppcart_invoice_token`. Invoice PDFs, public confirmation pages, and `[ppcart_order_downloads]` all accept it as query `token`.
_Avoid_: Invoice token (the meta key still says invoice; the secret is not invoice-only), Download key

**Trusted caller**:
Plugin code that already holds an Order object (notification/email builder, `email_download_links`). It may render Download links without public-page proof.
_Avoid_: Internal, authenticated, admin

**Public-page proof**:
The check that a storefront visitor may see an Order: logged-in owner, matching Order access token, or `manage_options`.
_Avoid_: Authentication, login (token proof does not require a User)

## Relationships

- A **Customer** places one or more **Orders**
- An **Order** may belong to a **User**, or to a guest **Customer** with no User
- An **Order** produces zero or more **Downloads**
- Each **Download** has one **Download key**
- Each **Order** has one **Order access token**
- A **Trusted caller** already holds an **Order**; a public page must present **Public-page proof**

## Example dialogue

> **Dev:** "The thank-you page has `?ppcart-order=123`. Can a **Customer** see those **Downloads**?"
> **Domain expert:** "Only with **Public-page proof**. A guest **Customer** uses the **Order access token** in `token`. A logged-in **User** who owns the **Order** does not need the token. Email is a **Trusted caller**, so it can list **Downloads** without that check. The `/file/{key}` URL is a **Download key**, not the **Order access token**."

## Flagged ambiguities

- "token" was used for both the **Download key** and the **Order access token** — resolved: those are different secrets.
- "account" was used for both **Customer** and **User** — resolved: a guest Customer is not a User.
- "invoice token" is the leftover name of the **Order access token** meta key (`_ppcart_invoice_token`) — do not mint a second secret.
