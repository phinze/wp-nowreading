<?php
/*
Plugin Name: Now Reading
Version: 4.4.4-beta
Plugin URI: http://robm.me.uk/projects/plugins/wordpress/now-reading/
Description: Allows you to display the books you're reading, have read recently and plan to read, with cover art fetched automatically from Amazon.
Author: Rob Miller
Author URI: http://robm.me.uk/
 */
/**
 * @author Rob Miller <r@robm.me.uk>
 * @version 4.4.4-beta
 * @package now-reading
 */

define('NOW_READING_VERSION', '5.0-beta');
define('NOW_READING_DB', 40);
define('NOW_READING_OPTIONS', 10);
define('NOW_READING_REWRITE', 9);

define('NRTD', 'now-reading');

define('NR_MENU_SINGLE', 2);
define('NR_MENU_MULTIPLE', 4);

define('NR_BASENAME', plugin_basename(__FILE__));
define('NR_PATH', dirname(NR_BASENAME));

/**
 * Load our l18n domain.
 */
$locale = get_locale();
$path = "wp-content/plugins/now-reading/translations/$locale";
load_plugin_textdomain(NRTD, $path);

/**
 * Array of the statuses that books can be.
 * @global array $GLOBALS['nr_statuses']
 * @name $nr_statuses
 */
$nr_statuses = apply_filters('nr_statuses', array(
	'unread'	=> __('Yet to read', NRTD),
	'onhold'	=> __('On Hold', NRTD),
	'reading'	=> __('Currently reading', NRTD),
	'read'		=> __('Finished', NRTD)
));

/**
 * Array of the domains we can use for Amazon.
 * @global array $GLOBALS['nr_domains']
 * @name $nr_domains
 */
$nr_domains = array(
	'.com'		=> __('International', NRTD),
	'.co.uk'	=> __('United Kingdom', NRTD),
	'.fr'		=> __('France', NRTD),
	'.de'		=> __('Germany', NRTD),
	'.co.jp'	=> __('Japan', NRTD),
	'.ca'		=> __('Canada', NRTD)
);

// Include other functionality
require_once dirname(__FILE__) . '/compat.php';
require_once dirname(__FILE__) . '/url.php';
require_once dirname(__FILE__) . '/book.php';
require_once dirname(__FILE__) . '/amazon.php';
require_once dirname(__FILE__) . '/admin.php';
require_once dirname(__FILE__) . '/default-filters.php';
require_once dirname(__FILE__) . '/template-functions.php';
require_once dirname(__FILE__) . '/widget.php';

/**
 * Checks if the install needs to be run by checking the `nowReadingVersions` option, which stores the current installed database, options and rewrite versions.
 */
function nr_check_versions() {
	$versions = get_option('nowReadingVersions');
	if ( empty($versions) )
		nr_install();
	else {
		if ( $versions['db'] < NOW_READING_DB || $versions['options'] < NOW_READING_OPTIONS || $versions['rewrite'] < NOW_READING_REWRITE )
			nr_install();
	}
}
add_action('init', 'nr_check_versions');

/**
 * Handler for the activation hook. Installs/upgrades the database table and adds/updates the nowReadingOptions option.
 */
function nr_install() {
	global $wpdb, $wp_rewrite, $wp_version;
	
	if ( version_compare('2.0', $wp_version) == 1 && strpos($wp_version, 'wordpress-mu') === false ) {
		echo '
		<p><code>+++ Divide By Cucumber Error. Please Reinstall Universe And Reboot +++</code></p>
		<p>Melon melon melon</p>
		<p>(Now Reading only works with WordPress 2.0 and above, sorry!)</p>
		';
		return;
	}
	
	$defaultOptions = array(
		'formatDate'	=> 'jS F Y',
		'associate'		=> 'roblog-21',
		'domain'		=> '.com',
		'imageSize'		=> 'Medium',
		'httpLib'		=> 'snoopy',
		'useModRewrite'	=> false,
		'debugMode'		=> false,
		'menuLayout'	=> NR_MENU_MULTIPLE,
		'booksPerPage'  => 15,
		'permalinkBase' => 'library/'
	);
	add_option('nowReadingOptions', $defaultOptions);
	
	// Merge any new options to the existing ones.
	$options = get_option('nowReadingOptions');
	$options = array_merge($defaultOptions, $options);
	update_option('nowReadingOptions', $options);
	
	// Update our .htaccess file.
	$wp_rewrite->flush_rules();
	
	// Set an option that stores the current installed versions of the database, options and rewrite.
	$versions = array('db' => NOW_READING_DB, 'options' => NOW_READING_OPTIONS, 'rewrite' => NOW_READING_REWRITE);
	update_option('nowReadingVersions', $versions);
}
register_activation_hook('now-reading/now-reading.php', 'nr_install');

/**
 * Adds information to the header for future statistics purposes.
 */
function nr_header_stats() {
	echo '
	<meta name="now-reading-version" content="' . NOW_READING_VERSION . '" />
	';
}
add_action('wp_head', 'nr_header_stats');

?>
