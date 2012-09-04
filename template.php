<?php

/** 
 * Template functions for determining whether posts have topics, accessing topic data, etc. 
 */

function bbppt_post_has_topic( $post = null ) {
	
	$post = get_post( $post );
	if( empty( $post ) )	return false;

	return get_post_meta( $post->ID, 'use_bbpress_discussion_topic', true );
	
}

function bbppt_post_get_topic( $post = null ) {

	$post = get_post( $post );
	if( empty( $post ) )	return false;
	
	if( get_post_meta( $post->ID, 'use_bbpress_discussion_topic', true ) ) {
		return get_post_meta( $post->ID, 'bbpress_discussion_topic_id', true );
	}
	return false;
	
}

?>
