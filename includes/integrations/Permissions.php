<?php

namespace PublishPress\Cart;

use PublishPress\Permissions\API as PermissionsAPI;

\defined('ABSPATH') || exit;

/** Product-triggered membership in PublishPress Permissions custom groups. */
class Permissions
{
    private const SERVICE = 'publishpress_permissions';

    public function __construct()
    {
        add_action('plugins_loaded', [$this, 'init'], 20);
    }

    public function init()
    {
        add_filter('ppcart_integrations', [$this, 'add_service']);
        add_filter('ppcart_integration_fields', [$this, 'add_integration_fields'], 10, 2);
        add_action('ppcart_publishpress_permissions_integrations', [$this, 'update_membership'], 10, 3);
    }

    private function is_available()
    {
        return function_exists('presspermit') && presspermit() && class_exists(PermissionsAPI::class);
    }

    public function add_service($options)
    {
        if ($this->is_available()) {
            $options[self::SERVICE] = __('PublishPress Permissions', 'publishpress-cart');
        }

        return $options;
    }

    public function add_integration_fields($fields, $save)
    {
        $condition = [['field' => 'services', 'value' => self::SERVICE, 'compare' => '=']];
        $fields[1]['fields'][] = [
            'select' => [
                'id' => 'permissions_action',
                'label' => __('Group membership action', 'publishpress-cart'),
                'type' => 'select',
                'value' => 'add',
                'class' => 'widefat required',
                'selections' => [
                    'add' => __('Add to group', 'publishpress-cart'),
                    'remove' => __('Remove from group', 'publishpress-cart'),
                ],
                'description' => __('Use Product Purchased to add membership. A separate refund or cancellation integration can remove it.', 'publishpress-cart'),
                'conditional_logic' => $condition,
            ],
        ];
        $fields[1]['fields'][] = [
            'select' => [
                'id' => 'permissions_group',
                'label' => __('Permission Group', 'publishpress-cart'),
                'type' => 'select',
                'value' => '',
                'class' => 'widefat required',
                'selections' => $save ? [] : $this->get_group_choices(),
                'description' => __('Custom Permission Groups only. Adding a guest buyer creates a customer account using the normal Cart account workflow.', 'publishpress-cart'),
                'conditional_logic' => $condition,
            ],
        ];

        return $fields;
    }

    private function get_group_choices()
    {
        $choices = ['' => __('Select a Permission Group', 'publishpress-cart')];
        if (!$this->is_available()) {
            return $choices;
        }

        foreach (PermissionsAPI::getGroups('pp_group', ['metagroup_type' => '']) as $group) {
            if ($this->is_custom_group($group)) {
                $choices[(int) $group->ID] = $group->group_name ?? $group->name;
            }
        }

        return $choices;
    }

    private function is_custom_group($group)
    {
        return is_object($group) && !empty($group->ID)
            && empty($group->metagroup_type) && empty($group->metagroup_id);
    }

    /**
     * The integration dispatcher has already matched the saved product, trigger and payment plan.
     * Use the order customer, never the currently logged-in administrator or webhook user.
     */
    public function update_membership($integration, $product_id, $order)
    {
        if (!$this->is_available() || !is_array($integration) || !is_array($order)) {
            return false;
        }

        $action = $integration['permissions_action'] ?? 'add';
        $raw_group_id = $integration['permissions_group'] ?? '';
        if (!in_array($action, ['add', 'remove'], true) || !is_scalar($raw_group_id) || !ctype_digit((string) $raw_group_id)) {
            return false;
        }

        $group_id = absint($raw_group_id);
        if (!$this->is_custom_group(PermissionsAPI::getGroup($group_id, 'pp_group'))) {
            return false;
        }

        if (empty($order['email']) || (empty($order['id']) && empty($order['ID'])) || !$product_id) {
            return false;
        }

        // Removal must not create an account for a guest or an already-deleted customer.
        $user_id = ppcart_get_order_user_id($order, 'add' === $action);
        if (is_wp_error($user_id) || !is_numeric($user_id) || (int) $user_id < 1) {
            return false;
        }

        if ('remove' === $action) {
            return PermissionsAPI::removeGroupUser($group_id, [(int) $user_id]);
        }

        return PermissionsAPI::addGroupUser($group_id, [(int) $user_id]);
    }
}
