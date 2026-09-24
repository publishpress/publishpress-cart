import { readFileSync } from 'node:fs';
import { dirname, join, relative } from 'node:path';

const SKIP_IMPORTS = new Set(['RegressionConfig']);
const ORACLE_NAME = /^(assert|expect)/i;
const EXPECT_CALL = /\bexpect\s*\(/;

/**
 * @param {string} source
 * @returns {{ names: string[]; module: string }[]}
 */
export function localImports(source) {
  const blocks = [];
  const pattern =
    /import\s+(?:type\s+)?(?:(\w+)|{([^}]+)})\s+from\s+['"](\.\.?\/[^'"]+)['"]/g;
  let match;

  while ((match = pattern.exec(source)) !== null) {
    const module = match[3].replace(/\.ts$/, '');
    const names = match[1]
      ? [match[1]]
      : match[2]
          .split(',')
          .map((part) => part.trim().split(/\s+as\s+/).pop()?.trim())
          .filter(Boolean)
          .filter((name) => !name.startsWith('type '));

    blocks.push({ names: names.filter((name) => !SKIP_IMPORTS.has(name)), module });
  }

  return blocks;
}

/**
 * @param {string} source
 * @param {string} name
 * @returns {string | null}
 */
export function extractFunction(source, name) {
  const start = source.search(
    new RegExp(String.raw`(?:export\s+)?(?:async\s+)?function\s+${name}\s*\(`),
  );

  if (start < 0) {
    return null;
  }

  const brace = source.indexOf('{', start);

  if (brace < 0) {
    return null;
  }

  let depth = 0;

  for (let i = brace; i < source.length; i += 1) {
    const char = source[i];

    if (char === '{') {
      depth += 1;
    } else if (char === '}') {
      depth -= 1;

      if (depth === 0) {
        return source.slice(start, i + 1).trim();
      }
    }
  }

  return null;
}

/**
 * @param {string} body
 * @param {string[]} exportsInFile
 * @returns {string[]}
 */
function referencedExports(body, exportsInFile) {
  return exportsInFile.filter((name) => new RegExp(String.raw`\b${name}\b`).test(body));
}

/**
 * @param {string} repoRoot
 * @param {string} specRel
 * @returns {{ spec: string; helpers: Record<string, string> }}
 */
export function loadSpecState(repoRoot, specRel) {
  const specPath = join(repoRoot, specRel);
  const spec = readFileSync(specPath, 'utf8');
  const specDir = dirname(specPath);
  /** @type {Record<string, string>} */
  const helpers = {};

  for (const block of localImports(spec)) {
    const modulePath = join(specDir, `${block.module}.ts`);
    let moduleSource;

    try {
      moduleSource = readFileSync(modulePath, 'utf8');
    } catch {
      continue;
    }

    const moduleRel = relative(repoRoot, modulePath).split('\\').join('/');
    const exported = [...moduleSource.matchAll(/export\s+(?:async\s+)?function\s+(\w+)/g)].map(
      (entry) => entry[1],
    );
    const imported = new Set(block.names);
    const queue = [...block.names];

    while (queue.length > 0) {
      const name = queue.shift();
      const body = extractFunction(moduleSource, name);

      if (!body) {
        continue;
      }

      const key = `${moduleRel}#${name}`;

      if (helpers[key]) {
        continue;
      }

      const include =
        ORACLE_NAME.test(name) ||
        (imported.has(name) &&
          EXPECT_CALL.test(body) &&
          !/^(fill|click|login|select|resolve|reset|skip)/i.test(name));

      if (!include) {
        continue;
      }

      helpers[key] = body;

      for (const ref of referencedExports(body, exported)) {
        if (ref !== name && EXPECT_CALL.test(extractFunction(moduleSource, ref) ?? '')) {
          queue.push(ref);
        }
      }
    }
  }

  return { spec, helpers };
}
