#!/usr/bin/env node
/**
 * RT oracle-quality pilot (Jev 1.13).
 *
 *   node tests/tools/jev-regression-effectiveness/run.mjs
 *   node tests/tools/jev-regression-effectiveness/run.mjs --live
 *   node tests/tools/jev-regression-effectiveness/run.mjs --case RT-001 --print-state
 *
 * Default is dry-run. --live needs TYPESAFE_API_KEY (.env or env).
 */
import { existsSync, readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

import { CASES, MODEL, QUESTIONS } from './pack.mjs';
import { loadSpecState } from './lib/state.mjs';

const ROOT = join(dirname(fileURLToPath(import.meta.url)), '../../..');
const ENDPOINT = 'https://api.typesafe.ai/v1/systemone';

/**
 * @param {string} repoRoot
 */
function loadDotEnv(repoRoot) {
  const file = join(repoRoot, '.env');

  if (!existsSync(file)) {
    return;
  }

  for (const rawLine of readFileSync(file, 'utf8').split(/\r?\n/)) {
    const line = rawLine.trim();

    if (line === '' || line.startsWith('#') || !line.includes('=')) {
      continue;
    }

    const [key, ...rest] = line.split('=');
    const trimmedKey = key.trim();

    if (trimmedKey === '' || (process.env[trimmedKey] !== undefined && process.env[trimmedKey] !== '')) {
      continue;
    }

    process.env[trimmedKey] = rest
      .join('=')
      .trim()
      .replace(/^['"]|['"]$/g, '')
      .replace(/\\(['"\\$])/g, '$1');
  }
}

/**
 * @param {string[]} argv
 */
function parseArgs(argv) {
  const options = { live: false, printState: false, json: false, cases: [] };

  for (let i = 0; i < argv.length; i += 1) {
    const arg = argv[i];

    if (arg === '--live') {
      options.live = true;
    } else if (arg === '--print-state') {
      options.printState = true;
    } else if (arg === '--json') {
      options.json = true;
    } else if (arg === '--case') {
      const value = argv[i + 1];

      if (!value) {
        throw new Error('--case requires an id (RT-001)');
      }

      options.cases.push(value.toUpperCase());
      i += 1;
    } else if (arg === '--help' || arg === '-h') {
      options.help = true;
    } else {
      throw new Error(`Unknown argument: ${arg}`);
    }
  }

  return options;
}

/**
 * @param {{ id: string; spec: string; should_catch: string }} packCase
 */
function buildState(packCase) {
  const loaded = loadSpecState(ROOT, packCase.spec);

  return {
    case_id: packCase.id,
    should_catch: packCase.should_catch,
    spec_path: packCase.spec,
    spec: loaded.spec,
    helpers: loaded.helpers,
  };
}

/**
 * @param {Record<string, unknown>} state
 */
function stateBytes(state) {
  return Buffer.byteLength(JSON.stringify(state), 'utf8');
}

/**
 * @param {Record<string, unknown>} state
 */
async function evaluate(state) {
  const key = process.env.TYPESAFE_API_KEY;

  if (!key || key.startsWith('ts-your-')) {
    throw new Error('Set TYPESAFE_API_KEY in .env for --live');
  }

  const response = await fetch(ENDPOINT, {
    method: 'POST',
    headers: {
      Authorization: `Bearer ${key}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      model: MODEL,
      state,
      questions: QUESTIONS,
    }),
  });

  const body = await response.text();

  if (!response.ok) {
    throw new Error(`TypeSafe ${response.status}: ${body.slice(0, 500)}`);
  }

  return JSON.parse(body);
}

/**
 * @param {object} answers
 */
function summarize(answers) {
  return {
    noul: answers.catches_distinguishing?.noul,
    gap: answers.gap?.choice,
    gap_confidence: answers.gap?.confidence,
    oracle_strength: answers.oracle_strength?.score,
  };
}

function printHelp() {
  process.stdout.write(`RT oracle-quality pilot (Jev ${MODEL})

  node tests/tools/jev-regression-effectiveness/run.mjs [--live] [--case RT-001] [--print-state] [--json]

  Default: dry-run (no API). --live calls Jev. Score is mean noul, not Score.score.
`);
}

async function main() {
  const options = parseArgs(process.argv.slice(2));

  if (options.help) {
    printHelp();
    return;
  }

  loadDotEnv(ROOT);

  const selected = options.cases.length
    ? CASES.filter((packCase) => options.cases.includes(packCase.id))
    : CASES;

  if (selected.length === 0) {
    throw new Error(`No cases matched: ${options.cases.join(', ')}`);
  }

  const rows = [];

  for (const packCase of selected) {
    const state = buildState(packCase);
    const helperKeys = Object.keys(state.helpers);
    const row = {
      id: packCase.id,
      spec: packCase.spec,
      should_catch: packCase.should_catch,
      helpers: helperKeys,
      state_bytes: stateBytes(state),
    };

    if (options.printState) {
      row.state = state;
    }

    if (options.live) {
      const result = await evaluate(state);
      row.model = result.model;
      row.usage = result.usage;
      row.answers = result.answers;
      Object.assign(row, summarize(result.answers));
    }

    rows.push(row);
  }

  if (options.json) {
    const nouls = rows.map((row) => row.noul).filter((value) => typeof value === 'number');
    process.stdout.write(
      `${JSON.stringify(
        {
          model: MODEL,
          mean_noul: nouls.length ? nouls.reduce((sum, value) => sum + value, 0) / nouls.length : null,
          cases: rows,
        },
        null,
        2,
      )}\n`,
    );
    return;
  }

  for (const row of rows) {
    const helpers = row.helpers.length ? row.helpers.join(', ') : '(none)';
    process.stdout.write(`${row.id}  ${row.state_bytes}B  helpers: ${helpers}\n`);
    process.stdout.write(`  should_catch: ${row.should_catch}\n`);

    if (options.live) {
      process.stdout.write(
        `  noul=${row.noul?.toFixed?.(3) ?? row.noul}  gap=${row.gap} (conf=${row.gap_confidence?.toFixed?.(2)})  strength=${row.oracle_strength?.toFixed?.(2)}\n`,
      );
    }
  }

  if (options.live) {
    const nouls = rows.map((row) => row.noul).filter((value) => typeof value === 'number');
    const mean = nouls.reduce((sum, value) => sum + value, 0) / nouls.length;
    process.stdout.write(`\nmean catches_distinguishing.noul = ${mean.toFixed(3)}  (n=${nouls.length})\n`);
  }
}

main().catch((error) => {
  process.stderr.write(`${error.message}\n`);
  process.exitCode = 1;
});
