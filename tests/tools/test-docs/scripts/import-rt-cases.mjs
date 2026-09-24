#!/usr/bin/env node
import { importRtCasesFromSpreadsheet } from '../lib/import-spreadsheet.mjs';

const args = process.argv.slice(2);
const dryRun = args.includes('--dry-run');
const replace = !args.includes('--no-replace');

async function main() {
  const result = await importRtCasesFromSpreadsheet({
    dryRun,
    replace,
  });

  console.log(`Master Test Suite → RT catalog import${dryRun ? ' (dry run)' : ''}`);
  console.log(`  Sheet: ${result.sheet}`);
  console.log(`  Parsed: ${result.total} TC rows`);
  console.log(`  Deleted existing: ${result.deleted}`);
  console.log(`  Written: ${result.written} RT cases`);

  if (!dryRun && result.written > 0) {
    console.log('\nNext: composer scribe:doctor');
  }
}

main().catch((err) => {
  console.error(err instanceof Error ? err.message : err);
  process.exit(1);
});
