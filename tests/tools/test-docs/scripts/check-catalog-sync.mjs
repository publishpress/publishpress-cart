#!/usr/bin/env node
/**
 * Check that the test case catalog and the test code agree.
 *
 * Test files are the source of truth: every id a suite declares in code must
 * have a catalog case, and every case must point back at code that exists.
 *
 * Usage:
 *   node tests/tools/test-docs/scripts/check-catalog-sync.mjs [options]
 *
 *   --suite <key>   Restrict to one suite (repeatable)
 *   --json          Emit machine-readable JSON
 *   --strict        Treat warnings as failures
 *   --quiet         Only print the summary line
 */
import { existsSync, readFileSync } from 'node:fs';
import { join, relative } from 'node:path';

import { loadConfig } from '../lib/load-config.mjs';
import { listCases } from '../lib/parse-cases.mjs';
import { discoverSuiteTests, listTestFiles } from '../lib/discover-tests.mjs';

const SEVERITY = { error: 'error', warning: 'warning' };

/**
 * Parse argv into options.
 * @param {string[]} argv
 * @returns {{ suites: string[]; json: boolean; strict: boolean; quiet: boolean }}
 */
function parseArgs(argv) {
  const options = { suites: [], json: false, strict: false, quiet: false };

  for (let i = 0; i < argv.length; i += 1) {
    const arg = argv[i];

    if (arg === '--suite') {
      const value = argv[i + 1];
      if (!value) {
        throw new Error('--suite requires a suite key');
      }
      options.suites.push(value);
      i += 1;
    } else if (arg.startsWith('--suite=')) {
      options.suites.push(arg.slice('--suite='.length));
    } else if (arg === '--json') {
      options.json = true;
    } else if (arg === '--strict') {
      options.strict = true;
    } else if (arg === '--quiet') {
      options.quiet = true;
    } else if (arg === '--help' || arg === '-h') {
      options.help = true;
    } else {
      throw new Error(`Unknown argument: ${arg}`);
    }
  }

  return options;
}

