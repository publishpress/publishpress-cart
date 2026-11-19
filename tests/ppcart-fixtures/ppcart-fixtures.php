<?php
/**
 * Plugin Name:       PublishPress Cart Test Fixtures
 * Description:       Creates dummy PublishPress Cart data for local and automated testing.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            PublishPress
 * License:           GPL-2.0-or-later
 *
 * Install by copying or symlinking this directory into wp-content/plugins/
 * and activating it:
 *
 *   ln -s /path/to/publishpress-cart/tests/ppcart-fixtures wp-content/plugins/ppcart-fixtures
 *   cp -R tests/ppcart-fixtures /path/to/wp-content/plugins/
 *
 * Fixtures are only created when WP_DEBUG is true or PPCART_FIXTURES_ENABLED is set.
 * Manage fixtures from Tools → PublishPress Cart Fixtures in wp-admin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/ppcart-regression-fixtures.php';
require_once __DIR__ . '/ppcart-smoke-fixtures.php';
require_once __DIR__ . '/ppcart-admin-fixtures.php';
require_once __DIR__ . '/ppcart-regression-checkout-fixtures.php';
require_once __DIR__ . '/ppcart-regression-paypal-mock.php';
require_once __DIR__ . '/ppcart-regression-stripe-mock.php';

/**
 * Whether regression tests requested mocked Stripe/PayPal for this request.
 */
function ppcart_regression_mocked_gateways_requested(): bool {
	if ( defined( 'PPCART_REGRESSION_MOCKED_GATEWAYS' ) && PPCART_REGRESSION_MOCKED_GATEWAYS ) {
		return true;
	}

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Compared against a fixed literal below.
	$header = isset( $_SERVER['HTTP_X_PPCART_REGRESSION_MOCKED'] ) ? (string) wp_unslash( $_SERVER['HTTP_X_PPCART_REGRESSION_MOCKED'] ) : '';

	if ( '1' === $header ) {
		return true;
	}

	return get_option( 'ppcart_regression_mocked_gateways', '0' ) === '1';
}

/**
 * Persist fixture post meta on canonical `_ppcart_*` keys.
 *
 * @param mixed $value Meta value.
 */
function ppcart_fixtures_update_post_meta( $post_id, $key, $value ): void {
	if ( function_exists( 'ppcart_update_post_meta' ) ) {
		ppcart_update_post_meta( (int) $post_id, (string) $key, $value );

		return;
	}

	update_post_meta( $post_id, $key, $value );
}

/**
 * @return mixed
 */
function ppcart_fixtures_get_pay_options( $post_id ) {
	if ( function_exists( 'ppcart_get_post_meta' ) ) {
		return ppcart_get_post_meta( (int) $post_id, 'pay_options', true );
	}

	$canonical = get_post_meta( $post_id, '_ppcart_pay_options', true );
	if ( is_array( $canonical ) && $canonical !== [] ) {
		return $canonical;
	}

	return get_post_meta( $post_id, '_ppcart_pay_options', true );
}

function ppcart_fixtures_pay_options_objects_key(): string {
	return function_exists( 'ppcart_meta_key' ) ? ppcart_meta_key( 'pay_options' ) : '_ppcart_pay_options';
}

function ppcart_fixtures_product_post_type(): string {
	return function_exists( 'ppcart_live_post_type' ) ? ppcart_live_post_type( 'product' ) : 'ppcart_product';
}

/**
 * Delete published pages occupying the given slugs so leftover shortcode
 * content cannot keep fixture URLs from rendering canonical tags.
 *
 * @param list<string> $slugs Page slugs.
 */
function ppcart_fixtures_delete_pages_by_slugs( array $slugs ): int {
	$removed = 0;

	foreach ( $slugs as $slug ) {
		$slug = sanitize_title( (string) $slug );
		if ( '' === $slug ) {
			continue;
		}

		$page = get_page_by_path( $slug, OBJECT, 'page' );
		if ( $page instanceof WP_Post && wp_delete_post( (int) $page->ID, true ) ) {
			++$removed;
		}
	}

	return $removed;
}

PPCart_Regression_Checkout_Fixtures::init();
PPCart_Regression_PayPal_Mock::init();
PPCart_Regression_Stripe_Mock::init();

final class PPCart_Fixtures {

	const REGISTRY_OPTION = 'ppcart_fixtures_registry';

	/**
	 * Whether fixture creation is allowed in this environment.
	 */
	public static function is_enabled() {
		if ( defined( 'PPCART_FIXTURES_ENABLED' ) ) {
			return (bool) PPCART_FIXTURES_ENABLED;
		}

		return defined( 'WP_DEBUG' ) && WP_DEBUG;
	}

	/**
	 * Whether PublishPress Cart post types are available.
	 */
	public static function cart_is_available() {
		return post_type_exists( function_exists( 'ppcart_live_post_type' ) ? ppcart_live_post_type( 'product' ) : 'ppcart_product' );
	}

