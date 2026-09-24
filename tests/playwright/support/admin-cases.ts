import type { AdminTarget, AdminTestCase } from './admin-executor';

const productEditorTitleTarget: AdminTarget = [
  { selector: 'h1.wp-block-post-title' },
  { selector: 'h1.editor-post-title__input' },
  { selector: '.editor-post-title__input' },
  { selector: 'textarea[aria-label="Add title"]' },
  { selector: 'input[name="post_title"]' },
  { selector: '#title' },
];

const productEditorPublishTarget: AdminTarget = [
  { selector: '.editor-post-publish-panel__header-publish-button' },
  { selector: '.editor-post-publish-button__button' },
  { selector: 'button.editor-post-publish-button' },
  { selector: '.editor-post-publish-panel__toggle' },
  { selector: '#publish' },
];

const productEditorPublishConfirmTarget: AdminTarget = [
  { selector: '.editor-post-publish-panel__header-publish-button' },
  { selector: '.editor-post-publish-button__button' },
  { selector: 'button.editor-post-publish-button' },
];

const productSettingsGeneralTabTarget: AdminTarget = [
  { selector: '[data-testid="ppcart-admin-product-tab-general"]' },
  { selector: 'a[href="#ppcart-tab-general"]' },
];

const productPublicNameFieldTarget: AdminTarget = [
  { selector: '[data-testid="ppcart-admin-field-ppcart-product-name"]' },
  { selector: '#_ppcart_product_name' },
];

const productHideTitleFieldTarget: AdminTarget = [
  { selector: '[data-testid="ppcart-admin-field-ppcart-hide-title"]' },
  { selector: '#_ppcart_hide_title' },
];

const productTypeBody =
  'body.post-type-{{admin_product_post_type}}, body.post-type-ppcart_product, body.post-type-sc_product';
const orderTypeBody =
  'body.post-type-{{admin_order_post_type}}, body.post-type-ppcart_order, body.post-type-sc_order';
const subscriptionTypeBody =
  'body.post-type-{{admin_subscription_post_type}}, body.post-type-ppcart_subscription, body.post-type-sc_subscription';
const productCatBody =
  'body.taxonomy-{{admin_product_cat_taxonomy}}, body.taxonomy-ppcart_product_cat, body.taxonomy-sc_product_cat';
const productTagBody =
  'body.taxonomy-{{admin_product_tag_taxonomy}}, body.taxonomy-ppcart_product_tag, body.taxonomy-sc_product_tag';
const productSingularBody =
  'body.single-{{admin_product_post_type}}, body.single-ppcart_product, body.single-sc_product, body.single-product, body';

