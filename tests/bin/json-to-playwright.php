#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Converts Ghost Inspector JSON exports into Playwright smoke spec files.
 *
 * Ghost Inspector exports one JSON file per test (not Selenium IDE .side).
 * Each file has `name`, `startUrl`, and `steps[]` with GI-specific commands.
 *
 * Usage:
 *   php tests/bin/json-to-playwright.php [--source=path] [--output=path] [--test=ST-001]
 *
 * Defaults:
 *   --source  tests/playwright/smoke/source/
 *   --output  tests/playwright/smoke/
 *
 * Skips `-placeholder` and `-conditional` filename variants unless --include-variants.
 *
 * Conventions: tests/playwright/support/smoke-config.ts, checkout-flow-steps.ts
 */

final class GiJsonToPlaywrightConverter
{
  private const ORDER_NOW_PATTERN = "xpath=//button[contains(.,'Purchase') or contains(.,'Complete') or contains(.,'Pay') or contains(.,'Submit')";

  /** @var list<string> */
  private array $unsupported = [];

  public function __construct(
    private readonly string $sourcePath,
    private readonly string $outputDir,
    private readonly bool $includeVariants = false
  ) {
  }

  /**
   * @return list<string> Generated file paths.
   */
  public function convert(?string $onlyTest = null): array
  {
    $files = $this->resolveSourceFiles();

    if (!is_dir($this->outputDir) && !mkdir($this->outputDir, 0755, true) && !is_dir($this->outputDir)) {
      throw new RuntimeException(sprintf('Unable to create output directory: %s', $this->outputDir));
    }

    $generated = [];

    foreach ($files as $file) {
      $raw = file_get_contents($file);

      if ($raw === false) {
        throw new RuntimeException(sprintf('Unable to read source file: %s', $file));
      }

      /** @var array<string, mixed> $data */
      $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
      $testName = (string) ($data['name'] ?? basename($file, '.json'));

      if ($onlyTest !== null && !$this->matchesTestFilter($testName, $onlyTest)) {
        continue;
      }

      $urlKey = $this->extractUrlKey((string) ($data['startUrl'] ?? ''));
      $groups = $this->groupsFromTest($testName, $urlKey);
      $emailKey = $this->emailKeyFromTest($testName);
      $steps = is_array($data['steps'] ?? null) ? $data['steps'] : [];
      $lines = $this->convertSteps($steps, $groups, $emailKey, $urlKey, $testName);
      $usedHelpers = $this->detectUsedHelpers($lines, $groups);
      $content = $this->renderSpec($testName, $groups, $urlKey, $lines, $usedHelpers);
      $path = $this->outputDir . '/' . $this->specFileNameFromTest($testName);
      file_put_contents($path, $content);
      $generated[] = $path;
    }

    if ($this->unsupported !== []) {
      fwrite(STDERR, "Unsupported commands encountered:\n");

      foreach (array_unique($this->unsupported) as $message) {
        fwrite(STDERR, "  - {$message}\n");
      }
    }

    return $generated;
  }

  /**
   * @return list<string>
   */
  private function resolveSourceFiles(): array
  {
    if (is_file($this->sourcePath)) {
      return [$this->sourcePath];
    }

    if (!is_dir($this->sourcePath)) {
      throw new RuntimeException(sprintf('Source path not found: %s', $this->sourcePath));
    }

    $files = glob($this->sourcePath . '/*.json') ?: [];

    if ($files === []) {
      throw new RuntimeException(sprintf('No JSON files found in: %s', $this->sourcePath));
    }

    sort($files);

    return array_values(array_filter($files, fn (string $path): bool => $this->shouldIncludeFile(basename($path))));
  }

  private function shouldIncludeFile(string $basename): bool
  {
    if ($this->includeVariants) {
      return true;
    }

    return !preg_match('/[-_](?:placeholder|conditional)\.json$/i', $basename);
  }

