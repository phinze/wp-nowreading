<?php
/**
 * Updates our options
 * @package now-reading
 */

if ( !empty($_POST['update']) ) {
	require '../../../../wp-config.php';
	
	if ( !current_user_can('level_9') )
		die ( __('Cheatin&#8217; uh?') );
	
	check_admin_referer('now-reading-update-options');
	
	$_POST = stripslashes_deep($_POST);
	
	$append = '';
	
	$options['formatDate']		= $_POST['format_date'];
	$options['associate']		= $_POST['associate'];
	$options['domain']			= $_POST['domain'];
	$options['debugMode']		= $_POST['debug_mode'];
	$options['useModRewrite']	= $_POST['use_mod_rewrite'];
	$options['menuLayout']		= ( $_POST['menu_layout'] == 'single' ) ? NR_MENU_SINGLE : NR_MENU_MULTIPLE;
	$options['proxyHost']		= $_POST['proxy_host'];
	$options['proxyPort']		= $_POST['proxy_port'];
	$options['booksPerPage']    = $_POST['books_per_page'];
	$options['permalinkBase']   = $_POST['permalink_base'];
	$options['multiuserMode']	= $_POST['multiuser_mode'];
	
	$nr_url->load_scheme($options['menuLayout']);
	
	switch ( $_POST['image_size'] ) {
		case 'Small':
		case 'Medium':
		case 'Large':
			$options['imageSize'] = $_POST['image_size'];
			break;
		default:
			$append .= '&imagesize=1';
			$options['imageSize'] = 'Medium';
			break;
	}
	
	if ( $_POST['http_lib'] == 'curl' ) {
		if ( !function_exists('curl_init') ) {
			$options['httpLib'] = 'snoopy';
			$append .= '&curl=1';
		} else {
			$options['httpLib'] = 'curl';
		}
	} else {
		$options['httpLib'] = 'snoopy';
	}
	
	update_option('nowReadingOptions', $options);
	
	global $wp_rewrite;
	$wp_rewrite->flush_rules();
	
	wp_redirect($nr_url->urls['options'] . "&updated=1$append");
	die;
}

?>
