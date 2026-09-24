<?php
/**
 * Integration smoke test for the PublishPress Cart checkout Gutenberg block.
 *
 * This test intentionally boots the real WordPress install because the behavior
 * under test depends on WordPress block registration, REST routes, do_blocks(),
 * and the existing ppcart_form shortcode.
 */

// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited -- Template-resolution checks intentionally swap the global post context.

if ( PHP_SAPI !== 'cli' ) {
    // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fwrite -- CLI smoke test writes bootstrap failures to STDERR before WordPress loads.
    fwrite( STDERR, "This test must be run from the command line.\n" );
    exit( 1 );
}

$plugin_root = dirname( dirname( dirname( __DIR__ ) ) );
$wp_load     = dirname( dirname( dirname( $plugin_root ) ) ) . '/wp-load.php';

if ( ! file_exists( $wp_load ) ) {
    // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fwrite -- CLI smoke test writes bootstrap failures to STDERR before WordPress loads.
    fwrite( STDERR, "Unable to find wp-load.php at: {$wp_load}\n" );
    exit( 1 );
}

require $wp_load;

$failures = array();
$temporary_user_id = 0;
$temporary_product_id = 0;
$temporary_recurring_product_id = 0;
$temporary_filtered_product_id = 0;
$temporary_featured_attachment_id = 0;
$temporary_account_page_id = 0;
$temporary_account_component_page_id = 0;
$temporary_account_order_id = 0;
$temporary_account_subscription_id = 0;
$temporary_account_payment_plan_id = 0;
$temporary_other_user_id = 0;
$temporary_other_order_id = 0;
$temporary_other_subscription_id = 0;
$temporary_navigation_admin_id = 0;

function ppcart_block_test_assert( $condition, $message ) {
    global $failures;

    if ( $condition ) {
        echo esc_html( "[PASS] {$message}\n" );
        return;
    }

    echo esc_html( "[FAIL] {$message}\n" );
    $failures[] = $message;
}

function ppcart_block_test_skip( $message ) {
    echo esc_html( "[SKIP] {$message}\n" );
}

function ppcart_block_test_registered_style_src( $handle ) {
    $styles = wp_styles();

    if ( ! $styles || empty( $styles->registered[ $handle ] ) ) {
        return '';
    }

    return (string) $styles->registered[ $handle ]->src;
}

function ppcart_block_test_registered_script_src( $handle ) {
    $scripts = wp_scripts();

    if ( ! $scripts || empty( $scripts->registered[ $handle ] ) ) {
        return '';
    }

    return (string) $scripts->registered[ $handle ]->src;
}

function ppcart_block_test_include_filtered_product_type( $post_types ) {
    $post_types   = (array) $post_types;
    $post_types[] = 'ppcart_filter_prod';

    return array_values( array_unique( $post_types ) );
}

function ppcart_block_test_render_blocks( $content ) {
    if ( function_exists( 'ppcart_checkout_reset_request_render_guard' ) ) {
        ppcart_checkout_reset_request_render_guard();
    }

    return do_blocks( $content );
}

function ppcart_block_test_render_post_content( $content ) {
    if ( function_exists( 'ppcart_checkout_reset_request_render_guard' ) ) {
        ppcart_checkout_reset_request_render_guard();
    }

    return do_shortcode( do_blocks( $content ) );
}

function ppcart_block_test_meta_defaults( $user_id, $product_name ) {
    $user = get_userdata( $user_id );

    return array(
        '_ppcart_product_id'         => 0,
        '_ppcart_product_name'       => $product_name,
        '_ppcart_item_name'          => 'Account Block Plan',
        '_ppcart_option_id'          => 'account_block_plan',
        '_ppcart_plan'               => (object) array(
            'name'      => 'Account Block Plan',
            'price'     => 25,
            'type'      => 'recurring',
            'stripe_id' => 'account_block_plan',
        ),
        '_ppcart_amount'             => 25,
        '_ppcart_main_offer_amt'     => 25,
        '_ppcart_pre_tax_amount'     => 25,
        '_ppcart_invoice_total'      => 25,
        '_ppcart_invoice_subtotal'   => 25,
        '_ppcart_sub_amount'         => 25,
        '_ppcart_sub_item_name'      => 'Account Block Plan',
        '_ppcart_sub_interval'       => 'month',
        '_ppcart_sub_frequency'      => 1,
        '_ppcart_sub_next_bill_date' => strtotime( '+1 month' ),
        '_ppcart_first_name'         => 'Account',
        '_ppcart_last_name'          => 'Customer',
        '_ppcart_customer_name'      => 'Account Customer',
        '_ppcart_email'              => $user ? $user->user_email : 'account-block@example.invalid',
        '_ppcart_user_account'       => $user_id,
        '_ppcart_pay_method'         => 'cod',
        '_ppcart_currency'           => 'USD',
        '_ppcart_quantity'           => 1,
    );
}

function ppcart_block_test_create_account_order( $user_id, $product_name ) {
    $order_id = wp_insert_post(
        array(
            'post_type'   => 'sc_order',
            'post_status' => 'paid',
            'post_title'  => $product_name . ' Order',
        )
    );

    if ( ! $order_id || is_wp_error( $order_id ) ) {
        return 0;
    }

    foreach ( ppcart_block_test_meta_defaults( $user_id, $product_name ) as $key => $value ) {
        update_post_meta( $order_id, $key, $value );
    }
    update_post_meta( $order_id, '_ppcart_status', 'paid' );

    return $order_id;
}

function ppcart_block_test_create_account_subscription( $user_id, $product_name, $installments = '-1' ) {
    $subscription_id = wp_insert_post(
        array(
            'post_type'   => 'sc_subscription',
            'post_status' => 'active',
            'post_title'  => $product_name . ' Subscription',
        )
    );

    if ( ! $subscription_id || is_wp_error( $subscription_id ) ) {
        return 0;
    }

    foreach ( ppcart_block_test_meta_defaults( $user_id, $product_name ) as $key => $value ) {
        update_post_meta( $subscription_id, $key, $value );
    }
    update_post_meta( $subscription_id, '_ppcart_status', 'active' );
    update_post_meta( $subscription_id, '_ppcart_sub_status', 'active' );
    update_post_meta( $subscription_id, '_ppcart_subscription_id', 'sub_account_block_' . $subscription_id );
    update_post_meta( $subscription_id, '_ppcart_sub_installments', (string) $installments );

    return $subscription_id;
}

class PPCart_Block_Test_Confirmation_Public extends PPCart_Public_Page_Controller {
    public function __construct() {}

    protected function is_order_confirmation_request() {
        return true;
    }
}

function ppcart_block_test_cleanup() {
    global $temporary_user_id, $temporary_product_id, $temporary_recurring_product_id, $temporary_filtered_product_id, $temporary_featured_attachment_id, $temporary_account_page_id, $temporary_account_component_page_id, $temporary_account_order_id, $temporary_account_subscription_id, $temporary_account_payment_plan_id, $temporary_other_user_id, $temporary_other_order_id, $temporary_other_subscription_id, $temporary_navigation_admin_id;

    if ( $temporary_user_id ) {
        require_once ABSPATH . 'wp-admin/includes/user.php';
        wp_delete_user( $temporary_user_id );
    }

    if ( $temporary_other_user_id ) {
        require_once ABSPATH . 'wp-admin/includes/user.php';
        wp_delete_user( $temporary_other_user_id );
    }

    if ( $temporary_navigation_admin_id ) {
        require_once ABSPATH . 'wp-admin/includes/user.php';
        wp_delete_user( $temporary_navigation_admin_id );
    }

    if ( $temporary_featured_attachment_id ) {
        wp_delete_attachment( $temporary_featured_attachment_id, true );
    }

    if ( $temporary_product_id ) {
        wp_delete_post( $temporary_product_id, true );
    }

    if ( $temporary_recurring_product_id ) {
        wp_delete_post( $temporary_recurring_product_id, true );
    }

    if ( $temporary_filtered_product_id ) {
        wp_delete_post( $temporary_filtered_product_id, true );
    }

    if ( $temporary_account_page_id && ! is_wp_error( $temporary_account_page_id ) ) {
        wp_delete_post( $temporary_account_page_id, true );
    }

    if ( $temporary_account_component_page_id && ! is_wp_error( $temporary_account_component_page_id ) ) {
        wp_delete_post( $temporary_account_component_page_id, true );
    }

    if ( $temporary_account_order_id ) {
        wp_delete_post( $temporary_account_order_id, true );
    }

    if ( $temporary_account_subscription_id ) {
        wp_delete_post( $temporary_account_subscription_id, true );
    }

    if ( $temporary_account_payment_plan_id ) {
        wp_delete_post( $temporary_account_payment_plan_id, true );
    }

    if ( $temporary_other_order_id ) {
        wp_delete_post( $temporary_other_order_id, true );
    }

    if ( $temporary_other_subscription_id ) {
        wp_delete_post( $temporary_other_subscription_id, true );
    }
}

if ( ! did_action( 'init' ) ) {
    do_action( 'init' );
}

$registry = WP_Block_Type_Registry::get_instance();

$checkout_block = $registry->get_registered( 'publishpress-cart/checkout-form' );
$removed_account_block = 'publishpress-cart/account-' . 'page';

ppcart_block_test_assert(
    $registry->is_registered( 'publishpress-cart/checkout-form' ),
    'New checkout block is registered.'
);

ppcart_block_test_assert(
    ! $registry->is_registered( 'sc-products-shortcode/product-shortcode' ),
    'Legacy checkout block alias is not registered by Cart.'
);

ppcart_block_test_assert(
    ! $registry->is_registered( $removed_account_block ),
    'Simple account page block is not registered.'
);

ppcart_block_test_assert(
    wp_style_is( 'ppcart-checkout-form-style', 'registered' )
    && wp_style_is( 'ppcart-checkout-form-editor-style', 'registered' ),
    'Checkout block frontend and editor styles are registered.'
);

if ( $checkout_block instanceof WP_Block_Type
    && property_exists( $checkout_block, 'style_handles' )
    && property_exists( $checkout_block, 'editor_style_handles' ) ) {
    ppcart_block_test_assert(
        in_array( 'ppcart-checkout-form-style', $checkout_block->style_handles, true )
        && in_array( 'ppcart-checkout-form-editor-style', $checkout_block->editor_style_handles, true ),
        'Checkout block uses the dedicated checkout frontend and editor style handles.'
    );
} else {
    ppcart_block_test_skip( 'Checkout block style handle introspection requires WP_Block_Type style handle properties.' );
}

