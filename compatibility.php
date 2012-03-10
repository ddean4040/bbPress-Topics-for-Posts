<?php
/*
 * Compatiblity functions for bbPress 2.0.x - 2.1
 */

function bbppt_get_forum_parent_id( $forum_id = 0 ) {
	if( function_exists( 'bbp_get_forum_parent_id' ) ) {
		return bbp_get_forum_parent_id( $forum_id );
	}
	return bbp_get_forum_parent( $forum_id );
}


?>
