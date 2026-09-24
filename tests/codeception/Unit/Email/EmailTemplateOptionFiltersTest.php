<?php

namespace unit\Email;

use Codeception\Test\Unit;
use Tests\Support\WordPressStubContext;
use UnitTester;

class EmailTemplateOptionFiltersTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var bool
     */
    private static $bootstrapped = false;

    protected function _before(): void
    {
        if (! defined('WPINC')) {
            define('WPINC', 'wp-includes');
        }

        if (! self::$bootstrapped) {
            $this->bootstrapEmailTemplateStubs();
            self::$bootstrapped = true;
        } else {
            WordPressStubContext::setState(
                'options',
                array(
                    'admin_email' => 'admin@example.test',
                )
            );
        }

        $this->mockOptionDatabase();
    }

    protected function _after(): void
    {
        unset($GLOBALS['wpdb']);
        parent::_after();
    }

    /**
     * @return void
     */
    private function bootstrapEmailTemplateStubs(): void
    {
        WordPressStubContext::setState('filters', array());
        WordPressStubContext::setState('actions', array());
        WordPressStubContext::setState('did_actions', array());
        WordPressStubContext::setState('requested_options', array());
        WordPressStubContext::setState(
            'options',
            array(
                'admin_email' => 'admin@example.test',
            )
        );
        WordPressStubContext::setState('translation_calls', 0);

        WordPressStubContext::set(
            'add_filter',
            function ($hook_name, $callback, $priority = 10, $accepted_args = 1) {
                $filters = WordPressStubContext::getState('filters', array());

                if (! isset($filters[$hook_name])) {
                    $filters[$hook_name] = array();
                }
                if (! isset($filters[$hook_name][$priority])) {
                    $filters[$hook_name][$priority] = array();
                }

                $filters[$hook_name][$priority][] = array(
                    'callback' => $callback,
                    'accepted_args' => $accepted_args,
                );

                WordPressStubContext::setState('filters', $filters);

                return true;
            }
        );
        WordPressStubContext::set(
            'add_action',
            function ($hook_name, $callback, $priority = 10, $accepted_args = 1) {
                $actions = WordPressStubContext::getState('actions', array());

                if (! isset($actions[$hook_name])) {
                    $actions[$hook_name] = array();
                }
                if (! isset($actions[$hook_name][$priority])) {
                    $actions[$hook_name][$priority] = array();
                }

                $actions[$hook_name][$priority][] = array(
                    'callback' => $callback,
                    'accepted_args' => $accepted_args,
                );

                WordPressStubContext::setState('actions', $actions);

                return true;
            }
        );
        WordPressStubContext::set(
            'apply_filters',
            function ($hook_name, $value) {
                $filters = WordPressStubContext::getState('filters', array());

                if (empty($filters[$hook_name])) {
                    return $value;
                }

                $args = array_merge(array($value), array_slice(func_get_args(), 2));
                ksort($filters[$hook_name]);

                foreach ($filters[$hook_name] as $callbacks) {
                    foreach ($callbacks as $filter) {
                        $filter_args = array_slice($args, 0, $filter['accepted_args']);
                        $value = call_user_func_array($filter['callback'], $filter_args);
                        $args[0] = $value;
                    }
                }

                return $value;
            }
        );
        WordPressStubContext::set(
            'did_action',
            function ($hook_name) {
                $did_actions = WordPressStubContext::getState('did_actions', array());

                return isset($did_actions[$hook_name]) ? $did_actions[$hook_name] : 0;
            }
        );
        WordPressStubContext::set(
            'get_option',
            function ($option, $default = false) {
                $options = WordPressStubContext::getState('options', array());
                $requested_options = WordPressStubContext::getState('requested_options', array());
                $requested_options[] = $option;
                WordPressStubContext::setState('requested_options', $requested_options);
                $pre_option = apply_filters('pre_option', false, $option, $default);

                if (false !== $pre_option) {
                    return $pre_option;
                }

                if (array_key_exists($option, $options)) {
                    $value = $options[$option];
                } else {
                    $value = apply_filters('default_option_' . $option, $default, $option);
                }

                return apply_filters('option_' . $option, $value, $option);
            }
        );
        WordPressStubContext::set(
            'wp_kses_allowed_html',
            function ($context = '') {
                return array();
            }
        );
        WordPressStubContext::set(
            'wp_kses',
            function ($html, $allowed_html) {
                return $html;
            }
        );
        WordPressStubContext::set(
            'get_bloginfo',
            function ($show = '') {
                return 'Test Site';
            }
        );

        if (! function_exists('ppcart_email_template_option_defaults')) {
            require_once PPCART_PLUGIN_ROOT . 'includes/email/ppcart-email-template-functions.php';
        }
        if (! function_exists('ppcart_personalize')) {
            require_once PPCART_PLUGIN_ROOT . 'includes/functions/merge-tags-and-dates.php';
        }
        if (! function_exists('ppcart_notification_send')) {
            require_once PPCART_PLUGIN_ROOT . 'includes/functions/users-and-notifications.php';
        }
    }

    public function test_UT_078_file_load_registration_does_not_compute_translated_template_defaults(): void
    {
        $this->assertSame(0, WordPressStubContext::getState('translation_calls', 0));
    }

    public function test_UT_079_core_email_option_default_filter_is_registered_during_file_load(): void
    {
        $this->assertSame(
            1,
            $this->getFilterCallbackCount('default_option__ppcart_email_completed_subject')
        );
    }

    public function test_UT_080_core_email_option_value_filter_is_registered_during_file_load(): void
    {
        $this->assertSame(
            1,
            $this->getFilterCallbackCount('option__ppcart_email_completed_subject')
        );
    }

    public function test_UT_081_late_init_registration_pass_is_scheduled_at_priority_99(): void
    {
        $actions = WordPressStubContext::getState('actions', array());

        $this->assertArrayHasKey('init', $actions);
        $this->assertArrayHasKey(99, $actions['init']);
    }

    public function test_UT_082_static_email_option_names_cover_all_core_defaults_and_aliases(): void
    {
        $missing_core_option_names = array_diff(
            array_merge(
                array_keys(ppcart_email_template_option_defaults()),
                array_keys(ppcart_email_template_alias_options())
            ),
            ppcart_email_template_option_names()
        );

        $this->assertSame(array(), $missing_core_option_names);
    }

    public function test_UT_083_addon_email_option_filter_is_not_registered_before_addon_declares_option_name(): void
    {
        $this->assertSame(
            0,
            $this->getFilterCallbackCount('default_option__ppcart_email_addon_subject')
        );
    }

    public function test_UT_084_addon_email_option_default_filter_is_registered_by_late_pass(): void
    {
        add_filter(
            'ppcart_email_template_option_names',
            function ($option_names) {
                $option_names[] = '_ppcart_email_addon_subject';

                return $option_names;
            }
        );

        add_filter(
            'ppcart_email_templates',
            function ($templates) {
                $templates['addon'] = array(
                    'subject' => array(
                        'option' => '_ppcart_email_addon_subject',
                        'default' => 'Addon default',
                    ),
                );

                return $templates;
            }
        );

        ppcart_register_email_template_option_filters();

        $this->assertSame(
            1,
            $this->getFilterCallbackCount('default_option__ppcart_email_addon_subject')
        );
    }

    public function test_UT_085_re_running_registration_does_not_duplicate_existing_core_option_filters(): void
    {
        $completed_subject_filter_count = $this->getFilterCallbackCount(
            'default_option__ppcart_email_completed_subject'
        );

        ppcart_register_email_template_option_filters();

        $this->assertSame(
            $completed_subject_filter_count,
            $this->getFilterCallbackCount('default_option__ppcart_email_completed_subject')
        );
    }

    public function test_UT_086_missing_core_email_option_resolves_to_template_default(): void
    {
        $this->assertSame(
            'Your order from {site_name} is complete!',
            get_option('_ppcart_email_completed_subject')
        );
    }

    public function test_UT_087_missing_addon_email_option_resolves_to_addon_template_default(): void
    {
        add_filter(
            'ppcart_email_template_option_names',
            function ($option_names) {
                $option_names[] = '_ppcart_email_runtime_addon_subject';

                return $option_names;
            }
        );

        add_filter(
            'ppcart_email_templates',
            function ($templates) {
                $templates['runtime_addon'] = array(
                    'subject' => array(
                        'option' => '_ppcart_email_runtime_addon_subject',
                        'default' => 'Addon default',
                    ),
                );

                return $templates;
            }
        );

        ppcart_register_email_template_option_filters();

        $this->assertSame('Addon default', get_option('_ppcart_email_runtime_addon_subject'));
    }

    public function test_UT_088_stored_core_email_option_value_is_preserved(): void
    {
        $options = WordPressStubContext::getState('options', array());
        $options['_ppcart_email_completed_subject'] = 'Custom complete subject';
        WordPressStubContext::setState('options', $options);

        $this->assertSame('Custom complete subject', get_option('_ppcart_email_completed_subject'));

        $options['_ppcart_email_completed_subject'] = '';
        $options['_ppcart_email_completed_headline'] = '';
        $options['_ppcart_email_completed_body'] = '';
        WordPressStubContext::setState('options', $options);
        WordPressStubContext::setState('requested_options', array());

        add_filter(
            'ppcart_notification_email_to',
            static function () {
                throw new \RuntimeException('Notification option reads completed.');
            }
        );

        try {
            ppcart_notification_send('completed', array('email' => 'customer@example.test'), true);
            $this->fail('Expected the notification recipient filter to stop the send.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Notification option reads completed.', $exception->getMessage());
        }

        $requested_options = WordPressStubContext::getState('requested_options', array());

        $this->assertContains('_ppcart_email_completed_subject', $requested_options);
        $this->assertContains('_ppcart_email_completed_headline', $requested_options);
        $this->assertContains('_ppcart_email_completed_body', $requested_options);
        $this->assertNotContains('_sc_email_completed_subject', $requested_options);
    }

    public function test_UT_089_legacy_past_due_aliases_still_resolve_through_current_option_names(): void
    {
        $options = WordPressStubContext::getState('options', array());
        $options['_ppcart_email_failed_enable'] = '1';
        WordPressStubContext::setState('options', $options);

        $this->assertSame('1', get_option('_ppcart_email_past_due_enable'));
    }

    /**
     * @return void
     */
    private function mockOptionDatabase(): void
    {
        global $wpdb;

        // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Unit test installs a database stub.
        $wpdb = new class {
            /**
             * @var string
             */
            public $options = 'wp_options';

            public function prepare($query, ...$args)
            {
                if (isset($args[0])) {
                    return sprintf($query, "'" . $args[0] . "'");
                }

                return $query;
            }

            public function get_var($query)
            {
                $options = WordPressStubContext::getState('options', array());

                foreach ($options as $option => $value) {
                    if (false !== strpos($query, "'" . $option . "'")) {
                        return $value;
                    }
                }

                return null;
            }

            public function get_row($query, $output = null)
            {
                $value = $this->get_var($query);

                if (null === $value) {
                    return null;
                }

                return [
                    'option_name'  => '',
                    'option_value' => $value,
                    'autoload'     => 'no',
                ];
            }
        };
    }

    /**
     * @param string $hook_name
     * @return int
     */
    private function getFilterCallbackCount(string $hook_name): int
    {
        $filters = WordPressStubContext::getState('filters', array());
        $count = 0;

        if (empty($filters[$hook_name])) {
            return $count;
        }

        foreach ($filters[$hook_name] as $callbacks) {
            $count += count($callbacks);
        }

        return $count;
    }
}
