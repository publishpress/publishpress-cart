<?php

declare(strict_types=1);

namespace Tests\Integration\Prefix;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Admin;
use PPCart_Admin_Order_List_Controller;
use PPCart_Admin_Settings;
use PPCart_Files;
use PPCart_Order_Admin;
use PPCart_Order_Metaboxes;
use PPCart_Product_Metabox_Option_Sources;
use PublishPress\Cart\CancelSubscription;
use PublishPress\Cart\Kit;
use ReflectionFunction;
use ReflectionMethod;
use WP_Hook;

class MethodNamesTest extends WPTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    /**
     * @test-id IT-311
     */
    public function test_IT_311_admin_hooks_register_canonical_callbacks_on_live_cpt_tags(): void
    {
        $order_type = ppcart_live_post_type('order');
        $product_type = ppcart_live_post_type('product');
        $subscription_type = ppcart_live_post_type('subscription');

        $this->assertContains(
            'save_post_order',
            $this->hookCallbackMethods('save_post_' . $order_type)
        );
        $this->assertNotContains(
            'save_post_sc_order',
            $this->hookCallbackMethods('save_post_' . $order_type)
        );

        $this->assertContains(
            'set_custom_edit_product_columns',
            $this->hookCallbackMethods('manage_' . $product_type . '_posts_columns')
        );
        $this->assertContains(
            'custom_product_column',
            $this->hookCallbackMethods('manage_' . $product_type . '_posts_custom_column')
        );
        $this->assertNotContains(
            'set_custom_edit_sc_product_columns',
            $this->hookCallbackMethods('manage_' . $product_type . '_posts_columns')
        );
        $this->assertNotContains(
            'custom_sc_product_column',
            $this->hookCallbackMethods('manage_' . $product_type . '_posts_custom_column')
        );

        $this->assertContains(
            'set_custom_edit_order_columns',
            $this->hookCallbackMethods('manage_' . $order_type . '_posts_columns')
        );
        $this->assertContains(
            'custom_order_column',
            $this->hookCallbackMethods('manage_' . $order_type . '_posts_custom_column')
        );
        $this->assertNotContains(
            'set_custom_edit_sc_order_columns',
            $this->hookCallbackMethods('manage_' . $order_type . '_posts_columns')
        );
        $this->assertNotContains(
            'custom_sc_order_column',
            $this->hookCallbackMethods('manage_' . $order_type . '_posts_custom_column')
        );

        $this->assertContains(
            'set_custom_edit_subscription_columns',
            $this->hookCallbackMethods('manage_' . $subscription_type . '_posts_columns')
        );
        $this->assertContains(
            'custom_subscription_column',
            $this->hookCallbackMethods('manage_' . $subscription_type . '_posts_custom_column')
        );
        $this->assertNotContains(
            'set_custom_edit_sc_subscription_columns',
            $this->hookCallbackMethods('manage_' . $subscription_type . '_posts_columns')
        );
        $this->assertNotContains(
            'custom_sc_subscription_column',
            $this->hookCallbackMethods('manage_' . $subscription_type . '_posts_custom_column')
        );

        $this->assertContains(
            'register_importers',
            $this->hookCallbackMethods('admin_init')
        );
        $this->assertNotContains(
            'register_sc_importers',
            $this->hookCallbackMethods('admin_init')
        );

        $this->assertTrue(class_exists(PPCart_Files::class, false));
        new PPCart_Files();
        $this->assertTrue(
            $this->hookHasCallbackAtPriority('save_post_' . $order_type, 'update_order_downloads', 99)
        );
        $this->assertContains(
            'update_order_downloads',
            $this->hookCallbackMethods('save_post_' . $order_type)
        );
    }

    /**
     * @test-id IT-311
     */
    public function test_IT_311_owning_classes_expose_canonical_methods_not_leftover_sc_names(): void
    {
        $this->assertTrue(method_exists(PPCart_Order_Admin::class, 'save_post_order'));
        $this->assertFalse(method_exists(PPCart_Order_Admin::class, 'save_post_sc_order'));

        $this->assertTrue(method_exists(PPCart_Admin::class, 'register_importers'));
        $this->assertFalse(method_exists(PPCart_Admin::class, 'register_sc_importers'));

        $this->assertTrue(method_exists(PPCart_Admin_Order_List_Controller::class, 'set_custom_edit_product_columns'));
        $this->assertTrue(method_exists(PPCart_Admin_Order_List_Controller::class, 'custom_product_column'));
        $this->assertTrue(method_exists(PPCart_Admin_Order_List_Controller::class, 'set_custom_edit_order_columns'));
        $this->assertTrue(method_exists(PPCart_Admin_Order_List_Controller::class, 'custom_order_column'));
        $this->assertTrue(method_exists(PPCart_Admin_Order_List_Controller::class, 'set_custom_edit_subscription_columns'));
        $this->assertTrue(method_exists(PPCart_Admin_Order_List_Controller::class, 'custom_subscription_column'));
        $this->assertFalse(method_exists(PPCart_Admin_Order_List_Controller::class, 'set_custom_edit_sc_product_columns'));
        $this->assertFalse(method_exists(PPCart_Admin_Order_List_Controller::class, 'custom_sc_product_column'));
        $this->assertFalse(method_exists(PPCart_Admin_Order_List_Controller::class, 'set_custom_edit_sc_order_columns'));
        $this->assertFalse(method_exists(PPCart_Admin_Order_List_Controller::class, 'custom_sc_order_column'));
        $this->assertFalse(method_exists(PPCart_Admin_Order_List_Controller::class, 'set_custom_edit_sc_subscription_columns'));
        $this->assertFalse(method_exists(PPCart_Admin_Order_List_Controller::class, 'custom_sc_subscription_column'));

        $this->assertTrue(method_exists(PPCart_Product_Metabox_Option_Sources::class, 'get_mailchimp_lists'));
        $this->assertTrue(method_exists(PPCart_Product_Metabox_Option_Sources::class, 'get_service_type'));
        $this->assertFalse(method_exists(PPCart_Product_Metabox_Option_Sources::class, 'get_sc_mailchimp_lists'));
        $this->assertFalse(method_exists(PPCart_Product_Metabox_Option_Sources::class, 'get_sc_trigger_option'));

        $this->assertTrue(method_exists(PPCart_Admin_Settings::class, 'get_tab_fields'));
        $this->assertTrue(method_exists(PPCart_Admin_Settings::class, 'register_tab_section'));
        $this->assertFalse(method_exists(PPCart_Admin_Settings::class, 'get_sc_tab_fields'));
        $this->assertFalse(method_exists(PPCart_Admin_Settings::class, 'register_sc_tab_section'));

        $this->assertTrue(method_exists(PPCart_Order_Metaboxes::class, 'get_products'));
        $this->assertTrue(method_exists(PPCart_Order_Metaboxes::class, 'get_products_payment'));
        $this->assertFalse(method_exists(PPCart_Order_Metaboxes::class, 'get_sc_products'));
        $this->assertFalse(method_exists(PPCart_Order_Metaboxes::class, 'get_sc_products_payment'));
    }

    /**
     * @test-id IT-311
     */
    public function test_IT_311_leftover_sc_parameters_are_canonical_on_loaded_classes(): void
    {
        $tab_fields = new ReflectionMethod(PPCart_Admin_Settings::class, 'get_tab_fields');
        $this->assertSame('ppcart_tab', $tab_fields->getParameters()[0]->getName());

        $tab_section = new ReflectionMethod(PPCart_Admin_Settings::class, 'register_tab_section');
        $this->assertSame('ppcart_tabs', $tab_section->getParameters()[0]->getName());

        $this->assertTrue(class_exists(Kit::class, false));
        $add_remove = new ReflectionMethod(Kit::class, 'add_remove_to_service');
        $this->assertSame('ppcart_product_id', $add_remove->getParameters()[1]->getName());

        $subscriber = new ReflectionMethod(Kit::class, 'add_remove_convertkit_subscriber');
        $this->assertSame('ppcart_mail_forms', $subscriber->getParameters()[3]->getName());
        $this->assertSame('ppcart_mail_tags', $subscriber->getParameters()[4]->getName());

        $this->assertTrue(class_exists(CancelSubscription::class, false));
        $cancel = new ReflectionMethod(CancelSubscription::class, 'maybe_cancel_subscription');
        $this->assertSame('ppcart_product_id', $cancel->getParameters()[1]->getName());

        $this->assertTrue(function_exists('ppcart_do_integrations'));
        $do_integrations = new ReflectionFunction('ppcart_do_integrations');
        $this->assertSame('ppcart_product_id', $do_integrations->getParameters()[0]->getName());
    }

    /**
     * @return list<string>
     */
    private function hookCallbackMethods(string $hook): array
    {
        global $wp_filter;

        if (! isset($wp_filter[ $hook ]) || ! $wp_filter[ $hook ] instanceof WP_Hook) {
            return [];
        }

        $methods = [];
        foreach ($wp_filter[ $hook ]->callbacks as $callbacks) {
            foreach ($callbacks as $callback) {
                $function = $callback['function'] ?? null;
                if (is_array($function) && isset($function[1]) && is_string($function[1])) {
                    $methods[] = $function[1];
                }
            }
        }

        return $methods;
    }

    private function hookHasCallbackAtPriority(string $hook, string $method, int $priority): bool
    {
        global $wp_filter;

        if (! isset($wp_filter[ $hook ]) || ! $wp_filter[ $hook ] instanceof WP_Hook) {
            return false;
        }

        if (! isset($wp_filter[ $hook ]->callbacks[ $priority ])) {
            return false;
        }

        foreach ($wp_filter[ $hook ]->callbacks[ $priority ] as $callback) {
            $function = $callback['function'] ?? null;
            if (is_array($function) && ($function[1] ?? null) === $method) {
                return true;
            }
        }

        return false;
    }
}
