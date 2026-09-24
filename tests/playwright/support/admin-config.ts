import { loadDotEnv } from './load-dotenv';

export class AdminConfig {
  private readonly values: Record<string, string>;

  private constructor(values: Record<string, string>) {
    this.values = values;
  }

  static fromEnv(): AdminConfig {
    loadDotEnv();

    const runId = sanitizeRunId(readEnvValue('ADMIN_WORKFLOW_RUN_ID') ?? `${Date.now()}-${process.pid}`);
    const workflowSlug = `ppcart-pw-${runId}`.slice(0, 120);
    const price = readEnvValue('ADMIN_WORKFLOW_PRODUCT_PRICE') ?? '19';

    return new AdminConfig({
      publishpress_cart_host: (readEnvValue('PUBLISHPRESS_CART_HOST') ?? 'http://tests.local').replace(/\/$/, ''),
      wp_admin_username: readEnvValue('WP_TESTS_ADMIN_USER') ?? '',
      wp_admin_password: readEnvValue('WP_TESTS_ADMIN_PASSWORD') ?? '',
      admin_customer_email: readEnvValue('ADMIN_CUSTOMER_EMAIL') ?? 'playwright-customer@example.invalid',
      admin_product_id: readEnvValue('ADMIN_PRODUCT_ID') ?? '',
      admin_order_id: readEnvValue('ADMIN_ORDER_ID') ?? '',
      admin_subscription_id: readEnvValue('ADMIN_SUBSCRIPTION_ID') ?? '',
      admin_email_product_id: readEnvValue('ADMIN_EMAIL_PRODUCT_ID') ?? '',
      admin_email_order_id: readEnvValue('ADMIN_EMAIL_ORDER_ID') ?? '',
      admin_email_subscription_id: readEnvValue('ADMIN_EMAIL_SUBSCRIPTION_ID') ?? '',
      admin_email_customer_email: readEnvValue('ADMIN_EMAIL_CUSTOMER_EMAIL') ?? '',
      admin_product_post_type: readEnvValue('ADMIN_PRODUCT_POST_TYPE') ?? 'ppcart_product',
      admin_order_post_type: readEnvValue('ADMIN_ORDER_POST_TYPE') ?? 'ppcart_order',
      admin_subscription_post_type: readEnvValue('ADMIN_SUBSCRIPTION_POST_TYPE') ?? 'ppcart_subscription',
      admin_product_cat_taxonomy: readEnvValue('ADMIN_PRODUCT_CAT_TAXONOMY') ?? 'ppcart_product_cat',
      admin_product_tag_taxonomy: readEnvValue('ADMIN_PRODUCT_TAG_TAXONOMY') ?? 'ppcart_product_tag',
      admin_workflow_product_title:
        readEnvValue('ADMIN_WORKFLOW_PRODUCT_TITLE') ?? `PP Cart Playwright Product ${runId}`,
      admin_workflow_meta_product_title:
        readEnvValue('ADMIN_WORKFLOW_META_PRODUCT_TITLE') ?? `PP Cart Playwright Meta Product ${runId}`,
      admin_workflow_plan_slug:
        readEnvValue('ADMIN_WORKFLOW_PLAN_SLUG') ?? `${workflowSlug}-plan`,
      admin_workflow_plan_label:
        readEnvValue('ADMIN_WORKFLOW_PLAN_LABEL') ?? 'Playwright One Time Plan',
      admin_workflow_product_price: price,
      admin_workflow_product_price_display:
        readEnvValue('ADMIN_WORKFLOW_PRODUCT_PRICE_DISPLAY') ?? `$${Number(price).toFixed(2)}`,
      admin_workflow_button_text:
        readEnvValue('ADMIN_WORKFLOW_BUTTON_TEXT') ?? 'Buy Playwright Product',
      admin_workflow_category_name:
        readEnvValue('ADMIN_WORKFLOW_CATEGORY_NAME') ?? `Playwright Category ${runId}`,
      admin_workflow_category_slug:
        readEnvValue('ADMIN_WORKFLOW_CATEGORY_SLUG') ?? `${workflowSlug}-category`,
      admin_workflow_tag_name:
        readEnvValue('ADMIN_WORKFLOW_TAG_NAME') ?? `Playwright Tag ${runId}`,
      admin_workflow_tag_slug:
        readEnvValue('ADMIN_WORKFLOW_TAG_SLUG') ?? `${workflowSlug}-tag`,
      admin_workflow_company_name:
        readEnvValue('ADMIN_WORKFLOW_COMPANY_NAME') ?? `Playwright Company ${runId}`,
      admin_workflow_company_address:
        readEnvValue('ADMIN_WORKFLOW_COMPANY_ADDRESS') ?? '123 Playwright Test Street',
      admin_workflow_public_product_name:
        readEnvValue('ADMIN_WORKFLOW_PUBLIC_PRODUCT_NAME') ?? `Playwright Public Name ${runId}`,
    });
  }

  var(name: string): string {
    const value = this.values[name] ?? process.env[name.toUpperCase()] ?? '';

    if (value === '') {
      throw new Error(`Missing admin test variable: ${name}`);
    }

    return value;
  }

  optional(name: string): string {
    return this.values[name] ?? process.env[name.toUpperCase()] ?? '';
  }

  resolve(value: string): string {
    return value.replace(/\{\{([a-z0-9_]+)\}\}/gi, (_match, name: string) => this.var(name));
  }

  host(): string {
    return this.var('publishpress_cart_host').replace(/\/$/, '');
  }

  hasCredentials(): boolean {
    return this.optional('wp_admin_username') !== '' && this.optional('wp_admin_password') !== '';
  }

  hasFixtureIds(): boolean {
    return [
      'admin_customer_email',
      'admin_product_id',
      'admin_order_id',
      'admin_subscription_id',
    ].every((name) => this.optional(name) !== '');
  }

  hasEmailFixtureIds(): boolean {
    return [
      'admin_email_order_id',
      'admin_email_subscription_id',
      'admin_email_customer_email',
    ].every((name) => this.optional(name) !== '');
  }
}

function sanitizeRunId(value: string): string {
  const normalized = value
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');

  return normalized || 'local';
}

function readEnvValue(key: string): string | null {
  const value = process.env[key];

  return value !== undefined && value !== '' ? value : null;
}
