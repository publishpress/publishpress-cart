import { readdirSync, readFileSync, statSync } from 'node:fs';
import { join, basename } from 'node:path';
import matter from 'gray-matter';

const SECTION_MAP = {
  scenario: /^##\s+Scenario\s*$/im,
  steps: /^##\s+Steps\s*$/im,
  expected: /^##\s+Expected result\s*$/im,
  edge_cases: /^##\s+Edge cases\s*$/im,
};

/**
 * Split markdown body into named sections after ## headings.
 * @param {string} body
 * @returns {Record<string, string>}
 */
function parseBodySections(body) {
  const sections = {
    scenario: '',
    steps: '',
    expected: '',
    edge_cases: '',
  };

  const lines = body.replace(/\r\n/g, '\n').split('\n');
  let currentKey = null;
  const buffers = /** @type {Record<string, string[]>} */ ({
    scenario: [],
    steps: [],
    expected: [],
    edge_cases: [],
  });

  for (const line of lines) {
    let matched = false;

    for (const [key, pattern] of Object.entries(SECTION_MAP)) {
      if (pattern.test(line)) {
        currentKey = key;
        matched = true;
        break;
      }
    }

    if (matched) {
      continue;
    }

    if (currentKey) {
      buffers[currentKey].push(line);
    }
  }

  for (const key of Object.keys(sections)) {
    sections[key] = buffers[key].join('\n').trim();
  }

  return sections;
}

/**
 * Extract title from first # heading or frontmatter title.
 * @param {string} body
 * @param {Record<string, unknown>} data
 * @returns {string}
 */
function extractTitle(body, data) {
  if (typeof data.title === 'string' && data.title.trim()) {
    return data.title.trim();
  }

  const match = body.match(/^#\s+(.+)$/m);
  return match ? match[1].trim() : '';
}

/**
 * Parse a single case markdown file into a case object.
 * @param {string} filePath
 * @param {string} [content]
 * @returns {{
 *   id: string;
 *   suite: string;
 *   filePath: string;
 *   title: string;
 *   edition?: string;
 *   module?: string;
 *   features: string[];
 *   priority?: string;
 *   tags?: string[];
 *   extensions?: Record<string, unknown>;
 *   automation?: Record<string, unknown>;
 *   refs?: unknown[];
 *   notes?: string;
 *   scenario: string;
 *   steps: string;
 *   expected: string;
 *   edge_cases: string;
 *   body: string;
 *   frontmatter: Record<string, unknown>;
 * }}
 */
export function parseCaseFile(filePath, content) {
  const raw = content ?? readFileSync(filePath, 'utf8');
  const { data, content: body } = matter(raw);
  const sections = parseBodySections(body);
  const frontmatter = /** @type {Record<string, unknown>} */ ({ ...data });

  const id =
    typeof frontmatter.id === 'string'
      ? frontmatter.id
      : basename(filePath, '.md');

  const suiteDir = basename(join(filePath, '..'));
  const suite =
    typeof frontmatter.suite === 'string' ? frontmatter.suite : suiteDir;

  const title = extractTitle(body, frontmatter);

  return {
    id,
    suite,
    filePath,
    title,
    edition: typeof frontmatter.edition === 'string' ? frontmatter.edition : undefined,
    module: typeof frontmatter.module === 'string' ? frontmatter.module : undefined,
    features: Array.isArray(frontmatter.features)
      ? frontmatter.features.filter((feature) => typeof feature === 'string')
      : [],
    priority: typeof frontmatter.priority === 'string' ? frontmatter.priority : undefined,
    tags: Array.isArray(frontmatter.tags) ? frontmatter.tags : undefined,
    extensions:
      frontmatter.extensions && typeof frontmatter.extensions === 'object'
        ? /** @type {Record<string, unknown>} */ (frontmatter.extensions)
        : undefined,
    automation:
      frontmatter.automation && typeof frontmatter.automation === 'object'
        ? /** @type {Record<string, unknown>} */ (frontmatter.automation)
        : undefined,
    refs: Array.isArray(frontmatter.refs) ? frontmatter.refs : undefined,
    notes: typeof frontmatter.notes === 'string' ? frontmatter.notes : undefined,
    scenario: sections.scenario,
    steps: sections.steps,
    expected: sections.expected,
    edge_cases: sections.edge_cases,
    body: body.trim(),
    frontmatter,
  };
}

/**
 * List case file paths under a cases root, optionally filtered by suite.
 * @param {string} casesRoot
 * @param {{ suite?: string }} [options]
 * @returns {string[]}
 */
export function listCaseFiles(casesRoot, options = {}) {
  const files = [];

  if (!statSync(casesRoot, { throwIfNoEntry: false })?.isDirectory()) {
    return files;
  }

  const suites = options.suite
    ? [options.suite]
    : readdirSync(casesRoot).filter((entry) => {
        try {
          return statSync(join(casesRoot, entry)).isDirectory();
        } catch {
          return false;
        }
      });

  for (const suite of suites) {
    const suiteDir = join(casesRoot, suite);
    if (!statSync(suiteDir, { throwIfNoEntry: false })?.isDirectory()) {
      continue;
    }

    for (const entry of readdirSync(suiteDir)) {
      if (entry.endsWith('.md')) {
        files.push(join(suiteDir, entry));
      }
    }
  }

  return files.sort();
}

/**
 * List parsed cases from the catalog directory.
 * @param {string} casesRoot
 * @param {{ suite?: string }} [options]
 * @returns {ReturnType<typeof parseCaseFile>[]}
 */
export function listCases(casesRoot, options = {}) {
  return listCaseFiles(casesRoot, options).map((filePath) =>
    parseCaseFile(filePath),
  );
}

/**
 * Load all cases keyed by id.
 * @param {string} casesRoot
 * @param {{ suite?: string }} [options]
 * @returns {Record<string, ReturnType<typeof parseCaseFile>>}
 */
export function loadAllCases(casesRoot, options = {}) {
  const cases = listCases(casesRoot, options);
  /** @type {Record<string, ReturnType<typeof parseCaseFile>>} */
  const byId = {};

  for (const testCase of cases) {
    byId[testCase.id] = testCase;
  }

  return byId;
}
