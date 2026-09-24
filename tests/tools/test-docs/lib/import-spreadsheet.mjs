import { existsSync, readdirSync, readFileSync, unlinkSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { parse as parseYaml } from 'yaml';
import { loadConfig } from './load-config.mjs';
import { parseCaseFile } from './parse-cases.mjs';
import { writeCase } from './write-case.mjs';

const __dirname = dirname(fileURLToPath(import.meta.url));
const TOOL_ROOT = join(__dirname, '..');

/**
 * @param {string} html
 * @returns {string}
 */
function stripHtml(html) {
  return html
    .replace(/<br\s*\/?>/gi, '\n')
    .replace(/<[^>]+>/g, '')
    .replace(/&nbsp;/g, ' ')
    .replace(/&amp;/g, '&')
    .replace(/&lt;/g, '<')
    .replace(/&gt;/g, '>')
    .replace(/\r\n/g, '\n')
    .replace(/[ \t]+\n/g, '\n')
    .replace(/\n{3,}/g, '\n\n')
    .trim();
}

/**
 * @param {string} text
 * @returns {string}
 */
function normalizeNumberedList(text) {
  if (!text) {
    return '';
  }
  return text
    .replace(/(\d+)\.\s*/g, '\n$1. ')
    .replace(/^\n+/, '')
    .trim();
}

/**
 * @param {string} html
 * @returns {string[][]}
 */
export function parseSheetTableRows(html) {
  const rows = [];
  const matches = html.matchAll(/<tr[^>]*>([\s\S]*?)<\/tr>/gi);

  for (const match of matches) {
    const cells = [...match[1].matchAll(/<t[hd][^>]*>([\s\S]*?)<\/t[hd]>/gi)].map(
      (cell) => stripHtml(cell[1]),
    );
    if (cells.length > 0) {
      rows.push(cells);
    }
  }

  return rows;
}

/**
 * @param {string[][]} rows
 * @param {string} idColumn
 * @returns {Array<Record<string, string>>}
 */
export function rowsToRecords(rows, idColumn) {
  if (rows.length < 2) {
    return [];
  }

  const headerRow = rows.find((row) => row.includes(idColumn));
  if (!headerRow) {
    throw new Error(`Header row with "${idColumn}" not found`);
  }

  const headerIndex = headerRow.indexOf(idColumn);
  const headers = headerRow.slice(headerIndex);

  const records = [];
  for (const row of rows) {
    const id = row[headerIndex];
    if (!id || !/^TC-\d{3}$/i.test(id)) {
      continue;
    }

    /** @type {Record<string, string>} */
    const record = {};
    headers.forEach((header, offset) => {
      record[header] = row[headerIndex + offset] ?? '';
    });
    records.push(record);
  }

  return records;
}

/**
 * @param {string} scenario
 * @param {string} steps
 * @returns {string | undefined}
 */
function inferGateway(scenario, steps) {
  const haystack = `${scenario}\n${steps}`.toLowerCase();
  if (/\bstripe\b/.test(haystack)) {
    return 'stripe';
  }
  if (/\bpaypal\b/.test(haystack)) {
    return 'paypal';
  }
  if (/\bcod\b|cash on delivery\b/.test(haystack)) {
    return 'cod';
  }
  return undefined;
}

/**
 * @param {string} text
 * @returns {string}
 */
function slugify(text) {
  return text
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');
}

/**
 * @param {string} text
 * @returns {string[]}
 */
function inferTags(text) {
  const tags = new Set();
  const haystack = text.toLowerCase();
  if (haystack.includes('stripe')) {
    tags.add('stripe');
  }
  if (haystack.includes('paypal')) {
    tags.add('paypal');
  }
  if (haystack.includes('subscription')) {
    tags.add('subscription');
  }
  if (haystack.includes('coupon')) {
    tags.add('coupon');
  }
  if (haystack.includes('free')) {
    tags.add('free');
  }
  return [...tags];
}

/**
 * @param {number} tcNumber
 * @param {Array<{ from: number; to: number; module: string }>} ranges
 * @returns {string | undefined}
 */
function moduleForTcNumber(tcNumber, ranges) {
  for (const range of ranges) {
    if (tcNumber >= range.from && tcNumber <= range.to) {
      return range.module;
    }
  }
  return undefined;
}

/**
 * @param {Record<string, string>} record
 * @param {Record<string, unknown>} profile
 * @returns {Record<string, unknown>}
 */
export function recordToRtCase(record, profile) {
  const tcId = record[profile.id_column || 'Test ID'].toUpperCase();
  const tcNumber = Number(tcId.replace(/^TC-/, ''));
  const catalogPrefix = /** @type {string} */ (profile.catalog_id_prefix ?? 'RT');
  const id = `${catalogPrefix}-${String(tcNumber).padStart(3, '0')}`;

  const priorityMap = /** @type {Record<string, string>} */ (profile.priority_map ?? {});
  const purchaseMap = /** @type {Record<string, string>} */ (profile.purchase_type_map ?? {});
  const taxMap = /** @type {Record<string, string>} */ (profile.tax_context_map ?? {});
  const editionMap = /** @type {Record<string, string>} */ (profile.edition_map ?? {});
  const moduleRanges = /** @type {Array<{ from: number; to: number; module: string }>} */ (
    profile.module_ranges ?? []
  );

  const scenario = record.Scenario?.trim() ?? '';
  const steps = normalizeNumberedList(record['Test Steps'] ?? '');
  const expected = normalizeNumberedList(record['Expected Result'] ?? '');
  const edgeCases = normalizeNumberedList(record['Edge Cases'] ?? '');
  const purchaseRaw = record['Purchase Type']?.trim() ?? '';
  const taxRaw = record['Tax Context']?.trim() ?? '';
  const priorityRaw = record.Priority?.trim() ?? 'medium';
  const packageRaw = record.Package?.trim() ?? '';

  /** @type {Record<string, string>} */
  const extensions = {};
  const purchaseType = purchaseMap[purchaseRaw];
  if (purchaseType) {
    extensions.purchase_type = purchaseType;
  }
  const taxContext = taxMap[taxRaw];
  if (taxContext) {
    extensions.tax_context = taxContext;
  }
  const gateway = inferGateway(scenario, steps);
  if (gateway) {
    extensions.gateway = gateway;
  }

  const tags = purchaseRaw ? [slugify(purchaseRaw)] : [];
  const edition = editionMap[packageRaw] ?? 'both';
  if (edition === 'pro' && !tags.includes('pro')) {
    tags.push('pro');
  }

  return {
    id,
    suite: profile.suite || 'regression',
    edition,
    module: moduleForTcNumber(tcNumber, moduleRanges),
    priority: priorityMap[priorityRaw] || priorityRaw.toLowerCase(),
    title: scenario || id,
    tags,
    extensions: Object.keys(extensions).length > 0 ? extensions : {},
    scenario,
    steps,
    expected,
    edge_cases: edgeCases,
    automation: {
      status: 'none',
      implementations: [],
    },
  };
}

/**
 * @param {Record<string, string>} record
 * @param {Record<string, unknown>} profile
 * @returns {Record<string, unknown>}
 */
export function recordToCase(record, profile) {
  const id = record[profile.id_column || 'Test ID'].toUpperCase();
  const tcNumber = Number(id.replace(/^TC-/, ''));
  const priorityMap = /** @type {Record<string, string>} */ (profile.priority_map ?? {});
  const purchaseMap = /** @type {Record<string, string>} */ (profile.purchase_type_map ?? {});
  const taxMap = /** @type {Record<string, string>} */ (profile.tax_context_map ?? {});
  const moduleRanges = /** @type {Array<{ from: number; to: number; module: string }>} */ (
    profile.module_ranges ?? []
  );

  const scenario = record.Scenario?.trim() ?? '';
  const steps = normalizeNumberedList(record['Test Steps'] ?? '');
  const expected = normalizeNumberedList(record['Expected Result'] ?? '');
  const edgeCases = normalizeNumberedList(record['Edge Cases'] ?? '');
  const purchaseRaw = record['Purchase Type']?.trim() ?? '';
  const taxRaw = record['Tax Context']?.trim() ?? '';
  const priorityRaw = record.Priority?.trim() ?? 'medium';

  /** @type {Record<string, string>} */
  const extensions = {};
  const purchaseType = purchaseMap[purchaseRaw];
  if (purchaseType) {
    extensions.purchase_type = purchaseType;
  }
  const taxContext = taxMap[taxRaw];
  if (taxContext) {
    extensions.tax_context = taxContext;
  }
  const gateway = inferGateway(scenario, steps);
  if (gateway) {
    extensions.gateway = gateway;
  }

  const tags = inferTags(`${scenario}\n${purchaseRaw}\n${steps}`);

  return {
    id,
    suite: profile.suite || 'regression',
    edition: 'both',
    module: moduleForTcNumber(tcNumber, moduleRanges),
    priority: priorityMap[priorityRaw] || priorityRaw.toLowerCase(),
    title: scenario || id,
    tags,
    extensions: Object.keys(extensions).length > 0 ? extensions : {},
    scenario,
    steps,
    expected,
    edge_cases: edgeCases,
    automation: {
      status: 'none',
      implementations: [],
    },
    notes: record.Status ? `Spreadsheet status: ${record.Status}` : undefined,
  };
}

/**
 * @param {{
 *   profile?: string;
 *   sourceDir?: string;
 *   dryRun?: boolean;
 *   skipExisting?: boolean;
 * }} [options]
 */
export async function importSpreadsheet(options = {}) {
  const profileName = options.profile ?? 'publishpress-cart';
  const profileDir = join(TOOL_ROOT, 'profiles', profileName);
  const mapPath = join(profileDir, 'column-map.yaml');

  if (!existsSync(mapPath)) {
    throw new Error(`Profile not found: ${mapPath}`);
  }

  const profile = parseYaml(readFileSync(mapPath, 'utf8'));
  const sourceDir = options.sourceDir ?? join(profileDir, 'source');
  const sheetPath = join(sourceDir, profile.sheet);

  if (!existsSync(sheetPath)) {
    throw new Error(
      `Spreadsheet HTML not found: ${sheetPath}\n` +
        `Copy exports to ${sourceDir} (see profiles/${profileName}/README.md).`,
    );
  }

  const rt = await loadConfig();
  const catalog = /** @type {{ cases?: string }} */ (rt.config.catalog ?? {});
  const casesRoot = join(rt.repoRoot, catalog.cases ?? 'docs/testing/cases');
  const html = readFileSync(sheetPath, 'utf8');
  const records = rowsToRecords(parseSheetTableRows(html), profile.id_column);

  let written = 0;
  let skipped = 0;
  const paths = [];

  for (const record of records) {
    const testCase = recordToCase(record, profile);
    const filePath = join(casesRoot, testCase.suite, `${testCase.id}.md`);

    if (options.skipExisting !== false && existsSync(filePath)) {
      skipped += 1;
      continue;
    }

    if (!options.dryRun) {
      writeCase(casesRoot, testCase);
    }

    written += 1;
    paths.push(filePath);
  }

  return {
    profile: profileName,
    sheet: sheetPath,
    casesRoot,
    total: records.length,
    written,
    skipped,
    dryRun: Boolean(options.dryRun),
    paths,
  };
}

/**
 * Merge spreadsheet edge cases into existing RT-* cases when TC numbers align
 * with Playwright legacy numbering (RT-NNN), preserving automation blocks.
 *
 * @param {{ dryRun?: boolean }} [options]
 */
export async function mergeSpreadsheetIntoRtCases(options = {}) {
  const rt = await loadConfig();
  const catalog = /** @type {{ cases?: string }} */ (rt.config.catalog ?? {});
  const casesRoot = join(rt.repoRoot, catalog.cases ?? 'docs/testing/cases');
  const profileDir = join(TOOL_ROOT, 'profiles', 'publishpress-cart');
  const profile = parseYaml(readFileSync(join(profileDir, 'column-map.yaml'), 'utf8'));
  const sheetPath = join(profileDir, 'source', profile.sheet);
  const html = readFileSync(sheetPath, 'utf8');
  const records = rowsToRecords(parseSheetTableRows(html), profile.id_column);

  /** @type {Record<string, Record<string, string>>} */
  const byTc = {};
  for (const record of records) {
    byTc[record['Test ID'].toUpperCase()] = record;
  }

  let updated = 0;
  for (let n = 1; n <= 19; n += 1) {
    const tcId = `TC-${String(n).padStart(3, '0')}`;
    const rtId = `RT-${String(n).padStart(3, '0')}`;
    const record = byTc[tcId];
    const rtPath = join(casesRoot, 'regression', `${rtId}.md`);

    if (!record || !existsSync(rtPath)) {
      continue;
    }

    const existing = parseCaseFile(rtPath);
    const edgeCases = normalizeNumberedList(record['Edge Cases'] ?? '');
    if (!edgeCases) {
      continue;
    }

    const merged = {
      ...existing,
      edge_cases: edgeCases,
    };

    if (!options.dryRun) {
      writeCase(casesRoot, merged);
    }
    updated += 1;
  }

  return { updated, dryRun: Boolean(options.dryRun) };
}

/**
 * @param {string} casesRoot
 * @param {string} suite
 * @returns {string[]}
 */
function deleteSuiteCasesMatching(casesRoot, suite, pattern) {
  const dir = join(casesRoot, suite);
  if (!existsSync(dir)) {
    return [];
  }

  const deleted = [];
  for (const name of readdirSync(dir)) {
    if (pattern.test(name)) {
      unlinkSync(join(dir, name));
      deleted.push(name);
    }
  }

  return deleted;
}

/**
 * Replace all RT-* regression catalog cases from Master Test Suite HTML.
 * Maps TC-NNN spreadsheet rows to RT-NNN catalog IDs (same number).
 * Does not merge with or preserve existing case content.
 *
 * @param {{
 *   profile?: string;
 *   sourceDir?: string;
 *   dryRun?: boolean;
 *   replace?: boolean;
 * }} [options]
 */
export async function importRtCasesFromSpreadsheet(options = {}) {
  const profileName = options.profile ?? 'publishpress-cart';
  const profileDir = join(TOOL_ROOT, 'profiles', profileName);
  const mapPath = join(profileDir, 'column-map.yaml');

  if (!existsSync(mapPath)) {
    throw new Error(`Profile not found: ${mapPath}`);
  }

  const profile = parseYaml(readFileSync(mapPath, 'utf8'));
  const sourceDir = options.sourceDir ?? join(profileDir, 'source');
  const sheetPath = join(sourceDir, profile.sheet);

  if (!existsSync(sheetPath)) {
    throw new Error(
      `Spreadsheet HTML not found: ${sheetPath}\n` +
        `Copy exports to ${sourceDir} (see profiles/${profileName}/README.md).`,
    );
  }

  const rt = await loadConfig();
  const catalog = /** @type {{ cases?: string }} */ (rt.config.catalog ?? {});
  const casesRoot = join(rt.repoRoot, catalog.cases ?? 'docs/testing/cases');
  const suite = profile.suite || 'regression';
  const html = readFileSync(sheetPath, 'utf8');
  const records = rowsToRecords(parseSheetTableRows(html), profile.id_column);

  let deleted = [];
  if (options.replace !== false) {
    if (!options.dryRun) {
      deleted = deleteSuiteCasesMatching(casesRoot, suite, /^RT-\d{3}\.md$/);
    }
  }

  let written = 0;
  const paths = [];

  for (const record of records) {
    const testCase = recordToRtCase(record, profile);
    const filePath = join(casesRoot, suite, `${testCase.id}.md`);

    if (!options.dryRun) {
      writeCase(casesRoot, testCase);
    }

    written += 1;
    paths.push(filePath);
  }

  return {
    profile: profileName,
    sheet: sheetPath,
    casesRoot,
    suite,
    total: records.length,
    deleted: options.replace !== false ? (options.dryRun ? 'all RT-*.md (dry run)' : deleted.length) : 0,
    written,
    dryRun: Boolean(options.dryRun),
    paths,
  };
}
