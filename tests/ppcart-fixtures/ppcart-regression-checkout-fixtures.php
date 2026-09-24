<?php

declare(strict_types=1);

/**
 * Regression REST helpers for checkout completion authorization tests.
 */
final class PPCart_Regression_Checkout_Fixtures {

	public static function init(): void {
		add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
	}

	public static function register_routes(): void {
		register_rest_route(
			'ppcart-fixtures/v1',
			'/checkout-complete/(?P<order_id>\d+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'get_checkout_complete' ],
				'permission_callback' => [ __CLASS__, 'allow_mocked_regression_request' ],
			]
		);

		register_rest_route(
			'ppcart-fixtures/v1',
			'/pending-order',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ __CLASS__, 'create_pending_order' ],
				'permission_callback' => [ __CLASS__, 'allow_mocked_regression_request' ],
			]
		);

		register_rest_route(
			'ppcart-fixtures/v1',
			'/paypal-pdt-token',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ __CLASS__, 'set_paypal_pdt_token' ],
				'permission_callback' => [ __CLASS__, 'allow_mocked_regression_request' ],
			]
		);
	}

	public static function allow_mocked_regression_request(): bool {
		return PPCart_Regression_PayPal_Mock::allow_mocked_regression_request();
	}

	/**
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function get_checkout_complete( \WP_REST_Request $request ) {
		$order_id = absint( $request['order_id'] );
		if ( ! self::is_order( $order_id ) ) {
			return new \WP_Error( 'ppcart_regression_invalid_order', 'Invalid regression order.', [ 'status' => 400 ] );
		}

		$status = (string) ppcart_get_post_meta( $order_id, 'status', true );

		return new \WP_REST_Response(
			[
				'fired'  => function_exists( 'ppcart_checkout_complete_fire_count' )
					? ppcart_checkout_complete_fire_count( $order_id )
					: 0,
				'status' => $status,
			],
			200
		);
	}

	/**
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function create_pending_order( \WP_REST_Request $request ) {
		$product_id = absint( $request->get_param( 'product_id' ) );
		if ( ! $product_id ) {
			$registry = get_option( PPCart_Regression_Fixtures::REGISTRY_OPTION, [] );
			if ( is_array( $registry ) && ! empty( $registry['products']['one_time_paypal']['id'] ) ) {
				$product_id = absint( $registry['products']['one_time_paypal']['id'] );
			}
		}

		$order_id = wp_insert_post(
			[
				'post_type'   => ppcart_live_post_type( 'order' ),
				'post_status' => 'publish',
				'post_title'  => 'Regression pending order',
			]
		);

		if ( ! $order_id || is_wp_error( $order_id ) ) {
			return new \WP_Error( 'ppcart_regression_order_failed', 'Could not create pending order.', [ 'status' => 500 ] );
		}

		ppcart_update_post_meta( $order_id, 'status', 'pending-payment' );
		ppcart_update_post_meta( $order_id, 'amount', 10 );
		ppcart_update_post_meta( $order_id, 'email', 'buyer@example.test' );
		if ( $product_id ) {
			ppcart_update_post_meta( $order_id, 'product_id', $product_id );
		}

		$access = PPCart_Order::ensure_invoice_access_token( (int) $order_id );

		return new \WP_REST_Response(
			[
				'order_id' => (int) $order_id,
				'access'   => $access,
			],
			200
		);
	}

	/**
	 * @return \WP_REST_Response
	 */
	public static function set_paypal_pdt_token( \WP_REST_Request $request ) {
		$enabled = '1' === (string) $request->get_param( 'enabled' );
		if ( $enabled ) {
			update_option( '_ppcart_paypal_enable_sandbox', 'enable' );
			update_option( '_ppcart_paypal_sandbox_pdt_token', 'regression-pdt-token' );
			// Keep outcome=success so parallel purchase workers are not poisoned.
			// RT-022 fail cases use tx=bogus-tx (see PayPal mock).
			update_option( 'ppcart_regression_pdt_outcome', 'success' );
		} else {
			delete_option( '_ppcart_paypal_sandbox_pdt_token' );
			update_option( 'ppcart_regression_pdt_outcome', 'success' );
		}

		return new \WP_REST_Response( [ 'enabled' => $enabled ], 200 );
	}

	private static function is_order( int $order_id ): bool {
		return $order_id > 0 && get_post_type( $order_id ) === ppcart_live_post_type( 'order' );
	}
}
