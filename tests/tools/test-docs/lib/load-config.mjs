import { readFileSync, existsSync } from 'node:fs';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { parse as parseYaml } from 'yaml';

const __dirname = dirname(fileURLToPath(import.meta.url));
const TOOLING_ROOT = join(__dirname, '..');
const DEFAULTS_PATH = join(TOOLING_ROOT, 'schema', 'defaults.yaml');
const CONFIG_FILENAMES = ['.scribe.config.yaml', '.tests.config.yaml'];

/**
 * Deep-merge `source` into `target`. Arrays from source replace target arrays.
 * @param {Record<string, unknown>} target
 * @param {Record<string, unknown>} source
 * @returns {Record<string, unknown>}
 */
function deepMerge(target, source) {
  const result = { ...target };

  for (const [key, value] of Object.entries(source)) {
    if (
      value !== null &&
      typeof value === 'object' &&
      !Array.isArray(value) &&
      target[key] !== null &&
      typeof target[key] === 'object' &&
      !Array.isArray(target[key])
    ) {
      result[key] = deepMerge(
        /** @type {Record<string, unknown>} */ (target[key]),
        /** @type {Record<string, unknown>} */ (value),
      );
    } else if (value !== undefined) {
      result[key] = value;
    }
  }

  return result;
}

/**
 * Walk upward from `startDir` looking for a repo config, preferring
 * `.scribe.config.yaml` over the legacy `.tests.config.yaml`.
 * @param {string} startDir
 * @returns {{ repoRoot: string; configPath: string } | null}
 */
function findRepoConfig(startDir) {
  let dir = resolve(startDir);

  while (true) {
    for (const filename of CONFIG_FILENAMES) {
      const candidate = join(dir, filename);
      if (existsSync(candidate)) {
        return { repoRoot: dir, configPath: candidate };
      }
    }

    const parent = dirname(dir);
    if (parent === dir) {
      return null;
    }
    dir = parent;
  }
}

/**
 * Resolve repository root and config file path.
 * @returns {{ repoRoot: string; configPath: string }}
 */
function resolveConfigLocation() {
  const envOverride = process.env.TEST_DOCS_CONFIG;

  if (envOverride) {
    const configPath = resolve(envOverride);
    if (!existsSync(configPath)) {
      throw new Error(`TEST_DOCS_CONFIG not found: ${configPath}`);
    }
    return { repoRoot: dirname(configPath), configPath };
  }

  const found = findRepoConfig(TOOLING_ROOT);
  if (!found) {
    throw new Error(
      `${CONFIG_FILENAMES[0]} not found. Set TEST_DOCS_CONFIG or add config at repo root.`,
    );
  }

  return found;
}

/**
 * Load tooling defaults from schema/defaults.yaml.
 * @returns {Record<string, unknown>}
 */
export function loadDefaults() {
  const raw = readFileSync(DEFAULTS_PATH, 'utf8');
  return /** @type {Record<string, unknown>} */ (parseYaml(raw));
}

/**
 * Load and merge tooling defaults with the repo config.
 * @param {{ configPath?: string; repoRoot?: string }} [options]
 * @returns {Promise<{
 *   config: Record<string, unknown>;
 *   repoRoot: string;
 *   configPath: string;
 *   defaultsPath: string;
 * }>}
 */
export async function loadConfig(options = {}) {
  const defaults = loadDefaults();
  let repoRoot;
  let configPath;

  if (options.configPath) {
    configPath = resolve(options.configPath);
    repoRoot = options.repoRoot ? resolve(options.repoRoot) : dirname(configPath);
  } else {
    ({ repoRoot, configPath } = resolveConfigLocation());
  }

  if (!existsSync(configPath)) {
    const merged = {
      ...defaults,
      catalog: {
        cases: 'docs/testing/cases',
      },
    };

    return {
      config: merged,
      repoRoot,
      configPath,
      defaultsPath: DEFAULTS_PATH,
    };
  }

  const raw = readFileSync(configPath, 'utf8');
  const repoConfig = /** @type {Record<string, unknown>} */ (parseYaml(raw) ?? {});

  const lists = /** @type {Record<string, unknown>} */ (repoConfig.lists ?? {});
  const merged = deepMerge(defaults, repoConfig);

  if (lists.priority) {
    merged.priority = lists.priority;
  }
  if (lists.new_case) {
    merged.new_case = deepMerge(
      /** @type {Record<string, unknown>} */ (defaults.new_case ?? {}),
      /** @type {Record<string, unknown>} */ (lists.new_case),
    );
  }

  return {
    config: merged,
    repoRoot,
    configPath,
    defaultsPath: DEFAULTS_PATH,
  };
}

export default loadConfig;
