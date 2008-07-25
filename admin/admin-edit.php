<?php

if ( !function_exists('nr_edit') ) {
	
	function nr_edit() {
		global $books;
		
		$id = intval($_GET['id']);
		
		$book = get_book($id);
		?>
		
		<div class="wrap nr_edit">
			
			<h2>Edit Book</h2>
			
			<?php if ( $book->ID ) : ?>
				
				<form method="post" action="">
					
					<?php wp_nonce_field('nr_edit_' . $book->ID) ?>
					
					<img src="<?php echo $book->image ?>" alt="" />
					
					<p><label for="title">Title:</label></p>
					<p><input type="text" name="title" id="title" value="<?php echo htmlentities($book->title) ?>" /></p>
					
					<p><label for="author">Author:</label></p>
					<p><input type="text" name="author" id="author" value="<?php echo htmlentities($book->author) ?>" /></p>
					
				</form>
				
			<?php else: ?>
				<p>I can't find that book, sorry.</p>
			<?php endif; ?>
			
		</div>
		
		
		<?php
		
	}
	
}

?>