<?php get_header(); ?>

<div class="content">
	
	<div id="content" class="narrowcolumn primary now-reading">
	
	<div class="post">
	
	<?php if( can_now_reading_admin() ) : ?>
		
		<p>Admin: &raquo; <a href="<?php manage_library_url() ?>">Manage Books</a></p>
		
	<?php endif; ?>
	
	<p><a href="<?php library_url() ?>">&larr; Back to library</a></p>
	
	<?php library_search_form() ?>
	
	<p>Viewing books tagged with &ldquo;<?php the_tag(); ?>&rdquo;:</p>
	
	<?php if( have_books("tag={$GLOBALS['nr_tag']}") ) : ?>
		
		<ul>
		
		<?php while( have_books("tag={$GLOBALS['nr_tag']}") ) : the_book(); ?>
			
			<li><a href="<?php book_permalink() ?>"><?php book_title() ?></a> by <?php book_author() ?></li>
			
		<?php endwhile; ?>
		
		</ul>
		
	<?php else : ?>
		
		<p>Sorry, but there were no search results for your query.</p>
		
	<?php endif; ?>
	
	<p class="now-reading-copyright">Powered by <a href="http://robm.me.uk/">Rob Miller's</a> <a href="http://robm.me.uk/projects/plugins/wordpress/now-reading/">Now Reading</a></p>
	
	</div>
		
	</div>
	
</div>

<?php get_sidebar() ?>

<?php get_footer() ?>
