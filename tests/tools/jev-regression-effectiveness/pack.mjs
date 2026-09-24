/**
 * Jev question pack for RT oracle quality.
 *
 * `should_catch` is the independent claim. Do not load
 * docs/testing/cases/regression — those files are generated from the specs.
 *
 * Suite score in run.mjs: mean of catches_distinguishing.noul.
 * Do not treat oracle_strength.score as a percentage (Jev 1.13 jaggedness).
 */
export const MODEL = 'jev-1.13.0';

export const QUESTIONS = {
  catches_distinguishing: {
    type: 'noul',
    instructions:
      'If `should_catch` were false in the running product, would `spec` plus `helpers` fail? A fail means an expect() or thrown Error that checks that claim. Ignore test.skip gates, timeouts, and helper functions that only fill forms or click buttons.',
    criteria: {
      true: 'The spec or an inlined helper asserts the claim in `should_catch`. The test would fail if that claim were false.',
      false: 'The spec can still pass when `should_catch` is false. Assertions only check a generic confirmation, navigation, HTTP ok, or a different claim.',
    },
  },
  gap: {
    type: 'choice',
    instructions: 'What is the main gap between `should_catch` and what `spec` plus `helpers` actually assert?',
    criteria: {
      ok: 'An assertion would fail if `should_catch` were false.',
      weak_oracle: 'Assertions only check a generic order confirmation, visible UI, or HTTP ok.',
      sibling_clone: 'Unique steps click or select something (for example a payment plan) but never assert the resulting order, plan, price, or security outcome.',
      setup_only: 'The spec reaches a page or sends a request but does not assert a product outcome.',
    },
  },
  oracle_strength: {
    type: 'score',
    instructions: 'How strongly do `spec` plus `helpers` assert a product outcome? Rate the assertions that exist, not the test title.',
    criteria: [
      'No expect() on a product outcome.',
      'Only UI visibility or HTTP ok.',
      'Generic order confirmation: ppcart-order in the URL and a thank-you message, with no check of the distinguishing claim.',
      'An assertion that would fail if `should_catch` were false.',
    ],
  },
};

/** @type {{ id: string; spec: string; should_catch: string }[]} */
export const CASES = [
  {
    id: 'RT-001',
    spec: 'tests/playwright/regression/rt001-purchase-one-time-stripe-checkout-flow-first-price.spec.ts',
    should_catch:
      'The completed one-time Stripe order is for the product first (default) payment plan, not a secondary plan or a sale price.',
  },
  {
    id: 'RT-002',
    spec: 'tests/playwright/regression/rt002-purchase-one-time-stripe-checkout-flow-secondary-price.spec.ts',
    should_catch:
      'The buyer selected payment plan 2, and the completed one-time Stripe order is for that plan, not plan 1.',
  },
  {
    id: 'RT-020',
    spec: 'tests/playwright/regression/rt020-reject-unverified-paypal-ipn-refund.spec.ts',
    should_catch:
      'A PayPal IPN with payment_status Refunded that is not a verified PayPal IPN is rejected: the order refund_log stays empty.',
  },
  {
    id: 'RT-021',
    spec: 'tests/playwright/regression/rt021-checkout-completion-requires-order-access.spec.ts',
    should_catch:
      'Checkout completion does not fire for a pending order unless the request carries that order ppcart-access token. Replaying the original payment POST does not fire completion a second time.',
  },
  {
    id: 'RT-022',
    spec: 'tests/playwright/regression/rt022-paypal-return-requires-order-access.spec.ts',
    should_catch:
      'A PayPal return URL without ppcart-access does not complete the order. When PDT is required, a return with a bogus or missing tx does not mark the pending order paid.',
  },
];
