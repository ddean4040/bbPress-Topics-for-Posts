<?php

/*
Plugin Name: bbPress Topics for Posts
Plugin URI: http://www.generalthreat.com/projects/bbpress-post-topics
Description: Give authors the option to replace the comments on a WordPress blog post with a topic from an integrated bbPress install
Author: David Dean
Version: 1.5-testing
Revision Date: 08/19/2012
Requires at least: WP 3.0, bbPress 2.0
Tested up to: WP 3.4.1 , bbPress 2.1.2
Author URI: http://www.generalthreat.com/
*/

include dirname( __FILE__ ) . '/compatibility.php';
include dirname( __FILE__ ) . '/template.php';
include dirname( __FILE__ ) . '/ajax.php';

/** load localization files if present */
if( file_exists( dirname( __FILE__ ) . '/' . dirname(plugin_basename(__FILE__)) . '-' . get_locale() . '.mo' ) ) {
	load_plugin_textdomain( 'bbpress-post-topics', false, dirname(plugin_basename(__FILE__)) . '' );
}

class BBP_PostTopics {
	
	var $topic_ID = false;
	var $xmlrpc_post = false;
	
	/**
	 * Create the bbPress Topics for Posts meta box for post types defined by
	 * 'bbppt_eligible_post_types' filter
	 */
	function add_meta_box() {

		foreach( apply_filters( 'bbppt_eligible_post_types', array( 'post', 'page' )) as $post_type ) {
			add_meta_box(
				'bbpress-post-topic',
				__('bbPress Topic for this Post', 'bbpress-post-topics'),
				array( &$this, 'display_meta_box' ),
				$post_type
			);
		}
	}
	
	function display_meta_box( $post ) {}
	
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
		
		$bbpress_topic_options = $this->get_topic_options_for_post( $post->ID );
		
		$bbpress_topic_status	= $bbpress_topic_options['enabled'] != false;
		$bbpress_topic_display	= $bbpress_topic_options['display'];
		$bbpress_topic_slug		= $bbpress_topic_options['topic_id'];
		
		if($bbpress_topic_slug) {
			$bbpress_topic = bbp_get_topic( $bbpress_topic_slug);
			$bbpress_topic_slug = $bbpress_topic->post_name;
			
			/** If a topic already exists, don't select default forum */
			$bbpress_topic_options['forum_id'] = 0;
		}
		
		$forums = bbp_has_forums();
		
		if(!$forums) {
			?><br /><p><?php _e('bbPress Topics for Posts has been enabled, but you have not created any forums yet.','bbpress-post-topics'); ?></p><?php
			return; 
		} 
		?>
		<br />
		<input type="hidden" name="bbpress_topic[form_displayed]" value="true" />
		<label for="bbpress_topic_status" class="selectit"><input name="bbpress_topic[enabled]" type="checkbox" id="bbpress_topic_status" value="open" <?php checked($bbpress_topic_status); ?> /> <?php _e( 'Use a bbPress forum topic for comments on this post.', 'bbpress-post-topics' ); ?></label><br />
		<div id="bbpress_topic_status_options" class="inside" style="display: <?php echo checked($bbpress_topic_status, true, false) ? 'block' : 'none' ?>;">
			<h4>bbPress Topic Options</h4>
			<label for="bbpress_topic_slug"><?php _e('Use an existing topic:', 'bbpress-post-topics' ) ?> </label> <input type="text" name="bbpress_topic[slug]" id="bbpress_topic_slug" placeholder="<?php _e( 'Topic ID or slug', 'bbpress-post-topics' ); ?>" value="<?php echo $bbpress_topic_slug ?>" <?php if( $bbpress_topic_options['forum_id'] ) echo ' disabled="true"'; ?> />
			  - OR - <label for="bbpress_topic_forum"><?php _e('Create a new topic in forum:', 'bbpress-post-topics' ); ?></label>
			<select name="bbpress_topic[forum_id]" id="bbpress_topic_forum">
				<option value="0" selected><?php _e('Select a Forum', 'bbpress-post-topics' ); ?></option>
				<?php
				$forum_dropdown_options = array(
					'selected'		=> $bbpress_topic_options['forum_id'],
					'options_only'	=> true
				);
				bbp_dropdown( $forum_dropdown_options ); 
				?>
			</select><br />
			
			&mdash; <input type="checkbox" name="bbpress_topic[copy_tags]" id="bbpress_topic_copy_tags" <?php checked( $bbpress_topic_options['copy_tags'] ) ?> /> <label for="bbpress_topic_copy_tags"><?php _e( 'Copy post tags to new topic', 'bbpress-post-topics' ) ?></label>
			<?php if( $import_date = get_post_meta( $post->ID, 'bbpress_discussion_tags_copied', true ) ) :
				printf( '( ' . __( 'last copied %s ago', 'bbpress-post-topics' ) . ' )', human_time_diff( $import_date ) );
			endif; ?>
			<br />
					
			<?php if( wp_count_comments( $post->ID )->total_comments > 0 ) : ?>
				&mdash; <input type="checkbox" name="bbpress_topic[copy_comments]" id="bbpress_topic_copy_comments" <?php checked( $bbpress_topic_options['copy_comments'] ) ?> /> <label for="bbpress_topic_copy_comments"><?php _e( 'Copy comments to bbPress topic', 'bbpress-post-topics' ) ?></label>
				<?php if( $import_date = get_post_meta( $post->ID, 'bbpress_discussion_comments_copied', true ) ) :
					printf( '( ' . __( 'last copied %s ago', 'bbpress-post-topics' ) . ' )', human_time_diff( $import_date ) );
				endif; ?>
				<br />
			<?php endif; ?>
			
