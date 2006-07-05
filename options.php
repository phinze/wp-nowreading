<?php

if ( !empty($_POST['update']) ) {
	$base = realpath(dirname(__FILE__) . '/../../../');
	chdir($base . '/wp-admin');
	require_once 'admin.php';
	
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
		$_POST['http_lib'] == 'snoopy';
	}
	
	update_option('nowReadingOptions', $options);
	
	wp_redirect($nr_url->urls['options'] . "&updated=1$append");
	die;
}

?>