ppcart_block_test_assert(
    false !== strpos( ppcart_block_test_registered_style_src( 'ppcart-checkout-form-style' ), '/includes/integrations/gutenberg/css/checkout-block.css' )
    && false !== strpos( ppcart_block_test_registered_style_src( 'ppcart-checkout-form-editor-style' ), '/includes/integrations/gutenberg/css/checkout-editor.css' ),
    'Checkout block CSS is loaded from includes/integrations/gutenberg/css.'
);

$account_page_block = $registry->get_registered( 'publishpress-cart/account-page-builder' );

ppcart_block_test_assert(
    wp_style_is( 'ppcart-account-block-style', 'registered' )
    && wp_style_is( 'ppcart-account-block-editor-style', 'registered' ),
    'Account block frontend and editor styles are registered with dedicated handles.'
);

if ( $account_page_block instanceof WP_Block_Type
    && property_exists( $account_page_block, 'style_handles' )
    && property_exists( $account_page_block, 'editor_style_handles' ) ) {
    ppcart_block_test_assert(
        in_array( 'ppcart-account-block-style', $account_page_block->style_handles, true )
        && in_array( 'ppcart-account-block-editor-style', $account_page_block->editor_style_handles, true ),
        'Account Page Builder block uses the dedicated account style handles.'
    );
} else {
    ppcart_block_test_skip( 'Account block style handle introspection requires WP_Block_Type style handle properties.' );
}

ppcart_block_test_assert(
    false !== strpos( ppcart_block_test_registered_style_src( 'ppcart-account-block-style' ), '/includes/integrations/gutenberg/css/account-block.css' )
    && false !== strpos( ppcart_block_test_registered_style_src( 'ppcart-account-block-editor-style' ), '/includes/integrations/gutenberg/css/account-editor.css' ),
    'Account block CSS is loaded from includes/integrations/gutenberg/css.'
);

$account_component_blocks = array(
    'publishpress-cart/account-page-builder',
    'publishpress-cart/account-navigation',
    'publishpress-cart/account-tab',
    'publishpress-cart/account-orders',
    'publishpress-cart/account-subscriptions',
    'publishpress-cart/account-payment-plans',
    'publishpress-cart/account-profile',
    'publishpress-cart/account-login',
    'publishpress-cart/account-downloads',
);

foreach ( $account_component_blocks as $account_component_block ) {
    ppcart_block_test_assert(
        $registry->is_registered( $account_component_block ),
        "{$account_component_block} is registered."
    );
}

$temporary_navigation_admin_id = wp_insert_user(
    array(
        'user_login' => 'ppcart_nav_admin_' . wp_generate_uuid4(),
        'user_pass'  => wp_generate_password(),
        'user_email' => 'ppcart-nav-admin-' . wp_generate_uuid4() . '@example.invalid',
        'role'       => 'administrator',
    )
);

if ( ! is_wp_error( $temporary_navigation_admin_id ) && class_exists( 'PPCart_Gutenberg_Bootstrap' ) && function_exists('ppcart_account_tabs') ) {
    wp_set_current_user( $temporary_navigation_admin_id );

    $account_navigation_options = PPCart_Gutenberg_Bootstrap::get_instance()->get_account_renderer()->get_account_navigation_options();
    $editor_tab_values          = wp_list_pluck( $account_navigation_options, 'value' );
    $frontend_tab_values        = wp_list_pluck( ppcart_account_tabs(), 'id' );

    ppcart_block_test_assert(
        in_array( 'tab-files', $editor_tab_values, true )
        && ! in_array( 'tab-files', $frontend_tab_values, true ),
        'Account navigation editor options include Downloads even when the current admin has no downloadable orders.'
    );
} else {
    $temporary_navigation_admin_id = 0;
    ppcart_block_test_skip( 'Account navigation editor option regression check requires creating a temporary admin user.' );
}

wp_set_current_user( 0 );

$account_page_builder_logged_out_output = ppcart_block_test_render_blocks( '<!-- wp:publishpress-cart/account-page-builder {"anchor":"account-page-builder-anchor"} --><!-- wp:publishpress-cart/account-login /--><!-- wp:publishpress-cart/account-navigation /--><!-- /wp:publishpress-cart/account-page-builder -->' );

ppcart_block_test_assert(
    false !== strpos( $account_page_builder_logged_out_output, 'id="account-page-builder-anchor"' )
    && false !== strpos( $account_page_builder_logged_out_output, 'publishpress-cart-account-page-builder' )
    && false !== strpos( $account_page_builder_logged_out_output, 'id="ppcart-login"' )
    && false === strpos( $account_page_builder_logged_out_output, 'ppcart-nav-tabs' ),
    'Account Page Builder block renders saved child blocks and keeps logged-out-only component behavior.'
);

ppcart_block_test_assert(
    wp_script_is( 'ppcart-account-page-view', 'enqueued' )
    && false !== strpos( ppcart_block_test_registered_script_src( 'ppcart-account-page-view' ), '/includes/integrations/gutenberg/build/account-page-view.js' ),
    'Account Page Builder block enqueues the frontend account detail enhancement script.'
);

$temporary_account_page_id = wp_insert_post(
    array(
        'post_type'    => 'page',
        'post_status'  => 'publish',
        'post_title'   => 'PublishPress Cart Account Block Test ' . wp_generate_uuid4(),
        'post_content' => '<!-- wp:publishpress-cart/account-page-builder --><!-- wp:publishpress-cart/account-login /--><!-- /wp:publishpress-cart/account-page-builder -->',
    )
);

$account_controller = class_exists( 'PPCart_Public_Account_Controller' )
    ? new PPCart_Public_Account_Controller()
    : null;

if ( $temporary_account_page_id && ! is_wp_error( $temporary_account_page_id ) && is_object( $account_controller ) && method_exists( $account_controller, 'add_shortcode_specific_body_class' ) ) {
    $previous_post = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;
    // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- CLI smoke test temporarily sets the current post to exercise body_class behavior.
    $GLOBALS['post'] = get_post( $temporary_account_page_id );
    setup_postdata( $GLOBALS['post'] );

    $account_body_classes = $account_controller->add_shortcode_specific_body_class( array() );

    ppcart_block_test_assert(
        in_array( 'account-page', $account_body_classes, true ),
        'Account Page Builder block receives the account-page body class.'
    );

    wp_reset_postdata();
    if ( $previous_post ) {
        // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the global post after the body_class smoke check.
        $GLOBALS['post'] = $previous_post;
    } else {
        unset( $GLOBALS['post'] );
    }
} else {
    ppcart_block_test_skip( 'Account page body class check requires a temporary page and the public plugin instance.' );
}

$temporary_account_component_page_id = wp_insert_post(
    array(
        'post_type'    => 'page',
        'post_status'  => 'publish',
        'post_title'   => 'PublishPress Cart Account Component Block Test ' . wp_generate_uuid4(),
        'post_content' => '<!-- wp:publishpress-cart/account-page-builder --><!-- wp:publishpress-cart/account-orders /--><!-- /wp:publishpress-cart/account-page-builder -->',
    )
);

if ( $temporary_account_component_page_id && ! is_wp_error( $temporary_account_component_page_id ) && is_object( $account_controller ) && method_exists( $account_controller, 'add_shortcode_specific_body_class' ) ) {
    $previous_post = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;
    // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- CLI smoke test temporarily sets the current post to exercise body_class behavior.
    $GLOBALS['post'] = get_post( $temporary_account_component_page_id );
    setup_postdata( $GLOBALS['post'] );

    $account_component_body_classes = $account_controller->add_shortcode_specific_body_class( array() );

    ppcart_block_test_assert(
        in_array( 'account-page', $account_component_body_classes, true ),
        'Account component blocks receive the account-page body class.'
    );

    wp_reset_postdata();
    if ( $previous_post ) {
        // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the global post after the body_class smoke check.
        $GLOBALS['post'] = $previous_post;
    } else {
        unset( $GLOBALS['post'] );
    }
} else {
    ppcart_block_test_skip( 'Account component body class check requires a temporary page and the public plugin instance.' );
}

if ( ! did_action( 'rest_api_init' ) ) {
    do_action( 'rest_api_init' );
}

$routes = rest_get_server()->get_routes();

ppcart_block_test_assert(
    isset( $routes['/publishpress-cart/v1/checkout-block/products'] ),
    'Products REST route is registered.'
);

ppcart_block_test_assert(
    isset( $routes['/publishpress-cart/v1/checkout-block/preview'] ),
    'Preview REST route is registered.'
);

ppcart_block_test_assert(
    isset( $routes['/publishpress-cart/v1/account-block/detail'] ),
    'Account detail REST route is registered.'
);

$products_route = isset( $routes['/publishpress-cart/v1/checkout-block/products'][0] ) ? $routes['/publishpress-cart/v1/checkout-block/products'][0] : null;
$permission    = $products_route && isset( $products_route['permission_callback'] ) ? call_user_func( $products_route['permission_callback'] ) : null;

ppcart_block_test_assert(
    false === (bool) $permission,
    'Products REST route requires an authenticated editor capability.'
);

$account_detail_request = new WP_REST_Request( 'GET', '/publishpress-cart/v1/account-block/detail' );
$account_detail_request->set_param( 'type', 'order' );
$account_detail_request->set_param( 'id', 1 );
$account_detail_logged_out_response = rest_do_request( $account_detail_request );

ppcart_block_test_assert(
    401 === $account_detail_logged_out_response->get_status() || 403 === $account_detail_logged_out_response->get_status(),
    'Account detail REST route requires a logged-in customer.'
);

$limited_user_id = wp_insert_user(
    array(
        'user_login' => 'ppcart_block_limited_' . wp_generate_uuid4(),
        'user_pass'  => wp_generate_password(),
        'user_email' => 'ppcart-block-limited-' . wp_generate_uuid4() . '@example.invalid',
        'role'       => 'author',
    )
);

if ( ! is_wp_error( $limited_user_id ) ) {
    $temporary_user_id = $limited_user_id;
    wp_set_current_user( $limited_user_id );
    $permission = $products_route && isset( $products_route['permission_callback'] ) ? call_user_func( $products_route['permission_callback'] ) : null;

    ppcart_block_test_assert(
        false === (bool) $permission,
        'Products REST route rejects users without product editing capability.'
    );

    wp_set_current_user( 0 );
} else {
    ppcart_block_test_skip( 'Limited capability REST permission check requires creating a temporary author user.' );
}

