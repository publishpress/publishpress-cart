<?php

declare(strict_types=1);

/**
 * Playwright smoke-suite fixtures (Ghost Inspector JSON → ST-* specs).
 */
final class PPCart_Smoke_Fixtures {

	const REGISTRY_OPTION = 'ppcart_smoke_fixtures_registry';

	const COUPON_100_CODE = 'SMOKE100';

	/**
	 * @param array<string, int> $regression_products Existing regression product IDs keyed by fixture name.
	 * @return array{products: array<string, int>, pages: array<string, array<string, int|string>>, paths: array<string, string>, env: array<string, string>}
	 */
	public static function setup( array $regression_products = [] ): array {
		self::cleanup();
		self::ensure_global_options();

		$products = self::create_products( $regression_products );
		$pages    = self::create_pages( $products, $regression_products );

		$registry = [
			'created_at' => time(),
			'products'   => $products,
			'pages'      => $pages,
		];

		update_option( self::REGISTRY_OPTION, $registry, false );

		$paths = [];
		foreach ( $pages as $env_key => $page ) {
			$paths[ $env_key ] = (string) $page['path'];
		}
		$paths['smoke_admin_path'] = '/wp-admin/';

		return [
			'products' => $products,
			'pages'    => $pages,
			'paths'    => $paths,
			'env'      => self::env_defaults(),
		];
	}

