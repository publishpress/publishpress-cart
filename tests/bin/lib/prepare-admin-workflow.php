<?php

require_once __DIR__ . '/admin-workflow-state.php';

$run_id = sanitize_key( (string) getenv( 'ADMIN_WORKFLOW_RUN_ID' ) );

if ( '' === $run_id ) {
	throw new RuntimeException( 'ADMIN_WORKFLOW_RUN_ID is required.' );
}

PPCart_Admin_Workflow_State::prepare( $run_id );

echo wp_json_encode( array( 'prepared' => true, 'run_id' => $run_id ) );
