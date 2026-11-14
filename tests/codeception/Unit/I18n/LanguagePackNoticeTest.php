<?php

namespace unit\I18n;

use Codeception\Test\Unit;
use Tests\Support\WordPressStubContext;

class LanguagePackNoticeTest extends Unit
{
    protected function _before(): void
    {
        WordPressStubContext::clear();
        WordPressStubContext::set('add_filter', static function () { return true; });
        WordPressStubContext::set('get_current_screen', static function () {
            return WordPressStubContext::getState('screen');
        });
        WordPressStubContext::set('get_admin_page_parent', static function () { return ''; });
        WordPressStubContext::set('get_post_type', static function () { return ''; });
        $_GET = [];
        $_POST = [];
        require_once PPCART_PLUGIN_ROOT . 'includes/class-ppcart-admin-screens.php';
        require_once PPCART_PLUGIN_ROOT . 'includes/helpers/ppcart-language-pack-notice.php';
    }

    protected function _after(): void
    {
        WordPressStubContext::clear();
    }

    public function testNoticePreservesOtherPluginsScreensAndOnlyRegistersItsOwnDomain(): void
    {
        WordPressStubContext::setState('screen', (object) ['id' => 'ppcart_page_ppcart-settings', 'base' => 'ppcart_page_ppcart-settings']);
        $this->assertSame(['other-plugin', 'ppcart_page_ppcart-settings'], ppcart_language_pack_notice_screens(['other-plugin'], 'publishpress-cart'));
        $this->assertSame(['other-plugin'], ppcart_language_pack_notice_screens(['other-plugin'], 'another-domain'));
    }

    public function testNoticeDoesNotRegisterWordPressDashboard(): void
    {
        WordPressStubContext::setState('screen', (object) ['id' => 'dashboard', 'base' => 'dashboard']);
        $this->assertSame(['other-plugin'], ppcart_language_pack_notice_screens(['other-plugin'], 'publishpress-cart'));
    }
}
