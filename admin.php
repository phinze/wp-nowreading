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
	';
	switch ( $_GET['page'] ) {
		case 'add_book':
		case 'manage_books':
		case 'nr_options':
		case 'edit_book':
			echo '
			<script type="text/javascript">
				jQuery(function() {
					jQuery("#submenu li:first").hide();
					jQuery("#submenu li:last").hide();
				});
			</script>
			';
			break;
	}
}
add_action('admin_head', 'nr_add_head');

require_once dirname(__FILE__) . '/admin/admin-add.php';
require_once dirname(__FILE__) . '/admin/admin-manage.php';
require_once dirname(__FILE__) . '/admin/admin-options.php';
require_once dirname(__FILE__) . '/admin/admin-edit.php';

/**
 * Manages the various admin pages Now Reading uses.
 */
function nr_add_pages() {
	$options = get_option('nowReadingOptions');

	add_menu_page('Now Reading', 'Now Reading', 'publish_posts', 'admin.php?page=add_book', 'nr_add');
	
	add_submenu_page('admin.php?page=add_book', 'Add a Book', 'Add a Book', 'publish_posts', 'add_book', 'nr_add');
	add_submenu_page('admin.php?page=add_book', 'Manage Books', 'Manage Books', 'edit_others_posts', 'manage_books', 'nr_manage');
	add_submenu_page('admin.php?page=add_book', 'Options', 'Options', 'manage_options', 'nr_options', 'nr_options');
	add_submenu_page('admin.php?page=add_book', 'Edit a Book', 'Edit a Book', 'edit_others_posts', 'edit_book', 'nr_edit');
}
add_action('admin_menu', 'nr_add_pages');

function admin_init() {
	if ( is_admin() && !empty($_POST) ) {
		switch ( $_GET['page'] ) {
			case 'edit_book':
				require_once dirname(__FILE__) . '/admin/edit.php';
		}
	}	
}
add_action('plugins_loaded', 'admin_init');

?>