  private function matchesTestFilter(string $testName, string $filter): bool
  {
    if (stripos($testName, $filter) !== false) {
      return true;
    }

    if (preg_match('/TC-(\d+)/i', $testName, $matches)) {
      $st = 'ST-' . str_pad($matches[1], 3, '0', STR_PAD_LEFT);

      return stripos($st, strtoupper($filter)) !== false;
    }

    return false;
  }

  /**
   * @param list<array<string, mixed>> $steps
   * @param list<string> $groups
   * @return list<string>
   */
  private function convertSteps(
    array $steps,
    array $groups,
    string $emailKey,
    ?string $urlKey,
    string $testName
  ): array {
    $lines = [];

    if ($urlKey !== null) {
      if ($urlKey === 'smoke_admin_path') {
        $lines[] = "await page.goto(config.pagePath('smoke_admin_path'));";
      } else {
        $lines[] = sprintf("await page.goto(config.pagePath('%s'));", $urlKey);
      }
    }

    if ($this->needsFailedStripeCheckout($groups, $testName, $steps)) {
      $lines[] = "await fillCard(page, stripeConfig, { cardNumber: stripeConfig.var('stripe_card_number_decline') });";
    } elseif ($this->needsStripeCardPrerequisite($groups, $testName, $steps)) {
      $lines[] = 'await fillCard(page, stripeConfig);';
    }

    if ($this->needsCheckoutContactFields($groups, $testName, $steps) || $this->needsFailedStripeCheckout($groups, $testName, $steps)) {
      foreach ($this->checkoutContactFieldLines($emailKey) as $line) {
        $lines[] = $line;
      }
    }

    foreach ($steps as $index => $step) {
      if (!is_array($step)) {
        continue;
      }

      $command = (string) ($step['command'] ?? '');
      $target = $this->normalizeTarget($step['target'] ?? '');
      $value = (string) ($step['value'] ?? '');

      foreach ($this->convertStep($command, $target, $value, $groups, $emailKey, $steps, $index) as $line) {
        $lines[] = $line;
      }
    }

    if ($this->needsOrderConfirmation($groups, $testName, $steps)) {
      $lines[] = 'await assertOrderReceived(page);';
    }

    if ($lines === [] && $urlKey === null) {
      $lines[] = '// TODO: Ghost Inspector export has no startUrl or steps — implement manually';
    } elseif ($lines === []) {
      $lines[] = '// TODO: Ghost Inspector export has no steps — implement manually';
    }

    return $lines;
  }

  /**
   * @param list<string> $groups
   * @param list<array<string, mixed>> $steps
   * @return list<string>
   */
  private function convertStep(
    string $command,
    string $target,
    string $value,
    array $groups,
    string $emailKey,
    array $steps,
    int $index
  ): array {
    switch ($command) {
      case 'assign':
        $fill = $this->convertAssign($target, $value, $groups, $emailKey);

        return $fill !== null ? [$fill] : [];

      case 'click':
        return $this->convertClick($target, $value, $steps, $index);

      case 'assertText':
        return $this->convertAssertText($target, $value);

      case 'assertElementPresent':
        if ($target === '') {
          return [];
        }

        if ($this->isSubmittingButtonAssert($target)) {
          return ['await assertOrderNowSubmitting(page);'];
        }

        return [
          sprintf(
            "await page.locator(%s).waitFor({ state: 'visible', timeout: 10_000 });",
            $this->playwrightLocator($target)
          ),
        ];

      case 'assertElementNotPresent':
        if ($target === '') {
          return [];
        }

        return [
          sprintf('await assertElementNotPresent(page, %s);', var_export($this->cssSelector($target), true)),
        ];

      case 'pause':
        $ms = max(0, (int) $value);

        return $ms > 0 ? [sprintf('await page.waitForTimeout(%d);', $ms)] : [];

      default:
        $this->unsupported[] = $command;

        return [sprintf('// TODO: unsupported GI command %s', $command)];
    }
  }

