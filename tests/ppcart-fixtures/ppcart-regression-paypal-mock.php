<?php

declare(strict_types=1);

/**
 * Local PayPal checkout mock for regression browser tests.
 *
 * Redirects PayPal checkout to a WordPress page that mimics the sandbox UI
 * selectors used by PayPalSteps, then returns to the Cart thank-you flow.
 */
final class PPCart_Regression_PayPal_Mock {

	const PAGE_SLUG = 'regression-paypal-mock';

	public static function init(): void {
		add_filter( 'ppcart_paypal_checkout_url', array( __CLASS__, 'filter_checkout_url' ), 10, 3 );
		add_filter( 'pre_http_request', array( __CLASS__, 'mock_ipn_verification' ), 10, 3 );
		add_action( 'rest_api_init', array( __CLASS__, 'register_test_routes' ) );
		add_action( 'template_redirect', array( __CLASS__, 'render_mock_checkout' ), 0 );
	}

	public static function is_enabled(): bool {
		if ( defined( 'PPCART_REGRESSION_REAL_PAYPAL' ) && PPCART_REGRESSION_REAL_PAYPAL ) {
			return false;
		}

		$mocked = function_exists( 'ppcart_regression_mocked_gateways_requested' )
			? ppcart_regression_mocked_gateways_requested()
			: (
				get_option( 'ppcart_regression_mocked_gateways', '0' ) === '1'
				|| ( defined( 'PPCART_REGRESSION_MOCKED_GATEWAYS' ) && PPCART_REGRESSION_MOCKED_GATEWAYS )
			);

		if ( ! $mocked && get_option( 'ppcart_regression_real_paypal', '1' ) === '1' ) {
			return false;
		}

		$registry = get_option( PPCart_Regression_Fixtures::REGISTRY_OPTION, array() );

		return is_array( $registry ) && ! empty( $registry['pages'] );
	}

	/**
	 * @param mixed $order
	 * @param mixed $sub
	 */
	public static function filter_checkout_url( string $url, $order, $sub ): string {
		if ( ! self::is_enabled() || ! is_object( $order ) || empty( $order->return_url ) ) {
			return $url;
		}

		return add_query_arg(
			array(
				'return'       => rawurlencode( (string) $order->return_url ),
				'subscription' => ! empty( $sub ) ? '1' : '0',
			),
			home_url( '/' . self::PAGE_SLUG . '/' )
		);
	}

	/**
	 * Return PayPal's invalid-IPN response for mocked regression requests.
	 *
	 * @param false|array|\WP_Error $preempt
	 * @param array<string, mixed>  $request
	 * @return false|array|\WP_Error
	 */
	public static function mock_ipn_verification( $preempt, array $request, string $url ) {
		if ( ! self::is_enabled() || false === strpos( $url, 'paypal.com/cgi-bin/webscr' ) ) {
			return $preempt;
		}

		$body = self::request_body_string( $request['body'] ?? '' );
		if ( false !== strpos( $body, 'cmd=_notify-synch' ) ) {
			$raw_body = $request['body'] ?? '';
			$pdt_body = self::should_fail_pdt( $raw_body )
				? 'FAIL'
				: self::successful_pdt_body( $raw_body );

			return array(
				'headers'  => array(),
				'body'     => $pdt_body,
				'response' => array(
					'code'    => 200,
					'message' => 'OK',
				),
				'cookies'  => array(),
				'filename' => null,
			);
		}

		if ( false === strpos( $body, 'cmd=_notify-validate' ) ) {
			return $preempt;
		}

		return array(
			'headers'  => array(),
			'body'     => 'INVALID',
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'cookies'  => array(),
			'filename' => null,
		);
	}

	/**
	 * Normalize wp_remote_post body (string or form array) for cmd detection.
	 *
	 * @param mixed $body Request body from WP HTTP API args.
	 */
	private static function request_body_string( $body ): string {
		if ( is_string( $body ) ) {
			return $body;
		}

		if ( is_array( $body ) ) {
			return (string) http_build_query( $body );
		}

		return '';
	}

