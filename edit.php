<?php

if ( strpos($_SERVER['REQUEST_URI'], 'wp-content/plugins') ) {
	$base = realpath(dirname(__FILE__) . '/../../../');
	chdir($base . '/wp-admin');
	require_once 'admin.php';
	
	$_POST = stripslashes_deep($_POST);
	
	if ( !current_user_can('level_9') )
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
	
	switch ( $action ) {
		case 'delete':
			$id = intval($_GET['id']);
			
			check_admin_referer('now-reading-delete-book_' . $id);
			
			$wpdb->query("
			DELETE FROM {$wpdb->prefix}now_reading
			WHERE b_id = $id
			");
			
			wp_redirect($nr_url->urls['manage'] . '&deleted=1');
			die;
		break;
		
		case 'update':
			check_admin_referer('now-reading-edit');
			
			$count = intval($_POST['count']);
			
			if ( $count > total_books(0, 0) )
				die;
			
			$updated = 0;
			
			for ( $i = 0; $i < $count; $i++ ) {
				
				$id = intval($_POST['id'][$i]);
				if ( $id == 0 )
					continue;
				
				$author			= $wpdb->escape($_POST['author'][$i]);
				$title			= $wpdb->escape($_POST['title'][$i]);
				
				$nice_author	= $wpdb->escape(sanitize_title($_POST['author'][$i]));
				$nice_title		= $wpdb->escape(sanitize_title($_POST['title'][$i]));
				
				$status			= $wpdb->escape($_POST['status'][$i]);
				
				$added			= ( empty($_POST['added'][$i]) )	? '0000-00-00 00:00:00' : $wpdb->escape(date('Y-m-d h:i:s', strtotime($_POST['added'][$i])));
				$started		= ( empty($_POST['started'][$i]) )	? '0000-00-00 00:00:00' : $wpdb->escape(date('Y-m-d h:i:s', strtotime($_POST['started'][$i])));
				$finished		= ( empty($_POST['finished'][$i]) )	? '0000-00-00 00:00:00' : $wpdb->escape(date('Y-m-d h:i:s', strtotime($_POST['finished'][$i])));
				
				$post			= intval($_POST['posts'][$i]);
				$visible		= ( $_POST['visible'] ) ? 1 : 0;
				
				$rating			= ( is_numeric($_POST['rating'][$i]) ) ? intval($_POST["rating"][$i]) : 0;
				$review			= $wpdb->escape($_POST["review"][$i]);
				
				if ( !empty($_POST['tags'][$i]) ) {
					// Delete current relationships and add them fresh.
					$wpdb->query("
					DELETE FROM
						{$wpdb->prefix}now_reading_books2tags
					WHERE
						book_id = '$id'
					");
					
					$tags = stripslashes($_POST['tags'][$i]);
					$tags = explode(',', $tags);
					
					foreach ( (array) $tags as $tag ) {
						$tag = trim($tag);
						tag_book($id, $tag);
					}
				}
				
				$current_status = $wpdb->get_var("
				SELECT b_status
				FROM {$wpdb->prefix}now_reading
				WHERE b_id = $id
				");
				
				// If the book is currently "unread"/"reading" but is being changed to "read", we need to add a b_finished value.
				if ( $current_status != 'read' && $status == 'read' )
					$finished = 'b_finished = "' . date('Y-m-d h:i:s') . '",';
				else
					$finished = "b_finished = '$finished',";
				
				// Likewise, if the book is currently "unread" but is being changed to "reading", we need to add a b_started value.
				if ( $current_status != 'reading' && $status == 'reading' )
					$started = 'b_started = "' . date('Y-m-d h:i:s') . '",';
				else
					$started = "b_started = '$started',";
				
				$result = $wpdb->query("
				UPDATE {$wpdb->prefix}now_reading
				SET
					$started
					$finished
					b_author = '$author',
					b_title = '$title',
					b_nice_author = '$nice_author',
					b_nice_title = '$nice_title',
					b_status = '$status',
					b_added = '$added',
					b_post = '$post',
					b_visible = '$visible',
					b_rating = '$rating',
					b_review = '$review'
				WHERE
					b_id = $id
				");
				if ( $wpdb->rows_affected > 0 )
					$updated++;
				
				// Meta stuff
				$keys = $_POST["keys-$i"];
				$vals = $_POST["values-$i"];
				
				if ( count($keys) > 0 && count($vals) > 0 ) {
					for ( $j = 0; $j < count($keys); $j++ ) {
						$key = $keys[$j];
						$val = $vals[$j];
						
						if ( empty($key) || empty($val) )
							continue;
						
						update_book_meta($id, $key, $val);
					}
				}
			}
			
			$referer = wp_get_referer();
			if ( empty($referer) )
				$forward = $nr_url->urls['manage'] . '&updated=' . $updated;
			else
				$forward = wp_get_referer() . '&updated=' . $updated;
			
			header("Location: $forward");
			die;
		break;
		
		case 'deletemeta':
			$id = intval($_GET['id']);
			$key = $_GET['key'];
			
			check_admin_referer('now-reading-delete-meta_' . $id . $key);
			
			delete_book_meta($id, $key);
			
			$forward = $nr_url->urls['manage'] . "&action=editsingle&id=$id&updated=1";
			header("Location: $forward");
			die;
		break;
	}
	
	die;
}


?>
