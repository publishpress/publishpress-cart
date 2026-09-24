<?php

declare(strict_types=1);

/**
 * Read-only fixture records used by the Playwright admin suite.
 *
 * Also exposes the fixtures-only notification trigger endpoint used by the
 * email settings gate specs (`tests/playwright/admin/email-*.spec.ts`). The
 * endpoint is registered from `ppcart-fixtures.php` only, so it never exists on
 * a normal site boot, and every request needs `manage_options` plus a nonce.
 */
final class PPCart_Admin_Fixtures {

	const REGISTRY_OPTION = 'ppcart_admin_fixtures_registry';

	/** AJAX action that hands an authenticated admin a trigger nonce. */
	const NONCE_AJAX_ACTION = 'ppcart_admin_fixtures_nonce';

	/** AJAX action that fires a notification trigger. */
	const TRIGGER_AJAX_ACTION = 'ppcart_admin_fixtures_trigger';

	/** Nonce action guarding `TRIGGER_AJAX_ACTION`. */
	const TRIGGER_NONCE_ACTION = 'ppcart_admin_fixtures_trigger';

	/**
	 * Statuses the trigger endpoint accepts, mirroring the switch in
	 * `ppcart_trigger_integrations()`.
	 *
	 * @var list<string>
	 */
	const ALLOWED_STATUSES = [
		'pending',
		'paid',
		'refunded',
		'renewal',
		'failed',
		'uncollectible',
		'completed',
		'active',
		'trialing',
		'paused',
		'canceled',
		'past_due',
	];

	/**
	 * Register the fixtures-only trigger endpoint.
	 *
	 * Called from `ppcart-fixtures.php`, never from the plugin itself.
	 */
	public static function init(): void {
		add_action( 'wp_ajax_' . self::NONCE_AJAX_ACTION, [ __CLASS__, 'handle_nonce_request' ] );
		add_action( 'wp_ajax_' . self::TRIGGER_AJAX_ACTION, [ __CLASS__, 'handle_trigger_request' ] );
	}

	/**
	 * @return array<string, int|string>
	 */
	public static function setup(): array {
		PPCart_Fixtures::assert_can_create();
		self::cleanup();

		$registry = [];
		update_option( self::REGISTRY_OPTION, $registry, false );

		$product_id = PPCart_Fixtures::create_product(
			[
				'type'  => 'recurring',
				'title' => 'PublishPress Cart Playwright Admin Fixture Product',
			]
		);

		if ( is_wp_error( $product_id ) ) {
			throw new RuntimeException( $product_id->get_error_message() );
		}

		$registry['product_id'] = (int) $product_id;
		update_option( self::REGISTRY_OPTION, $registry, false );

		$customer = PPCart_Fixtures::create_customer(
			[
				'login'        => 'ppcart_playwright_admin_customer',
				'email'        => 'ppcart-playwright-admin@example.invalid',
				'first_name'   => 'Playwright',
				'last_name'    => 'Customer',
				'display_name' => 'Playwright Customer',
			]
		);

		if ( is_wp_error( $customer['user_id'] ) ) {
			throw new RuntimeException( $customer['user_id']->get_error_message() );
		}

		$registry['user_id'] = (int) $customer['user_id'];
		update_option( self::REGISTRY_OPTION, $registry, false );

		$order_id = PPCart_Fixtures::create_order(
			[
				'user_id'    => (int) $customer['user_id'],
				'product_id' => (int) $product_id,
				'status'     => 'paid',
			]
		);

		if ( is_wp_error( $order_id ) ) {
			throw new RuntimeException( $order_id->get_error_message() );
		}

		$registry['order_id'] = (int) $order_id;
		update_option( self::REGISTRY_OPTION, $registry, false );

		$subscription_id = PPCart_Fixtures::create_subscription(
			[
				'user_id'      => (int) $customer['user_id'],
				'product_id'   => (int) $product_id,
				'status'       => 'active',
				'installments' => '-1',
			]
		);

		if ( is_wp_error( $subscription_id ) ) {
			throw new RuntimeException( $subscription_id->get_error_message() );
		}

		$registry['subscription_id'] = (int) $subscription_id;
		$registry['customer_email']  = (string) $customer['email'];
		update_option( self::REGISTRY_OPTION, $registry, false );

		$email_bundle = self::setup_email_bundle( $registry );

		return array_merge(
			self::live_screen_slugs(),
			[
				'admin_product_id'      => (int) $product_id,
				'admin_order_id'        => (int) $order_id,
				'admin_subscription_id' => (int) $subscription_id,
				'admin_customer_email'  => (string) $customer['email'],
			],
			$email_bundle
		);
	}

