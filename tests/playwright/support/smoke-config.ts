import { loadDotEnv } from './load-dotenv';

const DEFAULTS: Record<string, string> = {
  publishpress_cart_host: 'http://tests.local',
  checkout_stripe_url: '/',
  checkout_paypal_url: '/',
  checkout_bump_url: '/',
  checkout_subscription_url: '/',
  checkout_coupon_url: '/',
  checkout_upsell_url: '/',
  checkout_terms_url: '/',
  checkout_tax_url: '/',
  smoke_admin_path: '/wp-admin/',
  first_name: 'GI',
  last_name: 'PublishPress Cart',
  email: 'test@example.com',
  test_email: 'abandoned@example.com',
  phone: '5550101001',
  coupon_100_code: 'SMOKE100',
  expected_product_price: 'One payment of $10',
  taxable_address_line1: '123 Market St',
  taxable_city: 'San Francisco',
  taxable_postal_code: '94105',
  stripe_card_number: '4242424242424242',
  stripe_cc_exp: '12/34',
  cvc: '123',
  postal: '12345',
  paypal_username: '',
  paypal_password: '',
};

/** Smoke GI URL keys that reuse regression fixture pages when unset. */
const REGRESSION_URL_FALLBACKS: Record<string, string> = {
  checkout_stripe_url: 'url_one_time_stripe',
  checkout_paypal_url: 'url_one_time_paypal',
  checkout_subscription_url: 'url_subs_stripe',
};

const REGRESSION_FIELD_FALLBACKS: Record<string, string> = {
  first_name: 'FIRST_NAME',
  last_name: 'LAST_NAME',
  email: 'EMAIL',
  phone: 'PHONE',
};

export class SmokeConfig {
  private readonly values: Record<string, string>;

  private constructor(values: Record<string, string>) {
    this.values = values;
  }

  static fromEnv(): SmokeConfig {
    loadDotEnv();

    const values = { ...DEFAULTS };

    for (const key of Object.keys(DEFAULTS)) {
      const envValue = readEnvValue(key.toUpperCase());

      if (envValue !== null) {
        values[key] = envValue;
      }
    }

    for (const key of ['paypal_username', 'paypal_password'] as const) {
      const envValue = readEnvValue(key.toUpperCase());

      if (envValue !== null) {
        values[key] = envValue;
      }
    }

    const stripeCard = readEnvValue('STRIPE_CARD_NUMBER_SUCCESS') ?? readEnvValue('CARD_NUMBER_SUCCESS');

    if (stripeCard !== null) {
      values.stripe_card_number = stripeCard;
    }

    const stripeExp = readEnvValue('STRIPE_CC_EXP') ?? readEnvValue('CC_EXP');

    if (stripeExp !== null) {
      values.stripe_cc_exp = stripeExp;
    }

    applyRegressionUrlFallbacks(values);
    applyRegressionFieldFallbacks(values);

    return new SmokeConfig(values);
  }

  static regressionFallbackEnvKey(urlKey: string): string | null {
    const regressionKey = REGRESSION_URL_FALLBACKS[urlKey];

    return regressionKey ? regressionKey.toUpperCase() : null;
  }

  var(name: string): string {
    if (name === 'card_number_success') {
      name = 'stripe_card_number';
    }

    if (name === 'cc_exp') {
      name = 'stripe_cc_exp';
    }

    if (!this.values[name]) {
      const envValue = readEnvValue(name.toUpperCase());

      if (envValue !== null) {
        return envValue;
      }

      throw new Error(`Missing smoke variable: ${name}`);
    }

    return this.values[name];
  }

  uniqueEmail(testKey: string): string {
    const email = this.var('email');

    if (!email.includes('@')) {
      return email;
    }

    const [local, domain] = email.split('@', 2);
    const slug = testKey.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');

    return `${local}+${slug}@${domain}`;
  }

  host(): string {
    return this.var('publishpress_cart_host').replace(/\/$/, '');
  }

  pagePath(configKey: string): string {
    const pagePath = this.var(configKey);

    return pagePath.startsWith('/') ? pagePath : `/${pagePath}`;
  }

  isUrlConfigured(configKey: string): boolean {
    const pagePath = this.var(configKey);

    return pagePath !== '' && pagePath !== '/';
  }

  requiresRealPaypal(): boolean {
    const value = (readEnvValue('REGRESSION_REAL_PAYPAL') ?? '1').toLowerCase();

    return ['1', 'true', 'yes', 'on'].includes(value);
  }

  hasPaypalCredentials(): boolean {
    return this.var('paypal_username') !== '' && this.var('paypal_password') !== '';
  }
}

function readEnvValue(key: string): string | null {
  const value = process.env[key];

  if (value !== undefined && value !== '') {
    return value;
  }

  return null;
}

function isUnsetUrl(path: string): boolean {
  return path === '' || path === '/';
}

function applyRegressionUrlFallbacks(values: Record<string, string>): void {
  for (const [smokeKey, regressionKey] of Object.entries(REGRESSION_URL_FALLBACKS)) {
    if (!isUnsetUrl(values[smokeKey] ?? '/')) {
      continue;
    }

    const fallback = readEnvValue(regressionKey.toUpperCase());

    if (fallback !== null && !isUnsetUrl(fallback)) {
      values[smokeKey] = fallback;
    }
  }
}

function applyRegressionFieldFallbacks(values: Record<string, string>): void {
  for (const [smokeKey, regressionEnvKey] of Object.entries(REGRESSION_FIELD_FALLBACKS)) {
    const current = values[smokeKey] ?? '';

    if (current !== '' && !(smokeKey === 'email' && current === 'test@example.com')) {
      continue;
    }

    const fallback = readEnvValue(regressionEnvKey);

    if (fallback !== null && fallback !== '') {
      values[smokeKey] = fallback;
    }
  }
}
