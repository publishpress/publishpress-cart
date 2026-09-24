#!/usr/bin/env node
import { mergeSpreadsheetIntoRtCases, importSpreadsheet, importRtCasesFromSpreadsheet } from '../lib/import-spreadsheet.mjs';

const args = process.argv.slice(2);
const dryRun = args.includes('--dry-run');
const force = args.includes('--force');
const mergeRt = args.includes('--merge-rt-edge-cases');
const importRt = args.includes('--rt') || args.includes('--replace-rt');

async function main() {
  if (importRt) {
    const replace = !args.includes('--no-replace');
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
    return;
  }

  const result = await importSpreadsheet({
    dryRun,
    skipExisting: !force,
  });

  console.log(`Spreadsheet import${dryRun ? ' (dry run)' : ''}`);
  console.log(`  Sheet: ${result.sheet}`);
  console.log(`  Parsed: ${result.total} TC cases`);
  console.log(`  Written: ${result.written}`);
  console.log(`  Skipped (already exist): ${result.skipped}`);

  if (mergeRt) {
    const merge = await mergeSpreadsheetIntoRtCases({ dryRun });
    console.log(`  RT edge-case merge: ${merge.updated} files${dryRun ? ' (dry run)' : ''}`);
  }

  if (!dryRun && result.written > 0) {
    console.log('\nNext: composer scribe:doctor');
  }
}

main().catch((err) => {
  console.error(err instanceof Error ? err.message : err);
  process.exit(1);
});
