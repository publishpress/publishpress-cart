import { loadDotEnv } from './load-dotenv';

const DEFAULTS: Record<string, string> = {
  publishpress_cart_host: 'http://tests.local',
  url_one_time_stripe: '/',
  url_one_time_stripe_with_sale_price: '/',
  url_subs_stripe: '/',
  url_subs_stripe_with_sale_price: '/',
  url_custom_price_stripe: '/',
  url_one_time_paypal: '/',
  url_one_time_paypal_with_sale_price: '/',
  url_subs_paypal: '/',
  url_subs_paypal_with_sale_price: '/',
  url_custom_price_paypal: '/',
  url_product_free: '/',
  first_name: 'Test',
  last_name: 'User',
  email: 'test@example.com',
  phone: '5551234567',
  company: 'Test Company',
  stripe_card_number: '4242424242424242',
  stripe_card_number_decline: '4000000000000002',
  stripe_cc_exp: '12/34',
  cvc: '123',
  postal: '12345',
  paypal_card_number: '',
  paypal_cc_exp: '',
  modify_price: '25',
  paypal_username: '',
  paypal_password: '',
};

export class RegressionConfig {
  private readonly values: Record<string, string>;

  private constructor(values: Record<string, string>) {
    this.values = values;
  }

  static fromEnv(): RegressionConfig {
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

    const stripeDeclineCard = readEnvValue('STRIPE_CARD_NUMBER_DECLINE');

    if (stripeDeclineCard !== null) {
      values.stripe_card_number_decline = stripeDeclineCard;
    }

    for (const key of ['paypal_card_number', 'paypal_cc_exp'] as const) {
      const envValue = readEnvValue(key.toUpperCase());

      if (envValue !== null) {
        values[key] = envValue;
      }
    }

    return new RegressionConfig(values);
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

      throw new Error(`Missing regression variable: ${name}`);
    }

    return this.values[name];
  }

  optional(name: string): string {
    const envValue = readEnvValue(name.toUpperCase());

    if (envValue !== null) {
      return envValue;
    }

    return this.values[name] ?? '';
  }


  uniqueEmail(testKey: string): string {
    const email = this.var('email');

    if (!email.includes('@')) {
      return email;
    }

    const [local, domain] = email.split('@', 2);
    const slug = testKey.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    const resolvedLocal = local.replace(
      /\{\{timestamp\}\}/gi,
      `${Date.now()}-${process.pid}`,
    );

    return `${resolvedLocal}+${slug}@${domain}`;
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
    if (this.mockedGateways()) {
      return false;
    }

    const value = (readEnvValue('REGRESSION_REAL_PAYPAL') ?? '1').toLowerCase();

    return ['1', 'true', 'yes', 'on'].includes(value);
  }

  mockedGateways(): boolean {
    const value = (readEnvValue('REGRESSION_MOCKED_GATEWAYS') ?? '0').toLowerCase();

    return ['1', 'true', 'yes', 'on'].includes(value);
  }

  hasPaypalCredentials(): boolean {
    return this.optional('paypal_username').trim() !== '' && this.optional('paypal_password').trim() !== '';
  }

  static normalizeCardExpiry(value: string): string {
    const trimmed = value.trim();
    const match = /^(\d{2})\s*\/\s*(\d{4})$/.exec(trimmed);

    if (match) {
      return `${match[1]}/${match[2].slice(-2)}`;
    }

    return trimmed;
  }
}

function readEnvValue(key: string): string | null {
  const value = process.env[key];

  if (value !== undefined && value !== '') {
    return value;
  }

  return null;
}