if ( $temporary_user_id ) {
    $temporary_account_order_id        = ppcart_block_test_create_account_order( $temporary_user_id, 'Account Block Order' );
    $temporary_account_subscription_id = ppcart_block_test_create_account_subscription( $temporary_user_id, 'Account Block Subscription' );
    $temporary_account_payment_plan_id = ppcart_block_test_create_account_subscription( $temporary_user_id, 'Account Block Payment Plan', '3' );
    $temporary_other_user_id           = wp_insert_user(
        array(
            'user_login' => 'ppcart_account_other_' . wp_generate_uuid4(),
            'user_pass'  => wp_generate_password(),
            'user_email' => 'ppcart-account-other-' . wp_generate_uuid4() . '@example.invalid',
            'role'       => 'subscriber',
        )
    );

    if ( ! is_wp_error( $temporary_other_user_id ) ) {
        $temporary_other_order_id        = ppcart_block_test_create_account_order( $temporary_other_user_id, 'Other Account Block Order' );
        $temporary_other_subscription_id = ppcart_block_test_create_account_subscription( $temporary_other_user_id, 'Other Account Block Subscription' );
    } else {
        $temporary_other_user_id = 0;
    }

    wp_set_current_user( $temporary_user_id );

    $account_navigation_output = ppcart_block_test_render_blocks( '<!-- wp:publishpress-cart/account-navigation {"activeTab":"tab-profile","includedTabs":["tab-orders","tab-profile"],"anchor":"account-nav-anchor"} /-->' );

    ppcart_block_test_assert(
        false !== strpos( $account_navigation_output, 'id="account-nav-anchor"' )
        && false !== strpos( $account_navigation_output, 'publishpress-cart-account-navigation' )
        && false !== strpos( $account_navigation_output, 'href="#tab-orders"' )
        && false !== strpos( $account_navigation_output, 'href="#tab-profile"' )
        && false === strpos( $account_navigation_output, 'href="#tab-subscriptions"' )
        && false !== strpos( $account_navigation_output, 'tablinks active' ),
        'Account navigation block renders selected tabs and honors the selected active tab.'
    );

    $account_page_builder_sorted_output = ppcart_block_test_render_blocks( '<!-- wp:publishpress-cart/account-page-builder {"anchor":"account-page-builder-sorted"} --><!-- wp:publishpress-cart/account-profile /--><!-- wp:publishpress-cart/account-navigation /--><!-- wp:publishpress-cart/account-orders /--><!-- /wp:publishpress-cart/account-page-builder -->' );
    $layout_profile_position      = strpos( $account_page_builder_sorted_output, 'profile-wrapper' );
    $layout_navigation_position   = strpos( $account_page_builder_sorted_output, 'ppcart-nav-tabs' );
    $layout_orders_position       = strpos( $account_page_builder_sorted_output, 'order-history-tab' );

    ppcart_block_test_assert(
        false !== strpos( $account_page_builder_sorted_output, 'id="account-page-builder-sorted"' )
        && false !== strpos( $account_page_builder_sorted_output, 'publishpress-cart-account-page-builder' )
        && false !== $layout_profile_position
        && false !== $layout_navigation_position
        && false !== $layout_orders_position
        && $layout_profile_position < $layout_navigation_position
        && $layout_navigation_position < $layout_orders_position,
        'Account Page Builder block renders sortable child blocks in saved order.'
    );

	ppcart_block_test_assert(
		1 === substr_count( $account_page_builder_sorted_output, 'class="ppcart-my-account ppcart-account-list"' )
		&& 1 === substr_count( $account_page_builder_sorted_output, 'class="tabcontent active"' )
		&& false !== strpos( $account_page_builder_sorted_output, 'id="tab-orders" class="tabcontent active"' )
		&& false !== strpos( $account_page_builder_sorted_output, 'id="tab-profile" class="tabcontent"' ),
		'Account Page Builder block renders one shared account shell with one active tab panel.'
	);

    $account_page_builder_nested_output = ppcart_block_test_render_blocks( '<!-- wp:publishpress-cart/account-page-builder --><!-- wp:publishpress-cart/account-navigation --><!-- wp:publishpress-cart/account-tab {"tabId":"tab-orders","label":"Orders"} --><!-- wp:publishpress-cart/account-orders /--><!-- /wp:publishpress-cart/account-tab --><!-- wp:publishpress-cart/account-tab {"tabId":"tab-subscriptions","label":"Subscriptions"} --><!-- wp:publishpress-cart/account-subscriptions /--><!-- /wp:publishpress-cart/account-tab --><!-- wp:publishpress-cart/account-tab {"tabId":"tab-profile","label":"My Profile"} --><!-- wp:publishpress-cart/account-profile /--><!-- /wp:publishpress-cart/account-tab --><!-- /wp:publishpress-cart/account-navigation --><!-- /wp:publishpress-cart/account-page-builder -->' );

    ppcart_block_test_assert(
        1 === substr_count( $account_page_builder_nested_output, 'class="ppcart-my-account ppcart-account-list"' )
        && false !== strpos( $account_page_builder_nested_output, 'ppcart-nav-tabs' )
        && false !== strpos( $account_page_builder_nested_output, 'publishpress-cart-account-tab' )
        && false !== strpos( $account_page_builder_nested_output, 'order-history-tab' )
        && false !== strpos( $account_page_builder_nested_output, 'Active Subscriptions' )
        && false !== strpos( $account_page_builder_nested_output, 'profile-wrapper' )
        && 1 === substr_count( $account_page_builder_nested_output, 'class="tabcontent active"' )
        && false !== strpos( $account_page_builder_nested_output, 'id="tab-orders" class="tabcontent active"' ),
        'Account navigation block can contain sortable account tab blocks inside the layout.'
    );

    $account_page_builder_included_tabs_output = ppcart_block_test_render_blocks( '<!-- wp:publishpress-cart/account-page-builder --><!-- wp:publishpress-cart/account-navigation {"includedTabs":["tab-orders"],"includedTabsConfigured":true} /--><!-- wp:publishpress-cart/account-orders /--><!-- wp:publishpress-cart/account-subscriptions /--><!-- /wp:publishpress-cart/account-page-builder -->' );

    ppcart_block_test_assert(
        false !== strpos( $account_page_builder_included_tabs_output, 'order-history-tab' )
        && false === strpos( $account_page_builder_included_tabs_output, 'subscriptions-tab' )
        && false === strpos( $account_page_builder_included_tabs_output, 'id="tab-subscriptions"' ),
        'Account Page Builder block honors navigation includedTabs for direct sibling panels.'
    );

    $account_page_builder_styled_output = ppcart_block_test_render_blocks( '<!-- wp:publishpress-cart/account-page-builder --><!-- wp:publishpress-cart/account-navigation {"tabStyle":"pills","tabAlignment":"center","tabGap":24,"accentColor":"#1e73be","tabTextColor":"#334155","activeTabTextColor":"#ffffff","tabBackgroundColor":"#f8fafc","activeTabBackgroundColor":"#1e73be"} --><!-- wp:publishpress-cart/account-tab {"tabId":"tab-orders","label":"Orders","panelStyle":"card","panelPadding":32,"panelRadius":12,"panelBackgroundColor":"#ffffff","panelTextColor":"#111827","panelBorderColor":"#dbe3ef"} --><!-- wp:publishpress-cart/account-orders /--><!-- /wp:publishpress-cart/account-tab --><!-- /wp:publishpress-cart/account-navigation --><!-- /wp:publishpress-cart/account-page-builder -->' );

    ppcart_block_test_assert(
        false !== strpos( $account_page_builder_styled_output, 'ppcart-account-nav-style-pills' )
        && false !== strpos( $account_page_builder_styled_output, 'ppcart-account-nav-align-center' )
        && false !== strpos( $account_page_builder_styled_output, '--ppcart-account-nav-gap:24px' )
        && false !== strpos( $account_page_builder_styled_output, '--ppcart-account-nav-accent:#1e73be' )
        && false !== strpos( $account_page_builder_styled_output, '--ppcart-account-nav-active-background:#1e73be' )
        && false !== strpos( $account_page_builder_styled_output, 'ppcart-account-tab-panel-style-card' )
        && false !== strpos( $account_page_builder_styled_output, '--ppcart-account-panel-padding:32px' )
        && false !== strpos( $account_page_builder_styled_output, '--ppcart-account-panel-radius:12px' )
        && false !== strpos( $account_page_builder_styled_output, '--ppcart-account-panel-border-color:#dbe3ef' ),
        'Account navigation and tab blocks render saved style customization attributes.'
    );

    $account_page_builder_width_output = ppcart_block_test_render_blocks( '<!-- wp:publishpress-cart/account-page-builder {"contentWidth":100,"contentWidthUnit":"%"} --><!-- wp:publishpress-cart/account-navigation /--><!-- /wp:publishpress-cart/account-page-builder -->' );

    ppcart_block_test_assert(
        false !== strpos( $account_page_builder_width_output, 'ppcart-account-page-builder-has-container' )
        && false !== strpos( $account_page_builder_width_output, '--ppcart-account-page-builder-max-width:100%' )
        && false === strpos( $account_page_builder_width_output, '--ppcart-account-page-builder-max-width:100px' ),
        'Account Page Builder block supports percentage content max width.'
    );

    $account_page_builder_relative_width_output = ppcart_block_test_render_blocks( '<!-- wp:publishpress-cart/account-page-builder {"contentWidth":42.5,"contentWidthUnit":"rem"} --><!-- wp:publishpress-cart/account-navigation /--><!-- /wp:publishpress-cart/account-page-builder -->' );

	ppcart_block_test_assert(
		false !== strpos( $account_page_builder_relative_width_output, '--ppcart-account-page-builder-max-width:42.5rem' ),
		'Account Page Builder block supports relative content max width units.'
	);

	$account_page_builder_spacing_output = ppcart_block_test_render_blocks( '<!-- wp:publishpress-cart/account-page-builder {"style":{"spacing":{"padding":{"top":"12px","right":"14px","bottom":"16px","left":"18px"},"margin":{"top":"20px","bottom":"24px"}}}} --><!-- wp:publishpress-cart/account-navigation /--><!-- /wp:publishpress-cart/account-page-builder -->' );

	ppcart_block_test_assert(
		false !== strpos( $account_page_builder_spacing_output, 'padding-top:12px' )
		&& false !== strpos( $account_page_builder_spacing_output, 'padding-right:14px' )
		&& false !== strpos( $account_page_builder_spacing_output, 'padding-bottom:16px' )
		&& false !== strpos( $account_page_builder_spacing_output, 'padding-left:18px' )
		&& false !== strpos( $account_page_builder_spacing_output, 'margin-top:20px' )
		&& false !== strpos( $account_page_builder_spacing_output, 'margin-bottom:24px' ),
		'Account Page Builder block supports native padding and margin styles.'
	);

    $account_page_builder_default_inner_output = ppcart_block_test_render_blocks( '<!-- wp:publishpress-cart/account-page-builder --><!-- wp:publishpress-cart/account-navigation /--><!-- /wp:publishpress-cart/account-page-builder -->' );

    ppcart_block_test_assert(
        false === strpos( $account_page_builder_default_inner_output, 'ppcart-account-page-builder-inner-' ),
        'Account Page Builder block keeps the default inner layout backward compatible.'
    );

    $account_page_builder_stretch_inner_output = ppcart_block_test_render_blocks( '<!-- wp:publishpress-cart/account-page-builder {"innerLayout":"stretch"} --><!-- wp:publishpress-cart/account-navigation /--><!-- /wp:publishpress-cart/account-page-builder -->' );

    ppcart_block_test_assert(
        false !== strpos( $account_page_builder_stretch_inner_output, 'ppcart-account-page-builder-inner-stretch' ),
        'Account Page Builder block renders the stretch inner layout class.'
    );

    foreach ( array( 'left', 'center', 'right' ) as $inner_layout_value ) {
        $account_page_builder_inner_output = ppcart_block_test_render_blocks( '<!-- wp:publishpress-cart/account-page-builder {"innerLayout":"' . esc_attr( $inner_layout_value ) . '"} --><!-- wp:publishpress-cart/account-navigation /--><!-- /wp:publishpress-cart/account-page-builder -->' );

        ppcart_block_test_assert(
            false !== strpos( $account_page_builder_inner_output, 'ppcart-account-page-builder-inner-' . $inner_layout_value ),
            'Account Page Builder block renders the ' . $inner_layout_value . ' inner layout class.'
        );
    }

	// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- CLI smoke test checks a local plugin asset for the account tab regression.
	$account_tab_script = file_get_contents( trailingslashit( $plugin_root ) . 'public/js/ppcart-public.js' );

    ppcart_block_test_assert(
        false !== strpos( $account_tab_script, 'tab_id.substring(1)' )
        && false !== strpos( $account_tab_script, 'account.find("#" + tab_id).addClass' )
        && false === strpos( $account_tab_script, "var tab_id = jQuery(this).attr('href');" ),
        'Account tab script normalizes hash links before activating account panels.'
    );

    $orders_extension_callback = function () {
        echo '<div class="ppcart-account-orders-extension-test">Orders extension fixture</div>';
    };
    add_action( 'ppcart_tab_content_tab-orders', $orders_extension_callback );
    $account_orders_output = ppcart_block_test_render_blocks( '<!-- wp:publishpress-cart/account-orders {"anchor":"account-orders-anchor"} /-->' );
    remove_action( 'ppcart_tab_content_tab-orders', $orders_extension_callback );

    ppcart_block_test_assert(
        false !== strpos( $account_orders_output, 'id="account-orders-anchor"' )
        && false !== strpos( $account_orders_output, 'publishpress-cart-account-orders' )
        && false !== strpos( $account_orders_output, 'Account Block Order' )
        && false !== strpos( $account_orders_output, 'order-history-tab' )
        && false !== strpos( $account_orders_output, 'ppcart-account-orders-extension-test' ),
        'Account orders block renders the current customer order history and tab extension hook output.'
    );

    $account_subscriptions_output = ppcart_block_test_render_blocks( '<!-- wp:publishpress-cart/account-subscriptions /-->' );

    ppcart_block_test_assert(
        false !== strpos( $account_subscriptions_output, 'publishpress-cart-account-subscriptions' )
        && false !== strpos( $account_subscriptions_output, 'Account Block Subscription' )
        && false !== strpos( $account_subscriptions_output, 'Active Subscriptions' ),
        'Account subscriptions block renders the current customer subscriptions.'
    );

    $account_payment_plans_output = ppcart_block_test_render_blocks( '<!-- wp:publishpress-cart/account-payment-plans /-->' );

    ppcart_block_test_assert(
        false !== strpos( $account_payment_plans_output, 'publishpress-cart-account-payment-plans' )
        && false !== strpos( $account_payment_plans_output, 'Account Block Payment Plan' )
        && false !== strpos( $account_payment_plans_output, 'Active Plans' ),
        'Account payment plans block renders installment subscriptions.'
    );

    $account_profile_output = ppcart_block_test_render_blocks( '<!-- wp:publishpress-cart/account-profile /-->' );
    $temporary_user         = get_userdata( $temporary_user_id );

    ppcart_block_test_assert(
        false !== strpos( $account_profile_output, 'publishpress-cart-account-profile' )
        && false !== strpos( $account_profile_output, 'id="ppcart-update-profile-form"' )
        && $temporary_user
        && false !== strpos( $account_profile_output, esc_attr( $temporary_user->user_email ) ),
        'Account profile block renders the current customer profile form.'
    );

    $downloads_callback = function () {
        echo '<div class="ppcart-account-downloads-test">Download fixture</div>';
    };
    add_action( 'ppcart_tab_content_tab-files', $downloads_callback );
    $account_downloads_output = ppcart_block_test_render_blocks( '<!-- wp:publishpress-cart/account-downloads /-->' );
    remove_action( 'ppcart_tab_content_tab-files', $downloads_callback );

    ppcart_block_test_assert(
        false !== strpos( $account_downloads_output, 'publishpress-cart-account-downloads' )
        && false !== strpos( $account_downloads_output, 'ppcart-account-downloads-test' ),
        'Account downloads block renders download tab hook output.'
    );

    $logged_in_login_output = ppcart_block_test_render_blocks( '<!-- wp:publishpress-cart/account-login /-->' );

    ppcart_block_test_assert(
        '' === trim( $logged_in_login_output ),
        'Account login block is hidden for logged-in customers by default.'
    );

    $forced_login_output = ppcart_block_test_render_blocks( '<!-- wp:publishpress-cart/account-login {"hideWhenLoggedIn":false} /-->' );

    ppcart_block_test_assert(
        false !== strpos( $forced_login_output, 'publishpress-cart-account-login' )
        && false !== strpos( $forced_login_output, 'id="ppcart-login"' ),
        'Account login block can render the login form when explicitly shown.'
    );

    $minimal_login_output = ppcart_block_test_render_blocks( '<!-- wp:publishpress-cart/account-login {"hideWhenLoggedIn":false,"blockStyle":"minimal"} /-->' );

    ppcart_block_test_assert(
        false !== strpos( $minimal_login_output, 'ppcart-account-login-style-minimal' )
        && false !== strpos( $minimal_login_output, 'id="ppcart-login"' ),
        'Account login block renders the minimal preset class.'
    );

    if ( $temporary_account_order_id ) {
        $previous_request_uri   = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : null;
        $_REQUEST['ppcart-order']   = (string) $temporary_account_order_id;
        $_SERVER['REQUEST_URI'] = '/?page_id=236&ppcart-order=' . rawurlencode( (string) $temporary_account_order_id );

        $account_order_presented_output = ppcart_block_test_render_blocks( '<!-- wp:publishpress-cart/account-page-builder --><!-- wp:publishpress-cart/account-navigation --><!-- wp:publishpress-cart/account-tab {"tabId":"tab-orders","label":"Orders"} --><!-- wp:publishpress-cart/account-orders {"detailPresentation":"slide-right"} /--><!-- /wp:publishpress-cart/account-tab --><!-- wp:publishpress-cart/account-tab {"tabId":"tab-subscriptions","label":"Subscriptions"} --><!-- wp:publishpress-cart/account-subscriptions /--><!-- /wp:publishpress-cart/account-tab --><!-- /wp:publishpress-cart/account-navigation --><!-- /wp:publishpress-cart/account-page-builder -->' );

        unset( $_REQUEST['ppcart-order'] );
        if ( null === $previous_request_uri ) {
            unset( $_SERVER['REQUEST_URI'] );
        } else {
            $_SERVER['REQUEST_URI'] = $previous_request_uri;
        }

        ppcart_block_test_assert(
            false !== strpos( $account_order_presented_output, 'ppcart-nav-tabs' )
            && false !== strpos( $account_order_presented_output, 'order-history-tab' )
            && false !== strpos( $account_order_presented_output, 'id="tab-orders" class="tabcontent active"' )
            && false !== strpos( $account_order_presented_output, 'ppcart-account-detail-presenter--slide-right' )
            && false !== strpos( $account_order_presented_output, 'publishpress-cart-account-order-detail' )
            && false !== strpos( $account_order_presented_output, 'Account Block Order' )
            && false !== strpos( $account_order_presented_output, 'id="ppcart-order-details"' )
            && false !== strpos( $account_order_presented_output, 'Order Details' )
            && false !== strpos( $account_order_presented_output, 'ppcart-subscription-table' )
            && false !== strpos( $account_order_presented_output, 'Invoice' )
            && false === strpos( $account_order_presented_output, 'Purchase receipt' )
            && false === strpos( $account_order_presented_output, 'ppcart-order-detail__hero' )
            && false !== strpos( $account_order_presented_output, 'href="/?page_id=236"' ),
            'Account orders block presents the order detail route from the list block.'
        );

        $account_order_ajax_return_url = home_url( '/?page_id=236&ppcart-order=' . rawurlencode( (string) $temporary_account_order_id ) );
        $account_order_ajax_request    = new WP_REST_Request( 'GET', '/publishpress-cart/v1/account-block/detail' );
        $account_order_ajax_request->set_param( 'type', 'order' );
        $account_order_ajax_request->set_param( 'id', $temporary_account_order_id );
        $account_order_ajax_request->set_param( 'presentation', 'slide-down' );
        $account_order_ajax_request->set_param( 'returnUrl', $account_order_ajax_return_url );
        $account_order_ajax_response = rest_do_request( $account_order_ajax_request );
        $account_order_ajax_data     = $account_order_ajax_response->get_data();
        $account_order_ajax_html     = is_array( $account_order_ajax_data ) && isset( $account_order_ajax_data['html'] ) ? $account_order_ajax_data['html'] : '';

        ppcart_block_test_assert(
            200 === $account_order_ajax_response->get_status()
            && false !== strpos( $account_order_ajax_html, 'ppcart-account-detail-presenter--slide-down' )
            && false !== strpos( $account_order_ajax_html, 'publishpress-cart-account-order-detail' )
            && false !== strpos( $account_order_ajax_html, 'Account Block Order' )
            && false !== strpos( $account_order_ajax_html, 'Order Details' )
            && false !== strpos( $account_order_ajax_html, 'ppcart-subscription-table' )
            && false === strpos( $account_order_ajax_html, 'Purchase receipt' )
            && false === strpos( $account_order_ajax_html, 'ppcart-order-detail__hero' )
            && false !== strpos( $account_order_ajax_html, 'href="' . esc_url( remove_query_arg( array( 'ppcart-order', 'ppcart-plan', 'ppcart-manage', 'action' ), $account_order_ajax_return_url ) ) . '"' )
            && false === strpos( $account_order_ajax_html, 'ppcart-order=' ),
            'Account detail REST route returns order detail presenter HTML without requiring a page reload.'
        );
    } else {
        ppcart_block_test_skip( 'Account orders detail presentation requires creating a temporary order.' );
    }

    if ( $temporary_other_order_id ) {
        $previous_request_uri   = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : null;
        $_REQUEST['ppcart-order']   = (string) $temporary_other_order_id;
        $_SERVER['REQUEST_URI'] = '/?page_id=236&ppcart-order=' . rawurlencode( (string) $temporary_other_order_id );

        $other_order_output = ppcart_block_test_render_blocks( '<!-- wp:publishpress-cart/account-page-builder --><!-- wp:publishpress-cart/account-navigation --><!-- wp:publishpress-cart/account-tab {"tabId":"tab-orders","label":"Orders"} --><!-- wp:publishpress-cart/account-orders {"detailPresentation":"slide-right"} /--><!-- /wp:publishpress-cart/account-tab --><!-- /wp:publishpress-cart/account-navigation --><!-- /wp:publishpress-cart/account-page-builder -->' );

        unset( $_REQUEST['ppcart-order'] );
        if ( null === $previous_request_uri ) {
            unset( $_SERVER['REQUEST_URI'] );
        } else {
            $_SERVER['REQUEST_URI'] = $previous_request_uri;
        }

        ppcart_block_test_assert(
            false !== strpos( $other_order_output, 'You do not have permission to access this account content.' )
            && false === strpos( $other_order_output, 'Other Account Block Order' ),
            'Account orders block detail presentation denies access to another customer order.'
        );

        $other_order_ajax_request = new WP_REST_Request( 'GET', '/publishpress-cart/v1/account-block/detail' );
        $other_order_ajax_request->set_param( 'type', 'order' );
        $other_order_ajax_request->set_param( 'id', $temporary_other_order_id );
        $other_order_ajax_response = rest_do_request( $other_order_ajax_request );
        $other_order_ajax_data     = $other_order_ajax_response->get_data();

        ppcart_block_test_assert(
            403 === $other_order_ajax_response->get_status()
            && is_array( $other_order_ajax_data )
            && isset( $other_order_ajax_data['code'] )
            && 'publishpress_cart_account_detail_forbidden' === $other_order_ajax_data['code']
            && false === strpos( wp_json_encode( $other_order_ajax_data ), 'Other Account Block Order' ),
            'Account detail REST route denies access to another customer order.'
        );
    } else {
        ppcart_block_test_skip( 'Account orders detail ownership check requires creating a second temporary order.' );
    }

    if ( $temporary_account_subscription_id ) {
        $previous_request_uri   = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : null;
        $_REQUEST['ppcart-plan']    = (string) $temporary_account_subscription_id;
        $_SERVER['REQUEST_URI'] = '/?page_id=236&ppcart-plan=' . rawurlencode( (string) $temporary_account_subscription_id );

        $account_subscription_route_output = ppcart_block_test_render_blocks( '<!-- wp:publishpress-cart/account-page-builder --><!-- wp:publishpress-cart/account-navigation --><!-- wp:publishpress-cart/account-tab {"tabId":"tab-orders","label":"Orders"} --><!-- wp:publishpress-cart/account-orders /--><!-- /wp:publishpress-cart/account-tab --><!-- wp:publishpress-cart/account-tab {"tabId":"tab-subscriptions","label":"Subscriptions"} --><!-- wp:publishpress-cart/account-subscriptions {"detailPresentation":"slide-left"} /--><!-- /wp:publishpress-cart/account-tab --><!-- /wp:publishpress-cart/account-navigation --><!-- /wp:publishpress-cart/account-page-builder -->' );

        unset( $_REQUEST['ppcart-plan'] );
        if ( null === $previous_request_uri ) {
            unset( $_SERVER['REQUEST_URI'] );
        } else {
            $_SERVER['REQUEST_URI'] = $previous_request_uri;
        }

        ppcart_block_test_assert(
            false !== strpos( $account_subscription_route_output, 'ppcart-nav-tabs' )
            && false !== strpos( $account_subscription_route_output, 'subscriptions-tab' )
            && false !== strpos( $account_subscription_route_output, 'id="tab-subscriptions" class="tabcontent active"' )
            && false !== strpos( $account_subscription_route_output, 'ppcart-account-detail-presenter--slide-left' )
            && false !== strpos( $account_subscription_route_output, 'publishpress-cart-account-subscription-detail' )
            && false !== strpos( $account_subscription_route_output, 'Account Block Subscription' )
            && false !== strpos( $account_subscription_route_output, 'Details' )
            && false !== strpos( $account_subscription_route_output, 'href="/?page_id=236"' )
            && false !== strpos( $account_subscription_route_output, 'order-history-tab' ),
            'Account subscriptions block presents the subscription detail route from the list block and keeps account navigation visible.'
        );

        $account_subscription_ajax_return_url = home_url( '/?page_id=236&ppcart-plan=' . rawurlencode( (string) $temporary_account_subscription_id ) );
        $account_subscription_ajax_request    = new WP_REST_Request( 'GET', '/publishpress-cart/v1/account-block/detail' );
        $account_subscription_ajax_request->set_param( 'type', 'subscription' );
        $account_subscription_ajax_request->set_param( 'id', $temporary_account_subscription_id );
        $account_subscription_ajax_request->set_param( 'presentation', 'slide-left' );
        $account_subscription_ajax_request->set_param( 'returnUrl', $account_subscription_ajax_return_url );
        $account_subscription_ajax_response = rest_do_request( $account_subscription_ajax_request );
        $account_subscription_ajax_data     = $account_subscription_ajax_response->get_data();
        $account_subscription_ajax_html     = is_array( $account_subscription_ajax_data ) && isset( $account_subscription_ajax_data['html'] ) ? $account_subscription_ajax_data['html'] : '';

        ppcart_block_test_assert(
            200 === $account_subscription_ajax_response->get_status()
            && false !== strpos( $account_subscription_ajax_html, 'ppcart-account-detail-presenter--slide-left' )
            && false !== strpos( $account_subscription_ajax_html, 'publishpress-cart-account-subscription-detail' )
            && false !== strpos( $account_subscription_ajax_html, 'Account Block Subscription' )
            && false !== strpos( $account_subscription_ajax_html, 'href="' . esc_url( remove_query_arg( array( 'ppcart-order', 'ppcart-plan', 'ppcart-manage', 'action' ), $account_subscription_ajax_return_url ) ) . '"' )
            && false === strpos( $account_subscription_ajax_html, 'ppcart-plan=' ),
            'Account detail REST route returns subscription detail presenter HTML without requiring a page reload.'
        );
    } else {
        ppcart_block_test_skip( 'Account subscriptions detail presentation requires creating a temporary subscription.' );
    }

    if ( $temporary_other_subscription_id ) {
        $previous_request_uri   = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : null;
        $_REQUEST['ppcart-plan']    = (string) $temporary_other_subscription_id;
        $_SERVER['REQUEST_URI'] = '/?page_id=236&ppcart-plan=' . rawurlencode( (string) $temporary_other_subscription_id );

        $other_subscription_output = ppcart_block_test_render_blocks( '<!-- wp:publishpress-cart/account-page-builder --><!-- wp:publishpress-cart/account-navigation --><!-- wp:publishpress-cart/account-tab {"tabId":"tab-subscriptions","label":"Subscriptions"} --><!-- wp:publishpress-cart/account-subscriptions {"detailPresentation":"slide-left"} /--><!-- /wp:publishpress-cart/account-tab --><!-- /wp:publishpress-cart/account-navigation --><!-- /wp:publishpress-cart/account-page-builder -->' );

        unset( $_REQUEST['ppcart-plan'] );
        if ( null === $previous_request_uri ) {
            unset( $_SERVER['REQUEST_URI'] );
        } else {
            $_SERVER['REQUEST_URI'] = $previous_request_uri;
        }

        ppcart_block_test_assert(
            false !== strpos( $other_subscription_output, 'You do not have permission to access this account content.' )
            && false === strpos( $other_subscription_output, 'Other Account Block Subscription' ),
            'Account subscriptions block detail presentation denies access to another customer subscription.'
        );

        $other_subscription_ajax_request = new WP_REST_Request( 'GET', '/publishpress-cart/v1/account-block/detail' );
        $other_subscription_ajax_request->set_param( 'type', 'subscription' );
        $other_subscription_ajax_request->set_param( 'id', $temporary_other_subscription_id );
        $other_subscription_ajax_response = rest_do_request( $other_subscription_ajax_request );
        $other_subscription_ajax_data     = $other_subscription_ajax_response->get_data();

        ppcart_block_test_assert(
            403 === $other_subscription_ajax_response->get_status()
            && is_array( $other_subscription_ajax_data )
            && isset( $other_subscription_ajax_data['code'] )
            && 'publishpress_cart_account_detail_forbidden' === $other_subscription_ajax_data['code']
            && false === strpos( wp_json_encode( $other_subscription_ajax_data ), 'Other Account Block Subscription' ),
            'Account detail REST route denies access to another customer subscription.'
        );
    } else {
        ppcart_block_test_skip( 'Account subscriptions detail ownership check requires creating a second temporary subscription.' );
    }

    wp_set_current_user( 0 );
} else {
    ppcart_block_test_skip( 'Account component rendering checks require creating a temporary account user.' );
}

