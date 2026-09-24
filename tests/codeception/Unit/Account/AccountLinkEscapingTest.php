<?php

namespace unit\Account;

use Codeception\Test\Unit;
use ReflectionProperty;
use Tests\Support\WordPressStubContext;
use UnitTester;

class AccountLinkEscapingTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var int
     */
    private $currentPostId = 1;

    /**
     * @var int
     */
    private $accountPageId = 99;

    protected function _before(): void
    {
        WordPressStubContext::clear();
        $this->currentPostId = 1;
        $this->accountPageId = 99;

        WordPressStubContext::set(
            'add_action',
            static function () {
                return true;
            }
        );
        WordPressStubContext::set(
            'add_filter',
            static function () {
                return true;
            }
        );
        WordPressStubContext::set(
            'add_shortcode',
            static function () {
                return true;
            }
        );
        WordPressStubContext::set(
            'get_the_ID',
            function () {
                return $this->currentPostId;
            }
        );
        WordPressStubContext::set(
            'get_option',
            function ($option, $default = false) {
                if ('_ppcart_myaccount_page_id' === $option) {
                    return $this->accountPageId;
                }

                return $default;
            }
        );

        if (! class_exists(\PPCart_Public_Account_Controller::class, false)) {
            require_once PPCART_PLUGIN_ROOT . 'public/controllers/class-ppcart-public-account-controller.php';
        }
    }

    protected function _after(): void
    {
        WordPressStubContext::clear();
        parent::_after();
    }

    /**
     * @test-id UT-327
     */
    public function test_UT_327_account_link_shortcode_escapes_url_and_label(): void
    {
        $controller = new \PPCart_Public_Account_Controller();
        $urlProperty = new ReflectionProperty($controller, 'my_account_url');
        $urlProperty->setAccessible(true);
        $urlProperty->setValue($controller, 'https://example.com/account?"onclick=alert(1)');

        $html = $controller->my_account_page_link_shortcode(
            ['label' => '<img src=x onerror=alert(1)>']
        );

        $this->assertSame(
            '<a href="https://example.com/account?%22onclick=alert(1)">&lt;img src=x onerror=alert(1)&gt;</a>',
            $html
        );
    }

    /**
     * @test-id UT-327
     */
    public function test_UT_327_lost_password_link_escapes_label_and_href(): void
    {
        $this->currentPostId = $this->accountPageId;

        $controller = new \PPCart_Public_Account_Controller();
        $html = $controller->add_lost_password_link();

        $this->assertSame(
            '<a href="?action=lostpassword">Forgot your password?</a>',
            $html
        );
    }

    /**
     * @test-id UT-327
     */
    public function test_UT_327_account_link_shortcode_uses_default_label_when_omitted(): void
    {
        $controller = new \PPCart_Public_Account_Controller();
        $urlProperty = new ReflectionProperty($controller, 'my_account_url');
        $urlProperty->setAccessible(true);
        $urlProperty->setValue($controller, 'https://example.com/account');

        $html = $controller->my_account_page_link_shortcode([]);

        $this->assertSame(
            '<a href="https://example.com/account">My Account</a>',
            $html
        );
    }

    /**
     * @test-id UT-327
     */
    public function test_UT_327_account_link_shortcode_returns_false_without_account_url(): void
    {
        $controller = new \PPCart_Public_Account_Controller();
        $urlProperty = new ReflectionProperty($controller, 'my_account_url');
        $urlProperty->setAccessible(true);
        $urlProperty->setValue($controller, false);

        $this->assertFalse($controller->my_account_page_link_shortcode(['label' => 'Account']));
    }
}