	/**
	 * One-time order plus subscription owned by a customer at a run-unique
	 * `@example.invalid` address, used by the email settings gate specs.
	 *
	 * The product deliberately leaves the per-product `disable_{type}_email`
	 * meta unset, otherwise `PPCart_Public::do_after_integration_functions()`
	 * would suppress the mail for the wrong reason.
	 *
	 * @param array<string, int|string> $registry Registry, updated in place.
	 *
	 * @return array<string, int|string>
	 */
	private static function setup_email_bundle( array &$registry ): array {
		$token = self::unique_token();

		$product_id = PPCart_Fixtures::create_product(
			[
				'type'  => 'one-time',
				'title' => 'PublishPress Cart Playwright Email Fixture Product',
			]
		);

		if ( is_wp_error( $product_id ) ) {
			throw new RuntimeException( $product_id->get_error_message() );
		}

		$registry['email_product_id'] = (int) $product_id;
		update_option( self::REGISTRY_OPTION, $registry, false );

		$customer = PPCart_Fixtures::create_customer(
			[
				'login'        => 'ppcart_playwright_email_customer_' . $token,
				'email'        => 'ppcart-email-' . $token . '@example.invalid',
				'first_name'   => 'Playwright',
				'last_name'    => 'Email',
				'display_name' => 'Playwright Email Customer',
			]
		);

		if ( is_wp_error( $customer['user_id'] ) ) {
			throw new RuntimeException( $customer['user_id']->get_error_message() );
		}

		$registry['email_user_id'] = (int) $customer['user_id'];
		update_option( self::REGISTRY_OPTION, $registry, false );

		$order_id = PPCart_Fixtures::create_order(
			[
				'user_id'    => (int) $customer['user_id'],
				'product_id' => (int) $product_id,
				'status'     => 'paid',
			]
		);

		if ( is_wp_error( $order_id ) ) {
			throw new RuntimeException( $order_id->get_error_message() );
		}

		$registry['email_order_id'] = (int) $order_id;
		update_option( self::REGISTRY_OPTION, $registry, false );

		$subscription_id = PPCart_Fixtures::create_subscription(
			[
				'user_id'      => (int) $customer['user_id'],
				'product_id'   => (int) $product_id,
				'status'       => 'active',
				'installments' => '-1',
			]
		);

		if ( is_wp_error( $subscription_id ) ) {
			throw new RuntimeException( $subscription_id->get_error_message() );
		}

		$registry['email_subscription_id'] = (int) $subscription_id;
		$registry['email_customer_email']  = (string) $customer['email'];
		update_option( self::REGISTRY_OPTION, $registry, false );

		return [
			'admin_email_product_id'      => (int) $product_id,
			'admin_email_order_id'        => (int) $order_id,
			'admin_email_subscription_id' => (int) $subscription_id,
			'admin_email_customer_email'  => (string) $customer['email'],
		];
	}

	/**
	 * Live CPT/taxonomy slugs for Playwright admin URLs and body classes.
	 *
	 * @return array<string, string>
	 */
	public static function live_screen_slugs(): array {
		$post_type = static function ( string $family, string $fallback ): string {
			return function_exists( 'ppcart_live_post_type' ) ? (string) ppcart_live_post_type( $family ) : $fallback;
		};
		$taxonomy  = static function ( string $family, string $fallback ): string {
			return function_exists( 'ppcart_live_taxonomy' ) ? (string) ppcart_live_taxonomy( $family ) : $fallback;
		};

		return [
			'admin_product_post_type'      => $post_type( 'product', 'ppcart_product' ),
			'admin_order_post_type'        => $post_type( 'order', 'ppcart_order' ),
			'admin_subscription_post_type' => $post_type( 'subscription', 'ppcart_subscription' ),
			'admin_product_cat_taxonomy'   => $taxonomy( 'product_cat', 'ppcart_product_cat' ),
			'admin_product_tag_taxonomy'   => $taxonomy( 'product_tag', 'ppcart_product_tag' ),
		];
	}

