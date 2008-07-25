<?php

if ( !current_user_can('edit_others_posts') )
	die;

if ( !empty($_POST['id']) ) {
	
	if ( $_POST['action'] == 'edit' ) {
		check_admin_referer('nr_edit_' . $book->ID);
	}
	
	if ( $_POST['action'] == 'delete' ) {
		check_admin_referer('nr_delete_' . $_POST['id']);
		
		delete_book($_POST['id']);
		
		header('Location: ' . get_bloginfo('siteurl') . '/wp-admin/admin.php?page=manage_books&message=1' );
	}
	
}

?>