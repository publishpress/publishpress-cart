<?php

namespace {
    if (! function_exists('add_settings_field')) {
        /**
         * @param mixed ...$args Settings field arguments.
         * @return mixed
         */
        function add_settings_field(...$args)
        {
            return Tests\Support\WordPressStubContext::invoke('add_settings_field', $args);
        }
    }

    if (! function_exists('register_setting')) {
        /**
         * @param mixed ...$args Setting registration arguments.
         * @return mixed
         */
        function register_setting(...$args)
        {
            return Tests\Support\WordPressStubContext::invoke('register_setting', $args);
        }
    }
}

namespace unit\Email {

use Codeception\Test\Unit;
use Tests\Support\WordPressStubContext;
use UnitTester;

class EmailBodySanitizeCallbackTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    protected function _before(): void
    {
        if (! function_exists('ppcart_kses_email_html')) {
            require_once PPCART_PLUGIN_ROOT . 'includes/email/ppcart-template-functions/templates.php';
        }

        WordPressStubContext::set(
            'apply_filters',
            static function ($hook_name, $value) {
                return $value;
            }
        );
        WordPressStubContext::set(
            'add_settings_field',
            static function () {
                return true;
            }
        );
        WordPressStubContext::setState('registered_settings', array());
        WordPressStubContext::set(
            'register_setting',
            static function ($option_group, $option_name, $args = array()) {
                $registered = WordPressStubContext::getState('registered_settings', array());
                $registered[$option_name] = $args;
                WordPressStubContext::setState('registered_settings', $registered);

                return true;
            }
        );
    }

    /**
     * @test-id UT-222
     */
    public function test_UT_222_email_body_settings_register_ppcart_kses_email_html(): void
    {
        $registrar = new class {
            /**
             * @var string
             */
            public $plugin_name = 'ppcart';

            /**
             * @return array
             */
            public function get_options_list()
            {
                return array(
                    'email' => array(
                        'confirmation_body' => array(
                            'type'     => 'editor',
                            'label'    => 'Body',
                            'settings' => array(
                                'id'                    => '_ppcart_email_confirmation_body',
                                'email_template_field'  => 'body',
                            ),
                        ),
                        'confirmation_subject' => array(
                            'type'     => 'text',
                            'label'    => 'Subject',
                            'settings' => array(
                                'id'                   => '_ppcart_email_confirmation_subject',
                                'email_template_field' => 'subject',
                            ),
                        ),
                    ),
                );
            }

            /**
             * @return mixed
             */
            public function register_fields()
            {
                return include PPCART_PLUGIN_ROOT . 'admin/settings/traits/templates/settings-fields-register-fields.php';
            }
        };

        $registrar->register_fields();

        $registered = WordPressStubContext::getState('registered_settings', array());

        $this->assertSame(
            'ppcart_kses_email_html',
            $registered['_ppcart_email_confirmation_body']['sanitize_callback'] ?? null
        );
        $this->assertTrue(is_callable('ppcart_kses_email_html'));
        $this->assertSame(
            'sanitize_text_field',
            $registered['_ppcart_email_confirmation_subject']['sanitize_callback'] ?? null
        );
        $this->assertNotSame(
            'sc_kses_email_html',
            $registered['_ppcart_email_confirmation_body']['sanitize_callback'] ?? null
        );
    }
}

}
