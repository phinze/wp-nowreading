<?php

if( !empty($_POST['update']) ) {
	define('ABSPATH', realpath(dirname(__FILE__) . '/../../../') . '/');
	require_once ABSPATH . '/wp-admin/admin.php';
	
	if( !current_user_can('level_9') )
		die ( __('Cheatin&#8217; uh?') );
	
	check_admin_referer('now-reading-update-options');
	
	$_POST = stripslashes_deep($_POST);
	
	$append = '';
	
	$options['formatDate']		= $_POST['format_date'];
	$options['associate']		= $_POST['associate'];
	$options['domain']			= $_POST['domain'];
	$options['debugMode']		= $_POST['debug_mode'];
	$options['useModRewrite']	= $_POST['use_mod_rewrite'];
	
	switch( $_POST['image_size'] ) {
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
	
	if( $_POST['http_lib'] == 'curl' ) {
		if( !function_exists('curl_init') ) {
			$options['httpLib'] = 'snoopy';
			$append .= '&curl=1';
		} else {
			$options['httpLib'] = 'curl';
		}
	} else {
		$_POST['http_lib'] == 'snoopy';
	}
	
	update_option('nowReadingOptions', $options);
	
	wp_redirect(get_settings('home')."/wp-admin/options-general.php?page=now-reading-options.php&updated=1$append");
	die;
}

?>