	/**
	 * @return array<string, int>
	 */
	public static function get_registry_counts() {
		$registry = self::get_registry();
		$types    = [ 'products', 'pages', 'users', 'orders', 'subscriptions' ];
		$counts   = [];

		foreach ( $types as $type ) {
			$counts[ $type ] = isset( $registry[ $type ] ) && is_array( $registry[ $type ] )
				? count( $registry[ $type ] )
				: 0;
		}

		return $counts;
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function get_registry() {
		$registry = get_option( self::REGISTRY_OPTION, [] );

		return is_array( $registry ) ? $registry : [];
	}

	/**
	 * @param array<string, mixed> $registry Registry payload.
	 */
	private static function save_registry( array $registry ) {
		update_option( self::REGISTRY_OPTION, $registry, false );
	}

	/**
	 * @param string               $type Entity type key.
	 * @param int                  $id   Entity ID.
	 * @param array<string, mixed> $meta Optional metadata stored for cleanup.
	 */
	private static function track( $type, $id, array $meta = [] ) {
		$id = (int) $id;

		if ( $id <= 0 ) {
			return;
		}

		$registry = self::get_registry();

		if ( ! isset( $registry[ $type ] ) || ! is_array( $registry[ $type ] ) ) {
			$registry[ $type ] = [];
		}

		$registry[ $type ][ (string) $id ] = array_merge(
			[
				'created_at' => time(),
			],
			$meta
		);

		self::save_registry( $registry );
	}

	/**
	 * @param string $type Entity type key.
	 * @param int    $id   Entity ID.
	 */
	private static function untrack( $type, $id ) {
		$registry = self::get_registry();
		$key      = (string) (int) $id;

		if ( isset( $registry[ $type ][ $key ] ) ) {
			unset( $registry[ $type ][ $key ] );
			self::save_registry( $registry );
		}
	}

	/**
	 * @return int|\WP_Error
	 */
	public static function create_product( array $args = [] ) {
		self::assert_can_create();

		$type  = isset( $args['type'] ) ? (string) $args['type'] : 'e2e';
		$title = isset( $args['title'] ) ? (string) $args['title'] : self::unique_title( 'PublishPress Cart Test Product' );

		$product_id = wp_insert_post(
			[
				'post_type'   => ppcart_live_post_type('product'),
				'post_status' => 'publish',
				'post_title'  => $title,
			],
			true
		);

		if ( is_wp_error( $product_id ) ) {
			return $product_id;
		}

		self::apply_product_meta( (int) $product_id, $type );
		self::track( 'products', (int) $product_id, [ 'type' => $type, 'title' => $title ] );

		return (int) $product_id;
	}

	/**
	 * @return int|\WP_Error
	 */
	public static function create_page( array $args = [] ) {
		self::assert_can_create();

		$title = isset( $args['title'] ) ? (string) $args['title'] : self::unique_title( 'PublishPress Cart Test Page' );

		$page_id = wp_insert_post(
			[
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_content' => isset( $args['content'] ) ? (string) $args['content'] : '',
			],
			true
		);

		if ( is_wp_error( $page_id ) ) {
			return $page_id;
		}

		self::track( 'pages', (int) $page_id, [ 'title' => $title ] );

		return (int) $page_id;
	}

	/**
	 * @return array<string, int|\WP_Error>
	 */
	public static function create_customer( array $args = [] ) {
		self::assert_can_create();

		$role     = isset( $args['role'] ) ? (string) $args['role'] : 'subscriber';
		$login    = isset( $args['login'] ) ? (string) $args['login'] : 'ppcart_customer_' . wp_generate_uuid4();
		$email    = isset( $args['email'] ) ? (string) $args['email'] : $login . '@example.invalid';
		$password = isset( $args['password'] ) ? (string) $args['password'] : wp_generate_password( 24, true, true );

		$user_id = wp_insert_user(
			[
				'user_login'   => $login,
				'user_email'   => $email,
				'user_pass'    => $password,
				'role'         => $role,
				'first_name'   => isset( $args['first_name'] ) ? (string) $args['first_name'] : '',
				'last_name'    => isset( $args['last_name'] ) ? (string) $args['last_name'] : '',
				'display_name' => isset( $args['display_name'] ) ? (string) $args['display_name'] : $login,
			]
		);

		if ( is_wp_error( $user_id ) ) {
			return [
				'user_id'  => $user_id,
				'login'    => $login,
				'email'    => $email,
				'password' => $password,
			];
		}

		self::track(
			'users',
			(int) $user_id,
			[
				'login' => $login,
				'email' => $email,
				'role'  => $role,
			]
		);

		return [
			'user_id'  => (int) $user_id,
			'login'    => $login,
			'email'    => $email,
			'password' => $password,
		];
	}

	/**
	 * @return int|\WP_Error
	 */
	public static function create_order( array $args = [] ) {
		self::assert_can_create();

		$user_id      = isset( $args['user_id'] ) ? (int) $args['user_id'] : 0;
		$product_id   = isset( $args['product_id'] ) ? (int) $args['product_id'] : 0;
		$product_name = isset( $args['product_name'] ) ? (string) $args['product_name'] : 'Test Product';

		if ( $product_id > 0 ) {
			$product = get_post( $product_id );

			if ( $product && ( function_exists( 'ppcart_is_product_post_type' ) ? ppcart_is_product_post_type( $product->post_type ) : 'ppcart_product' === $product->post_type ) ) {
				$product_name = $product->post_title;
			}
		}

		$status = isset( $args['status'] ) ? (string) $args['status'] : 'paid';

		$order_id = wp_insert_post(
			[
				'post_type'   => ppcart_live_post_type('order'),
				'post_status' => $status,
				'post_title'  => $product_name . ' Order',
				'post_date'   => self::post_date_from_args( $args ),
			],
			true
		);

		if ( is_wp_error( $order_id ) ) {
			return $order_id;
		}

		foreach ( self::order_meta_defaults( $user_id, $product_id, $product_name, $args ) as $key => $value ) {
			ppcart_fixtures_update_post_meta( $order_id, $key, $value );
		}

		ppcart_fixtures_update_post_meta( $order_id, '_ppcart_status', $status );
		self::track( 'orders', (int) $order_id, [ 'user_id' => $user_id, 'product_id' => $product_id, 'status' => $status ] );

		return (int) $order_id;
	}

	/**
	 * @return int|\WP_Error
	 */
	public static function create_subscription( array $args = [] ) {
		self::assert_can_create();

		$user_id       = isset( $args['user_id'] ) ? (int) $args['user_id'] : 0;
		$product_id    = isset( $args['product_id'] ) ? (int) $args['product_id'] : 0;
		$product_name  = isset( $args['product_name'] ) ? (string) $args['product_name'] : 'Test Product';
		$status       = isset( $args['status'] ) ? (string) $args['status'] : 'active';
		$installments = isset( $args['installments'] ) ? (string) $args['installments'] : '-1';

		if ( $product_id > 0 ) {
			$product = get_post( $product_id );

			if ( $product && ( function_exists( 'ppcart_is_product_post_type' ) ? ppcart_is_product_post_type( $product->post_type ) : 'ppcart_product' === $product->post_type ) ) {
				$product_name = $product->post_title;
			}
		}

		$subscription_id = wp_insert_post(
			[
				'post_type'   => ppcart_live_post_type('subscription'),
				'post_status' => $status,
				'post_title'  => $product_name . ' Subscription',
				'post_date'   => self::post_date_from_args( $args ),
			],
			true
		);

		if ( is_wp_error( $subscription_id ) ) {
			return $subscription_id;
		}

		foreach ( self::order_meta_defaults( $user_id, $product_id, $product_name, $args ) as $key => $value ) {
			ppcart_fixtures_update_post_meta( $subscription_id, $key, $value );
		}

		ppcart_fixtures_update_post_meta( $subscription_id, '_ppcart_status', $status );
		ppcart_fixtures_update_post_meta( $subscription_id, 'sub_status', $status );
		ppcart_fixtures_update_post_meta( $subscription_id, 'subscription_id', 'sub_fixture_' . $subscription_id );
		ppcart_fixtures_update_post_meta( $subscription_id, 'sub_installments', $installments );

		self::track(
			'subscriptions',
			(int) $subscription_id,
			[
				'user_id'      => $user_id,
				'product_id'   => $product_id,
				'installments' => $installments,
			]
		);

		return (int) $subscription_id;
	}

	/**
	 * Bundle used by checkout-block E2E and manual QA.
	 *
	 * @return array<string, mixed>
	 */
	public static function create_e2e_bundle( array $args = [] ) {
		self::assert_can_create();

		$create_admin = ! isset( $args['create_admin'] ) || (bool) $args['create_admin'];
		$result       = [];

		if ( $create_admin ) {
			$admin = self::create_customer(
				[
					'role'  => 'administrator',
					'login' => isset( $args['admin_login'] ) ? (string) $args['admin_login'] : 'ppcart_e2e_admin_' . time(),
				]
			);

			if ( is_wp_error( $admin['user_id'] ) ) {
				return [ 'error' => $admin['user_id'] ];
			}

			$result['admin'] = $admin;
		}

		$product_id = self::create_product(
			[
				'type'  => 'e2e',
				'title' => isset( $args['product_title'] ) ? (string) $args['product_title'] : self::unique_title( 'PublishPress Cart E2E Product' ),
			]
		);

		if ( is_wp_error( $product_id ) ) {
			return array_merge( $result, [ 'error' => $product_id ] );
		}

		$page_id = self::create_page(
			[
				'title' => isset( $args['page_title'] ) ? (string) $args['page_title'] : self::unique_title( 'PublishPress Cart E2E Checkout' ),
			]
		);

		if ( is_wp_error( $page_id ) ) {
			return array_merge( $result, [ 'error' => $page_id ] );
		}

		$result['product_id'] = (int) $product_id;
		$result['page_id']    = (int) $page_id;
		$result['page_url']   = get_permalink( $page_id );

		return $result;
	}

	/**
	 * Order + subscription pair for account block tests.
	 *
	 * @return array<string, int|\WP_Error>
	 */
	public static function create_account_bundle( array $args = [] ) {
		self::assert_can_create();

		$user_id = isset( $args['user_id'] ) ? (int) $args['user_id'] : 0;

		if ( $user_id <= 0 ) {
			$customer = self::create_customer( $args );
			$user_id  = $customer['user_id'];

			if ( is_wp_error( $user_id ) ) {
				return [ 'error' => $user_id ];
			}
		}

		$product_name = isset( $args['product_name'] ) ? (string) $args['product_name'] : 'Account Fixture Product';

		$order_id = self::create_order(
			[
				'user_id'      => $user_id,
				'product_name' => $product_name . ' Order',
			]
		);

		if ( is_wp_error( $order_id ) ) {
			return [ 'error' => $order_id ];
		}

		$subscription_id = self::create_subscription(
			[
				'user_id'      => $user_id,
				'product_name' => $product_name . ' Subscription',
				'installments' => isset( $args['installments'] ) ? (string) $args['installments'] : '-1',
			]
		);

		if ( is_wp_error( $subscription_id ) ) {
			return [ 'error' => $subscription_id ];
		}

		return [
			'user_id'         => $user_id,
			'order_id'        => $order_id,
			'subscription_id' => $subscription_id,
		];
	}

	/**
	 * Populate the site with a realistic catalog, customers, orders, and subscriptions.
	 *
	 * @return array<string, mixed>
	 */
	public static function seed_site( array $args = [] ) {
		self::assert_can_create();

		$products_count      = max( 1, (int) ( $args['products'] ?? 6 ) );
		$customers_count     = max( 1, (int) ( $args['customers'] ?? 12 ) );
		$orders_count        = max( 0, (int) ( $args['orders'] ?? 24 ) );
		$subscriptions_count = max( 0, (int) ( $args['subscriptions'] ?? 10 ) );
		$pages_count         = max( 0, (int) ( $args['pages'] ?? 3 ) );
		$seed_id             = isset( $args['seed_id'] ) ? (string) $args['seed_id'] : wp_generate_uuid4();

		$result = [
			'seed_id'       => $seed_id,
			'products'      => [],
			'customers'     => [],
			'orders'        => [],
			'subscriptions' => [],
			'pages'         => [],
		];

		$product_catalog = self::seed_product_catalog( $products_count );
		$recurring_ids   = [];

		foreach ( $product_catalog as $index => $product_def ) {
			$product_id = self::create_product(
				[
					'type'  => $product_def['type'],
					'title' => self::seed_title( $product_def['title'] ),
				]
			);

			if ( is_wp_error( $product_id ) ) {
				return array_merge( $result, [ 'error' => $product_id ] );
			}

			$result['products'][] = (int) $product_id;

			if ( 'recurring' === $product_def['type'] ) {
				$recurring_ids[] = (int) $product_id;
			}

			if ( $index < $pages_count ) {
				$page_id = self::create_page(
					[
						'title'   => self::seed_title( $product_def['title'] . ' Checkout' ),
						'content' => '<!-- wp:publishpress-cart/checkout-form {"productId":' . (int) $product_id . '} /-->',
					]
				);

				if ( is_wp_error( $page_id ) ) {
					return array_merge( $result, [ 'error' => $page_id ] );
				}

				$result['pages'][] = (int) $page_id;
			}
		}

		$customer_profiles = self::seed_customer_profiles( $customers_count );

		$customer_records = [];

		foreach ( $customer_profiles as $index => $profile ) {
			$customer = self::create_customer(
				[
					'login'        => sprintf( 'ppcart_seed_customer_%03d', $index + 1 ),
					'email'        => sprintf( 'ppcart-seed-customer-%03d@example.invalid', $index + 1 ),
					'first_name'   => $profile['first_name'],
					'last_name'    => $profile['last_name'],
					'display_name' => $profile['display_name'],
				]
			);

			if ( is_wp_error( $customer['user_id'] ) ) {
				return array_merge( $result, [ 'error' => $customer['user_id'] ] );
			}

			$customer_records[] = [
				'user_id' => (int) $customer['user_id'],
				'profile' => $profile,
			];

			$result['customers'][] = (int) $customer['user_id'];
		}

		if ( empty( $customer_records ) || empty( $result['products'] ) ) {
			return $result;
		}

		$order_statuses = [ 'paid', 'paid', 'paid', 'paid', 'completed', 'refunded', 'failed' ];

		for ( $i = 0; $i < $orders_count; $i++ ) {
			$customer   = $customer_records[ array_rand( $customer_records ) ];
			$user_id    = $customer['user_id'];
			$profile    = $customer['profile'];
			$product_id = (int) $result['products'][ array_rand( $result['products'] ) ];
			$amount  = self::seed_amount_for_index( $i );
			$status  = $order_statuses[ array_rand( $order_statuses ) ];
			$product = get_post( $product_id );

			$order_id = self::create_order(
				[
					'user_id'      => $user_id,
					'product_id'   => $product_id,
					'product_name' => $product ? $product->post_title : 'PublishPress Cart Seed Product',
					'status'       => $status,
					'amount'       => $amount,
					'created_at'   => self::seed_past_timestamp( $i, $orders_count ),
					'first_name'   => $profile['first_name'],
					'last_name'    => $profile['last_name'],
				]
			);

			if ( is_wp_error( $order_id ) ) {
				return array_merge( $result, [ 'error' => $order_id ] );
			}

			$result['orders'][] = (int) $order_id;
		}

		if ( empty( $recurring_ids ) ) {
			$recurring_ids = $result['products'];
		}

		$subscription_statuses = [ 'active', 'active', 'active', 'trialing', 'past_due', 'paused', 'canceled', 'completed' ];

		for ( $i = 0; $i < $subscriptions_count; $i++ ) {
			$customer   = $customer_records[ array_rand( $customer_records ) ];
			$user_id    = $customer['user_id'];
			$profile    = $customer['profile'];
			$product_id = (int) $recurring_ids[ array_rand( $recurring_ids ) ];
			$product    = get_post( $product_id );
			$status     = $subscription_statuses[ array_rand( $subscription_statuses ) ];
			$amount     = self::seed_amount_for_index( $i + 3 );

			$subscription_id = self::create_subscription(
				[
					'user_id'      => $user_id,
					'product_id'   => $product_id,
					'product_name' => $product ? $product->post_title : 'PublishPress Cart Seed Product',
					'status'       => $status,
					'amount'       => $amount,
					'installments' => ( 0 === $i % 4 ) ? '6' : '-1',
					'created_at'   => self::seed_past_timestamp( $i, $subscriptions_count, 180 ),
					'first_name'   => $profile['first_name'],
					'last_name'    => $profile['last_name'],
				]
			);

			if ( is_wp_error( $subscription_id ) ) {
				return array_merge( $result, [ 'error' => $subscription_id ] );
			}

			$result['subscriptions'][] = (int) $subscription_id;
		}

		$registry = self::get_registry();
		$registry['seed_batches']  = isset( $registry['seed_batches'] ) && is_array( $registry['seed_batches'] )
			? $registry['seed_batches']
			: [];
		$registry['seed_batches'][ $seed_id ] = [
			'created_at' => time(),
			'counts'     => [
				'products'      => count( $result['products'] ),
				'customers'     => count( $result['customers'] ),
				'orders'        => count( $result['orders'] ),
				'subscriptions' => count( $result['subscriptions'] ),
				'pages'         => count( $result['pages'] ),
			],
		];
		self::save_registry( $registry );

		return $result;
	}

	/**
	 * @return array<string, int>
	 */
	public static function cleanup( array $args = [] ) {
		$all      = ! empty( $args['all'] );
		$registry = self::get_registry();
		$removed  = [
			'products'      => 0,
			'pages'         => 0,
			'orders'        => 0,
			'subscriptions' => 0,
			'users'         => 0,
		];

		if ( ! $all && empty( $registry ) ) {
			return $removed;
		}

		require_once ABSPATH . 'wp-admin/includes/user.php';

		foreach ( [ 'subscriptions', 'orders', 'pages', 'products' ] as $type ) {
			$ids = $all ? self::find_tracked_post_ids( $type ) : array_keys( isset( $registry[ $type ] ) ? $registry[ $type ] : [] );

			foreach ( $ids as $id ) {
				$id = (int) $id;

				if ( $id <= 0 ) {
					continue;
				}

				if ( wp_delete_post( $id, true ) ) {
					++$removed[ $type ];
				}

				self::untrack( $type, $id );
			}
		}

		$user_ids = $all ? self::find_tracked_user_ids() : array_keys( isset( $registry['users'] ) ? $registry['users'] : [] );

		foreach ( $user_ids as $id ) {
			$id = (int) $id;

			if ( $id <= 0 || get_current_user_id() === $id ) {
				continue;
			}

			if ( wp_delete_user( $id, 1 ) ) {
				++$removed['users'];
			}

			self::untrack( 'users', $id );
		}

		if ( $all ) {
			delete_option( self::REGISTRY_OPTION );
		}

		return $removed;
	}

	/**
	 * Delete one entity created through this fixture plugin and remove it from
	 * the shared registry.
	 *
	 * @param string $type Registry entity type.
	 * @param int    $id   Entity ID.
	 */
	public static function delete_tracked_entity( $type, $id ) {
		$id = (int) $id;

		if ( $id <= 0 || ! in_array( $type, [ 'products', 'pages', 'orders', 'subscriptions', 'users' ], true ) ) {
			return false;
		}

		if ( 'users' === $type ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';

			if ( get_current_user_id() === $id ) {
				return false;
			}

			$deleted = (bool) wp_delete_user( $id, 1 );
		} else {
			$deleted = (bool) wp_delete_post( $id, true );
		}

		self::untrack( $type, $id );

		return $deleted;
	}

	public static function assert_can_create() {
		if ( ! self::is_enabled() ) {
			throw new RuntimeException( 'PublishPress Cart fixtures are disabled. Enable WP_DEBUG or define PPCART_FIXTURES_ENABLED as true.' );
		}

		if ( ! self::cart_is_available() ) {
			throw new RuntimeException( 'PublishPress Cart is not available. Activate the plugin before creating fixtures.' );
		}
	}

	private static function unique_title( $prefix ) {
		return trim( $prefix ) . ' ' . gmdate( 'Y-m-d H:i:s' );
	}

	private static function seed_title( $label ) {
		return 'PublishPress Cart Seed — ' . trim( $label );
	}

	/**
	 * @return array<int, array<string, string>>
	 */
	private static function seed_product_catalog( $count ) {
		$catalog = [
			[ 'title' => 'Quill — Editorial Calendar', 'type' => 'one-time' ],
			[ 'title' => 'Quill Club', 'type' => 'recurring' ],
			[ 'title' => 'Harbor Forms', 'type' => 'one-time' ],
			[ 'title' => 'Willow Support Desk', 'type' => 'recurring' ],
			[ 'title' => 'Atlas SEO Club', 'type' => 'recurring' ],
			[ 'title' => 'Harbor Forms Starter', 'type' => 'one-time' ],
			[ 'title' => 'Beacon Analytics', 'type' => 'recurring' ],
			[ 'title' => 'Atlas SEO', 'type' => 'one-time' ],
			[ 'title' => 'Compass Redirects', 'type' => 'one-time' ],
			[ 'title' => 'Ledger Invoices', 'type' => 'one-time' ],
			[ 'title' => 'Quill Lite', 'type' => 'one-time' ],
			[ 'title' => 'Signpost Legal Pages', 'type' => 'one-time' ],
		];

		if ( $count <= count( $catalog ) ) {
			return array_slice( $catalog, 0, $count );
		}

		$extras = [
			[ 'title' => 'Maple Comments', 'type' => 'one-time' ],
			[ 'title' => 'Pebble Breadcrumbs', 'type' => 'one-time' ],
			[ 'title' => 'Finch Social Proof', 'type' => 'one-time' ],
			[ 'title' => 'Oak Schema', 'type' => 'one-time' ],
			[ 'title' => 'Pine Cache', 'type' => 'recurring' ],
			[ 'title' => 'Moss SMTP', 'type' => 'one-time' ],
			[ 'title' => 'Cedar Backups', 'type' => 'recurring' ],
			[ 'title' => 'Drift Related Posts', 'type' => 'one-time' ],
			[ 'title' => 'River Table of Contents', 'type' => 'one-time' ],
			[ 'title' => 'Cinder Image SEO', 'type' => 'one-time' ],
			[ 'title' => 'Lark Popups', 'type' => 'one-time' ],
			[ 'title' => 'Heron Multilingual', 'type' => 'recurring' ],
			[ 'title' => 'Ibis Accessibility', 'type' => 'one-time' ],
			[ 'title' => 'Tern Media Library', 'type' => 'one-time' ],
			[ 'title' => 'Vole A/B Tests', 'type' => 'recurring' ],
			[ 'title' => 'Bramble Webhooks', 'type' => 'recurring' ],
		];
		$editions = [ '', ' Pro', ' Agency', ' Cloud', ' Studio' ];
		$expanded = $catalog;

		for ( $i = count( $catalog ); $i < $count; $i++ ) {
			$extra_i    = $i - count( $catalog );
			$base       = $extras[ $extra_i % count( $extras ) ];
			$edition    = (int) floor( $extra_i / count( $extras ) );
			$cycle      = (int) floor( $edition / count( $editions ) );
			$title      = $base['title'] . $editions[ $edition % count( $editions ) ];
			if ( $cycle > 0 ) {
				$title .= ' ' . ( $cycle + 1 );
			}
			$expanded[] = [
				'title' => $title,
				'type'  => $base['type'],
			];
		}

		return $expanded;
	}

	/**
	 * @return array<int, array<string, string>>
	 */
	private static function seed_customer_profiles( $count ) {
		$profiles = [
			[ 'first_name' => 'Alex', 'last_name' => 'Rivera', 'display_name' => 'Alex Rivera' ],
			[ 'first_name' => 'Jordan', 'last_name' => 'Kim', 'display_name' => 'Jordan Kim' ],
			[ 'first_name' => 'Taylor', 'last_name' => 'Brooks', 'display_name' => 'Taylor Brooks' ],
			[ 'first_name' => 'Casey', 'last_name' => 'Nguyen', 'display_name' => 'Casey Nguyen' ],
			[ 'first_name' => 'Riley', 'last_name' => 'Patel', 'display_name' => 'Riley Patel' ],
			[ 'first_name' => 'Morgan', 'last_name' => 'Chen', 'display_name' => 'Morgan Chen' ],
			[ 'first_name' => 'Avery', 'last_name' => 'Johnson', 'display_name' => 'Avery Johnson' ],
			[ 'first_name' => 'Quinn', 'last_name' => 'Martinez', 'display_name' => 'Quinn Martinez' ],
			[ 'first_name' => 'Jamie', 'last_name' => 'Wilson', 'display_name' => 'Jamie Wilson' ],
			[ 'first_name' => 'Drew', 'last_name' => 'Thompson', 'display_name' => 'Drew Thompson' ],
			[ 'first_name' => 'Skyler', 'last_name' => 'Lopez', 'display_name' => 'Skyler Lopez' ],
			[ 'first_name' => 'Reese', 'last_name' => 'Adams', 'display_name' => 'Reese Adams' ],
		];

		if ( $count <= count( $profiles ) ) {
			return array_slice( $profiles, 0, $count );
		}

		$expanded = $profiles;
		$first    = [ 'Alex', 'Jordan', 'Taylor', 'Casey', 'Riley', 'Morgan', 'Avery', 'Quinn', 'Jamie', 'Drew', 'Skyler', 'Reese', 'Cameron', 'Hayden', 'Rowan', 'Parker' ];
		$last     = [ 'Rivera', 'Kim', 'Brooks', 'Nguyen', 'Patel', 'Chen', 'Johnson', 'Martinez', 'Wilson', 'Thompson', 'Lopez', 'Adams', 'Singh', 'Garcia', 'Khan', 'Okafor' ];
		$used     = [];
		foreach ( $profiles as $profile ) {
			$used[ $profile['first_name'] . '|' . $profile['last_name'] ] = true;
		}

		$i   = 0;
		$cap = count( $first ) * count( $last );
		while ( count( $expanded ) < $count ) {
			if ( $i >= $cap ) {
				$expanded[] = [
					'first_name'   => 'Northstar',
					'last_name'    => 'Buyer ' . count( $expanded ),
					'display_name' => 'Northstar Buyer ' . count( $expanded ),
				];
				continue;
			}
			$first_name = $first[ $i % count( $first ) ];
			$last_name  = $last[ (int) floor( $i / count( $first ) ) % count( $last ) ];
			++$i;
			$pair = $first_name . '|' . $last_name;
			if ( isset( $used[ $pair ] ) ) {
				continue;
			}
			$used[ $pair ] = true;
			$expanded[]   = [
				'first_name'   => $first_name,
				'last_name'    => $last_name,
				'display_name' => $first_name . ' ' . $last_name,
			];
		}

		return $expanded;
	}

	private static function seed_amount_for_index( $index ) {
		$amounts = [ 19.0, 29.0, 49.0, 79.0, 99.0, 149.0, 199.0, 249.0, 299.0, 499.0 ];

		return $amounts[ $index % count( $amounts ) ];
	}

	private static function seed_past_timestamp( $index, $total, $days_back = 90 ) {
		if ( $total <= 1 ) {
			return time() - ( DAY_IN_SECONDS * wp_rand( 1, $days_back ) );
		}

		$slot = (int) floor( ( $index / max( 1, $total - 1 ) ) * $days_back );

		return time() - ( DAY_IN_SECONDS * max( 1, $days_back - $slot ) ) - wp_rand( 0, DAY_IN_SECONDS );
	}

	/**
	 * @param array<string, mixed> $args
	 */
	private static function post_date_from_args( array $args ) {
		if ( isset( $args['created_at'] ) ) {
			return gmdate( 'Y-m-d H:i:s', (int) $args['created_at'] );
		}

		return current_time( 'mysql' );
	}

	/**
	 * @return array<int, string>
	 */
	private static function find_tracked_post_ids( $type ) {
		$post_type_map = [
			'products'      => function_exists( 'ppcart_query_post_types' ) ? ppcart_query_post_types( 'product' ) : 'ppcart_product',
			'pages'         => 'page',
			'orders'        => function_exists( 'ppcart_query_post_types' ) ? ppcart_query_post_types( 'order' ) : 'ppcart_order',
			'subscriptions' => function_exists( 'ppcart_query_post_types' ) ? ppcart_query_post_types( 'subscription' ) : 'ppcart_subscription',
		];

		if ( ! isset( $post_type_map[ $type ] ) ) {
			return [];
		}

		$posts = get_posts(
			[
				'post_type'      => $post_type_map[ $type ],
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				's'              => 'PublishPress Cart',
			]
		);

		return array_map( 'intval', $posts );
	}

	/**
	 * @return array<int, int>
	 */
	private static function find_tracked_user_ids() {
		$users = get_users(
			[
				'search'         => '*ppcart_*',
				'search_columns' => [ 'user_login' ],
				'fields'         => 'ID',
			]
		);

		return array_map( 'intval', $users );
	}

	private static function apply_product_meta( $product_id, $type ) {
		$common = [
			'plan_heading'        => 'Payment Plan',
			'button_color'        => '#000000',
			'button_text'         => 'Order Now',
			'step1_button_label'  => 'Continue',
			'step1_button_icon_pos' => 'left',
			'checkout_ended_action'   => 'message',
			'checkout_ended_message' => 'Sorry, this product is no longer for sale.',
		];

		foreach ( $common as $key => $value ) {
			ppcart_fixtures_update_post_meta( $product_id, $key, $value );
		}

		if ( 'recurring' === $type ) {
			ppcart_fixtures_update_post_meta(
				$product_id,
				'_ppcart_pay_options',
				[
					[
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
					],
				]
			);

			return;
		}

		$pay_options = [
			[
				'option_id'         => 'fixture_plan',
				'option_name'       => 'Fixture Plan',
				'price'             => '100',
				'frequency'         => '1',
				'sale_frequency'    => '1',
				'interval'          => 'day',
				'sale_interval'     => 'day',
				'installments'      => '-1',
				'sale_installments' => '-1',
				'stripe_plan_id'    => 'fixture_plan',
			],
		];

		if ( 'e2e' === $type ) {
			$pay_options = [
				[
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
				],
				[
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
				],
			];

			ppcart_fixtures_update_post_meta( $product_id, 'enabled_gateways', '' );
			ppcart_fixtures_update_post_meta( $product_id, 'show_coupon_field', '1' );
		}

		ppcart_fixtures_update_post_meta( $product_id, '_ppcart_pay_options', $pay_options );
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function order_meta_defaults( $user_id, $product_id, $product_name, array $args = [] ) {
		$user       = $user_id > 0 ? get_userdata( $user_id ) : false;
		$amount     = isset( $args['amount'] ) ? (float) $args['amount'] : 25.0;
		$plan       = isset( $args['option_id'] ) ? (string) $args['option_id'] : 'fixture_plan';
		$first_name = isset( $args['first_name'] ) ? (string) $args['first_name'] : ( $user && $user->first_name ? $user->first_name : 'Fixture' );
		$last_name  = isset( $args['last_name'] ) ? (string) $args['last_name'] : ( $user && $user->last_name ? $user->last_name : 'Customer' );

		return [
			'product_id'         => $product_id,
			'product_name'       => $product_name,
			'item_name'          => 'Fixture Plan',
			'option_id'          => $plan,
			'plan'               => (object) [
				'name'      => 'Fixture Plan',
				'price'     => $amount,
				'type'      => 'recurring',
				'stripe_id' => $plan,
			],
			'amount'             => $amount,
			'main_offer_amt'     => $amount,
			'pre_tax_amount'     => $amount,
			'invoice_total'      => $amount,
			'invoice_subtotal'   => $amount,
			'sub_amount'         => $amount,
			'sub_item_name'      => 'Fixture Plan',
			'sub_interval'       => 'month',
			'sub_frequency'      => 1,
			'sub_next_bill_date' => strtotime( '+1 month' ),
			'first_name'         => $first_name,
			'last_name'          => $last_name,
			'customer_name'      => trim( $first_name . ' ' . $last_name ),
			'email'              => $user ? $user->user_email : 'fixture-customer@example.invalid',
			'user_account'       => $user_id,
			'pay_method'         => 'cod',
			'currency'           => 'USD',
			'quantity'           => 1,
		];
	}
}

final class PPCart_Fixtures_Admin {

	const PAGE_SLUG     = 'ppcart-fixtures';
	const NOTICE_PREFIX = 'ppcart_fixtures_notice_';

	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'register_menu' ] );
		add_action( 'admin_init', [ __CLASS__, 'handle_actions' ] );
		add_action( 'admin_notices', [ __CLASS__, 'render_notices' ] );
	}

	public static function register_menu() {
		add_management_page(
			__( 'PublishPress Cart Fixtures', 'ppcart-fixtures' ),
			__( 'PublishPress Cart Fixtures', 'ppcart-fixtures' ),
			'manage_options',
			self::PAGE_SLUG,
			[ __CLASS__, 'render_page' ]
		);
	}

	public static function handle_actions() {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( empty( $_POST['ppcart_fixtures_action'] ) ) {
			return;
		}

		check_admin_referer( 'ppcart_fixtures_action', 'ppcart_fixtures_nonce' );

		$action = sanitize_key( wp_unslash( $_POST['ppcart_fixtures_action'] ) );

		try {
			$result = self::dispatch_action( $action, $_POST );
			self::store_notice( 'success', $result['message'], isset( $result['details'] ) ? $result['details'] : '' );
		} catch ( RuntimeException $exception ) {
			self::store_notice( 'error', $exception->getMessage() );
		}

		wp_safe_redirect( self::page_url() );
		exit;
	}

	/**
	 * @param array<string, mixed> $post Raw POST payload.
	 *
	 * @return array<string, string>
	 */
	private static function dispatch_action( $action, array $post ) {
		switch ( $action ) {
			case 'e2e':
				$bundle = PPCart_Fixtures::create_e2e_bundle();

				if ( isset( $bundle['error'] ) && is_wp_error( $bundle['error'] ) ) {
					throw new RuntimeException( $bundle['error']->get_error_message() );
				}

				return [
					'message' => __( 'Created E2E fixture bundle.', 'ppcart-fixtures' ),
					'details' => self::format_details( $bundle ),
				];

			case 'seed':
				$result = PPCart_Fixtures::seed_site(
					[
						'products'      => self::post_int( $post, 'seed_products', 6 ),
						'customers'     => self::post_int( $post, 'seed_customers', 12 ),
						'orders'        => self::post_int( $post, 'seed_orders', 24 ),
						'subscriptions' => self::post_int( $post, 'seed_subscriptions', 10 ),
						'pages'         => self::post_int( $post, 'seed_pages', 3 ),
					]
				);

				if ( isset( $result['error'] ) && is_wp_error( $result['error'] ) ) {
					throw new RuntimeException( $result['error']->get_error_message() );
				}

				return [
					'message' => sprintf(
						/* translators: 1: products, 2: customers, 3: orders, 4: subscriptions, 5: pages */
						__( 'Seeded site with %1$d products, %2$d customers, %3$d orders, %4$d subscriptions, and %5$d pages.', 'ppcart-fixtures' ),
						count( $result['products'] ),
						count( $result['customers'] ),
						count( $result['orders'] ),
						count( $result['subscriptions'] ),
						count( $result['pages'] )
					),
					'details' => self::format_details( $result ),
				];

			case 'account':
				$args   = [];
				$user_id = self::post_int( $post, 'account_user_id', 0 );

				if ( $user_id > 0 ) {
					$args['user_id'] = $user_id;
				}

				$bundle = PPCart_Fixtures::create_account_bundle( $args );

				if ( isset( $bundle['error'] ) && is_wp_error( $bundle['error'] ) ) {
					throw new RuntimeException( $bundle['error']->get_error_message() );
				}

				return [
					'message' => __( 'Created account fixture bundle.', 'ppcart-fixtures' ),
					'details' => self::format_details( $bundle ),
				];

			case 'product':
				$product_id = PPCart_Fixtures::create_product(
					[
						'type'  => self::post_text( $post, 'product_type', 'e2e' ),
						'title' => self::optional_post_text( $post, 'product_title' ),
					]
				);

				if ( is_wp_error( $product_id ) ) {
					throw new RuntimeException( $product_id->get_error_message() );
				}

				return [
					'message' => sprintf(
						/* translators: %d: product ID */
						__( 'Created product %d.', 'ppcart-fixtures' ),
						$product_id
					),
				];

			case 'page':
				$page_id = PPCart_Fixtures::create_page(
					[
						'title' => self::optional_post_text( $post, 'page_title' ),
					]
				);

				if ( is_wp_error( $page_id ) ) {
					throw new RuntimeException( $page_id->get_error_message() );
				}

				return [
					'message' => sprintf(
						/* translators: 1: page ID, 2: page URL */
						__( 'Created page %1$d (%2$s).', 'ppcart-fixtures' ),
						$page_id,
						get_permalink( $page_id )
					),
				];

			case 'customer':
				$customer = PPCart_Fixtures::create_customer(
					[
						'role'  => self::post_text( $post, 'customer_role', 'subscriber' ),
						'login' => self::optional_post_text( $post, 'customer_login' ),
						'email' => self::optional_post_text( $post, 'customer_email' ),
					]
				);

				if ( is_wp_error( $customer['user_id'] ) ) {
					throw new RuntimeException( $customer['user_id']->get_error_message() );
				}

				return [
					'message' => sprintf(
						/* translators: 1: user ID, 2: login, 3: password */
						__( 'Created user %1$d (%2$s / %3$s).', 'ppcart-fixtures' ),
						$customer['user_id'],
						$customer['login'],
						$customer['password']
					),
				];

			case 'order':
				$args = [];

				if ( self::post_int( $post, 'order_user_id', 0 ) > 0 ) {
					$args['user_id'] = self::post_int( $post, 'order_user_id', 0 );
				}

				if ( self::post_int( $post, 'order_product_id', 0 ) > 0 ) {
					$args['product_id'] = self::post_int( $post, 'order_product_id', 0 );
				}

				$order_id = PPCart_Fixtures::create_order( $args );

				if ( is_wp_error( $order_id ) ) {
					throw new RuntimeException( $order_id->get_error_message() );
				}

				return [
					'message' => sprintf(
						/* translators: %d: order ID */
						__( 'Created order %d.', 'ppcart-fixtures' ),
						$order_id
					),
				];

			case 'subscription':
				$args = [];

				if ( self::post_int( $post, 'subscription_user_id', 0 ) > 0 ) {
					$args['user_id'] = self::post_int( $post, 'subscription_user_id', 0 );
				}

				if ( self::post_int( $post, 'subscription_product_id', 0 ) > 0 ) {
					$args['product_id'] = self::post_int( $post, 'subscription_product_id', 0 );
				}

				if ( isset( $post['subscription_installments'] ) && '' !== (string) $post['subscription_installments'] ) {
					$args['installments'] = self::post_text( $post, 'subscription_installments', '-1' );
				}

				$subscription_id = PPCart_Fixtures::create_subscription( $args );

				if ( is_wp_error( $subscription_id ) ) {
					throw new RuntimeException( $subscription_id->get_error_message() );
				}

				return [
					'message' => sprintf(
						/* translators: %d: subscription ID */
						__( 'Created subscription %d.', 'ppcart-fixtures' ),
						$subscription_id
					),
				];

			case 'cleanup':
				$removed = PPCart_Fixtures::cleanup();

				return [
					'message' => __( 'Removed tracked fixtures.', 'ppcart-fixtures' ),
					'details' => self::format_details( $removed ),
				];

			case 'cleanup_all':
				$removed = PPCart_Fixtures::cleanup( [ 'all' => true ] );

				return [
					'message' => __( 'Removed tracked fixtures and matching PublishPress Cart test content.', 'ppcart-fixtures' ),
					'details' => self::format_details( $removed ),
				];
		}

		throw new RuntimeException( __( 'Unknown fixture action.', 'ppcart-fixtures' ) );
	}

	public static function render_notices() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$notice = get_transient( self::NOTICE_PREFIX . get_current_user_id() );

		if ( ! is_array( $notice ) || empty( $notice['message'] ) ) {
			return;
		}

		delete_transient( self::NOTICE_PREFIX . get_current_user_id() );

		$class = ( isset( $notice['type'] ) && 'error' === $notice['type'] ) ? 'notice-error' : 'notice-success';

		echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible"><p>';
		echo esc_html( $notice['message'] );

		if ( ! empty( $notice['details'] ) ) {
			echo '</p><pre style="white-space:pre-wrap;margin:0.75em 0 0;padding:0.75em;background:#fff;border:1px solid #ccd0d4;">';
			echo esc_html( $notice['details'] );
			echo '</pre><p style="display:none;">';
		}

		echo '</p></div>';
	}

	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage fixtures.', 'ppcart-fixtures' ) );
		}

		$enabled  = PPCart_Fixtures::is_enabled();
		$cart_ok  = PPCart_Fixtures::cart_is_available();
		$counts   = PPCart_Fixtures::get_registry_counts();
		$registry = PPCart_Fixtures::get_registry();
		?>
		<div class="wrap ppcart-fixtures-wrap">
			<h1><?php esc_html_e( 'PublishPress Cart Test Fixtures', 'ppcart-fixtures' ); ?></h1>
			<p><?php esc_html_e( 'Create dummy PublishPress Cart data for local development and QA.', 'ppcart-fixtures' ); ?></p>

			<?php self::render_status_notice( $enabled, $cart_ok ); ?>

			<div class="metabox-holder ppcart-fixtures-grid">
				<div class="postbox ppcart-fixtures-panel">
					<div class="postbox-header">
						<h2 class="hndle"><span><?php esc_html_e( 'Quick bundles', 'ppcart-fixtures' ); ?></span></h2>
					</div>
					<div class="inside">
						<p class="description"><?php esc_html_e( 'One-click sets for common test scenarios.', 'ppcart-fixtures' ); ?></p>
						<div class="ppcart-fixtures-actions">
							<?php self::render_button_form( 'e2e', __( 'Create E2E bundle', 'ppcart-fixtures' ), 'button-primary' ); ?>
							<?php self::render_button_form( 'account', __( 'Create account bundle', 'ppcart-fixtures' ), 'button-secondary' ); ?>
						</div>
						<ul class="ppcart-fixtures-help">
							<li><?php esc_html_e( 'E2E bundle: admin user, checkout product, blank page.', 'ppcart-fixtures' ); ?></li>
							<li><?php esc_html_e( 'Account bundle: customer with one order and one subscription.', 'ppcart-fixtures' ); ?></li>
						</ul>
					</div>
				</div>

				<div class="postbox ppcart-fixtures-panel">
					<div class="postbox-header">
						<h2 class="hndle"><span><?php esc_html_e( 'Seed used site', 'ppcart-fixtures' ); ?></span></h2>
					</div>
					<div class="inside">
						<p class="description"><?php esc_html_e( 'Create a catalog, customers, order history, subscriptions, and checkout pages.', 'ppcart-fixtures' ); ?></p>
						<form method="post" action="" class="ppcart-fixtures-seed-form">
							<?php wp_nonce_field( 'ppcart_fixtures_action', 'ppcart_fixtures_nonce' ); ?>
							<input type="hidden" name="ppcart_fixtures_action" value="seed" />
							<table class="form-table ppcart-fixtures-form-table" role="presentation">
								<tr>
									<th scope="row"><label for="seed_products"><?php esc_html_e( 'Products', 'ppcart-fixtures' ); ?></label></th>
									<td><input name="seed_products" id="seed_products" type="number" min="1" value="6" class="small-text" /></td>
								</tr>
								<tr>
									<th scope="row"><label for="seed_customers"><?php esc_html_e( 'Customers', 'ppcart-fixtures' ); ?></label></th>
									<td><input name="seed_customers" id="seed_customers" type="number" min="1" value="12" class="small-text" /></td>
								</tr>
								<tr>
									<th scope="row"><label for="seed_orders"><?php esc_html_e( 'Orders', 'ppcart-fixtures' ); ?></label></th>
									<td><input name="seed_orders" id="seed_orders" type="number" min="0" value="24" class="small-text" /></td>
								</tr>
								<tr>
									<th scope="row"><label for="seed_subscriptions"><?php esc_html_e( 'Subscriptions', 'ppcart-fixtures' ); ?></label></th>
									<td><input name="seed_subscriptions" id="seed_subscriptions" type="number" min="0" value="10" class="small-text" /></td>
								</tr>
								<tr>
									<th scope="row"><label for="seed_pages"><?php esc_html_e( 'Checkout pages', 'ppcart-fixtures' ); ?></label></th>
									<td><input name="seed_pages" id="seed_pages" type="number" min="0" value="3" class="small-text" /></td>
								</tr>
							</table>
							<?php submit_button( __( 'Seed site', 'ppcart-fixtures' ), 'primary', 'submit', false ); ?>
						</form>
					</div>
				</div>

				<div class="postbox ppcart-fixtures-panel">
					<div class="postbox-header">
						<h2 class="hndle"><span><?php esc_html_e( 'Create one item', 'ppcart-fixtures' ); ?></span></h2>
					</div>
					<div class="inside ppcart-fixtures-single-forms">
						<form method="post" action="" class="ppcart-fixtures-item-form">
							<?php wp_nonce_field( 'ppcart_fixtures_action', 'ppcart_fixtures_nonce' ); ?>
							<input type="hidden" name="ppcart_fixtures_action" value="product" />
							<div class="ppcart-fixtures-fields">
								<p>
									<label for="product_type"><?php esc_html_e( 'Product type', 'ppcart-fixtures' ); ?></label>
									<select name="product_type" id="product_type">
										<option value="e2e">e2e</option>
										<option value="recurring">recurring</option>
										<option value="one-time">one-time</option>
									</select>
								</p>
								<p>
									<label for="product_title"><?php esc_html_e( 'Title', 'ppcart-fixtures' ); ?></label>
									<input type="text" name="product_title" id="product_title" placeholder="<? esc_attr_e( 'Optional', 'ppcart-fixtures' ); ?>" class="regular-text" />
								</p>
							</div>
							<?php submit_button( __( 'Create product', 'ppcart-fixtures' ), 'secondary', 'submit', false ); ?>
						</form>

						<form method="post" action="" class="ppcart-fixtures-item-form">
							<?php wp_nonce_field( 'ppcart_fixtures_action', 'ppcart_fixtures_nonce' ); ?>
							<input type="hidden" name="ppcart_fixtures_action" value="customer" />
							<div class="ppcart-fixtures-fields">
								<p>
									<label for="customer_role"><?php esc_html_e( 'Role', 'ppcart-fixtures' ); ?></label>
									<select name="customer_role" id="customer_role">
										<option value="subscriber"><?php esc_html_e( 'Subscriber', 'ppcart-fixtures' ); ?></option>
										<option value="administrator"><?php esc_html_e( 'Administrator', 'ppcart-fixtures' ); ?></option>
									</select>
								</p>
								<p>
									<label for="customer_login"><?php esc_html_e( 'Login', 'ppcart-fixtures' ); ?></label>
									<input type="text" name="customer_login" id="customer_login" placeholder="<? esc_attr_e( 'Optional', 'ppcart-fixtures' ); ?>" class="regular-text" />
								</p>
							</div>
							<?php submit_button( __( 'Create customer', 'ppcart-fixtures' ), 'secondary', 'submit', false ); ?>
						</form>

						<form method="post" action="" class="ppcart-fixtures-item-form">
							<?php wp_nonce_field( 'ppcart_fixtures_action', 'ppcart_fixtures_nonce' ); ?>
							<input type="hidden" name="ppcart_fixtures_action" value="order" />
							<div class="ppcart-fixtures-fields ppcart-fixtures-fields-inline">
								<p>
									<label for="order_user_id"><?php esc_html_e( 'User ID', 'ppcart-fixtures' ); ?></label>
									<input type="number" name="order_user_id" id="order_user_id" min="0" placeholder="<? esc_attr_e( 'Optional', 'ppcart-fixtures' ); ?>" class="small-text" />
								</p>
								<p>
									<label for="order_product_id"><?php esc_html_e( 'Product ID', 'ppcart-fixtures' ); ?></label>
									<input type="number" name="order_product_id" id="order_product_id" min="0" placeholder="<? esc_attr_e( 'Optional', 'ppcart-fixtures' ); ?>" class="small-text" />
								</p>
							</div>
							<?php submit_button( __( 'Create order', 'ppcart-fixtures' ), 'secondary', 'submit', false ); ?>
						</form>

						<form method="post" action="" class="ppcart-fixtures-item-form">
							<?php wp_nonce_field( 'ppcart_fixtures_action', 'ppcart_fixtures_nonce' ); ?>
							<input type="hidden" name="ppcart_fixtures_action" value="subscription" />
							<div class="ppcart-fixtures-fields ppcart-fixtures-fields-inline">
								<p>
									<label for="subscription_user_id"><?php esc_html_e( 'User ID', 'ppcart-fixtures' ); ?></label>
									<input type="number" name="subscription_user_id" id="subscription_user_id" min="0" placeholder="<? esc_attr_e( 'Optional', 'ppcart-fixtures' ); ?>" class="small-text" />
								</p>
								<p>
									<label for="subscription_product_id"><?php esc_html_e( 'Product ID', 'ppcart-fixtures' ); ?></label>
									<input type="number" name="subscription_product_id" id="subscription_product_id" min="0" placeholder="<? esc_attr_e( 'Optional', 'ppcart-fixtures' ); ?>" class="small-text" />
								</p>
							</div>
							<?php submit_button( __( 'Create subscription', 'ppcart-fixtures' ), 'secondary', 'submit', false ); ?>
						</form>

						<form method="post" action="" class="ppcart-fixtures-item-form">
							<?php wp_nonce_field( 'ppcart_fixtures_action', 'ppcart_fixtures_nonce' ); ?>
							<input type="hidden" name="ppcart_fixtures_action" value="page" />
							<div class="ppcart-fixtures-fields">
								<p>
									<label for="page_title"><?php esc_html_e( 'Title', 'ppcart-fixtures' ); ?></label>
									<input type="text" name="page_title" id="page_title" placeholder="<? esc_attr_e( 'Optional', 'ppcart-fixtures' ); ?>" class="regular-text" />
								</p>
							</div>
							<?php submit_button( __( 'Create page', 'ppcart-fixtures' ), 'secondary', 'submit', false ); ?>
						</form>
					</div>
				</div>

				<div class="postbox ppcart-fixtures-panel ppcart-fixtures-panel-wide">
					<div class="postbox-header">
						<h2 class="hndle"><span><?php esc_html_e( 'Registry', 'ppcart-fixtures' ); ?></span></h2>
					</div>
					<div class="inside">
						<ul class="ppcart-fixtures-counts">
							<li><?php echo esc_html( sprintf( __( 'Products: %d', 'ppcart-fixtures' ), $counts['products'] ) ); ?></li>
							<li><?php echo esc_html( sprintf( __( 'Pages: %d', 'ppcart-fixtures' ), $counts['pages'] ) ); ?></li>
							<li><?php echo esc_html( sprintf( __( 'Customers: %d', 'ppcart-fixtures' ), $counts['users'] ) ); ?></li>
							<li><?php echo esc_html( sprintf( __( 'Orders: %d', 'ppcart-fixtures' ), $counts['orders'] ) ); ?></li>
							<li><?php echo esc_html( sprintf( __( 'Subscriptions: %d', 'ppcart-fixtures' ), $counts['subscriptions'] ) ); ?></li>
						</ul>
						<details class="ppcart-fixtures-details">
							<summary><?php esc_html_e( 'View raw registry JSON', 'ppcart-fixtures' ); ?></summary>
							<pre class="ppcart-fixtures-registry"><?php echo esc_html( wp_json_encode( $registry, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); ?></pre>
						</details>
						<div class="ppcart-fixtures-actions">
							<?php self::render_button_form( 'cleanup', __( 'Cleanup tracked fixtures', 'ppcart-fixtures' ), 'delete' ); ?>
							<?php self::render_button_form( 'cleanup_all', __( 'Cleanup all PublishPress Cart fixtures', 'ppcart-fixtures' ), 'delete', __( 'Remove tracked fixtures and any PublishPress Cart-titled posts or ppcart_* users?', 'ppcart-fixtures' ) ); ?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<style>
			.ppcart-fixtures-wrap .ppcart-fixtures-grid {
				display: grid;
				grid-template-columns: repeat(3, minmax(0, 1fr));
				gap: 20px;
				margin-top: 20px;
			}
			.ppcart-fixtures-wrap .ppcart-fixtures-panel {
				margin: 0;
			}
			.ppcart-fixtures-wrap .ppcart-fixtures-panel-wide {
				grid-column: 1 / -1;
			}
			.ppcart-fixtures-wrap .postbox-header {
				border-bottom: 1px solid #dcdcde;
			}
			.ppcart-fixtures-wrap .postbox .hndle {
				padding: 12px 16px;
				margin: 0;
				font-size: 14px;
				font-weight: 600;
				line-height: 1.4;
				cursor: default;
			}
			.ppcart-fixtures-wrap .postbox .inside {
				margin: 0;
				padding: 16px 20px 20px;
			}
			.ppcart-fixtures-wrap .postbox .inside > :first-child {
				margin-top: 0;
			}
			.ppcart-fixtures-wrap .postbox .inside > :last-child {
				margin-bottom: 0;
			}
			.ppcart-fixtures-wrap .description {
				margin: 0 0 16px;
			}
			.ppcart-fixtures-actions {
				display: flex;
				flex-wrap: wrap;
				gap: 8px;
				margin: 0;
			}
			.ppcart-fixtures-actions form,
			.ppcart-fixtures-action-form {
				margin: 0;
			}
			.ppcart-fixtures-form-table th,
			.ppcart-fixtures-form-table td {
				padding: 10px 0;
			}
			.ppcart-fixtures-form-table th {
				padding-right: 16px;
				width: 140px;
			}
			.ppcart-fixtures-seed-form .submit {
				margin-top: 4px;
				padding-left: 0;
			}
			.ppcart-fixtures-help {
				margin: 16px 0 0;
				padding-left: 18px;
				color: #646970;
			}
			.ppcart-fixtures-item-form {
				margin: 0 0 16px;
				padding-bottom: 16px;
				border-bottom: 1px solid #dcdcde;
			}
			.ppcart-fixtures-item-form:last-child {
				margin-bottom: 0;
				padding-bottom: 0;
				border-bottom: 0;
			}
			.ppcart-fixtures-fields {
				margin-bottom: 12px;
			}
			.ppcart-fixtures-fields p {
				margin: 0 0 10px;
			}
			.ppcart-fixtures-fields p:last-child {
				margin-bottom: 0;
			}
			.ppcart-fixtures-fields label {
				display: block;
				margin-bottom: 4px;
				font-weight: 600;
			}
			.ppcart-fixtures-fields-inline {
				display: grid;
				grid-template-columns: repeat(2, minmax(0, 1fr));
				gap: 12px;
			}
			.ppcart-fixtures-counts {
				display: flex;
				flex-wrap: wrap;
				gap: 12px 24px;
				margin: 0 0 16px;
				padding: 0;
				list-style: none;
			}
			.ppcart-fixtures-details {
				margin-bottom: 16px;
			}
			.ppcart-fixtures-details summary {
				cursor: pointer;
				margin-bottom: 8px;
			}
			.ppcart-fixtures-registry {
				max-height: 280px;
				overflow: auto;
				margin: 0;
				padding: 12px 16px;
				background: #fff;
				border: 1px solid #dcdcde;
				border-radius: 4px;
			}
			@media (max-width: 1200px) {
				.ppcart-fixtures-wrap .ppcart-fixtures-grid {
					grid-template-columns: repeat(2, minmax(0, 1fr));
				}
			}
			@media (max-width: 782px) {
				.ppcart-fixtures-wrap .ppcart-fixtures-grid {
					grid-template-columns: 1fr;
				}
				.ppcart-fixtures-fields-inline {
					grid-template-columns: 1fr;
				}
			}
		</style>
		<?php
	}

	private static function render_status_notice( $enabled, $cart_ok ) {
		if ( $enabled && $cart_ok ) {
			echo '<div class="notice notice-info"><p>';
			esc_html_e( 'Fixtures are enabled and PublishPress Cart is available.', 'ppcart-fixtures' );
			echo '</p></div>';
			return;
		}

		echo '<div class="notice notice-warning"><p>';

		if ( ! $enabled ) {
			esc_html_e( 'Fixture creation is disabled. Enable WP_DEBUG or define PPCART_FIXTURES_ENABLED as true in wp-config.php.', 'ppcart-fixtures' );
			echo ' ';
		}

		if ( ! $cart_ok ) {
			esc_html_e( 'PublishPress Cart is not active.', 'ppcart-fixtures' );
		}

		echo '</p></div>';
	}

	private static function render_button_form( $action, $label, $class = 'button-secondary', $confirm = '' ) {
		$onclick = $confirm ? ' onclick="return confirm(' . wp_json_encode( $confirm ) . ');"' : '';
		?>
		<form method="post" action="" class="ppcart-fixtures-action-form">
			<?php wp_nonce_field( 'ppcart_fixtures_action', 'ppcart_fixtures_nonce' ); ?>
			<input type="hidden" name="ppcart_fixtures_action" value="<?php echo esc_attr( $action ); ?>" />
			<button type="submit" class="button <?php echo esc_attr( $class ); ?>"<?php echo $onclick; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON-encoded confirm. ?>>
				<?php echo esc_html( $label ); ?>
			</button>
		</form>
		<?php
	}

	private static function page_url() {
		return admin_url( 'tools.php?page=' . self::PAGE_SLUG );
	}

	private static function store_notice( $type, $message, $details = '' ) {
		set_transient(
			self::NOTICE_PREFIX . get_current_user_id(),
			[
				'type'    => $type,
				'message' => $message,
				'details' => $details,
			],
			MINUTE_IN_SECONDS
		);
	}

	/**
	 * @param mixed $data
	 */
	private static function format_details( $data ) {
		return wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	}

	/**
	 * @param array<string, mixed> $post
	 */
	private static function post_int( array $post, $key, $default ) {
		if ( ! isset( $post[ $key ] ) || '' === (string) $post[ $key ] ) {
			return (int) $default;
		}

		return max( 0, (int) $post[ $key ] );
	}

	/**
	 * @param array<string, mixed> $post
	 */
	private static function post_text( array $post, $key, $default ) {
		if ( ! isset( $post[ $key ] ) ) {
			return $default;
		}

		return sanitize_text_field( wp_unslash( $post[ $key ] ) );
	}

	/**
	 * @param array<string, mixed> $post
	 *
	 * @return string|null
	 */
	private static function optional_post_text( array $post, $key ) {
		if ( ! isset( $post[ $key ] ) ) {
			return null;
		}

		$value = sanitize_text_field( wp_unslash( $post[ $key ] ) );

		return '' === $value ? null : $value;
	}
}

PPCart_Fixtures_Admin::init();

// Fixtures-only notification trigger endpoint. Registered here so it exists
// only while this fixtures plugin is loaded, never on a normal site boot.
PPCart_Admin_Fixtures::init();
