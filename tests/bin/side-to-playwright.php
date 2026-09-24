#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Converts a Ghost Inspector Selenium IDE (.side) export into Playwright spec files.
 *
 * Usage: php tests/bin/side-to-playwright.php [--source=path] [--output=path] [--test=RT-001]
 *
 * Conventions: tests/playwright/support/
 */

final class SideToPlaywrightConverter
{
    /** @var list<string> */
    private const PAYPAL_PAYMENT_LABEL_TARGETS = [
        'css=label:nth-of-type(2) > .item-name',
    ];

    /** @var list<string> */
    private const PAYPAL_LOGIN_CLICK_TARGETS = [
        'xpath=//button[contains(text(), "Next")]',
        'xpath=//button[contains(text(), "Log In")]',
        'css=#password',
        'css=[data-testid="submit-button-initial"]',
        'css=[data-test-id="continueButton"]',
        'xpath=//button[contains(text(), "Continue")]',
    ];

    /** @var list<string> */
    private array $unsupported = [];

    public function __construct(
        private readonly string $sourcePath,
        private readonly string $outputDir
    ) {
    }

    /**
     * @return list<string> Generated file paths.
     */
    public function convert(?string $onlyTest = null): array
    {
        $raw = file_get_contents($this->sourcePath);

        if ($raw === false) {
            throw new RuntimeException(sprintf('Unable to read source file: %s', $this->sourcePath));
        }

        /** @var array<string, mixed> $data */
        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

        if (!isset($data['tests']) || !is_array($data['tests'])) {
            throw new RuntimeException('Invalid .side file: missing tests array.');
        }

        if (!is_dir($this->outputDir) && !mkdir($this->outputDir, 0755, true) && !is_dir($this->outputDir)) {
            throw new RuntimeException(sprintf('Unable to create output directory: %s', $this->outputDir));
        }

        $generated = [];

        foreach ($data['tests'] as $test) {
            if (!is_array($test) || !isset($test['name'], $test['commands'])) {
                continue;
            }

            $testName = (string) $test['name'];

            if ($onlyTest !== null && !$this->matchesTestFilter($testName, $onlyTest)) {
                continue;
            }

            $groups = $this->groupsFromTest($testName);
            $emailKey = $this->emailKeyFromTest($testName);
            $urlKey = $this->extractUrlKey($test['commands']);
            $lines = $this->convertCommands($test['commands'], $groups, $emailKey, $urlKey);
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

    private function matchesTestFilter(string $testName, string $filter): bool
    {
        if (stripos($testName, $filter) !== false) {
            return true;
        }

        if (preg_match('/TC-(\d+)/i', $testName, $matches)) {
            $rt = 'RT-' . str_pad($matches[1], 3, '0', STR_PAD_LEFT);

            return stripos($rt, strtoupper($filter)) !== false;
        }

        return false;
    }

    /**
     * @param list<array<string, mixed>> $commands
     * @param list<string> $groups
     * @return list<string>
     */
    private function convertCommands(array $commands, array $groups, string $emailKey, ?string $urlKey): array
    {
        $lines = [];

        for ($index = 0, $count = count($commands); $index < $count; $index++) {
            $command = $commands[$index];
            $name = (string) ($command['command'] ?? '');
            $target = (string) ($command['target'] ?? '');
            $value = (string) ($command['value'] ?? '');

            if ($name === 'click' && $this->isFollowedByTypeOnSameTarget($commands, $index)) {
                continue;
            }

            if ($this->isPaypalLoginBlockStart($commands, $index)) {
                $isSubscription = $this->testHasPaypalSubscriptionFlow($commands, $index);
                $lines[] = 'const paypalPage = await resolvePayPalPage(page);';
                $lines[] = sprintf(
                    'await loginAndComplete(paypalPage, config, %s);',
                    $isSubscription ? 'true' : 'false'
                );
                $lines[] = 'await assertOrderReceived(await resolveMerchantPage(page, config.host()));';
                $index = $this->skipPaypalLoginBlock($commands, $index);

                continue;
            }

            if ($this->isStripeCardBlockStart($commands, $index)) {
                $lines[] = 'await fillCard(page, config);';
                $index = $this->skipStripeCardBlock($commands, $index);

                continue;
            }

            if ($this->isPlanSelectionBlockStart($commands, $index)) {
                foreach ($this->emitPlanSelectionLines($commands, $index, $groups, $urlKey) as $line) {
                    $lines[] = $line;
                }

                $index = $this->skipPlanSelectionBlock($commands, $index);

                continue;
            }

            if ($name === 'executeScript' && $this->isFollowedByAssertTrue($commands, $index)) {
                foreach ($this->convertScriptAssertion($target) as $scriptLine) {
                    $lines[] = $scriptLine;
                }

                $index++;

                continue;
            }

            $converted = $this->convertCommand($name, $target, $value, $groups, $emailKey, $urlKey);

            if ($converted === '__PAYPAL_PAYMENT_BLOCK__') {
                array_push($lines, ...$this->selectPaypalPaymentLines());

                continue;
            }

            if ($converted !== null) {
                $lines[] = $converted;
            }
        }

        return $this->injectCheckoutPrerequisites($lines, $groups, $commands, $urlKey);
    }

    private function convertCommand(
        string $name,
        string $target,
        string $value,
        array $groups,
        string $emailKey,
        ?string $urlKey
    ): ?string {
        switch ($name) {
            case 'open':
                if ($urlKey === null) {
                    throw new RuntimeException('Unable to resolve URL key from open command.');
                }

                return sprintf("await page.goto(config.pagePath('%s'));", $urlKey);

            case 'setWindowSize':
                return null;

            case 'click':
                if ($target === 'xpath=//span[contains(text(), "Order Now")]') {
                    return "await page.locator('xpath=//span[contains(text(), \"Order Now\")]').click();";
                }

                if ($this->isPaypalPaymentSelectionTarget($target)) {
                    return '__PAYPAL_PAYMENT_BLOCK__';
                }

                return sprintf('await page.locator(%s).click();', $this->playwrightLocator($target));

            case 'type':
                if ($this->isStripeIframeSelector($target)) {
                    return null;
                }

                if (preg_match('/^css=#(first_name|last_name|email|phone|company)$/', $target, $matches)) {
                    $field = $matches[1];
                    $fillValue = $this->emailFillExpression($field, $groups, $emailKey, $urlKey);

                    return sprintf("await page.locator('#%s').fill(%s);", $field, $fillValue);
                }

                if ($target === 'css=input[placeholder="Amount"]') {
                    return sprintf(
                        "await fillCustomPrice(page, config.var('%s'));",
                        $this->variableNameFromPlaceholder($value)
                    );
                }

                return sprintf(
                    'await page.locator(%s).fill(config.var(%s));',
                    $this->playwrightLocator($target),
                    var_export($this->variableNameFromPlaceholder($value), true)
                );

            case 'assertElementPresent':
                if ($target === 'css=div > h4' || $target === 'css=h4.noBottom') {
                    return null;
                }

                $timeout = in_array('paypal', $groups, true) ? '30_000' : '10_000';

                return sprintf(
                    'await page.locator(%s).waitFor({ state: \'visible\', timeout: %s });',
                    $this->playwrightLocator($target),
                    $timeout
                );

            case 'executeScript':
            case 'assert':
                return null;

            default:
                $this->unsupported[] = $name;

                return sprintf('// TODO: unsupported command %s', $name);
        }
    }

    /**
     * @return list<string>
     */
    private function convertScriptAssertion(string $script): array
    {
        if (str_contains($script, "Thank you. We've received your order.")) {
            return ['await assertOrderReceived(page);'];
        }

        if (str_contains($script, 'Choose a way to pay')) {
            return [];
        }

        $this->unsupported[] = 'executeScript:' . substr($script, 0, 80);

        return [
            sprintf('await page.evaluate(%s);', var_export($script, true)),
            '// TODO: review script assertion above',
        ];
    }

    /**
     * @param list<array<string, mixed>> $commands
     * @param list<string> $groups
     * @return list<string>
     */
    private function emitPlanSelectionLines(array $commands, int $index, array $groups, ?string $urlKey): array
    {
        $analysis = $this->analyzePlanSelection($commands, $index, $urlKey);
        $isPaypal = in_array('paypal', $groups, true);
        $isStripe = in_array('stripe', $groups, true);
        $waitTimeout = $isPaypal && !$analysis['isSubscription'] ? '30_000' : '10_000';
        $lines = [];

        if ($analysis['saleItem'] !== null) {
            $planVar = $analysis['saleItem'] === 1 ? 'firstPlan' : 'secondPlan';
            $itemIndex = $analysis['saleItem'];

            if ($itemIndex === 1) {
                $lines[] = "const firstPlan = page.locator('.ppcart-section > div.item:nth-of-type(1)');";
                $lines[] = sprintf("await firstPlan.waitFor({ state: 'visible', timeout: %s });", $waitTimeout);
            } else {
                $lines[] = sprintf(
                    "await page.locator('.ppcart-section > div.item:nth-of-type(1)').waitFor({ state: 'visible', timeout: %s });",
                    $waitTimeout
                );
                $lines[] = "const secondPlan = page.locator('.ppcart-section > div.item:nth-of-type(2)');";
                $lines[] = sprintf("await secondPlan.waitFor({ state: 'visible', timeout: %s });", $waitTimeout);
            }

            $lines[] = sprintf('await %s.locator(\'xpath=.//span[contains(text(), "off")]\').click();', $planVar);
            $lines[] = sprintf('await %s.locator(\'.price > s\').click();', $planVar);
            $lines[] = sprintf("await %s.locator('.price').waitFor({ state: 'visible', timeout: %s });", $planVar, $waitTimeout);

            if ($isPaypal && !$analysis['isSubscription'] || $analysis['saleItem'] === 2) {
                $lines[] = sprintf("await %s.locator('label > .item-name').click();", $planVar);
            }

            if ($isPaypal && !$analysis['isSubscription']) {
                $lines[] = 'await waitForPositiveCheckoutAmount(page);';
            }

            return $lines;
        }

        if ($analysis['onePaymentClicks'] >= 2) {
            if ($isStripe) {
                return $this->collectRawPlanClicks($commands, $index);
            }

            $lines[] = sprintf(
                "await page.locator('.ppcart-section > div.item:nth-of-type(1)').waitFor({ state: 'visible', timeout: %s });",
                $waitTimeout
            );
            $lines[] = sprintf(
                "await page.locator('.ppcart-section > div.item:nth-of-type(2)').waitFor({ state: 'visible', timeout: %s });",
                $waitTimeout
            );
            $lines[] = "await page.locator('div.item:nth-of-type(1) > label > .item-name').click();";
            $lines[] = "await page.locator('div.item:nth-of-type(2) > label > .item-name').click();";
            $lines[] = "await page.locator('div.item:nth-of-type(2) > label > .item-name').click();";
            $lines[] = 'await waitForPositiveCheckoutAmount(page);';

            return $lines;
        }

        if ($analysis['hasMonthly'] && $analysis['hasAnnually']) {
            $lines[] = sprintf(
                "await page.locator('.ppcart-section > div.item:nth-of-type(1)').waitFor({ state: 'visible', timeout: %s });",
                $waitTimeout
            );
            $lines[] = sprintf(
                "await page.locator('.ppcart-section > div.item:nth-of-type(2)').waitFor({ state: 'visible', timeout: %s });",
                $waitTimeout
            );
            $lines[] = "await page.locator('xpath=//span[contains(text(), \"Monthly\")]').click();";
            $lines[] = "await page.locator('xpath=//span[contains(text(), \"Annually\")]').click();";

            if ($isStripe && $analysis['annuallyClicks'] >= 2) {
                $lines[] = "await page.locator('xpath=//span[contains(text(), \"Annually\")]').click();";
            }

            return $lines;
        }

        if ($analysis['itemNameClicks'] !== []) {
            $lines[] = sprintf(
                "await page.locator('.ppcart-section > div.item:nth-of-type(1)').waitFor({ state: 'visible', timeout: %s });",
                $waitTimeout
            );
            $lines[] = "await page.locator('div.item:nth-of-type(1) > label > .item-name').click();";

            if ($isPaypal) {
                $lines[] = 'await waitForPositiveCheckoutAmount(page);';
            }

            return $lines;
        }

        foreach ($this->collectRawPlanClicks($commands, $index) as $clickLine) {
            $lines[] = $clickLine;
        }

        return $lines;
    }

    /**
     * @param list<array<string, mixed>> $commands
     * @return list<string>
     */
    private function collectRawPlanClicks(array $commands, int $index): array
    {
        $lines = [];
        $count = count($commands);

        for ($i = $index; $i < $count; $i++) {
            $command = $commands[$i];
            $name = (string) ($command['command'] ?? '');
            $target = (string) ($command['target'] ?? '');

            if ($name === 'click' && ($target === 'css=#first_name' || $target === 'css=#last_name')) {
                break;
            }

            if ($name === 'type' && $target === 'css=#first_name') {
                break;
            }

            if ($name === 'assertElementPresent') {
                $timeout = '30_000';
                $lines[] = sprintf(
                    'await page.locator(%s).waitFor({ state: \'visible\', timeout: %s });',
                    $this->playwrightLocator($target),
                    $timeout
                );

                continue;
            }

            if ($name === 'click') {
                $lines[] = sprintf('await page.locator(%s).click();', $this->playwrightLocator($target));
            }
        }

        return $lines;
    }

    /**
     * @param list<array<string, mixed>> $commands
     * @return array{saleItem: ?int, onePaymentClicks: int, hasMonthly: bool, hasAnnually: bool, annuallyClicks: int, itemNameClicks: list<int>, isSubscription: bool}
     */
    private function analyzePlanSelection(array $commands, int $index, ?string $urlKey): array
    {
        $saleItem = null;
        $onePaymentClicks = 0;
        $hasMonthly = false;
        $hasAnnually = false;
        $annuallyClicks = 0;
        $itemNameClicks = [];
        $isSubscription = $urlKey !== null && str_contains($urlKey, 'subs_');

        for ($i = $index, $count = count($commands); $i < $count; $i++) {
            $command = $commands[$i];
            $name = (string) ($command['command'] ?? '');
            $target = (string) ($command['target'] ?? '');

            if ($name === 'click' && ($target === 'css=#first_name' || $target === 'css=#last_name')) {
                break;
            }

            if ($name === 'type' && $target === 'css=#first_name') {
                break;
            }

            if ($name === 'click' && str_contains($target, 'xpath=//span[contains(text(), "off")]')) {
                if (str_contains($target, 'nth-of-type(2)') || str_contains($target, 'item:nth-of-type(2)')) {
                    $saleItem = 2;
                } else {
                    $saleItem = 1;
                }
            }

            if ($name === 'click' && preg_match('/css=div\.item:nth-of-type\((\d+)\) > \.price > s/', $target, $matches)) {
                $saleItem = (int) $matches[1];
            }

            if ($name === 'click' && str_contains($target, 'One payment of $')) {
                $onePaymentClicks++;
            }

            if ($name === 'click' && str_contains($target, 'Monthly')) {
                $hasMonthly = true;
            }

            if ($name === 'click' && str_contains($target, 'Annually')) {
                $hasAnnually = true;
                $annuallyClicks++;
            }

            if ($name === 'click' && preg_match('/css=div\.item:nth-of-type\((\d+)\) > label > \.item-name/', $target, $matches)) {
                $itemNameClicks[] = (int) $matches[1];
            }
        }

        return [
            'saleItem' => $saleItem,
            'onePaymentClicks' => $onePaymentClicks,
            'hasMonthly' => $hasMonthly,
            'hasAnnually' => $hasAnnually,
            'annuallyClicks' => $annuallyClicks,
            'itemNameClicks' => $itemNameClicks,
            'isSubscription' => $isSubscription,
        ];
    }

    /**
     * @param list<string> $lines
     * @param list<string> $groups
     * @param list<array<string, mixed>> $commands
     * @return list<string>
     */
    private function injectCheckoutPrerequisites(array $lines, array $groups, array $commands, ?string $urlKey): array
    {
        $openTarget = $this->extractOpenTarget($commands);
        $hasPlanLines = false;
        $hasWaitForAmount = false;
        $result = [];

        foreach ($lines as $line) {
            if (str_contains($line, 'div.item:nth-of-type(') || str_contains($line, 'firstPlan') || str_contains($line, 'secondPlan')) {
                $hasPlanLines = true;
            }
        }

        foreach ($lines as $line) {
            if (str_starts_with($line, 'await page.goto(') && !$hasPlanLines && $openTarget !== null) {
                $result[] = $line;

                if (str_contains($openTarget, 'url_product_free')) {
                    $result[] = "await page.locator('div.item:nth-of-type(1) > label > .item-name').click();";
                    $hasPlanLines = true;
                } elseif (
                    in_array('paypal', $groups, true)
                    && str_contains($openTarget, 'url_one_time_paypal')
                    && !str_contains($openTarget, 'sale')
                ) {
                    $result[] = "await page.locator('.ppcart-section > div.item:nth-of-type(1)').waitFor({ state: 'visible', timeout: 30_000 });";
                    $result[] = "await page.locator('div.item:nth-of-type(1) > label > .item-name').click();";
                    $result[] = 'await waitForPositiveCheckoutAmount(page);';
                    $hasPlanLines = true;
                }

                continue;
            }

            $result[] = $line;

            if (
                str_contains($line, "Order Now\")]').click();")
                && in_array('paypal', $groups, true)
                && $this->paypalNeedsWaitForAmount($urlKey)
                && !$hasWaitForAmount
            ) {
                array_splice($result, count($result) - 1, 0, ['await waitForPositiveCheckoutAmount(page);']);
                $hasWaitForAmount = true;
            }
        }

        return $result;
    }

    private function paypalNeedsWaitForAmount(?string $urlKey): bool
    {
        if ($urlKey === null) {
            return false;
        }

        if (str_contains($urlKey, 'subs_') || str_contains($urlKey, 'custom_price')) {
            return false;
        }

        return true;
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
            'assertOrderReceived' => str_contains($body, 'assertOrderReceived('),
            'fillCustomPrice' => str_contains($body, 'fillCustomPrice('),
            'waitForPositiveCheckoutAmount' => str_contains($body, 'waitForPositiveCheckoutAmount('),
            'fillCard' => str_contains($body, 'fillCard('),
            'loginAndComplete' => str_contains($body, 'loginAndComplete('),
            'resolvePayPalPage' => str_contains($body, 'resolvePayPalPage('),
            'resolveMerchantPage' => str_contains($body, 'resolveMerchantPage('),
            'skipUnlessPaypal' => in_array('paypal', $groups, true),
            'skipUnlessRegressionUrl' => true,
        ];
    }

    /**
     * @param list<string> $groups
     * @param array<string, bool> $usedHelpers
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

        if ($usedHelpers['assertOrderReceived']) {
            $checkoutImports[] = 'assertOrderReceived';
        }

        if ($usedHelpers['fillCustomPrice']) {
            $checkoutImports[] = 'fillCustomPrice';
        }

        if ($usedHelpers['waitForPositiveCheckoutAmount']) {
            $checkoutImports[] = 'waitForPositiveCheckoutAmount';
        }

        if ($checkoutImports !== []) {
            $imports[] = 'import { ' . implode(', ', $checkoutImports) . " } from '../support/checkout-flow-steps';";
            $imports[] = '';
        }

        if ($usedHelpers['loginAndComplete']) {
            $imports[] = "import { loginAndComplete } from '../support/paypal-steps';";
        }

        if ($usedHelpers['resolvePayPalPage'] || $usedHelpers['resolveMerchantPage']) {
            $resolveImports = [];

            if ($usedHelpers['resolveMerchantPage']) {
                $resolveImports[] = 'resolveMerchantPage';
            }

            if ($usedHelpers['resolvePayPalPage']) {
                $resolveImports[] = 'resolvePayPalPage';
            }

            $imports[] = 'import { ' . implode(', ', $resolveImports) . " } from '../support/paypal-page';";
        }

        $imports[] = "import { RegressionConfig } from '../support/regression-config';";

        $skipImports = [];

        if ($usedHelpers['skipUnlessRegressionUrl']) {
            $skipImports[] = 'skipUnlessRegressionUrl';
        }

        if ($usedHelpers['skipUnlessPaypal']) {
            $skipImports[] = 'skipUnlessPaypal';
        }

        if ($skipImports !== []) {
            $imports[] = 'import { ' . implode(', ', $skipImports) . " } from '../support/regression-skip';";
        }

        if ($usedHelpers['fillCard']) {
            $imports[] = "import { fillCard } from '../support/stripe-steps';";
        }

        $importBlock = implode("\n", $imports);
        $body = implode("\n", array_map(static fn (string $line): string => '  ' . $line, $lines));
        $urlKeyLiteral = $urlKey ?? 'url_one_time_stripe';
        $skipLines = "  skipUnlessRegressionUrl(config, '{$urlKeyLiteral}');";

        if ($usedHelpers['skipUnlessPaypal']) {
            $skipLines .= "\n  skipUnlessPaypal(config);";
        }

        return <<<TS
{$importBlock}

const config = RegressionConfig.fromEnv();

test('{$title}', { tag: [{$tagList}] }, async ({ page }) => {
  test.setTimeout({$timeout});
{$skipLines}

{$body}
});

TS;
    }

    /**
     * @return list<string>
     */
    private function selectPaypalPaymentLines(): array
    {
        return [
            "await page.locator('.pay-methods').waitFor({ state: 'visible', timeout: 30_000 });",
            "const paypalMethod = page.locator('.pay-methods #method-paypal');",
            'if (!(await paypalMethod.isChecked())) {',
            "  await page.locator('.pay-methods label:has(#method-paypal)').click();",
            '}',
        ];
    }

    /**
     * @param list<string> $groups
     */
    private function emailFillExpression(string $field, array $groups, string $emailKey, ?string $urlKey): string
    {
        if ($field !== 'email') {
            return sprintf("config.var('%s')", $field);
        }

        if (in_array('stripe', $groups, true) || in_array('free', $groups, true)) {
            return sprintf("config.uniqueEmail('%s')", $emailKey);
        }

        if (in_array('paypal', $groups, true)) {
            if ($urlKey !== null && (str_contains($urlKey, 'subs_') || str_contains($urlKey, 'custom_price'))) {
                return "config.var('email')";
            }

            return sprintf("config.uniqueEmail('%s')", $emailKey);
        }

        return "config.var('email')";
    }

    private function playwrightLocator(string $target): string
    {
        if (str_starts_with($target, 'css=')) {
            return var_export(substr($target, 4), true);
        }

        return var_export($target, true);
    }

    private function isStripeIframeSelector(string $target): bool
    {
        return str_contains($target, 'js.stripe.com');
    }

    private function isPaypalPaymentSelectionTarget(string $target): bool
    {
        return in_array($target, self::PAYPAL_PAYMENT_LABEL_TARGETS, true);
    }

    /**
     * @param list<array<string, mixed>> $commands
     */
    private function isFollowedByTypeOnSameTarget(array $commands, int $index): bool
    {
        $next = $commands[$index + 1] ?? null;

        if ($next === null) {
            return false;
        }

        return ($next['command'] ?? '') === 'type'
            && ($next['target'] ?? '') === ($commands[$index]['target'] ?? '');
    }

    /**
     * @param list<array<string, mixed>> $commands
     */
    private function isStripeCardBlockStart(array $commands, int $index): bool
    {
        $command = $commands[$index];
        $target = (string) ($command['target'] ?? '');

        if (($command['command'] ?? '') === 'click' && str_contains($target, 'cardnumber')) {
            return true;
        }

        return ($command['command'] ?? '') === 'type' && $this->isStripeIframeSelector($target);
    }

    /**
     * @param list<array<string, mixed>> $commands
     */
    private function skipStripeCardBlock(array $commands, int $index): int
    {
        $count = count($commands);

        while ($index + 1 < $count) {
            $next = $commands[$index + 1];
            $target = (string) ($next['target'] ?? '');

            if (($next['command'] ?? '') === 'type' && $this->isStripeIframeSelector($target)) {
                $index++;

                continue;
            }

            break;
        }

        return $index;
    }

    /**
     * @param list<array<string, mixed>> $commands
     */
    private function isPlanSelectionBlockStart(array $commands, int $index): bool
    {
        $command = $commands[$index];
        $name = (string) ($command['command'] ?? '');
        $target = (string) ($command['target'] ?? '');

        if ($name === 'assertElementPresent' && str_contains($target, '.ppcart-section > div.item')) {
            return true;
        }

        if ($name !== 'click') {
            return false;
        }

        if (str_contains($target, 'div.item:nth-of-type(') && str_contains($target, '> label > .item-name')) {
            return true;
        }

        if (str_contains($target, 'One payment of $')) {
            return true;
        }

        return str_contains($target, 'xpath=//span[contains(text(), "off")]')
            || (str_contains($target, 'div.item:nth-of-type(') && str_contains($target, '> .price > s'));
    }

    /**
     * @param list<array<string, mixed>> $commands
     */
    private function skipPlanSelectionBlock(array $commands, int $index): int
    {
        $count = count($commands);

        while ($index < $count) {
            $command = $commands[$index];
            $name = (string) ($command['command'] ?? '');
            $target = (string) ($command['target'] ?? '');

            if ($name === 'click' && ($target === 'css=#first_name' || $target === 'css=#last_name')) {
                return $index;
            }

            if ($name === 'type' && $target === 'css=#first_name') {
                return $index;
            }

            $index++;
        }

        return $count - 1;
    }

    /**
     * @param list<array<string, mixed>> $commands
     */
    private function isPaypalLoginBlockStart(array $commands, int $index): bool
    {
        $command = $commands[$index];
        $name = (string) ($command['command'] ?? '');
        $target = (string) ($command['target'] ?? '');
        $value = (string) ($command['value'] ?? '');

        if ($name === 'type' && $value === '${paypal_username}') {
            return true;
        }

        return $name === 'click'
            && $target === 'css=#email'
            && $this->isPaypalEmailFillStep($commands, $index);
    }

    /**
     * @param list<array<string, mixed>> $commands
     */
    private function isPaypalEmailFillStep(array $commands, int $index): bool
    {
        $next = $commands[$index + 1] ?? null;

        if ($next === null) {
            return false;
        }

        return ($next['command'] ?? '') === 'type'
            && ($next['value'] ?? '') === '${paypal_username}';
    }

    /**
     * @param list<array<string, mixed>> $commands
     */
    private function skipPaypalLoginBlock(array $commands, int $index): int
    {
        $count = count($commands);

        while ($index < $count) {
            $next = $commands[$index + 1] ?? null;

            if ($next === null) {
                return $index;
            }

            $command = (string) ($next['command'] ?? '');
            $target = (string) ($next['target'] ?? '');

            if ($command === 'assertElementPresent' && $target === 'css=div > h4') {
                $index += 3;

                return min($index, $count - 1);
            }

            if ($command === 'executeScript' && str_contains($target, "Thank you. We've received your order.")) {
                $afterScript = $commands[$index + 2] ?? null;

                if ($afterScript !== null && ($afterScript['command'] ?? '') === 'assert') {
                    return $index + 2;
                }

                return $index + 1;
            }

            $index++;
        }

        return $index;
    }

    /**
     * @param list<array<string, mixed>> $commands
     */
    private function testHasPaypalSubscriptionFlow(array $commands, int $index): bool
    {
        $count = count($commands);

        for ($i = $index; $i < $count; $i++) {
            $command = $commands[$i];
            $name = (string) ($command['command'] ?? '');
            $target = (string) ($command['target'] ?? '');

            if ($name === 'executeScript' && str_contains($target, 'Choose a way to pay')) {
                return true;
            }

            if ($name === 'assertElementPresent' && $target === 'css=h4.noBottom') {
                return true;
            }

            if ($name === 'click' && $target === 'css=[data-test-id="continueButton"]') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<array<string, mixed>> $commands
     */
    private function isFollowedByAssertTrue(array $commands, int $index): bool
    {
        $next = $commands[$index + 1] ?? null;

        if ($next === null) {
            return false;
        }

        return ($next['command'] ?? '') === 'assert'
            && ($next['target'] ?? '') === 'checkReturnValue'
            && ($next['value'] ?? '') === 'true';
    }

    /**
     * @param list<array<string, mixed>> $commands
     */
    private function extractOpenTarget(array $commands): ?string
    {
        foreach ($commands as $command) {
            if (($command['command'] ?? '') === 'open') {
                return (string) ($command['target'] ?? '');
            }
        }

        return null;
    }

    /**
     * @param list<array<string, mixed>> $commands
     */
    private function extractUrlKey(array $commands): ?string
    {
        $openTarget = $this->extractOpenTarget($commands);

        if ($openTarget === null) {
            return null;
        }

        if (preg_match('/\$\{([a-z0-9_]+)\}$/i', $openTarget, $matches)) {
            return $matches[1];
        }

        throw new RuntimeException(sprintf('Unable to parse URL key from open target: %s', $openTarget));
    }

    private function variableNameFromPlaceholder(string $value): string
    {
        if (preg_match('/^\$\{([a-z0-9_]+)\}$/', $value, $matches)) {
            return $matches[1];
        }

        throw new RuntimeException(sprintf('Unsupported placeholder: %s', $value));
    }

    private function emailKeyFromTest(string $name): string
    {
        if (preg_match('/TC-(\d+)/i', $name, $matches)) {
            return 'rt' . str_pad($matches[1], 3, '0', STR_PAD_LEFT);
        }

        return strtolower((string) preg_replace('/[^a-z0-9]+/', '-', $name));
    }

    private function playwrightTitleFromTest(string $name): string
    {
        if (preg_match('/TC-(\d+)/i', $name, $matches)) {
            $code = 'RT-' . str_pad($matches[1], 3, '0', STR_PAD_LEFT);
            $slug = preg_replace('/^\[StudioCart\]\s*TC-\d+\s*Critical\s*-\s*/i', '', $name) ?? $name;
            $slug = preg_replace('/\s*-\s*/', ' ', $slug);
            $slug = strtolower(trim($slug));
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
            $slug = preg_replace('/^\[StudioCart\]\s*TC-\d+\s*Critical\s*-\s*/i', '', $name) ?? $name;
            $slug = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $slug));
            $slug = trim($slug, '-');

            return "rt{$num}-{$slug}.spec.ts";
        }

        $slug = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $name));

        return trim($slug, '-') . '.spec.ts';
    }

    /**
     * @return list<string>
     */
    private function groupsFromTest(string $name): array
    {
        $groups = [];

        if (stripos($name, 'stripe') !== false) {
            $groups[] = 'stripe';
        }

        if (stripos($name, 'paypal') !== false) {
            $groups[] = 'paypal';
        }

        if (stripos($name, 'free') !== false) {
            $groups[] = 'free';
        }

        $groups[] = 'regression';

        return $groups;
    }
}

$options = getopt('', ['source::', 'output::', 'test::']);

if (PHP_SAPI === 'cli' && realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) {
    $repoRoot = dirname(__DIR__, 2);
    $source = $options['source'] ?? $repoRoot . '/tests/playwright/regression/original.side';
    $output = $options['output'] ?? $repoRoot . '/tests/playwright/regression';
    $test = $options['test'] ?? null;

    $converter = new SideToPlaywrightConverter($source, $output);
    $files = $converter->convert($test);

    echo sprintf("Generated %d Playwright spec(s):\n", count($files));

    foreach ($files as $file) {
        echo "  - {$file}\n";
    }
}
