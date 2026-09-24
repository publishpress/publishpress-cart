# [ADR-0003] Stripe object metadata compatibility bridge

First-party Stripe writes and reads use `metadata.ppcart_*` via
`ppcart_stripe_metadata()`. The helper never branches on Compatibility Mode.
When the mode is on, Compat registers a leftover-name lookup
(`maps/stripe-metadata.php`) so empty canonical keys fall through to `sc_*`.
Canonical non-empty wins even if leftover differs. Missing, `''`, and `0` are
empty. Product save stamps `ppcart_product_id` (and `origin`) when the
canonical key is missing, even if leftover already matched. Webhooks, stripe-sync,
and hosted return stay read-only toward Stripe: leftover-only objects match only
while Compat is on. There is no Stripe-side data migration and no leftover writes.
