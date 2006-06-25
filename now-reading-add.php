<?php

/**
 * The write admin page deals with the searching for and ultimate addition of books to the database.
 */
function now_reading_add() {
	
	$_POST = stripslashes_deep($_POST);
	
	global $wpdb;
	
	$options = get_option('nowReadingOptions');
	
	if( !empty($_GET['error']) ) {
		echo '
		<div id="message" class="error fade">
			<p><strong>'.__("Error adding book!", NRTD).'</strong></p>
		</div>
		';
	}
	
	if( !empty($_GET['added']) ) {
		echo '
		<div id="message" class="updated fade">
			<p><strong>'.__("Book added.", NRTD).'</strong></p>
			<ul>
				<li><a href="edit.php?page=now-reading-manage.php">'.__("Manage books", NRTD).' &raquo;</a></li>
                <li><a href="'.library_url(0).'">'.__("View Library", NRTD).' &raquo;</a></li>
				<li><a href="'.get_settings('home').'">'.__("View Site").' &raquo;</a></li>
			</ul>
		</div>
		';
	}
	
	echo '
	<div class="wrap">
				
		<h2>Now Reading</h2>
	';
	
	$newer = nr_check_for_updates();
	if( is_wp_error($newer) ) {
		echo '
		<div id="message" class="error fade">
			<p><strong>'.__("Oops!", NRTD).'</strong></p>
			<p>'.__("I couldn't fetch the latest version of Now Reading, because you don't have cURL installed!", NRTD).'</p>
			<p>'.__("To solve this problem, please switch your <strong>HTTP Library</strong> setting to <strong>Snoopy</strong>, which works on virtually all server setups.", NRTD).'</p>
			<p>'.sprintf(__("You can change your options <a href='%s'>here</a>.", NRTD), 'options-general.php?page=now-reading-manage.php').'</p>
		</div>
		';
	} elseif( $newer ) {
		echo '<p style="color:red;"><strong>'.sprintf(__("CAUTION: A newer version of Now Reading exists! Please download it <a href='%s'>here</a>.", NRTD), 'http://robm.me.uk/projects/plugins/wordpress/now-reading/').'</strong></p>';
	}
	
	if( !empty($_POST['u_isbn']) || !empty($_POST['u_title']) ) {
		
		echo '<h3>'.__("Search Results", NRTD).'</h3>';
		
		$isbn	= $_POST['u_isbn'];
		$author	= $_POST['u_author'];
		$title	= $_POST['u_title'];
		if( !empty($_POST['u_isbn']) )
			$using_isbn = true;
			
		if( $using_isbn )
			$results = query_amazon("isbn=$isbn");
		else
			$results = query_amazon("title=$title&author=$author");
		
		if( is_wp_error($results) ) {
			foreach( $results->get_error_codes() as $code ) {
				if( $code == 'curl-not-installed' ) {
					echo '
						<div id="message" class="error fade">
							<p><strong>'.__("Oops!", NRTD).'</strong></p>
							<p>'.__("I couldn't fetch the results for your search, because you don't have cURL installed!", NRTD).'</p>
							<p>'.__("To solve this problem, please switch your <strong>HTTP Library</strong> setting to <strong>Snoopy</strong>, which works on virtually all server setups.", NRTD).'</p>
							<p>'.sprintf(__("You can change your options <a href='%s'>here</a>.", NRTD), 'options-general.php?page=now-reading-options.php').'</p>
						</div>
					';
				}
			}
		} else {
			if( count($results) < 1 ) {
				if( $using_isbn )
					echo '<p>'.sprintf(__("Sorry, but amazon%s did not return any results for the ISBN number <code>%s</code>.", NRTD), $options['domain'], $isbn).'</p>';
				else
					echo '<p>'.sprintf(__("Sorry, but amazon%s did not return any results for the book &ldquo;%s&rdquo;", NRTD), $options['domain'], $title).'</p>';
			} else {
				if( $using_isbn )
					echo '<p>'.sprintf(__("You searched for the ISBN <code>%s<code>. amazon%s returned these results:", NRTD), $isbn, $options['domain']).'</p>';
				else
					echo '<p>'.sprintf(__("You searched for the book &ldquo;%s&rdquo;. amazon%s returned these results:", NRTD), $title, $options['domain']).'</p>';
				
				foreach( $results as $result ) {
					extract($result);
					$data = serialize($result);
					echo '
					<form method="post" action="post-new.php?page=now-reading-add.php" style="border:1px solid #ccc; padding:5px; margin:5px;">
					';
					
					if ( function_exists('wp_nonce_field') )
						wp_nonce_field('now-reading-add-' . $title);
					
					echo '
						<input type="hidden" name="amazon_data" value="'.htmlentities($data, ENT_COMPAT, "UTF-8").'" />
						
						<img src="'.htmlentities($image, ENT_COMPAT, "UTF-8").'" alt="" style="float:left; margin:8px; padding:2px; width:46px; height:70px; border:1px solid #ccc;" />
						
						<h3>'.htmlentities($title, ENT_COMPAT, "UTF-8").'</h3>
						'.(($author) ? '<p>by <strong>'.htmlentities($author, ENT_COMPAT, "UTF-8").'</strong></p>' : '<p>('.__("No author", NRTD).')</p>').'
						
						<p style="clear:left;"><input type="submit" value="'.__("Use This Result", NRTD).'" /></p>
						
					</form>
					';
				}
			}
		}
		
		echo '
		<p>'.__("Not found what you like? You have a couple of options.", NRTD).'</p>
		
		<p>'.__("If you like, you can add a book manually", NRTD).':</p>
		
		<div class="nr-add-grouping">
		
		<h3>'.__("Add a book manually", NRTD).'</h3>
		
		<form method="post" action="post-new.php?page=now-reading-add.php">
		
		';
		
		if ( function_exists('wp_nonce_field') )
			wp_nonce_field('now-reading-manual-add');
		
		echo '
			<p><label for="custom_title">'.__("Title").':</label><br />
			<input type="text" name="custom_title" id="custom_title" size="50" /></p>
			
			<p><label for="custom_author">'.__("Author").':</label><br />
			<input type="text" name="custom_author" id="custom_author" size="50" /></p>
			
			<p><label for="custom_image">'.__("Link to image").':</label><br />
			<small>'.__("Remember, leeching images from other people's servers is nasty. Upload your own images or use Amazon's.", NRTD).'</small><br />
			<input type="text" name="custom_image" id="custom_image" size="50" /></p>
			
			<p><input type="submit" value="'.__("Add Book", NRTD).'" /></p>
			
		</form>
		
		</div>

		<p>'.__("Or, you can try searching again with some different search terms", NRTD).':</p>
		
		
		<div class="nr-add-grouping">
		
		<h3>Search again</h3>
		';
	} 
	
	elseif( !empty($_POST['amazon_data']) ) {
		
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
		
		if( add_book($query) ) {
			wp_redirect(get_settings('home').'/wp-admin/post-new.php?page=now-reading-add.php&added=true');
			die;
		} else {
			wp_redirect(get_settings('home').'/wp-admin/post-new.php?page=now-reading-add.php&error=true');
			die;
		}
	}
	
	elseif( !empty($_POST['custom_title']) ) {
		
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
			echo '
			<div id="message" class="updated fade">
				<p><strong>'.__("Book added", NRTD).'.</strong></p>
				<ul>
					<li><a href="edit.php?page=now-reading-manage.php">'.__("Manage books", NRTD).' &raquo;</a></li>
					<li><a href="'.get_settings('home').'">'.__("View Site", NRTD).' &raquo;</a></li>
				</ul>
			</div>
			';
		} else {
			echo '
			<div id="message" class="error fade">
				<p><strong>'.__("Error adding book!", NRTD).'</strong></p>
			</div>
			';
		}
	}
	
	if( empty($_POST) )
		echo '
		<div class="nr-add-grouping">
		<h3>Add a new book</h3>';
	
	echo '
	
	<p>'.__("Enter some information about the book that you'd like to add, and I'll try to fetch the information directly from Amazon.", NRTD).'</p>
	
	<p>'.sprintf(__("Now Reading is currently set to search the <strong>amazon%s</strong> domain; you can change this setting and others in the <a href='%s'>options page</a>.", NRTD), $options['domain'], "options-general.php?page=now-reading-options.php").'</p>
	
	<form method="post" action="post-new.php?page=now-reading-add.php">
	';
	
	if ( function_exists('wp_nonce_field') )
		wp_nonce_field('now-reading-add');
	
	echo '
		<p><label for="isbn"><acronym title="International Standard Book Number">ISBN</acronym>:</label><br />
		<input type="text" name="u_isbn" id="isbn" size="13" value="'.$results[0]['asin'].'" /></p>
		
		<p><strong>'.__("or", NRTD).'</strong></p>

		<p><label for="title">'.__("Title", NRTD).':</label><br />
		<input type="text" name="u_title" id="title" size="50" value="'.$results[0]['title'].'" /></p>
		
		<p><label for="title">'.__("Author", NRTD).' ('.__("optional", NRTD).'):</label><br />
		<input type="text" name="u_author" id="author" size="50" value="'.$results[0]['author'].'" /></p>
		
		<p><input type="submit" value="'.__("Search", NRTD).'" /></p>
		
	</form>
	
	</div>
		
	</div>
	';
	
}

?>
