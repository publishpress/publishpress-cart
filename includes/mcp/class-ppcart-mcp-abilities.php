<?php

/**
 * Private, edition-aware Cart ability registration.
 *
 * @package PPCart
 * @since 1.1.0
 */

defined('ABSPATH') || exit;

class PPCart_Mcp_Abilities
{
    private $settings;

    private $products;

    private $plans;

    private $native_products;

    /**
     * @since 1.1.0
     * @param PPCart_Mcp_Settings $settings Store settings operations.
     * @param PPCart_Mcp_Products $products Product controls.
     * @param PPCart_Mcp_Plans $plans Native plan operations.
     * @param PPCart_Product_Metaboxes $native_products Existing native controls.
     */
    public function __construct($settings, $products, $plans, $native_products)
    {
        $this->settings = $settings;
        $this->products = $products;
        $this->plans = $plans;
        $this->native_products = $native_products;
    }

    /**
     * @since 1.1.0
     * @return void
     */
    public function register_category()
    {
        if (function_exists('wp_has_ability_category') && ! wp_has_ability_category('publishpress')) {
            wp_register_ability_category('publishpress', [
                'label' => __('PublishPress', 'publishpress-cart'),
                'description' => __('PublishPress plugin actions.', 'publishpress-cart'),
            ]);
        }
    }

