<?php

/**
Plugin Name: bbPress Topics for Posts
Plugin URI: http://www.jerseyconnect.net/development/bbpress-post-topics
Description: Give authors the option to create bbPress topics for posts
Author: David Dean
Version: 0.1
Author URI: http://www.generalthreat.com/
*/

class BBP_PostTopics {
	
	/**
	 * Add the bbPress topic option to the Discussion meta box
	 */
	function display_topic_option( $post ) {

		if(!function_exists('bbp_forums'))	return;

		$bbpress_topic_status = get_post_meta( $post->ID, 'use_bbpress_discussion_topic', true);
		$bbpress_topic_slug   = get_post_meta( $post->ID, 'bbpress_discussion_topic_id', true);
		if($bbpress_topic_slug) {
			$bbpress_topic = bbp_get_topic( $bbpress_topic_slug);
			$bbpress_topic_slug = $bbpress_topic->post_name;
		}
		
		echo $bbpress_topic_status;
		
		?>
		<br />
		<label for="bbpress_topic_status" class="selectit"><input name="bbpress_topic_status" type="checkbox" id="bbpress_topic_status" value="open" <?php checked($bbpress_topic_status, 'true'); ?> /> <?php _e( 'Use a bbPress forum topic for comments on this post.', '' ); ?></label><br />
		<div id="bbpress_topic_status_options" style="display: <?php echo checked($bbpress_topic_status, 'true', false) ? 'block' : 'none' ?>;">
			 &mdash; <label for="bbpress_topic_slug"><?php _e('Use an existing topic:','') ?> </label> <input type="text" name="bbpress_topic_slug" id="bbpress_topic_slug" value="<?php echo $bbpress_topic_slug ?>" />
			  - OR - <label for="bbpress_topic_forum"><?php _e('Create a new topic in forum:',''); ?></label>
			<?php bbp_has_forums(); ?>
			<select name="bbpress_topic_forum" id="bbpress_topic_forum">
				<option value="0" selected><?php _e('Select a Forum',''); ?></option>
				<?php while ( bbp_forums() ) : bbp_the_forum(); ?>
					<?php if(bbp_is_forum_category())	continue; ?>
					<option value="<?php echo bbp_get_forum_id() ?>"><?php echo bbp_get_forum_title(); ?></option>
				<?php endwhile; ?>
			</select>
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
	}
	
	/**
	 * Process the user's bbPress topic selections when the post is saved
	 */
	function process_topic_option( $post_ID, $post ) {

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		if( !in_array( $post->post_type, apply_filters( 'bbppt_eligible_post_types', array('post','page') ) ) ) {
			return;			
		}

		if(!function_exists('bbp_forums'))	return;


		/**
		 * The user requested to use a bbPress topic for discussion
		 */
		if( isset($_POST['bbpress_topic_status']) && $_POST['bbpress_topic_status'] == 'open' ) {

			$topic_slug  = isset($_POST['bbpress_topic_slug']) ? $_POST['bbpress_topic_slug'] : '' ;
			$topic_forum = isset($_POST['bbpress_topic_forum']) ? (int)$_POST['bbpress_topic_forum'] : 0 ;
			
			if($topic_slug != '') {
				/** if user has selected an existing topic */
				
//				echo 'Using existing topic: ' . $topic_slug . "<br />\n";
				$topic = get_page_by_path($topic_slug, OBJECT, bbp_get_topic_post_type());
				if($topic == null) {
					// return an error of some kind
//					die('there was an error selecting the existing topic');
				} else {
					$topic_ID = $topic->ID;
					update_post_meta($post_ID, 'use_bbpress_discussion_topic','true');
					update_post_meta($post_ID, 'bbpress_discussion_topic_id',$topic_ID);
//					echo 'Setting post meta: bbpress_discussion_topic to: ' . $topic_ID . ' for post ID: ' . $post_ID . "<br />\n";
				}
				
			} else if($topic_forum != 0) {
				/** if user has opted to create a new topic */
				
				$topic_content = ($post->post_excerpt != '') ? apply_filters('the_excerpt', $post->post_excerpt) : bbppt_post_discussion_get_the_content($post->post_content, 25) ;
				$topic_content .= sprintf( __('[See the full post at: <a href="">%s</a>]'), get_permalink( $post_ID) );
				
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
					die('there was an error creating a new topic');
				} else {

					update_post_meta($post_ID, 'use_bbpress_discussion_topic','true');
					update_post_meta( $post_ID, 'bbpress_discussion_topic_id', $new_topic );
				}
				
			}
		} else {
			delete_post_meta( $post_ID, 'use_bbpress_discussion_topic' );
			delete_post_meta( $post_ID, 'bbpress_discussion_topic_id' );
		}
	}
	
	/**
	 * Display the bbPress topic plugin template instead of the WordPress comments template
	 */
	function maybe_change_comments_template( $template ) {

		global $post, $bbp;

		if(!function_exists('bbp_forums'))	return $template;
		
		if(get_post_meta( $post->ID, 'use_bbpress_discussion_topic', true)) {
			$topic_ID = get_post_meta( $post->ID, 'bbpress_discussion_topic_id', true); 
			if(file_exists(dirname( __FILE__ ) . '/templates/' . 'comments-bbpress.php')) {
//				bbp_has_topics( array('ID'=> $topic_ID) );
				$bbp->topic_query->post->ID = $topic_ID;
		 		return dirname( __FILE__ ) . '/templates/' . 'comments-bbpress.php';
			}
		}
		return $template;
	}
	
	
}

$bbp_post_topics = new BBP_PostTopics;

add_action( 'post_comment_status_meta_box-options', array(&$bbp_post_topics,'display_topic_option') );
add_action( 'save_post', array(&$bbp_post_topics,'process_topic_option'), 10, 2 );
add_filter( 'comments_template', array(&$bbp_post_topics,'maybe_change_comments_template') );

/**
 * Filter and limit the content for use in the bbPress topic
 */
function bbppt_post_discussion_get_the_content( $content, $cut = 0, $encode_html = 0 ) {
	$content = apply_filters('the_content_rss', $content);
	if ( $cut && !$encode_html )
		$encode_html = 2;
	if ( 1== $encode_html ) {
		$content = esc_html($content);
		$cut = 0;
	} elseif ( 0 == $encode_html ) {
		$content = make_url_footnote($content);
	} elseif ( 2 == $encode_html ) {
		$content = strip_tags($content);
	}
	if ( $cut ) {
		$blah = explode(' ', $content);
		if ( count($blah) > $cut ) {
			$k = $cut;
			$use_dotdotdot = 1;
		} else {
			$k = count($blah);
			$use_dotdotdot = 0;
		}

		for ( $i=0; $i<$k; $i++ )
			$excerpt .= $blah[$i].' ';
		$excerpt .= ($use_dotdotdot) ? '...' : '';
		$content = $excerpt;
	}
	$content = str_replace(']]>', ']]&gt;', $content);
	return $content;
}
?>