/** Normalize a title for comparison: lowercase alphanumerics only. */
function normalizeTitle(value) {
  return String(value ?? '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, ' ')
    .trim();
}

/**
 * Collect findings for one suite.
 * @returns {{ findings: object[]; stats: object }}
 */
/** Does `id` fall inside a module's `test_range` (e.g. `RT-001–RT-009, RT-019`)? */
function idInTestRange(id, testRange) {
  const number = Number(id.split('-')[1]);

  return String(testRange)
    .split(',')
    .some((part) => {
      const match = part.trim().match(/^[A-Z]+-(\d+)(?:\s*[–—-]\s*[A-Z]+-(\d+))?$/);

      if (!match) {
        return false;
      }

      const low = Number(match[1]);
      const high = match[2] ? Number(match[2]) : low;

      return number >= low && number <= high;
    });
}

/**
 * Check a case's `module` and `features` against the config catalogs.
 * @returns {{ severity: string; code: string; message: string; detail?: string }[]}
 */
function checkTaxonomy(testCase, suiteKey, modules, features) {
  const findings = [];
  const { id, module: moduleId } = testCase;

  if (moduleId) {
    const module = modules.get(moduleId);

    if (!module) {
      findings.push({
        severity: SEVERITY.error,
        code: 'unknown_module',
        message: `${id} references module "${moduleId}" which is not declared`,
      });
    } else {
      if (!(module.suites ?? []).includes(suiteKey)) {
        findings.push({
          severity: SEVERITY.error,
          code: 'module_suite_mismatch',
          message: `${id} uses module "${moduleId}", which is scoped to ${JSON.stringify(module.suites ?? [])}`,
        });
      }

      if (module.test_range && !idInTestRange(id, module.test_range)) {
        findings.push({
          severity: SEVERITY.warning,
          code: 'out_of_module_range',
          message: `${id} sits outside the "${moduleId}" test_range (${module.test_range})`,
        });
      }
    }
  }

  for (const feature of testCase.features ?? []) {
    if (!features.has(feature)) {
      findings.push({
        severity: SEVERITY.error,
        code: 'unknown_feature',
        message: `${id} references feature "${feature}" which is not declared`,
      });
    }
  }

  return findings;
}

function checkSuite(repoRoot, suiteKey, suite, cases, ignored = [], modules, features) {
  const findings = [];
  const add = (severity, code, id, message, detail) =>
    findings.push({ suite: suiteKey, severity, code, id, message, detail });

  const { files, byId } = discoverSuiteTests(repoRoot, suiteKey, suite);
  const caseById = new Map(cases.map((testCase) => [testCase.id, testCase]));

  // Test code without a catalog case.
  for (const [id, occurrences] of [...byId].sort()) {
    if (occurrences.length > 1) {
      add(
        SEVERITY.error,
        'duplicate_id',
        id,
        `${id} is declared by ${occurrences.length} test files`,
        occurrences.map((entry) => entry.rel).join(', '),
      );
    }

    if (!caseById.has(id)) {
      add(
        SEVERITY.error,
        'missing_case',
        id,
        `${id} exists in code but has no catalog case`,
        occurrences[0].rel,
      );
    }
  }

  // Spec files under the suite roots that declare no id at all.
  for (const file of files) {
    if (file.ids.length === 0 && !ignored.includes(file.rel)) {
      add(
        SEVERITY.error,
        'unidentified_test',
        '',
        `${file.rel} declares no ${suite.prefix}-NNN id`,
        file.rel,
      );
    }
  }

  // Catalog cases checked back against code.
  for (const testCase of cases) {
    const { id } = testCase;
    const prefix = suite.prefix ? `${suite.prefix}-` : '';

    for (const finding of checkTaxonomy(testCase, suiteKey, modules, features)) {
      add(finding.severity, finding.code, id, finding.message, finding.detail);
    }

    if (prefix && !id.startsWith(prefix)) {
      add(
        SEVERITY.error,
        'prefix_mismatch',
        id,
        `${id} does not use the "${suite.prefix}" prefix of suite "${suiteKey}"`,
        testCase.filePath,
      );
    }

    if (testCase.suite !== suiteKey) {
      add(
        SEVERITY.error,
        'suite_mismatch',
        id,
        `${id} sits in ${suiteKey}/ but declares suite: ${testCase.suite}`,
        testCase.filePath,
      );
    }

    const automation = testCase.automation ?? {};
    const implementations = Array.isArray(automation.implementations)
      ? automation.implementations
      : [];
    const inCode = byId.get(id);
    let linkIsBroken = false;

    for (const impl of implementations) {
      const file = impl?.file;

      if (!file) {
        add(SEVERITY.error, 'broken_link', id, `${id} has an implementation without a file`);
        linkIsBroken = true;
        continue;
      }

      if (!existsSync(join(repoRoot, file))) {
        add(SEVERITY.error, 'broken_link', id, `${id} links a file that no longer exists`, file);
        linkIsBroken = true;
        continue;
      }

      const content = readFileSync(join(repoRoot, file), 'utf8');
      const method = impl.method;

      if (method && !content.includes(method)) {
        add(
          SEVERITY.error,
          'stale_link',
          id,
          `${id} links ${method}() which is not in the file`,
          file,
        );
        linkIsBroken = true;
      } else if (!method && !content.includes(id)) {
        add(SEVERITY.error, 'stale_link', id, `${id} is not referenced by the linked file`, file);
        linkIsBroken = true;
      }
    }

    if (!inCode) {
      if (implementations.length === 0 && automation.status === 'implemented') {
        add(
          SEVERITY.error,
          'phantom_link',
          id,
          `${id} is marked implemented but links nothing and no test declares that id`,
          relative(repoRoot, testCase.filePath),
        );
      } else if (implementations.length === 0) {
        add(
          SEVERITY.warning,
          'orphan_case',
          id,
          `${id} has no test file (documented but not automated)`,
          relative(repoRoot, testCase.filePath),
        );
      }
      continue;
    }

    if (implementations.length === 0) {
      add(
        SEVERITY.error,
        'unlinked_case',
        id,
        `${id} is automated in ${inCode[0].rel} but automation.implementations is empty`,
        inCode.map((entry) => entry.rel).join(', '),
      );
    }

    if (!linkIsBroken && implementations.length > 0 && automation.status && automation.status !== 'implemented') {
      add(
        SEVERITY.warning,
        'status_drift',
        id,
        `${id} links code but automation.status is "${automation.status ?? 'unset'}"`,
        testCase.filePath,
      );
    }

    const codeTitle = inCode.find((entry) => entry.title)?.title;
    if (codeTitle && normalizeTitle(codeTitle) !== normalizeTitle(testCase.title)) {
      add(
        SEVERITY.warning,
        'title_drift',
        id,
        `${id} title differs from the test`,
        `case: "${testCase.title}" | test: "${codeTitle}"`,
      );
    }
  }

  return {
    findings,
    stats: {
      cases: cases.length,
      testFiles: files.length,
      idsInCode: byId.size,
      linked: cases.filter(
        (testCase) =>
          Array.isArray(testCase.automation?.implementations) &&
          testCase.automation.implementations.length > 0,
      ).length,
    },
  };
}

/**
 * Flag test files that live under a scanned tree but no suite claims.
 */
function checkUnclaimedFiles(repoRoot, suites, ignored = []) {
  const findings = [];
  const claimed = new Set(ignored);
  const trees = new Set();

  for (const suite of Object.values(suites)) {
    const glob = suite.runner === 'codeception' ? suite.file_glob : suite.spec_glob;

    for (const root of suite.roots ?? []) {
      for (const file of listTestFiles(repoRoot, root, glob ?? '**/*')) {
        claimed.add(file);
      }
      trees.add(root.split('/').slice(0, 2).join('/'));
    }
  }

  for (const tree of trees) {
    for (const file of listTestFiles(repoRoot, tree, '**/*.spec.ts')) {
      if (!claimed.has(file)) {
        findings.push({
          suite: '-',
          severity: SEVERITY.warning,
          code: 'unclaimed_test',
          id: '',
          message: `${file} is not covered by any suite root`,
          detail: file,
        });
      }
    }
  }

  return findings;
}

function printReport(report, options) {
  const { findings, suites, untestedFeatures } = report;

  if (!options.quiet) {
    console.log('\nTest catalog sync\n');

    const rows = Object.entries(suites).map(([key, stats]) => ({
      suite: key,
      cases: stats.cases,
      files: stats.testFiles,
      'ids in code': stats.idsInCode,
      linked: `${stats.linked}/${stats.cases}`,
      errors: stats.errors,
      warnings: stats.warnings,
    }));

    console.table(rows);

    const grouped = new Map();
    for (const finding of findings) {
      const key = `${finding.suite}:${finding.code}`;
      grouped.set(key, [...(grouped.get(key) ?? []), finding]);
    }

    for (const [key, group] of [...grouped].sort()) {
      const [suite, code] = key.split(':');
      const severity = group[0].severity.toUpperCase();
      console.log(`\n${severity}  ${suite} · ${code} (${group.length})`);

      for (const finding of group.slice(0, 15)) {
        console.log(`  - ${finding.message}${finding.detail ? `\n      ${finding.detail}` : ''}`);
      }

      if (group.length > 15) {
        console.log(`  … and ${group.length - 15} more`);
      }
    }

    if (untestedFeatures?.length > 0) {
      console.log(
        `\nNOTE  ${untestedFeatures.length} declared feature(s) have no case in any suite:`,
      );
      console.log(`  ${untestedFeatures.join(', ')}`);
      console.log('  Not a sync error — these are sellable features with no test coverage.');
    }
  }

  const errors = findings.filter((finding) => finding.severity === SEVERITY.error).length;
  const warnings = findings.length - errors;

  console.log(
    `\n${errors === 0 ? 'PASS' : 'FAIL'} — ${errors} error(s), ${warnings} warning(s)\n`,
  );
}

async function main() {
  const options = parseArgs(process.argv.slice(2));

  if (options.help) {
    console.log(readFileSync(new URL(import.meta.url), 'utf8').split('*/')[0]);
    return 0;
  }

  const { config, repoRoot } = await loadConfig();
  const casesRoot = join(repoRoot, config.catalog?.cases ?? 'docs/testing/cases');
  const allSuites = config.suites ?? {};

  const selected = options.suites.length > 0 ? options.suites : Object.keys(allSuites);

  for (const key of selected) {
    if (!allSuites[key]) {
      throw new Error(`Unknown suite "${key}". Known suites: ${Object.keys(allSuites).join(', ')}`);
    }
  }

  const findings = [];
  const suiteStats = {};
  const ignored = config.catalog?.ignore_tests ?? [];
  const modules = new Map((config.modules ?? []).map((module) => [module.id, module]));
  const features = new Set(Object.keys(config.features ?? {}));
  const featureUse = new Map([...features].map((feature) => [feature, 0]));

  for (const key of selected) {
    const suite = allSuites[key];
    const cases = listCases(casesRoot, { suite: key });

    for (const testCase of cases) {
      for (const feature of testCase.features ?? []) {
        featureUse.set(feature, (featureUse.get(feature) ?? 0) + 1);
      }
    }

    const result = checkSuite(repoRoot, key, suite, cases, ignored, modules, features);

    findings.push(...result.findings);
    suiteStats[key] = {
      ...result.stats,
      errors: result.findings.filter((finding) => finding.severity === SEVERITY.error).length,
      warnings: result.findings.filter((finding) => finding.severity === SEVERITY.warning).length,
    };
  }

  if (options.suites.length === 0) {
    findings.push(...checkUnclaimedFiles(repoRoot, allSuites, ignored));
  }

  const untestedFeatures =
    options.suites.length === 0
      ? [...featureUse].filter(([, count]) => count === 0).map(([feature]) => feature)
      : [];

  const report = { findings, suites: suiteStats, untestedFeatures };

  if (options.json) {
    console.log(JSON.stringify(report, null, 2));
  } else {
    printReport(report, options);
  }

  const errors = findings.filter((finding) => finding.severity === SEVERITY.error).length;
  const warnings = findings.length - errors;

  return errors > 0 || (options.strict && warnings > 0) ? 1 : 0;
}

main()
  .then((code) => process.exit(code))
  .catch((error) => {
    console.error(`check-catalog-sync: ${error.message}`);
    process.exit(2);
  });
