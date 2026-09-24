import { readdirSync, readFileSync, statSync } from 'node:fs';
import { join, relative, sep } from 'node:path';

/**
 * Convert a simple glob (`**`, `*`) into an anchored regular expression.
 * @param {string} glob
 * @returns {RegExp}
 */
function globToRegExp(glob) {
  let out = '';

  for (let i = 0; i < glob.length; i += 1) {
    const char = glob[i];

    if (char === '*') {
      if (glob[i + 1] === '*') {
        out += '.*';
        i += 1;
        if (glob[i + 1] === '/') {
          i += 1;
        }
      } else {
        out += '[^/]*';
      }
      continue;
    }

    out += char.replace(/[.+^${}()|[\]\\?]/g, '\\$&');
  }

  return new RegExp(`^${out}$`);
}

/**
 * Recursively collect files under `dir` whose repo-relative path matches `glob`.
 * @param {string} dir
 * @param {string} baseDir
 * @param {RegExp} pattern
 * @param {string[]} [acc]
 * @returns {string[]}
 */
function walk(dir, baseDir, pattern, acc = []) {
  let entries;

  try {
    entries = readdirSync(dir, { withFileTypes: true });
  } catch {
    return acc;
  }

  for (const entry of entries) {
    const full = join(dir, entry.name);

    if (entry.isDirectory()) {
      if (entry.name === 'node_modules' || entry.name.startsWith('.')) {
        continue;
      }
      walk(full, baseDir, pattern, acc);
      continue;
    }

    const rel = relative(baseDir, full).split(sep).join('/');
    if (pattern.test(rel)) {
      acc.push(full);
    }
  }

  return acc;
}

/**
 * Build the id-matching regex for a suite, tolerating configs that
 * double-escape the pattern (`RT-\\d{3}`).
 * @param {{ id_pattern?: string; prefix?: string }} suite
 * @returns {RegExp}
 */
export function suiteIdRegExp(suite) {
  const source = suite.id_pattern
    ? suite.id_pattern.replace(/\\\\/g, '\\')
    : `${suite.prefix ?? '[A-Z]+'}-\\d{3}`;

  return new RegExp(source, 'g');
}

/**
 * Extract the Playwright test titles declared in a spec file.
 * @param {string} content
 * @returns {string[]}
 */
function playwrightTitles(content) {
  const titles = [];
  const pattern = /\btest(?:\.\w+)*\s*\(\s*(['"`])((?:\\.|(?!\1)[^\\])*)\1/g;
  let match;

  while ((match = pattern.exec(content)) !== null) {
    titles.push(match[2]);
  }

  return titles;
}

/**
 * Extract PHP test method names from a Codeception test file.
 * @param {string} content
 * @returns {string[]}
 */
function phpTestMethods(content) {
  const methods = [];
  const pattern = /function\s+(test\w+)\s*\(/g;
  let match;

  while ((match = pattern.exec(content)) !== null) {
    methods.push(match[1]);
  }

  return methods;
}

/**
 * Discover the test IDs a suite declares in its own code.
 *
 * Playwright suites carry the id in the test title and filename; Codeception
 * suites carry it in the test method name (`test_UT_001_...`).
 *
 * @param {string} repoRoot
 * @param {string} suiteKey
 * @param {Record<string, any>} suite
 * @returns {{
 *   files: { path: string; rel: string; ids: string[]; titles: string[]; methods: string[] }[];
 *   byId: Map<string, { rel: string; title: string; method?: string }[]>;
 * }}
 */
export function discoverSuiteTests(repoRoot, suiteKey, suite) {
  const glob = suite.runner === 'codeception' ? suite.file_glob : suite.spec_glob;
  const pattern = globToRegExp(glob ?? '**/*');
  const files = [];
  /** @type {Map<string, { rel: string; title: string; method?: string }[]>} */
  const byId = new Map();

  for (const root of suite.roots ?? []) {
    const absRoot = join(repoRoot, root);
    if (!statSync(absRoot, { throwIfNoEntry: false })?.isDirectory()) {
      continue;
    }

    for (const path of walk(absRoot, absRoot, pattern)) {
      const rel = relative(repoRoot, path).split(sep).join('/');
      const content = readFileSync(path, 'utf8');
      const idRe = suiteIdRegExp(suite);
      const ids = [...new Set(content.match(idRe) ?? [])];

      const titles = suite.runner === 'codeception' ? [] : playwrightTitles(content);
      const methods = suite.runner === 'codeception' ? phpTestMethods(content) : [];

      // Codeception embeds the id in the method name as `test_UT_001_...`.
      if (suite.runner === 'codeception') {
        for (const method of methods) {
          const normalized = method.replace(/_/g, '-').toUpperCase();
          const found = normalized.match(suiteIdRegExp(suite)) ?? [];
          for (const id of found) {
            if (!ids.includes(id)) {
              ids.push(id);
            }
          }
        }
      }

      files.push({ path, rel, ids, titles, methods });

      for (const id of ids) {
        const title =
          titles.find((candidate) => candidate.includes(id))?.replace(id, '').trim() ?? '';
        const method = methods.find((candidate) =>
          candidate.replace(/_/g, '-').toUpperCase().includes(id),
        );

        const entries = byId.get(id) ?? [];
        entries.push({ rel, title, method });
        byId.set(id, entries);
      }
    }
  }

  files.sort((a, b) => a.rel.localeCompare(b.rel));

  return { files, byId };
}

/**
 * List every spec/test file under a directory tree, regardless of suite.
 * @param {string} repoRoot
 * @param {string} dir
 * @param {string} glob
 * @returns {string[]} repo-relative paths
 */
export function listTestFiles(repoRoot, dir, glob) {
  const absRoot = join(repoRoot, dir);
  if (!statSync(absRoot, { throwIfNoEntry: false })?.isDirectory()) {
    return [];
  }

  return walk(absRoot, absRoot, globToRegExp(glob))
    .map((path) => relative(repoRoot, path).split(sep).join('/'))
    .sort();
}
