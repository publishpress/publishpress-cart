<?php

namespace unit\I18n;

use Codeception\Test\Unit;
use Tests\Support\WordPressStubContext;
use UnitTester;

class ReportSchedulePrefixTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    protected function _before(): void
    {
        WordPressStubContext::clear();
        WordPressStubContext::set(
            'add_filter',
            static function () {
                return true;
            }
        );
        WordPressStubContext::set(
            'add_action',
            static function () {
                return true;
            }
        );
        WordPressStubContext::set(
            'did_action',
            static function () {
                return 0;
            }
        );
        WordPressStubContext::set(
            'get_option',
            static function ($option, $default = false) {
                return $default;
            }
        );
        WordPressStubContext::set(
            'update_option',
            static function () {
                return true;
            }
        );

        if (! function_exists('ppcart_register_report_schedule_event')) {
            require_once PPCART_PLUGIN_ROOT . 'includes/schedule-event.php';
        }
    }

    protected function _after(): void
    {
        WordPressStubContext::clear();
        parent::_after();
    }

    /**
     * @test-id UT-352
     */
    public function test_UT_352_weekly_period_uses_seven_day_window(): void
    {
        $period = ppcart_schedule_report_period('ppcart_weekly');

        $this->assertSame(gmdate('Y-m-d', strtotime('-7 days')), $period['date1']);
        $this->assertSame(gmdate('Y-m-d', strtotime('+7 days')), $period['datenxt']);
        $this->assertSame($period, ppcart_schedule_report_period('mt_weekly'));
    }

    /**
     * @test-id UT-352
     */
    public function test_UT_352_semi_monthly_period_uses_fifteen_day_window(): void
    {
        $period = ppcart_schedule_report_period('ppcart_semi_monthly');

        $this->assertSame(gmdate('Y-m-d', strtotime('-15 days')), $period['date1']);
        $this->assertSame(gmdate('Y-m-d', strtotime('+15 days')), $period['datenxt']);
        $this->assertSame($period, ppcart_schedule_report_period('mt_semi_monthly'));
    }

    /**
     * @test-id UT-352
     */
    public function test_UT_352_daily_period_uses_one_day_window(): void
    {
        $period = ppcart_schedule_report_period('ppcart_daily');

        $this->assertSame(gmdate('Y-m-d', strtotime('-1 days')), $period['date1']);
        $this->assertSame(gmdate('Y-m-d', strtotime('+1 days')), $period['datenxt']);
        $this->assertSame($period, ppcart_schedule_report_period('mt_daily'));
    }

    /**
     * @test-id UT-352
     */
    public function test_UT_352_canonicalizer_rewrites_legacy_two_letter_keys(): void
    {
        $this->assertSame('ppcart_none', ppcart_canonical_report_schedule('mt_none'));
        $this->assertSame('ppcart_daily', ppcart_canonical_report_schedule('mt_daily'));
        $this->assertSame('ppcart_weekly', ppcart_canonical_report_schedule('mt_weekly'));
        $this->assertSame('ppcart_semi_monthly', ppcart_canonical_report_schedule('mt_semi_monthly'));
        $this->assertSame('ppcart_daily', ppcart_canonical_report_schedule('ppcart_daily'));
        $this->assertSame('hourly', ppcart_canonical_report_schedule('hourly'));
    }

    /**
     * @test-id UT-352
     */
    public function test_UT_352_register_rewrites_legacy_option_and_schedules_canonical_recurrence(): void
    {
        $this->stubScheduleStorage(
            [
                'ppcart_report_schedule'      => 'mt_daily',
                'ppcart_current_schedule_val' => 'mt_daily',
            ]
        );

        ppcart_register_report_schedule_event();

        $options = WordPressStubContext::getState('options', []);
        $this->assertSame('ppcart_daily', $options['ppcart_report_schedule']);
        $this->assertSame('ppcart_daily', $options['ppcart_current_schedule_val']);
        $this->assertContains('ppcart_email_schedule_hook', WordPressStubContext::getState('cleared', []));
        $this->assertSame(
            'ppcart_daily',
            WordPressStubContext::getState('scheduled_recurrence')
        );
    }

    /**
     * @test-id UT-352
     */
    public function test_UT_352_none_clears_scheduled_hook(): void
    {
        $this->stubScheduleStorage(
            [
                'ppcart_report_schedule'      => 'mt_none',
                'ppcart_current_schedule_val' => 'mt_daily',
            ]
        );

        ppcart_register_report_schedule_event();

        $options = WordPressStubContext::getState('options', []);
        $this->assertSame('ppcart_none', $options['ppcart_report_schedule']);
        $this->assertSame('', $options['ppcart_current_schedule_val']);
        $this->assertContains('ppcart_email_schedule_hook', WordPressStubContext::getState('cleared', []));
        $this->assertNull(WordPressStubContext::getState('scheduled_recurrence'));
    }

    /**
     * @param array<string, mixed> $options
     */
    private function stubScheduleStorage(array $options): void
    {
        WordPressStubContext::setState('options', $options);
        WordPressStubContext::setState('cleared', []);
        WordPressStubContext::setState('next_scheduled', false);
        WordPressStubContext::setState('scheduled_recurrence', null);

        WordPressStubContext::set(
            'get_option',
            static function ($option, $default = false) {
                $stored = WordPressStubContext::getState('options', []);

                return array_key_exists($option, $stored) ? $stored[ $option ] : $default;
            }
        );
        WordPressStubContext::set(
            'update_option',
            static function ($option, $value) {
                $stored            = WordPressStubContext::getState('options', []);
                $stored[ $option ] = $value;
                WordPressStubContext::setState('options', $stored);

                return true;
            }
        );
        WordPressStubContext::set(
            'wp_clear_scheduled_hook',
            static function ($hook) {
                $cleared   = WordPressStubContext::getState('cleared', []);
                $cleared[] = $hook;
                WordPressStubContext::setState('cleared', $cleared);
                WordPressStubContext::setState('next_scheduled', false);
            }
        );
        WordPressStubContext::set(
            'wp_next_scheduled',
            static function () {
                return WordPressStubContext::getState('next_scheduled', false);
            }
        );
        WordPressStubContext::set(
            'wp_schedule_event',
            static function ($timestamp, $recurrence, $hook) {
                WordPressStubContext::setState('scheduled_recurrence', $recurrence);
                WordPressStubContext::setState('scheduled_hook', $hook);
                WordPressStubContext::setState('next_scheduled', $timestamp);

                return true;
            }
        );
    }
}
