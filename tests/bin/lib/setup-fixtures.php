<?php

if ( ! defined( 'PPCART_FIXTURES_ENABLED' ) ) {
	define( 'PPCART_FIXTURES_ENABLED', true );
}

if ( ! class_exists( 'PPCart_Regression_Fixtures' ) ) {
	throw new RuntimeException(
		'PublishPress Cart regression fixtures are unavailable. Activate the ppcart-fixtures plugin first.'
	);
}

$result = PPCart_Regression_Fixtures::setup();

flush_rewrite_rules( false );

echo wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
