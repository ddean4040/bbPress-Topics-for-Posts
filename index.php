<?php

/*
Plugin Name: bbPress Topics for Posts
Plugin URI: http://www.generalthreat.com/projects/bbpress-post-topics
Description: Give authors the option to replace the comments on a WordPress blog post with a topic from an integrated bbPress install
Author: David Dean
Version: 0.9
Revision Date: 01/04/2012
Requires at least: WP 3.0, bbPress 2.0-rc1
Tested up to: WP 3.3.1 , bbPress 2.0.2
Author URI: http://www.generalthreat.com/
*/

class BBP_PostTopics {
	
	/**
	 * Add the bbPress topic option to the Discussion meta box
	 */
	function display_topic_option( $post ) {
		
		/** Store the post being edited and restore it after looping over forums */
		global $post;
		$the_post = $post;

		if(!function_exists('bbp_has_forums')) {
			?><br /><p><?php _e('bbPress Topics for Posts has been enabled, but cannot detect your bbPress setup.','bbpress-post-topics'); ?></p><?php
			return;
		}
		
		$bbpress_topic_defaults = get_option( 'bbpress_discussion_defaults', array('enabled' => false, 'forum_id' => 0, 'hide_topic' => false) );
		
		$bbpress_topic_default_status	  = ($bbpress_topic_defaults['enabled'] == 'on');
		$bbpress_topic_default_forum	  = (int)$bbpress_topic_defaults['forum_id'];
		$bbpress_topic_default_hide_topic = ($bbpress_topic_defaults['hide_topic'] == 'on');
		
		$bbpress_topic_status	= (get_post_meta( $post->ID, 'use_bbpress_discussion_topic', true) == 'true') || get_post_meta( $post->ID, 'use_bbpress_discussion_topic', true) || $bbpress_topic_default_status;
		$bbpress_topic_hidden	= get_post_meta( $post->ID, 'bbpress_discussion_hide_topic', true) || $bbpress_topic_default_hide_topic;
		$bbpress_topic_slug		= get_post_meta( $post->ID, 'bbpress_discussion_topic_id', true);
		if($bbpress_topic_slug) {
			$bbpress_topic = bbp_get_topic( $bbpress_topic_slug);
			$bbpress_topic_slug = $bbpress_topic->post_name;
		}
		
		add_filter( 'bbp_has_forums_query', 'bbppt_remove_parent_forum_restriction' );
		$forums = bbp_has_forums();
		
		if(!$forums) {
			?><br /><p><?php _e('bbPress Topics for Posts has been enabled, but you have not created any forums yet.','bbpress-post-topics'); ?></p><?php
			return; 
		} 
		?>
		<br />
		<label for="bbpress_topic_status" class="selectit"><input name="bbpress_topic_status" type="checkbox" id="bbpress_topic_status" value="open" <?php checked($bbpress_topic_status); ?> /> <?php _e( 'Use a bbPress forum topic for comments on this post.', 'bbpress-post-topics' ); ?></label><br />
		<div id="bbpress_topic_status_options" style="display: <?php echo checked($bbpress_topic_status, true, false) ? 'block' : 'none' ?>;">
			 &mdash; <label for="bbpress_topic_slug"><?php _e('Use an existing topic:', 'bbpress-post-topics' ) ?> </label> <input type="text" name="bbpress_topic_slug" id="bbpress_topic_slug" value="<?php echo $bbpress_topic_slug ?>" />
			  - OR - <label for="bbpress_topic_forum"><?php _e('Create a new topic in forum:', 'bbpress-post-topics' ); ?></label>
			<select name="bbpress_topic_forum" id="bbpress_topic_forum">
				<option value="0" selected><?php _e('Select a Forum', 'bbpress-post-topics' ); ?></option>
				<?php while ( bbp_forums() ) : bbp_the_forum(); ?>
					<?php if(bbp_is_forum_category())	continue; ?>
					<option value="<?php echo bbp_get_forum_id() ?>" <?php selected($bbpress_topic_default_forum,bbp_get_forum_id()) ?>><?php if(bbp_get_forum_parent()) echo '&mdash; ' ?><?php echo bbp_get_forum_title(); ?></option>
				<?php endwhile; ?>
			</select><br />
			&mdash; <input type="checkbox" <?php checked($bbpress_topic_hidden) ?> name="bbpress_topic_hidden" id="bbpress_topic_hidden" /> <label for="bbpress_topic_hidden"><?php _e('Show only replies on the post page','bbpress-post-topics'); ?></label>
		</div>
		<script type="text/javascript">

			/** hide options when not checked */
			jQuery('#bbpress_topic_status').change(function() {
				if(jQuery(this).attr('checked')) {
					jQuery('#bbpress_topic_status_options').show();
				} else {
					jQuery('#bbpress_topic_status_options').hide();
				}
			});
			
			/** disable topic slug field when a forum is selected to prevent confusion */
			jQuery('#bbpress_topic_forum').change(function() {
				if(jQuery(this).val() != 0) {
					jQuery('#bbpress_topic_slug').attr('disabled','true');
				} else {
					jQuery('#bbpress_topic_slug').removeAttr('disabled');
				}
			});
			
		</script>
		<?php

		/** Restore the original post being edited */
		$post = $the_post;
	}
	
