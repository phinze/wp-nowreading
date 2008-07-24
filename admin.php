<?php
/**
 * Adds our admin menus, and some stylesheets and JavaScript to the admin head.
 * @package now-reading
 */

/**
 * Adds our stylesheets and JS to admin pages.
 */
function nr_add_head() {
	echo '
	<link rel="stylesheet" href="' . get_bloginfo('url') . '/wp-content/plugins/now-reading/admin/admin.css" type="text/css" />
	<script type="text/javascript">
		var lHide = "' . __("Hide", NRTD) . '";
		var lEdit = "' . __("Edit", NRTD) . '";
	</script>
	<script type="text/javascript" src="' . get_bloginfo('url') . '/wp-content/plugins/now-reading/js/manage.js"></script>
	';
	switch ( $_GET['page'] ) {
		case 'add_book':
		case 'manage_books':
		case 'nr_options':
			echo '
			<script type="text/javascript">
				function hideNRMenu() {
					document.getElementById("submenu").getElementsByTagName("li")[0].style.display = "none";
				}
				addLoadEvent(hideNRMenu);
			</script>
			';
			break;
	}
}
add_action('admin_head', 'nr_add_head');

require_once dirname(__FILE__) . '/admin/admin-add.php';
require_once dirname(__FILE__) . '/admin/admin-manage.php';
require_once dirname(__FILE__) . '/admin/admin-options.php';

/**
 * Manages the various admin pages Now Reading uses.
 */
function nr_add_pages() {
	$options = get_option('nowReadingOptions');
	
	//B. Spyckerelle
	//changing NR level access in order to let blog authors to add books in multiuser mode
	$nr_level = $options['multiuserMode'] ? 2 : 9 ;
	
	if ( $options['menuLayout'] == NR_MENU_SINGLE ) {
		add_menu_page('Now Reading', 'Now Reading', 9, 'admin.php?page=add_book', 'now_reading_add');
		
		add_submenu_page('admin.php?page=add_book', 'Add a Book', 'Add a Book',$nr_level , 'add_book', 'now_reading_add');
		add_submenu_page('admin.php?page=add_book', 'Manage Books', 'Manage Books', $nr_level, 'manage_books', 'nr_manage');
		add_submenu_page('admin.php?page=add_book', 'Options', 'Options', 9, 'nr_options', 'nr_options');
	} else {
		if ( file_exists( ABSPATH . '/wp-admin/post-new.php' ) )
			add_submenu_page('post-new.php', 'Now Reading', 'Now Reading', $nr_level, 'add_book', 'now_reading_add');
		else
			add_submenu_page('post.php', 'Now Reading', 'Now Reading', $nr_level, 'add_book', 'now_reading_add');
		
		add_management_page('Now Reading', 'Now Reading', $nr_level, 'manage_books', 'nr_manage');
		add_options_page('Now Reading', 'Now Reading', 9, 'nr_options', 'nr_options');
	}
}
add_action('admin_menu', 'nr_add_pages');

?>
