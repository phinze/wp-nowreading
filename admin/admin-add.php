<?php

if ( !function_exists('nr_add') ) {
	
	function nr_add() {
		
		function add_book_form() {
			?>
			<form method="post" action="">
				
				<?php wp_nonce_field('nr_add_book') ?>
				
				<input type="hidden" name="stage" value="2" />
				
				<div class="by_isbn">
					<h3>Search by ISBN</h3>
					
					<p class="add_isbn"><label for="isbn">ISBN:</label></p>
					<p><input type="text" id="isbn" name="isbn" /></p>
					
					<p><input type="submit" value="Search" /></p>
				</div>
				
				<div class="by_title">
					<h3>Search by title/author</h3>
					
					<p class="add_title"><label for="title">Title</label></p>
					<p><input type="text" id="title" name="title" /></p>
					
					<p class="add_author"><label for="author">Author (optional)</label></p>
					<p><input type="text" id="author" name="author" /></p>
					
					<p><input type="submit" value="Search" /></p>
				</div>
				
			</form>
			<?php
		}
		
		$stage = intval($_REQUEST['stage']);
		
		?>
		
		<div class="wrap nr_add">
			<h2>Add a Book</h2>
			
			<?php if ( empty($stage) || $stage == 1 ) : ?>
			<p>Enter the details of the book you'd like to find, and Now Reading will search Amazon to try and find it.</p>
			
			<?php add_book_form() ?>
			
			<?php
			
				elseif ( $stage == 2 ) :
					
					check_admin_referer('nr_add_book');
					
					$isbn = $_POST['isbn'];
					$title = $_POST['title'];
					$author = $_POST['author'];
					
					$books = query_amazon("title=$title&author=$author&isbn=$isbn");
					
					if ( count($books) > 0 ) :
					
			?>
						<p>I found <?php echo count($books) ?> results for your query:</p>
							
						<div class="amazon_results">
					
						<?php foreach ( (array) $books as $book ) : ?>
							
							<div class="amazon_result">
								<form method="post" action="">
									<?php wp_nonce_field('nr_add_book_' . md5(serialize($book))) ?>
									
									<input type="hidden" name="stage" value="3" />
									<input type="hidden" name="book" value="<?php echo htmlentities(serialize($book)) ?>" />
									
									<img src="<?php echo $book['image'] ?>" alt="" />
									
									<h3><?php echo $book['title'] ?></h3>
									<p>by <?php echo $book['author'] ?></p>
									
									<p><input type="submit" value="Use this result" /></p>
								</form>
							</div>
							
						<?php endforeach; ?>
						
						</div>
						
						<p>No good? <a href="?page=add_book">Try searching again</a>.</p>
						
					<?php else: ?>
						<p>Sorry, I couldn't find any results from Amazon.</p>
						
						<p><a href="?page=add_book">Search again?</a></p>
					<?php endif; ?>
				
			<?php elseif ( $stage == 3 ) : ?>
				
				<?php
				
				check_admin_referer('nr_add_book_' . md5(stripslashes($_POST['book'])));
				
				$book = unserialize(stripslashes($_POST['book']));
				
				$id = add_book($book);
				
				if ( $id > 0 ) :
				
				?>
					<br />
					<div class="updated"><p><strong>Success! Your book was added.</strong></p></div>
					
					<p>Add another?</p>
					
					<?php add_book_form() ?>
					
				<?php else: ?>
					
					<p>Oops! There was an error adding your book.</p>
					
				<?php endif; ?>
				
			<?php endif; ?>
		
		</div>
		
		<?php
		
	}
	
}

?>