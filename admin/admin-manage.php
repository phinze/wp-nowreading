<?php

if ( !function_exists('nr_manage') ) {
	
	function nr_manage() {
		global $books;
		
		$options = get_option('nowReadingOptions');
		
		$page = intval($_GET['paged']);
		$page = $page > 0 ? $page : 1;
		$perpage = $options['booksPerPage'];
		$offset = ( $page - 1 ) * $perpage;
		
		$query = "offset=$offset";
		
		if ( !empty($_GET['tag']) )
			$query .= "&tag=" . $_GET['tag'];
		
		$books = get_books($query);
		
		$total = total_books(0);
		$pages = ceil($total / $perpage);
		
		$pagination = '';
		if ( $page > 1 ) {
			$prev = $page - 1;
			$pagination .= '<a class="previous page-numbers" href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=manage_books&paged=' . $prev . '">&laquo;</a>';
		}
		
		for ( $i = 1; $i <= $pages; $i++ ) {
			$pagination .= "<a class='page-numbers' href='" . get_bloginfo('wpurl') . "/wp-admin/admin.php?page=manage_books&paged=$i'>$i</a>";
		}
		
		if ( $page < $pages ) {
			$next = $page + 1;
			$pagination .= '<a class="next page-numbers" href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=manage_books&paged=' . $next . '">&raquo;</a>';
		}
		
		?>
		
		<div class="wrap nr_manage">
			
			<?php
			switch ($_GET['message']) {
				case '1':
					echo '<div class="updated fade"><p><strong>Book deleted.</strong></p></div>';
					break;
			}
			?>
			
			<h2>Manage Books</h2>
			
			<?php if ( count($books) ) : ?>
			
			<div class="tablenav">

				<div class="alignleft actions">
					<input type="text" name="search" id="search" value="Search:" class="greyed" />
				</div>
				
					<div class="tablenav-pages">
					<span class="displaying-num">Displaying <?php echo $offset + 1 ?>&#8211;<?php echo $offset + count($books) ?> of <?php echo $total ?></span>
					
						<?php echo $pagination; ?>
					</div>
					
				<div class="clear"></div>
			
			</div>
				
				<table class="widefat post" cellspacing="0">
					<thead>
						<tr>
							<th scope="col"></th>
							<th scope="col">Title</th>
							<th scope="col">Author</th>
							<th scope="col">Status</th>
							<th scope="col">Tags</th>
							<th scope="col"></th>
						</tr>
					</thead>
					<tbody>

				<?php foreach ( (array) $books as $book ) : ?>
					
					<tr>
						<td><img src="<?php echo $book->image ?>"></td>
						<td><a href="?page=edit_book&id=<?php echo $book->ID ?>"><?php echo $book->title ?></a></td>
						<td><?php echo $book->author ?></td>
						<td><?php echo ucwords(current_book_status($book->ID)) ?></td>
						<td>
							<?php foreach ( get_book_tags($book->ID) as $tag ) : ?>
								<a href="?page=manage_books&tag=<?php echo htmlentities($tag->name) ?>">
									<?php echo $tag->name ?>
								</a>
							<?php endforeach; ?>
						</td>
						<td>
							<form method="post" action="admin.php?page=edit_book">
								<?php wp_nonce_field('nr_delete_' . $book->ID) ?>
								<input type="hidden" name="action" value="delete" />
								<input type="hidden" name="id" value="<?php echo $book->ID ?>" />
								<input type="submit" value="Delete" class="button-secondary delete" />
							</form>
						</td>
					</tr>
					
				<?php endforeach; ?>
				
				</table>
				
				<div class="tablenav">

					<div class="alignleft actions">
						<input type="text" name="search" id="search" value="Search:" class="greyed" />
					</div>
					
						<div class="tablenav-pages">
						<span class="displaying-num">Displaying <?php echo $offset + 1 ?>&#8211;<?php echo $offset + count($books) ?> of <?php echo $total ?></span>
						
							<?php echo $pagination; ?>
						</div>
						
					<div class="clear"></div>

				</div>
				
			<?php else: ?>
				<p>You don't have any books in your library! Head over <a href="?page=add_book">here</a> to add some.</p>
			<?php endif; ?>
			
		</div>
		
		
		<?php
		
	}
	
}

?>