import { expect, type Locator, type Page } from '@playwright/test';

export type AdminTarget = string | Array<{ selector: string }>;

export type AdminStep = {
  command:
    | 'click'
    | 'check'
    | 'assign'
    | 'assertElementPresent'
    | 'assertTextPresent'
    | 'assertValue'
    | 'assertChecked'
    | 'reload'
    | 'followHref'
    | 'openFirstProductView';
  target: AdminTarget;
  value?: string;
  optional?: boolean;
};

export type AdminTestCase = {
  id: string;
  title: string;
  startUrl: string;
  steps: AdminStep[];
  fixtureDependent?: boolean;
};

export type AdminVariableResolver = {
  resolve(value: string): string;
};

const STEP_TIMEOUT = 10_000;
const OPTIONAL_STEP_TIMEOUT = 1_500;

async function dismissEditorChrome(page: Page): Promise<void> {
  if (!/\/(post-new|post)\.php(?:[?#]|$)/.test(page.url())) {
    return;
  }

  const closers = [
    page.locator('.edit-post-welcome-guide button[aria-label="Close"]').first(),
    page.locator('.components-modal__header button[aria-label="Close"]').first(),
    page.getByRole('button', { name: 'Skip' }).first(),
  ];

  for (const closer of closers) {
    try {
      await closer.click({ timeout: 500 });
    } catch {
      // Welcome/preference modals are absent on Classic Editor.
    }
  }

  await expandProductMetabox(page);
}

async function expandProductMetabox(page: Page): Promise<void> {
  await page
    .evaluate(() => {
      const wp = (window as Window & { wp?: { data?: { dispatch?: (store: string) => Record<string, unknown> } } }).wp;
      const preferences = wp?.data?.dispatch?.('core/preferences') as
        | { set?: (scope: string, key: string, value: unknown) => void }
        | undefined;

      preferences?.set?.('core/edit-post', 'metaBoxesMainIsOpen', true);
      // Keep the pane short enough that it does not cover the canvas title.
      preferences?.set?.('core/edit-post', 'metaBoxesMainOpenHeight', 280);
    })
    .catch(() => {
      // Classic editor has no Gutenberg preferences store.
    });
}

export async function executeAdminTest(
  page: Page,
  testCase: AdminTestCase,
  resolver: AdminVariableResolver
): Promise<void> {
  await page.goto(resolver.resolve(testCase.startUrl));
  await dismissEditorChrome(page);

  for (const step of testCase.steps) {
    await executeAdminStep(page, step, resolver);
  }
}

export async function executeAdminStep(
  page: Page,
  step: AdminStep,
  resolver: AdminVariableResolver
): Promise<void> {
  try {
    await executeRequiredStep(page, step, resolver, step.optional ? OPTIONAL_STEP_TIMEOUT : STEP_TIMEOUT);
  } catch (error) {
    if (!step.optional) {
      throw error;
    }
  }
}

async function executeRequiredStep(
  page: Page,
  step: AdminStep,
  resolver: AdminVariableResolver,
  timeout: number
): Promise<void> {
  switch (step.command) {
    case 'click': {
      const locator = await firstVisibleLocator(page, resolveTarget(step.target, resolver), timeout);
      await locator.click({ timeout });
      return;
    }

    case 'check': {
      const locator = await firstAttachedLocator(page, resolveTarget(step.target, resolver), timeout);

      if (await locator.isChecked()) {
        return;
      }

      const fieldId = await locator.getAttribute('id');

      if (!fieldId) {
        throw new Error('Checkbox is missing an id for its visible label.');
      }

      const label = page.locator(`label[for=${JSON.stringify(fieldId)}]`).first();
      await expect(label).toBeVisible({ timeout });
      await label.click({ timeout });
      await expect(locator).toBeChecked({ timeout });
      return;
    }

    case 'assign': {
      const locator = await firstAttachedLocator(page, resolveTarget(step.target, resolver), timeout);
      try {
        await locator.scrollIntoViewIfNeeded({ timeout: Math.min(timeout, 2_000) });
      } catch {
        // Gutenberg metabox panels can stay in motion; fill still works when attached.
      }
      await locator.fill(resolver.resolve(step.value ?? ''), { timeout, force: true });
      return;
    }

    case 'assertElementPresent': {
      const locator = await firstAttachedLocator(page, resolveTarget(step.target, resolver), timeout);
      await expect(locator).toBeAttached({ timeout });
      return;
    }

    case 'assertTextPresent': {
      const locator = await firstVisibleLocator(page, resolveTarget(step.target, resolver), timeout);
      await expect(locator).toContainText(resolver.resolve(step.value ?? ''), { timeout });
      return;
    }

    case 'assertValue': {
      const locator = await firstAttachedLocator(page, resolveTarget(step.target, resolver), timeout);
      await expect(locator).toHaveValue(resolver.resolve(step.value ?? ''), { timeout });
      return;
    }

    case 'assertChecked': {
      const locator = await firstAttachedLocator(page, resolveTarget(step.target, resolver), timeout);
      await expect(locator).toBeChecked({ timeout });
      return;
    }

    case 'reload': {
      await page.reload({ waitUntil: 'domcontentloaded' });
      await dismissEditorChrome(page);
      await expandProductMetabox(page);
      return;
    }

    case 'followHref': {
      const locator = await firstVisibleLocator(page, resolveTarget(step.target, resolver), timeout);
      await navigateToLocatorHref(page, locator);
      return;
    }

    case 'openFirstProductView': {
      const row = await firstVisibleLocator(page, resolveTarget(step.target, resolver), timeout);
      const viewLink = row.locator('.row-actions .view a, a[aria-label^="View"]').first();
      await expect(viewLink).toBeAttached({ timeout });
      await navigateToLocatorHref(page, viewLink);
      return;
    }

    default: {
      const exhaustive: never = step.command;
      throw new Error(`Unsupported admin command: ${exhaustive}`);
    }
  }
}

const EDITOR_CANVAS_FRAMES = [
  'iframe[name="editor-canvas"]',
  'iframe.editor-canvas__iframe',
];

function locatorsForSelector(page: Page, selector: string): Locator[] {
  const pageLocator = page.locator(selector).first();

  if (isAdminDocumentSelector(selector)) {
    return [pageLocator];
  }

  return [
    pageLocator,
    ...EDITOR_CANVAS_FRAMES.map((frame) => page.frameLocator(frame).locator(selector).first()),
  ];
}

function isAdminDocumentSelector(selector: string): boolean {
  return /^(html|body)([.#:,]|$)/i.test(selector.trim());
}

async function firstLocatorInState(
  page: Page,
  target: AdminTarget,
  state: 'visible' | 'attached',
  timeout: number
): Promise<Locator> {
  const selectors = targetSelectors(target);
  const candidates = selectors.flatMap((selector) => locatorsForSelector(page, selector));
  let lastError: unknown;

  const result = await Promise.any(
    candidates.map(async (locator) => {
      try {
        await locator.waitFor({ state, timeout });
        return locator;
      } catch (error) {
        lastError = error;
        throw error;
      }
    })
  ).catch(() => null);

  if (result) {
    return result;
  }

  const label = state === 'visible' ? 'visible' : 'attached';
  throw new Error(`No ${label} locator found for: ${selectors.join(' | ')}`, { cause: lastError });
}

export async function firstVisibleLocator(
  page: Page,
  target: AdminTarget,
  timeout = STEP_TIMEOUT
): Promise<Locator> {
  return firstLocatorInState(page, target, 'visible', timeout);
}

export async function firstAttachedLocator(
  page: Page,
  target: AdminTarget,
  timeout = STEP_TIMEOUT
): Promise<Locator> {
  return firstLocatorInState(page, target, 'attached', timeout);
}

function resolveTarget(target: AdminTarget, resolver: AdminVariableResolver): AdminTarget {
  if (typeof target === 'string') {
    return resolver.resolve(target);
  }

  return target.map(({ selector }) => ({ selector: resolver.resolve(selector) }));
}

function targetSelectors(target: AdminTarget): string[] {
  const selectors = typeof target === 'string'
    ? [target]
    : target.map(({ selector }) => selector);
  const filtered = selectors.filter((selector) => selector.trim() !== '');

  if (filtered.length === 0) {
    throw new Error('Admin test step has no selector.');
  }

  return filtered;
}

async function navigateToLocatorHref(page: Page, locator: Locator): Promise<void> {
  const href = await locator.getAttribute('href');

  if (!href) {
    throw new Error('Expected link href was not found.');
  }

  await page.goto(new URL(href, page.url()).toString());
}
