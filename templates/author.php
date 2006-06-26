<?php get_header() ?>

<div class="content">
	
	<div id="content" class="now-reading primary narrowcolumn">
	
	<div class="post">
		
		<?php if( can_now_reading_admin() ) : ?>
			
			<p>Admin: &raquo; <a href="<?php manage_library_url() ?>">Manage Books</a></p>
			
		<?php endif; ?>
		
		<?php library_search_form() ?>
		
		<p><a href="<?php library_url() ?>">&larr; Back to library</a></p>
		
		<h2>Books by <?php the_book_author() ?></h2>
		
		<?php if( have_books("author={$GLOBALS['nr_author']}") ) : ?>
			
			<ul>
			
			<?php while( have_books("author={$GLOBALS['nr_author']}") ) : the_book(); ?>
				
				<li>
					<p><a href="<?php book_permalink() ?>"><img src="<?php book_image() ?>" alt="<?php book_title() ?>" /></a></p>
					<p><?php book_title() ?></p>
				</li>
				
			<?php endwhile; ?>
			
			</ul>
			
		<?php else : ?>
			
			<p>There are no books by this author!</p>
			
		<?php endif; ?>
		
		<p class="now-reading-copyright">Powered by <a href="http://robm.me.uk/">Rob</a>'s <a href="http://robm.me.uk/projects/plugins/wordpress/now-reading/">Now Reading</a> plugin.</p>
		
	</div>
	
	</div>
	
</div>

<?php get_sidebar() ?>

<?php get_footer() ?>