    /**
     * @since 1.1.0
     * @return void
     */
    public function register()
    {
        if (! function_exists('wp_register_ability')) {
            return;
        }
        $empty = PPCart_Mcp_Input::schema([]);
        $features = new PPCart_Mcp_Features();
        $this->ability(
            'list-features',
            __('Discover Cart features', 'publishpress-cart'),
            __('Find native Cart screen and editor scopes, available read/write actions, edition boundaries and required capabilities. Discovery does not grant permission; every action rechecks native capabilities and individual objects.', 'publishpress-cart'),
            $empty,
            [$features, 'read'],
            true,
            [$features, 'permitted']
        );
        $this->ability(
            'get-settings',
            __('Get Cart store settings', 'publishpress-cart'),
            __('Read store currency, company, account page and transactional email settings. Returns exact option identifiers, native stored values, defaults, accepted JSON types, choices and a revision. Read this before changing settings; checkbox writes use JSON booleans, not strings.', 'publishpress-cart'),
            $empty,
            function ($input) use ($empty) {
                $valid = PPCart_Mcp_Input::validate($input, $empty);
                return is_wp_error($valid) ? $valid : $this->settings->read();
            },
            true
        );
        $this->ability(
            'update-settings',
            __('Update Cart store settings', 'publishpress-cart'),
            __('Patch Cart store settings and transactional emails using identifiers and the revision from get-settings. Omitted settings are preserved. All changes are validated before writing. Use dry_run=true to preview native sanitized values. Does not accept credentials or payment gateway operations.', 'publishpress-cart'),
            $this->settings->patch_schema(),
            [$this->settings, 'update'],
            false
        );
        $product_permission = function ($input) {
            $input = PPCart_Mcp_Input::object($input);
            return ! is_wp_error($input) && isset($input['id']) && is_int($input['id'])
                && $this->products->can_edit($input['id']);
        };
        $files = new PPCart_Mcp_Files($this->native_products);
        foreach (['get' => 'read', 'set' => 'update'] as $verb => $method) {
            $this->ability(
                $verb . '-product-files',
                __('Cart secure product downloads', 'publishpress-cart'),
                __('Read native file fields and revision before replacing the ordered rows list. Retained file_id values preserve hidden source URLs and omitted properties. New sources require an editable attachment_id uploaded through Cart secure uploads and upload_files capability. No customer access keys, signed links or source URLs are returned. Native Pro enables expiry/hidden controls. dry_run previews without changing files or access.', 'publishpress-cart'),
                $files->schema('set' === $verb),
                [$files, $method],
                'get' === $verb,
                $product_permission
            );
        }
        $integrations = new PPCart_Mcp_Integrations($this->native_products);
        $integration_permission = function ($input) use ($integrations) {
            $input = PPCart_Mcp_Input::object($input);
            return ! is_wp_error($input) && isset($input['id']) && is_int($input['id']) && $integrations->permitted($input['id']);
        };
        foreach (['get' => 'read', 'set' => 'update'] as $verb => $method) {
            $this->ability(
                $verb . '-product-integrations',
                __('Cart product integration rules', 'publishpress-cart'),
                __('Read active native services, triggers, field choices and revision. Set replaces ordered rows; retain row_id to preserve credentials and omitted fields internally, or omit row_id for a new rule. Requires manage_options and product edit permission. User rules also check user administration capabilities and reject privileged roles. Native Pro dependencies govern Pro services. dry_run never executes rules. Read mapping_sources for approved order fields and declared non-password custom fields. Mappings use destination/source pairs; arbitrary metadata and secret fields are rejected. Refresh discovery when provider choices expire. Authentication headers and credentials stay in native administration and are never returned.', 'publishpress-cart'),
                $integrations->schema('set' === $verb),
                [$integrations, $method],
                'get' === $verb,
                $integration_permission
            );
        }
        $notifications = new PPCart_Mcp_Notifications($this->native_products);
        foreach (['get-product-notifications' => ['read', false], 'set-product-notifications' => ['update', true]] as $name => $operation) {
            $this->ability(
                $name,
                __('Cart product purchase notifications', 'publishpress-cart'),
                __('Read native notification fields and revision, or replace the complete ordered notification list for an editable product. Required fields are notification_name, subject, message and send_to (enter, purchaser, admin). Explicit recipients require send_to_email. Checkbox writes are booleans. Empty optional strings use native nulls. dry_run previews without saving or sending mail; configuring notifications never sends mail immediately.', 'publishpress-cart'),
                $notifications->schema($operation[1]),
                [$notifications, $operation[0]],
                ! $operation[1],
                $product_permission
            );
        }
        $read_product = PPCart_Mcp_Input::schema(['id' => ['type' => 'integer', 'minimum' => 1]], ['id']);
        $this->ability(
            'get-product-settings',
            __('Get Cart product settings', 'publishpress-cart'),
            __('Read a product configuration, native field labels, exact types, accepted choices and revision. Requires permission to edit this specific product. Does not return gateway secrets or download tokens.', 'publishpress-cart'),
            $read_product,
            function ($input) use ($read_product) {
                $valid = PPCart_Mcp_Input::validate($input, $read_product);
                return is_wp_error($valid) ? $valid : $this->products->read($valid['id']);
            },
            true,
            $product_permission
        );
        $this->ability(
            'update-product-settings',
            __('Update Cart product settings', 'publishpress-cart'),
            __('Patch product appearance, availability, stock, sale and confirmation settings. Use the exact identifiers and revision from get-product-settings. Preview with dry_run=true. Preserves omitted fields. Payment plan, coupon and external payment lifecycles are separate operations.', 'publishpress-cart'),
            $this->products->patch_schema(),
            [$this->products, 'update'],
            false,
            $product_permission
        );
        $this->ability(
            'get-payment-plans',
            __('Get Cart payment plans', 'publishpress-cart'),
            __('Read payment plan rows, native field definitions, exact accepted types and revision for a product. Gateway-generated identifiers are not editable. Use this before set-payment-plans.', 'publishpress-cart'),
            $read_product,
            function ($input) use ($read_product) {
                $valid = PPCart_Mcp_Input::validate($input, $read_product);
                return is_wp_error($valid) ? $valid : $this->plans->read($valid['id']);
            },
            true,
            $product_permission
        );
        $this->ability(
            'set-payment-plans',
            __('Set Cart payment plans', 'publishpress-cart'),
            __('Replace the ordered payment-plan list for a product using its revision. Every row needs a unique option_id; omitted fields on retained plan IDs are preserved. Omitted rows are removed. Native plan types include one-time (empty string), recurring, pwyw and free. At least one plan must remain visible. dry_run=true validates without contacting gateways. Saving runs native Stripe synchronization; an unconfirmed gateway result must not be automatically retried.', 'publishpress-cart'),
            $this->plans->schema(),
            [$this->plans, 'update'],
            false,
            $product_permission,
            false
        );

        $records = new PPCart_Mcp_Records();
        $record_permission = static function ($input) use ($records) {
            $input = PPCart_Mcp_Input::object($input);
            return ! is_wp_error($input) && isset($input['kind']) && is_string($input['kind'])
                && $records->permitted($input['kind']);
        };
        $this->ability(
            'list-records',
            __('Find Cart products, orders and subscriptions', 'publishpress-cart'),
            __('Find Cart record IDs by kind and title search with bounded pagination. Results are filtered by native record permissions. Use get-record for order/subscription details or get-product-settings for product configuration. This action never changes order or payment state.', 'publishpress-cart'),
            $records->schema(),
            [$records, 'listing'],
            true,
            $record_permission
        );
        $this->ability(
            'get-record',
            __('Get a Cart record', 'publishpress-cart'),
            __('Read a known product, order or subscription using its kind and ID. Orders include local payment status, amount, refund totals and customer name/email when permitted. No payment credentials, receipt tokens, download links or raw gateway responses are returned.', 'publishpress-cart'),
            $records->schema(true),
            [$records, 'read'],
            true,
            $record_permission
        );
        $content = new PPCart_Mcp_Content();
        $content_permission = static function ($input) use ($content) {
            $input = PPCart_Mcp_Input::object($input);
            return ! is_wp_error($input) && isset($input['kind']) && is_string($input['kind'])
                && $content->permitted($input['kind']);
        };
        $this->ability(
            'create-record',
            __('Create a Cart catalog record', 'publishpress-cart'),
            __('Create a Cart product draft or another supported catalog kind. Requires native create permission; publishing/private status also requires publish permission. Uses WordPress content sanitization. Defaults to draft. dry_run=true previews without creating a post. Configure product settings and payment plans separately after creation.', 'publishpress-cart'),
            $content->schema(true),
            [$content, 'create'],
            false,
            $content_permission,
            false
        );
        $this->ability(
            'update-record',
            __('Update a Cart catalog record', 'publishpress-cart'),
            __('Patch title, content, excerpt, slug, featured_image_id (zero removes it) or publication status of an editable Cart catalog record. Read get-record and supply its revision first. Preserves omitted post fields and product configuration. dry_run=true previews without writing. Cannot edit orders or subscription state.', 'publishpress-cart'),
            $content->schema(false),
            [$content, 'update'],
            false,
            $content_permission
        );
        $terms = new PPCart_Mcp_Terms();
        $term_permission = static function ($input) {
            $input = PPCart_Mcp_Input::object($input);
            if (is_wp_error($input) || ! isset($input['taxonomy']) || ! in_array($input['taxonomy'], ['category', 'tag'], true)) {
                return false;
            }
            $taxonomy = get_taxonomy(ppcart_live_taxonomy('category' === $input['taxonomy'] ? 'product_cat' : 'product_tag'));
            return $taxonomy && (current_user_can($taxonomy->cap->manage_terms) || current_user_can($taxonomy->cap->assign_terms));
        };
        $operations = [
            'list-product-terms' => ['list', 'listing', true, __('Find product categories and tags', 'publishpress-cart')],
            'create-product-term' => ['create', 'create', false, __('Create a product category or tag', 'publishpress-cart')],
            'update-product-term' => ['update', 'update', false, __('Update a product category or tag', 'publishpress-cart')],
            'get-product-terms' => ['get-product', 'get_product', true, __('Get product category or tag assignments', 'publishpress-cart')],
            'get-product-term-deletion' => ['deletion', 'deletion', true, __('Preview product term deletion scope', 'publishpress-cart')],
            'delete-product-term' => ['delete', 'delete', false, __('Delete a product category or tag', 'publishpress-cart')],
            'set-product-terms' => ['set-product', 'set_product', false, __('Set product category or tag assignments', 'publishpress-cart')],
        ];
        foreach ($operations as $name => $operation) {
            $this->ability(
                $name,
                $operation[3],
                __('Use taxonomy category or tag. Lists are paginated. Term updates and replacing a product term_ids list require the revision returned by the corresponding read. Writes support dry_run. Assignment validates every term before writing and preserves the other taxonomy. For deletion, first get-product-term-deletion and supply its scope revision; all affected products and reparented child terms require native permissions. Native taxonomy and product permissions apply.', 'publishpress-cart'),
                $terms->schema($operation[0]),
                [$terms, $operation[1]],
                $operation[2],
                $term_permission,
                'create' !== $operation[0]
            );
        }



        $reports = new PPCart_Mcp_Reports();
        $this->ability(
            'get-monthly-overview',
            __('Get Cart monthly overview', 'publishpress-cart'),
            __('Read the native Monthly Overview dashboard metrics for the current site month: order counts and amounts, subscription statistics and plan statistics. Uses the same native calculations as the dashboard widget.', 'publishpress-cart'),
            $empty,
            [$reports, 'read'],
            true,
            ['PPCart_Mcp_Reports', 'permitted']
        );
        $order_admin = new PPCart_Mcp_Order_Admin($this->native_products);
        $this->ability(
            'resend-order-confirmation',
            __('Resend an order confirmation', 'publishpress-cart'),
            __('Read get-order-details and supply its revision. Sends the native paid-order confirmation to native configured recipients. Requires financial and related object/user permissions. dry_run sends nothing. Not idempotent; check delivery before retrying.', 'publishpress-cart'),
            $order_admin->confirmation_schema(),
            [$order_admin, 'resend_confirmation'],
            false,
            [$order_admin, 'permitted'],
            false
        );
        $operations = new PPCart_Mcp_Payment_Operations();
        foreach (['get-payment-operation' => 'read', 'resolve-payment-operation' => 'resolve'] as $name => $method) {
            $this->ability(
                $name,
                __('Inspect or reconcile a payment operation', 'publishpress-cart'),
                __('Read uncertain operation state without provider IDs or credentials. Resolve requires its revision and a confirmed native outcome after provider synchronization. It cannot discard an unknown result or retry a charge/refund. dry_run does not release the lock.', 'publishpress-cart'),
                $operations->schema('resolve' === $method),
                [$operations, $method],
                'read' === $method,
                [$operations, 'permitted']
            );
        }
        $payments = new PPCart_Mcp_Payments();
        $payment_permission = static function () {
            return PPCart_Mcp_Payments::can_manage();
        };
        $this->ability(
            'refund-order',
            __('Refund a Cart order', 'publishpress-cart'),
            __('Issue a native order refund. Read get-record first and supply its revision. Amount is a positive decimal string using a dot, at most the remaining refundable balance. dry_run=true previews locally without contacting a gateway. Restocking also requires edit access to the product. A gateway action cannot be rolled back; do not automatically retry an unconfirmed result.', 'publishpress-cart'),
            $payments->schema(true),
            [$payments, 'refund'],
            false,
            $payment_permission,
            false
        );
        $this->ability(
            'cancel-subscription',
            __('Cancel a Cart subscription', 'publishpress-cart'),
            __('Cancel a subscription immediately or at period end using its persisted gateway identity. Read get-record first and supply its revision. dry_run=true previews locally without contacting the gateway. Does not issue a refund. Do not automatically retry an unconfirmed result.', 'publishpress-cart'),
            $payments->schema(false),
            [$payments, 'cancel'],
            false,
            $payment_permission,
            false
        );
        foreach (['pause-subscription' => 'pause', 'resume-subscription' => 'resume'] as $name => $method) {
            $this->ability(
                $name,
                __('Pause or resume a Cart subscription', 'publishpress-cart'),
                __('Read the subscription and provide its revision. Uses the persisted gateway identity/mode and native ownership rules, in addition to Cart payment and object permissions. dry_run validates locally without a gateway call. Pausing requires active/trialing/past_due; resuming requires paused. Inspect unconfirmed remote outcomes before retrying.', 'publishpress-cart'),
                $payments->transition_schema(),
                [$payments, $method],
                false,
                $payment_permission,
                false
            );
        }
        $download_access = new PPCart_Mcp_Download_Access();
        foreach (['get-order-downloads' => 'read', 'update-order-download' => 'update', 'revoke-order-download' => 'revoke'] as $name => $method) {
            $this->ability(
                $name,
                __('Cart order download access', 'publishpress-cart'),
                __('Read authorized order download access without customer keys, signed links or sources. Updates and revocation require its revision and an access row belonging to that order. downloads_remaining is a nonnegative integer or null for unlimited; download_expires is native site-local YYYY-MM-DD HH:MM:SS or null for no expiry. dry_run previews without changing access. Native order and payment permissions apply.', 'publishpress-cart'),
                $download_access->schema($method),
                [$download_access, $method],
                'read' === $method,
                $payment_permission
            );
        }
        $upload = new PPCart_Mcp_Upload($this->native_products);
        $this->ability(
            'upload-product-file',
            __('Upload a secure Cart file', 'publishpress-cart'),
            __('Upload a binary file of at most 2 MiB (or the lower WordPress limit) as strict base64 with its lowercase SHA-256 checksum and plain filename. Requires upload_files and product edit access. Uses WordPress MIME checks and the native secure upload directory. Returns only attachment identity, MIME type, size and checksum. Assign attachment_id using set-product-files. Do not upload credentials or automatically retry an unconfirmed upload.', 'publishpress-cart'),
            $upload->schema(),
            [$upload, 'create'],
            false,
            [$upload, 'permitted'],
            false
        );
        $setup = new PPCart_Mcp_Features();
        $this->ability(
            'get-service-setup',
            __('Cart service setup', 'publishpress-cart'),
            __('Get authenticated native WordPress settings destinations for gateway credentials and integration authorization. Does not accept, read or return credentials, bearer URLs or authorization tokens. Requires manage_options.', 'publishpress-cart'),
            PPCart_Mcp_Input::schema([]),
            [$setup, 'service_setup'],
            true
        );
        $experience = new PPCart_Mcp_Checkout_Experience($this->settings);
        foreach (['get-checkout-experience' => 'read', 'set-checkout-experience' => 'update'] as $name => $method) {
            $this->ability(
                $name,
                __('Cart Stripe checkout experience', 'publishpress-cart'),
                __('Choose classic, element or hosted using the native combined selector. Read its revision first. dry_run previews without changing options or contacting Stripe. Hosted checkout does not support order bumps or one-click upsells/downsells. Credentials are configured in the native settings screen.', 'publishpress-cart'),
                $experience->schema('update' === $method),
                [$experience, $method],
                'read' === $method
            );
        }
        $export = new PPCart_Mcp_Export();
        $this->ability(
            'export-records-csv',
            __('Export Cart customer reports', 'publishpress-cart'),
            __('Export CSV pages of orders, subscriptions or contacts. Order exports include customer names, email, phone, address and purchase amounts. Requires native report/export capabilities and read/edit access to every included record. Optional order/subscription filters: inclusive from/to dates, product_id and email. Contacts support email only, with at most 1,000 contributing orders per response. Default 50, maximum 100 rows and 1 MiB per response. Follow next_page; concurrent edits can change pages. No gateway/receipt tokens, arbitrary custom fields or source URLs. Spreadsheet formulas are escaped.', 'publishpress-cart'),
            $export->schema(),
            [$export, 'read'],
            true,
            [$export, 'permitted']
        );
        $lifecycle = new PPCart_Mcp_Lifecycle($this->native_products);
        foreach (['get-catalog-lifecycle' => 'read', 'update-catalog-lifecycle' => 'update'] as $name => $method) {
            $this->ability(
                $name,
                __('Cart catalog lifecycle', 'publishpress-cart'),
                __('Read a catalog lifecycle revision before duplicating a product, moving a catalog record to trash, restoring it or permanently deleting a trashed record. Orders and subscriptions are excluded. Native object permissions apply. Duplication also validates copied integration grants. dry_run validates without writes. Do not retry an unconfirmed mutation.', 'publishpress-cart'),
                $lifecycle->schema('update' === $method),
                [$lifecycle, $method],
                'read' === $method,
                $content_permission,
                false
            );
        }
        foreach (['get-order-details' => ['read', 'read'], 'update-order-details' => ['update', 'update'], 'create-manual-order' => ['create', 'create']] as $name => $operation) {
            $this->ability(
                $name,
                __('Cart manual order administration', 'publishpress-cart'),
                __('Read/update native order contact, address, account and administrative status fields. Updates require a current revision; omitted fields are preserved. Stripe status is locked. create-manual-order records an offline/COD order using native product plan pricing; it does not charge a payment or create a gateway subscription. Native status/create hooks can run notifications and integrations. All related user/product/subscription permissions and existing integration grant policies are checked. dry_run validates locally without saving, sending email or contacting providers. Use refund-order for actual refunds, rather than a bookkeeping status change.', 'publishpress-cart'),
                $order_admin->schema($operation[0]),
                [$order_admin, $operation[1]],
                'read' === $operation[0],
                [$order_admin, 'permitted'],
                'read' === $operation[0]
            );
        }
        $email_test = new PPCart_Mcp_Email_Test($this->settings);
        $this->ability(
            'send-test-email',
            __('Send a Cart transactional test email', 'publishpress-cart'),
            __('Send a native transactional template test to configured administrator/test recipients, using synthetic customer data without reading real orders or charging payments. Requires manage_options and native financial permission. dry_run=true lists current native template types and sends nothing. Sending is not idempotent; check delivery before retrying.', 'publishpress-cart'),
            $email_test->schema(),
            [$email_test, 'send'],
            false,
            [$email_test, 'permitted'],
            false
        );
        $sync = new PPCart_Mcp_Sync($this->native_products);
        foreach (['get-stripe-sync-scope' => 'read', 'sync-stripe-record' => 'update'] as $name => $method) {
            $this->ability(
                $name,
                __('Cart native Stripe synchronization', 'publishpress-cart'),
                __('Read the affected local scope and revision before refreshing a stored Stripe order or subscription through native synchronization. Requires financial and related record/product/user permissions, including native order creation permissions for invoice synchronization. Uses persisted gateway mode; accepts no provider identifiers or credentials. dry_run performs no network calls or writes. Synchronization can run native status effects; inspect unconfirmed outcomes before retrying.', 'publishpress-cart'),
                $sync->schema('update' === $method),
                [$sync, $method],
                'read' === $method,
                [$sync, 'permitted'],
                false
            );
        }
        do_action('ppcart_mcp_register_abilities', $this, $this->native_products);
    }

    /**
     * @since 1.1.0
     * @param string $name Action suffix.
     * @param string $label Display name.
     * @param string $description Action guidance.
     * @param array $schema Closed input schema.
     * @param callable $callback Operation.
     * @param bool $read_only Whether this operation only reads data.
     * @param callable|null $permission Native capability callback.
     * @param bool $idempotent Whether repeated execution has the same effect.
     * @return void
     */
    public function ability($name, $label, $description, array $schema, $callback, $read_only, $permission = null, $idempotent = true)
    {
        $name = 'publishpress-cart/' . $name;
        if (wp_has_ability($name)) {
            return;
        }
        wp_register_ability($name, [
            'label' => $label,
            'description' => $description,
            'category' => 'publishpress',
            'input_schema' => $schema,
            'output_schema' => ['type' => 'object', 'additionalProperties' => true],
            'execute_callback' => $callback,
            'permission_callback' => $permission ?? static function () {
                return current_user_can('manage_options');
            },
            'meta' => [
                'public' => false,
                'show_in_rest' => false,
                'mcp' => ['public' => false],
                'annotations' => ['readonly' => $read_only, 'destructive' => ! $read_only, 'idempotent' => $idempotent],
            ],
        ]);
    }
}
