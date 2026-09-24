<?php

if (! defined('ABSPATH')) {
    exit;
}


global $ppcart_product_fields, $ppcart_files;
$plugin_admin = new PPCart_Admin($this->cart->get_plugin_name(), $this->cart->get_plugin_title(), $this->cart->get_version());
$plugin_admin_order = new PPCart_Admin_Order_Controller($this->cart->get_plugin_name(), $this->cart->get_plugin_title(), $this->cart->get_version());
$plugin_admin_subscription = new PPCart_Admin_Subscription_Controller($this->cart->get_plugin_name(), $this->cart->get_plugin_title(), $this->cart->get_version());
$plugin_admin_order_list = new PPCart_Admin_Order_List_Controller($this->cart->get_plugin_name(), $this->cart->get_plugin_title(), $this->cart->get_version());
$plugin_admin_order_refund = new PPCart_Admin_Order_Refund_Controller($this->cart->get_plugin_name(), $this->cart->get_plugin_title(), $this->cart->get_version());
$plugin_admin_test_mode_notice = new PPCart_Admin_Test_Mode_Notice_Controller($this->cart->get_plugin_name(), $this->cart->get_plugin_title(), $this->cart->get_version());
$plugin_admin_page_notices = new PPCart_Admin_Page_Notices();
$plugin_admin_legacy_tracking_notice = new PPCart_Admin_Legacy_Tracking_Notice_Controller();
$ppcart_product_fields = new PPCart_Product_Metaboxes($this->cart->get_plugin_name(), $this->cart->get_version(), $this->cart->get_prefix());
$order_fields = new PPCart_Order_Metaboxes($this->cart->get_plugin_name(), $this->cart->get_version(), $this->cart->get_prefix());
$order_admin = new PPCart_Order_Admin($this->cart->get_plugin_name(), $this->cart->get_version(), $this->cart->get_prefix());
$plugin_settings = new PPCart_Admin_Settings($this->cart->get_plugin_name(), $this->cart->get_plugin_title(), $this->cart->get_version());
$plugin_reports = new PPCart_Admin_Reports($this->cart->get_plugin_name(), $this->cart->get_plugin_title(), $this->cart->get_version());
$plugin_contacts_page =  new PPCart_Contacts_Page($this->cart->get_plugin_name(), $this->cart->get_plugin_title(), $this->cart->get_version());
$plugin_customer =  new PPCart_Customer_Reports($this->cart->get_plugin_name(), $this->cart->get_plugin_title(), $this->cart->get_version());
$pro_item_reference = defined('PPCART_PRO_PLUGIN_NAME') ? PPCART_PRO_PLUGIN_NAME : 'PublishPress Cart Pro';
$plugin_extensions_page =  new PPCart_Extension_Page($this->cart->get_plugin_name(), $this->cart->get_plugin_title(), $this->cart->get_version(), $pro_item_reference);
$plugin_post_types = new PPCart_Post_Types();

$order_items = new PPCart_Order_Items();

$plugin_admin_ajax = new PPCart_Admin_Ajax($this->cart->get_plugin_name(), $this->cart->get_plugin_title(), $this->cart->get_version());

$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
$this->loader->add_action('admin_init', $plugin_admin_legacy_tracking_notice, 'maybe_dismiss_notice');
$this->loader->add_action('admin_notices', $plugin_admin_legacy_tracking_notice, 'render_notice');

$this->loader->add_action('init', $plugin_post_types, 'create_custom_post_type', 0);

//admin Ajax
$this->loader->add_action('wp_ajax_ppcart_ajax_action', $plugin_admin_ajax, 'ajax_action', 999);

// Files
if (apply_filters('ppcart_register_file_handler', true)) {
    $ppcart_files = new PPCart_Files();
    $this->loader->add_action('admin_init', $ppcart_files, 'init', 99);
}

// Order Items
$this->loader->add_action('admin_init', $order_items, 'init', 99);

// Product Metaboxes
$this->loader->add_action('admin_init', $ppcart_product_fields, 'add_metaboxes', 99);
$ppcart_product_types = function_exists('ppcart_query_post_types') ? ppcart_query_post_types('product') : [ 'ppcart_product' ];
$ppcart_order_types = function_exists('ppcart_query_post_types') ? ppcart_query_post_types('order') : [ 'ppcart_order' ];
$ppcart_subscription_types = function_exists('ppcart_query_post_types') ? ppcart_query_post_types('subscription') : [ 'ppcart_subscription' ];
foreach ($ppcart_product_types as $ppcart_product_type) {
    $this->loader->add_action('save_post_' . $ppcart_product_type, $ppcart_product_fields, 'validate_meta', 10, 2);
}