  /**
   * @param list<string> $groups
   */
  private function convertAssign(string $target, string $value, array $groups, string $emailKey): ?string
  {
    $field = $this->knownFieldFromAssignTarget($target);

    if ($field !== null) {
      $fillValue = $this->assignFillExpression($field, $value, $groups, $emailKey);

      return sprintf("await page.locator('#%s').fill(%s);", $field, $fillValue);
    }

    if ($target === '') {
      return null;
    }

    return sprintf(
      'await page.locator(%s).fill(%s);',
      $this->playwrightLocator($target),
      $this->fillValueExpression($value, $groups, $emailKey)
    );
  }

  /**
   * @param list<array<string, mixed>> $steps
   * @return list<string>
   */
  private function convertClick(string $target, string $value, array $steps, int $index): array
  {
    if ($this->isOrderNowTarget($target)) {
      $repeat = max(1, (int) $value);

      if ($this->isFailedStripeCheckoutTest($testName)) {
        return ['await submitStripeCheckoutExpectingDecline(page);'];
      }

      $lines = [];

      if ($repeat > 1 && $this->isDuplicateSubmitAssert($steps, $index)) {
        return [sprintf('await clickOrderNowRepeated(page, %d);', $repeat)];
      }

      $clickHelper = $this->isValidationOrderNowClick($steps, $index)
        ? 'clickOrderNowForValidation'
        : 'clickOrderNow';

      if ($clickHelper === 'clickOrderNowForValidation' && $index === 0) {
        if ($this->isTermsValidationOrderNowClick($steps, $index)) {
          $lines[] = "await page.locator('#ppcart_accept_terms').waitFor({ state: 'visible', timeout: 30_000 });";
        } else {
          $lines[] = "await page.locator('#email').waitFor({ state: 'visible', timeout: 30_000 });";
        }
      }

      for ($i = 0; $i < $repeat; $i++) {
        $lines[] = sprintf('await %s(page);', $clickHelper);
      }

      return $lines;
    }

    if ($this->isPaypalMethodTarget($target)) {
      return ['await selectPaypalPayment(page);'];
    }

    if ($this->isTermsCheckboxTarget($target)) {
      return ['await clickTermsCheckbox(page);'];
    }

    if ($target === '') {
      return [];
    }

    return [sprintf('await page.locator(%s).click();', $this->playwrightLocator($target))];
  }

  /**
   * @return list<string>
   */
  private function convertAssertText(string $target, string $value): array
  {
    $selector = $target !== '' ? $this->cssSelector($target) : 'body';
    $expectedExpr = $this->assertTextExpression($value);

    if ($selector === 'body') {
      return [sprintf('await assertBodyContains(page, %s);', $expectedExpr)];
    }

    return [
      sprintf(
        'await assertElementContainsText(page, %s, %s);',
        var_export($selector, true),
        $expectedExpr
      ),
    ];
  }

  private function assertTextExpression(string $value): string
  {
    if (preg_match('/^\*(.+)\*$/s', $value, $matches)) {
      return var_export($matches[1], true);
    }

    if (preg_match('/^\{\{([a-z0-9_]+)\}\}$/i', $value, $matches)) {
      return sprintf("config.var('%s')", $matches[1]);
    }

    return var_export($value, true);
  }

  /**
   * @param list<string> $groups
   * @param list<array<string, mixed>> $steps
   */
  private function needsStripeCardPrerequisite(array $groups, string $testName, array $steps): bool
  {
    if (!in_array('stripe', $groups, true)) {
      return false;
    }

    if (str_contains(strtolower($testName), 'failed')) {
      return false;
    }

    if (str_contains(strtolower($testName), 'validate')) {
      return false;
    }

    if (str_contains(strtolower($testName), 'required')) {
      return false;
    }

    if (str_contains(strtolower($testName), 'abandoned')) {
      return false;
    }

    foreach ($steps as $step) {
      if (($step['command'] ?? '') === 'click' && $this->isOrderNowTarget($this->normalizeTarget($step['target'] ?? ''))) {
        return true;
      }
    }

    return str_contains(strtolower($testName), 'succeeds');
  }

