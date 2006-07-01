<?php

/**
 * Adds our stylesheets and JS to admin pages.
 */
function nr_add_head() {
	echo '<link rel="stylesheet" href="' . get_settings('home') . '/wp-content/plugins/now-reading/admin.css" type="text/css" />';
	echo '<script type="text/javascript" src="' . get_settings('home') . '/wp-content/plugins/now-reading/js/manage.js"></script>';
}
add_action('admin_head', 'nr_add_head');

require_once 'now-reading-add.php';
require_once 'now-reading-manage.php';
require_once 'now-reading-options.php';

/**
 * Manages the various admin pages Now Reading uses.
 */
function nr_add_pages() {
	$options = get_option('nowReadingOptions');
	
	if ( $options['menuLayout'] == NR_MENU_SINGLE ) {
		add_menu_page('Now Reading', 'Now Reading', 9, 'admin.php?action=add');
	} else {
		add_submenu_page('post.php', 'Now Reading', 'Now Reading', 9, 'now-reading-add.php', 'now_reading_add');
		add_submenu_page('post-new.php', 'Now Reading', 'Now Reading', 9, 'now-reading-add.php', 'now_reading_add');
		
		add_management_page('Now Reading', 'Now Reading', 9, 'now-reading-manage.php', 'nr_manage');
		
		add_options_page('Now Reading', 'Now Reading', 9, 'now-reading-options.php', 'nr_options');
	}
}
add_action('admin_menu', 'nr_add_pages');

function nr_admin_add() {
	switch ( $_GET['action'] ) {
		case 'add':
			now_reading_add();
		break;
		case 'manage':
		break;
		case 'options':
		break;
		default:
			die('Oops!');
	}
}
if ( $_GET['action'] ) {
	define('ABSPATH', realpath(dirname(__FILE__) . '/../../../') . '/');
	
	$parent_file = 'admin.php';
	$submenu_file = 'edit.php';
	
	require_once ABSPATH . '/wp-admin/admin.php';
	require_once ABSPATH . '/wp-admin/admin-header.php';
	
	nr_admin_add();
}

?>
