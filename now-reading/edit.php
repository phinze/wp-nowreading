<?php

if( strpos($_SERVER['REQUEST_URI'], 'wp-content/plugins') ) {
	define('ABSPATH', realpath(dirname(__FILE__) . '/../../../') . '/');
	require_once ABSPATH . '/wp-admin/admin.php';
	
	if( !current_user_can('level_9') )
		die ( __('Cheatin&#8217; uh?') );
	
	$wpvarstoreset = array('action');
	for ($i=0; $i<count($wpvarstoreset); $i += 1) {
		$wpvar = $wpvarstoreset[$i];
		if (!isset($$wpvar)) {
			if (empty($_POST["$wpvar"])) {
				if (empty($_GET["$wpvar"])) {
					$$wpvar = '';
				} else {
					$$wpvar = $_GET["$wpvar"];
				}
			} else {
				$$wpvar = $_POST["$wpvar"];
			}
		}
	}
	
	switch( $action ) {
		case 'delete':
			$id = intval($_GET['id']);
			
			check_admin_referer('now-reading-delete-book_' . $id);
			
			$wpdb->query("
			DELETE FROM {$wpdb->prefix}now_reading
			WHERE b_id = $id
			");
			
			wp_redirect('edit.php?page=now-reading-manage.php&delete=1');
			die;
		break;
		
		case 'update':
			check_admin_referer('now-reading-edit');
			
			$count = intval($_POST['count']);
			
			if( $count > total_books(0) )
				die;
			
			$updated = 0;
			
			for( $i = 0; $i < $count; $i++ ) {
				
				$id = intval($_POST['id'][$i]);
				if( $id == 0 )
					continue;
				
				$author		= $wpdb->escape($_POST['author'][$i]);
				$title		= $wpdb->escape($_POST['title'][$i]);
				$status		= $wpdb->escape($_POST['status'][$i]);
				$added		= ( empty($_POST['added'][$i]) )	? '0000-00-00 00:00:00' : $wpdb->escape(date('Y-m-d h:i:s', strtotime($_POST['added'][$i])));
				$started	= ( empty($_POST['started'][$i]) )	? '0000-00-00 00:00:00' : $wpdb->escape(date('Y-m-d h:i:s', strtotime($_POST['started'][$i])));
				$finished	= ( empty($_POST['finished'][$i]) )	? '0000-00-00 00:00:00' : $wpdb->escape(date('Y-m-d h:i:s', strtotime($_POST['finished'][$i])));
				$post		= intval($_POST['posts'][$i]);
				
				if( !empty($_POST['tags'][$i]) ) {
					// Delete current relationships and add them fresh.
					$wpdb->query("
					DELETE FROM
						{$wpdb->prefix}now_reading_books2tags
					WHERE
						book_id = '$id'
					");
					
					$tags = stripslashes($_POST['tags'][$i]);
					$tags = explode(',', $tags);
					
					foreach( $tags as $tag ) {
						$tag = trim($tag);
						tag_book($id, $tag);
					}
				}
				
				if( !empty($_POST["review"][$i]) ) {
					$rating = intval($_POST["rating"][$i]);
					$review = $wpdb->escape($_POST["review"][$i]);
					$review = ", b_rating = '$rating', b_review = '$review'";
				}
				
				$current_status = $wpdb->get_var("
				SELECT b_status
				FROM {$wpdb->prefix}now_reading
				WHERE b_id = $id
				");
				
				// If the book is currently "unread"/"reading" but is being changed to "read", we need to add a b_finished value.
				if( $current_status != 'read' && $status == 'read' )
					$finished = 'b_finished = "'.date('Y-m-d h:i:s').'",';
				else
					$finished = "b_finished = '$finished',";
				
				// Likewise, if the book is currently "unread" but is being changed to "reading", we need to add a b_started value.
				if( $current_status != 'reading' && $status == 'reading' )
					$started = 'b_started = "'.date('Y-m-d h:i:s').'",';
				else
					$started = "b_started = '$started',";
				
				$result = $wpdb->query("
				UPDATE {$wpdb->prefix}now_reading
				SET
					$started
					$finished
					b_author = '$author',
					b_title = '$title',
					b_status = '$status',
					b_added = '$added',
					b_post = '$post'
					$review
				WHERE
					b_id = $id
				");
				if( $wpdb->rows_affected > 0 )
					$updated++;
				
				// Meta stuff
				$keys = $_POST["keys-$i"];
				$vals = $_POST["values-$i"];
				
				if( count($keys) > 0 && count($vals) > 0 ) {
					for( $j = 0; $j < count($keys); $j++ ) {
						$key = $keys[$j];
						$val = $vals[$j];
						
						if( empty($key) || empty($val) )
							continue;
						
						update_book_meta($id, $key, $val);
					}
				}
			}
			
			$referer = wp_get_referer();
			if( empty($referer) )
				$forward = get_settings('home') . '/wp-admin/edit.php?page=now-reading-manage.php&updated=' . $updated;
			else
				$forward = wp_get_referer() . '&updated=' . $updated;
				
			header("Location: $forward");
			die;
		break;
	}
	
	die;
}


?>