// Order Metaboxes
$this->loader->add_action('admin_init', $order_fields, 'add_metaboxes', 99);
foreach ($ppcart_subscription_types as $ppcart_subscription_type) {
    $this->loader->add_action('save_post_' . $ppcart_subscription_type, $order_fields, 'validate_meta', 10, 2);
}

//Add New Order Admin
foreach ($ppcart_order_types as $ppcart_order_type) {
    $this->loader->add_action('save_post_' . $ppcart_order_type, $order_admin, 'save_post_order', 1, 2);
}

//Plugin Admin Settings
$this->loader->add_action('admin_menu', $plugin_settings, 'setup_plugin_options_menu');
$this->loader->add_action('parent_file', $plugin_settings, 'taxonomy_menu_highlight');
$this->loader->add_action('admin_init', $plugin_settings, 'register_sections');
$this->loader->add_action('admin_init', $plugin_settings, 'register_fields');
$this->loader->add_action('admin_menu', $plugin_reports, 'setup_plugin_options_menu');
$this->loader->add_action('admin_menu', $plugin_customer, 'setup_plugin_options_menu');
$this->loader->add_action('admin_menu', $plugin_contacts_page, 'setup_plugin_options_menu');
$this->loader->add_action('admin_menu', $plugin_extensions_page, 'setup_plugin_options_menu');


// Register Importer
$this->loader->add_action('admin_init', $plugin_admin, 'register_importers');

// Order Custom Tax
$this->loader->add_filter('pre_update_option__ppcart_tax_rates', $plugin_settings, 'validate_custom_tax', 10, 3);

//Plugin Admin Functionality

//GDPR
$this->loader->add_action('admin_init', $plugin_admin, 'privacy_declarations', 10, 2);
$this->loader->add_action('wp_privacy_personal_data_erasers', $plugin_admin, 'register_erasers', 10, 2);
$this->loader->add_action('wp_privacy_personal_data_exporters', $plugin_admin, 'register_exporter', 10);

$this->loader->add_action('update_option__ppcart_stripe_express_payment_enable', $plugin_admin, 'handle_stripe_domain_enable', 10, 3);
$this->loader->add_action('update_option__ppcart_stripe_customer_portal_enable', $plugin_admin, 'handle_stripe_customer_portal_enable', 10, 3);

$this->loader->add_action('shutdown', $plugin_admin, 'manage_stripe_express_payment_customer_portal', 10);

$this->loader->add_action('add_option__ppcart_mailchimp_api', $plugin_admin, 'get_mailchimp_groups', 10);
$this->loader->add_action('add_option__ppcart_mailchimp_api', $plugin_admin, 'get_mailchimp_tags', 10);
$this->loader->add_action('add_option__ppcart_activecampaign_secret_key', $plugin_admin, 'get_activecampaign_lists', 10);
$this->loader->add_action('add_option__ppcart_activecampaign_secret_key', $plugin_admin, 'get_activecampaign_tags', 10);

$this->loader->add_action('update_option__ppcart_mailchimp_api', $plugin_admin, 'get_mailchimp_groups', 10);
$this->loader->add_action('update_option__ppcart_mailchimp_api', $plugin_admin, 'get_mailchimp_tags', 10);
$this->loader->add_action('update_option__ppcart_activecampaign_secret_key', $plugin_admin, 'get_activecampaign_lists', 10);
$this->loader->add_action('update_option__ppcart_activecampaign_secret_key', $plugin_admin, 'get_activecampaign_tags', 10);

$this->loader->add_action('add_option__ppcart_sendfox_api_key', $plugin_admin, 'get_sendfox_lists', 10);
$this->loader->add_action('update_option__ppcart_sendfox_api_key', $plugin_admin, 'get_sendfox_lists', 10);

