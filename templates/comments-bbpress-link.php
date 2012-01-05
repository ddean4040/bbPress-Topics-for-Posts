<?php
/**
 * The template for displaying a link to a comment topic when the user has selected one.
 *
 */
?>
<div id="comments">
	
	<?php 
		global $bbp;
		echo $bbp->shortcodes->display_topic(array('id'=>$bbp->topic_query->post->ID));
	?>

</div><!-- #comments -->