  /**
   * @param list<string> $groups
   * @param list<array<string, mixed>> $steps
   */
  private function needsOrderConfirmation(array $groups, string $testName, array $steps): bool
  {
    if (!in_array('stripe', $groups, true) && !in_array('paypal', $groups, true)) {
      return false;
    }

    if (!str_contains(strtolower($testName), 'succeeds')) {
      return false;
    }

    foreach ($steps as $step) {
      if (($step['command'] ?? '') === 'click' && $this->isOrderNowTarget($this->normalizeTarget($step['target'] ?? ''))) {
        return true;
      }
    }

    return false;
  }

  /**
   * @param list<string> $groups
   * @param list<array<string, mixed>> $steps
   */
  private function needsCheckoutContactFields(array $groups, string $testName, array $steps): bool
  {
    if (!in_array('stripe', $groups, true)) {
      return false;
    }

    if (str_contains(strtolower($testName), 'validate') || str_contains(strtolower($testName), 'required')) {
      return false;
    }

    if (str_contains(strtolower($testName), 'failed') || str_contains(strtolower($testName), 'abandoned')) {
      return false;
    }

    foreach ($steps as $step) {
      if (($step['command'] ?? '') === 'click' && $this->isOrderNowTarget($this->normalizeTarget($step['target'] ?? ''))) {
        return true;
      }
    }

    return str_contains(strtolower($testName), 'succeeds');
  }

  /**
   * @param list<string> $groups
   * @param list<array<string, mixed>> $steps
   */
  private function needsFailedStripeCheckout(array $groups, string $testName, array $steps): bool
  {
    if (!$this->isFailedStripeCheckoutTest($testName) || !in_array('stripe', $groups, true)) {
      return false;
    }

    foreach ($steps as $step) {
      if (($step['command'] ?? '') === 'click' && $this->isOrderNowTarget($this->normalizeTarget($step['target'] ?? ''))) {
        return true;
      }
    }

    return false;
  }

  private function isFailedStripeCheckoutTest(string $testName): bool
  {
    $normalized = strtolower($testName);

    return str_contains($normalized, 'failed') && str_contains($normalized, 'stripe');
  }

  /**
   * @return list<string>
   */
  private function checkoutContactFieldLines(string $emailKey): array
  {
    return [
      "await page.locator('#first_name').fill(config.var('first_name'));",
      "await page.locator('#last_name').fill(config.var('last_name'));",
      sprintf("await page.locator('#email').fill(config.uniqueEmail('%s'));", $emailKey),
      "await page.locator('#phone').fill(config.var('phone'));",
    ];
  }

  /**
   * @param list<array<string, mixed>> $steps
   */
  private function isDuplicateSubmitAssert(array $steps, int $index): bool
  {
    $next = $steps[$index + 1] ?? null;

    if (!is_array($next) || ($next['command'] ?? '') !== 'assertElementPresent') {
      return false;
    }

    return $this->isSubmittingButtonAssert($this->normalizeTarget($next['target'] ?? ''));
  }

  private function isSubmittingButtonAssert(string $target): bool
  {
    $normalized = strtolower($target);

    return str_contains($normalized, '@disabled')
      || str_contains($normalized, 'loading')
      || str_contains($normalized, 'disabled');
  }

  private function isTermsCheckboxTarget(string $target): bool
  {
    $normalized = strtolower($target);

    return str_contains($normalized, 'terms')
      && (str_contains($normalized, 'checkbox') || str_contains($normalized, 'label'));
  }