	/**
	 * @return array<string, int>
	 */
	public static function cleanup(): array {
		$registry = get_option( self::REGISTRY_OPTION, [] );
		$removed  = [
			'products'      => 0,
			'orders'        => 0,
			'subscriptions' => 0,
			'users'         => 0,
		];

		if ( ! is_array( $registry ) ) {
			delete_option( self::REGISTRY_OPTION );
			return $removed;
		}

		$entities = [
			[ 'subscriptions', (int) ( $registry['subscription_id'] ?? 0 ) ],
			[ 'subscriptions', (int) ( $registry['email_subscription_id'] ?? 0 ) ],
			[ 'orders', (int) ( $registry['order_id'] ?? 0 ) ],
			[ 'orders', (int) ( $registry['email_order_id'] ?? 0 ) ],
			[ 'products', (int) ( $registry['product_id'] ?? 0 ) ],
			[ 'products', (int) ( $registry['email_product_id'] ?? 0 ) ],
			[ 'users', (int) ( $registry['user_id'] ?? 0 ) ],
			[ 'users', (int) ( $registry['email_user_id'] ?? 0 ) ],
		];

		foreach ( $entities as $entity ) {
			list( $type, $id ) = $entity;

			if ( $id > 0 && PPCart_Fixtures::delete_tracked_entity( $type, $id ) ) {
				++$removed[ $type ];
			}
		}

		// Users created through `create_user_for_order()` are owned by the cart,
		// not the fixtures registry, so they are removed by id here.
		$created_users = isset( $registry['created_user_ids'] ) && is_array( $registry['created_user_ids'] )
			? $registry['created_user_ids']
			: [];

		foreach ( $created_users as $user_id ) {
			$user_id = (int) $user_id;

			if ( $user_id > 0 && get_userdata( $user_id ) && wp_delete_user( $user_id ) ) {
				++$removed['users'];
			}
		}

		delete_option( self::REGISTRY_OPTION );

		return $removed;
	}

	/**
	 * Fire `ppcart_trigger_integrations()` for an order, exactly as a real
	 * status change would.
	 *
	 * @param string $email Optional recipient override written to the order's
	 *                      `email` meta first, so parallel workers can assert on
	 *                      a unique address.
	 *
	 * @return array<string, mixed>
	 */
	public static function trigger_order_status( int $order_id, string $status, string $email = '' ): array {
		PPCart_Fixtures::assert_can_create();
		self::assert_valid_status( $status );
		self::assert_entity( $order_id, 'order' );

		$email = self::maybe_set_recipient( $order_id, $email );

		ppcart_trigger_integrations( $status, $order_id );

		return [
			'ok'        => true,
			'triggered' => 'order_status',
			'status'    => $status,
			'order_id'  => $order_id,
			'email'     => $email,
		];
	}

	/**
	 * Fire `ppcart_trigger_integrations()` for a subscription.
	 *
	 * @param string $email Optional recipient override, see
	 *                      `trigger_order_status()`.
	 *
	 * @return array<string, mixed>
	 */
	public static function trigger_subscription_status( int $subscription_id, string $status, string $email = '' ): array {
		PPCart_Fixtures::assert_can_create();
		self::assert_valid_status( $status );
		self::assert_entity( $subscription_id, 'subscription' );

		$email = self::maybe_set_recipient( $subscription_id, $email );

		ppcart_trigger_integrations( $status, $subscription_id );

		return [
			'ok'              => true,
			'triggered'       => 'subscription_status',
			'status'          => $status,
			'subscription_id' => $subscription_id,
			'email'           => $email,
		];
	}

	/**
	 * Run `ppcart_create_user()` for an order, which is what sends the New User
	 * Welcome email. The registration gate (`_ppcart_email_registration_enable`)
	 * is left to the plugin, since that gate is what the specs assert on.
	 *
	 * @return array<string, mixed>
	 */
	public static function create_user_for_order( int $order_id, string $email, string $first_name = 'Playwright', string $last_name = 'Registration', string $role = '' ): array {
		PPCart_Fixtures::assert_can_create();
		self::assert_entity( $order_id, 'order' );

		$email = sanitize_email( $email );

		if ( '' === $email || ! is_email( $email ) ) {
			throw new RuntimeException( 'A valid email address is required to create a user for an order.' );
		}

		if ( '' === $role ) {
			$role = (string) get_option( 'default_role', 'subscriber' );
		}

		$existing = email_exists( $email );
		$user_id  = ppcart_create_user( $order_id, $email, $first_name, $last_name, $role );

		if ( is_wp_error( $user_id ) ) {
			throw new RuntimeException( $user_id->get_error_message() );
		}

		$user_id = (int) $user_id;

		if ( ! $existing && $user_id > 0 ) {
			self::remember_created_user( $user_id );
		}

		return [
			'ok'        => true,
			'triggered' => 'create_user',
			'status'    => $existing ? 'existing' : 'created',
			'order_id'  => $order_id,
			'user_id'   => $user_id,
			'email'     => $email,
		];
	}

	/**
	 * Hands an authenticated admin the nonce for the trigger endpoint.
	 */
	public static function handle_nonce_request(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			self::send_error( 'The fixtures trigger endpoint requires manage_options.', 403 );
		}

		try {
			PPCart_Fixtures::assert_can_create();
		} catch ( RuntimeException $exception ) {
			self::send_error( $exception->getMessage(), 403 );
		}