	/**
	 * Fail PDT for explicit fixture outcome or RT-022's bogus tx (parallel-safe).
	 *
	 * @param mixed $body Original request body (may include tx).
	 */
	private static function should_fail_pdt( $body ): bool {
		if ( 'fail' === get_option( 'ppcart_regression_pdt_outcome', 'success' ) ) {
			return true;
		}

		$tx = self::pdt_tx_from_body( $body );

		return '' !== $tx && 0 === strpos( $tx, 'bogus' );
	}

	/**
	 * @param mixed $body Original request body (may include tx).
	 */
	private static function pdt_tx_from_body( $body ): string {
		if ( is_array( $body ) && isset( $body['tx'] ) && is_scalar( $body['tx'] ) ) {
			return (string) $body['tx'];
		}

		if ( is_string( $body ) && preg_match( '/(?:^|&)tx=([^&]*)/', $body, $matches ) ) {
			return rawurldecode( $matches[1] );
		}

		return '';
	}

	/**
	 * PDT SUCCESS payload that satisfies run_pdt_check (needs payment_status).
	 *
	 * @param mixed $body Original request body (may include tx).
	 */
	private static function successful_pdt_body( $body ): string {
		$tx = self::pdt_tx_from_body( $body );
		if ( '' === $tx ) {
			$tx = 'regression-mock-tx';
		}

		return "SUCCESS\npayment_status=Completed\ntxn_id={$tx}";
	}

