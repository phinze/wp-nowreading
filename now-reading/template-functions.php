<?php

$current_book = 0;
$books = null;
$book = null;

/**
 * Prints the book's title.
 */
function book_title( $echo = true ) {
	global $book;
	$title = apply_filters('book_title', $book->title);
	if( $echo )
		echo $title;
	return $title;
}

/**
 * Prints the author of the book.
 */
function book_author( $echo = true ) {
	global $book;
	$author = apply_filters('book_author', $book->author);
	if( $echo )
		echo $author;
	return $author;
}

/**
 * Prints a URL to the book's image, usually used within an HTML img element.
 */
function book_image( $echo = true ) {
	global $book;
	$image = apply_filters('book_image', $book->image);
	if( $echo )
		echo $image;
	return $image;
}

/**
 * Prints the date when the book was added to the database.
 */
function book_added( $echo = true ) {
	global $book;
	$added = apply_filters('book_added', $book->added);
	if( $echo )
		echo $added;
	return $added;
}

/**
 * Prints the date when the book's status was changed from unread to reading.
 */
function book_started( $echo = true ) {
	global $book;
	if( empty($book->started) )
		$started = __('Not yet started.', NRTD);
	else
		$started = $book->started;
	$started = apply_filters('book_started', $started);
	if( $echo )
		echo $started;
	return $started;
	
}

/**
 * Prints the date when the book's status was changed from reading to read.
 */
function book_finished( $echo = true ) {
	global $book;
	if( empty($book->finished) )
		$finished = __('Not yet finished.', NRTD);
	else
		$finished = $book->finished;
	$finished = apply_filters('book_finished', $finished);
	if( $echo )
		echo $finished;
	return $finished;
}

/**
 * Prints the number of books started and finished within a given time period.
 */