	/**
	 * Process the user's bbPress topic selections when the post is saved
	 */
	function process_topic_option( $post_ID, $post ) {

		/** Don't process on AJAX-based auto-drafts */
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;
		
		/** Don't process on initial page load auto drafts */
		if( $post->post_status == 'auto-draft' )
			return;

		/** Only process when the post is published */
		if( ! in_array( $post->post_status, apply_filters( 'bbppt_eligible_post_status', array( 'publish' ) ) ) )
			return;
		
		/** Only process for post types we specify */
		if( !in_array( $post->post_type, apply_filters( 'bbppt_eligible_post_types', array( 'post', 'page' ) ) ) ) {
			return;			
		}

		/**
		 * The user requested to use a bbPress topic for discussion
		 */
		if( isset($_POST['bbpress_topic_status']) && $_POST['bbpress_topic_status'] == 'open' ) {

			if(!function_exists('bbp_has_forums')) {
				?><br /><p><?php _e('bbPress Topics for Posts cannot process this request because it cannot detect your bbPress setup.','bbpress-post-topics'); ?></p><?php
				return;
			}

			$topic_slug   = isset($_POST['bbpress_topic_slug']) ? $_POST['bbpress_topic_slug'] : '' ;
			$topic_forum  = isset($_POST['bbpress_topic_forum']) ? (int)$_POST['bbpress_topic_forum'] : 0 ;
			$topic_hidden = isset($_POST['bbpress_topic_hidden']) ? true : false ;
			
			if($topic_slug != '') {
				/** if user has selected an existing topic */
				
				if(is_numeric($topic_slug)) {
					$topic = bbp_get_topic( (int)$topic_slug );
				} else {
					$topic = bbppt_get_topic_by_slug( $topic_slug );
				}
				
				if($topic == null) {
					// return an error of some kind
					wp_die(__('There was an error with your selected topic.','bbpress-post-topics'),__('Error Locating bbPress Topic','bbpress-post-topics'));
				} else {
					$topic_ID = $topic->ID;
					update_post_meta( $post_ID, 'use_bbpress_discussion_topic', true );
					update_post_meta( $post_ID, 'bbpress_discussion_topic_id', $topic_ID );
					update_post_meta( $post_ID, 'bbpress_discussion_hide_topic', $topic_hidden );
				}
				
			} else if($topic_forum != 0) {
				/** if user has opted to create a new topic */
				
				$topic_content = ($post->post_excerpt != '') ? apply_filters('the_excerpt', $post->post_excerpt) : bbppt_post_discussion_get_the_content($post->post_content, 150) ;
				$topic_content .= "<br />" . sprintf( __('[See the full post at: <a href="%s">%s</a>]','bbpress-post-topics'), get_permalink( $post_ID), get_permalink( $post_ID) );

				$topic_content = apply_filters( 'bbppt_topic_content', $topic_content, $post_ID );
				
				$new_topic_data = array(
					'post_parent'   => $topic_forum,
					'post_author'   => $post->post_author,
					'post_content'  => $topic_content,
					'post_title'    => $post->post_title,
				);
				
				$new_topic_meta = array(
					'forum_id'			=> $topic_forum
				);
				
				$new_topic = bbp_insert_topic( $new_topic_data, $new_topic_meta );
				if(!$new_topic) {
					// return an error of some kind
					wp_die(__('There was an error creating a new topic.','bbpress-post-topics'),__('Error Creating bbPress Topic','bbpress-post-topics'));
				} else {
					update_post_meta( $post_ID, 'use_bbpress_discussion_topic', true );
					update_post_meta( $post_ID, 'bbpress_discussion_topic_id', $new_topic );
					update_post_meta( $post_ID, 'bbpress_discussion_hide_topic', $topic_hidden );
				}
				
			}
		} else {
			delete_post_meta( $post_ID, 'use_bbpress_discussion_topic' );
			delete_post_meta( $post_ID, 'bbpress_discussion_topic_id' );
			delete_post_meta( $post_ID, 'bbpress_discussion_hide_topic' );
		}
	}
	
	/**
	 * Display the bbPress topic plugin template instead of the WordPress comments template
	 */
	function maybe_change_comments_template( $template ) {

		global $post, $bbp;

		if(!function_exists('bbp_has_forums'))	return $template;
		
		if(get_post_meta( $post->ID, 'use_bbpress_discussion_topic', true)) {
			$topic_ID = get_post_meta( $post->ID, 'bbpress_discussion_topic_id', true); 
			if(file_exists(dirname( __FILE__ ) . '/templates/' . 'comments-bbpress.php')) {
				$bbp->topic_query->post->ID = $topic_ID;
				
				if(get_post_meta( $post->ID, 'bbpress_discussion_hide_topic', true)) {
					add_filter( 'bbp_has_replies_query', 'bbppt_remove_topic_from_thread' );
				}
				
		 		return dirname( __FILE__ ) . '/templates/' . 'comments-bbpress.php';
			}
		}
		return $template;
	}
	
