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