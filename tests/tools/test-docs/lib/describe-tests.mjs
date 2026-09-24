/**
 * Translate test code into human-readable case content.
 *
 * The Playwright suites in this repo are generated from Ghost Inspector
 * exports and share a small helper vocabulary, so each call maps cleanly onto
 * one documented step or expected result. Anything unrecognised is reported by
 * the generator so the catalog never silently invents behaviour.
 */

/** @typedef {{ kind: 'step' | 'expected' | 'precondition'; text: string }} Line */

const QUOTED_LITERAL =
  /'((?:\\.|[^'\\])*)'|"((?:\\.|[^"\\])*)"|`((?:\\.|[^`\\])*)`/g;

/**
 * Every quoted literal in a statement, in source order.
 * @param {string} statement
 * @returns {string[]}
 */
function quotedLiterals(statement) {
  return [...statement.matchAll(QUOTED_LITERAL)].map(
    (match) => (match[1] ?? match[2] ?? match[3]).replace(/\\(['"`])/g, '$1'),
  );
}

/**
 * Describe how a value is supplied to a form field.
 * @param {string} statement
 * @param {string[]} args
 * @returns {string}
 */
function valueDescription(statement, args) {
  if (statement.includes('config.uniqueEmail(')) {
    return 'a unique test email address';
  }

  if (statement.includes('config.var(')) {
    return `the configured \`${args[1]}\``;
  }

  return args[1] === undefined ? 'the configured value' : `"${args[1]}"`;
}

/**
 * @type {{ match: RegExp; render: (args: string[], statement: string) => Line[] }[]}
 */
const PLAYWRIGHT_VOCABULARY = [
  {
    match: /^skipUnless(?:Regression|Smoke)Url\(/,
    render: (args) => [
      { kind: 'precondition', text: `\`${args[0]}\` is configured for the target site` },
    ],
  },
  {
    match: /^skipUnlessPaypal\(/,
    render: () => [
      { kind: 'precondition', text: 'PayPal sandbox credentials are configured' },
    ],
  },
  {
    match: /^page\.goto\(/,
    render: (args) => [
      { kind: 'step', text: `Open the checkout page configured as \`${args[0]}\`.` },
    ],
  },
  {
    match: /^fillCheckoutContact\(/,
    render: () => [
      {
        kind: 'step',
        text: 'Fill the checkout contact fields: first name, last name, a unique email, phone, and company.',
      },
    ],
  },
  {
    match: /^expectSalePaymentPlan\(/,
    render: (args, statement) => {
      const plan = statement.match(/,\s*(\d+)\s*\)/)?.[1] ?? '';
      return [
        { kind: 'step', text: `Review payment plan ${plan}.` },
        {
          kind: 'expected',
          text: `Payment plan ${plan} is visible and shows both its original price and its sale price.`,
        },
      ];
    },
  },
  {
    match: /^expectPaymentPlan\(/,
    render: (args, statement) => {
      const plan = statement.match(/,\s*(\d+)\s*\)/)?.[1] ?? '';
      return [
        { kind: 'step', text: `Review payment plan ${plan}.` },
        { kind: 'expected', text: `Payment plan ${plan} is visible on the checkout form.` },
      ];
    },
  },
  {
    match: /^selectPaymentPlan\(/,
    render: (args, statement) => [
      { kind: 'step', text: `Select payment plan ${statement.match(/,\s*(\d+)\s*\)/)?.[1] ?? ''}.` },
    ],
  },
  {
    match: /^fillCustomPrice\(/,
    render: (args) => [
      {
        kind: 'step',
        text: `Enter the custom price from \`${args[0]}\` and wait for the total to update.`,
      },
    ],
  },
  {
    match: /^fillCard\(/,
    render: (args, statement) => [
      {
        kind: 'step',
        text: statement.includes('decline')
          ? 'Enter a Stripe test card that will be declined.'
          : 'Enter the Stripe test card details.',
      },
    ],
  },
  {
    match: /^select(?:PayPal|PaypalPayment)\(/,
    render: () => [{ kind: 'step', text: 'Choose PayPal as the payment method.' }],
  },
  {
    match: /^fillTaxableAddress\(/,
    render: () => [
      { kind: 'step', text: 'Fill a taxable billing address and refresh the order total.' },
    ],
  },
  {
    match: /^clickTermsCheckbox\(/,
    render: () => [{ kind: 'step', text: 'Tick the terms and conditions checkbox.' }],
  },
  {
    match: /^clickOrderNowRepeated\(/,
    render: (args, statement) => [
      {
        kind: 'step',
        text: `Click Order Now ${statement.match(/,\s*(\d+)\s*\)/)?.[1] ?? 'several'} times in quick succession.`,
      },
    ],
  },
  {
    match: /^clickOrderNowForValidation\(/,
    render: () => [
      { kind: 'step', text: 'Force-submit the checkout form to trigger client-side validation.' },
    ],
  },
  {
    match: /^clickOrderNow\(/,
    render: () => [{ kind: 'step', text: 'Click Order Now.' }],
  },
  {
    match: /^submitStripeCheckoutExpectingDecline\(/,
    render: () => [
      { kind: 'step', text: 'Submit the checkout with a card Stripe will decline.' },
      {
        kind: 'expected',
        text: 'The decline is reported to the customer and Order Now becomes usable again without creating an order.',
      },
    ],
  },
  {
    match: /^resolvePayPalPage\(/,
    render: () => [{ kind: 'step', text: 'Switch to the PayPal checkout window.' }],
  },
  {
    match: /^loginAndComplete\(/,
    render: () => [
      { kind: 'step', text: 'Log in to the PayPal sandbox and approve the payment.' },
    ],
  },
  {
    match: /^assertOrderReceived\(/,
    render: (args, statement) => {
      const lines = [];

      if (statement.includes('resolveMerchantPage')) {
        lines.push({ kind: 'step', text: 'Return to the merchant site.' });
      }

      lines.push({
        kind: 'expected',
        text: 'The browser lands on a URL carrying a `ppcart-order` id and the order confirmation message is visible.',
      });

      return lines;
    },
  },
  {
    match: /^assertBodyContains\(/,
    render: (args) => [{ kind: 'expected', text: `The page contains "${args[0]}".` }],
  },
  {
    match: /^assertElementContainsText\(/,
    render: (args) => [{ kind: 'expected', text: `\`${args[0]}\` contains "${args[1]}".` }],
  },
  {
    match: /^assertElementNotPresent\(/,
    render: (args) => [{ kind: 'expected', text: `\`${args[0]}\` is not rendered.` }],
  },
  {
    match: /^assertOrderNowSubmitting\(/,
    render: () => [
      {
        kind: 'expected',
        text: 'Order Now switches to its running state and is disabled, so a second submit cannot be sent.',
      },
    ],
  },
  {
    match: /^assertOrderNowRecoverableAfterStripeError\(/,
    render: () => [
      {
        kind: 'expected',
        text: 'Order Now leaves its running state and becomes clickable again, and no order id appears in the URL.',
      },
    ],
  },
  {
    match: /^assertManualTaxApplied\(/,
    render: (args, statement) => [
      {
        kind: 'expected',
        text: `Manual tax is added to the order total, which equals ${
          statement.match(/,\s*([\d.]+)\s*\)/)?.[1] ?? 'the expected amount'
        }.`,
      },
    ],
  },
  {
    match: /^page\.locator\(.*\)\.fill\(/,
    render: (args, statement) => [
      { kind: 'step', text: `Fill \`${args[0]}\` with ${valueDescription(statement, args)}.` },
    ],
  },
  {
    match: /^page\.locator\(.*\)\.click\(\)$/,
    render: (args) => [{ kind: 'step', text: `Click \`${args[0]}\`.` }],
  },
  {
    match: /^page\.locator\(.*\)\.waitFor\(/,
    render: (args) => [{ kind: 'step', text: `Wait for \`${args[0]}\` to appear.` }],
  },
  {
    match: /^expect\(page\.locator\(.*\)\)\.not\.toBeChecked\(/,
    render: (args) => [{ kind: 'expected', text: `\`${args[0]}\` is not checked.` }],
  },
  {
    match: /^expect\(page\.locator\(.*\)\)\.toBeChecked\(/,
    render: (args) => [{ kind: 'expected', text: `\`${args[0]}\` is checked.` }],
  },
  {
    match: /^expect\(page\.locator\(.*\)\)\.toBeVisible\(/,
    render: (args) => [{ kind: 'expected', text: `\`${args[0]}\` is visible.` }],
  },
];

/** Statements that carry no documentation value. */
const PLAYWRIGHT_IGNORED = [
  /^test\.setTimeout\(/,
  /^test\.skip\(/,
  /^page\.waitForTimeout\(/,
  /^\}/,
  /^\)/,
];

/** Net bracket depth of a line, ignoring brackets inside string literals. */
function bracketDelta(line) {
  const bare = line.replace(QUOTED_LITERAL, '""');
  let delta = 0;

  for (const char of bare) {
    if (char === '(' || char === '[' || char === '{') {
      delta += 1;
    } else if (char === ')' || char === ']' || char === '}') {
      delta -= 1;
    }
  }

  return delta;
}

/**
 * Split a test body into single executable statements, rejoining calls that
 * wrap across several lines.
 * @param {string} body
 * @returns {string[]}
 */
function statements(body) {
  const out = [];
  let buffer = '';
  let depth = 0;

  for (const raw of body.split('\n')) {
    const line = raw.trim();

    if (!line) {
      continue;
    }

    buffer = buffer ? `${buffer} ${line}` : line;
    depth += bracketDelta(line);

    if (depth <= 0) {
      out.push(buffer.replace(/^await\s+/, '').replace(/;$/, ''));
      buffer = '';
      depth = 0;
    }
  }

  if (buffer) {
    out.push(buffer.replace(/^await\s+/, '').replace(/;$/, ''));
  }

  return out.filter(Boolean);
}

/**
 * Describe a Playwright test body as documented lines.
 * @param {string} body
 * @returns {{ lines: Line[]; unknown: string[] }}
 */
export function describePlaywrightTest(body) {
  /** @type {Line[]} */
  const lines = [];
  /** @type {string[]} */
  const unknown = [];
  /** Locators held in a local variable, so later uses can name their selector. */
  const locators = new Map();

  for (const raw of statements(body)) {
    let statement = raw;

    const locatorBinding = statement.match(/^(?:const|let)\s+(\w+)\s*=\s*(page\.locator\(.*\))$/);

    if (locatorBinding) {
      locators.set(locatorBinding[1], locatorBinding[2]);
      continue;
    }

    statement = statement.replace(/^(?:const|let)\s+[\w{}\s,]+?\s*=\s*/, '').replace(/^await\s+/, '');

    for (const [name, locator] of locators) {
      statement = statement
        .replace(new RegExp(String.raw`^${name}\.`), `${locator}.`)
        .replace(new RegExp(String.raw`^expect\(${name}\)`), `expect(${locator})`);
    }

    if (PLAYWRIGHT_IGNORED.some((pattern) => pattern.test(statement))) {
      continue;
    }

    const entry = PLAYWRIGHT_VOCABULARY.find(({ match }) => match.test(statement));

    if (!entry) {
      unknown.push(statement);
      continue;
    }

    lines.push(...entry.render(quotedLiterals(statement), statement));
  }

  return { lines, unknown };
}

/**
 * Describe one step of a data-driven admin test case.
 * @param {{ command: string; target?: string; value?: string }} step
 * @returns {Line | null}
 */
export function describeAdminStep(step) {
  const { command, target, value } = step;

  switch (command) {
    case 'assertElementPresent':
      return { kind: 'expected', text: `\`${target}\` is present on the page.` };
    case 'assertTextPresent':
      return { kind: 'expected', text: `\`${target}\` contains "${value}".` };
    case 'click':
      return { kind: 'step', text: `Click \`${target}\`.` };
    case 'assign':
      return { kind: 'step', text: `Set \`${target}\` to "${value}".` };
    case 'followHref':
      return { kind: 'step', text: `Follow the link in \`${target}\`.` };
    case 'openFirstProductView':
      return { kind: 'step', text: 'Open the public view of the first product in the list.' };
    default:
      return null;
  }
}
