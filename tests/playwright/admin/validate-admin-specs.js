const fs = require('fs');
const path = require('path');

const adminDir = __dirname;
const supportDir = path.resolve(adminDir, '../support');
const adminCasesPath = path.join(supportDir, 'admin-cases.ts');

const adminCasesSource = fs.readFileSync(adminCasesPath, 'utf8');
const caseIds = new Set(Array.from(adminCasesSource.matchAll(/id:\s*"(AD-\d+)"/g), ([, id]) => id));

if (caseIds.size === 0) {
  throw new Error(`No admin case IDs found in ${adminCasesPath}`);
}

const specFiles = fs
  .readdirSync(adminDir)
  .filter((file) => /^ad\d{3}-.+\.spec\.ts$/.test(file))
  .sort();

const problems = [];
const specIds = new Set();

for (const file of specFiles) {
  const filePath = path.join(adminDir, file);
  const source = fs.readFileSync(filePath, 'utf8');
  const match = source.match(/adminTestsById\.get\('(AD-\d+)'\)/);

  if (!match) {
    problems.push(`${file}: missing adminTestsById.get('AD-xxx') reference`);
    continue;
  }

  const [, id] = match;
  specIds.add(id);

  const expectedPrefix = id.toLowerCase().replace('-', '');

  if (!file.startsWith(`${expectedPrefix}-`)) {
    problems.push(`${file}: filename prefix does not match ${id}`);
  }

  if (!caseIds.has(id)) {
    problems.push(`${file}: references missing admin case ${id}`);
  }
}

for (const id of [...caseIds].sort()) {
  if (!specIds.has(id)) {
    problems.push(`missing spec file for ${id}`);
  }
}

if (problems.length > 0) {
  console.error('Admin spec validation failed:\n');
  for (const problem of problems) {
    console.error(`- ${problem}`);
  }
  process.exit(1);
}

console.log(`Validated ${specFiles.length} admin specs against ${caseIds.size} admin case definitions.`);