if ( class_exists( 'PPCart_Stripe' ) && class_exists( 'PublishPress\\Stripe\\StripeClient' ) ) {
    $stripe_option_keys = array(
        '_ppcart_stripe_api',
        '_ppcart_stripe_test_sk',
        '_ppcart_stripe_test_pk',
        '_ppcart_stripe_test_webhook_id',
    );
    $previous_stripe_options = array();

    foreach ( $stripe_option_keys as $stripe_option_key ) {
        $previous_stripe_options[ $stripe_option_key ] = array(
            'exists' => false !== get_option( $stripe_option_key, false ),
            'value'  => get_option( $stripe_option_key ),
        );
    }

    update_option( '_ppcart_stripe_api', 'test' );
    update_option( '_ppcart_stripe_test_sk', 'sk_test_ncscartblockcredential000000' );
    update_option( '_ppcart_stripe_test_pk', 'pk_test_ncscartblockcredential000000' );
    delete_option( '_ppcart_stripe_test_webhook_id' );

    $stripe_client_error = null;
    $stripe_client       = null;

    try {
        $stripe_client = PPCart_Stripe::instance()->stripe();
    } catch ( Throwable $e ) {
        $stripe_client_error = $e;
    }

    ppcart_block_test_assert(
        null === $stripe_client_error && $stripe_client instanceof \PublishPress\Stripe\StripeClient,
        'Stripe service can create a client with usable keys when the webhook ID is missing.'
    );

    update_option( '_ppcart_stripe_test_sk', '' );
    $stripe_missing_key_error = null;
    $stripe_missing_key       = null;

    try {
        $stripe_missing_key = PPCart_Stripe::instance()->stripe();
    } catch ( Throwable $e ) {
        $stripe_missing_key_error = $e;
    }

    ppcart_block_test_assert(
        null === $stripe_missing_key_error && false === $stripe_missing_key,
        'Stripe service returns false instead of throwing when account credentials are unusable.'
    );

    foreach ( $previous_stripe_options as $stripe_option_key => $stripe_option ) {
        if ( $stripe_option['exists'] ) {
            update_option( $stripe_option_key, $stripe_option['value'] );
        } else {
            delete_option( $stripe_option_key );
        }
    }
} else {
    ppcart_block_test_skip( 'Stripe service checks require the Stripe integration classes.' );
}

