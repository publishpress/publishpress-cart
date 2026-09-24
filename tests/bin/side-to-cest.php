#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Converts a Ghost Inspector Selenium IDE (.side) export into Codeception Cest files.
 *
 * Usage: php tests/bin/side-to-cest.php [--source=path] [--output=path] [--test=TC-001]
 *
 * Known .side → helper replacements (see tests/codeception/Support/Regression/README.md):
 * - css=label:nth-of-type(2) > .item-name → CheckoutFlowSteps::selectPaypalPayment()
 * - xpath=//span[contains(text(), "Order Now")] → CheckoutFlowSteps::clickOrderNow()
 * - executeScript "Thank you. We've received your order." → CheckoutFlowSteps::assertOrderReceived()
 * - css=input[placeholder="Amount"] → CheckoutFlowSteps::fillCustomPrice()
 * - PayPal login block (sandbox email/password through payment approval)
 *   → PayPalSteps::loginAndComplete($I, $config, $isSubscription)
 * - Stripe iframe card fields block → StripeSteps::fillCard() (uses STRIPE_CARD_NUMBER_SUCCESS, STRIPE_CC_EXP)
 * - css=#email on Stripe tests → $config->uniqueEmail('tcNNN') (isolates Stripe customers)
 * - Plan selection clicks / sale toggles → CheckoutFlowSteps::select*OneTimePlan() / selectFreePlan()
 * - PayPal tests → waitForPositiveCheckoutAmount() before Order Now when amount must be set
 *
 * Do not emit raw Stripe iframe type commands or raw PayPal login clicks — helpers encode
 * sandbox quirks (auto-tab CVC, optional postal, persisted PayPal sessions, etc.).
 */

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

$supportRegression = dirname(__DIR__) . '/codeception/Support/Regression';
require_once $supportRegression . '/LocatorHelper.php';

use Tests\Support\Regression\LocatorHelper;

