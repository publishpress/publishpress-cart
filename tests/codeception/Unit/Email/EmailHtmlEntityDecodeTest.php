<?php

namespace {
    if (! defined('PPCART_BASE_DIR')) {
        define('PPCART_BASE_DIR', PPCART_PLUGIN_ROOT);
    }

    if (! function_exists('esc_attr')) {
        /**
         * @param mixed $text Text to escape.
         * @return string
         */
        function esc_attr($text)
        {
            return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
        }
    }

    if (! function_exists('wpautop')) {
        /**
         * @param mixed $text Text to wrap.
         * @return string
         */
        function wpautop($text)
        {
            return (string) $text;
        }
    }

    if (! function_exists('ppcart_locate_theme_template')) {
        /**
         * @param string $relative Template path under public/templates/.
         * @return string
         */
        function ppcart_locate_theme_template($relative)
        {
            return '';
        }
    }

    if (! function_exists('ppcart_helper')) {
        /**
         * @return PPCart_Helper
         */
        function ppcart_helper()
        {
            return PPCart_Helper::instance();
        }
    }
}

namespace unit\Email {

use Codeception\Test\Unit;
use PPCart_Helper;
use Tests\Support\WordPressStubContext;
use UnitTester;

class EmailHtmlEntityDecodeTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    protected function _before(): void
    {
        WordPressStubContext::clear();

        if (! class_exists(\PPCart_Order_Helper::class, false)) {
            require_once PPCART_PLUGIN_ROOT . 'includes/helpers/class-ppcart-order-helper.php';
        }
        if (! class_exists(PPCart_Helper::class, false)) {
            require_once PPCART_PLUGIN_ROOT . 'includes/helpers/class-ppcart-helper.php';
        }
        if (! function_exists('ppcart_kses_email_html')) {
            require_once PPCART_PLUGIN_ROOT . 'includes/email/ppcart-template-functions/templates.php';
        }
        if (! function_exists('ppcart_personalize')) {
            require_once PPCART_PLUGIN_ROOT . 'includes/functions/merge-tags-and-dates.php';
        }
        if (! function_exists('ppcart_get_email_html')) {
            require_once PPCART_PLUGIN_ROOT . 'includes/functions/users-and-notifications.php';
        }

        WordPressStubContext::set(
            'add_action',
            static function () {
                return true;
            }
        );
        WordPressStubContext::set(
            'remove_action',
            static function () {
                return true;
            }
        );
        WordPressStubContext::set(
            'do_action',
            static function () {
                return null;
            }
        );
        WordPressStubContext::set(
            'apply_filters',
            static function ($hook_name, $value) {
                return $value;
            }
        );
        WordPressStubContext::set(
            'get_bloginfo',
            static function () {
                return 'Test Site';
            }
        );
        WordPressStubContext::set(
            'wp_kses_allowed_html',
            static function () {
                return array(
                    'a' => array(
                        'href' => true,
                        'target' => true,
                        'rel' => true,
                    ),
                    'span' => array(
                        'style' => true,
                    ),
                    'style' => array(
                        'type' => true,
                    ),
                );
            }
        );
        WordPressStubContext::set(
            'wp_kses',
            static function ($html) {
                return (string) $html;
            }
        );
        WordPressStubContext::set(
            'get_option',
            static function ($option, $default = false) {
                $options = WordPressStubContext::getState('options', array());

                return array_key_exists($option, $options) ? $options[$option] : $default;
            }
        );
        WordPressStubContext::setState(
            'options',
            array(
                '_ppcart_company_logo' => '',
                '_ppcart_company_name' => 'Acme',
                '_ppcart_email_footer_text' => '{publishpress_cart} &mdash; {customer_firstname}',
            )
        );
    }

    protected function _after(): void
    {
        WordPressStubContext::clear();
        parent::_after();
    }

    /**
     * @test-id UT-355
     */
    public function test_UT_355_rendered_email_keeps_markup_tags_and_escapes_customer_text(): void
    {
        $html = ppcart_get_email_html(
            array(
                'type' => 'registration',
                'order_info' => array(
                    'firstname' => 'A & B <a href="https://evil.test">x</a>',
                    'lastname' => 'Buyer',
                    'email' => 'buyer@example.test',
                    'phone' => '',
                ),
                'headline' => '',
                'body' => 'Thanks for registering.',
            )
        );

        $this->assertStringContainsString(
            'href="https://publishpress.com/publishpress-cart/"',
            $html
        );
        $this->assertStringContainsString('&mdash;', $html);
        $this->assertStringNotContainsString('&amp;mdash;', $html);
        $this->assertStringNotContainsString('href="https://evil.test"', $html);
        $this->assertStringContainsString('A &amp; B', $html);
        $this->assertStringContainsString('&lt;a href=', $html);
    }
}
}