$temporary_product_id = wp_insert_post(
    array(
        'post_type'   => 'sc_product',
        'post_status' => 'publish',
        'post_title'  => 'PublishPress Cart Block Test Product ' . wp_generate_uuid4(),
    )
);

if ( $temporary_product_id && ! is_wp_error( $temporary_product_id ) ) {
    update_post_meta(
        $temporary_product_id,
        '_ppcart_pay_options',
        array(
            array(
                'option_id'         => 'e2e_plan',
                'option_name'       => 'E2E Plan',
                'price'             => '100',
                'frequency'         => '1',
                'sale_frequency'    => '1',
                'interval'          => 'day',
                'sale_interval'     => 'day',
                'installments'      => '-1',
                'sale_installments' => '-1',
                'stripe_plan_id'    => 'e2e_plan',
            ),
            array(
                'option_id'         => 'e2e_plan_200',
                'option_name'       => 'E2E Plan 200',
                'price'             => '200',
                'frequency'         => '1',
                'sale_frequency'    => '1',
                'interval'          => 'day',
                'sale_interval'     => 'day',
                'installments'      => '-1',
                'sale_installments' => '-1',
                'stripe_plan_id'    => 'e2e_plan_200',
            ),
        )
    );
    update_post_meta( $temporary_product_id, '_ppcart_plan_heading', 'Payment Plan' );
    update_post_meta( $temporary_product_id, '_ppcart_button_color', '#000000' );
    update_post_meta( $temporary_product_id, '_ppcart_button_text', 'Order Now' );
    update_post_meta( $temporary_product_id, '_ppcart_checkout_ended_action', 'message' );
    update_post_meta( $temporary_product_id, '_ppcart_checkout_ended_message', 'Sorry, this product is no longer for sale.' );
}