$this->loader->add_action('admin_init', $plugin_admin_order, 'add_metaboxes', 99);
$this->loader->add_action('edit_form_after_editor', $plugin_admin_order, 'product_info_callback');
$this->loader->add_action('edit_form_advanced', $plugin_admin_order, 'product_form_callback');
$this->loader->add_action('edit_form_after_editor', $plugin_admin_subscription, 'subscription_info_callback');
$this->loader->add_action('edit_form_advanced', $plugin_admin_subscription, 'subscription_form_callback');
foreach ($ppcart_product_types as $ppcart_product_type) {
    $this->loader->add_filter('manage_' . $ppcart_product_type . '_posts_columns', $plugin_admin_order_list, 'set_custom_edit_product_columns');
    $this->loader->add_action('manage_' . $ppcart_product_type . '_posts_custom_column', $plugin_admin_order_list, 'custom_product_column', 10, 2);
}
$this->loader->add_filter('post_row_actions', $plugin_admin, 'add_product_duplicate_row_action', 10, 2);
$this->loader->add_filter('page_row_actions', $plugin_admin, 'add_product_duplicate_row_action', 10, 2);
$this->loader->add_action('admin_action_ppcart_duplicate_product', $plugin_admin, 'handle_product_duplicate_action');
$this->loader->add_action('admin_notices', $plugin_admin, 'product_duplicate_admin_notice');
foreach ($ppcart_order_types as $ppcart_order_type) {
    $this->loader->add_filter('manage_' . $ppcart_order_type . '_posts_columns', $plugin_admin_order_list, 'set_custom_edit_order_columns');
    $this->loader->add_action('manage_' . $ppcart_order_type . '_posts_custom_column', $plugin_admin_order_list, 'custom_order_column', 10, 2);
    $this->loader->add_filter('manage_edit-' . $ppcart_order_type . '_sortable_columns', $plugin_admin_order_list, 'order_sortable_columns');
    $this->loader->add_filter('bulk_actions-edit-' . $ppcart_order_type, $plugin_admin_order_list, 'order_bulk_action');
    $this->loader->add_filter('handle_bulk_actions-edit-' . $ppcart_order_type, $plugin_admin_order_list, 'bulk_action_handler', 10, 3);
}
foreach ($ppcart_subscription_types as $ppcart_subscription_type) {
    $this->loader->add_filter('manage_' . $ppcart_subscription_type . '_posts_columns', $plugin_admin_order_list, 'set_custom_edit_subscription_columns');
    $this->loader->add_action('manage_' . $ppcart_subscription_type . '_posts_custom_column', $plugin_admin_order_list, 'custom_subscription_column', 10, 2);
    $this->loader->add_filter('manage_edit-' . $ppcart_subscription_type . '_sortable_columns', $plugin_admin_order_list, 'order_sortable_columns');
    $this->loader->add_filter('bulk_actions-edit-' . $ppcart_subscription_type, $plugin_admin_order_list, 'subscription_bulk_action');
    $this->loader->add_filter('handle_bulk_actions-edit-' . $ppcart_subscription_type, $plugin_admin_order_list, 'bulk_action_handler', 10, 3);
}
$this->loader->add_action('show_user_profile', $plugin_admin, 'show_user_profile_address_fields');
$this->loader->add_action('edit_user_profile', $plugin_admin, 'show_user_profile_address_fields');
$this->loader->add_action('personal_options_update', $plugin_admin, 'update_profile_address_fields');
$this->loader->add_action('edit_user_profile_update', $plugin_admin, 'update_profile_address_fields');
$this->loader->add_action('admin_notices', $plugin_admin_order_list, 'sync_stripe_bulk_notice');
$this->loader->add_action('pre_get_posts', $plugin_admin_order_list, 'order_sortable_columns_orderby');
$this->loader->add_action('load-edit.php', $plugin_admin_order_list, 'load_edit_php_action');
$this->loader->add_action('init', $plugin_admin_order_list, 'order_custom_status', 1);
foreach ($ppcart_order_types as $ppcart_order_type) {
    $this->loader->add_filter('views_edit-' . $ppcart_order_type, $plugin_admin_order_list, 'order_remove_statuses');
}

// MODIFY ORDER details post title
$this->loader->add_action('wp_insert_post_data', $plugin_admin_order_list, 'modify_order_details');

//AJAX REQUEST
$this->loader->add_action('wp_ajax_ppcart_order_refund', $plugin_admin_order_refund, 'order_refund'); //refund
$this->loader->add_action('wp_ajax_ppcart_product_plans', $plugin_admin_order, 'product_plan_options_html');
$this->loader->add_action('wp_ajax_ppcart_fresh_product', $plugin_admin_order, 'clean_product_meta_duplicate');
$this->loader->add_action('wp_ajax_ppcart_send_email_test', $plugin_admin_order, 'send_email_test');
$this->loader->add_action('wp_ajax_ppcart_mailchimp_groups_tags', $plugin_admin, 'mailchimp_groups_tags');
$this->loader->add_action('wp_ajax_ppcart_get_payment_options', $plugin_admin_order, 'get_payment_options'); //get payment options
$this->loader->add_action('wp_ajax_ppcart_renew_integrations_lists', $plugin_admin, 'renew_integrations_lists'); //Mailchimp
