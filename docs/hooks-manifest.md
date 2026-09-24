# PublishPress Cart Hook Manifest

Generated on 2026-09-24 from a static PHP token audit of `publishpress-cart`.

## Prefix Policy

The default prefix for new public extension hooks is `ppcart_`. StudioCart-era hook names (`sc_*`, `studiocart_*`, `_sc_*`, `ncs_*`, `nsc_*`) live only in StudioCart Compatibility Mode. Canonical AJAX routes are `wp_ajax_ppcart_*`; Compatibility Mode dual-registers shipped `wp_ajax_sc_*` / `wp_ajax_ncs_*` names. Frozen CPT-derived and `admin_action_sc_*` hooks stay unchanged.

## Hooks

| Hook | Type | Operation | Classification | Status | File | Recommendation |
| --- | --- | --- | --- | --- | --- | --- |
| `_ppcart_custom_option_list` | Filter | `apply_filters` | Public | canonical | `admin/settings/traits/trait-ppcart-admin-settings-integration-options.php` | _ppcart_custom_option_list |
| `_ppcart_emails_tab_section` | Filter | `apply_filters` | Public | canonical | `admin/settings/traits/templates/settings-sections-emails.php` | _ppcart_emails_tab_section |
| `_ppcart_integrations_option_list` | Filter | `add_filter` | Public | canonical | `includes/integrations/templates/ppcart-googlerecaptcha---construct.php` | _ppcart_integrations_option_list |
| `_ppcart_integrations_option_list` | Filter | `add_filter` | Public | canonical | `includes/integrations/templates/ppcart-kit-init.php` | _ppcart_integrations_option_list |
| `_ppcart_integrations_option_list` | Filter | `apply_filters` | Public | canonical | `admin/settings/traits/trait-ppcart-admin-settings-integration-options.php` | _ppcart_integrations_option_list |
| `_ppcart_integrations_tab_section` | Filter | `add_filter` | Public | canonical | `includes/integrations/templates/ppcart-googlerecaptcha---construct.php` | _ppcart_integrations_tab_section |
| `_ppcart_integrations_tab_section` | Filter | `add_filter` | Public | canonical | `includes/integrations/templates/ppcart-kit-init.php` | _ppcart_integrations_tab_section |
| `_ppcart_integrations_tab_section` | Filter | `apply_filters` | Public | canonical | `admin/settings/traits/templates/settings-sections-integrations.php` | _ppcart_integrations_tab_section |
| `_ppcart_invoice_option_list` | Filter | `apply_filters` | Public | canonical | `admin/settings/traits/trait-ppcart-admin-settings-billing-options.php` | _ppcart_invoice_option_list |
| `_ppcart_invoice_tab_section` | Filter | `apply_filters` | Public | canonical | `admin/settings/traits/trait-ppcart-admin-settings-sections.php` | _ppcart_invoice_tab_section |
| `_ppcart_option_list` | Filter | `add_filter` | Public | canonical | `includes/files/class-ppcart-files.php` | _ppcart_option_list |
| `_ppcart_option_list` | Filter | `add_filter` | Public | canonical | `includes/logging/class-ppcart-stripe-webhook-logger.php` | _ppcart_option_list |
| `_ppcart_option_list` | Filter | `add_filter` | Public | canonical | `includes/logging/templates/ppcart-debug-logger---construct.php` | _ppcart_option_list |
| `_ppcart_option_list` | Filter | `apply_filters` | Public | canonical | `admin/settings/traits/trait-ppcart-admin-settings-core-options.php` | _ppcart_option_list |
| `_ppcart_payment_field_option_list` | Filter | `apply_filters` | Public | canonical | `admin/settings/traits/trait-ppcart-admin-settings-billing-options.php` | _ppcart_payment_field_option_list |
| `_ppcart_payment_gateway_tab_section` | Filter | `apply_filters` | Public | canonical | `admin/settings/traits/trait-ppcart-admin-settings-sections.php` | _ppcart_payment_gateway_tab_section |
| `_ppcart_plan` | Filter | `apply_filters` | Public | canonical | `includes/functions/plans-and-subscriptions.php` | _ppcart_plan |
| `_ppcart_register_gateways` | Action | `do_action` | Public | canonical | `admin/settings/traits/trait-ppcart-admin-settings-sections.php` | _ppcart_register_gateways |
| `_ppcart_register_sections` | Action | `do_action` | Public | canonical | `admin/settings/traits/templates/settings-sections-integrations.php` | _ppcart_register_sections |
| `_ppcart_taxes_tab_section` | Filter | `apply_filters` | Public | canonical | `admin/settings/traits/trait-ppcart-admin-settings-sections.php` | _ppcart_taxes_tab_section |
| `_ppcart_{$ppcart_tab_key}_tab_section` | Filter | `apply_filters` | Public | canonical | `admin/settings/traits/templates/settings-sections-custom-tabs.php` | _ppcart_{$ppcart_tab_key}_tab_section |
| `add_option__ppcart_activecampaign_secret_key` | Action | `add_action` | Internal | unclassified | `includes/bootstrap/templates/admin-hook-registrar-register.php` | add_option__ppcart_activecampaign_secret_key |
| `add_option__ppcart_converkit_api` | Action | `add_action` | Internal | unclassified | `includes/integrations/templates/ppcart-kit-init.php` | add_option__ppcart_converkit_api |
| `add_option__ppcart_mailchimp_api` | Action | `add_action` | Internal | unclassified | `includes/bootstrap/templates/admin-hook-registrar-register.php` | add_option__ppcart_mailchimp_api |
| `add_option__ppcart_sendfox_api_key` | Action | `add_action` | Internal | unclassified | `includes/bootstrap/templates/admin-hook-registrar-register.php` | add_option__ppcart_sendfox_api_key |
| `add_option_ppcart_download_slug` | Action | `add_action` | Internal | unclassified | `includes/files/class-ppcart-files.php` | add_option_ppcart_download_slug |
| `admin_action_ppcart_duplicate_product` | Action | `add_action` | Internal | unclassified | `includes/bootstrap/templates/admin-hook-registrar-register.php` | admin_action_ppcart_duplicate_product |
| `admin_bar_menu` | Action | `add_action` | External | wordpress_core | `admin/controllers/class-ppcart-admin-test-mode-notice-controller.php` |  |
| `admin_body_class` | Filter | `add_filter` | Internal | unclassified | `admin/class-ppcart-admin-settings.php` | admin_body_class |
| `admin_enqueue_scripts` | Action | `add_action` | External | wordpress_core | `includes/bootstrap/templates/admin-hook-registrar-register.php` |  |
| `admin_enqueue_scripts` | Action | `add_action` | External | wordpress_core | `includes/functions.php` |  |
| `admin_enqueue_scripts` | Action | `add_action` | External | wordpress_core | `includes/functions/admin-ajax-and-notices.php` |  |
| `admin_footer` | Action | `add_action` | Internal | unclassified | `admin/class-ppcart-admin-settings.php` | admin_footer |
| `admin_footer` | Action | `add_action` | Internal | unclassified | `admin/settings/class-ppcart-admin-stripe-webhook-settings.php` | admin_footer |
| `admin_head` | Action | `add_action` | Internal | unclassified | `admin/class-ppcart-customer-reports.php` | admin_head |
| `admin_head` | Action | `add_action` | Internal | unclassified | `admin/controllers/class-ppcart-admin-test-mode-notice-controller.php` | admin_head |
| `admin_init` | Action | `add_action` | External | wordpress_core | `admin/class-ppcart-admin.php` |  |
| `admin_init` | Action | `add_action` | External | wordpress_core | `admin/settings/class-ppcart-admin-stripe-connect-settings.php` |  |
| `admin_init` | Action | `add_action` | External | wordpress_core | `admin/settings/class-ppcart-admin-stripe-webhook-settings.php` |  |
| `admin_init` | Action | `add_action` | External | wordpress_core | `includes/bootstrap/templates/admin-hook-registrar-register.php` |  |
| `admin_init` | Action | `add_action` | External | wordpress_core | `includes/class-ppcart-reviews.php` |  |
| `admin_init` | Action | `add_action` | External | wordpress_core | `includes/files/class-ppcart-files.php` |  |
| `admin_init` | Action | `add_action` | External | wordpress_core | `includes/secrets/traits/trait-ppcart-secrets-config.php` |  |
| `admin_init` | Filter | `add_filter` | External | wordpress_core | `includes/bootstrap/class-ppcart-public-hook-registrar.php` |  |
| `admin_menu` | Action | `add_action` | External | wordpress_core | `includes/bootstrap/templates/admin-hook-registrar-register.php` |  |
| `admin_notices` | Action | `add_action` | External | wordpress_core | `admin/class-ppcart-admin.php` |  |
| `admin_notices` | Action | `add_action` | External | wordpress_core | `admin/class-ppcart-extension-page.php` |  |
| `admin_notices` | Action | `add_action` | External | wordpress_core | `admin/controllers/class-ppcart-admin-page-notices.php` |  |
| `admin_notices` | Action | `add_action` | External | wordpress_core | `includes/bootstrap/templates/admin-hook-registrar-register.php` |  |
| `admin_notices` | Action | `add_action` | External | wordpress_core | `includes/class-ppcart-studiocart-conflict.php` |  |
| `admin_notices` | Action | `add_action` | External | wordpress_core | `includes/files/class-ppcart-files.php` |  |
| `admin_notices` | Action | `add_action` | External | wordpress_core | `includes/functions/admin-ajax-and-notices.php` |  |
| `admin_notices` | Action | `add_action` | External | wordpress_core | `includes/secrets/traits/trait-ppcart-secrets-config.php` |  |
| `admin_post_ppcart_stripe_webhook_manual_setup` | Action | `add_action` | Internal | unclassified | `admin/settings/class-ppcart-admin-stripe-webhook-settings.php` | admin_post_ppcart_stripe_webhook_manual_setup |
| `all_admin_notices` | Action | `add_action` | Internal | unclassified | `admin/controllers/class-ppcart-admin-page-notices.php` | all_admin_notices |
| `alloptions` | Filter | `add_filter` | Internal | unclassified | `includes/secrets/traits/trait-ppcart-secrets-config.php` | alloptions |
| `authenticate` | Filter | `add_filter` | Internal | unclassified | `public/controllers/class-ppcart-public-account-controller.php` | authenticate |
| `body_class` | Filter | `add_filter` | Internal | unclassified | `public/controllers/class-ppcart-public-account-controller.php` | body_class |
| `bulk_actions-edit-{$ppcart_order_type}` | Filter | `add_filter` | Internal | dynamic | `includes/bootstrap/templates/admin-hook-registrar-register.php` | bulk_actions-edit-{$ppcart_order_type} |
| `bulk_actions-edit-{$ppcart_subscription_type}` | Filter | `add_filter` | Internal | dynamic | `includes/bootstrap/templates/admin-hook-registrar-register.php` | bulk_actions-edit-{$ppcart_subscription_type} |
| `check_admin_referer` | Action | `do_action` | Internal | unclassified | `includes/functions/ajax-security.php` | check_admin_referer |
| `check_ajax_referer` | Action | `do_action` | Internal | unclassified | `includes/functions/ajax-security.php` | check_ajax_referer |
| `cron_schedules` | Filter | `add_filter` | Internal | unclassified | `includes/schedule-event.php` | cron_schedules |
| `default_option_{$option}` | Filter | `add_filter` | Internal | dynamic | `includes/email/ppcart-template-functions/options-and-hooks.php` | default_option_{$option} |
| `edit_form_advanced` | Action | `add_action` | Internal | unclassified | `includes/bootstrap/templates/admin-hook-registrar-register.php` | edit_form_advanced |
| `edit_form_advanced` | Action | `add_action` | Internal | unclassified | `includes/files/class-ppcart-files.php` | edit_form_advanced |
| `edit_form_after_editor` | Action | `add_action` | Internal | unclassified | `includes/bootstrap/templates/admin-hook-registrar-register.php` | edit_form_after_editor |
| `edit_user_profile` | Action | `add_action` | Internal | unclassified | `includes/bootstrap/templates/admin-hook-registrar-register.php` | edit_user_profile |
| `edit_user_profile_update` | Action | `add_action` | Internal | unclassified | `includes/bootstrap/templates/admin-hook-registrar-register.php` | edit_user_profile_update |
| `enqueue_block_editor_assets` | Action | `add_action` | Internal | unclassified | `includes/integrations/gutenberg/lib/class-ppcart-gutenberg-bootstrap.php` | enqueue_block_editor_assets |
| `handle_bulk_actions-edit-{$ppcart_order_type}` | Filter | `add_filter` | Internal | dynamic | `includes/bootstrap/templates/admin-hook-registrar-register.php` | handle_bulk_actions-edit-{$ppcart_order_type} |
| `handle_bulk_actions-edit-{$ppcart_subscription_type}` | Filter | `add_filter` | Internal | dynamic | `includes/bootstrap/templates/admin-hook-registrar-register.php` | handle_bulk_actions-edit-{$ppcart_subscription_type} |
| `init` | Action | `add_action` | External | wordpress_core | `includes/bootstrap/class-ppcart-public-hook-registrar.php` |  |
| `init` | Action | `add_action` | External | wordpress_core | `includes/bootstrap/templates/admin-hook-registrar-register.php` |  |
| `init` | Action | `add_action` | External | wordpress_core | `includes/class-ppcart-stripe-sync.php` |  |
| `init` | Action | `add_action` | External | wordpress_core | `includes/class-ppcart.php` |  |
| `init` | Action | `add_action` | External | wordpress_core | `includes/email/ppcart-template-functions/options-and-hooks.php` |  |
| `init` | Action | `add_action` | External | wordpress_core | `includes/files/class-ppcart-files.php` |  |
| `init` | Action | `add_action` | External | wordpress_core | `includes/functions/prices-and-marketing.php` |  |
| `init` | Action | `add_action` | External | wordpress_core | `includes/integrations/gutenberg/lib/class-ppcart-gutenberg-bootstrap.php` |  |
| `init` | Action | `add_action` | External | wordpress_core | `includes/integrations/gutenberg/templates/class-ppcart-product-template.php` |  |
| `init` | Action | `add_action` | External | wordpress_core | `includes/logging/class-ppcart-stripe-webhook-logger.php` |  |
| `init` | Action | `add_action` | External | wordpress_core | `includes/logging/templates/ppcart-debug-logger---construct.php` |  |
| `init` | Action | `add_action` | External | wordpress_core | `includes/schedule-event.php` |  |
| `init` | Action | `add_action` | External | wordpress_core | `includes/secrets/traits/trait-ppcart-secrets-config.php` |  |
| `init` | Action | `add_action` | External | wordpress_core | `public/controllers/class-ppcart-public-account-controller.php` |  |
| `init` | Action | `add_action` | External | wordpress_core | `public/controllers/class-ppcart-public-payment-controller.php` |  |
| `init` | Filter | `add_filter` | External | wordpress_core | `includes/bootstrap/class-ppcart-public-hook-registrar.php` |  |
| `load-edit.php` | Action | `add_action` | External | wordpress_core | `includes/bootstrap/templates/admin-hook-registrar-register.php` |  |
| `login_form_bottom` | Action | `add_action` | Internal | unclassified | `public/controllers/class-ppcart-public-account-controller.php` | login_form_bottom |
| `login_form_lostpassword` | Action | `add_action` | Internal | unclassified | `public/controllers/class-ppcart-public-account-controller.php` | login_form_lostpassword |
| `login_form_resetpass` | Action | `add_action` | Internal | unclassified | `public/controllers/class-ppcart-public-account-controller.php` | login_form_resetpass |
| `login_form_rp` | Action | `add_action` | Internal | unclassified | `public/controllers/class-ppcart-public-account-controller.php` | login_form_rp |
| `manage_edit-{$ppcart_order_type}_sortable_columns` | Filter | `add_filter` | External | wordpress_core | `includes/bootstrap/templates/admin-hook-registrar-register.php` |  |
| `manage_edit-{$ppcart_subscription_type}_sortable_columns` | Filter | `add_filter` | External | wordpress_core | `includes/bootstrap/templates/admin-hook-registrar-register.php` |  |
| `manage_{$ppcart_order_type}_posts_columns` | Filter | `add_filter` | External | wordpress_core | `includes/bootstrap/templates/admin-hook-registrar-register.php` |  |
| `manage_{$ppcart_order_type}_posts_custom_column` | Action | `add_action` | External | wordpress_core | `includes/bootstrap/templates/admin-hook-registrar-register.php` |  |
| `manage_{$ppcart_product_type}_posts_columns` | Filter | `add_filter` | External | wordpress_core | `includes/bootstrap/templates/admin-hook-registrar-register.php` |  |
| `manage_{$ppcart_product_type}_posts_custom_column` | Action | `add_action` | External | wordpress_core | `includes/bootstrap/templates/admin-hook-registrar-register.php` |  |
| `manage_{$ppcart_subscription_type}_posts_columns` | Filter | `add_filter` | External | wordpress_core | `includes/bootstrap/templates/admin-hook-registrar-register.php` |  |
| `manage_{$ppcart_subscription_type}_posts_custom_column` | Action | `add_action` | External | wordpress_core | `includes/bootstrap/templates/admin-hook-registrar-register.php` |  |
| `network_admin_notices` | Action | `add_action` | Internal | unclassified | `admin/controllers/class-ppcart-admin-page-notices.php` | network_admin_notices |
| `network_admin_notices` | Action | `add_action` | Internal | unclassified | `includes/class-ppcart-studiocart-conflict.php` | network_admin_notices |
| `option_{$option_name}` | Filter | `add_filter` | Internal | dynamic | `includes/secrets/traits/trait-ppcart-secrets-config.php` | option_{$option_name} |
| `option_{$option}` | Filter | `add_filter` | Internal | dynamic | `includes/email/ppcart-template-functions/options-and-hooks.php` | option_{$option} |
| `page_row_actions` | Filter | `add_filter` | Internal | unclassified | `includes/bootstrap/templates/admin-hook-registrar-register.php` | page_row_actions |
| `parent_file` | Action | `add_action` | Internal | unclassified | `includes/bootstrap/templates/admin-hook-registrar-register.php` | parent_file |
| `personal_options_update` | Action | `add_action` | Internal | unclassified | `includes/bootstrap/templates/admin-hook-registrar-register.php` | personal_options_update |
| `plugins_loaded` | Action | `add_action` | External | wordpress_core | `includes/class-ppcart-version-notices.php` |  |
| `plugins_loaded` | Action | `add_action` | External | wordpress_core | `includes/integrations/CancelSubscription.php` |  |
| `plugins_loaded` | Action | `add_action` | External | wordpress_core | `includes/integrations/Kit.php` |  |
| `plugins_loaded` | Action | `add_action` | External | wordpress_core | `includes/integrations/templates/ppcart-googlerecaptcha---construct.php` |  |
| `plugins_loaded` | Action | `add_action` | External | wordpress_core | `includes/secrets/traits/trait-ppcart-secrets-config.php` |  |
| `plugins_loaded` | Action | `add_action` | External | wordpress_core | `publishpress-cart.php` |  |
| `post_row_actions` | Filter | `add_filter` | External | wordpress_core | `includes/bootstrap/templates/admin-hook-registrar-register.php` |  |
| `posts_search` | Filter | `add_filter` | Internal | unclassified | `admin/class-ppcart-admin-filters.php` | posts_search |
| `posts_where` | Filter | `add_filter` | Internal | unclassified | `includes/class-ppcart-post-status-sync.php` | posts_where |
| `pp_version_notice_menu_link_settings` | Filter | `add_filter` | Internal | unclassified | `includes/class-ppcart-version-notices.php` | pp_version_notice_menu_link_settings |
| `ppcart_account_before_subscription_details` | Action | `do_action` | Public | canonical | `public/templates/my-account/subscription-detail.php` | ppcart_account_before_subscription_details |
| `ppcart_account_block_navigation_options` | Filter | `apply_filters` | Public | canonical | `includes/integrations/gutenberg/lib/class-ppcart-account-context.php` | ppcart_account_block_navigation_options |
| `ppcart_account_subscription_action_links` | Action | `do_action` | Public | canonical | `public/templates/my-account/subscription-detail.php` | ppcart_account_subscription_action_links |
| `ppcart_account_tabs` | Filter | `add_filter` | Public | canonical | `includes/files/class-ppcart-files.php` | ppcart_account_tabs |
| `ppcart_account_tabs` | Filter | `apply_filters` | Public | canonical | `includes/helpers/ppcart-general-functions.php` | ppcart_account_tabs |
| `ppcart_activate` | Action | `add_action` | Public | canonical | `includes/files/class-ppcart-files.php` | ppcart_activate |
| `ppcart_activate` | Action | `add_action` | Public | canonical | `includes/order-items/class-ppcart-order-items.php` | ppcart_activate |
| `ppcart_activate` | Action | `do_action` | Public | canonical | `includes/class-ppcart-activator.php` | ppcart_activate |
| `ppcart_admin_ajax_dispatch` | Filter | `apply_filters` | Public | canonical | `admin/templates/ppcart-admin-ajax-ppcart-ajax-action.php` | ppcart_admin_ajax_dispatch |
| `ppcart_admin_allowed_html` | Filter | `apply_filters` | Public | canonical | `includes/functions.php` | ppcart_admin_allowed_html |
| `ppcart_admin_bar_test_mode_processors` | Filter | `apply_filters` | Public | canonical | `admin/controllers/class-ppcart-admin-test-mode-notice-controller.php` | ppcart_admin_bar_test_mode_processors |
| `ppcart_admin_notification_email` | Filter | `apply_filters` | Public | canonical | `includes/functions/users-and-notifications.php` | ppcart_admin_notification_email |
| `ppcart_admin_order_child_order_label` | Filter | `apply_filters` | Public | canonical | `admin/controllers/order/templates/product-form.php` | ppcart_admin_order_child_order_label |
| `ppcart_admin_order_item_type_labels` | Filter | `apply_filters` | Public | canonical | `admin/controllers/order/templates/product-form.php` | ppcart_admin_order_item_type_labels |
| `ppcart_admin_order_related_context` | Filter | `apply_filters` | Public | canonical | `admin/controllers/order/templates/product-form.php` | ppcart_admin_order_related_context |
| `ppcart_admin_reports_product_columns` | Action | `do_action` | Public | canonical | `admin/reports/templates/admin-reports-page.php` | ppcart_admin_reports_product_columns |
| `ppcart_admin_reports_summary_columns` | Action | `do_action` | Public | canonical | `admin/reports/templates/admin-reports-page.php` | ppcart_admin_reports_summary_columns |
| `ppcart_admin_reports_transactions_label` | Filter | `apply_filters` | Public | canonical | `admin/reports/templates/admin-reports-page.php` | ppcart_admin_reports_transactions_label |
| `ppcart_after_buy_button` | Action | `do_action` | Public | canonical | `public/templates/order-form/submit-button.php` | ppcart_after_buy_button |
| `ppcart_after_downloads_attached_to_order` | Action | `do_action` | Public | canonical | `includes/files/traits/trait-ppcart-files-order.php` | ppcart_after_downloads_attached_to_order |
| `ppcart_after_load_from_post` | Action | `do_action` | Public | canonical | `models/order/traits/templates/order-checkout-input-load-from-post.php` | ppcart_after_load_from_post |
| `ppcart_after_order_load_from_post` | Filter | `apply_filters` | Public | canonical | `admin/templates/order-admin-save-post-ppcart-order.php` | ppcart_after_order_load_from_post |
| `ppcart_after_order_load_from_post` | Filter | `apply_filters` | Public | canonical | `public/controllers/order/traits/templates/order-cart-ppcart-update-cart-amount.php` | ppcart_after_order_load_from_post |
| `ppcart_after_order_load_from_post` | Filter | `apply_filters` | Public | canonical | `public/controllers/order/traits/templates/order-save-save-order-to-db.php` | ppcart_after_order_load_from_post |
| `ppcart_after_order_load_from_post` | Filter | `apply_filters` | Public | canonical | `public/controllers/payment/traits/templates/payment-intent-create-payment-intent.php` | ppcart_after_order_load_from_post |
| `ppcart_after_order_load_from_post` | Filter | `apply_filters` | Public | canonical | `public/controllers/payment/traits/templates/payment-update-intent-amount.php` | ppcart_after_order_load_from_post |
| `ppcart_after_order_load_from_post` | Filter | `apply_filters` | Public | canonical | `public/controllers/templates/hosted-checkout-controller-create-checkout-session.php` | ppcart_after_order_load_from_post |
| `ppcart_after_order_load_from_post` | Filter | `apply_filters` | Public | canonical | `public/controllers/traits/templates/subscription-create-create-subscription.php` | ppcart_after_order_load_from_post |
| `ppcart_after_order_load_from_post` | Filter | `apply_filters` | Public | canonical | `public/paypal/traits/templates/paypal-requests-ppcart-paypal-request.php` | ppcart_after_order_load_from_post |
| `ppcart_after_order_paid` | Action | `do_action` | Public | canonical | `public/class-ppcart-public.php` | ppcart_after_order_paid |
| `ppcart_after_payment_info` | Action | `do_action` | Public | canonical | `public/templates/functions/payment-address.php` | ppcart_after_payment_info |
| `ppcart_after_product_list` | Action | `do_action` | Public | canonical | `public/templates/archive/item-wrapper-end.php` | ppcart_after_product_list |
| `ppcart_after_product_setup` | Action | `do_action` | Public | canonical | `public/templates/functions/checkout-core.php` | ppcart_after_product_setup |
| `ppcart_after_setup_atts_from_post` | Action | `do_action` | Public | canonical | `models/order/traits/templates/order-checkout-input-load-from-post.php` | ppcart_after_setup_atts_from_post |
| `ppcart_after_step_1_button` | Action | `do_action` | Public | canonical | `public/templates/functions/summary-and-scripts.php` | ppcart_after_step_1_button |
| `ppcart_after_subscription_load_from_order` | Action | `do_action` | Public | canonical | `models/traits/templates/subscription-storage-from-order.php` | ppcart_after_subscription_load_from_order |
| `ppcart_after_summary_items` | Action | `do_action` | Public | canonical | `public/templates/functions/summary-and-scripts.php` | ppcart_after_summary_items |
| `ppcart_after_update_stock` | Action | `add_action` | Public | canonical | `includes/functions/integrations-and-stock.php` | ppcart_after_update_stock |
| `ppcart_after_update_stock` | Action | `do_action` | Public | canonical | `includes/functions/prices-and-marketing.php` | ppcart_after_update_stock |
| `ppcart_after_user_is_created` | Action | `do_action` | Public | canonical | `includes/functions/users-and-notifications.php` | ppcart_after_user_is_created |
| `ppcart_after_validate_meta` | Action | `do_action` | Public | canonical | `admin/metaboxes/traits/templates/product-metabox-save-validate-meta.php` | ppcart_after_validate_meta |
| `ppcart_backend_message_{$k}` | Filter | `apply_filters` | Public | canonical | `includes/functions/merge-tags-and-dates.php` | ppcart_backend_message_{$k} |
| `ppcart_before_buy_button` | Action | `add_action` | Public | canonical | `includes/integrations/GoogleRecaptcha.php` | ppcart_before_buy_button |
| `ppcart_before_buy_button` | Action | `do_action` | Public | canonical | `public/templates/order-form/submit-button.php` | ppcart_before_buy_button |
| `ppcart_before_create_main_order` | Action | `add_action` | Public | canonical | `includes/integrations/GoogleRecaptcha.php` | ppcart_before_create_main_order |
| `ppcart_before_create_main_order` | Action | `add_action` | Public | canonical | `public/controllers/class-ppcart-public-checkout-controller.php` | ppcart_before_create_main_order |
| `ppcart_before_create_main_order` | Action | `do_action` | Public | canonical | `public/controllers/order/traits/templates/order-save-save-order-to-db.php` | ppcart_before_create_main_order |
| `ppcart_before_create_main_order` | Action | `do_action` | Public | canonical | `public/controllers/payment/traits/templates/payment-intent-create-payment-intent.php` | ppcart_before_create_main_order |
| `ppcart_before_create_main_order` | Action | `do_action` | Public | canonical | `public/controllers/templates/hosted-checkout-controller-create-checkout-session.php` | ppcart_before_create_main_order |
| `ppcart_before_create_main_order` | Action | `do_action` | Public | canonical | `public/controllers/traits/templates/subscription-create-create-subscription.php` | ppcart_before_create_main_order |
| `ppcart_before_create_main_order` | Action | `do_action` | Public | canonical | `public/paypal/traits/templates/paypal-requests-ppcart-paypal-request.php` | ppcart_before_create_main_order |
| `ppcart_before_create_stripe_payment_intent` | Action | `do_action` | Public | canonical | `public/controllers/payment/traits/templates/payment-intent-create-payment-intent.php` | ppcart_before_create_stripe_payment_intent |
| `ppcart_before_email_footer` | Action | `add_action` | Public | canonical | `public/templates/email/email-main.php` | ppcart_before_email_footer |
| `ppcart_before_email_footer` | Action | `do_action` | Public | canonical | `public/templates/email/email-main.php` | ppcart_before_email_footer |
| `ppcart_before_load` | Action | `do_action` | Public | canonical | `includes/class-ppcart.php` | ppcart_before_load |
| `ppcart_before_order_refund` | Action | `do_action` | Public | canonical | `includes/functions/payment-actions-and-refunds.php` | ppcart_before_order_refund |
| `ppcart_before_order_save` | Filter | `apply_filters` | Public | canonical | `public/controllers/order/traits/templates/order-save-save-order-to-db.php` | ppcart_before_order_save |
| `ppcart_before_payment_info` | Action | `add_action` | Public | canonical | `public/templates/checkout-shortcode.php` | ppcart_before_payment_info |
| `ppcart_before_payment_info` | Action | `add_action` | Public | canonical | `public/templates/checkout1.php` | ppcart_before_payment_info |
| `ppcart_before_payment_info` | Action | `do_action` | Public | canonical | `public/templates/functions/payment-address.php` | ppcart_before_payment_info |
| `ppcart_before_product_list` | Action | `do_action` | Public | canonical | `public/templates/archive/item-wrapper-start.php` | ppcart_before_product_list |
| `ppcart_before_show_download` | Action | `do_action` | Public | canonical | `includes/files/download.php` | ppcart_before_show_download |
| `ppcart_before_validate_meta` | Action | `do_action` | Public | canonical | `admin/metaboxes/traits/templates/product-metabox-save-validate-meta.php` | ppcart_before_validate_meta |
| `ppcart_bulk_sync_subscription_limit` | Filter | `apply_filters` | Public | canonical | `admin/controllers/templates/order-list-bulk-action-handler.php` | ppcart_bulk_sync_subscription_limit |
| `ppcart_buy_button_icon` | Action | `do_action` | Public | canonical | `public/templates/order-form/submit-button.php` | ppcart_buy_button_icon |
| `ppcart_buy_button_subtext` | Action | `do_action` | Public | canonical | `public/templates/order-form/submit-button.php` | ppcart_buy_button_subtext |
| `ppcart_cancel_subscription` | Filter | `add_filter` | Public | canonical | `public/class-ppcart-paypal.php` | ppcart_cancel_subscription |
| `ppcart_cancel_subscription` | Filter | `apply_filters` | Public | canonical | `includes/functions/payment-actions-and-refunds.php` | ppcart_cancel_subscription |
| `ppcart_cancel_subscription_event` | Action | `add_action` | Public | canonical | `includes/helpers/ppcart-scheduling.php` | ppcart_cancel_subscription_event |
| `ppcart_card_details_fields` | Action | `add_action` | Public | canonical | `public/templates/checkout-shortcode.php` | ppcart_card_details_fields |
| `ppcart_card_details_fields` | Action | `add_action` | Public | canonical | `public/templates/checkout1.php` | ppcart_card_details_fields |
| `ppcart_card_details_fields` | Action | `add_action` | Public | canonical | `public/templates/functions/checkout-core.php` | ppcart_card_details_fields |
| `ppcart_card_details_fields` | Action | `do_action` | Public | canonical | `public/templates/functions/checkout-core.php` | ppcart_card_details_fields |
| `ppcart_charge_amount` | Filter | `apply_filters` | Public | canonical | `models/order/traits/templates/order-checkout-input-load-from-post.php` | ppcart_charge_amount |
| `ppcart_checkout_arranged_core_card_details_callbacks` | Filter | `apply_filters` | Public | canonical | `public/templates/functions/checkout-core.php` | ppcart_checkout_arranged_core_card_details_callbacks |
| `ppcart_checkout_block_arrangement` | Filter | `add_filter` | Public | canonical | `includes/integrations/gutenberg/lib/checkout-renderer/trait-ppcart-checkout-renderer-request.php` | ppcart_checkout_block_arrangement |
| `ppcart_checkout_block_arrangement` | Filter | `apply_filters` | Public | canonical | `public/controllers/page/traits/templates/page-shortcodes-ppcart-product-shortcode.php` | ppcart_checkout_block_arrangement |
| `ppcart_checkout_block_sections` | Filter | `apply_filters` | Public | canonical | `includes/integrations/gutenberg/lib/checkout-renderer/trait-ppcart-checkout-renderer-request.php` | ppcart_checkout_block_sections |
| `ppcart_checkout_complete` | Filter | `add_filter` | Public | canonical | `includes/bootstrap/class-ppcart-public-hook-registrar.php` | ppcart_checkout_complete |
| `ppcart_checkout_complete` | Action | `do_action` | Public | canonical | `includes/functions/checkout-completion.php` | ppcart_checkout_complete |
| `ppcart_checkout_coupon` | Filter | `apply_filters` | Public | canonical | `public/templates/functions/summary-and-scripts.php` | ppcart_checkout_coupon |
| `ppcart_checkout_form` | Action | `add_action` | Public | canonical | `public/templates/checkout-shortcode.php` | ppcart_checkout_form |
| `ppcart_checkout_form` | Action | `add_action` | Public | canonical | `public/templates/checkout1.php` | ppcart_checkout_form |
| `ppcart_checkout_form` | Action | `do_action` | Public | canonical | `public/templates/checkout-shortcode.php` | ppcart_checkout_form |
| `ppcart_checkout_form` | Action | `do_action` | Public | canonical | `public/templates/checkout1.php` | ppcart_checkout_form |
| `ppcart_checkout_form_close` | Action | `add_action` | Public | canonical | `public/templates/checkout-shortcode.php` | ppcart_checkout_form_close |
| `ppcart_checkout_form_close` | Action | `add_action` | Public | canonical | `public/templates/checkout1.php` | ppcart_checkout_form_close |
| `ppcart_checkout_form_close` | Action | `do_action` | Public | canonical | `public/templates/checkout-shortcode.php` | ppcart_checkout_form_close |
| `ppcart_checkout_form_close` | Action | `do_action` | Public | canonical | `public/templates/checkout1.php` | ppcart_checkout_form_close |
| `ppcart_checkout_form_fields` | Action | `add_action` | Public | canonical | `public/templates/checkout-shortcode.php` | ppcart_checkout_form_fields |
| `ppcart_checkout_form_fields` | Action | `add_action` | Public | canonical | `public/templates/checkout1.php` | ppcart_checkout_form_fields |
| `ppcart_checkout_form_fields` | Action | `do_action` | Public | canonical | `public/templates/functions/plan-coupon-fields.php` | ppcart_checkout_form_fields |
| `ppcart_checkout_form_open` | Action | `add_action` | Public | canonical | `public/templates/checkout-shortcode.php` | ppcart_checkout_form_open |
| `ppcart_checkout_form_open` | Action | `add_action` | Public | canonical | `public/templates/checkout1.php` | ppcart_checkout_form_open |
| `ppcart_checkout_form_open` | Action | `do_action` | Public | canonical | `public/templates/checkout-shortcode.php` | ppcart_checkout_form_open |
| `ppcart_checkout_form_open` | Action | `do_action` | Public | canonical | `public/templates/checkout1.php` | ppcart_checkout_form_open |
| `ppcart_checkout_form_pay_options` | Filter | `apply_filters` | Public | canonical | `public/templates/functions/checkout-core.php` | ppcart_checkout_form_pay_options |
| `ppcart_checkout_form_pay_options` | Filter | `apply_filters` | Public | canonical | `public/templates/functions/plan-coupon-fields.php` | ppcart_checkout_form_pay_options |
| `ppcart_checkout_form_price_checked` | Filter | `apply_filters` | Public | canonical | `public/templates/functions/plan-coupon-fields.php` | ppcart_checkout_form_price_checked |
| `ppcart_checkout_form_scripts` | Action | `add_action` | Public | canonical | `public/templates/checkout-shortcode.php` | ppcart_checkout_form_scripts |
| `ppcart_checkout_form_scripts` | Action | `add_action` | Public | canonical | `public/templates/checkout1.php` | ppcart_checkout_form_scripts |
| `ppcart_checkout_form_scripts` | Action | `do_action` | Public | canonical | `public/templates/checkout-shortcode.php` | ppcart_checkout_form_scripts |
| `ppcart_checkout_form_scripts` | Action | `do_action` | Public | canonical | `public/templates/checkout1.php` | ppcart_checkout_form_scripts |
| `ppcart_checkout_form_validation_messafes` | Filter | `apply_filters` | Public | canonical | `public/controllers/checkout/traits/templates/checkout-validation-validate-order-form.php` | ppcart_checkout_form_validation_messafes |
| `ppcart_checkout_hide_labels` | Filter | `apply_filters` | Public | canonical | `public/templates/checkout1.php` | ppcart_checkout_hide_labels |
| `ppcart_checkout_page_error` | Filter | `apply_filters` | Public | canonical | `public/templates/functions/checkout-core.php` | ppcart_checkout_page_error |
| `ppcart_checkout_page_heading` | Action | `add_action` | Public | canonical | `public/templates/checkout-shortcode.php` | ppcart_checkout_page_heading |
| `ppcart_checkout_page_heading` | Action | `add_action` | Public | canonical | `public/templates/checkout1.php` | ppcart_checkout_page_heading |
| `ppcart_checkout_page_heading` | Action | `do_action` | Public | canonical | `public/templates/checkout-shortcode.php` | ppcart_checkout_page_heading |
| `ppcart_checkout_page_heading` | Action | `do_action` | Public | canonical | `public/templates/checkout1.php` | ppcart_checkout_page_heading |
| `ppcart_checkout_page_privacy_text` | Filter | `apply_filters` | Public | canonical | `public/templates/functions/summary-and-scripts.php` | ppcart_checkout_page_privacy_text |
| `ppcart_checkout_page_terms_text` | Filter | `apply_filters` | Public | canonical | `public/templates/functions/summary-and-scripts.php` | ppcart_checkout_page_terms_text |
| `ppcart_checkout_payment_method_enabled` | Filter | `apply_filters` | Public | canonical | `includes/integrations/gutenberg/lib/checkout-renderer/trait-ppcart-checkout-renderer-request.php` | ppcart_checkout_payment_method_enabled |
| `ppcart_checkout_payment_method_enabled` | Filter | `apply_filters` | Public | canonical | `public/templates/functions/payment-address.php` | ppcart_checkout_payment_method_enabled |
| `ppcart_checkout_should_guard_duplicate_render` | Filter | `apply_filters` | Public | canonical | `includes/functions.php` | ppcart_checkout_should_guard_duplicate_render |
| `ppcart_checkout_step_viewed` | Action | `do_action` | Public | canonical | `includes/functions/checkout-completion.php` | ppcart_checkout_step_viewed |
| `ppcart_checkout_stripe_subscription_args` | Filter | `apply_filters` | Public | canonical | `public/controllers/traits/templates/subscription-create-create-stripe-subscription.php` | ppcart_checkout_stripe_subscription_args |
| `ppcart_checkout_template_path` | Filter | `apply_filters` | Public | canonical | `public/controllers/page/traits/templates/page-shortcodes-ppcart-product-shortcode.php` | ppcart_checkout_template_path |
| `ppcart_cleanup_preloaded_intents` | Action | `add_action` | Public | canonical | `public/controllers/class-ppcart-public-payment-controller.php` | ppcart_cleanup_preloaded_intents |
| `ppcart_closed_message` | Action | `add_action` | Public | canonical | `public/templates/checkout-shortcode.php` | ppcart_closed_message |
| `ppcart_closed_message` | Action | `do_action` | Public | canonical | `public/templates/checkout-shortcode.php` | ppcart_closed_message |
| `ppcart_confirmation_fields` | Filter | `apply_filters` | Public | canonical | `admin/metaboxes/field-groups/message-fields.php` | ppcart_confirmation_fields |
| `ppcart_confirmation_match_conditions` | Action | `do_action` | Public | canonical | `public/controllers/order/traits/trait-ppcart-public-order-cart.php` | ppcart_confirmation_match_conditions |
| `ppcart_consent_required` | Filter | `apply_filters` | Public | canonical | `public/controllers/checkout/traits/templates/checkout-validation-validate-order-form.php` | ppcart_consent_required |
| `ppcart_consent_required` | Filter | `apply_filters` | Public | canonical | `public/templates/functions/summary-and-scripts.php` | ppcart_consent_required |
| `ppcart_countries` | Filter | `apply_filters` | Public | canonical | `includes/functions/payment-and-locale-lists.php` | ppcart_countries |
| `ppcart_coupon_fields` | Action | `do_action` | Public | canonical | `public/templates/functions/plan-coupon-fields.php` | ppcart_coupon_fields |
| `ppcart_coupon_status` | Action | `do_action` | Public | canonical | `public/templates/functions/plan-coupon-fields.php` | ppcart_coupon_status |
| `ppcart_cpt_options` | Filter | `apply_filters` | Public | canonical | `includes/templates/post-types-register-single-post-type.php` | ppcart_cpt_options |
| `ppcart_create_stripe_intent` | Filter | `apply_filters` | Public | canonical | `public/controllers/payment/traits/templates/payment-intent-create-payment-intent.php` | ppcart_create_stripe_intent |
| `ppcart_create_user_integrations` | Filter | `apply_filters` | Public | canonical | `admin/metaboxes/traits/templates/integration-fields/fields-03.php` | ppcart_create_user_integrations |
| `ppcart_csv_import_check_file_path` | Filter | `apply_filters` | Public | canonical | `includes/functions/payment-actions-and-refunds.php` | ppcart_csv_import_check_file_path |
| `ppcart_csv_import_valid_filetypes` | Filter | `apply_filters` | Public | canonical | `includes/functions/payment-actions-and-refunds.php` | ppcart_csv_import_valid_filetypes |
| `ppcart_currencies` | Filter | `apply_filters` | Public | canonical | `includes/functions/currency-data/currencies.php` | ppcart_currencies |
| `ppcart_currency_countries_code` | Filter | `apply_filters` | Public | canonical | `includes/functions/payment-and-locale-lists.php` | ppcart_currency_countries_code |
| `ppcart_currency_symbols` | Filter | `apply_filters` | Public | canonical | `includes/functions/currency-data/currency-symbols.php` | ppcart_currency_symbols |
| `ppcart_current_user_orders_meta_query_args` | Filter | `apply_filters` | Public | canonical | `models/order/traits/templates/order-lookups-get-current-user-orders.php` | ppcart_current_user_orders_meta_query_args |
| `ppcart_customer_defaults` | Filter | `apply_filters` | Public | canonical | `models/templates/ppcart-order-initialize.php` | ppcart_customer_defaults |
| `ppcart_customer_report_admin_notices` | Action | `add_action` | Public | canonical | `admin/class-ppcart-admin.php` | ppcart_customer_report_admin_notices |
| `ppcart_customer_report_admin_notices` | Action | `add_action` | Public | canonical | `admin/controllers/class-ppcart-admin-page-notices.php` | ppcart_customer_report_admin_notices |
| `ppcart_customer_report_admin_notices` | Action | `do_action` | Public | canonical | `admin/reports/templates/customer-report/page.php` | ppcart_customer_report_admin_notices |
| `ppcart_daily_events` | Action | `add_action` | Public | canonical | `includes/helpers/ppcart-scheduling.php` | ppcart_daily_events |
| `ppcart_db_schema_repaired` | Action | `do_action` | Public | canonical | `includes/db-schema/class-ppcart-db-schema-service.php` | ppcart_db_schema_repaired |
| `ppcart_db_table_schemas` | Filter | `apply_filters` | Public | canonical | `includes/db-schema/class-ppcart-db-schema-registry.php` | ppcart_db_table_schemas |
| `ppcart_default_field_settings_attributes` | Filter | `apply_filters` | Public | canonical | `admin/partials/ppcart-admin-field-default-fields.php` | ppcart_default_field_settings_attributes |
| `ppcart_default_fields` | Filter | `apply_filters` | Public | canonical | `admin/metaboxes/traits/templates/product-metabox-plan-options-get-fields.php` | ppcart_default_fields |
| `ppcart_default_fields_ids` | Filter | `apply_filters` | Public | canonical | `admin/metaboxes/traits/templates/product-metabox-save-validate-meta.php` | ppcart_default_fields_ids |
| `ppcart_default_{$field[...]}]_field_settings_attributes` | Filter | `apply_filters` | Public | canonical | `admin/partials/ppcart-admin-field-default-fields.php` | ppcart_default_{$field[...]}]_field_settings_attributes |
| `ppcart_defualt_fields_html` | Filter | `apply_filters` | Public | canonical | `admin/metaboxes/traits/templates/product-metabox-render-metabox-fields.php` | ppcart_defualt_fields_html |
| `ppcart_download` | Filter | `apply_filters` | Public | canonical | `includes/files/traits/templates/files-repository-setup-download.php` | ppcart_download |
| `ppcart_download_allowed_redirect_hosts` | Filter | `apply_filters` | Public | canonical | `includes/files/download.php` | ppcart_download_allowed_redirect_hosts |
| `ppcart_download_allowed_roots` | Filter | `apply_filters` | Public | canonical | `includes/files/traits/trait-ppcart-files-storage.php` | ppcart_download_allowed_roots |
| `ppcart_download_slug` | Filter | `apply_filters` | Public | canonical | `includes/files/class-ppcart-files.php` | ppcart_download_slug |
| `ppcart_download_tab_name` | Filter | `apply_filters` | Public | canonical | `includes/files/traits/trait-ppcart-files-frontend.php` | ppcart_download_tab_name |
| `ppcart_download_tab_name` | Filter | `apply_filters` | Public | canonical | `includes/integrations/gutenberg/lib/class-ppcart-account-context.php` | ppcart_download_tab_name |
| `ppcart_email_after_order_table` | Action | `add_action` | Public | canonical | `includes/files/class-ppcart-files.php` | ppcart_email_after_order_table |
| `ppcart_email_after_order_table` | Action | `do_action` | Public | canonical | `public/templates/email/order-table.php` | ppcart_email_after_order_table |
| `ppcart_email_schedule_hook` | Action | `add_action` | Public | canonical | `includes/schedule-event.php` | ppcart_email_schedule_hook |
| `ppcart_email_template_customer_info` | Filter | `apply_filters` | Public | canonical | `public/templates/email/order-table.php` | ppcart_email_template_customer_info |
| `ppcart_email_template_option_names` | Filter | `apply_filters` | Public | canonical | `includes/email/ppcart-template-functions/options-and-hooks.php` | ppcart_email_template_option_names |
| `ppcart_email_templates` | Filter | `apply_filters` | Public | canonical | `includes/email/ppcart-template-functions/templates.php` | ppcart_email_templates |
| `ppcart_enabled_payment_gateways` | Filter | `add_filter` | Public | canonical | `public/class-ppcart-paypal.php` | ppcart_enabled_payment_gateways |
| `ppcart_enabled_payment_gateways` | Filter | `apply_filters` | Public | canonical | `includes/functions/payment-and-locale-lists.php` | ppcart_enabled_payment_gateways |
| `ppcart_enabled_processors` | Filter | `apply_filters` | Public | canonical | `includes/functions/payment-and-locale-lists.php` | ppcart_enabled_processors |
| `ppcart_enqueue_admin_tax_settings` | Action | `do_action` | Public | canonical | `admin/traits/templates/admin-assets-enqueue-scripts.php` | ppcart_enqueue_admin_tax_settings |
| `ppcart_enqueue_frontend_assets` | Action | `add_action` | Public | canonical | `public/controllers/class-ppcart-public-asset-controller.php` | ppcart_enqueue_frontend_assets |
| `ppcart_enqueue_frontend_assets` | Action | `do_action` | Public | canonical | `public/controllers/page/traits/trait-ppcart-public-page-shortcodes.php` | ppcart_enqueue_frontend_assets |
| `ppcart_enqueue_frontend_assets` | Action | `do_action` | Public | canonical | `public/controllers/traits/templates/account-shortcodes-my-account-page-shortcode.php` | ppcart_enqueue_frontend_assets |
| `ppcart_enqueue_frontend_assets` | Action | `do_action` | Public | canonical | `public/controllers/traits/trait-ppcart-public-account-shortcodes.php` | ppcart_enqueue_frontend_assets |
| `ppcart_enqueue_public_tax_settings` | Action | `do_action` | Public | canonical | `public/controllers/templates/enqueue-tracking-scripts.php` | ppcart_enqueue_public_tax_settings |
| `ppcart_enqueue_scripts_upsell_downsell` | Filter | `apply_filters` | Public | canonical | `public/controllers/templates/enqueue-tracking-scripts.php` | ppcart_enqueue_scripts_upsell_downsell |
| `ppcart_expected_request_fields` | Filter | `apply_filters` | Public | canonical | `includes/functions/request-sanitization.php` | ppcart_expected_request_fields |
| `ppcart_export_columns` | Filter | `apply_filters` | Public | canonical | `public/partials/csv-export.php` | ppcart_export_columns |
| `ppcart_express_payment_method_fields` | Action | `add_action` | Public | canonical | `public/templates/functions/payment-address.php` | ppcart_express_payment_method_fields |
| `ppcart_express_payment_method_fields` | Action | `do_action` | Public | canonical | `public/templates/functions/summary-and-scripts.php` | ppcart_express_payment_method_fields |
| `ppcart_extension_action` | Filter | `apply_filters` | Public | canonical | `admin/class-ppcart-extension-page.php` | ppcart_extension_action |
| `ppcart_extensions_list` | Filter | `apply_filters` | Public | canonical | `admin/class-ppcart-extension-page.php` | ppcart_extensions_list |
| `ppcart_file_download_db_args` | Filter | `apply_filters` | Public | canonical | `includes/files/traits/trait-ppcart-files-order.php` | ppcart_file_download_db_args |
| `ppcart_format_price` | Filter | `apply_filters` | Public | canonical | `includes/functions/orders-products-and-formatting.php` | ppcart_format_price |
| `ppcart_format_subscription_order_detail` | Filter | `add_filter` | Public | canonical | `includes/functions/plans-and-subscriptions.php` | ppcart_format_subscription_order_detail |
| `ppcart_format_subscription_order_detail` | Filter | `apply_filters` | Public | canonical | `includes/functions/order-items-and-details.php` | ppcart_format_subscription_order_detail |
| `ppcart_format_subscription_order_detail` | Filter | `apply_filters` | Public | canonical | `includes/order-items/order-details-renderer.php` | ppcart_format_subscription_order_detail |
| `ppcart_format_subscription_order_detail` | Filter | `apply_filters` | Public | canonical | `public/controllers/order/traits/trait-ppcart-public-order-cart.php` | ppcart_format_subscription_order_detail |
| `ppcart_frontend_allowed_html` | Filter | `apply_filters` | Public | canonical | `includes/functions.php` | ppcart_frontend_allowed_html |
| `ppcart_frontend_assets_needed` | Filter | `apply_filters` | Public | canonical | `public/controllers/class-ppcart-public-asset-controller.php` | ppcart_frontend_assets_needed |
| `ppcart_frontend_message_{$k}` | Filter | `apply_filters` | Public | canonical | `includes/functions/merge-tags-and-dates.php` | ppcart_frontend_message_{$k} |
| `ppcart_fter_order_created` | Action | `do_action` | Public | canonical | `includes/stripe/templates/order-save.php` | ppcart_fter_order_created |
| `ppcart_fter_order_created` | Action | `do_action` | Public | canonical | `includes/stripe/traits/trait-ppcart-stripe-subscription-save.php` | ppcart_fter_order_created |
| `ppcart_gateway_webhook` | Action | `do_action` | Public | canonical | `public/class-ppcart-public.php` | ppcart_gateway_webhook |
| `ppcart_get_sub_args` | Filter | `apply_filters` | Public | canonical | `models/traits/templates/subscription-storage-get-by-sub-id.php` | ppcart_get_sub_args |
| `ppcart_host_purchase_url` | Filter | `apply_filters` | Public | canonical | `includes/helpers/ppcart-hosted-checkout.php` | ppcart_host_purchase_url |
| `ppcart_host_purchase_url` | Filter | `apply_filters` | Public | canonical | `public/controllers/order/traits/templates/order-save-save-order-to-db.php` | ppcart_host_purchase_url |
| `ppcart_host_purchase_url` | Filter | `apply_filters` | Public | canonical | `public/controllers/payment/traits/templates/payment-intent-create-payment-intent.php` | ppcart_host_purchase_url |
| `ppcart_host_purchase_url` | Filter | `apply_filters` | Public | canonical | `public/controllers/traits/templates/subscription-create-create-subscription.php` | ppcart_host_purchase_url |
| `ppcart_host_purchase_url` | Filter | `apply_filters` | Public | canonical | `public/paypal/traits/templates/paypal-requests-ppcart-paypal-process-payment.php` | ppcart_host_purchase_url |
| `ppcart_host_purchase_url` | Filter | `apply_filters` | Public | canonical | `public/paypal/traits/templates/paypal-requests-ppcart-paypal-request.php` | ppcart_host_purchase_url |
| `ppcart_integration_fields` | Filter | `add_filter` | Public | canonical | `admin/class-ppcart-product-metaboxes.php` | ppcart_integration_fields |
| `ppcart_integration_fields` | Filter | `add_filter` | Public | canonical | `includes/integrations/CancelSubscription.php` | ppcart_integration_fields |
| `ppcart_integration_fields` | Filter | `add_filter` | Public | canonical | `includes/integrations/templates/ppcart-kit-init.php` | ppcart_integration_fields |
| `ppcart_integration_fields` | Filter | `apply_filters` | Public | canonical | `admin/metaboxes/traits/templates/product-metabox-set-integration-field-group.php` | ppcart_integration_fields |
| `ppcart_integration_plan_targets` | Filter | `apply_filters` | Public | canonical | `admin/metaboxes/traits/templates/product-metabox-plan-options-get-payment-plans.php` | ppcart_integration_plan_targets |
| `ppcart_integration_service_action_field_logic_options` | Filter | `apply_filters` | Public | canonical | `admin/metaboxes/traits/templates/integration-fields/fields-01.php` | ppcart_integration_service_action_field_logic_options |
| `ppcart_integration_trigger_options` | Filter | `apply_filters` | Public | canonical | `admin/metaboxes/traits/trait-ppcart-product-metabox-plan-options.php` | ppcart_integration_trigger_options |
| `ppcart_integration_validation_error` | Filter | `apply_filters` | Public | canonical | `includes/functions/payment-actions-and-refunds.php` | ppcart_integration_validation_error |
| `ppcart_integrations` | Filter | `add_filter` | Public | canonical | `includes/integrations/CancelSubscription.php` | ppcart_integrations |
| `ppcart_integrations` | Filter | `add_filter` | Public | canonical | `includes/integrations/templates/ppcart-kit-init.php` | ppcart_integrations |
| `ppcart_integrations` | Filter | `apply_filters` | Public | canonical | `admin/metaboxes/traits/templates/product-metabox-plan-options-get-ppcart-service-type.php` | ppcart_integrations |
| `ppcart_invoice_date_documentation_url` | Filter | `apply_filters` | Public | canonical | `admin/settings/traits/options/invoice-fields.php` | ppcart_invoice_date_documentation_url |
| `ppcart_invoice_format` | Filter | `apply_filters` | Public | canonical | `includes/functions/orders-products-and-formatting.php` | ppcart_invoice_format |
| `ppcart_is_order_complete` | Filter | `add_filter` | Public | canonical | `includes/stripe/traits/templates/stripe-order-save-do-stripe-order-save.php` | ppcart_is_order_complete |
| `ppcart_is_order_complete` | Filter | `apply_filters` | Public | canonical | `includes/stripe/traits/trait-ppcart-stripe-order-save.php` | ppcart_is_order_complete |
| `ppcart_is_sensitive_option` | Filter | `apply_filters` | Public | canonical | `includes/secrets/traits/trait-ppcart-secrets-config.php` | ppcart_is_sensitive_option |
| `ppcart_is_sub_type_valid_for_cancel` | Filter | `apply_filters` | Public | canonical | `public/templates/my-account/subscription-detail.php` | ppcart_is_sub_type_valid_for_cancel |
| `ppcart_is_sub_type_valid_for_pause_restart` | Filter | `apply_filters` | Public | canonical | `public/templates/my-account/subscription-detail.php` | ppcart_is_sub_type_valid_for_pause_restart |
| `ppcart_js_purchase_tracking` | Action | `add_action` | Public | canonical | `public/controllers/class-ppcart-public-asset-controller.php` | ppcart_js_purchase_tracking |
| `ppcart_js_purchase_tracking` | Action | `do_action` | Public | canonical | `public/controllers/templates/order-tracking-script.php` | ppcart_js_purchase_tracking |
| `ppcart_load_pro_modules` | Action | `do_action` | Public | canonical | `includes/bootstrap/class-ppcart-dependency-loader.php` | ppcart_load_pro_modules |
| `ppcart_loaded` | Action | `do_action` | Public | canonical | `publishpress-cart.php` | ppcart_loaded |
| `ppcart_login_after_{$template_name}` | Action | `do_action` | Public | canonical | `public/controllers/class-ppcart-public-account-controller.php` | ppcart_login_after_{$template_name} |
| `ppcart_login_before_{$template_name}` | Action | `do_action` | Public | canonical | `public/controllers/class-ppcart-public-account-controller.php` | ppcart_login_before_{$template_name} |
| `ppcart_low_stock_threshold` | Filter | `apply_filters` | Public | canonical | `includes/functions/integrations-and-stock.php` | ppcart_low_stock_threshold |
| `ppcart_mailchimp_merge_data` | Filter | `apply_filters` | Public | canonical | `includes/functions/orders-products-and-formatting.php` | ppcart_mailchimp_merge_data |
| `ppcart_my_account_show_order_detail_link` | Filter | `apply_filters` | Public | canonical | `public/templates/my-account/tabs/order-history.php` | ppcart_my_account_show_order_detail_link |
| `ppcart_my_account_subscription_detail_after_details` | Action | `do_action` | Public | canonical | `public/templates/my-account/subscription-detail.php` | ppcart_my_account_subscription_detail_after_details |
| `ppcart_my_account_tab_content` | Filter | `apply_filters` | Public | canonical | `public/controllers/traits/templates/account-shortcodes-my-account-page-shortcode.php` | ppcart_my_account_tab_content |
| `ppcart_notification_email_to` | Filter | `apply_filters` | Public | canonical | `includes/functions/users-and-notifications.php` | ppcart_notification_email_to |
| `ppcart_order` | Filter | `apply_filters` | Public | canonical | `admin/controllers/order/templates/product-form.php` | ppcart_order |
| `ppcart_order` | Filter | `apply_filters` | Public | canonical | `admin/controllers/order/templates/product-info-metabox.php` | ppcart_order |
| `ppcart_order` | Filter | `apply_filters` | Public | canonical | `admin/controllers/templates/order-list-order-column.php` | ppcart_order |
| `ppcart_order` | Filter | `apply_filters` | Public | canonical | `includes/functions/integrations-and-stock.php` | ppcart_order |
| `ppcart_order` | Filter | `apply_filters` | Public | canonical | `includes/functions/order-items-and-details.php` | ppcart_order |
| `ppcart_order` | Filter | `apply_filters` | Public | canonical | `includes/order-items/order-details-renderer.php` | ppcart_order |
| `ppcart_order` | Filter | `apply_filters` | Public | canonical | `models/order/traits/trait-ppcart-order-lookups.php` | ppcart_order |
| `ppcart_order` | Filter | `apply_filters` | Public | canonical | `public/templates/pdf-invoice/invoice.php` | ppcart_order |
| `ppcart_order_add_bump_items_from_post` | Action | `do_action` | Public | canonical | `models/order/traits/trait-ppcart-order-checkout-input.php` | ppcart_order_add_bump_items_from_post |
| `ppcart_order_after_apply_cart_coupon` | Action | `do_action` | Public | canonical | `models/order/traits/templates/order-checkout-input-load-from-post.php` | ppcart_order_after_apply_cart_coupon |
| `ppcart_order_after_apply_plan_coupon` | Action | `do_action` | Public | canonical | `models/order/traits/templates/order-checkout-input-load-from-post.php` | ppcart_order_after_apply_plan_coupon |
| `ppcart_order_after_primary_integration_action` | Action | `do_action` | Public | canonical | `includes/functions/integrations-and-stock.php` | ppcart_order_after_primary_integration_action |
| `ppcart_order_apply_cart_coupon` | Filter | `apply_filters` | Public | canonical | `models/order/traits/trait-ppcart-order-checkout-input.php` | ppcart_order_apply_cart_coupon |
| `ppcart_order_apply_plan_coupon` | Filter | `apply_filters` | Public | canonical | `models/order/traits/trait-ppcart-order-checkout-input.php` | ppcart_order_apply_plan_coupon |
| `ppcart_order_apply_tax_to_item` | Filter | `apply_filters` | Public | canonical | `models/order/traits/trait-ppcart-order-amounts.php` | ppcart_order_apply_tax_to_item |
| `ppcart_order_before_apply_cart_coupon` | Action | `do_action` | Public | canonical | `models/order/traits/templates/order-checkout-input-load-from-post.php` | ppcart_order_before_apply_cart_coupon |
| `ppcart_order_before_apply_plan_coupon` | Action | `do_action` | Public | canonical | `models/order/traits/templates/order-checkout-input-load-from-post.php` | ppcart_order_before_apply_plan_coupon |
| `ppcart_order_before_complete_integrations` | Action | `do_action` | Public | canonical | `includes/functions/integrations-and-stock.php` | ppcart_order_before_complete_integrations |
| `ppcart_order_calculate_tax` | Filter | `apply_filters` | Public | canonical | `models/order/traits/trait-ppcart-order-amounts.php` | ppcart_order_calculate_tax |
| `ppcart_order_child_of` | Filter | `apply_filters` | Public | canonical | `models/order/traits/trait-ppcart-order-lookups.php` | ppcart_order_child_of |
| `ppcart_order_complete` | Action | `add_action` | Public | canonical | `public/class-ppcart-public.php` | ppcart_order_complete |
| `ppcart_order_created` | Action | `add_action` | Public | canonical | `includes/files/class-ppcart-files.php` | ppcart_order_created |
| `ppcart_order_created` | Action | `do_action` | Public | canonical | `models/order/traits/templates/order-persistence-create.php` | ppcart_order_created |
| `ppcart_order_details` | Action | `do_action` | Public | canonical | `admin/controllers/order/templates/product-info-metabox.php` | ppcart_order_details |
| `ppcart_order_details_link` | Filter | `apply_filters` | Public | canonical | `admin/controllers/order/templates/product-info-metabox.php` | ppcart_order_details_link |
| `ppcart_order_download_shortcode_tags` | Filter | `apply_filters` | Public | canonical | `includes/functions/users-and-notifications.php` | ppcart_order_download_shortcode_tags |
| `ppcart_order_form_address_fields` | Filter | `add_filter` | Public | canonical | `includes/functions/ajax-and-fields.php` | ppcart_order_form_address_fields |
| `ppcart_order_form_address_fields` | Filter | `apply_filters` | Public | canonical | `includes/functions/request-sanitization.php` | ppcart_order_form_address_fields |
| `ppcart_order_form_address_fields` | Filter | `apply_filters` | Public | canonical | `public/controllers/checkout/traits/templates/checkout-validation-validate-order-form.php` | ppcart_order_form_address_fields |
| `ppcart_order_form_address_fields` | Filter | `apply_filters` | Public | canonical | `public/templates/functions/payment-address.php` | ppcart_order_form_address_fields |
| `ppcart_order_form_fields` | Filter | `add_filter` | Public | canonical | `includes/functions/ajax-and-fields.php` | ppcart_order_form_fields |
| `ppcart_order_form_fields` | Filter | `apply_filters` | Public | canonical | `includes/functions/request-sanitization.php` | ppcart_order_form_fields |
| `ppcart_order_form_fields` | Filter | `apply_filters` | Public | canonical | `public/controllers/checkout/traits/templates/checkout-validation-validate-order-form.php` | ppcart_order_form_fields |
| `ppcart_order_form_fields` | Filter | `apply_filters` | Public | canonical | `public/templates/functions/plan-coupon-fields.php` | ppcart_order_form_fields |
| `ppcart_order_get_downsell` | Filter | `apply_filters` | Public | canonical | `models/order/traits/trait-ppcart-order-lookups.php` | ppcart_order_get_downsell |
| `ppcart_order_get_upsell` | Filter | `apply_filters` | Public | canonical | `models/order/traits/trait-ppcart-order-lookups.php` | ppcart_order_get_upsell |
| `ppcart_order_item_meta` | Filter | `apply_filters` | Public | canonical | `includes/order-items/templates/ppcart-order-item-initialize.php` | ppcart_order_item_meta |
| `ppcart_order_lead` | Action | `do_action` | Public | canonical | `includes/functions/integrations-and-stock.php` | ppcart_order_lead |
| `ppcart_order_load_coupon_from_post` | Filter | `apply_filters` | Public | canonical | `models/order/traits/trait-ppcart-order-checkout-input.php` | ppcart_order_load_coupon_from_post |
| `ppcart_order_pending` | Action | `add_action` | Public | canonical | `public/class-ppcart-public.php` | ppcart_order_pending |
| `ppcart_order_pre_calculate_tax` | Action | `do_action` | Public | canonical | `models/order/traits/templates/order-checkout-input-load-from-post.php` | ppcart_order_pre_calculate_tax |
| `ppcart_order_refund_paypal` | Action | `add_action` | Public | canonical | `public/class-ppcart-paypal.php` | ppcart_order_refund_paypal |
| `ppcart_order_refund_{$order}{->}{pay_method}` | Action | `do_action` | Public | canonical | `includes/functions/payment-actions-and-refunds.php` | ppcart_order_refund_{$order}{->}{pay_method} |
| `ppcart_order_refunded` | Action | `add_action` | Public | canonical | `includes/files/class-ppcart-files.php` | ppcart_order_refunded |
| `ppcart_order_refunded` | Action | `add_action` | Public | canonical | `public/class-ppcart-public.php` | ppcart_order_refunded |
| `ppcart_order_related_orders` | Action | `do_action` | Public | canonical | `admin/controllers/order/templates/product-form.php` | ppcart_order_related_orders |
| `ppcart_order_save_override` | Filter | `apply_filters` | Public | canonical | `public/controllers/order/traits/templates/order-save-save-order-to-db.php` | ppcart_order_save_override |
| `ppcart_order_setup_purchase_note` | Filter | `apply_filters` | Public | canonical | `models/order/traits/templates/order-checkout-input-setup-atts-from-post.php` | ppcart_order_setup_purchase_note |
| `ppcart_order_setup_tax` | Action | `do_action` | Public | canonical | `models/order/traits/trait-ppcart-order-amounts.php` | ppcart_order_setup_tax |
| `ppcart_order_store_pro_metadata` | Action | `do_action` | Public | canonical | `includes/stripe/templates/order-save.php` | ppcart_order_store_pro_metadata |
| `ppcart_order_summary` | Action | `add_action` | Public | canonical | `public/templates/checkout-shortcode.php` | ppcart_order_summary |
| `ppcart_order_summary` | Action | `add_action` | Public | canonical | `public/templates/checkout1.php` | ppcart_order_summary |
| `ppcart_order_summary` | Action | `do_action` | Public | canonical | `public/templates/functions/checkout-core.php` | ppcart_order_summary |
| `ppcart_order_summary_coupon_text` | Filter | `apply_filters` | Public | canonical | `models/order/traits/trait-ppcart-order-amounts.php` | ppcart_order_summary_coupon_text |
| `ppcart_order_summary_item_name` | Filter | `apply_filters` | Public | canonical | `models/order/traits/trait-ppcart-order-amounts.php` | ppcart_order_summary_item_name |
| `ppcart_order_summary_items` | Action | `do_action` | Public | canonical | `public/templates/checkout1.php` | ppcart_order_summary_items |
| `ppcart_order_summary_items` | Action | `do_action` | Public | canonical | `public/templates/functions/summary-and-scripts.php` | ppcart_order_summary_items |
| `ppcart_order_updated` | Action | `do_action` | Public | canonical | `models/order/traits/trait-ppcart-order-persistence.php` | ppcart_order_updated |
| `ppcart_orderform_before_payment_plans` | Action | `do_action` | Public | canonical | `public/templates/functions/plan-coupon-fields.php` | ppcart_orderform_before_payment_plans |
| `ppcart_page_metabox` | Action | `do_action` | Public | canonical | `admin/controllers/order/traits/trait-ppcart-admin-order-metabox.php` | ppcart_page_metabox |
| `ppcart_pay_plan_fields` | Filter | `apply_filters` | Public | canonical | `admin/metaboxes/traits/trait-ppcart-product-metaboxes-pay-plan-fields.php` | ppcart_pay_plan_fields |
| `ppcart_pay_plan_recurring_fields` | Filter | `apply_filters` | Public | canonical | `admin/metaboxes/traits/trait-ppcart-product-metaboxes-pay-plan-fields.php` | ppcart_pay_plan_recurring_fields |
| `ppcart_payment_confirmation` | Action | `add_action` | Public | canonical | `public/templates/checkout1.php` | ppcart_payment_confirmation |
| `ppcart_payment_confirmation` | Action | `do_action` | Public | canonical | `public/templates/checkout1.php` | ppcart_payment_confirmation |
| `ppcart_payment_intent` | Filter | `apply_filters` | Public | canonical | `admin/controllers/order/templates/product-form.php` | ppcart_payment_intent |
| `ppcart_payment_method` | Filter | `apply_filters` | Public | canonical | `admin/controllers/order/templates/product-form.php` | ppcart_payment_method |
| `ppcart_payment_method_change` | Action | `add_action` | Public | canonical | `public/templates/checkout1.php` | ppcart_payment_method_change |
| `ppcart_payment_method_change` | Action | `do_action` | Public | canonical | `public/templates/checkout1.php` | ppcart_payment_method_change |
| `ppcart_payment_method_fields` | Action | `add_action` | Public | canonical | `public/templates/functions/payment-address.php` | ppcart_payment_method_fields |
| `ppcart_payment_method_fields` | Action | `do_action` | Public | canonical | `public/templates/functions/payment-address.php` | ppcart_payment_method_fields |
| `ppcart_payment_methods` | Filter | `add_filter` | Public | canonical | `public/class-ppcart-paypal.php` | ppcart_payment_methods |
| `ppcart_payment_methods` | Filter | `apply_filters` | Public | canonical | `includes/integrations/gutenberg/lib/checkout-renderer/trait-ppcart-checkout-renderer-request.php` | ppcart_payment_methods |
| `ppcart_payment_methods` | Filter | `apply_filters` | Public | canonical | `public/templates/functions/payment-address.php` | ppcart_payment_methods |
| `ppcart_paypal_after_checkout_complete` | Filter | `apply_filters` | Public | canonical | `public/paypal/traits/templates/paypal-requests-ppcart-paypal-process-payment.php` | ppcart_paypal_after_checkout_complete |
| `ppcart_paypal_checkout_url` | Filter | `apply_filters` | Public | canonical | `public/paypal/traits/templates/paypal-requests-ppcart-paypal-request.php` | ppcart_paypal_checkout_url |
| `ppcart_paypal_class` | Filter | `apply_filters` | Public | canonical | `includes/bootstrap/class-ppcart-dependency-loader.php` | ppcart_paypal_class |
| `ppcart_paypal_custom_payment_vars` | Filter | `apply_filters` | Public | canonical | `public/paypal/traits/templates/paypal-requests-ppcart-build-paypal-url.php` | ppcart_paypal_custom_payment_vars |
| `ppcart_paypal_payment_vars` | Filter | `apply_filters` | Public | canonical | `public/paypal/traits/templates/paypal-requests-ppcart-build-paypal-url.php` | ppcart_paypal_payment_vars |
| `ppcart_paypal_recurring_payment_data` | Action | `do_action` | Public | canonical | `public/webhooks/paypal.php` | ppcart_paypal_recurring_payment_data |
| `ppcart_personalize_replacements` | Filter | `add_filter` | Public | canonical | `includes/email/ppcart-template-functions/options-and-hooks.php` | ppcart_personalize_replacements |
| `ppcart_personalize_replacements` | Filter | `apply_filters` | Public | canonical | `includes/functions/merge-tags-and-dates.php` | ppcart_personalize_replacements |
| `ppcart_plan` | Filter | `apply_filters` | Public | canonical | `includes/functions/plans-and-subscriptions.php` | ppcart_plan |
| `ppcart_plan_at_checkout` | Filter | `apply_filters` | Public | canonical | `models/order/traits/templates/order-checkout-input-setup-atts-from-post.php` | ppcart_plan_at_checkout |
| `ppcart_plan_heading` | Filter | `apply_filters` | Public | canonical | `public/templates/functions/plan-coupon-fields.php` | ppcart_plan_heading |
| `ppcart_plan_subscription_features` | Filter | `apply_filters` | Public | canonical | `includes/functions/plans-and-subscriptions.php` | ppcart_plan_subscription_features |
| `ppcart_plan_text_and_a` | Filter | `apply_filters` | Public | canonical | `includes/functions/merge-tags-and-dates.php` | ppcart_plan_text_and_a |
| `ppcart_plan_text_day_free_trial` | Filter | `apply_filters` | Public | canonical | `includes/functions/merge-tags-and-dates.php` | ppcart_plan_text_day_free_trial |
| `ppcart_plan_text_day_free_trial` | Filter | `apply_filters` | Public | canonical | `includes/functions/plans-and-subscriptions.php` | ppcart_plan_text_day_free_trial |
| `ppcart_plan_text_loading_processing` | Filter | `apply_filters` | Public | canonical | `includes/functions/merge-tags-and-dates.php` | ppcart_plan_text_loading_processing |
| `ppcart_plan_text_sign_up_fee` | Filter | `apply_filters` | Public | canonical | `includes/functions/merge-tags-and-dates.php` | ppcart_plan_text_sign_up_fee |
| `ppcart_plan_text_sign_up_fee` | Filter | `apply_filters` | Public | canonical | `includes/functions/plans-and-subscriptions.php` | ppcart_plan_text_sign_up_fee |
| `ppcart_plan_text_with_a` | Filter | `apply_filters` | Public | canonical | `includes/functions/merge-tags-and-dates.php` | ppcart_plan_text_with_a |
| `ppcart_plugin_title` | Filter | `apply_filters` | Public | canonical | `admin/class-ppcart-contacts-page.php` | ppcart_plugin_title |
| `ppcart_plugin_title` | Filter | `apply_filters` | Public | canonical | `admin/controllers/order/traits/trait-ppcart-admin-order-metabox.php` | ppcart_plugin_title |
| `ppcart_plugin_title` | Filter | `apply_filters` | Public | canonical | `admin/dashboard/class-ppcart-dashboard-widget.php` | ppcart_plugin_title |
| `ppcart_plugin_title` | Filter | `apply_filters` | Public | canonical | `admin/partials/ppcart-admin-page-settings.php` | ppcart_plugin_title |
| `ppcart_plugin_title` | Filter | `apply_filters` | Public | canonical | `admin/reports/templates/admin-reports-page.php` | ppcart_plugin_title |
| `ppcart_plugin_title` | Filter | `apply_filters` | Public | canonical | `admin/settings/traits/trait-ppcart-admin-settings-screen.php` | ppcart_plugin_title |
| `ppcart_plugin_title` | Filter | `apply_filters` | Public | canonical | `admin/templates/extensions-page.php` | ppcart_plugin_title |
| `ppcart_plugin_title` | Filter | `apply_filters` | Public | canonical | `admin/traits/templates/notices-admin-notices.php` | ppcart_plugin_title |
| `ppcart_plugin_title` | Filter | `apply_filters` | Public | canonical | `admin/traits/trait-ppcart-admin-privacy.php` | ppcart_plugin_title |
| `ppcart_plugin_title` | Filter | `apply_filters` | Public | canonical | `includes/integrations/gutenberg/lib/checkout-renderer/trait-ppcart-checkout-renderer-request.php` | ppcart_plugin_title |
| `ppcart_plugin_title` | Filter | `apply_filters` | Public | canonical | `includes/integrations/gutenberg/lib/class-ppcart-gutenberg-assets.php` | ppcart_plugin_title |
| `ppcart_plugin_title` | Filter | `apply_filters` | Public | canonical | `includes/schedule-event.php` | ppcart_plugin_title |
| `ppcart_plugin_title` | Filter | `apply_filters` | Public | canonical | `public/partials/csv-export.php` | ppcart_plugin_title |
| `ppcart_plugin_title` | Filter | `apply_filters` | Public | canonical | `public/webhooks/paypal.php` | ppcart_plugin_title |
| `ppcart_post_sanitize` | Action | `do_action` | Public | canonical | `includes/templates/ppcart-sanitize-clean.php` | ppcart_post_sanitize |
| `ppcart_post_types` | Filter | `apply_filters` | Public | canonical | `includes/templates/post-types-create-custom-post-type.php` | ppcart_post_types |
| `ppcart_pre_sanitize` | Action | `do_action` | Public | canonical | `includes/templates/ppcart-sanitize-clean.php` | ppcart_pre_sanitize |
| `ppcart_preloaded_intent_cleanup_force_archive_attempts` | Filter | `apply_filters` | Public | canonical | `public/controllers/payment/traits/templates/payment-preload-cleanup-preloaded-intents.php` | ppcart_preloaded_intent_cleanup_force_archive_attempts |
| `ppcart_preloaded_intent_ttl` | Filter | `apply_filters` | Public | canonical | `public/controllers/payment/traits/templates/payment-preload-cleanup-preloaded-intents.php` | ppcart_preloaded_intent_ttl |
| `ppcart_pricing_fields` | Filter | `apply_filters` | Public | canonical | `admin/metaboxes/traits/trait-ppcart-product-metaboxes-sales-fields.php` | ppcart_pricing_fields |
| `ppcart_pro_locked_emails` | Filter | `apply_filters` | Public | canonical | `includes/helpers/pro-locks/field-and-email-locks.php` | ppcart_pro_locked_emails |
| `ppcart_pro_locked_fields` | Filter | `apply_filters` | Public | canonical | `includes/helpers/pro-locks/settings-locks.php` | ppcart_pro_locked_fields |
| `ppcart_pro_locked_integration_keys` | Filter | `apply_filters` | Public | canonical | `includes/helpers/pro-locks/settings-locks.php` | ppcart_pro_locked_integration_keys |
| `ppcart_pro_locked_payment_methods` | Filter | `apply_filters` | Public | canonical | `includes/helpers/pro-locks/settings-locks.php` | ppcart_pro_locked_payment_methods |
| `ppcart_pro_locked_product_fields` | Filter | `apply_filters` | Public | canonical | `includes/helpers/pro-locks/product-locks.php` | ppcart_pro_locked_product_fields |
| `ppcart_pro_locked_product_tabs` | Filter | `apply_filters` | Public | canonical | `includes/helpers/pro-locks/field-and-email-locks.php` | ppcart_pro_locked_product_tabs |
| `ppcart_pro_locked_settings_field_sections` | Filter | `apply_filters` | Public | canonical | `includes/helpers/pro-locks/field-and-email-locks.php` | ppcart_pro_locked_settings_field_sections |
| `ppcart_pro_locked_tabs` | Filter | `apply_filters` | Public | canonical | `includes/helpers/pro-locks/settings-locks.php` | ppcart_pro_locked_tabs |
| `ppcart_pro_upgrade_url` | Filter | `apply_filters` | Public | canonical | `includes/helpers/pro-locks/settings-locks.php` | ppcart_pro_upgrade_url |
| `ppcart_process_payment_upsell_flow` | Filter | `apply_filters` | Public | canonical | `public/controllers/checkout/traits/templates/checkout-payment-ppcart-process-payment.php` | ppcart_process_payment_upsell_flow |
| `ppcart_process_stripe_products` | Filter | `apply_filters` | Public | canonical | `admin/metaboxes/traits/templates/product-metabox-save-validate-meta.php` | ppcart_process_stripe_products |
| `ppcart_product` | Filter | `apply_filters` | Public | canonical | `includes/functions/product-and-order-setup.php` | ppcart_product |
| `ppcart_product_archive_args` | Filter | `apply_filters` | Public | canonical | `public/controllers/page/traits/trait-ppcart-public-page-shortcodes.php` | ppcart_product_archive_args |
| `ppcart_product_duplicate_excluded_meta_keys` | Filter | `apply_filters` | Public | canonical | `includes/class-ppcart-product-duplicator.php` | ppcart_product_duplicate_excluded_meta_keys |
| `ppcart_product_duplicate_meta_key` | Filter | `apply_filters` | Public | canonical | `includes/class-ppcart-product-duplicator.php` | ppcart_product_duplicate_meta_key |
| `ppcart_product_field_groups` | Filter | `add_filter` | Public | canonical | `includes/files/class-ppcart-files.php` | ppcart_product_field_groups |
| `ppcart_product_field_groups` | Filter | `apply_filters` | Public | canonical | `admin/metaboxes/traits/trait-ppcart-product-metaboxes-render.php` | ppcart_product_field_groups |
| `ppcart_product_field_scripts` | Filter | `apply_filters` | Public | canonical | `admin/metaboxes/traits/templates/product-metabox-render-product-settings-fields.php` | ppcart_product_field_scripts |
| `ppcart_product_general_fields` | Filter | `apply_filters` | Public | canonical | `admin/metaboxes/traits/trait-ppcart-product-metaboxes-general-fields.php` | ppcart_product_general_fields |
| `ppcart_product_metabox_post_type` | Filter | `apply_filters` | Public | canonical | `admin/metaboxes/traits/templates/product-metabox-render-product-settings-fields.php` | ppcart_product_metabox_post_type |
| `ppcart_product_metabox_post_type` | Filter | `apply_filters` | Public | canonical | `admin/metaboxes/traits/templates/product-metabox-save-validate-meta.php` | ppcart_product_metabox_post_type |
| `ppcart_product_metabox_post_type` | Filter | `apply_filters` | Public | canonical | `admin/metaboxes/traits/trait-ppcart-product-metaboxes-render.php` | ppcart_product_metabox_post_type |
| `ppcart_product_metabox_post_type` | Filter | `apply_filters` | Public | canonical | `admin/metaboxes/traits/trait-ppcart-product-metaboxes-save.php` | ppcart_product_metabox_post_type |
| `ppcart_product_page_redirect` | Action | `do_action` | Public | canonical | `public/controllers/page/traits/templates/page-routing-ppcart-redirect.php` | ppcart_product_page_redirect |
| `ppcart_product_payments_fields` | Filter | `apply_filters` | Public | canonical | `admin/metaboxes/traits/trait-ppcart-product-metaboxes-sales-fields.php` | ppcart_product_payments_fields |
| `ppcart_product_paypal_enabled` | Filter | `apply_filters` | Public | canonical | `public/paypal/traits/trait-ppcart-paypal-settings.php` | ppcart_product_paypal_enabled |
| `ppcart_product_post_type` | Filter | `apply_filters` | Public | canonical | `includes/helpers/ppcart-live.php` | ppcart_product_post_type |
| `ppcart_product_post_type` | Filter | `apply_filters` | Public | canonical | `includes/integrations/gutenberg/lib/checkout-renderer/trait-ppcart-checkout-renderer-request.php` | ppcart_product_post_type |
| `ppcart_product_post_type` | Filter | `apply_filters` | Public | canonical | `includes/integrations/gutenberg/templates/class-ppcart-product-template.php` | ppcart_product_post_type |
| `ppcart_product_post_type` | Filter | `apply_filters` | Public | canonical | `public/class-ppcart-public.php` | ppcart_product_post_type |
| `ppcart_product_post_type` | Filter | `apply_filters` | Public | canonical | `public/controllers/page/traits/templates/page-routing-ppcart-redirect.php` | ppcart_product_post_type |
| `ppcart_product_post_type` | Filter | `apply_filters` | Public | canonical | `public/controllers/page/traits/templates/page-shortcodes-ppcart-product-shortcode.php` | ppcart_product_post_type |
| `ppcart_product_post_type` | Filter | `apply_filters` | Public | canonical | `public/controllers/page/traits/trait-ppcart-public-page-template.php` | ppcart_product_post_type |
| `ppcart_product_post_type` | Filter | `apply_filters` | Public | canonical | `public/controllers/templates/enqueue-tracking-scripts.php` | ppcart_product_post_type |
| `ppcart_product_post_type` | Filter | `apply_filters` | Public | canonical | `public/templates/checkout-shortcode.php` | ppcart_product_post_type |
| `ppcart_product_post_type` | Filter | `apply_filters` | Public | canonical | `public/templates/checkout1.php` | ppcart_product_post_type |
| `ppcart_product_post_type` | Filter | `apply_filters` | Public | canonical | `public/templates/functions/checkout-core.php` | ppcart_product_post_type |
| `ppcart_product_price` | Filter | `apply_filters` | Public | canonical | `includes/functions/product-and-order-setup.php` | ppcart_product_price |
| `ppcart_product_print_field_scripts` | Action | `do_action` | Public | canonical | `admin/metaboxes/traits/templates/product-metabox-render-product-settings-fields.php` | ppcart_product_print_field_scripts |
| `ppcart_product_save_stripe_meta` | Action | `do_action` | Public | canonical | `admin/templates/product-admin-save-stripe-objects.php` | ppcart_product_save_stripe_meta |
| `ppcart_product_setting_tab_fields` | Filter | `apply_filters` | Public | canonical | `admin/metaboxes/traits/trait-ppcart-product-metaboxes-render.php` | ppcart_product_setting_tab_fields |
| `ppcart_product_setting_tab_files_fields` | Filter | `add_filter` | Public | canonical | `includes/files/class-ppcart-files.php` | ppcart_product_setting_tab_files_fields |
| `ppcart_product_setting_tab_{$tab_id}_fields` | Filter | `apply_filters` | Public | canonical | `admin/metaboxes/traits/trait-ppcart-product-metaboxes-render.php` | ppcart_product_setting_tab_{$tab_id}_fields |
| `ppcart_product_setting_tabs` | Filter | `add_filter` | Public | canonical | `includes/files/class-ppcart-files.php` | ppcart_product_setting_tabs |
| `ppcart_product_setting_tabs` | Filter | `apply_filters` | Public | canonical | `admin/metaboxes/traits/templates/product-metabox-render-get-product-setting-tabs.php` | ppcart_product_setting_tabs |
| `ppcart_product_{$tab_id}_fields` | Filter | `apply_filters` | Public | canonical | `admin/metaboxes/traits/trait-ppcart-product-metaboxes-render.php` | ppcart_product_{$tab_id}_fields |
| `ppcart_ransaction_id` | Filter | `apply_filters` | Public | canonical | `includes/functions/orders-products-and-formatting.php` | ppcart_ransaction_id |
| `ppcart_receipt_after_order_details` | Action | `add_action` | Public | canonical | `includes/files/class-ppcart-files.php` | ppcart_receipt_after_order_details |
| `ppcart_receipt_after_order_details` | Action | `do_action` | Public | canonical | `public/templates/my-account/slm-plan-detail.php` | ppcart_receipt_after_order_details |
| `ppcart_receipt_after_order_details` | Action | `do_action` | Public | canonical | `public/templates/shortcodes/receipt.php` | ppcart_receipt_after_order_details |
| `ppcart_register_file_handler` | Filter | `apply_filters` | Public | canonical | `includes/bootstrap/templates/admin-hook-registrar-register.php` | ppcart_register_file_handler |
| `ppcart_register_importers` | Action | `do_action` | Public | canonical | `admin/traits/trait-ppcart-admin-product-duplicate.php` | ppcart_register_importers |
| `ppcart_register_pro_hooks` | Action | `do_action` | Public | canonical | `includes/class-ppcart.php` | ppcart_register_pro_hooks |
| `ppcart_register_public_ajax_handlers` | Action | `do_action` | Public | canonical | `includes/bootstrap/class-ppcart-public-hook-registrar.php` | ppcart_register_public_ajax_handlers |
| `ppcart_register_sections` | Action | `do_action` | Public | canonical | `admin/settings/traits/templates/settings-sections-register.php` | ppcart_register_sections |
| `ppcart_renew_integrations_lists` | Action | `add_action` | Public | canonical | `includes/integrations/templates/ppcart-kit-init.php` | ppcart_renew_integrations_lists |
| `ppcart_renew_integrations_lists` | Action | `do_action` | Public | canonical | `admin/traits/trait-ppcart-admin-integrations.php` | ppcart_renew_integrations_lists |
| `ppcart_renewal_failed` | Action | `add_action` | Public | canonical | `public/class-ppcart-public.php` | ppcart_renewal_failed |
| `ppcart_renewal_payment` | Action | `add_action` | Public | canonical | `public/class-ppcart-public.php` | ppcart_renewal_payment |
| `ppcart_renewal_uncollectible` | Action | `add_action` | Public | canonical | `public/class-ppcart-public.php` | ppcart_renewal_uncollectible |
| `ppcart_repeater_saved_rows` | Filter | `apply_filters` | Public | canonical | `admin/partials/ppcart-admin-field-repeater.php` | ppcart_repeater_saved_rows |
| `ppcart_run_after_integrations` | Action | `add_action` | Public | canonical | `public/class-ppcart-public.php` | ppcart_run_after_integrations |
| `ppcart_run_after_integrations` | Action | `do_action` | Public | canonical | `includes/functions/integrations-and-stock.php` | ppcart_run_after_integrations |
| `ppcart_run_price_formatting` | Action | `add_action` | Public | canonical | `includes/functions/admin-ajax-and-notices.php` | ppcart_run_price_formatting |
| `ppcart_script_vars` | Filter | `apply_filters` | Public | canonical | `public/controllers/templates/enqueue-tracking-scripts.php` | ppcart_script_vars |
| `ppcart_send_new_user_email` | Filter | `add_filter` | Public | canonical | `public/controllers/class-ppcart-public-account-controller.php` | ppcart_send_new_user_email |
| `ppcart_send_new_user_email` | Filter | `apply_filters` | Public | canonical | `includes/functions/users-and-notifications.php` | ppcart_send_new_user_email |
| `ppcart_sensitive_option_names` | Filter | `apply_filters` | Public | canonical | `includes/secrets/traits/trait-ppcart-secrets-config.php` | ppcart_sensitive_option_names |
| `ppcart_setting_tab_pages` | Filter | `apply_filters` | Public | canonical | `admin/partials/ppcart-admin-page-settings.php` | ppcart_setting_tab_pages |
| `ppcart_setting_tabs` | Filter | `apply_filters` | Public | canonical | `admin/partials/ppcart-admin-page-settings.php` | ppcart_setting_tabs |
| `ppcart_setting_tabs` | Filter | `apply_filters` | Public | canonical | `admin/settings/traits/templates/settings-sections-register.php` | ppcart_setting_tabs |
| `ppcart_setting_tabs` | Filter | `apply_filters` | Public | canonical | `admin/settings/traits/trait-ppcart-admin-settings-core-options.php` | ppcart_setting_tabs |
| `ppcart_settings_admin_notices` | Action | `add_action` | Public | canonical | `admin/class-ppcart-admin.php` | ppcart_settings_admin_notices |
| `ppcart_settings_admin_notices` | Action | `add_action` | Public | canonical | `admin/controllers/class-ppcart-admin-page-notices.php` | ppcart_settings_admin_notices |
| `ppcart_settings_admin_notices` | Action | `do_action` | Public | canonical | `admin/partials/ppcart-admin-page-settings.php` | ppcart_settings_admin_notices |
| `ppcart_setup_order_from_meta` | Filter | `apply_filters` | Public | canonical | `includes/functions/product-and-order-setup.php` | ppcart_setup_order_from_meta |
| `ppcart_setup_product_display_mode` | Filter | `apply_filters` | Public | canonical | `includes/functions/product-and-order-setup.php` | ppcart_setup_product_display_mode |
| `ppcart_setup_product_from_meta` | Filter | `apply_filters` | Public | canonical | `includes/functions/product-and-order-setup.php` | ppcart_setup_product_from_meta |
| `ppcart_setup_product_post_type` | Filter | `apply_filters` | Public | canonical | `includes/functions/product-and-order-setup.php` | ppcart_setup_product_post_type |
| `ppcart_setup_product_upsell_path` | Filter | `apply_filters` | Public | canonical | `includes/functions/product-and-order-setup.php` | ppcart_setup_product_upsell_path |
| `ppcart_should_send_product_notification` | Filter | `apply_filters` | Public | canonical | `includes/functions/users-and-notifications.php` | ppcart_should_send_product_notification |
| `ppcart_show_optin_checkbox_services` | Filter | `add_filter` | Public | canonical | `includes/integrations/templates/ppcart-kit-init.php` | ppcart_show_optin_checkbox_services |
| `ppcart_show_optin_checkbox_services` | Filter | `apply_filters` | Public | canonical | `includes/functions/integrations-and-stock.php` | ppcart_show_optin_checkbox_services |
| `ppcart_show_orderbump` | Filter | `apply_filters` | Public | canonical | `public/templates/checkout-shortcode.php` | ppcart_show_orderbump |
| `ppcart_show_orderbump` | Filter | `apply_filters` | Public | canonical | `public/templates/checkout1.php` | ppcart_show_orderbump |
| `ppcart_show_reviews` | Filter | `apply_filters` | Public | canonical | `includes/class-ppcart-reviews.php` | ppcart_show_reviews |
| `ppcart_show_stripe_payment_method` | Action | `add_action` | Public | canonical | `includes/bootstrap/class-ppcart-public-hook-registrar.php` | ppcart_show_stripe_payment_method |
| `ppcart_show_upsell` | Filter | `apply_filters` | Public | canonical | `public/controllers/checkout/traits/templates/checkout-payment-ppcart-process-payment.php` | ppcart_show_upsell |
| `ppcart_show_version_notices` | Filter | `apply_filters` | Public | canonical | `includes/class-ppcart-version-notices.php` | ppcart_show_version_notices |
| `ppcart_show_{$subscription_order}{->}{pay_method}_payment_method` | Action | `do_action` | Public | canonical | `public/templates/my-account/subscription-detail.php` | ppcart_show_{$subscription_order}{->}{pay_method}_payment_method |
| `ppcart_skip_default_order_summary` | Filter | `apply_filters` | Public | canonical | `public/templates/functions/plan-coupon-fields.php` | ppcart_skip_default_order_summary |
| `ppcart_states` | Filter | `apply_filters` | Public | canonical | `includes/functions/payment-and-locale-lists.php` | ppcart_states |
| `ppcart_step_1_button_icon` | Action | `do_action` | Public | canonical | `public/templates/functions/summary-and-scripts.php` | ppcart_step_1_button_icon |
| `ppcart_step_1_button_subtext` | Action | `do_action` | Public | canonical | `public/templates/functions/summary-and-scripts.php` | ppcart_step_1_button_subtext |
| `ppcart_stripe_checkout_session_args` | Filter | `apply_filters` | Public | canonical | `public/controllers/templates/hosted-checkout-controller-create-checkout-session.php` | ppcart_stripe_checkout_session_args |
| `ppcart_stripe_checkout_session_completed` | Action | `do_action` | Public | canonical | `includes/stripe-sync/traits/templates/stripe-sync-hosted-checkout-sync-checkout-session-completed.php` | ppcart_stripe_checkout_session_completed |
| `ppcart_stripe_connect_credentials_http_args` | Filter | `apply_filters` | Public | canonical | `admin/settings/traits/templates/stripe-connect-maybe-handle-proxy-return.php` | ppcart_stripe_connect_credentials_http_args |
| `ppcart_stripe_invoice_response` | Action | `do_action` | Public | canonical | `includes/stripe-sync/traits/templates/stripe-sync-invoices-sync-invoice-resource.php` | ppcart_stripe_invoice_response |
| `ppcart_stripe_platform_publishable_key` | Filter | `apply_filters` | Public | canonical | `includes/functions/prices-and-marketing.php` | ppcart_stripe_platform_publishable_key |
| `ppcart_stripe_platform_secret_key` | Filter | `apply_filters` | Public | canonical | `includes/functions/prices-and-marketing.php` | ppcart_stripe_platform_secret_key |
| `ppcart_stripe_subscription_invoice_args` | Filter | `apply_filters` | Public | canonical | `public/controllers/traits/templates/subscription-create-create-stripe-subscription.php` | ppcart_stripe_subscription_invoice_args |
| `ppcart_stripe_subscriptions_documentation_url` | Filter | `apply_filters` | Public | canonical | `admin/settings/traits/options/payment-fields.php` | ppcart_stripe_subscriptions_documentation_url |
| `ppcart_sub_details` | Action | `do_action` | Public | canonical | `admin/controllers/subscription/traits/templates/admin-subscription-info-callback.php` | ppcart_sub_details |
| `ppcart_sub_item_id` | Filter | `apply_filters` | Public | canonical | `public/templates/my-account/card-details.php` | ppcart_sub_item_id |
| `ppcart_subscription_active` | Action | `add_action` | Public | canonical | `public/class-ppcart-public.php` | ppcart_subscription_active |
| `ppcart_subscription_apply_coupon` | Action | `do_action` | Public | canonical | `models/traits/templates/subscription-storage-from-order.php` | ppcart_subscription_apply_coupon |
| `ppcart_subscription_cancel_credit_note_args` | Filter | `apply_filters` | Public | canonical | `includes/functions/payment-actions-and-refunds.php` | ppcart_subscription_cancel_credit_note_args |
| `ppcart_subscription_cancel_refund_amount` | Filter | `apply_filters` | Public | canonical | `includes/functions/payment-actions-and-refunds.php` | ppcart_subscription_cancel_refund_amount |
| `ppcart_subscription_canceled` | Action | `add_action` | Public | canonical | `public/class-ppcart-public.php` | ppcart_subscription_canceled |
| `ppcart_subscription_completed` | Action | `add_action` | Public | canonical | `public/class-ppcart-public.php` | ppcart_subscription_completed |
| `ppcart_subscription_created` | Action | `do_action` | Public | canonical | `models/traits/trait-ppcart-subscription-storage.php` | ppcart_subscription_created |
| `ppcart_subscription_detail_modals` | Action | `do_action` | Public | canonical | `public/templates/my-account/subscription-detail.php` | ppcart_subscription_detail_modals |
| `ppcart_subscription_details_link` | Filter | `apply_filters` | Public | canonical | `admin/controllers/subscription/traits/templates/admin-subscription-info-callback.php` | ppcart_subscription_details_link |
| `ppcart_subscription_past_due` | Action | `add_action` | Public | canonical | `public/class-ppcart-public.php` | ppcart_subscription_past_due |
| `ppcart_subscription_pause_restart` | Filter | `add_filter` | Public | canonical | `public/class-ppcart-paypal.php` | ppcart_subscription_pause_restart |
| `ppcart_subscription_pause_restart` | Filter | `apply_filters` | Public | canonical | `includes/functions/payment-actions-and-refunds.php` | ppcart_subscription_pause_restart |
| `ppcart_subscription_paused` | Action | `add_action` | Public | canonical | `public/class-ppcart-public.php` | ppcart_subscription_paused |
| `ppcart_subscription_related_orders` | Action | `do_action` | Public | canonical | `admin/controllers/subscription/traits/templates/admin-subscription-form-callback.php` | ppcart_subscription_related_orders |
| `ppcart_subscription_reminder_event` | Action | `add_action` | Public | canonical | `includes/helpers/ppcart-scheduling.php` | ppcart_subscription_reminder_event |
| `ppcart_subscription_store_address_fields` | Action | `do_action` | Public | canonical | `includes/stripe/traits/trait-ppcart-stripe-subscription-save.php` | ppcart_subscription_store_address_fields |
| `ppcart_subscription_store_pro_metadata` | Action | `do_action` | Public | canonical | `includes/stripe/traits/trait-ppcart-stripe-subscription-save.php` | ppcart_subscription_store_pro_metadata |
| `ppcart_subscription_transaction_id` | Filter | `apply_filters` | Public | canonical | `includes/functions/orders-products-and-formatting.php` | ppcart_subscription_transaction_id |
| `ppcart_subscription_updated` | Action | `do_action` | Public | canonical | `models/traits/trait-ppcart-subscription-storage.php` | ppcart_subscription_updated |
| `ppcart_supports_feature` | Filter | `apply_filters` | Public | canonical | `includes/class-ppcart-feature-support.php` | ppcart_supports_feature |
| `ppcart_tab_content_tab-files` | Action | `add_action` | Public | canonical | `includes/files/class-ppcart-files.php` | ppcart_tab_content_tab-files |
| `ppcart_tab_content_tab-files` | Action | `do_action` | Public | canonical | `includes/integrations/gutenberg/lib/account-renderer/trait-ppcart-account-renderer-tab-content.php` | ppcart_tab_content_tab-files |
| `ppcart_tab_content_{$account_tab[...]}]` | Action | `do_action` | Public | canonical | `public/templates/my-account/my-account.php` | ppcart_tab_content_{$account_tab[...]}] |
| `ppcart_tab_content_{$tab_id}` | Action | `do_action` | Public | canonical | `includes/integrations/gutenberg/lib/account-renderer/trait-ppcart-account-renderer-tab-content.php` | ppcart_tab_content_{$tab_id} |
| `ppcart_tax_rates` | Filter | `apply_filters` | Public | canonical | `includes/functions/product-and-order-setup.php` | ppcart_tax_rates |
| `ppcart_taxonomies` | Filter | `apply_filters` | Public | canonical | `includes/templates/post-types-create-custom-post-type.php` | ppcart_taxonomies |
| `ppcart_taxonomy_options` | Filter | `apply_filters` | Public | canonical | `includes/templates/post-types-register-single-taxonomy.php` | ppcart_taxonomy_options |
| `ppcart_template_after_{$slug}` | Action | `do_action` | Public | canonical | `includes/helpers/ppcart-general-functions.php` | ppcart_template_after_{$slug} |
| `ppcart_template_before_{$slug}` | Action | `do_action` | Public | canonical | `includes/helpers/ppcart-general-functions.php` | ppcart_template_before_{$slug} |
| `ppcart_theme_template_path` | Filter | `apply_filters` | Public | canonical | `includes/helpers/ppcart-general-functions.php` | ppcart_theme_template_path |
| `ppcart_trigger_order_integrations` | Filter | `apply_filters` | Public | canonical | `models/order/traits/templates/order-persistence-store.php` | ppcart_trigger_order_integrations |
| `ppcart_trigger_subscription_integrations` | Filter | `apply_filters` | Public | canonical | `models/traits/templates/subscription-storage-store.php` | ppcart_trigger_subscription_integrations |
| `ppcart_update_stripe_invoice_during_checkout` | Filter | `apply_filters` | Public | canonical | `public/controllers/traits/templates/subscription-create-create-subscription.php` | ppcart_update_stripe_invoice_during_checkout |
| `ppcart_upgrade` | Action | `add_action` | Public | canonical | `includes/files/class-ppcart-files.php` | ppcart_upgrade |
| `ppcart_upgrade` | Action | `add_action` | Public | canonical | `includes/order-items/class-ppcart-order-items.php` | ppcart_upgrade |
| `ppcart_upgrade` | Action | `do_action` | Public | canonical | `includes/class-ppcart-upgrade.php` | ppcart_upgrade |
| `ppcart_use_block_product_template` | Filter | `apply_filters` | Public | canonical | `includes/integrations/gutenberg/templates/class-ppcart-product-template.php` | ppcart_use_block_product_template |
| `ppcart_use_default_authentication_logic` | Filter | `apply_filters` | Public | canonical | `public/controllers/class-ppcart-public-account-controller.php` | ppcart_use_default_authentication_logic |
| `ppcart_valid_sub_statuses_for_cancel` | Filter | `apply_filters` | Public | canonical | `public/templates/my-account/subscription-detail.php` | ppcart_valid_sub_statuses_for_cancel |
| `ppcart_valid_sub_statuses_for_pause_restart` | Filter | `apply_filters` | Public | canonical | `public/templates/my-account/subscription-detail.php` | ppcart_valid_sub_statuses_for_pause_restart |
| `ppcart_valid_sub_statuses_for_update` | Filter | `apply_filters` | Public | canonical | `public/templates/my-account/subscription-detail.php` | ppcart_valid_sub_statuses_for_update |
| `ppcart_validate_custom_fields` | Filter | `apply_filters` | Public | canonical | `public/controllers/checkout/traits/templates/checkout-validation-validate-order-form.php` | ppcart_validate_custom_fields |
| `ppcart_vat_title` | Filter | `apply_filters` | Public | canonical | `public/templates/email/order-table.php` | ppcart_vat_title |
| `ppcart_vat_title` | Filter | `apply_filters` | Public | canonical | `public/templates/pdf-invoice/invoice.php` | ppcart_vat_title |
| `ppcart_webhook_order_data` | Filter | `apply_filters` | Public | canonical | `includes/functions/integrations-and-stock.php` | ppcart_webhook_order_data |
| `ppcart_webhook_url_type` | Filter | `apply_filters` | Public | canonical | `includes/functions/admin-ajax-and-notices.php` | ppcart_webhook_url_type |
| `ppcart_zero_decimal_currency` | Filter | `apply_filters` | Public | canonical | `includes/functions/currency-data/zero-decimal-currencies.php` | ppcart_zero_decimal_currency |
| `ppcart_{$ppcart_services}_integrations` | Action | `do_action` | Public | canonical | `includes/functions/integrations-and-stock.php` | ppcart_{$ppcart_services}_integrations |
| `ppcart_{$this}{->}{service_name}_integrations` | Action | `add_action` | Public | canonical | `includes/integrations/CancelSubscription.php` | ppcart_{$this}{->}{service_name}_integrations |
| `ppcart_{$this}{->}{service_name}_integrations` | Action | `add_action` | Public | canonical | `includes/integrations/templates/ppcart-kit-init.php` | ppcart_{$this}{->}{service_name}_integrations |
| `ppcart_{$trigger}_integrations` | Action | `do_action` | Public | canonical | `includes/functions/integrations-and-stock.php` | ppcart_{$trigger}_integrations |
| `pre_get_posts` | Action | `add_action` | External | wordpress_core | `admin/class-ppcart-admin-filters.php` |  |
| `pre_get_posts` | Action | `add_action` | External | wordpress_core | `admin/controllers/class-ppcart-admin-order-list-controller.php` |  |
| `pre_get_posts` | Action | `add_action` | External | wordpress_core | `includes/bootstrap/templates/admin-hook-registrar-register.php` |  |
| `pre_get_posts` | Action | `add_action` | External | wordpress_core | `includes/class-ppcart-post-status-sync.php` |  |
| `pre_update_option` | Filter | `add_filter` | Internal | unclassified | `includes/secrets/traits/trait-ppcart-secrets-config.php` | pre_update_option |
| `pre_update_option__ppcart_tax_rates` | Filter | `add_filter` | Internal | unclassified | `includes/bootstrap/templates/admin-hook-registrar-register.php` | pre_update_option__ppcart_tax_rates |
| `publishpress-cart_wp_reviews_allow_display_notice` | Filter | `add_filter` | Internal | unclassified | `includes/class-ppcart-reviews.php` | publishpress-cart_wp_reviews_allow_display_notice |
| `query_vars` | Filter | `add_filter` | Internal | unclassified | `includes/bootstrap/class-ppcart-public-hook-registrar.php` | query_vars |
| `render_block_core/cover` | Filter | `add_filter` | Internal | unclassified | `includes/integrations/gutenberg/templates/class-ppcart-product-template.php` | render_block_core/cover |
| `rest_api_init` | Action | `add_action` | Internal | unclassified | `includes/integrations/gutenberg/lib/class-ppcart-gutenberg-bootstrap.php` | rest_api_init |
| `restrict_manage_posts` | Action | `add_action` | External | wordpress_core | `admin/class-ppcart-admin-filters.php` |  |
| `retrieve_password_message` | Filter | `add_filter` | Internal | unclassified | `public/controllers/class-ppcart-public-account-controller.php` | retrieve_password_message |
| `save_post` | Action | `add_action` | External | wordpress_core | `admin/controllers/class-ppcart-admin-order-controller.php` |  |
| `save_post_{$post}{->}{post_type}` | Action | `add_action` | Internal | dynamic | `admin/templates/order-admin-save-post-ppcart-order.php` | save_post_{$post}{->}{post_type} |
| `save_post_{$post}{->}{post_type}` | Action | `add_action` | Internal | dynamic | `includes/files/traits/templates/files-admin-update-order-downloads.php` | save_post_{$post}{->}{post_type} |
| `save_post_{$ppcart_order_type}` | Action | `add_action` | Internal | dynamic | `includes/bootstrap/templates/admin-hook-registrar-register.php` | save_post_{$ppcart_order_type} |
| `save_post_{$ppcart_order_type}` | Action | `add_action` | Internal | dynamic | `includes/files/class-ppcart-files.php` | save_post_{$ppcart_order_type} |
| `save_post_{$ppcart_product_type}` | Action | `add_action` | Internal | dynamic | `includes/bootstrap/templates/admin-hook-registrar-register.php` | save_post_{$ppcart_product_type} |
| `save_post_{$ppcart_subscription_type}` | Action | `add_action` | Internal | dynamic | `includes/bootstrap/templates/admin-hook-registrar-register.php` | save_post_{$ppcart_subscription_type} |
| `show_user_profile` | Action | `add_action` | Internal | unclassified | `includes/bootstrap/templates/admin-hook-registrar-register.php` | show_user_profile |
| `shutdown` | Action | `add_action` | Internal | unclassified | `includes/bootstrap/templates/admin-hook-registrar-register.php` | shutdown |
| `single_post_title` | Filter | `add_filter` | Internal | unclassified | `includes/bootstrap/class-ppcart-public-hook-registrar.php` | single_post_title |
| `single_template` | Filter | `add_filter` | Internal | unclassified | `includes/bootstrap/class-ppcart-public-hook-registrar.php` | single_template |
| `template_redirect` | Action | `add_action` | External | wordpress_core | `includes/bootstrap/class-ppcart-public-hook-registrar.php` |  |
| `template_redirect` | Action | `add_action` | External | wordpress_core | `public/class-ppcart-paypal.php` |  |
| `the_content` | Filter | `apply_filters` | External | wordpress_core | `public/templates/functions/plan-coupon-fields.php` |  |
| `the_title` | Filter | `add_filter` | Internal | unclassified | `admin/metaboxes/traits/templates/product-metabox-plan-options-product-options.php` | the_title |
| `the_title` | Filter | `add_filter` | Internal | unclassified | `includes/bootstrap/class-ppcart-public-hook-registrar.php` | the_title |
| `the_title` | Filter | `add_filter` | Internal | unclassified | `includes/functions/orders-products-and-formatting.php` | the_title |
| `the_title` | Filter | `add_filter` | Internal | unclassified | `includes/integrations/templates/cancel-subscription-products.php` | the_title |
| `update_option__ppcart_activecampaign_secret_key` | Action | `add_action` | Internal | unclassified | `includes/bootstrap/templates/admin-hook-registrar-register.php` | update_option__ppcart_activecampaign_secret_key |
| `update_option__ppcart_converkit_api` | Action | `add_action` | Internal | unclassified | `includes/integrations/templates/ppcart-kit-init.php` | update_option__ppcart_converkit_api |
| `update_option__ppcart_email_reminder_enable` | Action | `add_action` | Internal | unclassified | `includes/helpers/ppcart-scheduling.php` | update_option__ppcart_email_reminder_enable |
| `update_option__ppcart_email_trial_ending_enable` | Action | `add_action` | Internal | unclassified | `includes/helpers/ppcart-scheduling.php` | update_option__ppcart_email_trial_ending_enable |
| `update_option__ppcart_mailchimp_api` | Action | `add_action` | Internal | unclassified | `includes/bootstrap/templates/admin-hook-registrar-register.php` | update_option__ppcart_mailchimp_api |
| `update_option__ppcart_sendfox_api_key` | Action | `add_action` | Internal | unclassified | `includes/bootstrap/templates/admin-hook-registrar-register.php` | update_option__ppcart_sendfox_api_key |
| `update_option__ppcart_stripe_customer_portal_enable` | Action | `add_action` | Internal | unclassified | `includes/bootstrap/templates/admin-hook-registrar-register.php` | update_option__ppcart_stripe_customer_portal_enable |
| `update_option__ppcart_stripe_express_payment_enable` | Action | `add_action` | Internal | unclassified | `includes/bootstrap/templates/admin-hook-registrar-register.php` | update_option__ppcart_stripe_express_payment_enable |
| `update_option_ppcart_download_slug` | Action | `add_action` | Internal | unclassified | `includes/files/class-ppcart-files.php` | update_option_ppcart_download_slug |
| `upgrader_process_complete` | Action | `add_action` | Internal | unclassified | `publishpress-cart.php` | upgrader_process_complete |
| `upload_dir` | Filter | `add_filter` | Internal | unclassified | `includes/files/class-ppcart-files.php` | upload_dir |
| `user_admin_notices` | Action | `add_action` | Internal | unclassified | `admin/controllers/class-ppcart-admin-page-notices.php` | user_admin_notices |
| `views_edit-{$ppcart_order_type}` | Filter | `add_filter` | Internal | dynamic | `includes/bootstrap/templates/admin-hook-registrar-register.php` | views_edit-{$ppcart_order_type} |
| `wp` | Action | `add_action` | External | wordpress_core | `includes/files/class-ppcart-files.php` |  |
| `wp` | Filter | `add_filter` | External | wordpress_core | `includes/bootstrap/class-ppcart-public-hook-registrar.php` |  |
| `wp_ajax_nopriv_ppcart_create_checkout_session` | Action | `add_action` | External | wordpress_core | `includes/bootstrap/class-ppcart-public-hook-registrar.php` |  |
| `wp_ajax_nopriv_ppcart_create_payment_intent` | Action | `add_action` | External | wordpress_core | `includes/bootstrap/class-ppcart-public-hook-registrar.php` |  |
| `wp_ajax_nopriv_ppcart_create_setup_intent` | Action | `add_action` | External | wordpress_core | `includes/bootstrap/class-ppcart-public-hook-registrar.php` |  |
| `wp_ajax_nopriv_ppcart_create_subscription` | Action | `add_action` | External | wordpress_core | `includes/bootstrap/class-ppcart-public-hook-registrar.php` |  |
| `wp_ajax_nopriv_ppcart_paypal_request` | Action | `add_action` | External | wordpress_core | `public/class-ppcart-paypal.php` |  |
| `wp_ajax_nopriv_ppcart_save_order_to_db` | Action | `add_action` | External | wordpress_core | `includes/bootstrap/class-ppcart-public-hook-registrar.php` |  |
| `wp_ajax_nopriv_ppcart_update_cart_amount` | Action | `add_action` | External | wordpress_core | `public/controllers/class-ppcart-public-order-controller.php` |  |
| `wp_ajax_nopriv_ppcart_update_payment_intent_amt` | Action | `add_action` | External | wordpress_core | `includes/bootstrap/class-ppcart-public-hook-registrar.php` |  |
| `wp_ajax_nopriv_ppcart_update_stripe_order_status` | Action | `add_action` | External | wordpress_core | `includes/bootstrap/class-ppcart-public-hook-registrar.php` |  |
| `wp_ajax_ppcart_ajax_action` | Action | `add_action` | External | wordpress_core | `includes/bootstrap/templates/admin-hook-registrar-register.php` |  |
| `wp_ajax_ppcart_create_checkout_session` | Action | `add_action` | External | wordpress_core | `includes/bootstrap/class-ppcart-public-hook-registrar.php` |  |
| `wp_ajax_ppcart_create_payment_intent` | Action | `add_action` | External | wordpress_core | `includes/bootstrap/class-ppcart-public-hook-registrar.php` |  |
| `wp_ajax_ppcart_create_setup_intent` | Action | `add_action` | External | wordpress_core | `includes/bootstrap/class-ppcart-public-hook-registrar.php` |  |
| `wp_ajax_ppcart_create_subscription` | Action | `add_action` | External | wordpress_core | `includes/bootstrap/class-ppcart-public-hook-registrar.php` |  |
| `wp_ajax_ppcart_db_schema_status` | Action | `add_action` | External | wordpress_core | `includes/db-schema/class-ppcart-db-schema-admin.php` |  |
| `wp_ajax_ppcart_dismissed_notice_handler` | Action | `add_action` | External | wordpress_core | `includes/functions/admin-ajax-and-notices.php` |  |
| `wp_ajax_ppcart_fix_db_schema` | Action | `add_action` | External | wordpress_core | `includes/db-schema/class-ppcart-db-schema-admin.php` |  |
| `wp_ajax_ppcart_fresh_product` | Action | `add_action` | External | wordpress_core | `includes/bootstrap/templates/admin-hook-registrar-register.php` |  |
| `wp_ajax_ppcart_get_payment_options` | Action | `add_action` | External | wordpress_core | `includes/bootstrap/templates/admin-hook-registrar-register.php` |  |
| `wp_ajax_ppcart_json_search_user` | Action | `add_action` | External | wordpress_core | `includes/functions.php` |  |
| `wp_ajax_ppcart_mailchimp_groups_tags` | Action | `add_action` | External | wordpress_core | `includes/bootstrap/templates/admin-hook-registrar-register.php` |  |
| `wp_ajax_ppcart_migrate_secrets` | Action | `add_action` | External | wordpress_core | `includes/secrets/traits/trait-ppcart-secrets-config.php` |  |
| `wp_ajax_ppcart_order_refund` | Action | `add_action` | External | wordpress_core | `includes/bootstrap/templates/admin-hook-registrar-register.php` |  |
| `wp_ajax_ppcart_pause_restart_subscription` | Action | `add_action` | External | wordpress_core | `includes/functions/payment-actions-and-refunds.php` |  |
| `wp_ajax_ppcart_paypal_request` | Action | `add_action` | External | wordpress_core | `public/class-ppcart-paypal.php` |  |
| `wp_ajax_ppcart_preview_email_template` | Action | `add_action` | External | wordpress_core | `admin/class-ppcart-admin-settings.php` |  |
| `wp_ajax_ppcart_preview_product_notification_email` | Action | `add_action` | External | wordpress_core | `admin/class-ppcart-admin-settings.php` |  |
| `wp_ajax_ppcart_product_plans` | Action | `add_action` | External | wordpress_core | `includes/bootstrap/templates/admin-hook-registrar-register.php` |  |
| `wp_ajax_ppcart_renew_integrations_lists` | Action | `add_action` | External | wordpress_core | `includes/bootstrap/templates/admin-hook-registrar-register.php` |  |
| `wp_ajax_ppcart_resend_purchase_confirmation_email` | Action | `add_action` | External | wordpress_core | `includes/functions/admin-ajax-and-notices.php` |  |
| `wp_ajax_ppcart_reset_email_template` | Action | `add_action` | External | wordpress_core | `admin/class-ppcart-admin-settings.php` |  |
| `wp_ajax_ppcart_save_order_to_db` | Action | `add_action` | External | wordpress_core | `includes/bootstrap/class-ppcart-public-hook-registrar.php` |  |
| `wp_ajax_ppcart_search_report_customers` | Action | `add_action` | External | wordpress_core | `includes/functions.php` |  |
| `wp_ajax_ppcart_search_report_products` | Action | `add_action` | External | wordpress_core | `includes/functions.php` |  |
| `wp_ajax_ppcart_send_email_test` | Action | `add_action` | External | wordpress_core | `includes/bootstrap/templates/admin-hook-registrar-register.php` |  |
| `wp_ajax_ppcart_send_product_notification_test` | Action | `add_action` | External | wordpress_core | `admin/class-ppcart-admin-settings.php` |  |
| `wp_ajax_ppcart_set_encrypt_secrets` | Action | `add_action` | External | wordpress_core | `includes/secrets/traits/trait-ppcart-secrets-config.php` |  |
| `wp_ajax_ppcart_sync_order` | Action | `add_action` | External | wordpress_core | `admin/controllers/class-ppcart-admin-subscription-controller.php` |  |
| `wp_ajax_ppcart_sync_subscription` | Action | `add_action` | External | wordpress_core | `admin/controllers/class-ppcart-admin-subscription-controller.php` |  |
| `wp_ajax_ppcart_unsubscribe_customer` | Action | `add_action` | External | wordpress_core | `includes/functions.php` |  |
| `wp_ajax_ppcart_update_cart_amount` | Action | `add_action` | External | wordpress_core | `public/controllers/class-ppcart-public-order-controller.php` |  |
| `wp_ajax_ppcart_update_payment_intent_amt` | Action | `add_action` | External | wordpress_core | `includes/bootstrap/class-ppcart-public-hook-registrar.php` |  |
| `wp_ajax_ppcart_update_stripe_order_status` | Action | `add_action` | External | wordpress_core | `includes/bootstrap/class-ppcart-public-hook-registrar.php` |  |
| `wp_ajax_ppcart_update_stripe_payment_method` | Action | `add_action` | External | wordpress_core | `includes/bootstrap/class-ppcart-public-hook-registrar.php` |  |
| `wp_ajax_ppcart_update_user_profile` | Action | `add_action` | External | wordpress_core | `includes/functions/payment-actions-and-refunds.php` |  |
| `wp_dashboard_setup` | Action | `add_action` | Internal | unclassified | `admin/dashboard/class-ppcart-dashboard-widget.php` | wp_dashboard_setup |
| `wp_enqueue_scripts` | Action | `add_action` | External | wordpress_core | `includes/bootstrap/class-ppcart-public-hook-registrar.php` |  |
| `wp_enqueue_scripts` | Action | `add_action` | External | wordpress_core | `includes/functions.php` |  |
| `wp_footer` | Action | `add_action` | External | wordpress_core | `includes/integrations/templates/ppcart-googlerecaptcha-gen-recaptcha-html.php` |  |
| `wp_footer` | Action | `add_action` | External | wordpress_core | `public/controllers/templates/enqueue-tracking-scripts.php` |  |
| `wp_head` | Action | `add_action` | Internal | unclassified | `admin/controllers/class-ppcart-admin-test-mode-notice-controller.php` | wp_head |
| `wp_insert_post_data` | Action | `add_action` | Internal | unclassified | `includes/bootstrap/templates/admin-hook-registrar-register.php` | wp_insert_post_data |
| `wp_insert_post_data` | Filter | `add_filter` | Internal | unclassified | `includes/class-ppcart-post-status-sync.php` | wp_insert_post_data |
| `wp_logout` | Action | `add_action` | Internal | unclassified | `public/controllers/class-ppcart-public-account-controller.php` | wp_logout |
| `wp_mail` | Filter | `add_filter` | Internal | unclassified | `includes/email/ppcart-template-functions/options-and-hooks.php` | wp_mail |
| `wp_privacy_personal_data_erasers` | Action | `add_action` | Internal | unclassified | `includes/bootstrap/templates/admin-hook-registrar-register.php` | wp_privacy_personal_data_erasers |
| `wp_privacy_personal_data_exporters` | Action | `add_action` | Internal | unclassified | `includes/bootstrap/templates/admin-hook-registrar-register.php` | wp_privacy_personal_data_exporters |
| `wp_title` | Filter | `add_filter` | Internal | unclassified | `includes/bootstrap/class-ppcart-public-hook-registrar.php` | wp_title |
| `{$action}` | Action | `do_action` | Internal | dynamic | `includes/functions/integrations-and-stock.php` | {$action} |
| `{$hook[...]}]` | Action | `add_action` | Internal | dynamic | `includes/class-ppcart-loader.php` | {$hook[...]}] |
| `{$hook[...]}]` | Filter | `add_filter` | Internal | dynamic | `includes/class-ppcart-loader.php` | {$hook[...]}] |
| `{$hook}` | Action | `add_action` | Internal | dynamic | `includes/class-ppcart-loader.php` | {$hook} |
| `{$hook}` | Filter | `add_filter` | Internal | dynamic | `includes/class-ppcart-loader.php` | {$hook} |
| `{$this}{->}{plugin_name}-field-checkbox-options-defaults` | Filter | `apply_filters` | Internal | dynamic | `admin/settings/traits/templates/settings-input-fields-field-checkbox.php` | {$this}{->}{plugin_name}-field-checkbox-options-defaults |
| `{$this}{->}{plugin_name}-field-editor-options-defaults` | Filter | `apply_filters` | Internal | dynamic | `admin/settings/traits/templates/settings-input-fields-field-editor.php` | {$this}{->}{plugin_name}-field-editor-options-defaults |
| `{$this}{->}{plugin_name}-field-radios-options-defaults` | Filter | `apply_filters` | Internal | dynamic | `admin/settings/traits/templates/settings-input-fields-field-radios.php` | {$this}{->}{plugin_name}-field-radios-options-defaults |
| `{$this}{->}{plugin_name}-field-repeater-options-defaults` | Filter | `apply_filters` | Internal | dynamic | `admin/settings/traits/templates/settings-input-fields-field-repeater.php` | {$this}{->}{plugin_name}-field-repeater-options-defaults |
| `{$this}{->}{plugin_name}-field-repeater-{$setatts[...]}]` | Filter | `apply_filters` | Internal | dynamic | `admin/metaboxes/traits/templates/product-metabox-render-metabox-fields.php` | {$this}{->}{plugin_name}-field-repeater-{$setatts[...]}] |
| `{$this}{->}{plugin_name}-field-repeater-{$setatts[...]}]` | Filter | `apply_filters` | Internal | dynamic | `admin/order-metaboxes/traits/templates/order-metabox-edit-fields-metabox-fields.php` | {$this}{->}{plugin_name}-field-repeater-{$setatts[...]}] |
| `{$this}{->}{plugin_name}-field-select-options-defaults` | Filter | `apply_filters` | Internal | dynamic | `admin/settings/traits/templates/settings-input-fields-field-select.php` | {$this}{->}{plugin_name}-field-select-options-defaults |
| `{$this}{->}{plugin_name}-field-text-options-defaults` | Filter | `apply_filters` | Internal | dynamic | `admin/settings/traits/templates/settings-text-fields-field-color.php` | {$this}{->}{plugin_name}-field-text-options-defaults |
| `{$this}{->}{plugin_name}-field-text-options-defaults` | Filter | `apply_filters` | Internal | dynamic | `admin/settings/traits/templates/settings-text-fields-field-email.php` | {$this}{->}{plugin_name}-field-text-options-defaults |
| `{$this}{->}{plugin_name}-field-text-options-defaults` | Filter | `apply_filters` | Internal | dynamic | `admin/settings/traits/templates/settings-text-fields-field-html.php` | {$this}{->}{plugin_name}-field-text-options-defaults |
| `{$this}{->}{plugin_name}-field-text-options-defaults` | Filter | `apply_filters` | Internal | dynamic | `admin/settings/traits/templates/settings-text-fields-field-password.php` | {$this}{->}{plugin_name}-field-text-options-defaults |
| `{$this}{->}{plugin_name}-field-text-options-defaults` | Filter | `apply_filters` | Internal | dynamic | `admin/settings/traits/templates/settings-text-fields-field-text.php` | {$this}{->}{plugin_name}-field-text-options-defaults |
| `{$this}{->}{plugin_name}-field-textarea-options-defaults` | Filter | `apply_filters` | Internal | dynamic | `admin/settings/traits/templates/settings-text-fields-field-textarea.php` | {$this}{->}{plugin_name}-field-textarea-options-defaults |
| `{$this}{->}{plugin_name}-field-textarea-options-defaults` | Filter | `apply_filters` | Internal | dynamic | `admin/settings/traits/templates/settings-text-fields-field-upload.php` | {$this}{->}{plugin_name}-field-textarea-options-defaults |
| `{$this}{->}{plugin_name}-field-{$atts[...]}]` | Filter | `apply_filters` | Internal | dynamic | `admin/metaboxes/traits/templates/product-metabox-render-metabox-fields.php` | {$this}{->}{plugin_name}-field-{$atts[...]}] |
| `{$this}{->}{plugin_name}-field-{$atts[...]}]` | Filter | `apply_filters` | Internal | dynamic | `admin/order-metaboxes/traits/templates/order-metabox-edit-fields-metabox-fields.php` | {$this}{->}{plugin_name}-field-{$atts[...]}] |
| `{$this}{->}{plugin_name}-metabox-title-access` | Filter | `apply_filters` | Internal | dynamic | `admin/controllers/order/traits/trait-ppcart-admin-order-metabox.php` | {$this}{->}{plugin_name}-metabox-title-access |
| `{$this}{->}{plugin_name}-metabox-title-order-details` | Filter | `apply_filters` | Internal | dynamic | `admin/class-ppcart-order-metaboxes.php` | {$this}{->}{plugin_name}-metabox-title-order-details |
| `{$this}{->}{plugin_name}-metabox-title-order-notes` | Filter | `apply_filters` | Internal | dynamic | `admin/controllers/order/traits/trait-ppcart-admin-order-metabox.php` | {$this}{->}{plugin_name}-metabox-title-order-notes |
| `{$this}{->}{plugin_name}-metabox-title-product-settings` | Filter | `apply_filters` | Internal | dynamic | `admin/metaboxes/traits/trait-ppcart-product-metaboxes-render.php` | {$this}{->}{plugin_name}-metabox-title-product-settings |
| `{$this}{->}{plugin_name}-repeater-more-link-label` | Filter | `apply_filters` | Internal | dynamic | `admin/partials/ppcart-admin-field-repeater.php` | {$this}{->}{plugin_name}-repeater-more-link-label |
| `{$this}{->}{plugin_name}-repeater-remove-link-label` | Filter | `apply_filters` | Internal | dynamic | `admin/partials/ppcart-admin-field-repeater.php` | {$this}{->}{plugin_name}-repeater-remove-link-label |
| `{$this}{->}{plugin_name}-settings-menu-title` | Filter | `apply_filters` | Internal | dynamic | `admin/class-ppcart-admin-reports.php` | {$this}{->}{plugin_name}-settings-menu-title |
| `{$this}{->}{plugin_name}-settings-menu-title` | Filter | `apply_filters` | Internal | dynamic | `admin/class-ppcart-contacts-page.php` | {$this}{->}{plugin_name}-settings-menu-title |
| `{$this}{->}{plugin_name}-settings-menu-title` | Filter | `apply_filters` | Internal | dynamic | `admin/class-ppcart-extension-page.php` | {$this}{->}{plugin_name}-settings-menu-title |
| `{$this}{->}{plugin_name}-settings-menu-title` | Filter | `apply_filters` | Internal | dynamic | `admin/settings/traits/trait-ppcart-admin-settings-screen.php` | {$this}{->}{plugin_name}-settings-menu-title |
| `{$this}{->}{plugin_name}-settings-page-title` | Filter | `apply_filters` | Internal | dynamic | `admin/class-ppcart-admin-reports.php` | {$this}{->}{plugin_name}-settings-page-title |
| `{$this}{->}{plugin_name}-settings-page-title` | Filter | `apply_filters` | Internal | dynamic | `admin/class-ppcart-contacts-page.php` | {$this}{->}{plugin_name}-settings-page-title |
| `{$this}{->}{plugin_name}-settings-page-title` | Filter | `apply_filters` | Internal | dynamic | `admin/class-ppcart-extension-page.php` | {$this}{->}{plugin_name}-settings-page-title |
| `{$this}{->}{plugin_name}-settings-page-title` | Filter | `apply_filters` | Internal | dynamic | `admin/settings/traits/trait-ppcart-admin-settings-screen.php` | {$this}{->}{plugin_name}-settings-page-title |
| `{$this}{->}{plugin_name}label-{$k}` | Filter | `apply_filters` | Internal | dynamic | `admin/settings/traits/templates/settings-fields-register-fields.php` | {$this}{->}{plugin_name}label-{$k} |
| `{$this}{->}{plugin_name}section-title-company` | Filter | `apply_filters` | Internal | dynamic | `admin/settings/traits/templates/settings-sections-register.php` | {$this}{->}{plugin_name}section-title-company |
| `{$this}{->}{plugin_name}section-title-currency` | Filter | `apply_filters` | Internal | dynamic | `admin/settings/traits/templates/settings-sections-register.php` | {$this}{->}{plugin_name}section-title-currency |
| `{$this}{->}{plugin_name}section-title-debug` | Filter | `apply_filters` | Internal | dynamic | `admin/settings/traits/templates/settings-sections-register.php` | {$this}{->}{plugin_name}section-title-debug |
| `{$this}{->}{plugin_name}section-title-downloads` | Filter | `apply_filters` | Internal | dynamic | `admin/settings/traits/templates/settings-sections-register.php` | {$this}{->}{plugin_name}section-title-downloads |
| `{$this}{->}{plugin_name}section-title-maintenance-db-schema` | Filter | `apply_filters` | Internal | dynamic | `admin/settings/traits/trait-ppcart-admin-settings-sections.php` | {$this}{->}{plugin_name}section-title-maintenance-db-schema |
| `{$this}{->}{plugin_name}section-title-maintenance-secrets` | Filter | `apply_filters` | Internal | dynamic | `admin/settings/traits/trait-ppcart-admin-settings-sections.php` | {$this}{->}{plugin_name}section-title-maintenance-secrets |
| `{$this}{->}{plugin_name}section-title-pages` | Filter | `apply_filters` | Internal | dynamic | `admin/settings/traits/templates/settings-sections-register.php` | {$this}{->}{plugin_name}section-title-pages |
| `{$this}{->}{plugin_name}section-title-settings` | Filter | `apply_filters` | Internal | dynamic | `admin/settings/traits/templates/settings-sections-register.php` | {$this}{->}{plugin_name}section-title-settings |
| `{$this}{->}{plugin_name}section-title-{$email_key}` | Filter | `apply_filters` | Internal | dynamic | `admin/settings/traits/templates/settings-sections-emails.php` | {$this}{->}{plugin_name}section-title-{$email_key} |
| `{$this}{->}{plugin_name}section-title-{$intigration_key}` | Filter | `apply_filters` | Internal | dynamic | `admin/settings/traits/templates/settings-sections-integrations.php` | {$this}{->}{plugin_name}section-title-{$intigration_key} |
| `{$this}{->}{plugin_name}section-title-{$invoice_key}` | Filter | `apply_filters` | Internal | dynamic | `admin/settings/traits/trait-ppcart-admin-settings-sections.php` | {$this}{->}{plugin_name}section-title-{$invoice_key} |
| `{$this}{->}{plugin_name}section-title-{$payment_gateway_key}` | Filter | `apply_filters` | Internal | dynamic | `admin/settings/traits/trait-ppcart-admin-settings-sections.php` | {$this}{->}{plugin_name}section-title-{$payment_gateway_key} |
| `{$this}{->}{plugin_name}section-title-{$ppcart_section_key}` | Filter | `apply_filters` | Internal | dynamic | `admin/settings/traits/templates/settings-sections-custom-tabs.php` | {$this}{->}{plugin_name}section-title-{$ppcart_section_key} |
| `{$this}{->}{plugin_name}section-title-{$tax_key}` | Filter | `apply_filters` | Internal | dynamic | `admin/settings/traits/trait-ppcart-admin-settings-sections.php` | {$this}{->}{plugin_name}section-title-{$tax_key} |
| `{$top_notice_settings_filter}` | Filter | `add_filter` | Internal | dynamic | `includes/class-ppcart-version-notices.php` | {$top_notice_settings_filter} |
| `{self}{::}{CRON_HOOK}` | Action | `add_action` | Internal | dynamic | `includes/class-ppcart-stripe-sync.php` | {self}{::}{CRON_HOOK} |

