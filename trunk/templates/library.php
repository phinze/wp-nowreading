<?php get_header() ?>

<div class="content">
	
	<div id="content" class="now-reading primary narrowcolumn">
	
	<div class="post">
		
		<?php if( can_now_reading_admin() ) : ?>
			
			<p>Admin: &raquo; <a href="<?php manage_library_url() ?>">Manage Books</a></p>
			
		<?php endif; ?>
		
		<p><?php total_books() ?> overall; <?php books_read_since('1 year') ?> read in the last year; <?php books_read_since('1 month') ?> read in the last month.</p>
		
		<?php library_search_form() ?>
		
		<h2>Planned books:</h2>
		
		<?php if( have_books('status=unread&num=-1') ) : ?>
			
			<ul>
			
			<?php while( have_books('status=unread&num=-1') ) : the_book(); ?>
				
				<li><a href="<?php book_permalink() ?>"><?php book_title() ?></a> by <?php book_author() ?></li>
				
			<?php endwhile; ?>
			
			</ul>
			
		<?php else : ?>
			
			<p>None</p>
			
		<?php endif; ?>
		
		<h2>Current books:</h2>
		
		<?php if( have_books('status=reading&num=-1') ) : ?>
			
			<ul>
			
			<?php while( have_books('status=reading&num=-1') ) : the_book(); ?>
				
				<li>
					<p><a href="<?php book_permalink() ?>"><img src="<?php book_image() ?>" alt="<?php book_title() ?>" /></a></p>
					<p><?php book_title() ?> by <?php book_author() ?></p>
				</li>
				
			<?php endwhile; ?>
			
			</ul>
			
		<?php else : ?>
			
			<p>None</p>
			
		<?php endif; ?>
		
		<h2>Recent books:</h2>
		
		<?php if( have_books('status=read&orderby=finished&order=desc&num=-1') ) : ?>
			
			<ul>
			
			<?php while( have_books('status=read&orderby=finished&order=desc&num=-1') ) : the_book(); ?>
				
				<li><a href="<?php book_permalink() ?>"><?php book_title() ?></a> by <?php book_author() ?></li>
				
			<?php endwhile; ?>
			
			</ul>
			
		<?php else : ?>
			
			<p>None</p>
			
		<?php endif; ?>
		
		<p class="now-reading-copyright">Powered by <a href="http://robm.me.uk/">Rob</a>'s <a href="http://robm.me.uk/projects/plugins/wordpress/now-reading/">Now Reading</a> plugin.</p>
		
	</div>
	
	</div>
	
</div>

<?php get_sidebar() ?>

<?php get_footer() ?>
