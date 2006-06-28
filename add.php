<?php

define('ABSPATH', realpath(dirname(__FILE__) . '/../../../') . '/');
require_once ABSPATH . '/wp-admin/admin.php';

if( !empty($_POST['amazon_data']) ) {
	
	if( !current_user_can('level_9') )
		die ( __('Cheatin&#8217; uh?') );
	
	$data = unserialize(stripslashes($_POST['amazon_data']));
	
	$b_author = $wpdb->escape($data['author']);
	$b_title = $wpdb->escape($data['title']);
	$b_image = $wpdb->escape($data['image']);
	$b_asin = $wpdb->escape($data['asin']);
	$b_added = date('Y-m-d h:i:s');
	$b_status = 'unread';
	
	check_admin_referer('now-reading-add-' . $b_title);
	
	foreach( compact('b_author', 'b_title', 'b_image', 'b_asin', 'b_added', 'b_status') as $field => $value )
		$query .= "$field=$value&";
	
	$redirect = get_settings('home').'/wp-admin/post-new.php?page=now-reading-add.php';
	if( !file_exists(dirname(__FILE__).'/../../../wp-admin/post-new.php') )
		$redirect = get_settings('home').'/wp-admin/post.php?page=now-reading-add.php';
	
	if( add_book($query) ) {
		wp_redirect("$redirect&added=true");
		die;
	} else {
		wp_redirect("$redirect&error=true");
		die;
	}
} elseif( !empty($_POST['custom_title']) ) {
		
		check_admin_referer('now-reading-manual-add');
		
		$b_author = $wpdb->escape($_POST['custom_author']);
		$b_title = $wpdb->escape($_POST['custom_title']);
		$b_image = $wpdb->escape($_POST['custom_image']);
		$b_asin = '';
		$b_added = date('Y-m-d h:i:s');
		$b_status = 'unread';
		
		foreach( compact('b_author', 'b_title', 'b_image', 'b_asin', 'b_added', 'b_status') as $field => $value )
			$query .= "$field=$value&";
		
		if( add_book($query) ) {
			wp_redirect(get_settings('home').'/wp-admin/post-new.php?page=now-reading-add.php&added=true');
			die;
		} else {
			wp_redirect(get_settings('home').'/wp-admin/post-new.php?page=now-reading-add.php&error=true');
			die;
		}
	}

?>