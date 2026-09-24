<?php

if ( ! defined( 'PPCART_FIXTURES_ENABLED' ) ) {
	define( 'PPCART_FIXTURES_ENABLED', true );
}

if ( ! class_exists( 'PPCart_Admin_Fixtures' ) ) {
	throw new RuntimeException(
		'PublishPress Cart admin fixtures are unavailable. Activate the ppcart-fixtures plugin first.'
	);
}

echo wp_json_encode(
	PPCart_Admin_Fixtures::setup(),
	JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
);