$temporary_recurring_product_id = wp_insert_post(
    array(
        'post_type'   => 'sc_product',
        'post_status' => 'publish',
        'post_title'  => 'PublishPress Cart Block Recurring Test Product ' . wp_generate_uuid4(),
    )
);

if ( $temporary_recurring_product_id && ! is_wp_error( $temporary_recurring_product_id ) ) {
    update_post_meta(
        $temporary_recurring_product_id,
        '_ppcart_pay_options',
        array(
            array(
                'option_id'         => 'recurring_plan',
                'option_name'       => 'Recurring Plan',
                'product_type'      => 'recurring',
                'price'             => '100',
                'frequency'         => '1',
                'sale_frequency'    => '1',
                'interval'          => 'month',
                'sale_interval'     => 'month',
                'installments'      => '-1',
                'sale_installments' => '-1',
                'stripe_plan_id'    => 'recurring_plan',
            ),
        )
    );
    update_post_meta( $temporary_recurring_product_id, '_ppcart_plan_heading', 'Payment Plan' );
    update_post_meta( $temporary_recurring_product_id, '_ppcart_button_color', '#000000' );
    update_post_meta( $temporary_recurring_product_id, '_ppcart_button_text', 'Order Now' );
    update_post_meta( $temporary_recurring_product_id, '_ppcart_checkout_ended_action', 'message' );
    update_post_meta( $temporary_recurring_product_id, '_ppcart_checkout_ended_message', 'Sorry, this product is no longer for sale.' );
}

if ( ! post_type_exists( 'ppcart_filter_prod' ) ) {
    register_post_type(
        'ppcart_filter_prod',
        array(
            'public'          => true,
            'show_ui'         => false,
            'capability_type' => 'post',
            'supports'        => array( 'title', 'editor', 'thumbnail' ),
        )
    );
}

$temporary_filtered_product_id = wp_insert_post(
    array(
        'post_type'   => 'ppcart_filter_prod',
        'post_status' => 'publish',
        'post_title'  => 'PublishPress Cart Filtered Product ' . wp_generate_uuid4(),
    )
);

if ( $temporary_filtered_product_id && ! is_wp_error( $temporary_filtered_product_id ) ) {
    update_post_meta(
        $temporary_filtered_product_id,
        '_ppcart_pay_options',
        array(
            array(
                'option_id'      => 'filtered_plan',
                'option_name'    => 'Filtered Plan',
                'price'          => '100',
                'frequency'      => '1',
                'sale_frequency' => '1',
                'interval'       => 'day',
                'sale_interval'  => 'day',
                'installments'   => '-1',
            ),
        )
    );
    update_post_meta( $temporary_filtered_product_id, '_ppcart_plan_heading', 'Payment Plan' );
    update_post_meta( $temporary_filtered_product_id, '_ppcart_button_color', '#000000' );
    update_post_meta( $temporary_filtered_product_id, '_ppcart_button_text', 'Order Now' );
    update_post_meta( $temporary_filtered_product_id, '_ppcart_checkout_ended_action', 'message' );
    update_post_meta( $temporary_filtered_product_id, '_ppcart_checkout_ended_message', 'Sorry, this product is no longer for sale.' );
}

$product_id = $temporary_product_id && ! is_wp_error( $temporary_product_id ) ? (string) $temporary_product_id : '';
$recurring_product_id = $temporary_recurring_product_id && ! is_wp_error( $temporary_recurring_product_id ) ? (string) $temporary_recurring_product_id : '';
$filtered_product_id = $temporary_filtered_product_id && ! is_wp_error( $temporary_filtered_product_id ) ? (string) $temporary_filtered_product_id : '';

if ( $product_id && $temporary_user_id ) {
    wp_set_current_user( $temporary_user_id );

    $preview_request = new WP_REST_Request( 'GET', '/publishpress-cart/v1/checkout-block/preview' );
    $preview_request->set_param( 'pid', $product_id );
    $preview_response = rest_do_request( $preview_request );

    ppcart_block_test_assert(
        $preview_response->is_error(),
        'Preview REST route rejects users without access to the selected product.'
    );

    wp_set_current_user( 0 );
} elseif ( ! $product_id ) {
    ppcart_block_test_skip( 'Preview permission denial check requires at least one product.' );
}

$capable_users = get_users(
    array(
        'number'   => 20,
        'fields'   => array( 'ID' ),
    )
);
$capable_user_id = 0;

foreach ( $capable_users as $user ) {
    wp_set_current_user( $user->ID );

    if ( current_user_can( 'edit_sc_products' ) ) {
        $capable_user_id = $user->ID;
        break;
    }
}

wp_set_current_user( 0 );

if ( $product_id && $capable_user_id ) {
    wp_set_current_user( $capable_user_id );

    $preview_request = new WP_REST_Request( 'GET', '/publishpress-cart/v1/checkout-block/preview' );
    $preview_request->set_param( 'pid', $product_id );
    $preview_request->set_param( 'template', 'normal' );
    $preview_request->set_param( 'text_settings', wp_json_encode( array( 'contactInfoHeading' => 'REST Buyer Details' ) ) );
    $preview_response = rest_do_request( $preview_request );
    $preview_data     = $preview_response->get_data();

    ppcart_block_test_assert(
        ! $preview_response->is_error(),
        'Preview REST route is available to an authenticated editor.'
    );

    ppcart_block_test_assert(
        ! empty( $preview_data['html'] ) && false !== strpos( $preview_data['html'], 'ppcart-form-container' ),
        'Preview REST route returns rendered checkout HTML.'
    );

    ppcart_block_test_assert(
        ! empty( $preview_data['html'] ) && false !== strpos( $preview_data['html'], 'REST Buyer Details' ),
        'Preview REST route applies checkout block text settings.'
    );

    if ( $filtered_product_id ) {
        add_filter( 'ppcart_product_post_type', 'ppcart_block_test_include_filtered_product_type' );
        add_filter( 'ppcart_setup_product_post_type', 'ppcart_block_test_include_filtered_product_type' );

        $products_request  = new WP_REST_Request( 'GET', '/publishpress-cart/v1/checkout-block/products' );
        $products_response = rest_do_request( $products_request );
        $products_data     = $products_response->get_data();
        $product_ids       = is_array( $products_data ) ? wp_list_pluck( $products_data, 'id' ) : array();

        ppcart_block_test_assert(
            in_array( absint( $filtered_product_id ), array_map( 'absint', $product_ids ), true ),
            'Products REST route honors filtered product post types.'
        );

        remove_filter( 'ppcart_product_post_type', 'ppcart_block_test_include_filtered_product_type' );
        remove_filter( 'ppcart_setup_product_post_type', 'ppcart_block_test_include_filtered_product_type' );
    } else {
        ppcart_block_test_skip( 'Filtered product REST check requires creating a temporary filtered product.' );
    }
} else {
    ppcart_block_test_skip( 'Rendered REST preview requires at least one product and one editor user.' );
}

