<?php
/**
 * The template for displaying a link to a comment topic when the user has selected one.
 *
 */
?>
<div id="comments">
	<?php 
		global $bbp, $post, $bbp_post_topics;
		$settings = $bbp_post_topics->get_topic_options_for_post( $post->ID );
	?>
	<a href="<?php bbp_topic_permalink( $bbp_post_topics->topic_ID ); ?>"><?php printf($settings['text']['link-text'],$post->post_title) ?></a>
</div><!-- #comments -->