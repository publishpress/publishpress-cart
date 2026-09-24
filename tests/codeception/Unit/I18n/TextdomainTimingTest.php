<?php

namespace unit\I18n;

use Codeception\Test\Unit;
use Tests\Support\WordPressStubContext;
use UnitTester;

class TextdomainTimingTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var array<int, array{0: string, 1: mixed, 2: int}>
     */
    private static $loadActions = [];

    /**
     * @var array<int, string>
     */
    private static $loadOptionReads = [];

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

        if (! function_exists('ppcart_register_report_schedule_event')) {
            $actions      = [];
            $option_reads = [];
            WordPressStubContext::set(
                'add_action',
                static function ($hook, $callback, $priority = 10) use (&$actions) {
                    $actions[] = [$hook, $callback, $priority];

                    return true;
                }
            );
            WordPressStubContext::set(
                'get_option',
                static function ($option, $default = false) use (&$option_reads) {
                    $option_reads[] = (string) $option;

                    return $default;
                }
            );

            require_once PPCART_PLUGIN_ROOT . 'includes/schedule-event.php';

            self::$loadActions     = $actions;
            self::$loadOptionReads = $option_reads;
        }

        WordPressStubContext::set(
            'add_action',
            static function () {
                return true;
            }
        );
        WordPressStubContext::set(
            'get_option',
            static function ($option, $default = false) {
                return $default;
            }
        );
    }

    protected function _after(): void
    {
        WordPressStubContext::clear();
        parent::_after();
    }

    /**
     * @test-id UT-238
     */
    public function test_UT_238_report_schedule_event_is_registered_on_init_not_at_file_load(): void
    {
        if (self::$loadActions !== []) {
            $this->assertContains(
                ['init', 'ppcart_register_report_schedule_event', 10],
                self::$loadActions
            );
            $this->assertNotContains(
                'ppcart_report_schedule',
                self::$loadOptionReads,
                'Report cron registration must not run while schedule-event.php is included'
            );
        }

        $translated = [];
        WordPressStubContext::set(
            '__',
            static function ($text, $domain = 'default') use (&$translated) {
                $translated[] = [(string) $text, (string) $domain];

                return $text;
            }
        );
        WordPressStubContext::set(
            'did_action',
            static function () {
                return 0;
            }
        );

        $schedules = ppcart_email_schedule_hook([]);

        $this->assertSame([], $translated, 'Cron labels must not be translated before init');
        $this->assertSame('Daily', $schedules['ppcart_daily']['display']);
        $this->assertSame('Weekly', $schedules['ppcart_weekly']['display']);
        $this->assertSame('Semi Monthly', $schedules['ppcart_semi_monthly']['display']);

        WordPressStubContext::set(
            'did_action',
            static function ($hook) {
                return 'init' === $hook ? 1 : 0;
            }
        );

        $schedules = ppcart_email_schedule_hook([]);

        $this->assertContains(['Daily', 'publishpress-cart'], $translated);
        $this->assertSame('Daily', $schedules['ppcart_daily']['display']);
    }
}
