<?php

declare(strict_types=1);

/**
 * Regression-suite fixtures for Ghost Inspector / Codeception browser tests.
 */
final class PPCart_Regression_Fixtures {

	const REGISTRY_OPTION = 'ppcart_regression_fixtures_registry';

	/**
	 * @return array<string, mixed>
	 */
	public static function setup(): array {
		PPCart_Fixtures::assert_can_create();

		self::cleanup();

		$products = self::create_products();
		$stripe_sync = self::sync_stripe_products( $products );
		$pages    = self::create_pages( $products );
		$mock_page = PPCart_Regression_PayPal_Mock::ensure_page();
		$smoke    = PPCart_Smoke_Fixtures::setup( $products );

		$registry = [
			'created_at' => time(),
			'products'   => $products,
			'pages'      => $pages,
			'mock_page'  => $mock_page,
			'smoke'      => [
				'products' => $smoke['products'],
				'pages'    => $smoke['pages'],
			],
		];

		update_option( self::REGISTRY_OPTION, $registry, false );

		$paths = [];

		foreach ( $pages as $env_key => $page ) {
			$paths[ self::env_key_for_url( $env_key ) ] = $page['path'];
		}

		foreach ( $smoke['paths'] as $env_key => $path ) {
			$paths[ self::env_key_for_url( $env_key ) ] = $path;
		}

		return [
			'products'    => $products,
			'pages'       => $pages,
			'paths'       => $paths,
			'smoke'       => $smoke,
			'stripe_sync' => $stripe_sync,
		];
	}