		wp_send_json(
			[
				'ok'     => true,
				'action' => self::TRIGGER_AJAX_ACTION,
				'nonce'  => wp_create_nonce( self::TRIGGER_NONCE_ACTION ),
			]
		);
	}

	/**
	 * Dispatches one trigger. Responds with `{ ok, triggered, status }`.
	 */
	public static function handle_trigger_request(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			self::send_error( 'The fixtures trigger endpoint requires manage_options.', 403 );
		}

		if ( ! check_ajax_referer( self::TRIGGER_NONCE_ACTION, 'nonce', false ) ) {
			self::send_error( 'Invalid or missing fixtures trigger nonce.', 403 );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified above.
		$post    = wp_unslash( $_POST );
		$trigger = isset( $post['trigger'] ) ? sanitize_key( (string) $post['trigger'] ) : '';
		$status  = isset( $post['status'] ) ? sanitize_key( (string) $post['status'] ) : '';
		$email   = isset( $post['email'] ) ? sanitize_email( (string) $post['email'] ) : '';

		try {
			switch ( $trigger ) {
				case 'order_status':
					$result = self::trigger_order_status(
						isset( $post['order_id'] ) ? absint( $post['order_id'] ) : 0,
						$status,
						$email
					);
					break;

				case 'subscription_status':
					$result = self::trigger_subscription_status(
						isset( $post['subscription_id'] ) ? absint( $post['subscription_id'] ) : 0,
						$status,
						$email
					);
					break;

				case 'create_user':
					$result = self::create_user_for_order(
						isset( $post['order_id'] ) ? absint( $post['order_id'] ) : 0,
						$email,
						isset( $post['first_name'] ) ? sanitize_text_field( (string) $post['first_name'] ) : 'Playwright',
						isset( $post['last_name'] ) ? sanitize_text_field( (string) $post['last_name'] ) : 'Registration'
					);
					break;

				default:
					self::send_error( sprintf( 'Unknown fixtures trigger: "%s".', $trigger ), 400 );
					return;
			}
		} catch ( RuntimeException $exception ) {
			self::send_error( $exception->getMessage(), 400 );
			return;
		}

		wp_send_json( $result );
	}

	/**
	 * Emits the error envelope and ends the request (`wp_send_json()` dies).
	 */
	private static function send_error( string $message, int $code ): void {
		wp_send_json(
			[
				'ok'        => false,
				'triggered' => '',
				'status'    => '',
				'error'     => $message,
			],
			$code
		);
	}

	private static function assert_valid_status( string $status ): void {
		if ( ! in_array( $status, self::ALLOWED_STATUSES, true ) ) {
			throw new RuntimeException(
				sprintf(
					'Unsupported trigger status "%1$s". Allowed: %2$s.',
					$status,
					implode( ', ', self::ALLOWED_STATUSES )
				)
			);
		}
	}

	/**
	 * @param string $family Either `order` or `subscription`.
	 */
	private static function assert_entity( int $id, string $family ): void {
		if ( $id <= 0 || ! get_post( $id ) ) {
			throw new RuntimeException( sprintf( 'Fixture %1$s %2$d does not exist.', $family, $id ) );
		}

		$post_type = (string) get_post_type( $id );
		$matches   = 'subscription' === $family
			? ppcart_is_subscription_post_type( $post_type )
			: ppcart_is_order_post_type( $post_type );

		if ( ! $matches ) {
			throw new RuntimeException(
				sprintf( 'Post %1$d is a "%2$s", not a cart %3$s.', $id, $post_type, $family )
			);
		}
	}

	private static function maybe_set_recipient( int $post_id, string $email ): string {
		if ( '' === $email ) {
			return (string) ppcart_get_post_meta( $post_id, 'email', true );
		}

		if ( ! is_email( $email ) ) {
			throw new RuntimeException( sprintf( 'Invalid recipient override: "%s".', $email ) );
		}

		ppcart_fixtures_update_post_meta( $post_id, 'email', $email );

		return $email;
	}

	private static function remember_created_user( int $user_id ): void {
		$registry = get_option( self::REGISTRY_OPTION, [] );

		if ( ! is_array( $registry ) ) {
			$registry = [];
		}

		$created = isset( $registry['created_user_ids'] ) && is_array( $registry['created_user_ids'] )
			? array_map( 'intval', $registry['created_user_ids'] )
			: [];

		if ( ! in_array( $user_id, $created, true ) ) {
			$created[] = $user_id;
		}

		$registry['created_user_ids'] = $created;
		update_option( self::REGISTRY_OPTION, $registry, false );
	}

	private static function unique_token(): string {
		return substr( md5( uniqid( 'ppcart-email', true ) ), 0, 10 );
	}
}
