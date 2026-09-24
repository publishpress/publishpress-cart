<?php

if ( ! class_exists( 'PPCart_Admin_Fixtures' ) ) {
	echo wp_json_encode(
		[
			'products'      => 0,
			'orders'        => 0,
			'subscriptions' => 0,
			'users'         => 0,
			'skipped'       => true,
		],
		JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
	);
	return;
}

echo wp_json_encode(
	PPCart_Admin_Fixtures::cleanup(),
	JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
);