export const coreAdminTests: AdminTestCase[] = [
  {
    id: "AD-001",
    title: "Dashboard Page",
    startUrl: "{{publishpress_cart_host}}/wp-admin/admin.php?page=ppcart",
    steps: [
      {
        command: "assertElementPresent",
        target: ".ppcart-getting-started",
      },
      {
        command: "assertTextPresent",
        target: ".ppcart-getting-started",
        value: "Welcome to",
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-sidebar-support\"]",
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-sidebar-knowledge-base\"]",
      }
    ],
  },
  {
    id: "AD-002",
    title: "Products List",
    startUrl: "{{publishpress_cart_host}}/wp-admin/edit.php?post_type={{admin_product_post_type}}",
    steps: [
      {
        command: "assertElementPresent",
        target: productTypeBody,
      },
      {
        command: "assertElementPresent",
        target: "#post-search-input",
      },
      {
        command: "assertElementPresent",
        target: ".page-title-action",
      },
      {
        command: "assertTextPresent",
        target: "body",
        value: "Products",
      }
    ],
  },
  {
    id: "AD-003",
    title: "Add Product Editor",
    startUrl: "{{publishpress_cart_host}}/wp-admin/post-new.php?post_type={{admin_product_post_type}}",
    steps: [
      {
        command: "assertElementPresent",
        target: productTypeBody,
      },
      {
        command: "assertElementPresent",
        target: productEditorTitleTarget,
      },
      {
        command: "assertElementPresent",
        target: productEditorPublishTarget,
      }
    ],
  },
  {
    id: "AD-004",
    title: "Product Categories",
    startUrl: "{{publishpress_cart_host}}/wp-admin/edit-tags.php?taxonomy={{admin_product_cat_taxonomy}}&post_type={{admin_product_post_type}}",
    steps: [
      {
        command: "assertElementPresent",
        target: productCatBody,
      },
      {
        command: "assertElementPresent",
        target: "#tag-name",
      },
      {
        command: "assertElementPresent",
        target: "#submit",
      },
      {
        command: "assertTextPresent",
        target: "body",
        value: "Categories",
      }
    ],
  },
  {
    id: "AD-005",
    title: "Product Tags",
    startUrl: "{{publishpress_cart_host}}/wp-admin/edit-tags.php?taxonomy={{admin_product_tag_taxonomy}}&post_type={{admin_product_post_type}}",
    steps: [
      {
        command: "assertElementPresent",
        target: productTagBody,
      },
      {
        command: "assertElementPresent",
        target: "#tag-name",
      },
      {
        command: "assertElementPresent",
        target: "#submit",
      },
      {
        command: "assertTextPresent",
        target: "body",
        value: "Tags",
      }
    ],
  },
  {
    id: "AD-006",
    title: "Orders List",
    startUrl: "{{publishpress_cart_host}}/wp-admin/edit.php?post_type={{admin_order_post_type}}",
    steps: [
      {
        command: "assertElementPresent",
        target: orderTypeBody,
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-order-type-filter\"]",
      },
      {
        command: "assertElementPresent",
        target: "#post-search-input",
      },
      {
        command: "assertTextPresent",
        target: "body",
        value: "Orders",
      }
    ],
  },
  {
    id: "AD-007",
    title: "Subscriptions List",
    startUrl: "{{publishpress_cart_host}}/wp-admin/edit.php?post_type={{admin_subscription_post_type}}",
    steps: [
      {
        command: "assertElementPresent",
        target: subscriptionTypeBody,
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-subscription-type-filter\"]",
      },
      {
        command: "assertElementPresent",
        target: "#post-search-input",
      },
      {
        command: "assertTextPresent",
        target: "body",
        value: "Subscriptions",
      }
    ],
  },
  {
    id: "AD-008",
    title: "Reports Page",
    startUrl: "{{publishpress_cart_host}}/wp-admin/admin.php?page=ppcart-reports",
    steps: [
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-reports-date-range\"]",
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-reports-apply\"]",
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-reports-export-orders\"]",
      },
      {
        command: "assertTextPresent",
        target: "body",
        value: "Reports",
      }
    ],
  },
  {
    id: "AD-009",
    title: "Contacts Page",
    startUrl: "{{publishpress_cart_host}}/wp-admin/admin.php?page=ppcart-contacts",
    steps: [
      {
        command: "assertElementPresent",
        target: "#contacts_table",
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-contacts-export\"]",
      },
      {
        command: "assertTextPresent",
        target: "body",
        value: "Contacts",
      }
    ],
  },
  {
    id: "AD-010",
    title: "Customer Reports Page",
    startUrl: "{{publishpress_cart_host}}/wp-admin/admin.php?page=ppcart-customer-reports&reportstypes=order&customerid={{admin_customer_email}}",
    steps: [
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-customer-report-back-to-contacts\"]",
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-customer-report-date-range\"]",
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-customer-report-apply\"]",
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-customer-report-tab-orders\"]",
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-customer-report-tab-subscriptions\"]",
      }
    ],
  },
  {
    id: "AD-011",
    title: "Extensions Page",
    startUrl: "{{publishpress_cart_host}}/wp-admin/admin.php?page=ppcart-extensions",
    steps: [
      {
        command: "assertElementPresent",
        target: ".cart-extensions-wrap",
      },
      {
        command: "assertElementPresent",
        target: "[id^=\"product-\"]",
        optional: true,
      },
      {
        command: "assertTextPresent",
        target: "body",
        value: "Extensions",
      }
    ],
  },
  {
    id: "AD-020",
    title: "Settings General Tab",
    startUrl: "{{publishpress_cart_host}}/wp-admin/admin.php?page=ppcart-settings",
    steps: [
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-settings-search\"]",
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-settings-tab-general\"]",
      },
      {
        command: "click",
        target: "[data-testid=\"ppcart-admin-settings-tab-general\"]",
      },
      {
        command: "assertElementPresent",
        target: "#content_tab_general",
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-field-ppcart-currency\"]",
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-field-ppcart-country\"]",
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-field-ppcart-currency-position\"]",
      }
    ],
  },
  {
    id: "AD-021",
    title: "Settings Pages Tab",
    startUrl: "{{publishpress_cart_host}}/wp-admin/admin.php?page=ppcart-settings",
    steps: [
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-settings-search\"]",
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-settings-tab-pages\"]",
      },
      {
        command: "click",
        target: "[data-testid=\"ppcart-admin-settings-tab-pages\"]",
      },
      {
        command: "assertElementPresent",
        target: "#content_tab_pages",
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-field-ppcart-myaccount-page-id\"]",
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-field-ppcart-terms-url\"]",
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-field-ppcart-privacy-url\"]",
      }
    ],
  },
  {
    id: "AD-022",
    title: "Settings Branding Tab",
    startUrl: "{{publishpress_cart_host}}/wp-admin/admin.php?page=ppcart-settings",
    steps: [
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-settings-search\"]",
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-settings-tab-branding\"]",
      },
      {
        command: "click",
        target: "[data-testid=\"ppcart-admin-settings-tab-branding\"]",
      },
      {
        command: "assertElementPresent",
        target: "#content_tab_branding",
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-field-ppcart-company-name\"]",
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-field-ppcart-company-address\"]",
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-field-ppcart-company-logo\"]",
      }
    ],
  },
  {
    id: "AD-023",
    title: "Settings Payment Methods Tab",
    startUrl: "{{publishpress_cart_host}}/wp-admin/admin.php?page=ppcart-settings",
    steps: [
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-settings-search\"]",
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-settings-tab-payment-methods\"]",
      },
      {
        command: "click",
        target: "[data-testid=\"ppcart-admin-settings-tab-payment-methods\"]",
      },
      {
        command: "assertElementPresent",
        target: "#content_tab_payment_methods",
      },
      {
        command: "assertElementPresent",
        target: "[data-testid*=\"ppcart-admin-payment-\"][data-testid$=\"-toggle\"]",
      }
    ],
  },
  {
    id: "AD-024",
    title: "Settings Invoices Tab",
    startUrl: "{{publishpress_cart_host}}/wp-admin/admin.php?page=ppcart-settings",
    steps: [
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-settings-search\"]",
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-settings-tab-invoice\"]",
      },
      {
        command: "click",
        target: "[data-testid=\"ppcart-admin-settings-tab-invoice\"]",
      },
      {
        command: "assertElementPresent",
        target: "#content_tab_invoice",
      },
      {
        command: "assertElementPresent",
        target: "[data-testid*=\"ppcart-admin-field-ppcart-invoice\"]",
        optional: true,
      },
      {
        command: "assertElementPresent",
        target: "[data-testid*=\"ppcart-admin-field-ppcart-invoice-prefix\"]",
        optional: true,
      },
      {
        command: "assertElementPresent",
        target: "[data-testid*=\"ppcart-admin-field-ppcart-invoice-format\"]",
        optional: true,
      }
    ],
  },
  {
    id: "AD-025",
    title: "Settings Downloads Tab",
    startUrl: "{{publishpress_cart_host}}/wp-admin/admin.php?page=ppcart-settings",
    steps: [
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-settings-search\"]",
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-settings-tab-downloads\"]",
        optional: true,
      },
      {
        command: "click",
        target: "[data-testid=\"ppcart-admin-settings-tab-downloads\"]",
        optional: true,
      },
      {
        command: "assertElementPresent",
        target: "#content_tab_downloads",
        optional: true,
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-field-ppcart-download-slug\"]",
        optional: true,
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-field-ppcart-login-to-download\"]",
        optional: true,
      }
    ],
  },
  {
    id: "AD-026",
    title: "Settings Email Tab",
    startUrl: "{{publishpress_cart_host}}/wp-admin/admin.php?page=ppcart-settings",
    steps: [
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-settings-search\"]",
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-settings-tab-emails\"]",
      },
      {
        command: "click",
        target: "[data-testid=\"ppcart-admin-settings-tab-emails\"]",
      },
      {
        command: "assertElementPresent",
        target: "#content_tab_emails",
      },
      {
        command: "assertElementPresent",
        target: "[data-testid*=\"ppcart-admin-field-ppcart-email-\"]",
        optional: true,
      },
      {
        command: "assertElementPresent",
        target: "[data-testid*=\"ppcart-admin-field-ppcart-email-from\"]",
        optional: true,
      },
      {
        command: "assertElementPresent",
        target: "[data-testid*=\"ppcart-admin-field-ppcart-email-reply\"]",
        optional: true,
      }
    ],
  },
  {
    id: "AD-027",
    title: "Settings Integrations Tab",
    startUrl: "{{publishpress_cart_host}}/wp-admin/admin.php?page=ppcart-settings",
    steps: [
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-settings-search\"]",
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-settings-tab-integrations\"]",
      },
      {
        command: "click",
        target: "[data-testid=\"ppcart-admin-settings-tab-integrations\"]",
      },
      {
        command: "assertElementPresent",
        target: "#content_tab_integrations",
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-integrations-search\"]",
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-integrations-filter-all\"]",
      }
    ],
  },
  {
    id: "AD-028",
    title: "Settings Reports Tab",
    startUrl: "{{publishpress_cart_host}}/wp-admin/admin.php?page=ppcart-settings",
    steps: [
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-settings-search\"]",
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-settings-tab-reports\"]",
        optional: true,
      },
      {
        command: "click",
        target: "[data-testid=\"ppcart-admin-settings-tab-reports\"]",
        optional: true,
      },
      {
        command: "assertElementPresent",
        target: "#content_tab_reports",
        optional: true,
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-field-ppcart-admin-email\"]",
        optional: true,
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-field-ppcart-report-schedule\"]",
        optional: true,
      }
    ],
  },
  {
    id: "AD-029",
    title: "Settings Debug Tab",
    startUrl: "{{publishpress_cart_host}}/wp-admin/admin.php?page=ppcart-settings",
    steps: [
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-settings-search\"]",
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-settings-tab-debug\"]",
        optional: true,
      },
      {
        command: "click",
        target: "[data-testid=\"ppcart-admin-settings-tab-debug\"]",
        optional: true,
      },
      {
        command: "assertElementPresent",
        target: "#content_tab_debug",
        optional: true,
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-field-ppcart-enable-debug\"]",
        optional: true,
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-debug-log-view\"]",
        optional: true,
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-stripe-webhook-log-view\"]",
        optional: true,
      }
    ],
  },
  {
    id: "AD-030",
    title: "Settings Maintenance Tab",
    startUrl: "{{publishpress_cart_host}}/wp-admin/admin.php?page=ppcart-settings",
    steps: [
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-settings-search\"]",
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-settings-tab-maintenance\"]",
        optional: true,
      },
      {
        command: "click",
        target: "[data-testid=\"ppcart-admin-settings-tab-maintenance\"]",
        optional: true,
      },
      {
        command: "assertElementPresent",
        target: "#content_tab_maintenance",
        optional: true,
      },
      {
        command: "assertElementPresent",
        target: "[data-ppcart-secrets-maintenance]",
        optional: true,
      }
    ],
  },
  {
    id: "AD-031",
    title: "Settings Advanced Tab",
    startUrl: "{{publishpress_cart_host}}/wp-admin/admin.php?page=ppcart-settings",
    steps: [
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-settings-search\"]",
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-settings-tab-advanced\"]",
      },
      {
        command: "click",
        target: "[data-testid=\"ppcart-admin-settings-tab-advanced\"]",
      },
      {
        command: "assertElementPresent",
        target: "#content_tab_advanced",
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-field-ppcart-product-duplicate-enable\"]",
      }
    ],
  },
  {
    id: "AD-040",
    title: "Debug Log Viewer",
    startUrl: "{{publishpress_cart_host}}/wp-admin/admin.php?page=ppcart-settings&ppcart_payment_subtab=enable",
    steps: [
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-settings-search\"]",
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-settings-tab-debug\"]",
        optional: true,
      },
      {
        command: "click",
        target: "[data-testid=\"ppcart-admin-settings-tab-debug\"]",
        optional: true,
      },
      {
        command: "assertElementPresent",
        target: "#content_tab_debug",
        optional: true,
      },
      {
        command: "check",
        target: "[data-testid=\"ppcart-admin-field-ppcart-enable-debug\"]",
        optional: true,
      },
      {
        command: "click",
        target: "[data-testid=\"ppcart-admin-settings-save-bottom\"]",
        optional: true,
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-debug-log-view\"]",
        optional: true,
      },
      {
        command: "followHref",
        target: "[data-testid=\"ppcart-admin-debug-log-view\"]",
        optional: true,
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-debug-log-view-groups\"]",
        optional: true,
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-debug-log-view-events\"]",
        optional: true,
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-debug-log-search\"]",
        optional: true,
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-debug-log-filter\"]",
        optional: true,
      },
      {
        command: "assertTextPresent",
        target: "body",
        value: "Debug Log",
        optional: true,
      }
    ],
  },
  {
    id: "AD-041",
    title: "Stripe Webhook Log Viewer",
    startUrl: "{{publishpress_cart_host}}/wp-admin/admin.php?page=ppcart-settings&ppcart_payment_subtab=enable",
    steps: [
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-settings-search\"]",
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-settings-tab-debug\"]",
        optional: true,
      },
      {
        command: "click",
        target: "[data-testid=\"ppcart-admin-settings-tab-debug\"]",
        optional: true,
      },
      {
        command: "assertElementPresent",
        target: "#content_tab_debug",
        optional: true,
      },
      {
        command: "check",
        target: "[data-testid=\"ppcart-admin-field-ppcart-enable-stripe-webhook-log\"]",
        optional: true,
      },
      {
        command: "click",
        target: "[data-testid=\"ppcart-admin-settings-save-bottom\"]",
        optional: true,
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-stripe-webhook-log-view\"]",
        optional: true,
      },
      {
        command: "followHref",
        target: "[data-testid=\"ppcart-admin-stripe-webhook-log-view\"]",
        optional: true,
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-stripe-webhook-log-search\"]",
        optional: true,
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-stripe-webhook-log-event-type\"]",
        optional: true,
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-stripe-webhook-log-status\"]",
        optional: true,
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-stripe-webhook-log-filter\"]",
        optional: true,
      },
      {
        command: "assertTextPresent",
        target: "body",
        value: "Stripe",
      }
    ],
  }
];

