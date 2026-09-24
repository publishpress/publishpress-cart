<?php

require_once __DIR__ . '/admin-workflow-state.php';

$run_id = sanitize_key( (string) getenv( 'ADMIN_WORKFLOW_RUN_ID' ) );

if ( '' === $run_id ) {
	throw new RuntimeException( 'ADMIN_WORKFLOW_RUN_ID is required.' );
}

echo wp_json_encode(
	PPCart_Admin_Workflow_State::cleanup( $run_id ),
	JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
);