	/**
	 * @return array<string, int>
	 */
	public static function cleanup(): array {
		$registry = get_option( self::REGISTRY_OPTION, [] );
		$removed  = [
			'products' => 0,
			'pages'    => 0,
		];

		$slugs = [];
		foreach ( self::page_definitions() as $definition ) {
			$slugs[] = (string) ( $definition['slug'] ?? '' );
		}
		$removed['pages'] += ppcart_fixtures_delete_pages_by_slugs( $slugs );

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

	private static function ensure_global_options(): void {
		update_option('_ppcart_terms_url', 'https://example.com/terms' );
		update_option('_ppcart_tax_enable', '1' );
		update_option('_ppcart_tax_type', 'exclusive_tax' );
		update_option('_ppcart_price_show_with_tax', 'exclude_tax' );
		self::ensure_smoke_tax_rate();
	}

	private static function ensure_smoke_tax_rate(): void {
		if ( ! class_exists( 'PPCart_Tax' ) ) {
			return;
		}

		global $wpdb;

		$table = ppcart_live_table('tax_rate');
		$title = 'Smoke CA Sales Tax';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Resets the dedicated smoke tax rate row.
		$wpdb->delete( $table, [ 'tax_rate_title' => $title ] );

		PPCart_Tax::insert_tax_rate(
			[
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => 'CA',
				'tax_rate_postcode' => '94105',
				'tax_rate_city'     => 'San Francisco',
				'tax_rate'          => '8.5',
				'tax_rate_title'    => $title,
				'tax_rate_priority' => 1,
			]
		);
	}

	/**
	 * @return array<string, string>
	 */
	private static function env_defaults(): array {
		return [
			'COUPON_100_CODE'          => self::COUPON_100_CODE,
			'EXPECTED_PRODUCT_PRICE'   => 'One payment of $10',
			'TAXABLE_ADDRESS_LINE1'    => '123 Market St',
			'TAXABLE_CITY'             => 'San Francisco',
			'TAXABLE_POSTAL_CODE'      => '94105',
			'SMOKE_ADMIN_PATH'         => '/wp-admin/',
		];
	}

	/**
	 * @param array<string, int> $regression_products
	 * @return array<string, int>
	 */
	private static function create_products( array $regression_products ): array {
		$definitions = self::product_definitions( $regression_products );
		$products    = [];

		foreach ( $definitions as $key => $definition ) {
			if ( ! empty( $definition['reuse_product_key'] ) ) {
				$reuse_key = (string) $definition['reuse_product_key'];
				$reuse_id  = $regression_products[ $reuse_key ] ?? 0;

				if ( $reuse_id > 0 ) {
					$products[ $key ] = (int) $reuse_id;
					continue;
				}
			}

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

			self::apply_product_definition( (int) $product_id, $definition, $products );
			$products[ $key ] = (int) $product_id;
		}

		if ( isset( $products['smoke_bump_main'], $products['smoke_bump_offer'] ) ) {
			self::link_order_bump( $products['smoke_bump_main'], $products['smoke_bump_offer'] );
		}

		self::sync_stripe_products( $products );

		return $products;
	}

	/**
	 * @param array<string, int> $products
	 * @param array<string, int> $regression_products
	 * @return array<string, array<string, int|string>>
	 */
	private static function create_pages( array $products, array $regression_products ): array {
		$pages = [];

		foreach ( self::page_definitions() as $env_key => $definition ) {
			if ( ! empty( $definition['static_html'] ) ) {
				$page_id = wp_insert_post(
					[
						'post_type'    => 'page',
						'post_status'  => 'publish',
						'post_title'   => $definition['title'],
						'post_name'    => $definition['slug'],
						'post_content' => (string) $definition['static_html'],
					],
					true
				);
			} else {
				$product_key = (string) $definition['product_key'];
				$product_id  = $products[ $product_key ] ?? ( $regression_products[ $product_key ] ?? 0 );

				if ( $product_id <= 0 ) {
					throw new RuntimeException( sprintf( 'Missing smoke product: %s', $product_key ) );
				}

				$content = sprintf( '[ppcart_form id="%d"]', $product_id );

				if ( ! empty( $definition['shortcode_coupon'] ) ) {
					$content = sprintf(
						'[ppcart_form id="%d" coupon="%s"]',
						$product_id,
						$definition['shortcode_coupon']
					);
				}

				$page_id = wp_insert_post(
					[
						'post_type'    => 'page',
						'post_status'  => 'publish',
						'post_title'   => $definition['title'],
						'post_name'    => $definition['slug'],
						'post_content' => $content,
					],
					true
				);
			}

			if ( is_wp_error( $page_id ) ) {
				throw new RuntimeException( $page_id->get_error_message() );
			}

			$permalink = get_permalink( $page_id );
			$path      = $permalink ? (string) wp_parse_url( $permalink, PHP_URL_PATH ) : '/' . $definition['slug'] . '/';

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
	 * @param array<string, int>   $products
	 */
	private static function apply_product_definition( int $product_id, array $definition, array $products ): void {
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

		if ( ! empty( $definition['disable_paypal'] ) ) {
			ppcart_fixtures_update_post_meta( $product_id, 'disable_paypal', '1' );
		}

		if ( ! empty( $definition['disable_stripe'] ) ) {
			ppcart_fixtures_update_post_meta( $product_id, 'disable_stripe', '1' );
		}

		if ( ! empty( $definition['show_coupon_field'] ) ) {
			ppcart_fixtures_update_post_meta( $product_id, 'show_coupon_field', '1' );
		}

		if ( ! empty( $definition['coupons'] ) && is_array( $definition['coupons'] ) ) {
			ppcart_fixtures_update_post_meta( $product_id, 'coupons', $definition['coupons'] );
		}

		if ( ! empty( $definition['terms_required'] ) ) {
			if ( function_exists( 'ppcart_delete_post_meta' ) ) {
				ppcart_delete_post_meta( $product_id, 'terms_setting' );
			} else {
				delete_post_meta( $product_id, '_ppcart_terms_setting' );
			}
		} else {
			ppcart_fixtures_update_post_meta( $product_id, 'terms_setting', 'off' );
		}

		if ( ! empty( $definition['product_taxable'] ) ) {
			ppcart_fixtures_update_post_meta( $product_id, 'product_taxable', 'tax' );
		}

		if ( ! empty( $definition['show_address_fields'] ) ) {
			ppcart_fixtures_update_post_meta( $product_id, 'show_address_fields', '1' );
		}

		ppcart_fixtures_update_post_meta( $product_id, '_ppcart_pay_options', $definition['pay_options'] );
	}

	private static function link_order_bump( int $main_product_id, int $bump_product_id ): void {
		ppcart_fixtures_update_post_meta( $main_product_id, 'order_bump', '1' );
		ppcart_fixtures_update_post_meta( $main_product_id, 'ob_product', (string) $bump_product_id );
		ppcart_fixtures_update_post_meta( $main_product_id, 'ob_price', '5' );
		ppcart_fixtures_update_post_meta(
			$main_product_id,
			'order_bump_options',
			[
				[
					'order_bump'     => '1',
					'ob_product'     => (string) $bump_product_id,
					'ob_price'       => '5',
					'ob_headline'    => 'Add this bonus',
					'ob_description' => 'Special smoke order bump',
					'ob_cb_label'    => 'Yes, add this bump',
				],
			]
		);
	}

	/**
	 * @param array<string, int> $regression_products
	 * @return array<string, array<string, mixed>>
	 */
	private static function product_definitions( array $regression_products ): array {
		$plan_defaults = [
			'frequency'         => '1',
			'sale_frequency'    => '1',
			'interval'          => 'day',
			'sale_interval'     => 'day',
			'installments'      => '-1',
			'sale_installments' => '-1',
		];

		$stripe_plan = array_merge(
			$plan_defaults,
			[
				'option_id'      => 'smoke_stripe_10',
				'option_name'    => 'One payment of $10',
				'price'          => '10',
				'stripe_plan_id' => 'smoke_stripe_10',
			]
		);

		$bump_plan = array_merge(
			$plan_defaults,
			[
				'option_id'      => 'smoke_bump_5',
				'option_name'    => 'Bump offer $5',
				'price'          => '5',
				'stripe_plan_id' => 'smoke_bump_5',
			]
		);

		return [
			'smoke_stripe_single' => [
				'title'          => 'PublishPress Cart Smoke - Stripe Single Plan',
				'disable_paypal' => true,
				'pay_options'    => [ $stripe_plan ],
			],
			'smoke_paypal'        => [
				'title'            => 'PublishPress Cart Smoke - PayPal One Time',
				'reuse_product_key' => 'one_time_paypal',
			],
			'smoke_subscription'  => [
				'title'            => 'PublishPress Cart Smoke - Subscription',
				'reuse_product_key' => 'subs_stripe',
			],
			'smoke_coupon'        => [
				'title'             => 'PublishPress Cart Smoke - 100 Percent Coupon',
				'disable_paypal'    => true,
				'show_coupon_field' => true,
				'coupons'           => [
					self::smoke_coupon( self::COUPON_100_CODE, 'cart-percent', '100' ),
				],
				'pay_options'       => [
					array_merge(
						$plan_defaults,
						[
							'option_id'      => 'smoke_coupon_free',
							'option_name'    => 'Free access',
							'product_type'   => 'free',
							'price'          => '0',
							'stripe_plan_id' => 'smoke_coupon_free',
						]
					),
				],
			],
			'smoke_terms'         => [
				'title'          => 'PublishPress Cart Smoke - Terms Required',
				'disable_paypal' => true,
				'terms_required' => true,
				'pay_options'    => [ $stripe_plan ],
			],
			'smoke_tax'           => [
				'title'               => 'PublishPress Cart Smoke - Manual Tax',
				'disable_paypal'      => true,
				'product_taxable'     => true,
				'show_address_fields' => true,
				'pay_options'         => [ $stripe_plan ],
			],
			'smoke_bump_main'     => [
				'title'          => 'PublishPress Cart Smoke - Main With Bump',
				'disable_paypal' => true,
				'pay_options'    => [ $stripe_plan ],
			],
			'smoke_bump_offer'    => [
				'title'          => 'PublishPress Cart Smoke - Bump Offer',
				'disable_paypal' => true,
				'pay_options'    => [ $bump_plan ],
			],
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function smoke_coupon( string $code, string $type, string $amount ): array {
		return [
			'code'      => $code,
			'type'      => $type,
			'amount'    => $amount,
			'limit'     => '',
			'expires'   => '',
			'stripe_id' => 'smoke_' . strtolower( $code ),
		];
	}

	/**
	 * @return array<string, array<string, string>>
	 */
	private static function page_definitions(): array {
		return [
			'checkout_stripe_url'       => [
				'slug'        => 'smoke-stripe',
				'title'       => 'PublishPress Cart Smoke - Stripe Checkout',
				'product_key' => 'smoke_stripe_single',
			],
			'checkout_paypal_url'       => [
				'slug'        => 'smoke-paypal',
				'title'       => 'PublishPress Cart Smoke - PayPal Checkout',
				'product_key' => 'smoke_paypal',
			],
			'checkout_subscription_url' => [
				'slug'        => 'smoke-subscription',
				'title'       => 'PublishPress Cart Smoke - Subscription Checkout',
				'product_key' => 'smoke_subscription',
			],
			'checkout_coupon_url'       => [
				'slug'             => 'smoke-coupon',
				'title'            => 'PublishPress Cart Smoke - Coupon Checkout',
				'product_key'      => 'smoke_coupon',
				'shortcode_coupon' => self::COUPON_100_CODE,
			],
			'checkout_terms_url'        => [
				'slug'        => 'smoke-terms',
				'title'       => 'PublishPress Cart Smoke - Terms Checkout',
				'product_key' => 'smoke_terms',
			],
			'checkout_tax_url'          => [
				'slug'        => 'smoke-tax',
				'title'       => 'PublishPress Cart Smoke - Tax Checkout',
				'product_key' => 'smoke_tax',
			],
			'checkout_bump_url'         => [
				'slug'        => 'smoke-bump',
				'title'       => 'PublishPress Cart Smoke - Order Bump Checkout',
				'product_key' => 'smoke_bump_main',
			],
			'checkout_upsell_url'       => [
				'slug'        => 'smoke-upsell',
				'title'       => 'PublishPress Cart Smoke - Upsell Step',
				'static_html' => '<p>Smoke upsell placeholder</p><button type="button">Yes, add this offer</button>',
			],
		];
	}

	/**
	 * @param array<string, int> $products
	 */
	private static function sync_stripe_products( array $products ): void {
		if ( '1' !== get_option('_ppcart_stripe_enable' ) ) {
			return;
		}

		if ( function_exists('ppcart_setup_stripe') ) {
			ppcart_setup_stripe();
		}

		global $ppcart_stripe;

		if ( empty( $ppcart_stripe ) || empty( $ppcart_stripe['sk'] ) ) {
			return;
		}

		if ( ! class_exists( 'PPCart_Product_Admin' ) ) {
			if ( ! defined( 'PPCART_BASE_DIR' ) ) {
				return;
			}

			$admin_file = PPCART_BASE_DIR . 'admin/class-ppcart-product-admin.php';

			if ( ! is_readable( $admin_file ) ) {
				return;
			}

			require_once $admin_file;
		}

		$stripe_product_admin = new PPCart_Product_Admin();
		$definitions          = self::product_definitions( [] );

		foreach ( $products as $key => $product_id ) {
			$definition = $definitions[ $key ] ?? null;

			if ( ! $definition || ! empty( $definition['reuse_product_key'] ) || ! empty( $definition['disable_stripe'] ) ) {
				continue;
			}

			$pay_options = ppcart_fixtures_get_pay_options( (int) $product_id );

			if ( ! is_array( $pay_options ) ) {
				continue;
			}

			$stripe_product_admin->save_stripe_objects(
				(int) $product_id,
				[
					ppcart_fixtures_pay_options_objects_key() => $pay_options,
				]
			);
		}
	}
}