## Overlapping And Legacy Hooks

| Canonical | Legacy | Type | Migration |
| --- | --- | --- | --- |
| `ppcart_after_order_paid` | `studiocart_checkout_complete` | Action | Use ppcart_after_order_paid for payment-complete workflows. studiocart_checkout_complete bridges in publishpress-cart-compat when Compatibility Mode is on. |

## Dynamic Patterns

| Pattern | Operation | Classification | Status | File | Recommendation |
| --- | --- | --- | --- | --- | --- |
| `_ppcart_{$ppcart_tab_key}_tab_section` | `apply_filters` | Public | canonical | `admin/settings/traits/templates/settings-sections-custom-tabs.php` | `_ppcart_{$ppcart_tab_key}_tab_section` |
| `bulk_actions-edit-{$ppcart_order_type}` | `add_filter` | Internal | dynamic | `includes/bootstrap/templates/admin-hook-registrar-register.php` | `bulk_actions-edit-{$ppcart_order_type}` |
| `bulk_actions-edit-{$ppcart_subscription_type}` | `add_filter` | Internal | dynamic | `includes/bootstrap/templates/admin-hook-registrar-register.php` | `bulk_actions-edit-{$ppcart_subscription_type}` |
| `default_option_{$option}` | `add_filter` | Internal | dynamic | `includes/email/ppcart-template-functions/options-and-hooks.php` | `default_option_{$option}` |
| `handle_bulk_actions-edit-{$ppcart_order_type}` | `add_filter` | Internal | dynamic | `includes/bootstrap/templates/admin-hook-registrar-register.php` | `handle_bulk_actions-edit-{$ppcart_order_type}` |
| `handle_bulk_actions-edit-{$ppcart_subscription_type}` | `add_filter` | Internal | dynamic | `includes/bootstrap/templates/admin-hook-registrar-register.php` | `handle_bulk_actions-edit-{$ppcart_subscription_type}` |
| `manage_edit-{$ppcart_order_type}_sortable_columns` | `add_filter` | External | wordpress_core | `includes/bootstrap/templates/admin-hook-registrar-register.php` | `` |
| `manage_edit-{$ppcart_subscription_type}_sortable_columns` | `add_filter` | External | wordpress_core | `includes/bootstrap/templates/admin-hook-registrar-register.php` | `` |
| `manage_{$ppcart_order_type}_posts_columns` | `add_filter` | External | wordpress_core | `includes/bootstrap/templates/admin-hook-registrar-register.php` | `` |
| `manage_{$ppcart_order_type}_posts_custom_column` | `add_action` | External | wordpress_core | `includes/bootstrap/templates/admin-hook-registrar-register.php` | `` |
| `manage_{$ppcart_product_type}_posts_columns` | `add_filter` | External | wordpress_core | `includes/bootstrap/templates/admin-hook-registrar-register.php` | `` |
| `manage_{$ppcart_product_type}_posts_custom_column` | `add_action` | External | wordpress_core | `includes/bootstrap/templates/admin-hook-registrar-register.php` | `` |
| `manage_{$ppcart_subscription_type}_posts_columns` | `add_filter` | External | wordpress_core | `includes/bootstrap/templates/admin-hook-registrar-register.php` | `` |
| `manage_{$ppcart_subscription_type}_posts_custom_column` | `add_action` | External | wordpress_core | `includes/bootstrap/templates/admin-hook-registrar-register.php` | `` |
| `option_{$option_name}` | `add_filter` | Internal | dynamic | `includes/secrets/traits/trait-ppcart-secrets-config.php` | `option_{$option_name}` |
| `option_{$option}` | `add_filter` | Internal | dynamic | `includes/email/ppcart-template-functions/options-and-hooks.php` | `option_{$option}` |
| `ppcart_backend_message_{$k}` | `apply_filters` | Public | canonical | `includes/functions/merge-tags-and-dates.php` | `ppcart_backend_message_{$k}` |
| `ppcart_default_{$field[...]}]_field_settings_attributes` | `apply_filters` | Public | canonical | `admin/partials/ppcart-admin-field-default-fields.php` | `ppcart_default_{$field[...]}]_field_settings_attributes` |
| `ppcart_frontend_message_{$k}` | `apply_filters` | Public | canonical | `includes/functions/merge-tags-and-dates.php` | `ppcart_frontend_message_{$k}` |
| `ppcart_login_after_{$template_name}` | `do_action` | Public | canonical | `public/controllers/class-ppcart-public-account-controller.php` | `ppcart_login_after_{$template_name}` |
| `ppcart_login_before_{$template_name}` | `do_action` | Public | canonical | `public/controllers/class-ppcart-public-account-controller.php` | `ppcart_login_before_{$template_name}` |
| `ppcart_order_refund_{$order}{->}{pay_method}` | `do_action` | Public | canonical | `includes/functions/payment-actions-and-refunds.php` | `ppcart_order_refund_{$order}{->}{pay_method}` |
| `ppcart_product_setting_tab_{$tab_id}_fields` | `apply_filters` | Public | canonical | `admin/metaboxes/traits/trait-ppcart-product-metaboxes-render.php` | `ppcart_product_setting_tab_{$tab_id}_fields` |
| `ppcart_product_{$tab_id}_fields` | `apply_filters` | Public | canonical | `admin/metaboxes/traits/trait-ppcart-product-metaboxes-render.php` | `ppcart_product_{$tab_id}_fields` |
| `ppcart_show_{$subscription_order}{->}{pay_method}_payment_method` | `do_action` | Public | canonical | `public/templates/my-account/subscription-detail.php` | `ppcart_show_{$subscription_order}{->}{pay_method}_payment_method` |
| `ppcart_tab_content_{$account_tab[...]}]` | `do_action` | Public | canonical | `public/templates/my-account/my-account.php` | `ppcart_tab_content_{$account_tab[...]}]` |
| `ppcart_tab_content_{$tab_id}` | `do_action` | Public | canonical | `includes/integrations/gutenberg/lib/account-renderer/trait-ppcart-account-renderer-tab-content.php` | `ppcart_tab_content_{$tab_id}` |
| `ppcart_template_after_{$slug}` | `do_action` | Public | canonical | `includes/helpers/ppcart-general-functions.php` | `ppcart_template_after_{$slug}` |
| `ppcart_template_before_{$slug}` | `do_action` | Public | canonical | `includes/helpers/ppcart-general-functions.php` | `ppcart_template_before_{$slug}` |
| `ppcart_{$ppcart_services}_integrations` | `do_action` | Public | canonical | `includes/functions/integrations-and-stock.php` | `ppcart_{$ppcart_services}_integrations` |
| `ppcart_{$this}{->}{service_name}_integrations` | `add_action` | Public | canonical | `includes/integrations/templates/ppcart-kit-init.php` | `ppcart_{$this}{->}{service_name}_integrations` |
| `ppcart_{$trigger}_integrations` | `do_action` | Public | canonical | `includes/functions/integrations-and-stock.php` | `ppcart_{$trigger}_integrations` |
| `save_post_{$post}{->}{post_type}` | `add_action` | Internal | dynamic | `includes/files/traits/templates/files-admin-update-order-downloads.php` | `save_post_{$post}{->}{post_type}` |
| `save_post_{$ppcart_order_type}` | `add_action` | Internal | dynamic | `includes/files/class-ppcart-files.php` | `save_post_{$ppcart_order_type}` |
| `save_post_{$ppcart_product_type}` | `add_action` | Internal | dynamic | `includes/bootstrap/templates/admin-hook-registrar-register.php` | `save_post_{$ppcart_product_type}` |
| `save_post_{$ppcart_subscription_type}` | `add_action` | Internal | dynamic | `includes/bootstrap/templates/admin-hook-registrar-register.php` | `save_post_{$ppcart_subscription_type}` |
| `views_edit-{$ppcart_order_type}` | `add_filter` | Internal | dynamic | `includes/bootstrap/templates/admin-hook-registrar-register.php` | `views_edit-{$ppcart_order_type}` |
| `{$action}` | `do_action` | Internal | dynamic | `includes/functions/integrations-and-stock.php` | `{$action}` |
| `{$hook[...]}]` | `add_action` | Internal | dynamic | `includes/class-ppcart-loader.php` | `{$hook[...]}]` |
| `{$hook}` | `add_filter` | Internal | dynamic | `includes/class-ppcart-loader.php` | `{$hook}` |
| `{$this}{->}{plugin_name}-field-checkbox-options-defaults` | `apply_filters` | Internal | dynamic | `admin/settings/traits/templates/settings-input-fields-field-checkbox.php` | `{$this}{->}{plugin_name}-field-checkbox-options-defaults` |
| `{$this}{->}{plugin_name}-field-editor-options-defaults` | `apply_filters` | Internal | dynamic | `admin/settings/traits/templates/settings-input-fields-field-editor.php` | `{$this}{->}{plugin_name}-field-editor-options-defaults` |
| `{$this}{->}{plugin_name}-field-radios-options-defaults` | `apply_filters` | Internal | dynamic | `admin/settings/traits/templates/settings-input-fields-field-radios.php` | `{$this}{->}{plugin_name}-field-radios-options-defaults` |
| `{$this}{->}{plugin_name}-field-repeater-options-defaults` | `apply_filters` | Internal | dynamic | `admin/settings/traits/templates/settings-input-fields-field-repeater.php` | `{$this}{->}{plugin_name}-field-repeater-options-defaults` |
| `{$this}{->}{plugin_name}-field-repeater-{$setatts[...]}]` | `apply_filters` | Internal | dynamic | `admin/order-metaboxes/traits/templates/order-metabox-edit-fields-metabox-fields.php` | `{$this}{->}{plugin_name}-field-repeater-{$setatts[...]}]` |
| `{$this}{->}{plugin_name}-field-select-options-defaults` | `apply_filters` | Internal | dynamic | `admin/settings/traits/templates/settings-input-fields-field-select.php` | `{$this}{->}{plugin_name}-field-select-options-defaults` |
| `{$this}{->}{plugin_name}-field-text-options-defaults` | `apply_filters` | Internal | dynamic | `admin/settings/traits/templates/settings-text-fields-field-text.php` | `{$this}{->}{plugin_name}-field-text-options-defaults` |
| `{$this}{->}{plugin_name}-field-textarea-options-defaults` | `apply_filters` | Internal | dynamic | `admin/settings/traits/templates/settings-text-fields-field-upload.php` | `{$this}{->}{plugin_name}-field-textarea-options-defaults` |
| `{$this}{->}{plugin_name}-field-{$atts[...]}]` | `apply_filters` | Internal | dynamic | `admin/order-metaboxes/traits/templates/order-metabox-edit-fields-metabox-fields.php` | `{$this}{->}{plugin_name}-field-{$atts[...]}]` |
| `{$this}{->}{plugin_name}-metabox-title-access` | `apply_filters` | Internal | dynamic | `admin/controllers/order/traits/trait-ppcart-admin-order-metabox.php` | `{$this}{->}{plugin_name}-metabox-title-access` |
| `{$this}{->}{plugin_name}-metabox-title-order-details` | `apply_filters` | Internal | dynamic | `admin/class-ppcart-order-metaboxes.php` | `{$this}{->}{plugin_name}-metabox-title-order-details` |
| `{$this}{->}{plugin_name}-metabox-title-order-notes` | `apply_filters` | Internal | dynamic | `admin/controllers/order/traits/trait-ppcart-admin-order-metabox.php` | `{$this}{->}{plugin_name}-metabox-title-order-notes` |
| `{$this}{->}{plugin_name}-metabox-title-product-settings` | `apply_filters` | Internal | dynamic | `admin/metaboxes/traits/trait-ppcart-product-metaboxes-render.php` | `{$this}{->}{plugin_name}-metabox-title-product-settings` |
| `{$this}{->}{plugin_name}-repeater-more-link-label` | `apply_filters` | Internal | dynamic | `admin/partials/ppcart-admin-field-repeater.php` | `{$this}{->}{plugin_name}-repeater-more-link-label` |
| `{$this}{->}{plugin_name}-repeater-remove-link-label` | `apply_filters` | Internal | dynamic | `admin/partials/ppcart-admin-field-repeater.php` | `{$this}{->}{plugin_name}-repeater-remove-link-label` |
| `{$this}{->}{plugin_name}-settings-menu-title` | `apply_filters` | Internal | dynamic | `admin/settings/traits/trait-ppcart-admin-settings-screen.php` | `{$this}{->}{plugin_name}-settings-menu-title` |
| `{$this}{->}{plugin_name}-settings-page-title` | `apply_filters` | Internal | dynamic | `admin/settings/traits/trait-ppcart-admin-settings-screen.php` | `{$this}{->}{plugin_name}-settings-page-title` |
| `{$this}{->}{plugin_name}label-{$k}` | `apply_filters` | Internal | dynamic | `admin/settings/traits/templates/settings-fields-register-fields.php` | `{$this}{->}{plugin_name}label-{$k}` |
| `{$this}{->}{plugin_name}section-title-company` | `apply_filters` | Internal | dynamic | `admin/settings/traits/templates/settings-sections-register.php` | `{$this}{->}{plugin_name}section-title-company` |
| `{$this}{->}{plugin_name}section-title-currency` | `apply_filters` | Internal | dynamic | `admin/settings/traits/templates/settings-sections-register.php` | `{$this}{->}{plugin_name}section-title-currency` |
| `{$this}{->}{plugin_name}section-title-debug` | `apply_filters` | Internal | dynamic | `admin/settings/traits/templates/settings-sections-register.php` | `{$this}{->}{plugin_name}section-title-debug` |
| `{$this}{->}{plugin_name}section-title-downloads` | `apply_filters` | Internal | dynamic | `admin/settings/traits/templates/settings-sections-register.php` | `{$this}{->}{plugin_name}section-title-downloads` |
| `{$this}{->}{plugin_name}section-title-maintenance-db-schema` | `apply_filters` | Internal | dynamic | `admin/settings/traits/trait-ppcart-admin-settings-sections.php` | `{$this}{->}{plugin_name}section-title-maintenance-db-schema` |
| `{$this}{->}{plugin_name}section-title-maintenance-secrets` | `apply_filters` | Internal | dynamic | `admin/settings/traits/trait-ppcart-admin-settings-sections.php` | `{$this}{->}{plugin_name}section-title-maintenance-secrets` |
| `{$this}{->}{plugin_name}section-title-pages` | `apply_filters` | Internal | dynamic | `admin/settings/traits/templates/settings-sections-register.php` | `{$this}{->}{plugin_name}section-title-pages` |
| `{$this}{->}{plugin_name}section-title-settings` | `apply_filters` | Internal | dynamic | `admin/settings/traits/templates/settings-sections-register.php` | `{$this}{->}{plugin_name}section-title-settings` |
| `{$this}{->}{plugin_name}section-title-{$email_key}` | `apply_filters` | Internal | dynamic | `admin/settings/traits/templates/settings-sections-emails.php` | `{$this}{->}{plugin_name}section-title-{$email_key}` |
| `{$this}{->}{plugin_name}section-title-{$intigration_key}` | `apply_filters` | Internal | dynamic | `admin/settings/traits/templates/settings-sections-integrations.php` | `{$this}{->}{plugin_name}section-title-{$intigration_key}` |
| `{$this}{->}{plugin_name}section-title-{$invoice_key}` | `apply_filters` | Internal | dynamic | `admin/settings/traits/trait-ppcart-admin-settings-sections.php` | `{$this}{->}{plugin_name}section-title-{$invoice_key}` |
| `{$this}{->}{plugin_name}section-title-{$payment_gateway_key}` | `apply_filters` | Internal | dynamic | `admin/settings/traits/trait-ppcart-admin-settings-sections.php` | `{$this}{->}{plugin_name}section-title-{$payment_gateway_key}` |
| `{$this}{->}{plugin_name}section-title-{$ppcart_section_key}` | `apply_filters` | Internal | dynamic | `admin/settings/traits/templates/settings-sections-custom-tabs.php` | `{$this}{->}{plugin_name}section-title-{$ppcart_section_key}` |
| `{$this}{->}{plugin_name}section-title-{$tax_key}` | `apply_filters` | Internal | dynamic | `admin/settings/traits/trait-ppcart-admin-settings-sections.php` | `{$this}{->}{plugin_name}section-title-{$tax_key}` |
| `{$top_notice_settings_filter}` | `add_filter` | Internal | dynamic | `includes/class-ppcart-version-notices.php` | `{$top_notice_settings_filter}` |
| `{self}{::}{CRON_HOOK}` | `add_action` | Internal | dynamic | `includes/class-ppcart-stripe-sync.php` | `{self}{::}{CRON_HOOK}` |
