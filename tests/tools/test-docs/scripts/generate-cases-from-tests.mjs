#!/usr/bin/env node
/**
 * Generate catalog cases from the test files that implement them.
 *
 * Test files are the source of truth. Titles, steps, expected results, tags
 * and the automation link are derived from code; module, features and
 * priority come from the plugin's case-generation profile.
 *
 * Usage:
 *   node tests/tools/test-docs/scripts/generate-cases-from-tests.mjs --suite regression [--write]
 *
 *   --suite <key>   Suite to generate (repeatable; required)
 *   --write         Write files (otherwise prints what would change)
 *   --prune         Delete existing cases in the suite that no test declares
 */
import { existsSync, readFileSync, readdirSync, unlinkSync } from 'node:fs';
import { basename, join, relative } from 'node:path';

import { parse as parseYaml } from 'yaml';

import { loadConfig } from '../lib/load-config.mjs';
import { writeCase, caseFilePath } from '../lib/write-case.mjs';
import { listTestFiles } from '../lib/discover-tests.mjs';
import { describePlaywrightTest, describeAdminStep } from '../lib/describe-tests.mjs';

const ADMIN_CASES_FILE = 'tests/playwright/support/admin-cases.ts';

/** Documented defaults for admin URL placeholders (live CPT/taxonomy names). */
const ADMIN_DOC_PLACEHOLDERS = {
  publishpress_cart_host: '<site>',
  admin_product_post_type: 'ppcart_product',
  admin_order_post_type: 'ppcart_order',
  admin_subscription_post_type: 'ppcart_subscription',
  admin_product_cat_taxonomy: 'ppcart_product_cat',
  admin_product_tag_taxonomy: 'ppcart_product_tag',
};

function documentAdminStartUrl(startUrl) {
  return startUrl.replace(/\{\{([\w_]+)\}\}/g, (full, key) => ADMIN_DOC_PLACEHOLDERS[key] ?? full);
}

function parseArgs(argv) {
  const options = { suites: [], write: false, prune: false };

  for (let i = 0; i < argv.length; i += 1) {
    const arg = argv[i];

    if (arg === '--suite') {
      options.suites.push(argv[++i]);
    } else if (arg.startsWith('--suite=')) {
      options.suites.push(arg.slice('--suite='.length));
    } else if (arg === '--write') {
      options.write = true;
    } else if (arg === '--prune') {
      options.prune = true;
    } else {
      throw new Error(`Unknown argument: ${arg}`);
    }
  }

  if (options.suites.length === 0) {
    throw new Error('At least one --suite is required');
  }

  return options;
}

/**
 * Load the plugin's case-generation profile, if present.
 */
function loadProfile(repoRoot, pluginId) {
  const path = join(
    repoRoot,
    'tests/tools/test-docs/profiles',
    pluginId ?? '',
    'case-generation.yaml',
  );

  return existsSync(path) ? (parseYaml(readFileSync(path, 'utf8')) ?? {}) : {};
}

/** Does `number` fall inside any `from-to` range? */
function inRanges(number, ranges) {
  return (ranges ?? []).some((range) => {
    const [from, to] = String(range).split('-').map(Number);
    return number >= from && number <= (Number.isNaN(to) ? from : to);
  });
}

function curationFor(profile, suiteKey, id, evidence = '') {
  const suite = profile.suites?.[suiteKey] ?? {};
  const number = Number(id.split('-')[1]);

  const extensions = (suite.extensions ?? [])
    .filter((entry) => inRanges(number, entry.ranges))
    .reduce((merged, entry) => ({ ...merged, ...entry.values }), {});

  const features = new Set(
    (suite.features ?? []).find((entry) => inRanges(number, entry.ranges))?.values ?? [],
  );

  for (const rule of suite.derived_features ?? []) {
    if (new RegExp(rule.when, 'i').test(evidence)) {
      for (const feature of rule.add ?? []) {
        features.add(feature);
      }
    }
  }

  return {
    module: (suite.modules ?? []).find((entry) => inRanges(number, entry.ranges))?.id,
    features: [...features],
    priority: suite.default_priority ?? 'medium',
    extensions,
  };
}

