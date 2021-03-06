<?php
/**
 * Book fetching/updating functions
 * @package now-reading
 */
 
 /**
 * Fetches books from the database based on a given query.
 * @param string $query Query string containing restrictions on what to fetch.
 * @return array Returns a numerically indexed array in which each element corresponds to a book.
 */
function get_books( $query = '' ) {
	global $books;
	
	$options = get_option('nowReadingOptions');
	
	$defaults = array(
		'post_type' => 'book',
		'numberposts' => $options['booksPerPage'],
		'offset' => 0,
		'status' => 'all'
	);
	
	$r = wp_parse_args($query, $defaults);
	
	$books = query_posts($r);
	
	foreach ( (array) $books as $book ) {
		$meta = get_post_custom($book->ID);
		foreach ( (array) $meta as $key => $value ) {
			if ( strpos($key, 'book_') !== false ) {
				$key = str_replace('book_', '', $key);
				$book->$key = join(', ', $value);
			}
		}
	}
	
	return $books;
}

/**
 * Fetches a single book with the given ID.
 * @param int $id The ID of the book you want to fetch.
 */
function get_book( $id ) {
	$options = get_option('nowReadingOptions');
	
	$id = intval($id);
	
	$books = get_books('include=' . $id);
	
	return $books[0];
}

/**
 * Adds a book to the database.
 * @param string $query Query string containing the fields to add.
 * @return int The ID of the book added.
 */
function add_book( $query ) {
	$defaults = array(
		'asin' => '',
		'title' => '',
		'author' => '',
		'image' => '',
		'binding' => '',
		'edition' => '',
		'isbn' => '',
		'publicationdate' => '',
		'numberofpages' => '',
		'publisher' => ''
	);
	
	$r = wp_parse_args($query, $defaults);
	
	$post = array(
		'post_type' => 'book',
		'post_status' => 'publish',
		'post_title' => $r['title'],
		'comment_status' => 'closed',
		'ping_status' => 'closed',
		'post_mime_type' => 'now-reading/book'
	);
	
	$id = wp_insert_post($post);
	
	foreach ( (array) $r as $key => $val ) {
		update_book_meta($id, "book_{$key}", $val);
	}
	
	return $id;
}

/**
 * Updates a given book's database entry
 * @param string $query Query string containing the fields to add.
 * @return boolean True on success, false on failure.
 */
function update_book( $query ) {
	return wp_update_post($query);
}

/**
 * Deletes a given book's database entry
 * @param int $id The ID of the book to delete.
 * @return boolean True on success, false on failure.
 */
function delete_book( $id ) {
	return wp_delete_post($id);
}

/**
 * Gets the tags for the given book.
 */
function get_book_tags( $id, $args = array() ) {
	return wp_get_post_tags($id, $args);
}

/**
 * Tags the book with the given tag.
 */
function tag_book( $id, $tag ) {
	return set_book_tags($id, $tag, true);
}

/**
 * Sets the tags for the given book.
 * @param bool $append If true, add the given tags onto the existing ones; if false, replace current tags with new ones.
 */
function set_book_tags( $id, $tags, $append = false ) {
	return wp_set_post_tags($id, $tags, $append);
}

/**
 * Fetches meta-data for the given book.
 */
function get_book_meta( $id, $key, $single = true ) {
	if ( strpos($key, 'book_') === false )
		$key = "book_$key";
	return get_post_meta($id, $key, $single);
}

/**
 * Updates the meta key-value pairing for the given book. If the key does not exist, it will be created.
 */
function update_book_meta( $id, $key, $value, $prev_value = '' ) {
	return update_post_meta($id, $key, $value, $prev_value);
}

/**
 * Deletes the meta key-value pairing for the given book with the given key.
 */
function delete_book_meta( $id, $key, $value = '' ) {
	return delete_post_meta($id, $key, $value);
}

function current_book_status( $id ) {
	$meta = get_post_custom($id);
	
	$started = count($meta['started']);
	$finished = count($meta['finished']);
	
	if ( $started == 0 )
		return 'unread';
	if ( $started < $finished )
		return 'reading';
	else
		return 'read';
	
}

?>