if ( class_exists( 'PPCart_Product_Template' ) && function_exists( 'register_block_template' ) ) {
    $product_template = new PPCart_Product_Template();
    $product_template->register();
    $registered_template = WP_Block_Templates_Registry::get_instance()->get_registered( PPCart_Product_Template::TEMPLATE_NAME );

    ppcart_block_test_assert(
        $registered_template && false !== strpos( $registered_template->content, 'wp:publishpress-cart/checkout-form' ),
        'FSE product template is registered with the checkout block.'
    );

    ppcart_block_test_assert(
        $registered_template && in_array( 'sc_product', (array) $registered_template->post_types, true ),
        'FSE product template is scoped to sc_product.'
    );

    add_filter( 'ppcart_product_post_type', 'ppcart_block_test_include_filtered_product_type' );
    ppcart_block_test_assert(
        in_array( 'ppcart_filter_prod', PPCart_Product_Template::get_product_post_types(), true ),
        'FSE product template helper honors filtered product post types.'
    );
    remove_filter( 'ppcart_product_post_type', 'ppcart_block_test_include_filtered_product_type' );

    if ( $product_id ) {
        $previous_post = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;
        // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- CLI smoke test temporarily sets the current product to exercise template rendering.
        $GLOBALS['post'] = get_post( $temporary_product_id );
        setup_postdata( $GLOBALS['post'] );

        $fallback_template_output = ppcart_block_test_render_blocks( PPCart_Product_Template::get_template_content() );

        ppcart_block_test_assert(
            1 === substr_count( $fallback_template_output, 'publishpress-cart-product-template__fallback-image' )
            && false !== strpos( $fallback_template_output, 'checkout-background.webp' ),
            'FSE product template Cover uses the fallback image when no featured image is set.'
        );

        $fallback_image_path = trailingslashit( PPCART_BASE_DIR ) . PPCart_Product_Template::FALLBACK_IMAGE_PATH;
        if ( file_exists( $fallback_image_path ) ) {
            // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- CLI test copies a local plugin asset into uploads.
            $upload = wp_upload_bits( 'ppcart-featured-cover-test.webp', null, file_get_contents( $fallback_image_path ) );

            if ( empty( $upload['error'] ) ) {
                $temporary_featured_attachment_id = wp_insert_attachment(
                    array(
                        'post_mime_type' => 'image/webp',
                        'post_title'     => 'PublishPress Cart Featured Cover Test',
                        'post_status'    => 'inherit',
                    ),
                    $upload['file'],
                    $temporary_product_id
                );

                if ( $temporary_featured_attachment_id && ! is_wp_error( $temporary_featured_attachment_id ) ) {
                    set_post_thumbnail( $temporary_product_id, $temporary_featured_attachment_id );

                    $featured_template_output = ppcart_block_test_render_blocks( PPCart_Product_Template::get_template_content() );

                    ppcart_block_test_assert(
                        false === strpos( $featured_template_output, 'publishpress-cart-product-template__fallback-image' )
                        && false !== strpos( $featured_template_output, 'wp-block-cover__image-background' ),
                        'FSE product template Cover keeps the featured image ahead of the fallback image.'
                    );

                    delete_post_thumbnail( $temporary_product_id );
                    wp_delete_attachment( $temporary_featured_attachment_id, true );
                    $temporary_featured_attachment_id = 0;
                } else {
                    wp_delete_file( $upload['file'] );
                    ppcart_block_test_skip( 'Featured image precedence check requires creating a temporary attachment.' );
                }
            } else {
                ppcart_block_test_skip( 'Featured image precedence check requires writing a temporary upload.' );
            }
        } else {
            ppcart_block_test_skip( 'Featured image fallback checks require the fallback image asset.' );
        }

        wp_reset_postdata();
        if ( $previous_post ) {
            // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the global post after the template rendering smoke check.
            $GLOBALS['post'] = $previous_post;
        }
    } else {
        ppcart_block_test_skip( 'FSE product template fallback image checks require a temporary product.' );
    }
} else {
    ppcart_block_test_skip( 'FSE template registration requires WordPress 6.7 register_block_template().' );
}

if ( $product_id && class_exists( 'PPCart_Public_Page_Controller' ) ) {
    $previous_post = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;
    // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- CLI smoke test temporarily sets the current product to exercise template resolution.
    $GLOBALS['post'] = get_post( $temporary_product_id );
    setup_postdata( $GLOBALS['post'] );

    $page_controller = new PPCart_Public_Page_Controller();
    $fallback_single = '/tmp/theme-single.php';
    $checkout_single = $page_controller->product_template( $fallback_single );

    ppcart_block_test_assert(
        $fallback_single === $checkout_single,
        'Supported block themes hand sc_product rendering back to WordPress template resolution.'
    );

    add_filter( 'ppcart_use_block_product_template', '__return_false' );
    $legacy_single = $page_controller->product_template( $fallback_single );
    remove_filter( 'ppcart_use_block_product_template', '__return_false' );

    ppcart_block_test_assert(
        false !== strpos( $legacy_single, 'public/templates/checkout1.php' ),
        'Classic or unsupported themes keep the legacy checkout1.php renderer.'
    );

    $confirmation_public = new PPCart_Block_Test_Confirmation_Public();
    $confirmation_single = $confirmation_public->product_template( $fallback_single );

    ppcart_block_test_assert(
        false !== strpos( $confirmation_single, 'public/templates/checkout1.php' ),
        'Product confirmation requests keep the legacy checkout1.php renderer.'
    );

    update_post_meta( $temporary_product_id, '_ppcart_page_template', 'theme' );
    add_filter( 'ppcart_use_block_product_template', '__return_false' );
    $theme_single = $page_controller->product_template( $fallback_single );
    remove_filter( 'ppcart_use_block_product_template', '__return_false' );
    delete_post_meta( $temporary_product_id, '_ppcart_page_template' );

    ppcart_block_test_assert(
        $fallback_single === $theme_single,
        'Product page template override still hands rendering back to WordPress.'
    );

    update_option( '_ppcart_disable_template', '1' );
    add_filter( 'ppcart_use_block_product_template', '__return_false' );
    $disabled_single = $page_controller->product_template( $fallback_single );
    remove_filter( 'ppcart_use_block_product_template', '__return_false' );
    delete_option( '_ppcart_disable_template' );

    ppcart_block_test_assert(
        $fallback_single === $disabled_single,
        'Global template disable still hands rendering back to WordPress.'
    );

    wp_reset_postdata();
    if ( $previous_post ) {
        // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the global post after the template resolution smoke check.
        $GLOBALS['post'] = $previous_post;
    }
} else {
    ppcart_block_test_skip( 'Template resolution checks require a product and page controller.' );
}

$new_block_content = '<!-- wp:publishpress-cart/checkout-form {"pid":"' . esc_attr( $product_id ) . '","anchor":"checkout-anchor","template":"normal","plan":"e2e_plan_200","hide_labels":true,"styleSettings":{"accentColor":"#123456","surfaceStyle":"card","cornerRadius":"rounded"}} /-->';
$new_block_output  = ppcart_block_test_render_blocks( $new_block_content );

ppcart_block_test_assert(
    false !== strpos( $new_block_output, 'publishpress-cart-checkout-form' ),
    'New checkout block renders a frontend wrapper.'
);

ppcart_block_test_assert(
    false === strpos( $new_block_output, '[ppcart_form' ) && false === strpos( $new_block_output, '[studiocart-form' ),
    'New checkout block consumes the shortcode instead of leaking it.'
);

ppcart_block_test_assert(
    false !== strpos( $new_block_output, '#123456' ) && false !== strpos( $new_block_output, 'border-radius: 10px' ),
    'New checkout block renders scoped customization styles.'
);

ppcart_block_test_assert(
    false !== strpos( $new_block_output, '.ppcart .total{align-items: center;display: flex;' ) && false !== strpos( $new_block_output, '.ppcart .total .price{float: none;' ),
    'New checkout block keeps the order total price aligned inside the total row.'
);

ppcart_block_test_assert(
    false !== strpos( $new_block_output, 'id="checkout-anchor"' ) && false !== strpos( $new_block_output, '#checkout-anchor' ),
    'New checkout block preserves the configured anchor as the style scope.'
);

ppcart_block_test_assert(
    false !== strpos( $new_block_output, 'id="option-e2e_plan_200" checked' ),
    'New checkout block selects the configured payment plan.'
);

if ( $product_id ) {
    $previous_post = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;
    // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- CLI smoke test temporarily sets the current product for dynamic block rendering.
    $GLOBALS['post'] = get_post( $temporary_product_id );
    setup_postdata( $GLOBALS['post'] );

    $dynamic_block_output = ppcart_block_test_render_blocks( '<!-- wp:publishpress-cart/checkout-form /-->' );

    ppcart_block_test_assert(
        false !== strpos( $dynamic_block_output, 'publishpress-cart-checkout-form' )
        && false !== strpos( $dynamic_block_output, 'id="ppcart-payment-form"' ),
        'Checkout block renders against the current sc_product when no product is manually selected.'
    );

    wp_reset_postdata();
    if ( $previous_post ) {
        // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the global post after dynamic block rendering.
        $GLOBALS['post'] = $previous_post;
    }
} else {
    ppcart_block_test_skip( 'Dynamic current-product rendering requires a temporary product.' );
}

if ( $filtered_product_id ) {
    add_filter( 'ppcart_product_post_type', 'ppcart_block_test_include_filtered_product_type' );
    add_filter( 'ppcart_setup_product_post_type', 'ppcart_block_test_include_filtered_product_type' );

    $previous_post = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;
    // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- CLI smoke test temporarily sets the current filtered product for dynamic block rendering.
    $GLOBALS['post'] = get_post( $temporary_filtered_product_id );
    setup_postdata( $GLOBALS['post'] );

    $filtered_dynamic_block_output = ppcart_block_test_render_blocks( '<!-- wp:publishpress-cart/checkout-form /-->' );

    ppcart_block_test_assert(
        false !== strpos( $filtered_dynamic_block_output, 'publishpress-cart-checkout-form' )
        && false !== strpos( $filtered_dynamic_block_output, 'id="ppcart-payment-form"' ),
        'Checkout block resolves current products from filtered product post types.'
    );

    wp_reset_postdata();
    if ( $previous_post ) {
        // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the global post after filtered product rendering.
        $GLOBALS['post'] = $previous_post;
    }

    remove_filter( 'ppcart_product_post_type', 'ppcart_block_test_include_filtered_product_type' );
    remove_filter( 'ppcart_setup_product_post_type', 'ppcart_block_test_include_filtered_product_type' );
} else {
    ppcart_block_test_skip( 'Filtered current-product rendering requires a temporary filtered product.' );
}

$duplicate_block_content = '<!-- wp:publishpress-cart/checkout-form {"pid":"' . esc_attr( $product_id ) . '","template":"normal"} /-->' . "\n" . '<!-- wp:publishpress-cart/checkout-form {"pid":"' . esc_attr( $product_id ) . '","template":"normal"} /-->';
$duplicate_block_output = ppcart_block_test_render_blocks( $duplicate_block_content );

ppcart_block_test_assert(
    1 === substr_count( $duplicate_block_output, 'id="ppcart-payment-form"' ),
    'Duplicate checkout blocks for the same product emit only one live checkout form.'
);

