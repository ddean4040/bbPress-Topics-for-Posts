<?php
/**
 * The template for displaying a comment topic when the user has selected one.
 *
 */
?>
<div id="comments">
	
	<?php do_action( 'bbp_template_before_single_topic' ); ?>
	<?php 
		global $bbp;
		echo $bbp->shortcodes->display_topic(array('id'=>$bbp->topic_query->post->ID));
	?>
	<?php do_action( 'bbp_template_after_single_topic' ); ?>

</div><!-- #comments -->
<?php
	/** Hide pagination and form when we are only displaying a certain number of posts */
	global $bbp_post_topics, $post;
	$settings = $bbp_post_topics->get_topic_options_for_post( $post->ID );
	if($settings['display'] == 'xreplies') {
		?>
		<style type="text/css">
			.bbp-pagination, .bbp-reply-form {
				display: none;
			}
		</style>
		<?
	}
?>