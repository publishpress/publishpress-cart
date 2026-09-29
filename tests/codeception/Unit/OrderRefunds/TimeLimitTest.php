<?php

namespace unit\OrderRefunds;

use Codeception\Test\Unit;
use Tests\Support\WordPressStubContext;
use UnitTester;

/**
 * ppcart_set_time_limit() only raises the PHP time limit; it never lowers a host limit.
 */
class TimeLimitTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var string|false
     */
    private $originalLimit;

    protected function _before(): void
    {
        foreach ([ 'add_action', 'add_filter', 'add_shortcode' ] as $function) {
            WordPressStubContext::set($function, function () {
                return true;
            });
        }
        WordPressStubContext::set('apply_filters', function ($hook, $value) {
            return $value;
        });

        // Same loader guard as SubscriptionCancellationTest, so both tests load functions.php once.
        if (! function_exists('ppcart_issue_stripe_subscription_cancellation_refund')) {
            require_once PPCART_PLUGIN_ROOT . 'includes/functions.php';
        }

        if (! function_exists('set_time_limit') || false !== strpos((string) ini_get('disable_functions'), 'set_time_limit')) {
            $this->markTestSkipped('set_time_limit() is disabled in this PHP process.');
        }

        $this->originalLimit = ini_get('max_execution_time');
    }

    protected function _after(): void
    {
        if (false !== $this->originalLimit) {
            set_time_limit((int) $this->originalLimit);
        }

        WordPressStubContext::clear();
        parent::_after();
    }

    public function test_a_lower_limit_does_not_reduce_the_host_limit(): void
    {
        $this->setHostLimitOrSkip(300);

        $this->assertTrue(ppcart_set_time_limit(60));
        $this->assertSame('300', ini_get('max_execution_time'));
    }

    public function test_a_higher_limit_raises_the_host_limit(): void
    {
        $this->setHostLimitOrSkip(30);

        $this->assertTrue(ppcart_set_time_limit(120));
        $this->assertSame('120', ini_get('max_execution_time'));
    }

    public function test_an_unlimited_host_stays_unlimited(): void
    {
        $this->setHostLimitOrSkip(0);

        $this->assertTrue(ppcart_set_time_limit(60));
        $this->assertSame('0', ini_get('max_execution_time'));
    }

    public function test_zero_removes_the_limit_only_when_asked(): void
    {
        $this->setHostLimitOrSkip(30);

        $this->assertTrue(ppcart_set_time_limit(0));
        $this->assertSame('0', ini_get('max_execution_time'));
    }

    public function test_a_negative_limit_changes_nothing(): void
    {
        $this->setHostLimitOrSkip(30);

        $this->assertFalse(ppcart_set_time_limit(-5));
        $this->assertSame('30', ini_get('max_execution_time'));
    }

    private function setHostLimitOrSkip(int $limit): void
    {
        // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged,WordPress.PHP.NoSilencedErrors.Discouraged -- The test must probe whether this PHP runtime permits changing its time limit.
        @set_time_limit($limit);

        if ((string) $limit !== ini_get('max_execution_time')) {
            $this->markTestSkipped('This PHP process does not permit changing max_execution_time.');
        }
    }
}
