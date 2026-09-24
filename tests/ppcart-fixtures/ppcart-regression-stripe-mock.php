<?php

declare(strict_types=1);

/**
 * Local Stripe mock for regression browser tests.
 *
 * Replaces js.stripe.com with a local Card Element stand-in and installs a
 * Stripe PHP HTTP client so checkout never calls api.stripe.com.
 */
final class PPCart_Regression_Stripe_Mock {

	const OPTION_KEY = 'ppcart_regression_mocked_gateways';

	/** @var int */
	private static $id_seq = 0;

	public static function init(): void {
		add_action( 'plugins_loaded', array( __CLASS__, 'install_http_client' ), 100 );
		add_action( 'init', array( __CLASS__, 'install_http_client' ), 1 );
		add_action( 'init', array( __CLASS__, 'maybe_serve_js' ), 0 );
		add_action( 'ppcart_before_create_stripe_payment_intent', array( __CLASS__, 'install_http_client' ), 1 );
		add_action( 'ppcart_before_create_main_order', array( __CLASS__, 'install_http_client' ), 1 );
		add_filter( 'script_loader_src', array( __CLASS__, 'filter_stripe_js_src' ), 10, 2 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'mark_mock_script' ), 100 );
	}

	public static function is_enabled(): bool {
		if ( function_exists( 'ppcart_regression_mocked_gateways_requested' ) ) {
			return ppcart_regression_mocked_gateways_requested();
		}

		if ( defined( 'PPCART_REGRESSION_MOCKED_GATEWAYS' ) && PPCART_REGRESSION_MOCKED_GATEWAYS ) {
			return true;
		}

		return get_option( self::OPTION_KEY, '0' ) === '1';
	}

	public static function install_http_client(): void {
		if ( ! self::is_enabled() ) {
			return;
		}

		if ( ! class_exists( '\PublishPress\Stripe\ApiRequestor' ) ) {
			$candidates = [];
			if ( defined( 'PPCART_BASE_DIR' ) ) {
				$candidates[] = PPCART_BASE_DIR . 'lib/vendor/autoload.php';
			}
			if ( defined( 'WP_PLUGIN_DIR' ) ) {
				$candidates[] = WP_PLUGIN_DIR . '/publishpress-cart/lib/vendor/autoload.php';
			}
			$candidates[] = dirname( __DIR__, 2 ) . '/lib/vendor/autoload.php';

			foreach ( $candidates as $autoload ) {
				if ( is_readable( $autoload ) ) {
					require_once $autoload;
					break;
				}
			}
		}

		if ( ! class_exists( '\PublishPress\Stripe\ApiRequestor' ) ) {
			return;
		}

		\PublishPress\Stripe\ApiRequestor::setHttpClient( new PPCart_Regression_Stripe_Http_Client() );
	}

	public static function maybe_serve_js(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public test asset served from the fixtures plugin only.
		if ( ! isset( $_GET['ppcart_regression_stripe_mock_js'] ) ) {
			return;
		}

		$path = __DIR__ . '/js/ppcart-regression-stripe-mock.js';

		if ( ! is_readable( $path ) ) {
			status_header( 404 );
			exit;
		}

		nocache_headers();
		header( 'Content-Type: application/javascript; charset=UTF-8' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local test fixture asset.
		echo file_get_contents( $path );
		exit;
	}

	/**
	 * @param string $src
	 * @param string $handle
	 */
	public static function filter_stripe_js_src( $src, $handle ) {
		if ( ! self::is_enabled() || 'ppcart-stripe-api-v3' !== $handle ) {
			return $src;
		}

		return add_query_arg( 'ppcart_regression_stripe_mock_js', '1', home_url( '/' ) );
	}

	public static function mark_mock_script(): void {
		if ( ! self::is_enabled() ) {
			return;
		}

		wp_add_inline_script(
			'ppcart-stripe-api-v3',
			'window.PPCART_REGRESSION_STRIPE_MOCKED=true;',
			'before'
		);
	}

	public static function next_id( string $prefix ): string {
		++self::$id_seq;

		$suffix = function_exists( 'wp_generate_password' )
			? wp_generate_password( 8, false, false )
			: bin2hex( random_bytes( 4 ) );

		return $prefix . '_mock_' . self::$id_seq . '_' . $suffix;
	}
}

/**
 * Stripe PHP SDK HTTP client that never leaves the machine.
 */