	/**
	 * Whether fixture products still point at mock Stripe IDs from a mocked setup.
	 */
	public static function has_mock_stripe_price_ids(): bool {
		$registry = get_option( self::REGISTRY_OPTION, [] );
		$products = ( isset( $registry['products'] ) && is_array( $registry['products'] ) )
			? $registry['products']
			: [];

		foreach ( $products as $product_id ) {
			$product_id = (int) $product_id;
			if ( $product_id < 1 ) {
				continue;
			}

			$stripe_prod = function_exists( 'ppcart_get_post_meta' )
				? (string) ppcart_get_post_meta( $product_id, 'stripe_prod_id', true )
				: '';
			if ( str_contains( $stripe_prod, '_mock_' ) ) {
				return true;
			}

			$opts = function_exists( 'ppcart_fixtures_get_pay_options' )
				? ppcart_fixtures_get_pay_options( $product_id )
				: [];
			$blob = wp_json_encode( $opts );
			if ( is_string( $blob ) && str_contains( $blob, 'price_mock_' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @return array<string, int>
	 */
	public static function cleanup(): array {
		$removed = [
			'products' => 0,
			'pages'    => 0,
		];

		if ( class_exists( 'PPCart_Smoke_Fixtures' ) ) {
			$smoke_removed = PPCart_Smoke_Fixtures::cleanup();
			$removed['products'] += $smoke_removed['products'];
			$removed['pages']    += $smoke_removed['pages'];
		}

		$slugs = [];
		foreach ( self::page_definitions() as $definition ) {
			$slugs[] = (string) ( $definition['slug'] ?? '' );
		}
		$removed['pages'] += ppcart_fixtures_delete_pages_by_slugs( $slugs );

		$registry = get_option( self::REGISTRY_OPTION, [] );

		if ( ! is_array( $registry ) ) {
			return $removed;
		}

		if ( ! empty( $registry['pages'] ) && is_array( $registry['pages'] ) ) {
			foreach ( $registry['pages'] as $page ) {
				$page_id = (int) ( $page['id'] ?? 0 );

				if ( $page_id > 0 && wp_delete_post( $page_id, true ) ) {
					++$removed['pages'];
				}
			}
		}

		if ( ! empty( $registry['mock_page']['id'] ) ) {
			$mock_page_id = (int) $registry['mock_page']['id'];

			if ( $mock_page_id > 0 && wp_delete_post( $mock_page_id, true ) ) {
				++$removed['pages'];
			}
		}

		if ( ! empty( $registry['products'] ) && is_array( $registry['products'] ) ) {
			foreach ( $registry['products'] as $product_id ) {
				$product_id = (int) $product_id;

				if ( $product_id > 0 && wp_delete_post( $product_id, true ) ) {
					++$removed['products'];
				}
			}
		}

		delete_option( self::REGISTRY_OPTION );

		return $removed;
	}

	/**
	 * @return array<string, int>
	 */
	private static function create_products(): array {
		$definitions = self::product_definitions();
		$products    = [];

		foreach ( $definitions as $key => $definition ) {
			$product_id = wp_insert_post(
				[
					'post_type'   => ppcart_fixtures_product_post_type(),
					'post_status' => 'publish',
					'post_title'  => $definition['title'],
				],
				true
			);

			if ( is_wp_error( $product_id ) ) {
				throw new RuntimeException( $product_id->get_error_message() );
			}

			self::apply_product_definition( (int) $product_id, $definition );
			$products[ $key ] = (int) $product_id;
		}

		return $products;
	}

	/**
	 * @param array<string, int> $products
	 * @return array<string, array<string, int|string>>
	 */
	private static function create_pages( array $products ): array {
		$pages = [];

		foreach ( self::page_definitions() as $env_key => $definition ) {
			$product_key = $definition['product_key'];
			$product_id  = $products[ $product_key ] ?? 0;

			if ( $product_id <= 0 ) {
				throw new RuntimeException( sprintf( 'Missing regression product: %s', $product_key ) );
			}

			$page_id = wp_insert_post(
				[
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => $definition['title'],
					'post_name'    => $definition['slug'],
					'post_content' => sprintf( '[ppcart_form id="%d"]', $product_id ),
				],
				true
			);

			if ( is_wp_error( $page_id ) ) {
				throw new RuntimeException( $page_id->get_error_message() );
			}

			$permalink = get_permalink( $page_id );
			$path      = $permalink ? (string) wp_parse_url( $permalink, PHP_URL_PATH ) : '/' . $definition['slug'] . '/';
			$query     = $permalink ? (string) wp_parse_url( $permalink, PHP_URL_QUERY ) : '';

			if ( '' !== $query ) {
				$path .= '?' . $query;
			}

			$pages[ $env_key ] = [
				'id'    => (int) $page_id,
				'slug'  => $definition['slug'],
				'path'  => $path ?: '/' . $definition['slug'] . '/',
				'title' => $definition['title'],
			];
		}

		return $pages;
	}

	/**
	 * @param array<string, mixed> $definition
	 */
	private static function apply_product_definition( int $product_id, array $definition ): void {
		$common = [
			'plan_heading'          => 'Payment Plan',
			'button_color'          => '#000000',
			'button_text'           => 'Order Now',
			'step1_button_label'    => 'Continue',
			'step1_button_icon_pos' => 'left',
			'checkout_ended_action'     => 'message',
			'checkout_ended_message'   => 'Sorry, this product is no longer for sale.',
			'show_full_price'       => '1',
		];

		foreach ( $common as $meta_key => $meta_value ) {
			ppcart_fixtures_update_post_meta( $product_id, $meta_key, $meta_value );
		}

		if ( ! empty( $definition['on_sale'] ) ) {
			ppcart_fixtures_update_post_meta( $product_id, 'on_sale', '1' );
		}

		if ( isset( $definition['disable_stripe'] ) ) {
			ppcart_fixtures_update_post_meta( $product_id, 'disable_stripe', $definition['disable_stripe'] ? '1' : '' );
		}

		if ( isset( $definition['disable_paypal'] ) ) {
			ppcart_fixtures_update_post_meta( $product_id, 'disable_paypal', $definition['disable_paypal'] ? '1' : '' );
		}

		ppcart_fixtures_update_post_meta( $product_id, '_ppcart_pay_options', $definition['pay_options'] );
	}

	/**
	 * @param array<string, int> $products
	 * @return array{enabled: bool, synced: int, skipped: int}
	 */
	private static function sync_stripe_products( array $products ): array {
		$result = [
			'enabled' => false,
			'synced'  => 0,
			'skipped' => 0,
		];

		if ( '1' !== get_option('_ppcart_stripe_enable' ) ) {
			return $result;
		}

		if ( class_exists( 'PPCart_Regression_Stripe_Mock' ) ) {
			PPCart_Regression_Stripe_Mock::install_http_client();
		}

		if ( function_exists('ppcart_setup_stripe') ) {
			ppcart_setup_stripe();
		}

		global $ppcart_stripe;

		if ( empty( $ppcart_stripe ) || empty( $ppcart_stripe['sk'] ) ) {
			return $result;
		}

		if ( ! class_exists( 'PPCart_Product_Admin' ) ) {
			if ( ! defined( 'PPCART_BASE_DIR' ) ) {
				return $result;
			}

			$admin_file = PPCART_BASE_DIR . 'admin/class-ppcart-product-admin.php';

			if ( ! is_readable( $admin_file ) ) {
				return $result;
			}

			require_once $admin_file;
		}

		$stripe_product_admin = new PPCart_Product_Admin();
		$definitions          = self::product_definitions();
		$result['enabled']    = true;

		foreach ( $products as $key => $product_id ) {
			$definition = $definitions[ $key ] ?? null;

			if ( ! $definition || ! empty( $definition['disable_stripe'] ) ) {
				++$result['skipped'];
				continue;
			}

			$pay_options = ppcart_fixtures_get_pay_options( $product_id );

			if ( ! is_array( $pay_options ) ) {
				++$result['skipped'];
				continue;
			}

			$stripe_product_admin->save_stripe_objects(
				$product_id,
				[
					ppcart_fixtures_pay_options_objects_key() => $pay_options,
				]
			);
			++$result['synced'];
		}

		return $result;
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private static function product_definitions(): array {
		$plan_defaults = [
			'frequency'         => '1',
			'sale_frequency'    => '1',
			'interval'          => 'day',
			'sale_interval'     => 'day',
			'installments'      => '-1',
			'sale_installments' => '-1',
		];

		return [
			'one_time_stripe' => [
				'title'          => 'PublishPress Cart Regression - One Time Stripe',
				'disable_paypal' => true,
				'pay_options'    => [
					array_merge(
						$plan_defaults,
						[
							'option_id'      => 'reg_stripe_10',
							'option_name'    => 'One payment of $10',
							'price'          => '10',
							'stripe_plan_id' => 'reg_stripe_10',
						]
					),
					array_merge(
						$plan_defaults,
						[
							'option_id'      => 'reg_stripe_20',
							'option_name'    => 'One payment of $20',
							'price'          => '20',
							'stripe_plan_id' => 'reg_stripe_20',
						]
					),
				],
			],
			'one_time_stripe_sale' => [
				'title'          => 'PublishPress Cart Regression - One Time Stripe Sale',
				'on_sale'        => true,
				'disable_paypal' => true,
				'pay_options'    => [
					array_merge(
						$plan_defaults,
						[
							'option_id'        => 'reg_stripe_sale_10',
							'option_name'      => 'One payment of $10',
							'sale_option_name' => 'One payment of $8 (20% off)',
							'price'            => '10',
							'sale_price'       => '8',
							'stripe_plan_id'   => 'reg_stripe_sale_10',
						]
					),
					array_merge(
						$plan_defaults,
						[
							'option_id'        => 'reg_stripe_sale_20',
							'option_name'      => 'One payment of $20',
							'sale_option_name' => 'One payment of $16 (20% off)',
							'price'            => '20',
							'sale_price'       => '16',
							'stripe_plan_id'   => 'reg_stripe_sale_20',
						]
					),
				],
			],
			'subs_stripe' => [
				'title'          => 'PublishPress Cart Regression - Subscription Stripe',
				'disable_paypal' => true,
				'pay_options'    => [
					array_merge(
						$plan_defaults,
						[
							'option_id'      => 'reg_stripe_monthly',
							'option_name'    => 'Monthly',
							'product_type'   => 'recurring',
							'price'          => '10',
							'interval'       => 'month',
							'sale_interval'  => 'month',
							'stripe_plan_id' => 'reg_stripe_monthly',
						]
					),
					array_merge(
						$plan_defaults,
						[
							'option_id'      => 'reg_stripe_annually',
							'option_name'    => 'Annually',
							'product_type'   => 'recurring',
							'price'          => '100',
							'interval'       => 'year',
							'sale_interval'  => 'year',
							'stripe_plan_id' => 'reg_stripe_annually',
						]
					),
				],
			],
			'subs_stripe_sale' => [
				'title'          => 'PublishPress Cart Regression - Subscription Stripe Sale',
				'on_sale'        => true,
				'disable_paypal' => true,
				'pay_options'    => [
					array_merge(
						$plan_defaults,
						[
							'option_id'        => 'reg_stripe_monthly_sale',
							'option_name'      => 'Monthly',
							'sale_option_name' => 'Monthly (20% off)',
							'product_type'     => 'recurring',
							'price'            => '10',
							'sale_price'       => '8',
							'interval'         => 'month',
							'sale_interval'    => 'month',
							'stripe_plan_id'   => 'reg_stripe_monthly_sale',
						]
					),
					array_merge(
						$plan_defaults,
						[
							'option_id'        => 'reg_stripe_annually_sale',
							'option_name'      => 'Annually',
							'sale_option_name' => 'Annually (20% off)',
							'product_type'     => 'recurring',
							'price'            => '100',
							'sale_price'       => '80',
							'interval'         => 'year',
							'sale_interval'    => 'year',
							'stripe_plan_id'   => 'reg_stripe_annually_sale',
						]
					),
				],
			],
			'custom_price_stripe' => [
				'title'          => 'PublishPress Cart Regression - Custom Price Stripe',
				'disable_paypal' => true,
				'pay_options'    => [
					array_merge(
						$plan_defaults,
						[
							'option_id'                => 'reg_stripe_pwyw',
							'option_name'              => 'Name your price',
							'product_type'             => 'pwyw',
							'price'                    => '1',
							'name_your_own_price_text' => 'Suggested $25',
							'stripe_plan_id'           => 'reg_stripe_pwyw',
						]
					),
				],
			],
			'one_time_paypal' => [
				'title'          => 'PublishPress Cart Regression - One Time PayPal',
				'disable_stripe' => true,
				'pay_options'    => [
					array_merge(
						$plan_defaults,
						[
							'option_id'      => 'reg_paypal_11',
							'option_name'    => 'One payment of $11',
							'price'          => '11',
							'stripe_plan_id' => 'reg_paypal_11',
						]
					),
					array_merge(
						$plan_defaults,
						[
							'option_id'      => 'reg_paypal_22',
							'option_name'    => 'One payment of $22',
							'price'          => '22',
							'stripe_plan_id' => 'reg_paypal_22',
						]
					),
				],
			],
			'one_time_paypal_sale' => [
				'title'          => 'PublishPress Cart Regression - One Time PayPal Sale',
				'on_sale'        => true,
				'disable_stripe' => true,
				'pay_options'    => [
					array_merge(
						$plan_defaults,
						[
							'option_id'        => 'reg_paypal_sale_11',
							'option_name'      => 'One payment of $11',
							'sale_option_name' => 'One payment of $9 (20% off)',
							'price'            => '11',
							'sale_price'       => '9',
							'stripe_plan_id'   => 'reg_paypal_sale_11',
						]
					),
					array_merge(
						$plan_defaults,
						[
							'option_id'        => 'reg_paypal_sale_22',
							'option_name'      => 'One payment of $22',
							'sale_option_name' => 'One payment of $18 (20% off)',
							'price'            => '22',
							'sale_price'       => '18',
							'stripe_plan_id'   => 'reg_paypal_sale_22',
						]
					),
				],
			],
			'subs_paypal' => [
				'title'          => 'PublishPress Cart Regression - Subscription PayPal',
				'disable_stripe' => true,
				'pay_options'    => [
					array_merge(
						$plan_defaults,
						[
							'option_id'      => 'reg_paypal_monthly',
							'option_name'    => 'Monthly',
							'product_type'   => 'recurring',
							'price'          => '11',
							'interval'       => 'month',
							'sale_interval'  => 'month',
							'stripe_plan_id' => 'reg_paypal_monthly',
						]
					),
					array_merge(
						$plan_defaults,
						[
							'option_id'      => 'reg_paypal_annually',
							'option_name'    => 'Annually',
							'product_type'   => 'recurring',
							'price'          => '110',
							'interval'       => 'year',
							'sale_interval'  => 'year',
							'stripe_plan_id' => 'reg_paypal_annually',
						]
					),
				],
			],
			'subs_paypal_sale' => [
				'title'          => 'PublishPress Cart Regression - Subscription PayPal Sale',
				'on_sale'        => true,
				'disable_stripe' => true,
				'pay_options'    => [
					array_merge(
						$plan_defaults,
						[
							'option_id'        => 'reg_paypal_monthly_sale',
							'option_name'      => 'Monthly',
							'sale_option_name' => 'Monthly (20% off)',
							'product_type'     => 'recurring',
							'price'            => '11',
							'sale_price'       => '9',
							'interval'         => 'month',
							'sale_interval'    => 'month',
							'stripe_plan_id'   => 'reg_paypal_monthly_sale',
						]
					),
					array_merge(
						$plan_defaults,
						[
							'option_id'        => 'reg_paypal_annually_sale',
							'option_name'      => 'Annually',
							'sale_option_name' => 'Annually (20% off)',
							'product_type'     => 'recurring',
							'price'            => '110',
							'sale_price'       => '88',
							'interval'         => 'year',
							'sale_interval'    => 'year',
							'stripe_plan_id'   => 'reg_paypal_annually_sale',
						]
					),
				],
			],
			'custom_price_paypal' => [
				'title'          => 'PublishPress Cart Regression - Custom Price PayPal',
				'disable_stripe' => true,
				'pay_options'    => [
					array_merge(
						$plan_defaults,
						[
							'option_id'                => 'reg_paypal_pwyw',
							'option_name'              => 'Name your price',
							'product_type'             => 'pwyw',
							'price'                    => '1',
							'name_your_own_price_text' => 'Suggested $25',
							'stripe_plan_id'           => 'reg_paypal_pwyw',
						]
					),
				],
			],
			'product_free' => [
				'title'       => 'PublishPress Cart Regression - Free Product',
				'pay_options' => [
					array_merge(
						$plan_defaults,
						[
							'option_id'      => 'reg_free',
							'option_name'    => 'Free access',
							'product_type'   => 'free',
							'price'          => '0',
							'stripe_plan_id' => 'reg_free',
						]
					),
				],
			],
		];
	}

	/**
	 * @return array<string, array<string, string>>
	 */
	private static function page_definitions(): array {
		return [
			'url_one_time_stripe'                   => [
				'slug'         => 'regression-one-time-stripe',
				'title'        => 'PublishPress Cart Regression - One Time Stripe',
				'product_key'  => 'one_time_stripe',
			],
			'url_one_time_stripe_with_sale_price'   => [
				'slug'         => 'regression-one-time-stripe-sale',
				'title'        => 'PublishPress Cart Regression - One Time Stripe Sale',
				'product_key'  => 'one_time_stripe_sale',
			],
			'url_subs_stripe'                       => [
				'slug'         => 'regression-subs-stripe',
				'title'        => 'PublishPress Cart Regression - Subscription Stripe',
				'product_key'  => 'subs_stripe',
			],
			'url_subs_stripe_with_sale_price'       => [
				'slug'         => 'regression-subs-stripe-sale',
				'title'        => 'PublishPress Cart Regression - Subscription Stripe Sale',
				'product_key'  => 'subs_stripe_sale',
			],
			'url_custom_price_stripe'               => [
				'slug'         => 'regression-custom-price-stripe',
				'title'        => 'PublishPress Cart Regression - Custom Price Stripe',
				'product_key'  => 'custom_price_stripe',
			],
			'url_one_time_paypal'                   => [
				'slug'         => 'regression-one-time-paypal',
				'title'        => 'PublishPress Cart Regression - One Time PayPal',
				'product_key'  => 'one_time_paypal',
			],
			'url_one_time_paypal_with_sale_price'   => [
				'slug'         => 'regression-one-time-paypal-sale',
				'title'        => 'PublishPress Cart Regression - One Time PayPal Sale',
				'product_key'  => 'one_time_paypal_sale',
			],
			'url_subs_paypal'                       => [
				'slug'         => 'regression-subs-paypal',
				'title'        => 'PublishPress Cart Regression - Subscription PayPal',
				'product_key'  => 'subs_paypal',
			],
			'url_subs_paypal_with_sale_price'       => [
				'slug'         => 'regression-subs-paypal-sale',
				'title'        => 'PublishPress Cart Regression - Subscription PayPal Sale',
				'product_key'  => 'subs_paypal_sale',
			],
			'url_custom_price_paypal'               => [
				'slug'         => 'regression-custom-price-paypal',
				'title'        => 'PublishPress Cart Regression - Custom Price PayPal',
				'product_key'  => 'custom_price_paypal',
			],
			'url_product_free'                      => [
				'slug'         => 'regression-free',
				'title'        => 'PublishPress Cart Regression - Free Product',
				'product_key'  => 'product_free',
			],
		];
	}

	private static function env_key_for_url( string $config_key ): string {
		return strtoupper( $config_key );
	}
}
