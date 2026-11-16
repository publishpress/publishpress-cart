<?php

/**
 * Cart feature scopes and native capability discovery without private record data.
 *
 * @package PPCart
 * @since 1.1.0
 */

defined('ABSPATH') || exit;

class PPCart_Mcp_Features
{
    /** @since 1.1.0 @return bool */
    public function permitted()
    {
        if (current_user_can('manage_options')) {
            return true;
        }
        $records = new PPCart_Mcp_Records();
        foreach (array_keys($records->types()) as $kind) {
            if ($records->permitted($kind)) {
                return true;
            }
        }
        return false;
    }

    /** @since 1.1.0 @param mixed $input JSON input. @return array|WP_Error */
    public function read($input)
    {
        $input = PPCart_Mcp_Input::validate($input, PPCart_Mcp_Input::schema([]));
        if (is_wp_error($input)) {
            return $input;
        }
        if (! $this->permitted()) {
            return new WP_Error('ppcart_mcp_forbidden', __('You cannot inspect Cart features.', 'publishpress-cart'));
        }
        $product = get_post_type_object(ppcart_live_post_type('product'));
        $features = [
            'store-settings' => ['label' => __('Store and email settings', 'publishpress-cart'),
                'capabilities' => ['manage_options'], 'reads' => ['get-settings', 'get-checkout-experience', 'get-service-setup'], 'writes' => ['update-settings', 'set-checkout-experience', 'send-test-email']],
            'products' => ['label' => __('Products and checkout configuration', 'publishpress-cart'),
                'capabilities' => $product ? [$product->cap->edit_posts] : [],
                'reads' => ['list-records', 'get-record', 'get-product-settings', 'get-payment-plans', 'get-catalog-lifecycle'],
                'writes' => ['create-record', 'update-record', 'update-product-settings', 'set-payment-plans', 'update-catalog-lifecycle']],
            'purchase-notifications' => ['label' => __('Additional Purchase Notifications', 'publishpress-cart'),
                'capabilities' => [], 'reads' => ['get-product-notifications'], 'writes' => ['set-product-notifications']],
            'product-taxonomy' => ['label' => __('Product categories and tags', 'publishpress-cart'),
                'capabilities' => [], 'object_permissions' => __('Native taxonomy and term capabilities plus edit access to the product.', 'publishpress-cart'),
                'reads' => ['list-product-terms', 'get-product-terms', 'get-product-term-deletion'], 'writes' => ['create-product-term', 'update-product-term', 'set-product-terms', 'delete-product-term']],
            'payments' => ['label' => __('Orders, refunds and subscriptions', 'publishpress-cart'),
                'capabilities' => [], 'object_permissions' => __('Native financial permission and access to the order or subscription; restocking requires product edit access.', 'publishpress-cart'),
                'reads' => ['list-records', 'get-record', 'get-order-details', 'get-stripe-sync-scope', 'get-payment-operation'], 'writes' => ['create-manual-order', 'update-order-details', 'refund-order', 'cancel-subscription', 'pause-subscription', 'resume-subscription', 'sync-stripe-record', 'resolve-payment-operation', 'resend-order-confirmation']],
            'secure-downloads' => ['label' => __('Secure product files and order access', 'publishpress-cart'),
                'capabilities' => [], 'reads' => ['get-product-files', 'get-order-downloads'],
                'writes' => ['upload-product-file', 'set-product-files', 'update-order-download', 'revoke-order-download']],
            'integrations' => ['label' => __('Product integration rules', 'publishpress-cart'),
                'capabilities' => ['manage_options'], 'reads' => ['get-product-integrations'], 'writes' => ['set-product-integrations']],
            'reports' => ['label' => __('Monthly Overview', 'publishpress-cart'),
                'capabilities' => [ppcart_live_cap('manager_option')],
                'object_permissions' => __('Edit others and read private records for every aggregated Cart record type.', 'publishpress-cart'),
                'reads' => ['get-monthly-overview', 'export-records-csv'], 'writes' => []],
        ];
        $features = apply_filters('ppcart_mcp_features', $features);
        foreach ($features as &$feature) {
            $feature += ['edition' => 'free', 'object_permissions' => __('Native per-object permissions apply.', 'publishpress-cart')];
            $feature['capabilities_satisfied'] = true;
            foreach ($feature['capabilities'] as $capability) {
                $feature['capabilities_satisfied'] = $feature['capabilities_satisfied'] && current_user_can($capability);
            }
        }
        unset($feature);
        return ['namespace' => 'publishpress-cart/', 'features' => (object) $features];
    }

    /** @since 1.1.0 @param mixed $input Input. @return array|WP_Error */
    public function service_setup($input)
    {
        $input = PPCart_Mcp_Input::validate($input, PPCart_Mcp_Input::schema([]));
        if (is_wp_error($input)) {
            return $input;
        }
        if (! current_user_can('manage_options')) {
            return new WP_Error('ppcart_mcp_forbidden', __('You cannot configure Cart services.', 'publishpress-cart'));
        }
        return ['payment_setup' => admin_url('admin.php?page=' . PPCart_Admin_Screens::PAGE_SETTINGS . '&tab=payment'),
            'integration_setup' => admin_url('admin.php?page=' . PPCart_Admin_Screens::PAGE_SETTINGS . '&tab=integrations'),
            'instructions' => __('Complete credentials and provider authorization in the native WordPress settings screen. Then read current product integration choices or checkout settings. Do not put passwords, API keys, authentication headers or signed links into chat or tool inputs.', 'publishpress-cart'),
            'stores_credentials' => false];
    }
}
