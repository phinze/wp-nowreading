<?php
/**
 * Adds our admin menus, and some stylesheets and JavaScript to the admin head.
 * @package now-reading
 */

require_once dirname(__FILE__) . '/admin/admin-add.php';
require_once dirname(__FILE__) . '/admin/admin-manage.php';
require_once dirname(__FILE__) . '/admin/admin-options.php';
require_once dirname(__FILE__) . '/admin/admin-edit.php';

function is_now_reading_admin() {
	return (
		is_admin() &&
		( $_GET['page'] == 'add_book'     ||
		  $_GET['page'] == 'manage_books' ||
		  $_GET['page'] == 'nr_options'   ||
		  $_GET['page'] == 'edit_book'       )
	);
}

/**
 * Manages the various admin pages Now Reading uses.
 */
function nr_add_pages() {
	$options = get_option('nowReadingOptions');

	add_menu_page('Now Reading', 'Now Reading', 'publish_posts', 'add_book', 'nr_add');
	
	add_submenu_page('add_book', __('Add a Book'), __('Add a Book'), 'publish_posts', 'add_book', 'nr_add');
	add_submenu_page('add_book', __('Manage Books'), __('Manage Books'), 'edit_others_posts', 'manage_books', 'nr_manage');
	add_submenu_page('add_book', __('Options'), __('Options'), 'manage_options', 'nr_options', 'nr_options');
}
add_action('admin_menu', 'nr_add_pages');

function admin_init() {
	if ( is_now_reading_admin() ) {
		
		if ( !empty($_POST) ) {
			switch ( $_GET['page'] ) {
				case 'edit_book':
					require_once dirname(__FILE__) . '/admin/edit.php';
			}
		}
		
		wp_enqueue_script('jquery');
		
		wp_enqueue_script('now_reading_admin', plugins_url('now-reading/admin/admin.js'));
		wp_enqueue_style('now_reading_admin', plugins_url('now-reading/admin/admin.css'));
	}
}
add_action('plugins_loaded', 'admin_init');

?>
