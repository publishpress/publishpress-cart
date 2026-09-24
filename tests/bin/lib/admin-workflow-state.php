<?php

declare(strict_types=1);

final class PPCart_Admin_Workflow_State {

	const SNAPSHOT_OPTION = 'ppcart_admin_workflow_snapshots';

	public static function prepare( string $run_id ): void {
		self::cleanup( $run_id );

		$snapshots = get_option( self::SNAPSHOT_OPTION, array() );

		if ( ! is_array( $snapshots ) ) {
			$snapshots = array();
		}

		$snapshots[ $run_id ] = array(
			'company_name'    => get_option('_ppcart_company_name', null ),
			'company_address' => get_option('_ppcart_company_address', null ),
		);

		update_option( self::SNAPSHOT_OPTION, $snapshots, false );
	}

	/**
	 * @return array<string, int|bool>
	 */
	public static function cleanup( string $run_id ): array {
		global $wpdb;

		$meta_product_title = (string) getenv( 'ADMIN_WORKFLOW_META_PRODUCT_TITLE' );
		$product_titles     = array_values(
			array_filter(
				array(
					(string) getenv( 'ADMIN_WORKFLOW_PRODUCT_TITLE' ),
					$meta_product_title,
					'' !== $meta_product_title ? $meta_product_title . ' Hide Title' : '',
				)
			)
		);
		$category_slug = (string) getenv( 'ADMIN_WORKFLOW_CATEGORY_SLUG' );
		$tag_slug      = (string) getenv( 'ADMIN_WORKFLOW_TAG_SLUG' );
		$removed       = array(
			'products'          => 0,
			'categories'        => 0,
			'tags'              => 0,
			'branding_restored' => false,
		);

		if ( $product_titles ) {
			$product_type = function_exists( 'ppcart_live_post_type' ) ? ppcart_live_post_type( 'product' ) : 'ppcart_product';
			foreach ( $product_titles as $product_title ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Exact cleanup for an isolated test record.
				$product_ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_title = %s",
						$product_type,
						$product_title
					)
				);

				foreach ( $product_ids as $product_id ) {
					if ( wp_delete_post( (int) $product_id, true ) ) {
						++$removed['products'];
					}
				}
			}
		}

		$category_taxonomy = function_exists( 'ppcart_live_taxonomy' ) ? ppcart_live_taxonomy( 'product_cat' ) : 'ppcart_product_cat';
		$tag_taxonomy      = function_exists( 'ppcart_live_taxonomy' ) ? ppcart_live_taxonomy( 'product_tag' ) : 'ppcart_product_tag';
		$removed['categories'] = self::delete_term_by_slug( $category_slug, $category_taxonomy ) ? 1 : 0;
		$removed['tags']       = self::delete_term_by_slug( $tag_slug, $tag_taxonomy ) ? 1 : 0;

		$snapshots = get_option( self::SNAPSHOT_OPTION, array() );

		if ( is_array( $snapshots ) && isset( $snapshots[ $run_id ] ) && is_array( $snapshots[ $run_id ] ) ) {
			self::restore_option( '_sc_company_name', $snapshots[ $run_id ]['company_name'] ?? null );
			self::restore_option( '_sc_company_address', $snapshots[ $run_id ]['company_address'] ?? null );
			unset( $snapshots[ $run_id ] );
			$removed['branding_restored'] = true;

			if ( array() === $snapshots ) {
				delete_option( self::SNAPSHOT_OPTION );
			} else {
				update_option( self::SNAPSHOT_OPTION, $snapshots, false );
			}
		}

		return $removed;
	}

	private static function delete_term_by_slug( string $slug, string $taxonomy ): bool {
		if ( '' === $slug ) {
			return false;
		}

		$term = get_term_by( 'slug', $slug, $taxonomy );

		if ( ! $term instanceof WP_Term ) {
			return false;
		}

		return ! is_wp_error( wp_delete_term( $term->term_id, $taxonomy ) );
	}

	/**
	 * @param mixed $value
	 */
	private static function restore_option( string $name, $value ): void {
		if ( null === $value ) {
			delete_option( $name );
			return;
		}

		update_option( $name, $value );
	}
}