  /**
   * @param list<string> $lines
   * @param list<string> $groups
   * @return array<string, bool>
   */
  private function detectUsedHelpers(array $lines, array $groups): array
  {
    $body = implode("\n", $lines);

    return [
      'assertBodyContains' => str_contains($body, 'assertBodyContains('),
      'assertElementContainsText' => str_contains($body, 'assertElementContainsText('),
      'assertElementNotPresent' => str_contains($body, 'assertElementNotPresent('),
      'assertOrderReceived' => str_contains($body, 'assertOrderReceived('),
      'clickOrderNow' => str_contains($body, 'clickOrderNow('),
      'clickOrderNowForValidation' => str_contains($body, 'clickOrderNowForValidation('),
      'clickOrderNowRepeated' => str_contains($body, 'clickOrderNowRepeated('),
      'assertOrderNowSubmitting' => str_contains($body, 'assertOrderNowSubmitting('),
      'submitStripeCheckoutExpectingDecline' => str_contains($body, 'submitStripeCheckoutExpectingDecline('),
      'fillCard' => str_contains($body, 'fillCard('),
      'clickTermsCheckbox' => str_contains($body, 'clickTermsCheckbox('),
      'selectPaypalPayment' => str_contains($body, 'selectPaypalPayment('),
      'skipUnlessPaypal' => in_array('paypal', $groups, true),
      'skipUnlessSmokeUrl' => true,
      'useRegressionConfigForStripe' => str_contains($body, 'fillCard('),
    ];
  }

  /**
   * @param list<string> $groups
   * @param array<string, bool> $usedHelpers
   * @param list<string> $lines
   */
  private function renderSpec(
    string $testName,
    array $groups,
    ?string $urlKey,
    array $lines,
    array $usedHelpers
  ): string {
    $title = $this->playwrightTitleFromTest($testName);
    $tags = array_map(static fn (string $group): string => '"@' . $group . '"', $groups);
    $tagList = implode(',', $tags);
    $timeout = in_array('paypal', $groups, true) ? '180000' : '90000';

    $imports = ["import { test } from '@playwright/test';", ''];

    $checkoutImports = [];

    foreach (
      [
        'assertBodyContains',
        'assertElementContainsText',
        'assertElementNotPresent',
        'assertOrderReceived',
        'submitStripeCheckoutExpectingDecline',
        'clickOrderNow',
        'clickOrderNowForValidation',
        'clickOrderNowRepeated',
        'assertOrderNowSubmitting',
        'clickTermsCheckbox',
        'selectPaypalPayment',
      ] as $helper
    ) {
      if ($usedHelpers[$helper]) {
        $checkoutImports[] = $helper;
      }
    }

    if ($checkoutImports !== []) {
      $imports[] = 'import { ' . implode(', ', $checkoutImports) . " } from '../support/checkout-flow-steps';";
      $imports[] = '';
    }

    if ($usedHelpers['fillCard']) {
      $imports[] = "import { RegressionConfig } from '../support/regression-config';";
      $imports[] = "import { fillCard } from '../support/stripe-steps';";
      $imports[] = '';
    }

    $imports[] = "import { SmokeConfig } from '../support/smoke-config';";

    $skipImports = ['skipUnlessSmokeUrl'];

    if ($usedHelpers['skipUnlessPaypal']) {
      $skipImports[] = 'skipUnlessPaypal';
    }

    $imports[] = 'import { ' . implode(', ', $skipImports) . " } from '../support/smoke-skip';";

    $importBlock = implode("\n", $imports);
    $body = implode("\n", array_map(static fn (string $line): string => '  ' . $line, $lines));
    $urlKeyLiteral = $urlKey ?? 'checkout_stripe_url';
    $skipLines = "  skipUnlessSmokeUrl(config, '{$urlKeyLiteral}');";

    if ($usedHelpers['skipUnlessPaypal']) {
      $skipLines .= "\n  skipUnlessPaypal(config);";
    }

    $configInit = $usedHelpers['useRegressionConfigForStripe']
      ? "const config = SmokeConfig.fromEnv();\nconst stripeConfig = RegressionConfig.fromEnv();"
      : 'const config = SmokeConfig.fromEnv();';

    $body = str_replace('fillCard(page, config)', 'fillCard(page, stripeConfig)', $body);

    return <<<TS
// AUTO-GENERATED by tests/bin/json-to-playwright.php — edit the converter or JSON source, then regenerate.

{$importBlock}

{$configInit}

test('{$title}', { tag: [{$tagList}] }, async ({ page }) => {
  test.setTimeout({$timeout});
{$skipLines}

{$body}
});

TS;
  }

