jQuery( function() {
	
	// Add/remove our "Search:" text in the Manage screen.
	jQuery("#search").focus(
		function() {
			if ( jQuery(this).val() == "Search:" ) {
				jQuery(this).val("");
				jQuery(this).removeClass("greyed");
			}
		}
	);
	
	jQuery("#search").blur(
		function() {
			if ( jQuery(this).val() == "" ) {
				jQuery(this).val("Search:");
				jQuery(this).addClass("greyed");
			}
		}
	);
	
} );