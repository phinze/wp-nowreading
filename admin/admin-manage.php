<?php
/**
 * The admin interface for managing and editing books.
 * @package now-reading
 */

/**
 * Creates the manage admin page, and deals with the creation and editing of reviews.
 */
function nr_manage() {
	
	global $wpdb, $nr_statuses, $userdata;
	
	$options = get_option('nowReadingOptions');
	get_currentuserinfo();
	
	$list = true;
	
	$_POST = stripslashes_deep($_POST);
	
	$options = get_option('nowReadingOptions');
	
	if( !$nr_url ) {
		$nr_url = new nr_url();
		$nr_url->load_scheme($options['menuLayout']);
	}
	
	if ( !empty($_GET['updated']) ) {
		$updated = intval($_GET['updated']);
		
		if ( $updated == 1 )
			$updated .= ' book';
		else
			$updated .= ' books';
		
		echo '
		<div id="message" class="updated fade">
			<p><strong>' . $updated . ' updated.</strong></p>
		</div>
		';
	}
	
	if ( !empty($_GET['deleted']) ) {
		$deleted = intval($_GET['deleted']);
		
		if ( $deleted == 1 )
			$deleted .= ' book';
		else
			$deleted .= ' books';
		
		echo '
		<div id="message" class="updated fade">
			<p><strong>' . $deleted . ' deleted.</strong></p>
		</div>
		';
	}
	
	global $action;
	nr_reset_vars(array('action'));
	
	switch ( $action ) {
		case 'editsingle':
			$id = intval($_GET['id']);
			$existing = get_book($id);
			$meta = get_book_meta($existing->id);
			$tags = join(get_book_tags($existing->id), ',');
			
			$newer = nr_check_for_updates();
			if ( is_wp_error($newer) ) {
				echo '
				<div id="message" class="error fade">
					<p><strong>' . __("Oops!", NRTD) . '</strong></p>
					<p>' . __("I couldn't fetch the latest version of Now Reading, because you don't have cURL installed!", NRTD) . '</p>
					<p>' . __("To solve this problem, please switch your <strong>HTTP Library</strong> setting to <strong>Snoopy</strong>, which works on virtually all server setups.", NRTD) . '</p>
					<p>' . sprintf(__("You can change your options <a href='%s'>here</a>.", NRTD), $nr_url->urls['options']) . '</p>
				</div>
				';
			} elseif ( $newer ) {
				echo '<div id="message" class="error"><p><strong>' . sprintf(__("CAUTION: A newer version of Now Reading exists! Please download it <a href='%s'>here</a>.", NRTD), 'http://robm.me.uk/projects/plugins/wordpress/now-reading/') . '</strong></p></div>';
			}
			
			echo '
			<div class="wrap">
				<h2>' . __("Edit Book", NRTD) . '</h2>
				
				<form method="post" action="' . get_option('siteurl') . '/wp-content/plugins/now-reading/admin/edit.php">
			';
			
			if ( function_exists('wp_nonce_field') )
				wp_nonce_field('now-reading-edit');
			if ( function_exists('wp_referer_field') )
				wp_referer_field();
			
			echo '
				<input type="hidden" name="action" value="update" />
				<input type="hidden" name="count" value="1" />
				
				<div class="manage-book">
					
					<input type="hidden" name="id[]" value="' . $existing->id . '" />
					
					<div class="book-image">
						<img id="book-image-0" alt="Book Cover" src="' . $existing->image . '" />
					</div>
					
					<div class="book-details">
						<h3>' . __("Book", NRTD) . ' ' . $existing->id . ': &ldquo;' . $existing->title . '&rdquo; by ' . $existing->author . '</h3>
						
						<div id="book-details-extra-0">
							<p><label class="left" for="title-0">' . __("Title", NRTD) . ':</label> <input type="text" class="main" id="title-0" name="title[]" value="' . $existing->title . '" /></p>
							
							<p><label class="left" for="author-0">' . __("Author", NRTD) . ':</label> <input type="text" class="main" id="author-0" name="author[]" value="' . $existing->author . '" /></p>
						
							<p><label class="left" for="status-0">' . __("Status", NRTD) . ':</label>
								<select name="status[]" id="status-0">
				';
				foreach ( (array) $nr_statuses as $status => $name ) {
					$selected = '';
					if ( $existing->status == $status )
						$selected = ' selected="selected"';
					
					echo '
										<option value="' . $status . '"' . $selected . '>' . $name . '</option>
					';
				}
				echo '
								</select>
							</p>
							
							<p>
								<label for="added[]">' . __("Added", NRTD) . ':</label> <input type="text" id="added-0" name="added[]" value="' . htmlentities($existing->added, ENT_QUOTES, "UTF-8") . '" />
								
								<label for="started[]">' . __("Started", NRTD) . ':</label> <input type="text" id="started-0" name="started[]" value="' . htmlentities($existing->started, ENT_QUOTES, "UTF-8") . '" />
								
								<label for="finished[]">' . __("Finished", NRTD) . ':</label> <input type="text" id="finished-0" name="finished[]" value="' . htmlentities($existing->finished, ENT_QUOTES, "UTF-8") . '" />
							</p>
							
							<p><label class="left" for="image-0">' . __("Image", NRTD) . ':</label> <input type="text" class="main" id="image-0" name="image[]" value="' . htmlentities($existing->image) . '" /></p>
							
							<div id="book-meta-0">
								<h4>Meta-Data:</h4>
								<p><a href="#" onclick="addMeta(\'0\'); return false;">' . __("Add another field", NRTD) . ' +</a></p>
								<table>
									<thead>
										<tr>
											<th scope="col">' . __("Key", NRTD) . ':</th>
											<th scope="col">' . __("Value", NRTD) . ':</th>
											<th scope="col"></th>
										</tr>
									</thead>
									<tbody id="book-meta-table-0" class="book-meta-table">
			';
			foreach ( (array) $meta as $key => $val ) {
				$url = get_option('siteurl') . "/wp-content/plugins/now-reading/admin/edit.php?action=deletemeta&id={$existing->id}&key=" . urlencode($key);
				if ( function_exists('wp_nonce_url') )
					$url = wp_nonce_url($url, 'now-reading-delete-meta_' . $existing->id . $key);
				
				echo '
					<tr>
						<td><textarea name="keys-0[]" class="key">' . htmlspecialchars($key, ENT_QUOTES, "UTF-8") . '</textarea></td>
						<td><textarea name="values-0[]" class="value">' . htmlspecialchars($val, ENT_QUOTES, "UTF-8") . '</textarea></td>
						<td><a href="' . $url . '">' . __("Delete", NRTD) . '</a></td>
					</tr>
				';
			}
			echo '
										<tr>
											<td><textarea name="keys-0[]" class="key"></textarea></td>
											<td><textarea name="values-0[]" class="value"></textarea></td>
										</tr>
									</tbody>
								</table>
							</div>
							
							<h4>Tags:</h4>
							<p>' . __("A comma-separated list of keywords that describe the book.", NRTD) . '</p>
							
							<p><input type="text" name="tags[]" value="' . htmlspecialchars($tags, ENT_QUOTES, "UTF-8") . '" /></p>
							
							<h4>' . __("Link to post", NRTD) . ':</h4>
							<p>' . __("If you wish, you can link this book to a blog entry by entering that entry's ID here. The entry will be linked to from the book's library page.", NRTD) . '</p>
							
							<p><input type="text" name="posts[]" value="' . intval($existing->post) . '" /></p>
							
							<h4>' . __("Actions", NRTD) . ':</h4>
							<ul>
								<li><a href="' . book_permalink(0, $existing->id) . '">' . __("View library entry", NRTD) . '</a></li>
			';

			$delete = get_option('siteurl') . '/wp-content/plugins/now-reading/admin/edit.php?action=delete&id=' . $existing->id;
			if ( function_exists('wp_nonce_url') )
				$delete = wp_nonce_url($delete, 'now-reading-delete-book_' . $existing->id);

			echo '
								<li><a href="' . $delete . '" onclick="return confirm(\'' . __("Are you sure you wish to delete this book permanently?", NRTD) . '\')">' . __("Delete", NRTD) . '</a></li>
							</ul>
						</div>
						
						<h4>' . __("Review", NRTD) . ':</h4>
					
						<p><label for="rating">' . __("Rating", NRTD) . ':</label><br />
						<select name="rating[]" id="rating-' . $i . '" style="width:100px;">
							<option value="unrated">&nbsp;</option>
				';
				for ($i = 10; $i >=1; $i--) {
					$selected = ($i == $existing->rating) ? ' selected="selected"' : '';
					echo "
							<option value='$i'$selected>$i</option>";
				}
				echo '
						</select></p>
						
						<p><label for="review">' . __("Review", NRTD) . ':</label><br />
						<textarea name="review[]" id="review-' . $i . '" style="width:500px; height:200px">' . htmlentities($existing->review, ENT_QUOTES, "UTF-8") . '</textarea></p>
						
						<p style="display:none;" id="review-size-link">
								<small>
								<a accesskey="i" href="#" onclick="reviewBigger(\'' . $i . '\'); return false;">' . __("Increase size", NRTD) . ' (Alt + I)</a>
								&middot;
								<a accesskey="d" href="#" onclick="reviewSmaller(\'' . $i . '\'); return false;">' . __("Decrease size", NRTD) . ' (Alt + D)</a>
							</small>
						</p>
						
						<p class="submit">
							<input type="submit" value="' . __("Save", NRTD) . ' &raquo;" />
						</p>
					</div>
				</div>
				<br style="clear:left;" />
					
				</form>
				
			</div>
			';
			$list = false;
		break;
	}
	
	if ( $list ) {
		//depends on multiusermode (B. Spyckerelle)
		if ($options['multiuserMode']) {
			$count = total_books(0, 0, $userdata->ID); //counting only current users books
		} else {
			$count = total_books(0, 0); //counting all books
		}
		
		
		if ( $count ) {
			if ( !empty($_GET['q']) )
				$search = '&search=' . urlencode($_GET['q']);
			else
				$search = '';
			
			if ( empty($_GET['p']) )
				$page = 1;
			else
				$page = intval($_GET['p']);
			
			$perpage = $options['booksPerPage'];
			
			$offset = ($page * $perpage) - $perpage;
			$num = $perpage;
			$pageq = "&num=$num&offset=$offset";
			
			//depends on multiuser mode
			if ($options['multiuserMode']) {
				$reader = "&reader=".$userdata->ID;
			} else {
				$reader = '';
			}
			
			$books = get_books("num=-1&status=all&orderby=status&order=desc{$search}{$pageq}{$reader}"); //get only current reader's books -> &reader=$reader_id
			$count = count($books);
			
			$numpages = ceil(total_books(0, 0, $userdata->ID) / $perpage);
			
			$pages = '<p>' . __("Pages", NRTD) . ':';
			
			if ( $page > 1 ) {
				$previous = $page - 1;
				$pages .= " <a href='{$nr_url->urls['manage']}&p=$previous'>&laquo;</a>";
			}
			
			for ( $i = 1; $i <= $numpages; $i++) {
				if ( $page == $i )
					$pages .= " $i";
				else
					$pages .= " <a href='{$nr_url->urls['manage']}&p=$i'>$i</a>";
			}
			
			if ( $numpages > $page ) {
				$next = $page + 1;
				$pages .= " <a href='{$nr_url->urls['manage']}&p=$next'>&raquo;</a>";
			}
			
			$pages .= '</p>';
			
			echo '
			<div class="wrap">
			
				<h2>Now Reading</h2>
				
				<div class="nr-actions">
					<form method="get" action="" onsubmit="location.href += \'&q=\' + document.getElementById(\'q\').value; return false;">
						<p><label for="q">' . __("Search books", NRTD) . ':</label> <input type="text" name="q" id="q" value="' . htmlentities($_GET['q']) . '" /> <input type="submit" value="' . __('Search', NRTD) . '" /></p>
					</form>
					
					<div>
						<ul>
			';
			if ( !empty($_GET['q']) ) {
				echo '
							<li><a href="' . $nr_url->urls['manage'] . '">' . __('Show all books', NRTD) . '</a></li>
				';
			}
			echo '
							<li><a href="' . library_url(0) . '">' . __('View library', NRTD) . '</a></li>
						</ul>
					</div>
					
					<div>
						' . $pages . '
					</div>
				</div>
				
				<br style="clear:both;" />
				
				<form method="post" action="' . get_option('siteurl') . '/wp-content/plugins/now-reading/admin/edit.php">
			';
				
			if ( function_exists('wp_nonce_field') )
				wp_nonce_field('now-reading-edit');
			if ( function_exists('wp_referer_field') )
				wp_referer_field();
				
			echo '
				<input type="hidden" name="action" value="update" />
				<input type="hidden" name="count" value="' . $count . '" />
			';
			
			$i = 0;
			
			foreach ( (array) $books as $book ) {
				
				$meta = get_book_meta($book->id);
				$tags = join(get_book_tags($book->id), ',');
				
				$alt = ( $i % 2 == 0 ) ? ' alternate' : '';
				
				$delete = get_option('siteurl') . '/wp-content/plugins/now-reading/admin/edit.php?action=delete&id=' . $book->id;
				if ( function_exists('wp_nonce_url') )
					$delete = wp_nonce_url($delete, 'now-reading-delete-book_' . $book->id);
				
				echo '
					<div class="manage-book' . $alt . '">
						
						<input type="hidden" name="id[]" value="' . $book->id . '" />
						<input type="hidden" name="title[]" value="' . $book->title . '" />
						<input type="hidden" name="author[]" value="' . $book->author . '" />
						
						<div class="book-image">
							<img id="book-image-' . $i . '" class="small" alt="' . __('Book Cover', NRTD) . '" src="' . $book->image . '" />
						</div>
						
						<div class="book-details">
							<h3>' . __('Book', NRTD) . ' ' . $book->id . ': &ldquo;' . stripslashes($book->title) . '&rdquo; by ' . $book->author . ' <a href="#" id="book-edit-link-' . $i . '" onclick="toggleBook(\'' . $i . '\'); return false;">' . __("Edit", NRTD) . ' &darr;</a></h3>
							
							<p>(' . $nr_statuses[$book->status] . ')</p>
							
							<div id="book-details-extra-' . $i . '" class="book-details-extra">
							
								<p><label class="left" for="status[]">' . __("Status", NRTD) . ':</label>
									<select name="status[]">
				';
				foreach ( (array) $nr_statuses as $status => $name ) {
					$selected = '';
					if ( $book->status == $status )
						$selected = ' selected="selected"';
					
					echo '
										<option value="' . $status . '"' . $selected . '>' . $name . '</option>
					';
				}
				echo '
									</select>
								</p>
								
								<p>
									<label for="added[]">' . __('Added', NRTD) . ':</label> <input type="text" id="added-' . $i . '" name="added[]" value="' . $book->added . '" />
									
									<label for="started[]">' . __('Started', NRTD) . ':</label> <input type="text" id="started-' . $i . '" name="started[]" value="' . $book->started . '" />
									
									<label for="finished[]">' . __('Finished', NRTD) . ':</label> <input type="text" id="finished-' . $i . '" name="finished[]" value="' . $book->finished . '" />
								</p>
								
								<p>Tags: <input type="text" name="tags[]" value="' . htmlspecialchars($tags, ENT_QUOTES, "UTF-8") . '" /></p>
								
								<h4>' . __('Actions', NRTD) . ':</h4>
								<ul>
									<li><a href="' . book_permalink(0, $book->id) . '">' . __('View library entry', NRTD) . '</a></li>
									<li><a href="' . $nr_url->urls['manage'] . '&amp;action=editsingle&amp;id=' . $book->id . '">' . __('Edit details/write review', NRTD) . '</a> (' . (($book->rating == 0) ? __('Not yet rated', NRTD) : __('Current rating', NRTD) . ': ' . $book->rating . '/10') . ')</li>
									<li><a href="' . $delete . '" onclick="return confirm(\'' . __("Are you sure you wish to delete this book permanently?", NRTD) . '\')">' . __("Delete", NRTD) . '</a></li>
								</ul>
							</div>
							
							<p><a href="#">' . __('Top', NRTD) . ' &uarr;</a> <a href="#update">' . __('Bottom', NRTD) . ' &darr;</a></p>
						</div>
					</div>
					<br style="clear:left;" />
				';
				
				$i++;
				
			}
			
			echo '
				
				<p class="submit">
					<input type="submit" id="update" value="' . __("Update", NRTD) . ' &raquo;" />
				</p>
				
				</form>
			';
			
		} else {
			echo '
			<div class="wrap">
				<h2>' . __("Manage Books", NRTD) . '</h2>
				<p>' . sprintf(__("No books to display. To add some books, head over <a href='%s'>here</a>.", NRTD), $nr_url->urls['add']) . '</p>
			</div>
			';
		}
			
		echo '
		</div>
		';
	}
}

?>