if ( $recurring_product_id ) {
    $multi_product_block_content = '<!-- wp:publishpress-cart/checkout-form {"pid":"' . esc_attr( $product_id ) . '","template":"normal"} /-->' . "\n" . '<!-- wp:publishpress-cart/checkout-form {"pid":"' . esc_attr( $recurring_product_id ) . '","template":"normal"} /-->';
    $multi_product_block_output = ppcart_block_test_render_blocks( $multi_product_block_content );

    ppcart_block_test_assert(
        2 === substr_count( $multi_product_block_output, 'id="ppcart-payment-form"' ),
        'Checkout blocks for different products can render multiple live checkout forms.'
    );
} else {
    ppcart_block_test_skip( 'Multi-product checkout rendering requires a second temporary product.' );
}

$mixed_shortcode_content = '<!-- wp:publishpress-cart/checkout-form {"pid":"' . esc_attr( $product_id ) . '","template":"normal"} /-->' . "\n" . '[ppcart_form id="' . esc_attr( $product_id ) . '"]';
$mixed_shortcode_output = ppcart_block_test_render_post_content( $mixed_shortcode_content );

ppcart_block_test_assert(
    1 === substr_count( $mixed_shortcode_output, 'id="ppcart-payment-form"' ),
    'Checkout block plus legacy checkout shortcode emits only one live checkout form.'
);

$popup_shortcode_content = '<!-- wp:publishpress-cart/checkout-form {"pid":"' . esc_attr( $product_id ) . '","template":"normal"} /-->' . "\n" . '[ppcart_form id="' . esc_attr( $product_id ) . '" ele_popup="1"]';
$popup_shortcode_output = ppcart_block_test_render_post_content( $popup_shortcode_content );

ppcart_block_test_assert(
    2 === substr_count( $popup_shortcode_output, 'id="ppcart-payment-form"' ),
    'Checkout block plus Elementor popup checkout can render two live checkout forms.'
);

$text_block_content = '<!-- wp:publishpress-cart/checkout-form {"pid":"' . esc_attr( $product_id ) . '","template":"normal","textSettings":{"contactInfoHeading":"Buyer Details","paymentPlanHeading":"Choose Your Plan","paymentInfoHeading":"Billing Details","orderTotalHeading":"Checkout Total","dueTodayLabel":"Pay Today"}} /-->';
$text_block_output  = ppcart_block_test_render_blocks( $text_block_content );

ppcart_block_test_assert(
    false !== strpos( $text_block_output, 'Buyer Details' )
    && false !== strpos( $text_block_output, 'Choose Your Plan' )
    && false !== strpos( $text_block_output, 'Billing Details' )
    && false !== strpos( $text_block_output, 'Checkout Total' )
    && false !== strpos( $text_block_output, 'Pay Today' )
    && false !== strpos( $text_block_output, 'name="ppcart_due_today_label" value="Pay Today"' ),
    'New checkout block renders customized normal checkout text.'
);

if ( $recurring_product_id ) {
    $recurring_normal_block_content = '<!-- wp:publishpress-cart/checkout-form {"pid":"' . esc_attr( $recurring_product_id ) . '","template":"normal"} /-->';
    $recurring_normal_block_output  = ppcart_block_test_render_blocks( $recurring_normal_block_content );

    ppcart_block_test_assert(
        false !== strpos( $recurring_normal_block_output, 'Due Today' ),
        'New checkout block renders Due Today for the selected recurring plan.'
    );
} else {
    ppcart_block_test_skip( 'Recurring label checks require creating a temporary recurring product.' );
}

$empty_text_block_content = '<!-- wp:publishpress-cart/checkout-form {"pid":"' . esc_attr( $product_id ) . '","template":"normal","textSettings":{"contactInfoHeading":" "}} /-->';
$empty_text_block_output  = ppcart_block_test_render_blocks( $empty_text_block_content );

ppcart_block_test_assert(
    false === strpos( $empty_text_block_output, '>Contact Info</h3>' ),
    'New checkout block supports intentionally empty text settings.'
);

$arranged_block_content = '<!-- wp:publishpress-cart/checkout-form {"pid":"' . esc_attr( $product_id ) . '","template":"normal","contentOrder":["submit_button","contact_info","payment_plan","coupon","payment_method","payment_details","order_bumps","order_summary","terms_consent","express_payment"]} /-->';
$arranged_block_output  = ppcart_block_test_render_blocks( $arranged_block_content );
$submit_position        = strpos( $arranged_block_output, 'id="ppcart_card_button"' );
$contact_position       = strpos( $arranged_block_output, 'checkout-contact-info' );
$plan_position          = strpos( $arranged_block_output, 'class="ppcart-section products' );

ppcart_block_test_assert(
    false !== $submit_position && false !== $contact_position && false !== $plan_position && $submit_position < $contact_position && $contact_position < $plan_position,
    'New checkout block renders sections in the configured content order.'
);

ppcart_block_test_assert(
    false !== strpos( $arranged_block_output, 'ppcart-section pay-info ppcart-payment-method-section' ),
    'New checkout block keeps arranged payment methods in the payment info visibility group.'
);

if ( ppcart_supports('pro') ) {
    ppcart_block_test_assert(
        false === strpos( $arranged_block_output, 'sc-coupon-section' ),
        'New checkout block does not render a blank coupon section when coupons are unavailable.'
    );
}

$extension_callback = function () {
    echo '<div class="ppcart-test-extension-field">Extension field</div>';
};
add_action( 'ppcart_card_details_fields', $extension_callback, 7, 3 );
$arranged_extension_output = ppcart_block_test_render_blocks( $arranged_block_content );
remove_action( 'ppcart_card_details_fields', $extension_callback, 7 );

ppcart_block_test_assert(
    false !== strpos( $arranged_extension_output, 'ppcart-test-extension-field' ),
    'New checkout block preserves additional checkout field hooks in arranged mode.'
);

$legacy_block_content = '<!-- wp:sc-products-shortcode/product-shortcode {"template":"true","hide_labels":true} /-->';
$legacy_block_output  = ppcart_block_test_render_blocks( $legacy_block_content );

ppcart_block_test_assert(
    false === strpos( $legacy_block_output, 'publishpress-cart-checkout-form' ),
    'Legacy checkout block does not render without Compatibility Mode.'
);

ppcart_block_test_assert(
    false === strpos( $legacy_block_output, '[ppcart_form' ) && false === strpos( $legacy_block_output, '[studiocart-form' ),
    'Legacy checkout block consumes the shortcode instead of leaking it.'
);

if ( $temporary_user_id && ( ! defined( 'REST_REQUEST' ) || REST_REQUEST ) ) {
    if ( ! defined( 'REST_REQUEST' ) ) {
        define( 'REST_REQUEST', true );
    }

    global $wp;

    $previous_rest_route      = is_object( $wp ) && isset( $wp->query_vars['rest_route'] ) ? $wp->query_vars['rest_route'] : null;
    $previous_request_uri     = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : null;
    $had_sc_order_request     = isset( $_REQUEST['ppcart-order'] );
    $previous_sc_order        = $had_sc_order_request ? sanitize_text_field( wp_unslash( $_REQUEST['ppcart-order'] ) ) : null;
    $editor_block_rest_route  = '/wp/v2/block-renderer/publishpress-cart/account-orders';
    $editor_block_request_uri = '/wp-json/wp/v2/block-renderer/publishpress-cart/account-orders';

    wp_set_current_user( $temporary_user_id );

    if ( is_object( $wp ) ) {
        $wp->query_vars['rest_route'] = $editor_block_rest_route;
    }

    $_SERVER['REQUEST_URI'] = $editor_block_request_uri;

    $editor_orders_preview_output = ppcart_block_test_render_blocks( '<!-- wp:publishpress-cart/account-orders /-->' );

    ppcart_block_test_assert(
        false !== strpos( $editor_orders_preview_output, 'Sample Product' )
        && false === strpos( $editor_orders_preview_output, 'Account Block Order' ),
        'Account orders block renders sample data for authenticated editor block previews.'
    );

    $editor_nested_tab_preview_output = ppcart_block_test_render_blocks( '<!-- wp:publishpress-cart/account-navigation --><!-- wp:publishpress-cart/account-tab {"tabId":"tab-orders","label":"Orders"} --><!-- wp:publishpress-cart/account-orders /--><!-- /wp:publishpress-cart/account-tab --><!-- /wp:publishpress-cart/account-navigation -->' );

    ppcart_block_test_assert(
        false !== strpos( $editor_nested_tab_preview_output, 'Sample Product' )
        && false === strpos( $editor_nested_tab_preview_output, 'Account Block Order' ),
        'Nested account tab blocks render sample data for authenticated editor block previews.'
    );

    $editor_downloads_preview_output = ppcart_block_test_render_blocks( '<!-- wp:publishpress-cart/account-downloads /-->' );

    ppcart_block_test_assert(
        false !== strpos( $editor_downloads_preview_output, 'User Guide.pdf' ),
        'Account downloads block renders sample data for authenticated editor block previews.'
    );

    if ( $temporary_other_order_id ) {
        $_REQUEST['ppcart-order']   = (string) $temporary_other_order_id;
        $_SERVER['REQUEST_URI'] = $editor_block_request_uri . '?ppcart-order=' . rawurlencode( (string) $temporary_other_order_id );

        $editor_order_route_output = ppcart_block_test_render_blocks( '<!-- wp:publishpress-cart/account-orders /-->' );

        ppcart_block_test_assert(
            false !== strpos( $editor_order_route_output, 'You do not have permission to access this account content.' )
            && false === strpos( $editor_order_route_output, 'Other Account Block Order' )
            && false === strpos( $editor_order_route_output, 'Sample Product' ),
            'Account orders editor preview keeps ownership checks for explicit detail routes.'
        );
    } else {
        ppcart_block_test_skip( 'Account orders editor preview route ownership check requires creating a second temporary order.' );
    }

    if ( $had_sc_order_request ) {
        $_REQUEST['ppcart-order'] = $previous_sc_order;
    } else {
        unset( $_REQUEST['ppcart-order'] );
    }

    if ( is_object( $wp ) ) {
        if ( null === $previous_rest_route ) {
            unset( $wp->query_vars['rest_route'] );
        } else {
            $wp->query_vars['rest_route'] = $previous_rest_route;
        }
    }

    if ( null === $previous_request_uri ) {
        unset( $_SERVER['REQUEST_URI'] );
    } else {
        $_SERVER['REQUEST_URI'] = $previous_request_uri;
    }

    wp_set_current_user( 0 );
} else {
    ppcart_block_test_skip( 'Account editor preview checks require a temporary editor user and REST_REQUEST support.' );
}

if ( $failures ) {
    ppcart_block_test_cleanup();
    echo "\nGutenberg checkout block integration test failed.\n";
    exit( 1 );
}

ppcart_block_test_cleanup();
echo "\nGutenberg checkout block integration test passed.\n";