final class PPCart_Regression_Stripe_Http_Client {

	/** @var array<string, array<string, mixed>> */
	private $store = array();

	/**
	 * @param 'delete'|'get'|'post' $method
	 * @param string                $absUrl
	 * @param array                 $headers
	 * @param array                 $params
	 * @param bool                  $hasFile
	 * @param 'v1'|'v2'             $apiMode
	 * @param null|int              $maxNetworkRetries
	 * @return array{0: string, 1: int, 2: array}
	 */
	public function request( $method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null ) {
		$method = strtolower( (string) $method );
		$path   = (string) ( parse_url( $absUrl, PHP_URL_PATH ) ?: '' );
		$query  = (string) ( parse_url( $absUrl, PHP_URL_QUERY ) ?: '' );

		if ( '' !== $query ) {
			parse_str( $query, $query_params );
			if ( is_array( $query_params ) ) {
				$params = array_merge( $query_params, is_array( $params ) ? $params : array() );
			}
		}

		$response = $this->dispatch( $method, $path, is_array( $params ) ? $params : array() );
		$body     = wp_json_encode( $response );

		return array( $body, 200, array( 'Request-Id' => 'req_mock_regression' ) );
	}

	/**
	 * @param array<string, mixed> $params
	 * @return array<string, mixed>
	 */
	private function dispatch( string $method, string $path, array $params ): array {
		if ( preg_match( '#/v1/customers/?$#', $path ) && 'post' === $method ) {
			return $this->create_customer( $params );
		}

		if ( preg_match( '#/v1/customers/?$#', $path ) && 'get' === $method ) {
			return array(
				'object'   => 'list',
				'data'     => array(),
				'has_more' => false,
				'url'      => '/v1/customers',
			);
		}

		if ( preg_match( '#/v1/customers/([^/]+)$#', $path, $m ) ) {
			if ( 'post' === $method ) {
				return $this->update_object( 'cus', $m[1], $params );
			}

			return $this->get_object( 'cus', $m[1], $this->customer_defaults( $m[1] ) );
		}

		if ( preg_match( '#/v1/payment_intents/?$#', $path ) && 'post' === $method ) {
			return $this->create_payment_intent( $params );
		}

		if ( preg_match( '#/v1/payment_intents/([^/]+)$#', $path, $m ) ) {
			if ( 'post' === $method ) {
				return $this->update_object( 'pi', $m[1], $params );
			}

			return $this->get_object( 'pi', $m[1], $this->payment_intent_defaults( $m[1], $params ) );
		}

		if ( preg_match( '#/v1/setup_intents/?$#', $path ) && 'post' === $method ) {
			return $this->create_setup_intent( $params );
		}

		if ( preg_match( '#/v1/payment_methods/([^/]+)/attach$#', $path, $m ) && 'post' === $method ) {
			$pm = $this->get_object( 'pm', $m[1], $this->payment_method_defaults( $m[1] ) );
			$pm['customer'] = isset( $params['customer'] ) ? (string) $params['customer'] : null;

			return $this->put_object( 'pm', $m[1], $pm );
		}

		if ( preg_match( '#/v1/payment_methods/([^/]+)$#', $path, $m ) ) {
			return $this->get_object( 'pm', $m[1], $this->payment_method_defaults( $m[1] ) );
		}

		if ( preg_match( '#/v1/subscriptions/?$#', $path ) && 'post' === $method ) {
			return $this->create_subscription( $params );
		}

		if ( preg_match( '#/v1/products/?$#', $path ) && 'post' === $method ) {
			return $this->create_product( $params );
		}

		if ( preg_match( '#/v1/products/([^/]+)$#', $path, $m ) ) {
			if ( 'post' === $method ) {
				return $this->update_object( 'prod', $m[1], $params );
			}

			return $this->get_object( 'prod', $m[1], $this->product_defaults( $m[1], $params ) );
		}

		if ( preg_match( '#/v1/prices/?$#', $path ) && 'post' === $method ) {
			return $this->create_price( $params );
		}

		if ( preg_match( '#/v1/prices/([^/]+)$#', $path, $m ) ) {
			return $this->get_object( 'price', $m[1], $this->price_defaults( $m[1], $params ) );
		}

		if ( preg_match( '#/v1/invoices/([^/]+)$#', $path, $m ) ) {
			return $this->get_object( 'in', $m[1], $this->invoice_defaults( $m[1] ) );
		}

		if ( preg_match( '#/v1/coupons/?$#', $path ) && 'post' === $method ) {
			$id = PPCart_Regression_Stripe_Mock::next_id( 'coupon' );

			return array(
				'id'     => $id,
				'object' => 'coupon',
				'valid'  => true,
			);
		}

		// Unknown endpoint: return a generic succeeded object so callers do not fatally fail.
		return array(
			'id'     => PPCart_Regression_Stripe_Mock::next_id( 'obj' ),
			'object' => 'object',
		);
	}

