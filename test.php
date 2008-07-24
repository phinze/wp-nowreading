<?php

require_once '../../../wp-blog-header.php';

if ( !current_user_can('activate_plugins') )
	die;

if ( $_GET['amazon'] ) {
	echo "<p>query_amazon('title=1984&author=George Orwell')";
	$books = query_amazon('title=1984&author=George Orwell');
	if ( count($books) > 0 ) {
		echo "<br />Success, found " . count($books) . " books:<br /><pre>";
		var_dump($books);
		echo "</pre></p>";
	} else {
		echo "<br />Failure!</p>";
	}
} else {
	echo '
	<form method="get" action="">
	
	<input type="hidden" name="amazon" value="1" />
	
	<p><input type="submit" value="Test Amazon" /></p>
	
	</form>';
}

if ( $_GET['books'] ) {
	echo "<p>add_book('title=1984&author=George Orwell')";
	$id = add_book('title=1984&author=George Orwell');
	if ( $id > 0 ) {
		echo "<br />Successfully created book, ID is $id.</p>";
	} else {
		echo "<br />Failure!</p>";
	}
	
	echo "<p>get_book($id)";
	$book = get_book($id);
	if ( $book->ID == $id ) {
		echo "<br />Successfully fetched book with ID $id:<br /><pre>";
		var_dump($book);
		echo "</pre></p>";
	} else {
		echo "<br />Failure!</p>";
	}
	
	echo "<p>delete_book($id)";
	$del = delete_book($id);
	if ( $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->posts WHERE ID = %d", $id)) == 0 ) {
		echo "<br />Successfully deleted book with ID $id.</p>";
	} else {
		echo "<br />Failure!</p>";
	}
} else {
	echo '
	<form method="get" action="">
	
	<input type="hidden" name="books" value="1" />
	
	<p><input type="submit" value="Test Books" /></p>
	
	</form>';
}

?>