/** Strip the leading id from a test title. */
function titleFrom(rawTitle, id) {
  const text = rawTitle.replace(id, '').trim().replace(/^[-–—:]\s*/, '');
  return text.charAt(0).toUpperCase() + text.slice(1);
}

function renderList(lines) {
  return lines.map((line, index) => `${index + 1}. ${line}`).join('\n');
}

/**
 * Build case content from a Playwright spec file.
 */
function casesFromSpec(repoRoot, rel, suiteKey, idPattern) {
  const content = readFileSync(join(repoRoot, rel), 'utf8');
  const testPattern = new RegExp(
    String.raw`\btest\(\s*(['"\`])(.*?)\1\s*,\s*(?:\{([^}]*)\}\s*,\s*)?async\s*\([^)]*\)\s*=>\s*\{([\s\S]*?)\n\}\);`,
    'g',
  );

  const cases = [];
  let match;

  while ((match = testPattern.exec(content)) !== null) {
    const rawTitle = match[2];
    const id = rawTitle.match(idPattern)?.[0];

    if (!id) {
      continue;
    }

    const tags = [...(match[3] ?? '').matchAll(/["']@([\w-]+)["']/g)].map((entry) => entry[1]);
    const { lines, unknown } = describePlaywrightTest(match[4]);

    cases.push({
      id,
      title: titleFrom(rawTitle, id),
      tags: tags.filter((tag) => tag !== suiteKey),
      lines,
      unknown,
      file: rel,
      evidence: `${rawTitle}\n${match[4]}`,
    });
  }

  return cases;
}

/**
 * Build case content from the data-driven admin case definitions.
 */
function casesFromAdminDefinitions(repoRoot, suiteKey, specsById) {
  const source = readFileSync(join(repoRoot, ADMIN_CASES_FILE), 'utf8');
  const cases = [];
  const blockPattern = /\{\s*\n\s*id:\s*"(AD-\d{3})",\s*\n\s*title:\s*"([^"]*)",\s*\n\s*startUrl:\s*"([^"]*)",\s*\n\s*steps:\s*\[([\s\S]*?)\n\s{4}\],/g;
  let match;

  while ((match = blockPattern.exec(source)) !== null) {
    const [, id, title, startUrl, stepsBlock] = match;
    const lines = [
      {
        kind: 'step',
        text: `Sign in to WP Admin and open \`${documentAdminStartUrl(startUrl)}\`.`,
      },
    ];

    const stepPattern = /\{\s*command:\s*"(\w+)",(?:\s*target:\s*"((?:\\.|[^"\\])*)",)?(?:\s*value:\s*"((?:\\.|[^"\\])*)",?)?\s*\}/g;
    let stepMatch;

    while ((stepMatch = stepPattern.exec(stepsBlock)) !== null) {
      const described = describeAdminStep({
        command: stepMatch[1],
        target: stepMatch[2]?.replace(/\\"/g, '"'),
        value: stepMatch[3]?.replace(/\\"/g, '"'),
      });

      if (described) {
        lines.push(described);
      }
    }

    const spec = specsById.get(id);

    cases.push({
      id,
      title,
      tags: spec?.tags ?? [],
      lines,
      unknown: [],
      file: spec?.file ?? '',
      evidence: `${title}\n${startUrl}\n${stepsBlock}`,
    });
  }

  return cases;
}

function buildCase(entry, suiteKey, profile, edition) {
  const steps = entry.lines.filter((line) => line.kind === 'step').map((line) => line.text);
  const expected = entry.lines.filter((line) => line.kind === 'expected').map((line) => line.text);
  const preconditions = entry.lines
    .filter((line) => line.kind === 'precondition')
    .map((line) => line.text);

  const curation = curationFor(profile, suiteKey, entry.id, entry.evidence ?? '');

  const scenarioParts = [
    `Automated by \`${entry.file}\`. This case documents what that test actually does; edit the test first, then regenerate.`,
  ];

  if (preconditions.length > 0) {
    scenarioParts.push(
      `The test is skipped unless ${preconditions.join(' and ')}.`,
    );
  }

  return {
    id: entry.id,
    suite: suiteKey,
    edition,
    module: curation.module,
    features: curation.features,
    priority: curation.priority,
    title: entry.title,
    tags: entry.tags,
    extensions: curation.extensions,
    automation: {
      status: 'implemented',
      implementations: [{ suite: suiteKey, file: entry.file }],
    },
    scenario: scenarioParts.join('\n\n'),
    steps: steps.length > 0 ? renderList(steps) : '1. See the linked test file.',
    expected:
      expected.length > 0
        ? renderList(expected)
        : '1. The test completes without a failed assertion.',
    edge_cases: '- None.',
  };
}

async function main() {
  const options = parseArgs(process.argv.slice(2));
  const { config, repoRoot } = await loadConfig();
  const casesRoot = join(repoRoot, config.catalog?.cases ?? 'docs/testing/cases');
  const profile = loadProfile(repoRoot, config.plugin?.id);
  const edition = config.plugin?.edition === 'pro' ? 'pro' : 'both';

  for (const suiteKey of options.suites) {
    const suite = config.suites?.[suiteKey];

    if (!suite) {
      throw new Error(`Unknown suite "${suiteKey}"`);
    }

    const idPattern = new RegExp((suite.id_pattern ?? '').replace(/\\\\/g, '\\'));
    const specCases = new Map();

    for (const root of suite.roots ?? []) {
      for (const rel of listTestFiles(repoRoot, root, suite.spec_glob ?? '**/*.spec.ts')) {
        for (const entry of casesFromSpec(repoRoot, rel, suiteKey, idPattern)) {
          specCases.set(entry.id, entry);
        }
      }
    }

    const entries =
      suiteKey === 'admin'
        ? casesFromAdminDefinitions(repoRoot, suiteKey, specCases)
        : [...specCases.values()];

    entries.sort((a, b) => a.id.localeCompare(b.id));

    const unknown = entries.flatMap((entry) =>
      entry.unknown.map((statement) => `${entry.id}: ${statement}`),
    );

    if (unknown.length > 0) {
      console.warn(`\n${suiteKey}: ${unknown.length} statement(s) had no documented meaning:`);
      for (const line of unknown) {
        console.warn(`  ${line}`);
      }
      console.warn('Add them to PLAYWRIGHT_VOCABULARY in lib/describe-tests.mjs.\n');
    }

    const generated = new Set();

    for (const entry of entries) {
      const testCase = buildCase(entry, suiteKey, profile, edition);
      generated.add(`${entry.id}.md`);

      if (options.write) {
        writeCase(casesRoot, testCase);
      }
    }

    console.log(
      `${suiteKey}: ${options.write ? 'wrote' : 'would write'} ${entries.length} case(s)`,
    );

    if (options.prune) {
      const suiteDir = join(casesRoot, suiteKey);
      const existing = existsSync(suiteDir)
        ? readdirSync(suiteDir).filter((name) => name.endsWith('.md'))
        : [];
      const stale = existing.filter((name) => !generated.has(name));

      for (const name of stale) {
        if (options.write) {
          unlinkSync(join(suiteDir, name));
        }
      }

      if (stale.length > 0) {
        console.log(
          `${suiteKey}: ${options.write ? 'deleted' : 'would delete'} ${stale.length} unbacked case(s): ${stale
            .map((name) => basename(name, '.md'))
            .join(', ')}`,
        );
      }
    }
  }

  if (!options.write) {
    console.log('\nDry run. Re-run with --write to apply.');
  }

  return 0;
}

main()
  .then((code) => process.exit(code))
  .catch((error) => {
    console.error(`generate-cases-from-tests: ${error.message}`);
    process.exit(2);
  });