	/**
	 * Test-only endpoints that prepare and inspect an order around a real IPN request.
	 */
	public static function register_test_routes(): void {
		register_rest_route(
			'ppcart-fixtures/v1',
			'/paypal-ipn-order/(?P<order_id>\\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( __CLASS__, 'prepare_ipn_order' ),
					'permission_callback' => array( __CLASS__, 'allow_mocked_regression_request' ),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'get_ipn_order_refund_log' ),
					'permission_callback' => array( __CLASS__, 'allow_mocked_regression_request' ),
				),
			)
		);
	}

	public static function allow_mocked_regression_request(): bool {
		return self::is_enabled() && ppcart_regression_mocked_gateways_requested();
	}

	/**
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function prepare_ipn_order( \WP_REST_Request $request ) {
		$order_id       = absint( $request['order_id'] );
		$transaction_id = sanitize_text_field( (string) $request->get_param( 'transaction_id' ) );

		if ( ! self::is_order( $order_id ) || '' === $transaction_id ) {
			return new \WP_Error( 'ppcart_regression_invalid_order', 'Invalid regression order.', array( 'status' => 400 ) );
		}

		$order                 = new PPCart_Order( $order_id );
		$order->transaction_id = $transaction_id;
		$order->refund_log     = array();
		$order->store();

		return new \WP_REST_Response( array( 'order_id' => $order_id ), 200 );
	}

	/**
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function get_ipn_order_refund_log( \WP_REST_Request $request ) {
		$order_id = absint( $request['order_id'] );
		if ( ! self::is_order( $order_id ) ) {
			return new \WP_Error( 'ppcart_regression_invalid_order', 'Invalid regression order.', array( 'status' => 400 ) );
		}

		$refund_log = ppcart_get_post_meta( $order_id, 'refund_log', true );

		return new \WP_REST_Response(
			array(
				'refund_log' => is_array( $refund_log ) ? $refund_log : array(),
			),
			200
		);
	}

	private static function is_order( int $order_id ): bool {
		return $order_id > 0 && get_post_type( $order_id ) === ppcart_live_post_type( 'order' );
	}

	/**
	 * @return array{id: int, slug: string, path: string, title: string}
	 */
	public static function ensure_page(): array {
		$existing = get_page_by_path( self::PAGE_SLUG, OBJECT, 'page' );

		if ( $existing instanceof WP_Post ) {
			return array(
				'id'    => (int) $existing->ID,
				'slug'  => self::PAGE_SLUG,
				'path'  => '/' . self::PAGE_SLUG . '/',
				'title' => (string) $existing->post_title,
			);
		}

		$page_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => 'PublishPress Cart Regression - PayPal Mock',
				'post_name'    => self::PAGE_SLUG,
				'post_content' => '<!-- PayPal regression mock -->',
			),
			true
		);

		if ( is_wp_error( $page_id ) ) {
			throw new RuntimeException( $page_id->get_error_message() );
		}

		return array(
			'id'    => (int) $page_id,
			'slug'  => self::PAGE_SLUG,
			'path'  => '/' . self::PAGE_SLUG . '/',
			'title' => 'PublishPress Cart Regression - PayPal Mock',
		);
	}

	public static function render_mock_checkout(): void {
		if ( ! self::is_enabled() || ! is_page( self::PAGE_SLUG ) ) {
			return;
		}

		$return_url = isset( $_GET['return'] ) ? rawurldecode( (string) wp_unslash( $_GET['return'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$is_subscription = isset( $_GET['subscription'] ) && '1' === (string) wp_unslash( $_GET['subscription'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( '' === $return_url || ! wp_http_validate_url( $return_url ) ) {
			wp_die( esc_html__( 'Missing PayPal mock return URL.', 'publishpress-cart' ), '', array( 'response' => 400 ) );
		}

		nocache_headers();
		status_header( 200 );

		echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>PayPal Sandbox Mock</title>';
		echo '<style>body{font-family:Helvetica,Arial,sans-serif;background:#f5f7fa;margin:0;padding:40px}.panel{max-width:460px;margin:0 auto;background:#fff;border:1px solid #cbd2d9;border-radius:8px;padding:24px}.logo{font-size:28px;font-weight:700;color:#003087;margin-bottom:24px}label{display:block;margin:12px 0 6px}input{width:100%;padding:10px;border:1px solid #cbd2d9;border-radius:4px;box-sizing:border-box}button{margin-top:16px;padding:12px 16px;border:0;border-radius:20px;background:#0070ba;color:#fff;font-weight:700;cursor:pointer}.hidden{display:none}h4.noBottom{margin:0 0 16px}</style>';
		echo '</head><body><div class="panel"><div class="logo">PayPal</div>';
		echo '<div id="login-step">';
		echo '<label for="email">Email</label><input id="email" type="text" autocomplete="username">';
		echo '<button type="button" id="next-button">Next</button>';
		echo '<label for="password">Password</label><input id="password" type="password" autocomplete="current-password" class="hidden">';
		echo '<button type="button" id="login-button" class="hidden">Log In</button>';
		echo '</div>';
		echo '<div id="payment-step" class="hidden">';

		if ( $is_subscription ) {
			echo '<div id="subscription-step"><h4 class="noBottom">Choose a way to pay</h4>';
			echo '<button type="button" id="continue-button">Continue</button>';
			echo '<button type="button" data-test-id="continueButton">Approve</button></div>';
		} else {
			echo '<button type="button" id="submit-button-initial" data-testid="submit-button-initial">Pay Now</button>';
		}

		echo '</div></div>';
		echo '<script>(function(){var returnUrl=' . wp_json_encode( $return_url ) . ';';
		echo 'if(returnUrl.indexOf("tx=")===-1){returnUrl+=(returnUrl.indexOf("?")>=0?"&":"?")+"tx=regression-mock-tx";}';
		echo 'var nextButton=document.getElementById("next-button");var loginButton=document.getElementById("login-button");';
		echo 'var passwordField=document.getElementById("password");var loginStep=document.getElementById("login-step");';
		echo 'var paymentStep=document.getElementById("payment-step");';
		echo 'nextButton.addEventListener("click",function(){passwordField.classList.remove("hidden");loginButton.classList.remove("hidden");});';
		echo 'loginButton.addEventListener("click",function(){loginStep.classList.add("hidden");paymentStep.classList.remove("hidden");});';
		echo 'var approveButton=document.querySelector("[data-test-id=\\"continueButton\\"]");';
		echo 'if(approveButton){approveButton.addEventListener("click",function(){window.location.href=returnUrl;});}';
		echo 'var submitButton=document.getElementById("submit-button-initial");';
		echo 'if(submitButton){submitButton.addEventListener("click",function(){window.location.href=returnUrl;});}';
		echo '})();</script>';
		echo '</body></html>';
		exit;
	}
}
