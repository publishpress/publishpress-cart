<?php

if ( ! class_exists( 'PPCart_Regression_Fixtures' ) ) {
	echo wp_json_encode(
		[
			'products' => 0,
			'pages'    => 0,
			'skipped'  => true,
		],
		JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
	);
	return;
}

$result = PPCart_Regression_Fixtures::cleanup();

flush_rewrite_rules( false );

echo wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