	/**
	 * @param array<string, mixed> $params
	 * @return array<string, mixed>
	 */
	private function create_customer( array $params ): array {
		$id = PPCart_Regression_Stripe_Mock::next_id( 'cus' );
		$customer = array_merge(
			$this->customer_defaults( $id ),
			array(
				'email' => isset( $params['email'] ) ? (string) $params['email'] : '',
				'name'  => isset( $params['name'] ) ? (string) $params['name'] : '',
				'phone' => isset( $params['phone'] ) ? (string) $params['phone'] : null,
			)
		);

		return $this->put_object( 'cus', $id, $customer );
	}

	/**
	 * @param array<string, mixed> $params
	 * @return array<string, mixed>
	 */
	private function create_payment_intent( array $params ): array {
		$id = PPCart_Regression_Stripe_Mock::next_id( 'pi' );
		$intent = array_merge(
			$this->payment_intent_defaults( $id, $params ),
			array(
				'amount'   => isset( $params['amount'] ) ? (int) $params['amount'] : 0,
				'currency' => isset( $params['currency'] ) ? (string) $params['currency'] : 'usd',
				'customer' => isset( $params['customer'] ) ? (string) $params['customer'] : null,
				'status'   => 'requires_confirmation',
			)
		);

		return $this->put_object( 'pi', $id, $intent );
	}

	/**
	 * @param array<string, mixed> $params
	 * @return array<string, mixed>
	 */
	private function create_setup_intent( array $params ): array {
		$id = PPCart_Regression_Stripe_Mock::next_id( 'seti' );

		$intent = array(
			'id'            => $id,
			'object'        => 'setup_intent',
			'client_secret' => $id . '_secret_mock',
			'customer'      => isset( $params['customer'] ) ? (string) $params['customer'] : null,
			'status'        => 'requires_payment_method',
			'usage'         => 'off_session',
		);

		return $this->put_object( 'seti', $id, $intent );
	}

	/**
	 * @param array<string, mixed> $params
	 * @return array<string, mixed>
	 */
	private function create_subscription( array $params ): array {
		$id         = PPCart_Regression_Stripe_Mock::next_id( 'sub' );
		$invoice_id = PPCart_Regression_Stripe_Mock::next_id( 'in' );
		$pi_id      = PPCart_Regression_Stripe_Mock::next_id( 'pi' );

		$payment_intent = array(
			'id'            => $pi_id,
			'object'        => 'payment_intent',
			'client_secret' => $pi_id . '_secret_mock',
			'status'        => 'succeeded',
		);

		$invoice = array(
			'id'             => $invoice_id,
			'object'         => 'invoice',
			'payment_intent' => $payment_intent,
			'status'         => 'paid',
		);

		$subscription = array(
			'id'             => $id,
			'object'         => 'subscription',
			'status'         => 'active',
			'customer'       => isset( $params['customer'] ) ? (string) $params['customer'] : null,
			'latest_invoice' => $invoice,
			'items'          => array(
				'object' => 'list',
				'data'   => array(),
			),
		);

		$this->put_object( 'in', $invoice_id, $invoice );
		$this->put_object( 'pi', $pi_id, $payment_intent );

		return $this->put_object( 'sub', $id, $subscription );
	}

	/**
	 * @param array<string, mixed> $params
	 * @return array<string, mixed>
	 */
	private function create_product( array $params ): array {
		$id = PPCart_Regression_Stripe_Mock::next_id( 'prod' );
		$product = array_merge(
			$this->product_defaults( $id, $params ),
			array(
				'name'        => isset( $params['name'] ) ? (string) $params['name'] : 'Mock product',
				'description' => isset( $params['description'] ) ? (string) $params['description'] : '',
				'metadata'    => isset( $params['metadata'] ) && is_array( $params['metadata'] ) ? $params['metadata'] : array(),
			)
		);

		return $this->put_object( 'prod', $id, $product );
	}