  private function extractUrlKey(string $startUrl): ?string
  {
    $decoded = urldecode($startUrl);

    if (preg_match('/\{\{([a-z0-9_]+)\}\}/i', $decoded, $matches)) {
      return $matches[1];
    }

    if (str_contains($decoded, '/wp-admin')) {
      return 'smoke_admin_path';
    }

    if ($decoded === '') {
      return null;
    }

    throw new RuntimeException(sprintf('Unable to parse URL key from startUrl: %s', $startUrl));
  }

  private function normalizeTarget(mixed $target): string
  {
    if (is_string($target)) {
      return $target;
    }

    if (!is_array($target)) {
      return '';
    }

    if (isset($target['selector']) && is_string($target['selector'])) {
      return $target['selector'];
    }

    $first = $target[0] ?? null;

    if (is_array($first) && isset($first['selector']) && is_string($first['selector'])) {
      return $first['selector'];
    }

    return '';
  }

  private function knownFieldFromAssignTarget(string $target): ?string
  {
    if (preg_match("/first[_-]?name|id\\*='first'/i", $target)) {
      return 'first_name';
    }

    if (preg_match("/last[_-]?name|id\\*='last'/i", $target)) {
      return 'last_name';
    }

    if (preg_match("/type=['\"]email['\"]|name=['\"]email['\"]/i", $target)) {
      return 'email';
    }

    if (preg_match("/type=['\"]tel['\"]|phone/i", $target)) {
      return 'phone';
    }

    return null;
  }

  /**
   * @param list<string> $groups
   */
  private function assignFillExpression(string $field, string $value, array $groups, string $emailKey): string
  {
    if ($field === 'email') {
      if (preg_match('/^\{\{test_email\}\}$/i', $value)) {
        return "config.var('test_email')";
      }

      if (preg_match('/^\{\{email\}\}$/i', $value) && in_array('stripe', $groups, true)) {
        return sprintf("config.uniqueEmail('%s')", $emailKey);
      }
    }

    if (preg_match('/^\{\{([a-z0-9_]+)\}\}$/i', $value, $matches)) {
      return sprintf("config.var('%s')", $matches[1]);
    }

    return var_export($value, true);
  }

  /**
   * @param list<string> $groups
   */
  private function fillValueExpression(string $value, array $groups, string $emailKey): string
  {
    if (preg_match('/^\{\{([a-z0-9_]+)\}\}$/i', $value, $matches)) {
      return sprintf("config.var('%s')", $matches[1]);
    }

    return var_export($value, true);
  }

  private function isOrderNowTarget(string $target): bool
  {
    return str_contains($target, self::ORDER_NOW_PATTERN)
      || str_contains($target, "Order Now");
  }

  /**
   * @param list<array<string, mixed>> $steps
   */
  private function isValidationOrderNowClick(array $steps, int $index): bool
  {
    $next = $steps[$index + 1] ?? null;

    if (!is_array($next) || ($next['command'] ?? '') !== 'assertText') {
      return false;
    }

    $expected = strtolower((string) ($next['value'] ?? ''));

    return str_contains($expected, 'required')
      || str_contains($expected, 'email')
      || str_contains($expected, 'terms');
  }

  private function isTermsValidationOrderNowClick(array $steps, int $index): bool
  {
    $next = $steps[$index + 1] ?? null;

    if (!is_array($next) || ($next['command'] ?? '') !== 'assertText') {
      return false;
    }

    return str_contains(strtolower((string) ($next['value'] ?? '')), 'terms');
  }

  private function isPaypalMethodTarget(string $target): bool
  {
    return str_contains(strtolower($target), 'paypal');
  }

