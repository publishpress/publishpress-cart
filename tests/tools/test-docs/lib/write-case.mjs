import { mkdirSync, unlinkSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { stringify as stringifyYaml } from 'yaml';

/**
 * Build markdown body from case sections.
 * @param {{
 *   title?: string;
 *   scenario?: string;
 *   steps?: string;
 *   expected?: string;
 *   edge_cases?: string;
 *   body?: string;
 * }} testCase
 * @returns {string}
 */
function buildBody(testCase) {
  if (testCase.body && !testCase.scenario && !testCase.steps && !testCase.expected) {
    return testCase.body.trim();
  }

  const parts = [];

  if (testCase.title) {
    parts.push(`# ${testCase.title}`, '');
  }

  if (testCase.scenario) {
    parts.push('## Scenario', '', testCase.scenario.trim(), '');
  }

  if (testCase.steps) {
    parts.push('## Steps', '', testCase.steps.trim(), '');
  }

  if (testCase.expected) {
    parts.push('## Expected result', '', testCase.expected.trim(), '');
  }

  if (testCase.edge_cases) {
    parts.push('## Edge cases', '', testCase.edge_cases.trim(), '');
  }

  return parts.join('\n').trimEnd() + '\n';
}

/**
 * Build YAML frontmatter object from a case.
 * @param {Record<string, unknown>} testCase
 * @returns {Record<string, unknown>}
 */
function buildFrontmatter(testCase) {
  /** @type {Record<string, unknown>} */
  const fm = {};

  const assign = (key) => {
    const value = testCase[key];
    if (value !== undefined && value !== '') {
      fm[key] = value;
    }
  };

  for (const key of ['suite', 'edition', 'module']) {
    assign(key);
  }

  fm.features = Array.isArray(testCase.features)
    ? testCase.features.filter((feature) => typeof feature === 'string')
    : [];

  for (const key of ['priority', 'title', 'notes']) {
    assign(key);
  }

  if (Array.isArray(testCase.tags) && testCase.tags.length > 0) {
    fm.tags = testCase.tags;
  }

  if (
    testCase.extensions &&
    typeof testCase.extensions === 'object' &&
    Object.keys(testCase.extensions).length > 0
  ) {
    fm.extensions = testCase.extensions;
  }

  if (testCase.automation && typeof testCase.automation === 'object') {
    const automation = { ...testCase.automation };
    delete automation.status;
    fm.automation = automation;
  }

  if (Array.isArray(testCase.refs) && testCase.refs.length > 0) {
    fm.refs = testCase.refs;
  }

  return fm;
}

/**
 * Serialize a case object to markdown with YAML frontmatter.
 * @param {Record<string, unknown>} testCase
 * @returns {string}
 */
export function serializeCase(testCase) {
  const frontmatter = buildFrontmatter(testCase);
  const yaml = stringifyYaml(frontmatter, { lineWidth: 0 }).trim();
  const body = buildBody(testCase);

  return `---\n${yaml}\n---\n\n${body}`;
}

/**
 * Resolve output path for a case file.
 * @param {string} casesRoot
 * @param {string} suite
 * @param {string} id
 * @returns {string}
 */
export function caseFilePath(casesRoot, suite, id) {
  return join(casesRoot, suite, `${id}.md`);
}

/**
 * Write a case to disk under catalog.cases/<suite>/<id>.md
 * @param {string} casesRoot
 * @param {Record<string, unknown>} testCase
 * @returns {string} Written file path
 */
export function writeCase(casesRoot, testCase) {
  const id = /** @type {string} */ (testCase.id);
  const suite = /** @type {string} */ (testCase.suite);

  if (!id || !suite) {
    throw new Error('Case must include id and suite');
  }

  const filePath = caseFilePath(casesRoot, suite, id);
  mkdirSync(dirname(filePath), { recursive: true });
  writeFileSync(filePath, serializeCase(testCase), 'utf8');

  return filePath;
}

/**
 * Delete a case markdown file from disk.
 * @param {string} casesRoot
 * @param {string} suite
 * @param {string} id
 * @returns {string} Deleted file path
 */
export function deleteCase(casesRoot, suite, id) {
  if (!id || !suite) {
    throw new Error('Case must include id and suite');
  }

  const filePath = caseFilePath(casesRoot, suite, id);

  try {
    unlinkSync(filePath);
  } catch (err) {
    const code = /** @type {NodeJS.ErrnoException} */ (err).code;
    if (code === 'ENOENT') {
      throw new Error(`Case not found: ${id}`);
    }
    throw err;
  }

  return filePath;
}