final class SideToCestConverter
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

            if ($onlyTest !== null && stripos($testName, $onlyTest) === false) {
                continue;
            }

            $className = $this->classNameFromTest($testName);
            $methodName = $this->methodNameFromTest($testName);
            $groups = $this->groupsFromTest($testName);
            $emailKey = $this->emailKeyFromTest($testName);
            $lines = $this->convertCommands($test['commands'], $groups, $emailKey);

            $usedHelpers = [
                'CheckoutFlowSteps' => false,
                'LocatorHelper' => false,
                'PayPalSteps' => false,
                'RegressionConfig' => true,
                'StripeSteps' => false,
            ];

            foreach ($lines as $line) {
                if (str_contains($line, 'CheckoutFlowSteps::')) {
                    $usedHelpers['CheckoutFlowSteps'] = true;
                }

                if (str_contains($line, 'LocatorHelper::')) {
                    $usedHelpers['LocatorHelper'] = true;
                }

                if (str_contains($line, 'PayPalSteps::')) {
                    $usedHelpers['PayPalSteps'] = true;
                }

                if (str_contains($line, 'StripeSteps::')) {
                    $usedHelpers['StripeSteps'] = true;
                }
            }

            $content = $this->renderCest($className, $methodName, $groups, $lines, $usedHelpers);
            $path = $this->outputDir . '/' . $className . '.php';
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
     * @param list<array<string, mixed>> $commands
     * @param list<string> $groups
     * @return list<string>
     */
    private function convertCommands(array $commands, array $groups, string $emailKey): array
    {
        $lines = [
            '$config = RegressionConfig::fromEnv();',
        ];

        $count = count($commands);

        for ($index = 0; $index < $count; $index++) {
            $command = $commands[$index];
            $name = (string) ($command['command'] ?? '');
            $target = (string) ($command['target'] ?? '');
            $value = (string) ($command['value'] ?? '');

            if ($name === 'click' && $this->isFollowedByTypeOnSameTarget($commands, $index)) {
                continue;
            }

            if ($this->isPaypalLoginBlockStart($commands, $index)) {
                $isSubscription = $this->testHasPaypalSubscriptionFlow($commands, $index);
                $lines[] = sprintf(
                    'PayPalSteps::loginAndComplete($I, $config, %s);',
                    $isSubscription ? 'true' : 'false'
                );
                $index = $this->skipPaypalLoginBlock($commands, $index);

                continue;
            }

            if ($this->isStripeCardBlockStart($commands, $index)) {
                $lines[] = 'StripeSteps::fillCard($I, $config);';
                $index = $this->skipStripeCardBlock($commands, $index);

                continue;
            }

            if ($this->isPlanSelectionBlockStart($commands, $index)) {
                $helper = $this->detectPlanSelectionHelper($commands, $index);

                if ($helper !== null) {
                    $lines[] = $helper;
                }

                $index = $this->skipPlanSelectionBlock($commands, $index);

                continue;
            }

            if ($name === 'executeScript' && $this->isFollowedByAssertTrue($commands, $index)) {
                $scriptLines = $this->convertScriptAssertion($target);

                foreach ($scriptLines as $scriptLine) {
                    $lines[] = $scriptLine;
                }

                $index++;

                continue;
            }

            $converted = $this->convertCommand($name, $target, $value, $commands, $index, $groups, $emailKey);

            if ($converted !== null) {
                $lines[] = $converted;
            }
        }

        return $this->injectCheckoutPrerequisites($lines, $groups, $commands);
    }

    private function convertCommand(
        string $name,
        string $target,
        string $value,
        array $commands,
        int $index,
        array $groups,
        string $emailKey
    ): ?string {
        switch ($name) {
            case 'open':
                return sprintf('$I->amOnPage($config->pageFromOpenTarget(%s));', var_export($target, true));

            case 'setWindowSize':
                [$width, $height] = array_map('intval', explode('x', $target) + [0, 0]);

                return sprintf('$I->resizeWindow(%d, %d);', $width, $height);

            case 'click':
                if ($target === 'xpath=//span[contains(text(), "Order Now")]') {
                    return 'CheckoutFlowSteps::clickOrderNow($I);';
                }

                if ($this->isPaypalPaymentSelectionTarget($target)) {
                    return 'CheckoutFlowSteps::selectPaypalPayment($I);';
                }

                if ($this->isPaypalLoginStepCommand($name, $target, $value)) {
                    return null;
                }

                return sprintf(
                    '$I->click(%s);',
                    LocatorHelper::exportPhp(LocatorHelper::fromSide($target))
                );

            case 'type':
                if (LocatorHelper::isStripeIframeSelector($target)) {
                    return null;
                }

                if ($this->isPaypalLoginStepCommand($name, $target, $value)) {
                    return null;
                }

                if (preg_match('/^css=#(first_name|last_name|email|phone|company)$/', $target)) {
                    $field = substr($target, 5);

                    if ($field === 'email' && $this->usesUniqueCustomerEmail($groups)) {
                        return sprintf(
                            '$I->fillField(%s, $config->uniqueEmail(%s));',
                            var_export('#email', true),
                            var_export($emailKey, true)
                        );
                    }

                    return sprintf(
                        '$I->fillField(%s, $config->var(%s));',
                        var_export('#' . $field, true),
                        var_export($this->variableNameFromPlaceholder($value), true)
                    );
                }

                if ($target === 'css=input[placeholder="Amount"]') {
                    return sprintf(
                        'CheckoutFlowSteps::fillCustomPrice($I, $config->var(%s));',
                        var_export($this->variableNameFromPlaceholder($value), true)
                    );
                }

                return sprintf(
                    '$I->fillField(%s, $config->resolve(%s));',
                    LocatorHelper::exportPhp(LocatorHelper::fromSide($target)),
                    var_export($value, true)
                );

            case 'assertElementPresent':
                if ($target === 'css=div > h4') {
                    return null;
                }

                if ($target === 'css=h4.noBottom') {
                    return null;
                }

                return sprintf(
                    '$I->waitForElement(%s, 10);',
                    LocatorHelper::exportPhp(LocatorHelper::fromSide($target))
                );

            case 'executeScript':
                return null;

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
            return ['CheckoutFlowSteps::assertOrderReceived($I);'];
        }

        if (str_contains($script, 'Choose a way to pay')) {
            return [];
        }

        $this->unsupported[] = 'executeScript:' . substr($script, 0, 80);

        return [
            sprintf('$I->executeJS(%s);', var_export($script, true)),
            '// TODO: review script assertion above',
        ];
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

        if (($command['command'] ?? '') === 'type' && LocatorHelper::isStripeIframeSelector($target)) {
            return true;
        }

        return false;
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

            if (($next['command'] ?? '') === 'type' && LocatorHelper::isStripeIframeSelector($target)) {
                $index++;

                continue;
            }

            break;
        }

        return $index;
    }

    private function isPaypalPaymentSelectionTarget(string $target): bool
    {
        return in_array($target, self::PAYPAL_PAYMENT_LABEL_TARGETS, true);
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
            || str_contains($target, 'div.item:nth-of-type(') && str_contains($target, '> .price > s');
    }

    /**
     * @param list<array<string, mixed>> $commands
     */
    private function detectPlanSelectionHelper(array $commands, int $index): ?string
    {
        $count = count($commands);
        $hasOff = false;
        $saleItem = null;
        $onePaymentClicks = 0;
        $itemNameClicks = [];

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

            if ($name === 'click' && str_contains($target, 'xpath=//span[contains(text(), "off")]')) {
                $hasOff = true;
            }

            if ($name === 'click' && preg_match('/css=div\.item:nth-of-type\((\d+)\) > \.price > s/', $target, $matches)) {
                $saleItem = (int) $matches[1];
            }

            if ($name === 'click' && str_contains($target, 'One payment of $')) {
                $onePaymentClicks++;
            }

            if ($name === 'click' && preg_match('/css=div\.item:nth-of-type\((\d+)\) > label > \.item-name/', $target, $matches)) {
                $itemNameClicks[] = (int) $matches[1];
            }
        }

        if ($hasOff && $saleItem === 2) {
            return 'CheckoutFlowSteps::selectSecondarySaleOneTimePlan($I);';
        }

        if ($hasOff && $saleItem === 1) {
            return 'CheckoutFlowSteps::selectFirstSaleOneTimePlan($I);';
        }

        if ($onePaymentClicks >= 2 || in_array(2, $itemNameClicks, true)) {
            return 'CheckoutFlowSteps::selectSecondaryOneTimePlan($I);';
        }

        if ($itemNameClicks !== []) {
            return 'CheckoutFlowSteps::selectFirstOneTimePlan($I);';
        }

        return null;
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
     * @param list<string> $lines
     * @param list<string> $groups
     * @param list<array<string, mixed>> $commands
     * @return list<string>
     */
    private function injectCheckoutPrerequisites(array $lines, array $groups, array $commands): array
    {
        $openTarget = $this->extractOpenTarget($commands);
        $hasPlanHelper = false;
        $hasWaitForAmount = false;
        $result = [];

        foreach ($lines as $line) {
            if (str_contains($line, 'CheckoutFlowSteps::select') && str_contains($line, 'Plan($I)')) {
                $hasPlanHelper = true;
            }
        }

        foreach ($lines as $line) {
            $result[] = $line;

            if (str_starts_with($line, '$I->resizeWindow(') && !$hasPlanHelper && $openTarget !== null) {
                if (str_contains($openTarget, 'url_product_free')) {
                    $result[] = 'CheckoutFlowSteps::selectFreePlan($I);';
                    $hasPlanHelper = true;
                } elseif (
                    in_array('paypal', $groups, true)
                    && str_contains($openTarget, 'url_one_time_paypal')
                    && !str_contains($openTarget, 'sale')
                ) {
                    $result[] = 'CheckoutFlowSteps::selectFirstOneTimePlan($I);';
                    $hasPlanHelper = true;
                }
            }

            if (
                $line === 'CheckoutFlowSteps::clickOrderNow($I);'
                && in_array('paypal', $groups, true)
                && !$hasWaitForAmount
            ) {
                array_splice($result, count($result) - 1, 0, ['CheckoutFlowSteps::waitForPositiveCheckoutAmount($I);']);
                $hasWaitForAmount = true;
            }
        }

        return $result;
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

    private function isPaypalLoginStepCommand(string $command, string $target, string $value): bool
    {
        if ($command === 'type') {
            return in_array($value, ['${paypal_username}', '${paypal_password}'], true);
        }

        if ($command !== 'click') {
            return false;
        }

        if (in_array($target, self::PAYPAL_LOGIN_CLICK_TARGETS, true)) {
            return true;
        }

        return false;
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
    private function isFollowedByPaypalSubscriptionScript(array $commands, int $index): bool
    {
        $next = $commands[$index + 1] ?? null;

        if ($next === null) {
            return false;
        }

        return ($next['command'] ?? '') === 'executeScript'
            && str_contains((string) ($next['target'] ?? ''), 'Choose a way to pay');
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

        while ($index < $count - 1) {
            $next = $commands[$index + 1];
            $command = (string) ($next['command'] ?? '');
            $target = (string) ($next['target'] ?? '');

            if (
                ($command === 'assertElementPresent' && $target === 'css=div > h4')
                || ($command === 'executeScript' && str_contains($target, "Thank you. We've received your order."))
            ) {
                break;
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
            return 'tc' . str_pad($matches[1], 3, '0', STR_PAD_LEFT);
        }

        return strtolower((string) preg_replace('/[^a-z0-9]+/', '-', $name));
    }

    /**
     * @param list<string> $groups
     */
    private function isStripeGroup(array $groups): bool
    {
        return in_array('stripe', $groups, true);
    }

    /**
     * @param list<string> $groups
     */
    private function usesUniqueCustomerEmail(array $groups): bool
    {
        foreach (['stripe', 'paypal', 'free'] as $group) {
            if (in_array($group, $groups, true)) {
                return true;
            }
        }

        return false;
    }

    private function classNameFromTest(string $name): string
    {
        $number = '000';

        if (preg_match('/TC-(\d+)/', $name, $matches)) {
            $number = str_pad($matches[1], 3, '0', STR_PAD_LEFT);
        }

        $slug = preg_replace('/^\[StudioCart\]\s*TC-\d+\s*Critical\s*-\s*/', '', $name) ?? $name;
        $slug = preg_replace('/[^a-zA-Z0-9]+/', '', $slug) ?? $slug;

        return 'Tc' . $number . $slug . 'Cest';
    }

    private function methodNameFromTest(string $name): string
    {
        $class = $this->classNameFromTest($name);

        return lcfirst(substr($class, 0, -4));
    }

    /**
     * @return list<string>
     */
    private function groupsFromTest(string $name): array
    {
        $groups = ['regression'];

        if (stripos($name, 'stripe') !== false) {
            $groups[] = 'stripe';
        }

        if (stripos($name, 'paypal') !== false) {
            $groups[] = 'paypal';
        }

        if (stripos($name, 'free') !== false) {
            $groups[] = 'free';
        }

        return $groups;
    }

    /**
     * @param list<string> $groups
     * @param list<string> $lines
     * @param array<string, bool> $usedHelpers
     */
    private function renderCest(
        string $className,
        string $methodName,
        array $groups,
        array $lines,
        array $usedHelpers
    ): string {
        $imports = [];

        if ($usedHelpers['CheckoutFlowSteps']) {
            $imports[] = 'use Tests\Support\Regression\CheckoutFlowSteps;';
        }

        if ($usedHelpers['LocatorHelper']) {
            $imports[] = 'use Tests\Support\Regression\LocatorHelper;';
        }

        if ($usedHelpers['PayPalSteps']) {
            $imports[] = 'use Tests\Support\Regression\PayPalSteps;';
        }

        if ($usedHelpers['RegressionConfig']) {
            $imports[] = 'use Tests\Support\Regression\RegressionConfig;';
        }

        if ($usedHelpers['StripeSteps']) {
            $imports[] = 'use Tests\Support\Regression\StripeSteps;';
        }

        $imports[] = 'use Tests\Support\RegressionTester;';
        $importBlock = implode("\n", $imports);
        $groupDoc = '';

        foreach ($groups as $group) {
            $groupDoc .= "     * @group {$group}\n";
        }

        $body = implode("\n        ", $lines);

        return <<<PHP
<?php

declare(strict_types=1);

namespace Tests\Regression;

{$importBlock}

/**
 * AUTO-GENERATED from Ghost Inspector .side export. Do not edit manually.
 * Re-run: php tests/bin/side-to-cest.php
 * Conventions: tests/codeception/Support/Regression/README.md
 */
final class {$className}
{
    /**
{$groupDoc}     */
    public function {$methodName}(RegressionTester \$I): void
    {
        {$body}
    }
}

PHP;
    }
}

$options = getopt('', ['source::', 'output::', 'test::']);

if (PHP_SAPI === 'cli' && realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) {
    $repoRoot = dirname(__DIR__, 2);
    $source = $options['source'] ?? $repoRoot . '/tests/codeception/Regression/source/full-regression.side';
    $output = $options['output'] ?? $repoRoot . '/tests/codeception/Regression';
    $test = $options['test'] ?? null;

    $converter = new SideToCestConverter($source, $output);
    $files = $converter->convert($test);

    echo sprintf("Generated %d Cest file(s):\n", count($files));

    foreach ($files as $file) {
        echo "  - {$file}\n";
    }
}
