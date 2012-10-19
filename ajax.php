<?php

/**
 * AJAX function declarations
 */

add_action( 'wp_ajax_bbppt_import_old_posts', 'bbppt_ajax_process_existing_posts' );

/**
 * AJAX wrapper to return helpful text
 */
function bbppt_ajax_process_existing_posts() {
	
	if( bbppt_process_existing_posts() ) {
		_e('Processing completed successfully','bbpress-post-topics');
	} else {
		_e('There was an error processing your posts','bbpress-post-topics');
	}
	die();
	
}

?>