			&mdash; <input type="checkbox" name="bbpress_topic[use_defaults]" id="bbpress_topic_use_defaults" <?php checked( $bbpress_topic_options['use_defaults'] ) ?> /> <label for="bbpress_topic_use_defaults"><?php _e( 'Use default display settings', 'bbpress-post-topics' ) ?></label>
			<div id="bbpress_topic_display_options"  style="display: <?php echo checked( $bbpress_topic_options['use_defaults'], true, false ) ? 'none' : 'block' ?>; border-left: 1px solid #ccc; margin-left: 9px; padding-left: 5px;">
				<label for=""><?php _e( 'On the post page, show:', 'bbpress-post-topics' ); ?></label><br />
				<?php
				
				$xreplies_sort_options = array(
					'newest'	=> __( 'most recent', 'bbpress-post-topics' ),
					'oldest'	=> __( 'oldest', 'bbpress-post-topics' )
				);
		
				$xreplies_count = isset($bbpress_topic_options['display-extras']['xcount']) ? $bbpress_topic_options['display-extras']['xcount'] : 5;
				$xreplies_count_string = '<input type="text" name="bbpress_topic[display-extras][xcount]" value="' . $xreplies_count . '" class="small-text" maxlength="3" />';
		
				$xsort_select_string = '<select name="bbpress_topic[display-extras][xsort]" id="bbpress_topic_display_sort">';
				foreach($xreplies_sort_options as $option => $label) {
					$xsort_select_string .= '<option value="' . $option . '" ' . selected( $bbpress_topic_options['display-extras']['xsort'], $option, false ) . '>' . $label . '</option>';
				}
				$xsort_select_string .= '</select>';

				/** Build list of display formats, including custom ones */
				$display_formats = array(
					'topic'		=> __( 'Entire topic', 'bbpress-post-topics' ),
					'replies'	=> __( 'Replies only', 'bbpress-post-topics' ),
					'xreplies'	=> sprintf(__( 'Only the %s %s %s replies', 'bbpress-post-topics' ),'</label>', $xreplies_count_string, $xsort_select_string ),
					'link'		=> __( 'A link to the topic', 'bbpress-post-topics' )
				);
				$display_formats = apply_filters( 'bbppt_display_format_options', $display_formats, $the_post->ID );
				
				?>
				<fieldset>
					<?php foreach ($display_formats as $format_code => $format_label) : ?>
					<input type="radio" name="bbpress_topic[display]" id="bbpress_topic_display_<?php echo $format_code ?>" value="<?php echo $format_code ?>" <?php checked($bbpress_topic_options['display'], $format_code ) ?> /><label for="bbpress_topic_display_<?php echo $format_code ?>"><?php echo $format_label ?></label><br />
					<?php endforeach; ?>
				</fieldset>
			</div>
		</div>
		<script type="text/javascript">

			/** hide topic options when not checked */
			jQuery('#bbpress_topic_status').change(function() {
				if(jQuery(this).attr('checked')) {
					jQuery('#bbpress_topic_status_options').show();
				} else {
					jQuery('#bbpress_topic_status_options').hide();
				}
			});