function books_read_since( $interval, $echo = true ) {
	global $wpdb;
	
	$interval = $wpdb->escape($interval);
	$num = $wpdb->get_var("
	SELECT
		COUNT(*) AS count
	FROM
		{$wpdb->prefix}now_reading
	WHERE
		DATE_SUB(CURDATE(), INTERVAL $interval) <= b_finished
	");
	
	if( $echo )
		echo "$num book".($num != 1 ? 's' : '');
	return $num;
}

/**
 * Prints the total number of books in the library.
 */
function total_books( $echo = true ) {
	global $wpdb;
	
	$num = $wpdb->get_var("
	SELECT
		COUNT(*) AS count
	FROM 
		{$wpdb->prefix}now_reading
	");
	
	if( $echo )
		echo "$num book".($num != 1 ? 's' : '');
	return $num;
}

/**
 * Prints the URL to an internal page displaying data about the book.
 */
function book_permalink( $echo = true, $id = 0 ) {
	global $book;
	$options = get_option('nowReadingOptions');
	
	if( $id == 0 )
		$id = $book->id;
	
	if( $options['useModRewrite'] )
		$url = get_settings('home')."/library/{$id}/";
	else
		$url = get_settings('home')."/index.php?now_reading_single=true&now_reading_id=$id";
	
	$url = apply_filters('book_permalink', $url);
	if( $echo )
		echo $url;
	return $url;
}

/**
 * Prints a URL to the book's Amazon detail page. If the book is a custom one, it will print a URL to the book's permalink page.
 * @see book_permalink()
 * @see is_custom_book()
 */
function book_url( $echo = true ) {
	global $book;
	$options = get_option('nowReadingOptions');
	
	if( is_custom_book() )
		book_permalink($echo);
	else {
		$url = apply_filters('book_url', "http://www.amazon{$options['domain']}/exec/obidos/ASIN/{$book->asin}/{$options['associate']}?tag={$options['associate']}");
		if( $echo )
			echo $url;
		return $url;
	}
}

/**
 * Returns true if the current book is linked to a post, false if it isn't.
 */
function book_has_post() {
	global $book;
	
	return ( $book->post > 0 );
}

/**
 * Returns or prints the permalink of the post linked to the current book.
 */
function book_post_url( $echo = true ) {
	global $book;
	
	if( !book_has_post() )
		return;
	
	$permalink = get_permalink($book->post);
	
	if( $echo )
		echo $permalink;
	return $permalink;
}

/**
 * Returns or prints the title of the post linked to the current book.
 */
function book_post_title( $echo = true ) {
	global $book;
	
	if( !book_has_post() )
		return;
	
	$post = get_post($book->post);
	
	if( $echo )
		echo $post->post_title;
	return $post->post_title;
}

/**
 * If the current book is linked to a post, prints an HTML link to said post.
 */
function book_post_link( $echo = true ) {
	global $book;
	
	if( !book_has_post() )
		return;
	
	$link = '<a href="'.book_post_url(0).'">'.book_post_title(0).'</a>';
	
	if( $echo )
		echo $link;
	return $link;
}

/**
 * If the user has the correct permissions, prints a URL to the Manage -> Now Reading page of the WP admin.
 */
function manage_library_url( $echo = true ) {
	if( can_now_reading_admin() )
		echo apply_filters('book_manage_url', get_settings('home').'/wp-admin/edit.php?page=now-reading-manage.php');
}

/**
 * If the user has the correct permissions, prints a URL to the review-writing screen for the current book.
 */
function book_edit_url( $echo = true ) {
	global $book;
	if( can_now_reading_admin() )
		echo apply_filters('book_edit_url', get_settings('home').'/wp-admin/edit.php?page=now-reading-manage.php&action=editsingle&id='.$book->id);
}

/**
 * Returns true if the book is a custom one or false if it is one from Amazon.
 */
function is_custom_book() {
	global $book;
	return empty($book->asin);
}

/**
 * Returns true if the user has the correct permissions to view the Now Reading admin panel.
 */
function can_now_reading_admin() {
	return current_user_can('level_9');
}

/**
 * Prints a URL pointing to the main library page that respects the useModRewrite option.
 */
function library_url( $echo = true ) {
	$options = get_option('nowReadingOptions');
	
	if( $options['useModRewrite'] )
		$url = get_settings('home').'/library/';
	else
		$url = get_settings('home').'/index.php?now_reading_library=true';
	
	$url = apply_filters('book_library_url', $url);
	
	if( $echo )
		echo $url;
	return $url;
}

/**
 * Prints the book's rating or "Unrated" if the book is unrated.
 */
function book_rating( $echo = true ) {
	global $book;
	if( $book->rating )
		echo apply_filters('book_rating', $book->rating);
	else
		echo apply_filters('book_rating', __('Unrated', NRTD));
}

/**
 * Prints the book's review or "This book has not yet been reviewed" if the book is unreviewed.
 */
function book_review( $echo = true ) {
	global $book;
	if( $book->review )
		echo apply_filters('book_review', $book->review);
	else
		echo apply_filters('book_review', '<p>'.__('This book has not yet been reviewed.', NRTD).'</p>');
}

/**
 * Prints the URL of the search page, ready to be appended with a query or simply used as the action of a GET form.
 */
function search_url( $echo = true ) {
	$options = get_option('nowReadingOptions');
	
	if( $options['useModRewrite'] )
		$url = get_settings('home').'/library/search?q=';
	else
		$url = get_settings('home').'/index.php?now_reading_search=true&q=';
	
	$url = apply_filters('library_search_url', $url);
	
	if( $echo )
		echo $url;
	return $url;
}

/**
 * Prints the current search query, if it exists.
 */
function search_query( $echo = true ) {
	global $query;
	if( !empty($query) )
		echo htmlentities(stripslashes($query));
}

/**
 * Prints a standard search form for users who don't want to create their own.
 */
function library_search_form( $echo = true ) {
	echo '
	<form method="get" action="'.search_url(0).'">
		<input type="text" name="q" /> <input type="submit" value="'.__("Search Library", NRTD).'" />
	</form>
	';
}

/**
 * Prints the book's meta data in a definition list.
 * @see get_book_meta()
 * @param bool $new_list Whether to start a new list (creating new <dl> tags).
 */
function print_book_meta( $new_list = true ) {
	global $book;
	
	$meta = get_book_meta($book->id);
	
	if( count($meta) < 1 )
		return;
	
	if( $new_list )
		echo '<dl>';
	
	foreach( $meta as $key => $value ) {
		$key = apply_filters('book_meta_key', $key);
		$value = apply_filters('book_meta_val', $value);
		
		echo '<dt>';
		if( strtolower($key) == $key )
			echo ucwords($key);
		else
			echo $key;
		echo '</dt>';
		
		echo "<dd>$value</dd>";
	}
	
	if( $new_list )
		echo '</dl>';
}

function book_meta( $key, $echo = true ) {
	global $book;
	
	$meta = get_book_meta($book->id, $key);
	
	if( count($meta) < 1 )
		return;
	
	$meta = apply_filters('book_meta_val', $meta[0]);
	
	if( $echo )
		echo $meta;
	return $meta;
}

/**
 * Prints a comma-separated list of tags for the current book.
 */
function print_book_tags( $echo = true ) {
	global $book;
	
	$tags = get_book_tags($book->id);
	
	if( count($tags) < 1 )
		return;
	
	$i = 0;
	$string = '';
	foreach( $tags as $tag ) {
		if( $i++ != 0 )
			$string .= ', ';
		$link = book_tag_url($tag, 0);
		$string .= "<a href='$link'>$tag</a>";
	}
	
	if( $echo )
		echo $string;
	return $string;
}

/**
 * Returns a URL to the permalink for a given tag.
 */
function book_tag_url( $tag, $echo = true ) {
	$options = get_option('nowReadingOptions');
	
	if( $options['useModRewrite'] )
		$url = get_settings('home').'/library/tag/'.urlencode($tag);
	else
		$url = get_settings('home').'/index.php?now_reading_tag=true&nr_tag='.urlencode($tag);
	
	$url = apply_filters('library_tag_url', $url);
	
	if( $echo )
		echo $url;
	return $url;
}

/**
 * Returns or prints the currently viewed tag.
 */
function the_tag( $echo = true ) {
	$tag = htmlentities(stripslashes($GLOBALS['nr_tag']));
	if( $echo )
		echo $tag;
	return $tag;
}

/**
 * Use in the main template loop; if un-fetched, fetches books for given $query and returns true whilst there are still books to loop through.
 * @param string $query The query string to pass to get_books()
 * @return boolean True if there are still books to loop through, false at end of loop.
 */
function have_books( $query ) {
	global $books, $current_book;
	if( !$books ) {
		if( strstr($query, 'tag=') ) {
			parse_str($query, $q);
			$GLOBALS['books'] = get_books_by_tag($q['tag']);
		} elseif( is_numeric($query) )
			$GLOBALS['books'] = get_book($query);
		else
			$GLOBALS['books'] = get_books($query);
	}
	if(is_a($books, 'stdClass'))
		$books = array($books);
	$have_books = ( !empty($books[$current_book]) );
	if( !$have_books ) {
		$GLOBALS['books']			= null;
		$GLOBALS['current_book']	= 0;
	}
	return $have_books;
}

/**
 * Advances counter used by have_books(), and sets the global variable $book used by the template functions. Be sure to call it each template loop to avoid infinite loops.
 */
function the_book() {
	global $books, $current_book;
	$GLOBALS['book'] = $books[$current_book];
	$GLOBALS['current_book']++;
}

?>