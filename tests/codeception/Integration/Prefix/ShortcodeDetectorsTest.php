<?php

declare(strict_types=1);

namespace Tests\Integration\Prefix;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Public_Asset_Controller;

class ShortcodeDetectorsTest extends WPTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    /**
     * @var array<int, int>
     */
    private $postIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->isolateAssetDetectionContext();
    }

    protected function tearDown(): void
    {
        foreach ($this->postIds as $postId) {
            wp_delete_post($postId, true);
        }

        unset($GLOBALS['post']);
        $this->isolateAssetDetectionContext();

        parent::tearDown();
    }

    /**
     * @test-id IT-343
     */
    public function test_IT_343_leftover_form_tag_does_not_trip_frontend_assets(): void
    {
        $id = $this->insertPage('[studiocart-form id="1"]');
        $this->assertFalse($this->assetsNeededForPost($id));
    }

    /**
     * @param string $content Post content.
     * @return int
     */
    private function insertPage($content)
    {
        $id = (int) wp_insert_post(
            [
                'post_title'   => 'Shortcode detectors',
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
        $this->isolateAssetDetectionContext();
        $GLOBALS['post'] = get_post($postId);

        $controller = new PPCart_Public_Asset_Controller();

        return $controller->frontend_assets_needed();
    }

    /**
     * Earlier integration tests may leave product globals or checkout query args.
     *
     * @return void
     */
    private function isolateAssetDetectionContext()
    {
        unset($GLOBALS['ppcart_product']);
        unset(
            $_GET['ppcart-order'],
            $_GET['ppcart-pid'],
            $_GET['ppcart-oto'],
            $_GET['ppcart-preview'],
            $_GET['ppcart-plan']
        );
        unset($_POST['ppcart_purchase_amount']);
        delete_option('_ppcart_compatibility_mode');
    }
}