  private function playwrightLocator(string $target): string
  {
    if (str_starts_with($target, 'css=')) {
      return var_export(substr($target, 4), true);
    }

    if (str_starts_with($target, 'xpath=')) {
      return var_export($target, true);
    }

    return var_export($target, true);
  }

  private function cssSelector(string $target): string
  {
    if (str_starts_with($target, 'css=')) {
      return substr($target, 4);
    }

    if (str_starts_with($target, 'xpath=')) {
      return $target;
    }

    return $target;
  }

  private function emailKeyFromTest(string $name): string
  {
    if (preg_match('/TC-(\d+)/i', $name, $matches)) {
      return 'st' . str_pad($matches[1], 3, '0', STR_PAD_LEFT);
    }

    return strtolower((string) preg_replace('/[^a-z0-9]+/', '-', $name));
  }

  private function playwrightTitleFromTest(string $name): string
  {
    if (preg_match('/TC-(\d+)/i', $name, $matches)) {
      $code = 'ST-' . str_pad($matches[1], 3, '0', STR_PAD_LEFT);
      $slug = $this->stripGiPrefix($name);
      $slug = preg_replace('/\s*-\s*(?:placeholder|conditional)\s*$/i', '', $slug);
      $slug = preg_replace('/\s*-\s*/', ' ', $slug);
      $slug = strtolower(trim((string) $slug));
      $slug = str_replace('one time', 'one-time', $slug);
      $slug = preg_replace('/\bstripe\b/', 'Stripe', $slug);
      $slug = preg_replace('/\bpaypal\b/', 'PayPal', $slug);

      return $code . ' ' . $slug;
    }

    return strtolower($name);
  }

  private function specFileNameFromTest(string $name): string
  {
    if (preg_match('/TC-(\d+)/i', $name, $matches)) {
      $num = str_pad($matches[1], 3, '0', STR_PAD_LEFT);
      $slug = $this->stripGiPrefix($name);
      $slug = preg_replace('/\s*-\s*(?:placeholder|conditional)\s*$/i', '', $slug);
      $slug = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $slug));
      $slug = trim($slug, '-');

      return "st{$num}-{$slug}.spec.ts";
    }

    $slug = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $name));

    return trim($slug, '-') . '.spec.ts';
  }

  private function stripGiPrefix(string $name): string
  {
    return (string) preg_replace(
      '/^\[(?:PublishPress Cart|StudioCart(?:Pro)?)\]\s*(?:Smoke\s*-\s*)?TC-\d+\s*/i',
      '',
      $name
    );
  }

  /**
   * @return list<string>
   */
  private function groupsFromTest(string $name, ?string $urlKey): array
  {
    $groups = ['smoke'];
    $lower = strtolower($name);

    if (stripos($name, 'stripe') !== false || $urlKey === 'checkout_stripe_url') {
      $groups[] = 'stripe';
    }

    if (stripos($name, 'paypal') !== false || $urlKey === 'checkout_paypal_url') {
      $groups[] = 'paypal';
    }

    if (str_contains($lower, 'subscription') || $urlKey === 'checkout_subscription_url') {
      $groups[] = 'subscription';
    }

    if (str_contains($lower, 'admin') || $urlKey === 'smoke_admin_path') {
      $groups[] = 'admin';
    }

    return $groups;
  }
}

$options = getopt('', ['source::', 'output::', 'test::', 'include-variants']);

if (PHP_SAPI === 'cli' && realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) {
  $repoRoot = dirname(__DIR__, 2);
  $source = $options['source'] ?? $repoRoot . '/tests/playwright/smoke/source';
  $output = $options['output'] ?? $repoRoot . '/tests/playwright/smoke';
  $test = $options['test'] ?? null;
  $includeVariants = array_key_exists('include-variants', $options);

  $converter = new GiJsonToPlaywrightConverter($source, $output, $includeVariants);
  $files = $converter->convert($test);

  echo sprintf("Generated %d Playwright spec(s):\n", count($files));

  foreach ($files as $file) {
    echo "  - {$file}\n";
  }
}