			/** hide display options when defaults are selected */
			jQuery('#bbpress_topic_use_defaults').change(function() {
				if(jQuery(this).attr('checked')) {
					jQuery('#bbpress_topic_display_options').hide();
				} else {
					jQuery('#bbpress_topic_display_options').show();
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

		do_action( 'bbppt_display_options_fields', $the_post->ID );

		/** Restore the original post being edited */
		$post = $the_post;
	}
	
	/**
	 * Note XMLRPC invocation so we can apply defaults to any posts created during this request
	 */
	function catch_xmlrpc_post( $callname ) {
		$this->xmlrpc_post = true;
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

		/** Only process for post types we specify */
		if( !in_array( $post->post_type, apply_filters( 'bbppt_eligible_post_types', array( 'post', 'page' ) ) ) ) {
			return;			
		}
		
		// TODO: combine existing topic and draft settings branches
		
		if( isset( $_POST['bbpress_topic'] ) ) {
			/**
			 * Post was created through the full post editor form
 			 * Check the POST for settings
			 */
			
			if( $_POST['bbpress_topic']['enabled'] == 'open' ) {
				
				$bbppt_options = $_POST['bbpress_topic'];
				$create_topic = true;
				$use_defaults = isset( $bbppt_options['use_defaults'] );

				bbppt_debug( 'Processing a topic for regular post ' . $post_ID . ' with the following settings: ' . print_r( $bbppt_options, true ) );
				bbppt_debug( 'Creating topic?: ' . $create_topic . '; using defaults?: ' . $use_defaults );
				
			} else {
				
				$create_topic = false;
				bbppt_debug( 'NOT creating a topic for regular post ' . $post_ID );
				
			}
			
		} else if( get_post_meta( $post_ID, 'bbpress_discussion_topic_id', true ) ) {
			/**
			 * If post has an existing topic, use its settings
			 */
			
			$bbppt_options = $this->get_topic_options_for_post( $post_ID );
			$create_topic = ( isset($bbppt_options['enabled']) && $bbppt_options['enabled'] );
			$use_defaults = ( isset( $bbppt_options['use_defaults'] ) && $bbppt_options['use_defaults'] );
			
			bbppt_debug( 'Processing topic for existing post ' . $post_ID . ' with the following settings: ' . print_r( $bbppt_options, true ) );
			bbppt_debug( 'Creating topic?: ' . $create_topic . '; using defaults?: ' . $use_defaults );
			
		} else if( $this->has_draft_settings( $post ) ) {
			/**
			 * If the post has draft settings saved, use draft settings
			 */
		
			$bbppt_options = $this->get_draft_settings( $post );
			$create_topic = ( isset($bbppt_options['enabled']) && $bbppt_options['enabled'] == 'open' );
			$use_defaults = ( isset( $bbppt_options['use_defaults'] ) && $bbppt_options['use_defaults'] );
			
			bbppt_debug( 'Processing a topic for draft post ' . $post_ID . ' with the following settings: ' . print_r( $bbppt_options, true ) );
			bbppt_debug( 'Creating topic?: ' . $create_topic . '; using defaults?: ' . $use_defaults );
			
		} else {
			/**
			 * Post was created through some other means (including XML-RPC) - use defaults
			 */
			
			$bbppt_options = get_option( 'bbpress_discussion_defaults' );
			$create_topic = ( isset($bbppt_options['enabled']) && $bbppt_options['enabled'] == 'on' );
			$use_defaults = true;
			
			bbppt_debug( 'Processing a topic for unattended post ' . $post_ID . ' with the following settings: ' . print_r( $bbppt_options, true ) );
			bbppt_debug( 'Creating topic?: ' . $create_topic . '; using defaults?: ' . $use_defaults );
			
		}

		/** If post is saved as a draft, store selected settings for publication later */
		if( in_array( $post->post_status, apply_filters( 'bbppt_draft_post_status', array( 'draft', 'future' ) ) ) ) {
			$this->set_draft_settings( $bbppt_options, $post );
			return;
		}

		/** Only process further when the post is published */
		if( ! in_array( $post->post_status, apply_filters( 'bbppt_eligible_post_status', array( 'publish' ) ) ) )
			return;
		
		/**
		 * The user requested to use a bbPress topic for discussion
		 */
		if( $create_topic ) {

			if( ! function_exists('bbp_has_forums') ) {
				?><br /><p><?php _e('bbPress Topics for Posts cannot process this request because it cannot detect your bbPress setup.','bbpress-post-topics'); ?></p><?php
				return;
			}

			$topic_slug		= isset( $bbppt_options['slug'] ) ? $bbppt_options['slug'] : '' ;
			$topic_forum	= isset( $bbppt_options['forum_id'] ) ? (int)$bbppt_options['forum_id'] : 0 ;
			
			if( ! $use_defaults ) {
				
				$topic_display	= isset($bbppt_options['display']) ? $bbppt_options['display'] : 'topic' ;
	
				/** Store extra data for xreplies, as well as any custom display formats */
				$topic_display_extras = apply_filters( 'bbppt_store_display_extras', $bbppt_options['display-extras'], $post );
				
			}
			
			if( ! empty( $topic_slug ) ) {
				
				/** if user has selected an existing topic */
				
				if( is_numeric( $topic_slug ) ) {
					$topic = bbp_get_topic( (int)$topic_slug );
				} else {
					$topic = bbppt_get_topic_by_slug( $topic_slug );
				}
				
				if( $topic == null ) {
					// return an error of some kind
					wp_die( __( 'There was an error with your selected topic.', 'bbpress-post-topics' ), __( 'Error Locating bbPress Topic', 'bbpress-post-topics' ) );
				} else {
					$topic_ID = $topic->ID;
					update_post_meta( $post_ID, 'use_bbpress_discussion_topic', true );
					update_post_meta( $post_ID, 'bbpress_discussion_topic_id', $topic_ID );
					
					/** Update topic with tags from the post */
					if( $bbppt_options['copy_tags'] ) {
						$post_tags = wp_get_post_tags( $post_ID );
						$post_tags = array_map( create_function( '$term', 'return $term->name;' ), $post_tags );
						wp_set_post_terms( $topic_ID, join( ',', $post_tags ), bbp_get_topic_tag_tax_id(), true );
						update_post_meta( $post_ID, 'bbpress_discussion_tags_copied', time() );
					}
					
					/** Export comments from the post to the new bbPress topic */
					if( $bbppt_options['copy_comments'] ) {
						bbppt_import_comments( $post_ID, $topic_ID );
						update_post_meta( $post_ID, 'bbpress_discussion_comments_copied', time() );
					}
					
					if( $use_defaults ) {
						update_post_meta( $post_ID, 'bbpress_discussion_use_defaults', true );
					} else {
						delete_post_meta( $post_ID, 'bbpress_discussion_use_defaults' );
						update_post_meta( $post_ID, 'bbpress_discussion_display_format', $topic_display );
						update_post_meta( $post_ID, 'bbpress_discussion_display_extras', $topic_display_extras );
					}
					
				}
				do_action( 'bbppt_topic_assigned', $post_ID, $topic_ID );
				
			} else if($topic_forum != 0) {
				/** if user has opted to create a new topic */

				$topic_ID = $this->build_new_topic( $post, $topic_forum );
				
				if( ! $topic_ID ) {
					// return an error of some kind
					wp_die(__('There was an error creating a new topic.','bbpress-post-topics'),__('Error Creating bbPress Topic','bbpress-post-topics'));
				} else {
					update_post_meta( $post_ID, 'use_bbpress_discussion_topic', true );
					update_post_meta( $post_ID, 'bbpress_discussion_topic_id', $topic_ID );

					/** Update topic with tags from the post */
					if( $bbppt_options['copy_tags'] ) {
						$post_tags = wp_get_post_tags( $post_ID );
						$post_tags = array_map( create_function( '$term', 'return $term->name;' ), $post_tags );
						wp_set_post_terms( $topic_ID, join( ',', $post_tags ), bbp_get_topic_tag_tax_id(), false );
						update_post_meta( $post_ID, 'bbpress_discussion_tags_copied', time() );
					}
					
					/** Export comments from the post to the new bbPress topic */
					if( $bbppt_options['copy_comments'] ) {
						bbppt_import_comments( $post_ID, $topic_ID );
						update_post_meta( $post_ID, 'bbpress_discussion_comments_copied', time() );
					}
					
					if( $use_defaults ) {
						update_post_meta( $post_ID, 'bbpress_discussion_use_defaults', true );
					} else {
						update_post_meta( $post_ID, 'bbpress_discussion_display_format', $topic_display );
						update_post_meta( $post_ID, 'bbpress_discussion_display_extras', $topic_display_extras );
					}
					
				}
				do_action( 'bbppt_topic_created', $post_ID, $topic_ID );
				
			}
		} else {
			delete_post_meta( $post_ID, 'use_bbpress_discussion_topic' );
			delete_post_meta( $post_ID, 'bbpress_discussion_topic_id' );
			delete_post_meta( $post_ID, 'bbpress_discussion_use_defaults' );
			delete_post_meta( $post_ID, 'bbpress_discussion_display_format' );
			delete_post_meta( $post_ID, 'bbpress_discussion_display_extras' );
			delete_post_meta( $post_ID, 'bbpress_discussion_tags_copied' );
			delete_post_meta( $post_ID, 'bbpress_discussion_comments_copied' );
		}
		
		$this->delete_draft_settings( $post_ID );
		do_action( 'bbppt_topic_associated', $post_ID, $topic_ID );
		
	}
	
	/**
	 * Create the new topic when selected, including shortcode substitution
	 * @param WP_Post $post post object to associate with new topic
	 * @param int $topic_forum ID of forum to hold new topic
	 */
	function build_new_topic( $post, $topic_forum ) {

		$strings = get_option( 'bbpress_discussion_text' );
		$author_info = get_userdata( $post->post_author );
		
		if( isset( $strings['topic-text'] ) ) {
			
			$topic_content = $strings['topic-text'];
			
		} else {
			
			$topic_content = "%excerpt<br />" . sprintf( __('[See the full post at: <a href="%s">%s</a>]','bbpress-post-topics'), '%url', '%url' );
			
		}

		$shortcodes = array(
			'%title'	=> $post->post_title,
			'%url'		=> get_permalink( $post->ID ),
			'%author'	=> $author_info->user_nicename,
			'%excerpt'	=> ( empty( $post->post_excerpt ) ? bbppt_post_discussion_get_the_content($post->post_content, 150) : apply_filters('the_excerpt', $post->post_excerpt) ),
			'%post'		=> $post->post_content
		);
		$shortcodes = apply_filters( 'bbppt_shortcodes_output', $shortcodes, $post, $topic_forum );
		
		$topic_content = str_replace( array_keys($shortcodes), array_values($shortcodes), $topic_content );
		$topic_content = apply_filters( 'bbppt_topic_content', addslashes( $topic_content ), $post->ID );
		
		$new_topic_data = array(
			'post_parent'   => (int)$topic_forum,
			'post_author'   => $post->post_author,
			'post_content'  => $topic_content,
			'post_title'    => $post->post_title,
			'post_date'		=> $post->post_date,
			'post_date_gmt'	=> $post->post_date_gmt
		);
		
		$new_topic_meta = array(
			'forum_id'			=> (int)$topic_forum
		);
		
		$new_topic = bbp_insert_topic( $new_topic_data, $new_topic_meta );
		return $new_topic;
	}
	
	/**
	 * Display the bbPress topic plugin template instead of the WordPress comments template
	 */
	function maybe_change_comments_template( $template ) {

		global $post, $bbp;

		if( ! function_exists( 'bbp_has_forums' ) )	return $template;
		
		if( get_post_meta( $post->ID, 'use_bbpress_discussion_topic', true ) ) {
			
			$topic_ID = get_post_meta( $post->ID, 'bbpress_discussion_topic_id', true);
			$this->topic_ID = $topic_ID;

			// Since bbPress has removed the $bbp global, use of this property is deprecated
			$bbp->topic_query->post->ID = $topic_ID;
			
			/** Handle posts where defaults were kept */
			$settings = $this->get_topic_options_for_post( $post->ID );
			
			switch($settings['display']) {
				case 'topic':
			 		return bbppt_locate_template('comments-bbpress.php');
					break;
				case 'xreplies':
					add_filter( 'bbp_has_replies_query', 'bbppt_limit_replies_in_thread');
				case 'replies':
					add_filter( 'bbp_has_replies_query', 'bbppt_remove_topic_from_thread' );
			 		return bbppt_locate_template('comments-bbpress.php');
					break;
				case 'link':
			 		return bbppt_locate_template( 'comments-bbpress-link.php' );
					break;
				default:
					return apply_filters( 'bbppt_template_display_format_' . $settings['display'], $template, $settings );
					break;
			}
		}
		return $template;
	}
	
	/**
	 * If a topic has been used for a post, give the number of replies in place of comment count
	 */
	function maybe_change_comments_number( $number, $post_ID ) {
		
		if( ! function_exists( 'bbp_has_forums' ) )	return $number;
		
		if( get_post_meta( $post_ID, 'use_bbpress_discussion_topic', true ) ) {
			$topic_ID = get_post_meta( $post_ID, 'bbpress_discussion_topic_id', true );
			return bbp_get_topic_reply_count( $topic_ID );
		}
		
		return $number;
	}

	/**
	 * Delete the bbPress topic associated with a post being deleted unless:
	 *  - that topic is also associated with another post
	 *  - cancelled by 'bbppt_do_delete_topic' filter
	 * @param int post_ID ID of the post being deleted
	 */
	function maybe_delete_topic( $post_ID ) {
		
		if( get_post_meta( $post_ID, 'use_bbpress_discussion_topic', true ) ) {
			
			$topic_ID = get_post_meta( $post_ID, 'bbpress_discussion_topic_id', true );
			
			$affected_posts = get_posts(
				array(
					'post_type'		=> apply_filters( 'bbppt_eligible_post_types', array( 'post', 'page' ) ),
					'meta_key'		=> 'bbpress_discussion_topic_id',
					'meta_value'	=> $topic_ID
				)
			);
			
			if( count( $affected_posts ) == 1 && apply_filters( 'bbppt_do_delete_topic', true, $topic_ID ) ) {
				
				// Delete topic
				bbp_delete_topic( $topic_ID );
				
			}
			
		}		
	}
	
	
	/****************************
	 * General Discussion options
	 */
	
	/**
	 * Register our sections for the Discussion settings page
	 */
	function add_discussion_page_settings() {
		register_setting( 'discussion', 'bbpress_discussion_defaults' );
		register_setting( 'discussion', 'bbpress_discussion_text', array( &$this, 'sanitize_text_settings' ) );
		add_settings_field( 'bbpress_discussion_defaults', __('bbPress Topics for Posts Defaults','bbpress-post-topics'), array(&$this,'general_discussion_settings'), 'discussion', 'default', array('label_for'=>'bbpress_discussion_defaults_enabled') );
		add_settings_field( 'bbpress_discussion_text', __('bbPress Topics for Posts Strings','bbpress-post-topics'), array(&$this,'general_discussion_text_settings'), 'discussion', 'default' );
		
		wp_register_script( 'bbppt-admin-script', plugins_url( 'inc/bbppt-admin.js', __FILE__ ), array('jquery') );
		wp_localize_script( 'bbppt-admin-script', 'bbPPTStrings', array(
			'disabledTitle'	=> __('Disabled - save changes or reload to enable','bbpress-post-topics'),
			'imgSrc'		=> ADMIN_COOKIE_PATH . '/images/wpspin_light.gif'
		));
	}
	
	/**
	 * Section for setting defaults for bbPress Topics for Posts
	 */
	function general_discussion_settings() {
		
		?><div id="bbppt-discussion-settings"><?php
		
		if( ! function_exists( 'bbp_has_forums' ) ) {
			?>
			<p><?php _e( 'You must install or enable bbPress to use this plugin.', 'bbpress-post-topics' ); ?></p>
			<?php
			return;
		}
		
		wp_enqueue_script('bbppt-admin-script');
		
		$ex_options = get_option( 'bbpress_discussion_defaults' );
		
		$forum_dropdown_options = array(
			'selected'		=> $ex_options['forum_id'],
			'options_only'	=> true
		);
		$forum_select_string = '<select name="bbpress_discussion_defaults[forum_id]" id="bbpress_discussion_defaults_forum_id">';
		$forum_select_string .= '<option value="0">' . __('Select a Forum','bbpress-post-topics') . '</option>';
		$forum_select_string .= bbp_get_dropdown( $forum_dropdown_options ); 
		$forum_select_string .= '</select>';
		?>
		<input type="checkbox" name="bbpress_discussion_defaults[enabled]" id="bbpress_discussion_defaults_enabled" <?php checked($ex_options['enabled'],'on') ?>>
		<label for="bbpress_discussion_defaults_enabled"><?php printf(__('Create a new bbPress topic in %s %s for new posts','bbpress-post-topics'), '</label>', $forum_select_string); ?> 

		<?php if($ex_options['enabled'] == 'on') : ?>
		&mdash; <a class="button" id="create_topics" href="#" title="<?php _e('Create topics and apply these settings to all existing posts','bbpress-post-topics') ?>"><?php _e('Apply settings to existing posts', 'bbpress-post-topics'); ?></a>
		<?php endif; ?>
		<br />

		<input type="checkbox" name="bbpress_discussion_defaults[copy_tags]" id="bbpress_discussion_defaults_copy_tags" <?php checked($ex_options['copy_tags'],'on') ?>>
		<label for="bbpress_discussion_defaults_copy_tags"><?php _e('Copy post tags to new topics','bbpress-post-topics'); ?></label><br />

		<input type="checkbox" name="bbpress_discussion_defaults[copy_comments]" id="bbpress_discussion_defaults_copy_comments" <?php checked($ex_options['copy_comments'],'on') ?>>
		<label for="bbpress_discussion_defaults_copy_comments"><?php _e('Copy post comments to new topics (when available)','bbpress-post-topics'); ?></label><br />

		<label for=""><?php _e( 'On the post page, show:', 'bbpress-post-topics' ); ?></label><br />
		<?php

		$xreplies_count = isset($ex_options['display-extras']['xcount']) ? $ex_options['display-extras']['xcount'] : 5;
		$xreplies_count_string = '<input type="text" name="bbpress_discussion_defaults[display-extras][xcount]" id="bbpress_discussion_defaults_display-extras_xcount" value="' . $xreplies_count . '" class="small-text" maxlength="3" />';

		$xreplies_sort_options = array(
			'newest'	=> __( 'most recent', 'bbpress-post-topics' ),
			'oldest'	=> __( 'oldest', 'bbpress-post-topics' )
		);

		$xsort_select_string = '<select name="bbpress_discussion_defaults[display-extras][xsort]" id="bbpress_discussion_defaults_display_sort">';
		foreach($xreplies_sort_options as $option => $label) {
			$xsort_select_string .= '<option value="' . $option . '" ' . selected( $ex_options['display-extras']['xsort'], $option, false ) . '>' . $label . '</option>';
		}
		$xsort_select_string .= '</select>';
		
		
		/** Build list of display formats, including custom ones */
		$display_formats = array(
			'topic'		=> __( 'Entire topic', 'bbpress-post-topics' ),
			'replies'	=> __( 'Replies only', 'bbpress-post-topics' ),
			'xreplies'	=> sprintf(__( 'Only the %s %s %s replies', 'bbpress-post-topics' ),'</label>', $xreplies_count_string, $xsort_select_string ),
			'link'		=> __( 'A link to the topic', 'bbpress-post-topics' ) . '</label>'
		);
		$display_formats = apply_filters( 'bbppt_display_format_options', $display_formats, 0 );
		
		?>
		<fieldset>
			<?php foreach ($display_formats as $format_code => $format_label) : ?>
			<input type="radio" name="bbpress_discussion_defaults[display]" id="bbpress_discussion_default_display_<?php echo $format_code ?>" value="<?php echo $format_code ?>" <?php checked($ex_options['display'], $format_code ) ?> /><label for="bbpress_discussion_default_display_<?php echo $format_code ?>"><?php echo $format_label ?></label><br />
			<?php endforeach; ?>
		</fieldset>
		<?php
		
		do_action( 'bbppt_display_options_fields', 0 );
		
		?></div><?php
		
	}
	
	/**
	 * Section for setting strings for new topics and link
	 */
	function general_discussion_text_settings() {

		?><div id="bbppt-discussion-text-settings"><?php

		if( ! function_exists( 'bbp_has_forums' ) ) {
			?>
			<p><?php _e( 'You must install or enable bbPress to use this plugin.', 'bbpress-post-topics' ); ?></p>
			<?php
			return;
		}
		
		$text_options = get_option( 'bbpress_discussion_text' );
		
		if(isset($text_options['topic-text'])) {
			$topic_text_value = $text_options['topic-text'];
		} else {
			$topic_text_value = '%excerpt' . '<br />' . sprintf(__( '[See the full post at: <a href="%s">%s</a>]', 'bbpress-post-topics' ), '%url', '%title' );
		}
		$link_text_value = ( isset( $text_options['link-text'] ) ? $text_options['link-text'] : __( 'Follow this link to join the discussion', 'bbpress-post-topics' ) );
		
		$shortcodes = array(
			'%title'	=> __( 'Post title', 'bbpress-post-topics' ),
			'%url'		=> __( 'Post Permalink', 'bbpress-post-topics' ),
			'%author'	=> __( 'Post author\'s display name', 'bbpress-post-topics' ),
			'%excerpt'	=> __( 'Post except (or a 150-character snippet)', 'bbpress-post-topics' ),
			'%post'		=> __( 'Full post text', 'bbpress-post-topics' )
		);
		$shortcodes = apply_filters( 'bbppt_shortcodes_list', $shortcodes );
		
		?>
		<label for="bbpress_discussion_text_topic_text"><?php _e( 'Content of topic first post:', 'bbpress-post-topics' ) ?></label>
		<p>
			<textarea name="bbpress_discussion_text[topic-text]" id="bbpress_discussion_text_topic_text" class="large-text code"><?php echo $topic_text_value ?></textarea>
			<small>
				(<?php _e( 'Use the substitutions below:', 'bbpress-post-topics' ) ?>)<br />
				<?php foreach( $shortcodes as $code => $description ) {
					echo $code . ' &mdash; ' . $description . '<br />';
				} ?>
			</small>
		</p>
		<label for=""><?php _e( 'Link text (when showing only a link to the topic):', 'bbpress-post-topics' ) ?></label>
		<input type="text" name="bbpress_discussion_text[link-text]" class="regular-text" id="bbpress_discussion_text_link_text" value="<?php echo $link_text_value ?>" />
		<small>(<?php _e( 'Use %s to include the post name', 'bbpress-post-topics' ) ?>)</small>
		<?php

		do_action( 'bbppt_display_text_options_fields', 0 );

		?></div><?php

	}
	
	/**
	 * Sanitize the general discussion strings
	 */
	function sanitize_text_settings( $strings ) {
		
		if( isset( $strings['topic-text'] ) ) {
			
			$strings['topic-text'] = wp_kses_post( $strings['topic-text'] );
			
		}
		
		return $strings;
	}
	
	/**
	 * Retrieve topic options for posts, including cases where defaults are used
	 * @param int $ID ID of post
	 * @param string $option_name Optional name of an option to filter by
	 */
	function get_topic_options_for_post( $ID, $option_name = null ) {
		
		/** Conditions for applying default settings:
		 * 1 - post where keep defaults was checked
		 * 2 - new post
		 */

		$defaults = get_option( 'bbpress_discussion_defaults' );
		if( ! array_key_exists( 'display-extras', $defaults ) ) {
			$defaults['display-extras'] = array();
		}

		$strings = get_option( 'bbpress_discussion_text' );
		
		$draft_settings = $this->get_draft_settings( $ID );
		
		if( $draft_settings && $draft_settings['enabled'] && ! $draft_settings['use_defaults'] ) {
			
			/** Post has draft settings saved */
			$options = $draft_settings;
			
		} else if(
			get_post_meta( $ID, 'bbpress_discussion_use_defaults', true ) || 
			! get_post_meta( $ID, 'bbpress_discussion_topic_id', true )
		) {
			
			/** Post is using defaults, or is new - return default values */
			
			$display_extras = maybe_unserialize( $defaults['display-extras'] );
			
			$options = array(
				'enabled'			=> get_post_meta( $ID, 'use_bbpress_discussion_topic', true ) || $defaults['enabled'],
				'use_defaults'		=> true,
				'topic_id'			=> get_post_meta( $ID, 'bbpress_discussion_topic_id', true ),
				'slug'				=> get_post_meta( $ID, 'bbpress_discussion_topic_id', true ),
				'forum_id'			=> $defaults['forum_id'],
				'copy_tags'			=> $defaults['copy_tags'],
				'copy_comments'		=> $defaults['copy_comments'],
				'display'			=> $defaults['display'],
				'display-extras'	=> $display_extras,
				'text'				=> $strings
			);
			
		} else {
			
			/** Post is using custom settings - return those values */

			/** Remove legacy post meta when post is accessed */
			if( get_post_meta( $ID, 'bbpress_discussion_hide_topic', true ) ) {
				update_post_meta( $ID, 'bbpress_discussion_display_format', 'replies' );
				delete_post_meta( $ID, 'bbpress_discussion_hide_topic' );
				$display = 'replies';
			} else if( ! get_post_meta( $ID, 'bbpress_discussion_display_format', true ) ) {
				update_post_meta( $ID, 'bbpress_discussion_display_format', 'topic' );
				$display = 'topic';
			} else {
				$display = get_post_meta( $ID, 'bbpress_discussion_display_format', true );
			}
			
			$display_extras = maybe_unserialize( get_post_meta( $ID, 'bbpress_discussion_display_extras', true ) );
			
			/** Fill in any missing fields with defaults */
			if( ! empty( $display_extras ) ) {
				foreach( $defaults['display-extras'] as $display_extra => $extra_value ) {
					if( ! array_key_exists( $display_extra, $display_extras ) ) {
						$display_extras[$display_extra] = $extra_value;
					}
				}
			} else {
				$display_extras = $defaults['display-extras'];
			}
			
			$options = array(
				'enabled'			=> get_post_meta( $ID, 'use_bbpress_discussion_topic', true ),
				'use_defaults'		=> false,
				'topic_id'			=> get_post_meta( $ID, 'bbpress_discussion_topic_id', true ),
				'slug'				=> get_post_meta( $ID, 'bbpress_discussion_topic_id', true ),
				'copy_tags'			=> $defaults['copy_tags'],
				'copy_comments'		=> $defaults['copy_comments'],
				'display'			=> $display,
				'display-extras'	=> $display_extras,
				'text'				=> $strings
			);
		}
		
		return $options;
		
	}

	/** Functions for managing options for posts that have not been published */
	
	function has_draft_settings( $post = null ) {
	
		$post = get_post( $post );
		if( ! is_object( $post ) )	return false;
		
		return get_post_meta( $post->ID, 'bbppt_draft_settings', true );
		
	}
	
	function get_draft_settings( $post = null ) {
	
		return $this->has_draft_settings( $post );
		
	}
	
	function set_draft_settings( $settings, $post = null ) {
	
		$post = get_post( $post );
		if( ! is_object( $post ) )	return false;
		
		update_post_meta( $post->ID, 'bbppt_draft_settings', $settings );
		
	}
	
	function delete_draft_settings( $post = null ) {
		
		$post = get_post( $post );
		if( ! is_object( $post ) )	return false;
		
		return delete_post_meta( $post->ID, 'bbppt_draft_settings', true );
		
	}


}

$bbp_post_topics = new BBP_PostTopics;

//add_action( 'add_meta_boxes',		array( &$bbp_post_topics, 'add_meta_box' ) );
add_action( 'post_comment_status_meta_box-options', array( &$bbp_post_topics, 'display_topic_option' ) );
add_action( 'save_post', 			array( &$bbp_post_topics, 'process_topic_option' ), 10, 2 );
add_action( 'admin_init', 			array( &$bbp_post_topics, 'add_discussion_page_settings' ) );
add_action( 'xmlrpc_call', 			array( &$bbp_post_topics, 'catch_xmlrpc_post' ) );
add_action( 'before_delete_post',	array( &$bbp_post_topics, 'maybe_delete_topic' ) );
add_filter( 'comments_template', 	array( &$bbp_post_topics, 'maybe_change_comments_template' ) );
add_filter( 'get_comments_number', 	array( &$bbp_post_topics, 'maybe_change_comments_number' ), 10, 2 );

register_activation_hook( __FILE__, 'bbppt_activate' );

function bbppt_activate() {
	
	/** Update global settings to new format */
	$ex_options = get_option( 'bbpress_discussion_defaults' );
	$text_options = get_option( 'bbpress_discussion_text' );
	
	if($ex_options['hide_topic'] == 'on') {
		$ex_options['display']	= 'replies';
	} else {
		$ex_options['display']	= 'topic';
	}
	unset($ex_options['hide_topic']);
	
	/** Update link text storage to new format - old format was never released, but was available in dev version */
	if( isset( $ex_options['display-extras'] ) && isset( $ex_options['display-extras']['link-text'] ) )	{
		$text_options['link-text'] = $ex_options['display-extras']['link-text'];
		unset($ex_options['display-extras']['link-text']);
	}
	
	update_option( 'bbpress_discussion_defaults', $ex_options );
	
	/** Set default link text */
	if( ! isset( $text_options['link-text'] ) ) {
		$text_options = array(
			'link-text'		=> __( 'Follow this link to join the discussion', 'bbpress-post-topics' )
		);
	}
	update_option( 'bbpress_discussion_text', $text_options );
	
}

/****************************
 * Utility functions
 */

/**
 * Check for a bbPress topic with a post_name matching the slug provided
 * @param string Post slug
 * @return object|NULL the topic or NULL if not found
 */
function bbppt_get_topic_by_slug( $slug ) {
	
	global $wpdb;
	
	$topic = $wpdb->get_row( $wpdb->prepare('SELECT ID, post_name, post_parent FROM ' . $wpdb->posts .  ' WHERE post_name = %s AND post_type = %s', $slug, bbp_get_topic_post_type()) );
	
	if(is_null($topic))	return $topic;
	return $topic;
}

/**
 * Filter and limit the content for use in the bbPress topic
 * @param text $content Post content to be filtered
 * @param int $cut # of characters to keep in the excerpt (set to 0 for whole post)
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
 * Remove the original topic post from the replies query for a forum thread
 * Made for use with the bbp_has_replies_query filter
 */
function bbppt_remove_topic_from_thread( $bbp_args ) {
	$bbp_args['post_type'] = bbp_get_reply_post_type();
	return $bbp_args;
}

/**
 * Return only the selected number of replies, sorted in the selected way
 */
function bbppt_limit_replies_in_thread( $bbp_args ) {
	
	global $post, $bbp_post_topics;

	$settings = $bbp_post_topics->get_topic_options_for_post( $post->ID );

	$per_page = $settings['display-extras']['xcount'];
	$sort = $settings['display-extras']['xsort'];

	$bbp_args['posts_per_page'] = $per_page;
	
	if($sort == 'newest') {
		$bbp_args['orderby'] = 'date';
		$bbp_args['order']	 = 'DESC';
	} else if($sort == 'oldest') {
		$bbp_args['orderby'] = 'date';
		$bbp_args['order']	 = 'ASC';
	}
	
	return $bbp_args;
	
}

/**
 * Locate a template file for bbPress Topics for Posts
 * Search child and parent theme directories before falling back to files included with plugin
 */
function bbppt_locate_template( $template_name, $load = false ) {
	
	$located = '';
	if( $located = locate_template( apply_filters( 'bbppt_template_name', '/bbpress/' . $template_name, $template_name ) ) ) {
		
	} else if( file_exists(  dirname( __FILE__ ) . '/templates/' . $template_name ) ) {
		$located = dirname( __FILE__ ) . '/templates/' . $template_name;
	}
	
	if( $load && $located && $located != '' ) {
		load_template( $located );
	}
	return $located;
}

/**
 * Create bbPress replies with existing comments on a post
 * 
 * @author javiarques
 * @param int $post_id ID of the post whose comments to import to topic
 * @param int $topic_id ID of the topic where comments will be added as replies
 */
function bbppt_import_comments( $post_id, $topic_id ) {
	
	$topic_forum = bbp_get_topic_forum_id( $topic_id );
	
	/** getting post comments */
	$post_comments = get_comments( array( 'post_id' => $post_id, 'order' => 'ASC' ) );
	
	if ( $post_comments ) {
		foreach ( $post_comments as $post_comment ) {
			
			if ( ! empty( $post_comment->comment_type ) ) {
				continue;
			}
			
			/** Allow individual comments to be skipped with `bbppt_do_import_comment` filter
			 *  By default, skip comments that have already been imported
			 */
			if( ! apply_filters( 'bbppt_do_import_comment', ! get_comment_meta( $post_comment->comment_ID, 'bbppt_imported', true ), $post_comment ) )
				continue;

			// If user is not registered
			if ( empty( $post_comment->user_id ) ) {
				
				// 1. Check if user exists by email
				if ( ! empty( $post_comment->comment_author_email ) ) {
					$existing_user = get_user_by( 'email', $post_comment->comment_author_email );
					
					if ( $existing_user )
						$post_comment->user_id = $existing_user->ID;
				}

			}
			
			// Reply data
			$reply_data = array(
				'post_parent'   => $topic_id, // topic ID
				'post_status'   => bbp_get_public_status_id(),	// TODO: are other statuses applicable?
				'post_type'     => bbp_get_reply_post_type(),
				'post_author'   => $post_comment->user_id ,
				'post_content'  => apply_filters( 'bbppt_imported_comment_content', $post_comment->comment_content, $post_comment ),
				'post_date' 	=> $post_comment->comment_date,
				'post_date_gmt' => $post_comment->comment_date_gmt,
				'post_modified' => $post_comment->comment_date,
				'post_modified_gmt'	=> $post_comment->comment_date_gmt
			);
			
			// Reply meta
			$reply_meta = array(
				'author_ip' 			=> $post_comment->comment_author_IP,
				'forum_id'  			=> $topic_forum,
				'topic_id'  			=> $topic_id,
				'imported_comment_id'	=> $post_comment->comment_ID
			);
			
			// If not registered user, add anonymous user information
			if ( empty( $post_comment->user_id ) ) {
				// Parse args
				$anonymous = array(
					'anonymous_name' => $post_comment->comment_author,
					'anonymous_email'=> $post_comment->comment_author_email,
					'anonymous_website' => $post_comment->comment_author_url
				);
				$reply_meta = wp_parse_args( $reply_meta, $anonymous );
			}
			
			$reply_id = bbp_insert_reply( $reply_data, $reply_meta );
			
			update_comment_meta( $post_comment->comment_ID, 'bbppt_imported', true );
			
			do_action( 'bbppt_comment_imported', $post_comment, $post_id, $topic_id );
			
		}
	}
}

/**
 * Process existing posts with default topic settings -- unless they have an associated topic
 */
function bbppt_process_existing_posts() {
	
	global $bbp_post_topics;
	
	// Force use of defaults
	$bbp_post_topics->xmlrpc_post = true;
	
	$offset = 0;
	
	do {
		$posts = get_posts(
			array(
				'numberposts'	=> 1000,
				'offset'		=> $offset,
				'post_type'		=> apply_filters( 'bbppt_eligible_post_types', array( 'post' ) )
			)
		);
		
		if( $posts ) {
			foreach($posts as $post) {
				$bbp_post_topics->process_topic_option( $post->ID, $post );
			}
			$offset += 1000;
			
		}
	
	} while( $posts != null );
	
	return true;
	
}


/**
 * Debugging function
 */
function bbppt_debug( $message ) {

	if( ! defined( 'WP_DEBUG') || ! WP_DEBUG )	return;

	if(defined( 'WP_DEBUG_LOG') && WP_DEBUG_LOG ) {
		$GLOBALS['wp_log']['bbpress-post-topics'][] = 'bbPress Topics for Posts: ' .  $message;
		error_log('bbPress Topics for Posts: ' .  $message);
	}

	if( defined('WP_DEBUG_DISPLAY') && false !== WP_DEBUG_DISPLAY) {
		echo '<div class="log">bbPress Topics for Posts: ' . $message . "</div>\n";
	}
	
}

?>