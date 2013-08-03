jQuery(function() {

	jQuery('#bbppt-discussion-settings input').change(  disableCreateTopicsButton );
	jQuery('#bbppt-discussion-settings select').change( disableCreateTopicsButton );
	jQuery('#bbppt-discussion-text-settings input').change(    disableCreateTopicsButton );
	jQuery('#bbppt-discussion-text-settings textarea').change( disableCreateTopicsButton );

	jQuery('#create_topics').click(function() {
		if( jQuery(this).hasClass('disabled') ) { return false;	}
		jQuery(this).addClass('disabled')
					.append('<img src="' + bbPPTStrings.imgSrc + '" style="margin: 0 0 -3px 4px;" />');
		
		jQuery.post(
			ajaxurl, {action: 'bbppt_import_old_posts'},
			function( r ) {
				jQuery('#create_topics').html(r).attr('title',r);
			}
		);
		return false;
	});
	
});

function disableCreateTopicsButton() {
	jQuery('#create_topics').addClass('disabled').attr('title',bbPPTStrings.disabledTitle);
}