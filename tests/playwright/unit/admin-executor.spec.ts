import { expect, test } from '@playwright/test';

import {
  executeAdminStep,
  firstAttachedLocator,
  firstVisibleLocator,
  type AdminVariableResolver,
} from '../support/admin-executor';

const resolver: AdminVariableResolver = {
  resolve: (value) => value.replace('{{value}}', 'Resolved value'),
};

test('admin executor resolves variables and asserts text', async ({ page }) => {
  await page.setContent('<div id="message">Resolved value</div>');
  await executeAdminStep(
    page,
    { command: 'assertTextPresent', target: '#message', value: '{{value}}' },
    resolver
  );
});

test('admin executor uses ordered selector fallbacks', async ({ page }) => {
  await page.setContent('<button id="available">Continue</button>');
  const locator = await firstVisibleLocator(
    page,
    [{ selector: '#missing' }, { selector: '#available' }],
    1_000
  );
  await expect(locator).toHaveAttribute('id', 'available');
});

test('admin executor matches comma-separated leftover or canonical body classes', async ({ page }) => {
  await page.setContent('<body class="wp-admin post-type-ppcart_product"><div id="wpbody">Products</div></body>');
  const locator = await firstAttachedLocator(
    page,
    'body.post-type-ppcart_product, body.post-type-sc_product',
    1_000
  );
  await expect(locator).toBeAttached();
});

test('admin executor does not look for admin body classes inside the editor canvas', async ({ page }) => {
  await page.setContent(
    '<body class="wp-admin post-type-ppcart_product">'
    + '<iframe name="editor-canvas" srcdoc="<body class=&quot;post-type-ppcart_product canvas&quot;>Canvas</body>"></iframe>'
    + '<div id="wpbody">Products</div>'
    + '</body>'
  );

  const locator = await firstAttachedLocator(page, 'body.post-type-ppcart_product', 1_000);
  await expect(locator).toHaveClass(/wp-admin/);
  await expect(locator).not.toHaveClass(/canvas/);
});

test('admin executor treats hidden enhanced controls as present', async ({ page }) => {
  await page.setContent(
    '<select id="currency" data-testid="ppcart-admin-field-sc-currency" style="display: none">'
    + '<option value="USD">USD</option>'
    + '</select>'
  );

  await executeAdminStep(
    page,
    {
      command: 'assertElementPresent',
      target: '[data-testid="ppcart-admin-field-sc-currency"]',
    },
    resolver
  );

  const locator = await firstAttachedLocator(
    page,
    '[data-testid="ppcart-admin-field-sc-currency"]',
    1_000
  );
  await expect(locator).toBeAttached();
  await expect(locator).toBeHidden();
});

test('admin executor ignores an unavailable optional click', async ({ page }) => {
  await page.setContent('<p>Ready</p>');
  await executeAdminStep(page, { command: 'click', target: '#missing', optional: true }, resolver);
  await expect(page.locator('p')).toHaveText('Ready');
});

test('admin executor follows a resolved href', async ({ page }) => {
  await page.goto('about:blank');
  await page.setContent('<a id="next" href="#done">Next</a>');
  await executeAdminStep(page, { command: 'followHref', target: '#next' }, resolver);
  await expect(page).toHaveURL(/#done$/);
});

test('admin executor opens the first product view link', async ({ page }) => {
  await page.goto('about:blank');
  await page.setContent(
    '<table id="the-list"><tr><td><span class="row-actions"><span class="view"><a href="#product">View</a></span></span></td></tr></table>'
  );
  await executeAdminStep(
    page,
    { command: 'openFirstProductView', target: '#the-list tr' },
    resolver
  );
  await expect(page).toHaveURL(/#product$/);
});