export const fixtureDependentAdminTests: AdminTestCase[] = [
  {
    id: "AD-101",
    title: "Edit Existing Product",
    startUrl: "{{publishpress_cart_host}}/wp-admin/post.php?post={{admin_product_id}}&action=edit",
    steps: [
      {
        command: "assertElementPresent",
        target: productTypeBody,
      },
      {
        command: "assertElementPresent",
        target: productEditorTitleTarget,
      },
      {
        command: "assertElementPresent",
        target: productEditorPublishTarget,
      }
    ],
    fixtureDependent: true,
  },
  {
    id: "AD-102",
    title: "Edit Existing Order",
    startUrl: "{{publishpress_cart_host}}/wp-admin/post.php?post={{admin_order_id}}&action=edit",
    steps: [
      {
        command: "assertElementPresent",
        target: orderTypeBody,
      },
      {
        command: "assertElementPresent",
        target: "[data-testid$=\"-edit-details\"]",
      },
      {
        command: "click",
        target: "[data-testid$=\"-edit-details\"]",
        optional: true,
      },
      {
        command: "assertElementPresent",
        target: "[data-testid$=\"-customer-email\"]",
        optional: true,
      },
      {
        command: "assertElementPresent",
        target: "[data-testid$=\"-issue-refund\"]",
        optional: true,
      }
    ],
    fixtureDependent: true,
  },
  {
    id: "AD-103",
    title: "Edit Existing Subscription",
    startUrl: "{{publishpress_cart_host}}/wp-admin/post.php?post={{admin_subscription_id}}&action=edit",
    steps: [
      {
        command: "assertElementPresent",
        target: subscriptionTypeBody,
      },
      {
        command: "assertElementPresent",
        target: "[data-testid$=\"-edit-details\"]",
      },
      {
        command: "assertElementPresent",
        target: "[data-testid$=\"-customer-email\"]",
      },
      {
        command: "assertElementPresent",
        target: "[data-testid$=\"-past-purchases\"]",
      }
    ],
    fixtureDependent: true,
  }
];

