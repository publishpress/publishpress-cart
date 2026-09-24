<?php

declare(strict_types=1);

namespace Tests\Integration\StripeSync;

use PPCart_Activator;
use PPCart_Admin_Subscription_Controller;
use Tests\Support\Integration\StripeSyncTestCase;

class SubscriptionSyncAjaxCapabilitiesTest extends StripeSyncTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists('PPCart_Activator', false)) {
            require_once PPCART_PLUGIN_ROOT . 'includes/class-ppcart-activator.php';
        }

        PPCart_Activator::add_cap();
    }

    public function test_IT_363_author_cannot_sync_subscription_with_valid_nonce(): void
    {
        $stripeSubscriptionId = 'sub_capability_author';
        $this->createStripeSubscription([ 'subscription_id' => $stripeSubscriptionId ]);
        $authorId = $this->factory()->user->create([ 'role' => 'author' ]);
        wp_set_current_user($authorId);

        $response = $this->requestSubscriptionSync($stripeSubscriptionId);

        $this->assertFalse($response['success']);
        $this->assertSame('Unauthorized access.', $response['data']['message']);
    }

    public function test_IT_363_manager_option_alone_cannot_sync_subscription_without_edit_post_cap(): void
    {
        $stripeSubscriptionId = 'sub_capability_manager_option_only';
        $this->createStripeSubscription([ 'subscription_id' => $stripeSubscriptionId ]);
        $authorId = $this->factory()->user->create([ 'role' => 'author' ]);
        $user = get_user_by('id', $authorId);
        $user->add_cap(ppcart_live_cap('manager_option'));
        wp_set_current_user($authorId);

        $response = $this->requestSubscriptionSync($stripeSubscriptionId);

        $this->assertFalse($response['success']);
        $this->assertSame('Unauthorized access.', $response['data']['message']);
    }

    public function test_IT_363_cart_manager_can_reach_subscription_sync_layer(): void
    {
        $stripeSubscriptionId = 'sub_capability_cart_manager';
        $this->createStripeSubscription([ 'subscription_id' => $stripeSubscriptionId ]);
        $managerId = $this->factory()->user->create([ 'role' => ppcart_live_role('cart_manager') ]);
        wp_set_current_user($managerId);

        $response = $this->requestSubscriptionSync($stripeSubscriptionId);

        $this->assertFalse($response['success']);
        $this->assertSame('Stripe secret key missing.', $response['data']['message']);
    }

    public function test_IT_363_subscription_editor_without_manager_option_is_unauthorized(): void
    {
        $stripeSubscriptionId = 'sub_capability_cpt_edit_only';
        $subscriptionPostId = $this->createStripeSubscription([ 'subscription_id' => $stripeSubscriptionId ]);
        $userId = $this->factory()->user->create([ 'role' => 'author' ]);
        $user = get_user_by('id', $userId);
        $type = ppcart_live_post_type('subscription');
        $user->add_cap("edit_{$type}s");
        $user->add_cap("edit_others_{$type}s");
        $user->add_cap("edit_published_{$type}s");
        wp_set_current_user($userId);

        $this->assertTrue(current_user_can('edit_post', $subscriptionPostId));
        $this->assertFalse(current_user_can('manage_options'));
        $this->assertFalse(ppcart_user_can('manager_option'));

        $response = $this->requestSubscriptionSync($stripeSubscriptionId);

        $this->assertFalse($response['success']);
        $this->assertSame('Unauthorized access.', $response['data']['message']);
    }

    public function test_IT_363_query_string_sync_does_not_mutate_subscription(): void
    {
        $subscriptionPostId = $this->createStripeSubscription([ 'subscription_id' => 'sub_capability_get' ]);
        $statusBefore = ppcart_get_post_meta($subscriptionPostId, 'status', true);
        $managerId = $this->factory()->user->create([ 'role' => ppcart_live_role('cart_manager') ]);
        wp_set_current_user($managerId);

        $post = get_post($subscriptionPostId);
        $this->assertInstanceOf(\WP_Post::class, $post);

        $_GET['post'] = (string) $subscriptionPostId;
        $_GET['ppcart-subscription-sync'] = '1';

        ob_start();
        try {
            $controller = new PPCart_Admin_Subscription_Controller('ppcart', 'Cart', 'test');
            $controller->subscription_info_callback($post);
        } finally {
            ob_end_clean();
            unset($_GET['post'], $_GET['ppcart-subscription-sync']);
        }

        $this->assertSame($statusBefore, ppcart_get_post_meta($subscriptionPostId, 'status', true));
    }

    /**
     * @param string $stripeSubscriptionId
     * @return array<string, mixed>
     */
    private function requestSubscriptionSync(string $stripeSubscriptionId): array
    {
        $_POST = [
            'nonce'                  => wp_create_nonce('ppcart_ajax_nonce'),
            'stripe_subscription_id' => $stripeSubscriptionId,
        ];
        $_REQUEST['nonce'] = $_POST['nonce'];
        $_REQUEST['stripe_subscription_id'] = $_POST['stripe_subscription_id'];

        add_filter('wp_doing_ajax', '__return_true');
        add_filter('wp_die_ajax_handler', [ $this, 'getAjaxDieHandler' ]);

        ob_start();
        try {
            $controller = new PPCart_Admin_Subscription_Controller('ppcart', 'Cart', 'test');
            $controller->sync_subscription_ajax();
        } catch (\RuntimeException $exception) {
            $this->assertSame('wp_die', $exception->getMessage());
        } finally {
            remove_filter('wp_doing_ajax', '__return_true');
            remove_filter('wp_die_ajax_handler', [ $this, 'getAjaxDieHandler' ]);
            unset($_POST['nonce'], $_POST['stripe_subscription_id'], $_REQUEST['nonce'], $_REQUEST['stripe_subscription_id']);
        }

        $json = trim((string) ob_get_clean());
        $response = json_decode($json, true);

        $this->assertIsArray($response);

        return $response;
    }

    /**
     * @return callable
     */
    public function getAjaxDieHandler(): callable
    {
        return static function ($message = '', $title = '', $args = []): void {
            throw new \RuntimeException('wp_die');
        };
    }
}
