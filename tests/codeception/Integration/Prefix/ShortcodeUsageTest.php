<?php

declare(strict_types=1);

namespace Tests\Integration\Prefix;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Public_Asset_Controller;

class ShortcodeUsageTest extends WPTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    /**
     * @var array<int, int>
     */
    private $postIds = [];

    protected function tearDown(): void
    {
        foreach ($this->postIds as $postId) {
            wp_delete_post($postId, true);
        }

        unset($GLOBALS['post']);

        parent::tearDown();
    }

    /**
     * @test-id IT-283
     */
    public function test_IT_283_confirmation_expands_ppcart_receipt(): void
    {
        $this->assertTrue(shortcode_exists('ppcart_receipt'));
        $this->assertStringNotContainsString('[ppcart_receipt]', (string) do_shortcode('[ppcart_receipt]'));
    }

    /**
     * @test-id IT-283
     */
    public function test_IT_283_enqueue_treats_canonical_form_tag_as_a_hit(): void
    {
        $canonicalId = $this->insertPage('[ppcart_form id="1"]');
        $this->assertTrue($this->assetsNeededForPost($canonicalId));
    }

    /**
     * @param string $content Post content.
     * @return int
     */
    private function insertPage($content)
    {
        $id = (int) wp_insert_post(
            [
                'post_title'   => 'Shortcode usage',
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_content' => $content,
            ]
        );
        $this->postIds[] = $id;

        return $id;
    }

    /**
     * @param int $postId Post ID.
     * @return bool
     */
    private function assetsNeededForPost($postId)
    {
        $GLOBALS['post'] = get_post($postId);

        $controller = new PPCart_Public_Asset_Controller();

        return $controller->frontend_assets_needed();
    }
}
