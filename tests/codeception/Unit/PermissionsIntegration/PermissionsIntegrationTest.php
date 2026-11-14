<?php

namespace unit\PermissionsIntegration;

use Codeception\Test\Unit;
use PublishPress\Cart\Permissions;
use Tests\Support\WordPressStubContext;

class PermissionsIntegrationTest extends Unit
{
    private $integration;
    private $membershipCalls;
    private $customerCalls;
    private $customerResult;

    protected function _before(): void
    {
        require_once __DIR__ . '/PermissionsStub.php';
        require_once PPCART_PLUGIN_ROOT . 'includes/integrations/Permissions.php';
        WordPressStubContext::setState('permissions_available', true);
        WordPressStubContext::setState('permissions_groups', [
            5 => (object) ['ID' => 5, 'name' => 'Customers', 'metagroup_type' => '', 'metagroup_id' => ''],
            6 => (object) ['ID' => 6, 'name' => 'Administrators', 'metagroup_type' => 'wp_role', 'metagroup_id' => 'administrator'],
        ]);
        WordPressStubContext::set('add_action', function () { return true; });
        WordPressStubContext::set('add_filter', function () { return true; });
        $this->membershipCalls = [];
        $this->customerCalls = [];
        $this->customerResult = 42;
        WordPressStubContext::set('permissions_membership', function (...$args) {
            $this->membershipCalls[] = $args;
            return true;
        });
        WordPressStubContext::set('permissions_order_user', function ($order, $create) {
            $this->customerCalls[] = [$order, $create];
            return $this->customerResult;
        });
        $this->integration = new Permissions();
    }

    protected function _after(): void
    {
        WordPressStubContext::clear();
        parent::_after();
    }

    private function settings($action = 'add')
    {
        return ['services' => 'publishpress_permissions', 'permissions_action' => $action, 'permissions_group' => '5'];
    }

    private function order()
    {
        return ['id' => 100, 'email' => 'buyer@example.test', 'first_name' => 'Buyer', 'last_name' => 'Test', 'phone' => '', 'plan_id' => 'gold', 'option_id' => '', 'amount' => 20];
    }

    public function testRegistersOnlyForActivePermissionsAndListsOnlyCustomGroups(): void
    {
        $this->assertArrayHasKey('publishpress_permissions', $this->integration->add_service([]));
        $fields = $this->integration->add_integration_fields([1 => ['fields' => []]], false);
        $this->assertSame([0 => 'add', 1 => 'remove'], array_keys($fields[1]['fields'][0]['select']['selections']));
        $this->assertArrayHasKey(5, $fields[1]['fields'][1]['select']['selections']);
        $this->assertArrayNotHasKey(6, $fields[1]['fields'][1]['select']['selections']);
        WordPressStubContext::setState('permissions_available', false);
        $this->assertSame([], $this->integration->add_service([]));
        $this->assertFalse($this->integration->update_membership($this->settings(), 200, $this->order()));
        $this->assertSame([], $this->membershipCalls);
    }

    public function testPurchaseAddsTheOrderCustomerAndAllowsNormalAccountCreation(): void
    {
        $this->assertTrue($this->integration->update_membership($this->settings(), 200, $this->order()));
        $this->assertSame([['add', 5, [42]]], $this->membershipCalls);
        $this->assertSame([[$this->order(), true]], $this->customerCalls);
    }

    public function testRemovalDoesNotCreateACustomerAccount(): void
    {
        $this->assertTrue($this->integration->update_membership($this->settings('remove'), 200, $this->order()));
        $this->assertSame([['remove', 5, [42]]], $this->membershipCalls);
        $this->assertSame([[$this->order(), false]], $this->customerCalls);
    }

    public function testInvalidDeletedOrRoleGroupsCannotGrantMembership(): void
    {
        foreach (['', '0', '999', '6', '5garbage', ['5']] as $group) {
            $settings = $this->settings();
            $settings['permissions_group'] = $group;
            $this->assertFalse($this->integration->update_membership($settings, 200, $this->order()));
        }
        $this->assertFalse($this->integration->update_membership($this->settings('unknown'), 200, $this->order()));
        $this->assertSame([], $this->membershipCalls);
        $this->assertSame([], $this->customerCalls);
    }

    public function testMissingOrFailedCustomerResolutionCannotGrantMembership(): void
    {
        foreach ([false, 0, 'not-a-user'] as $result) {
            $this->customerResult = $result;
            $this->assertFalse($this->integration->update_membership($this->settings(), 200, $this->order()));
        }
        $this->assertFalse($this->integration->update_membership($this->settings(), 200, ['id' => 100]));
        $this->assertSame([], $this->membershipCalls);
    }

    public function testFieldsRemainRegisteredForSavingWhenPermissionsIsInactive(): void
    {
        WordPressStubContext::setState('permissions_available', false);
        $fields = $this->integration->add_integration_fields([1 => ['fields' => []]], true);
        $this->assertSame('permissions_group', $fields[1]['fields'][1]['select']['id']);
        $this->assertSame([], $fields[1]['fields'][1]['select']['selections']);
    }

    public function testDispatcherRequiresTheConfiguredPurchaseTriggerAndPaymentPlan(): void
    {
        require_once PPCART_PLUGIN_ROOT . 'includes/helpers/ppcart-meta.php';
        require_once PPCART_PLUGIN_ROOT . 'includes/functions/integrations-and-stock.php';
        $settings = array_merge($this->settings(), ['service_trigger' => ['purchased'], 'int_plan' => ['gold']]);
        WordPressStubContext::set('get_post_meta', function ($id, $key) use ($settings) {
            return 200 === $id && '_ppcart_integrations' === $key ? [$settings] : '';
        });
        WordPressStubContext::set('do_action', function ($hook, ...$args) {
            if ('ppcart_publishpress_permissions_integrations' === $hook) {
                $this->integration->update_membership(...$args);
            }
        });
        ppcart_do_integrations(200, $this->order(), 'pending');
        ppcart_do_integrations(200, array_merge($this->order(), ['plan_id' => 'silver']), 'purchased');
        $this->assertSame([], $this->membershipCalls);
        ppcart_do_integrations(200, $this->order(), 'purchased');
        $this->assertSame([['add', 5, [42]]], $this->membershipCalls);
    }
}