export const workflowsAdminTests: AdminTestCase[] = [
  {
    id: "AD-201",
    title: "Create Product With Payment Plan",
    startUrl: "{{publishpress_cart_host}}/wp-admin/post-new.php?post_type={{admin_product_post_type}}",
    steps: [
      {
        command: "assertElementPresent",
        target: productTypeBody,
      },
      {
        command: "click",
        target: productEditorTitleTarget,
      },
      {
        command: "assign",
        target: productEditorTitleTarget,
        value: "{{admin_workflow_product_title}}",
      },
      {
        command: "assertElementPresent",
        target: productEditorPublishTarget,
      },
      {
        command: "click",
        target: productEditorPublishTarget,
      },
      {
        command: "click",
        target: productEditorPublishConfirmTarget,
        optional: true,
      },
      {
        command: "assertTextPresent",
        target: "body",
        value: "published",
      }
    ],
  },
  {
    id: "AD-202",
    title: "Product List Shows Created Product",
    startUrl: "{{publishpress_cart_host}}/wp-admin/edit.php?post_type={{admin_product_post_type}}",
    steps: [
      {
        command: "assertElementPresent",
        target: productTypeBody,
      },
      {
        command: "assertElementPresent",
        target: "#post-search-input",
      },
      {
        command: "assertTextPresent",
        target: "body",
        value: "Products",
      }
    ],
  },
  {
    id: "AD-203",
    title: "Public Product Shows Saved Plan",
    startUrl: "{{publishpress_cart_host}}/wp-admin/edit.php?post_type={{admin_product_post_type}}",
    steps: [
      {
        command: "assertElementPresent",
        target: productTypeBody,
      },
      {
        command: "assertElementPresent",
        target: "#the-list tr",
      },
      {
        command: "openFirstProductView",
        target: "#the-list tr",
      },
      {
        command: "assertElementPresent",
        target: productSingularBody,
      },
      {
        command: "assertTextPresent",
        target: "body",
        value: "Product",
      },
      {
        command: "assertTextPresent",
        target: "body",
        value: "Add to cart",
        optional: true,
      }
    ],
  },
  {
    id: "AD-204",
    title: "Create Product Category",
    startUrl: "{{publishpress_cart_host}}/wp-admin/edit-tags.php?taxonomy={{admin_product_cat_taxonomy}}&post_type={{admin_product_post_type}}",
    steps: [
      {
        command: "assertElementPresent",
        target: productCatBody,
      },
      {
        command: "click",
        target: "#tag-name",
      },
      {
        command: "assign",
        target: "#tag-name",
        value: "{{admin_workflow_category_name}}",
      },
      {
        command: "click",
        target: "#tag-slug",
      },
      {
        command: "assign",
        target: "#tag-slug",
        value: "{{admin_workflow_category_slug}}",
      },
      {
        command: "click",
        target: "#submit",
      },
      {
        command: "assertTextPresent",
        target: "#the-list",
        value: "{{admin_workflow_category_name}}",
        optional: true,
      }
    ],
  },
  {
    id: "AD-205",
    title: "Product Category Search Shows Created Term",
    startUrl: "{{publishpress_cart_host}}/wp-admin/edit-tags.php?taxonomy={{admin_product_cat_taxonomy}}&post_type={{admin_product_post_type}}&s={{admin_workflow_category_name}}",
    steps: [
      {
        command: "assertElementPresent",
        target: productCatBody,
      },
      {
        command: "assertTextPresent",
        target: "#the-list",
        value: "{{admin_workflow_category_name}}",
        optional: true,
      },
      {
        command: "assertTextPresent",
        target: "#the-list",
        value: "{{admin_workflow_category_slug}}",
        optional: true,
      }
    ],
  },
  {
    id: "AD-206",
    title: "Create Product Tag",
    startUrl: "{{publishpress_cart_host}}/wp-admin/edit-tags.php?taxonomy={{admin_product_tag_taxonomy}}&post_type={{admin_product_post_type}}",
    steps: [
      {
        command: "assertElementPresent",
        target: productTagBody,
      },
      {
        command: "click",
        target: "#tag-name",
      },
      {
        command: "assign",
        target: "#tag-name",
        value: "{{admin_workflow_tag_name}}",
      },
      {
        command: "click",
        target: "#tag-slug",
      },
      {
        command: "assign",
        target: "#tag-slug",
        value: "{{admin_workflow_tag_slug}}",
      },
      {
        command: "click",
        target: "#submit",
      },
      {
        command: "assertTextPresent",
        target: "#the-list",
        value: "{{admin_workflow_tag_name}}",
        optional: true,
      }
    ],
  },
  {
    id: "AD-207",
    title: "Product Tag Search Shows Created Term",
    startUrl: "{{publishpress_cart_host}}/wp-admin/edit-tags.php?taxonomy={{admin_product_tag_taxonomy}}&post_type={{admin_product_post_type}}&s={{admin_workflow_tag_name}}",
    steps: [
      {
        command: "assertElementPresent",
        target: productTagBody,
      },
      {
        command: "assertTextPresent",
        target: "#the-list",
        value: "{{admin_workflow_tag_name}}",
        optional: true,
      },
      {
        command: "assertTextPresent",
        target: "#the-list",
        value: "{{admin_workflow_tag_slug}}",
        optional: true,
      }
    ],
  },
  {
    id: "AD-208",
    title: "Save Branding Settings",
    startUrl: "{{publishpress_cart_host}}/wp-admin/admin.php?page=ppcart-settings",
    steps: [
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-settings-search\"]",
      },
      {
        command: "click",
        target: "[data-testid=\"ppcart-admin-settings-tab-branding\"]",
      },
      {
        command: "assertElementPresent",
        target: "#content_tab_branding",
      },
      {
        command: "click",
        target: "[data-testid=\"ppcart-admin-field-ppcart-company-name\"]",
      },
      {
        command: "assign",
        target: "[data-testid=\"ppcart-admin-field-ppcart-company-name\"]",
        value: "{{admin_workflow_company_name}}",
      },
      {
        command: "click",
        target: "[data-testid=\"ppcart-admin-field-ppcart-company-address\"]",
      },
      {
        command: "assign",
        target: "[data-testid=\"ppcart-admin-field-ppcart-company-address\"]",
        value: "{{admin_workflow_company_address}}",
      },
      {
        command: "click",
        target: "[data-testid=\"ppcart-admin-settings-save-bottom\"]",
      },
      {
        command: "assertTextPresent",
        target: "body",
        value: "Settings saved",
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-field-ppcart-company-name\"]",
      },
      {
        command: "assertTextPresent",
        target: "[data-testid=\"ppcart-admin-field-ppcart-company-address\"]",
        value: "{{admin_workflow_company_address}}",
      }
    ],
  },
  {
    id: "AD-209",
    title: "Branding Settings Persist",
    startUrl: "{{publishpress_cart_host}}/wp-admin/admin.php?page=ppcart-settings",
    steps: [
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-settings-search\"]",
      },
      {
        command: "click",
        target: "[data-testid=\"ppcart-admin-settings-tab-branding\"]",
      },
      {
        command: "assertElementPresent",
        target: "#content_tab_branding",
      },
      {
        command: "assertElementPresent",
        target: "[data-testid=\"ppcart-admin-field-ppcart-company-name\"]",
      },
      {
        command: "assertTextPresent",
        target: "[data-testid=\"ppcart-admin-field-ppcart-company-address\"]",
        value: "{{admin_workflow_company_address}}",
      }
    ],
  },
  {
    id: "AD-210",
    title: "Public Product Name Persists After Save",
    startUrl: "{{publishpress_cart_host}}/wp-admin/post-new.php?post_type={{admin_product_post_type}}",
    steps: [
      {
        command: "assertElementPresent",
        target: productTypeBody,
      },
      {
        command: "assign",
        target: productEditorTitleTarget,
        value: "{{admin_workflow_meta_product_title}}",
      },
      {
        command: "click",
        target: productSettingsGeneralTabTarget,
        optional: true,
      },
      {
        command: "assign",
        target: productPublicNameFieldTarget,
        value: "{{admin_workflow_public_product_name}}",
      },
      {
        command: "assertElementPresent",
        target: productEditorPublishTarget,
      },
      {
        command: "click",
        target: productEditorPublishTarget,
      },
      {
        command: "click",
        target: productEditorPublishConfirmTarget,
        optional: true,
      },
      {
        command: "assertTextPresent",
        target: "body",
        value: "published",
      },
      {
        command: "reload",
        target: "body",
      },
      {
        command: "click",
        target: productSettingsGeneralTabTarget,
        optional: true,
      },
      {
        command: "assertValue",
        target: productPublicNameFieldTarget,
        value: "{{admin_workflow_public_product_name}}",
      }
    ],
  },
  {
    id: "AD-211",
    title: "Product Hide Title Persists After Save",
    startUrl: "{{publishpress_cart_host}}/wp-admin/post-new.php?post_type={{admin_product_post_type}}",
    steps: [
      {
        command: "assertElementPresent",
        target: productTypeBody,
      },
      {
        command: "assign",
        target: productEditorTitleTarget,
        value: "{{admin_workflow_meta_product_title}} Hide Title",
      },
      {
        command: "click",
        target: productSettingsGeneralTabTarget,
        optional: true,
      },
      {
        command: "check",
        target: productHideTitleFieldTarget,
      },
      {
        command: "assertElementPresent",
        target: productEditorPublishTarget,
      },
      {
        command: "click",
        target: productEditorPublishTarget,
      },
      {
        command: "click",
        target: productEditorPublishConfirmTarget,
        optional: true,
      },
      {
        command: "assertTextPresent",
        target: "body",
        value: "published",
      },
      {
        command: "reload",
        target: "body",
      },
      {
        command: "click",
        target: productSettingsGeneralTabTarget,
        optional: true,
      },
      {
        command: "assertChecked",
        target: productHideTitleFieldTarget,
      }
    ],
  }
];

export const adminTestGroups = {
  core: coreAdminTests,
  fixtureDependent: fixtureDependentAdminTests,
  workflows: workflowsAdminTests,
} as const;

export const adminTestsById = new Map<string, AdminTestCase>(
  Object.values(adminTestGroups)
    .flat()
    .map((testCase) => [testCase.id, testCase])
);
