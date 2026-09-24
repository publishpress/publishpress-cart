# [ADR-0002] Reuse the invoice secret as the Order access token

Public confirmation pages and `[ppcart_order_downloads]` cannot trust an enumerable order ID. We reuse `_ppcart_invoice_token` (query `token`) as the Order access token instead of minting a second secret or relying on a checkout session cookie, so guest thank-you URLs stay bookmarkable and invoice PDF links already carry the same proof.
