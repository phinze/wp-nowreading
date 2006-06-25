<?php

/**
 * Adds our stylesheets and JS to admin pages.
 */
function nr_add_head() {
	echo '<link rel="stylesheet" href="'.get_settings('home').'/wp-content/plugins/now-reading/admin.css" type="text/css" />';
	echo '<script type="text/javascript" src="'.get_settings('home').'/wp-content/plugins/now-reading/js/manage.js"></script>';
}
add_action('admin_head', 'nr_add_head');

require_once 'now-reading-add.php';
require_once 'now-reading-manage.php';
require_once 'now-reading-options.php';

/**
 * Manages the various admin pages Now Reading uses.
 */
function nr_add_pages() {
	add_submenu_page('post.php', 'Now Reading', 'Now Reading', 9, 'now-reading-add.php', 'now_reading_add');
	add_submenu_page('post-new.php', 'Now Reading', 'Now Reading', 9, 'now-reading-add.php', 'now_reading_add');
	
	add_management_page('Now Reading', 'Now Reading', 9, 'now-reading-manage.php', 'nr_manage');
	
	add_options_page('Now Reading', 'Now Reading', 9, 'now-reading-options.php', 'nr_options');
}
add_action('admin_menu', 'nr_add_pages');

?>