	/**
	 * If a topic has been used for a post, give the number of replies in place of comment count
	 */
	function maybe_change_comments_number( $number, $post_ID ) {
		
		if(!function_exists('bbp_has_forums'))	return $number;
		
		if(get_post_meta( $post_ID, 'use_bbpress_discussion_topic', true)) {
			$topic_ID = get_post_meta( $post_ID, 'bbpress_discussion_topic_id', true);
			return bbp_get_topic_reply_count( $topic_ID );
		}
		
		return $number;
	}
	
	
	/****************************
	 * General Discussion options
	 */
	function add_discussion_page_settings() {
		register_setting( 'discussion', 'bbpress_discussion_defaults' );
		add_settings_field( 'bbpress_discussion_defaults', __('bbPress Topics for Posts Defaults','bbpress-post-topics'), array(&$this,'general_discussion_settings'), 'discussion', 'default', array('label_for'=>'bbpress_discussion_defaults_enabled') );
	}
	
	function general_discussion_settings() {
		$ex_options = get_option( 'bbpress_discussion_defaults' );
		
		add_filter( 'bbp_has_forums_query', 'bbppt_remove_parent_forum_restriction' );
		$forums = bbp_has_forums();
	
		$forum_options = array();
		$forum_options[0] = __('Select a Forum','bbpress-post-topics');
		while ( bbp_forums() ) : bbp_the_forum();
			if(bbp_is_forum_category())	continue;
			$forum_options[bbp_get_forum_id()] = (bbp_get_forum_parent() ? ' &mdash; ' : '') . bbp_get_forum_title();
		endwhile;
		
		$forum_select_string = '<select name="bbpress_discussion_defaults[forum_id]" id="bbpress_discussion_defaults_forum_id">';
		foreach($forum_options as $forum_ID => $forum_name) {
			$forum_select_string .= '<option value="' . $forum_ID . '" ' . selected( $ex_options['forum_id'], $forum_ID, false ) . '>' . $forum_name . '</option>';
		}
		$forum_select_string .= '</select>';
		
		?>
		<input type="checkbox" name="bbpress_discussion_defaults[enabled]" id="bbpress_discussion_defaults_enabled" <?php checked($ex_options['enabled'],'on') ?>>
		<label for="bbpress_discussion_defaults_enabled"><?php printf(__('Create a new bbPress topic in %s %s for new posts','bbpress-post-topics'), '</label>', $forum_select_string); ?><br />
		<input type="checkbox" name="bbpress_discussion_defaults[hide_topic]" id="bbpress_discussion_defaults_hide_topic" <?php checked($ex_options['hide_topic'],'on') ?> /><label for="bbpress_discussion_defaults_hide_topic"><?php _e('Show only replies on the post page','bbpress-post-topics'); ?></label>
		<?php
	}
}

$bbp_post_topics = new BBP_PostTopics;

add_action( 'post_comment_status_meta_box-options', array(&$bbp_post_topics,'display_topic_option') );
add_action( 'save_post', array(&$bbp_post_topics,'process_topic_option'), 10, 2 );
add_action( 'admin_init', array(&$bbp_post_topics, 'add_discussion_page_settings') );
add_filter( 'comments_template', array(&$bbp_post_topics,'maybe_change_comments_template') );
add_filter( 'get_comments_number', array(&$bbp_post_topics,'maybe_change_comments_number'), 10, 2 );

/****************************
 * Utility functions
 */

/**
 * Check for a bbPress topic with a post_name matching the slug provided
 * @param string Post slug
 * @return object|NULL the topic or NULL if not found
 */
function bbppt_get_topic_by_slug( $slug ) {
	
	global $bbp, $wpdb;
	
	$topic = $wpdb->get_row( $wpdb->prepare('SELECT ID, post_name, post_parent FROM ' . $wpdb->posts .  ' WHERE post_name = %s AND post_type = %s', $slug, bbp_get_topic_post_type()) );
	
	if(is_null($topic))	return $topic;
	return $topic;
}

/**
 * Filter and limit the content for use in the bbPress topic
 * @param text $content Post content to be filtered
 * @param int $cut # of characters to keep in the exceprt (set to 0 for whole post)
 * @return text filtered content
 */
function bbppt_post_discussion_get_the_content( $content, $cut = 0 ) {
	$content = strip_shortcodes( $content );
	$content = wp_html_excerpt( $content, $cut );
	
	/** The `the_content_rss` filter will be removed in a future version! */
	$content = apply_filters('the_content_rss', $content);
	
	$content = apply_filters( 'bbppt_topic_content_before_link', $content );
	return $content;
}

/**
 * Remove the `post_parent` field from the forum query to get a list of all available forums
 * Made for use with the bbp_has_forums_query filter
 */
function bbppt_remove_parent_forum_restriction( $bbp_args ) {
	
	$new_args = array();
	foreach($bbp_args as $key => $arg) {
		if($key != 'post_parent') {
			$new_args[$key] = $arg;
		}
	}
	
	return $new_args;
}

function bbppt_remove_topic_from_thread( $bbp_args ) {
	$bbp_args['post_type'] = bbp_get_reply_post_type();
	return $bbp_args;
}

?>