	/**
	 * @param array<string, mixed> $params
	 * @return array<string, mixed>
	 */
	private function create_price( array $params ): array {
		$id = PPCart_Regression_Stripe_Mock::next_id( 'price' );
		$price = array_merge(
			$this->price_defaults( $id, $params ),
			array(
				'unit_amount' => isset( $params['unit_amount'] ) ? (int) $params['unit_amount'] : 0,
				'currency'    => isset( $params['currency'] ) ? (string) $params['currency'] : 'usd',
				'product'     => isset( $params['product'] ) ? (string) $params['product'] : '',
				'recurring'   => isset( $params['recurring'] ) && is_array( $params['recurring'] ) ? $params['recurring'] : null,
				'metadata'    => isset( $params['metadata'] ) && is_array( $params['metadata'] ) ? $params['metadata'] : array(),
			)
		);

		return $this->put_object( 'price', $id, $price );
	}

	/**
	 * @param array<string, mixed> $defaults
	 * @return array<string, mixed>
	 */
	private function get_object( string $type, string $id, array $defaults ): array {
		$key = $type . ':' . $id;

		if ( isset( $this->store[ $key ] ) ) {
			return $this->store[ $key ];
		}

		return $this->put_object( $type, $id, $defaults );
	}

	/**
	 * @param array<string, mixed> $params
	 * @return array<string, mixed>
	 */
	private function update_object( string $type, string $id, array $params ): array {
		$existing = $this->get_object( $type, $id, array( 'id' => $id, 'object' => $type ) );

		foreach ( $params as $key => $value ) {
			$existing[ $key ] = $value;
		}

		return $this->put_object( $type, $id, $existing );
	}

	/**
	 * @param array<string, mixed> $object
	 * @return array<string, mixed>
	 */
	private function put_object( string $type, string $id, array $object ): array {
		$object['id'] = $id;
		$this->store[ $type . ':' . $id ] = $object;

		return $object;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function customer_defaults( string $id ): array {
		return array(
			'id'     => $id,
			'object' => 'customer',
			'email'  => '',
			'name'   => '',
		);
	}

	/**
	 * @param array<string, mixed> $params
	 * @return array<string, mixed>
	 */
	private function payment_intent_defaults( string $id, array $params = array() ): array {
		return array(
			'id'            => $id,
			'object'        => 'payment_intent',
			'client_secret' => $id . '_secret_mock',
			'status'        => 'requires_confirmation',
			'amount'        => isset( $params['amount'] ) ? (int) $params['amount'] : 0,
			'currency'      => isset( $params['currency'] ) ? (string) $params['currency'] : 'usd',
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function payment_method_defaults( string $id ): array {
		return array(
			'id'     => $id,
			'object' => 'payment_method',
			'type'   => 'card',
			'card'   => array(
				'brand' => 'visa',
				'last4' => '4242',
			),
		);
	}

	/**
	 * @param array<string, mixed> $params
	 * @return array<string, mixed>
	 */
	private function product_defaults( string $id, array $params = array() ): array {
		return array(
			'id'       => $id,
			'object'   => 'product',
			'name'     => isset( $params['name'] ) ? (string) $params['name'] : 'Mock product',
			'metadata' => isset( $params['metadata'] ) && is_array( $params['metadata'] ) ? $params['metadata'] : array(),
		);
	}

	/**
	 * @param array<string, mixed> $params
	 * @return array<string, mixed>
	 */
	private function price_defaults( string $id, array $params = array() ): array {
		return array(
			'id'          => $id,
			'object'      => 'price',
			'unit_amount' => isset( $params['unit_amount'] ) ? (int) $params['unit_amount'] : 1000,
			'currency'    => isset( $params['currency'] ) ? (string) $params['currency'] : 'usd',
			'product'     => isset( $params['product'] ) ? (string) $params['product'] : 'prod_mock',
			'recurring'   => array(
				'interval'       => 'month',
				'interval_count' => 1,
			),
			'metadata'    => array(),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function invoice_defaults( string $id ): array {
		return array(
			'id'             => $id,
			'object'         => 'invoice',
			'status'         => 'paid',
			'payment_intent' => null,
		);
	}
}
