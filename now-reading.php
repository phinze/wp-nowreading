<?php
/*
Plugin Name: Now Reading
Version: 4.4.1
Plugin URI: http://robm.me.uk/projects/plugins/wordpress/now-reading/
Description: Allows you to display the books you're reading, have read recently and plan to read, with cover art fetched automatically from Amazon.
Author: Rob Miller
Author URI: http://robm.me.uk/
 */
/**
 * @author Rob Miller <r@robm.me.uk>
 * @version 4.4.1
 * @package now-reading
 */

define('NOW_READING_VERSION', '4.4.1');
define('NOW_READING_DB', 38);
define('NOW_READING_OPTIONS', 10);
define('NOW_READING_REWRITE', 7);

define('NRTD', 'now-reading');

define('NR_MENU_SINGLE', 2);
define('NR_MENU_MULTIPLE', 4);

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
	
	// WP's dbDelta function takes care of installing/upgrading our DB table.
	$upgrade_file = file_exists(ABSPATH . 'wp-admin/upgrade.php') ? ABSPATH . 'wp-admin/upgrade.php' : ABSPATH . 'wp-admin/upgrade-functions.php';
	require_once $upgrade_file;
	// Until the nasty bug with duplicate indexes is fixed, we should hide dbDelta output.
	ob_start();
	dbDelta("
	CREATE TABLE {$wpdb->prefix}now_reading (
	b_id bigint(20) NOT NULL auto_increment,
	b_added datetime NOT NULL default '0000-00-00 00:00:00',
	b_started datetime NOT NULL default '0000-00-00 00:00:00',
	b_finished datetime NOT NULL default '0000-00-00 00:00:00',
	b_title VARCHAR(100) NOT NULL default '',
	b_nice_title VARCHAR(100) NOT NULL default '',
	b_author VARCHAR(100) NOT NULL default '',
	b_nice_author VARCHAR(100) NOT NULL default '',
	b_image text NOT NULL default '',
	b_asin varchar(12) NOT NULL default '',
	b_status VARCHAR(8) NOT NULL default 'read',
	b_rating tinyint(4) NOT NULL default '0',
	b_review text NOT NULL default '',
	b_post bigint(20) NOT NULL default '0',
	PRIMARY KEY  (b_id),
	INDEX permalink (b_nice_author, b_nice_title),
	INDEX title (b_title),
	INDEX author (b_author)
	);
	CREATE TABLE {$wpdb->prefix}now_reading_meta (
	m_id BIGINT(20) NOT NULL auto_increment,
	m_book BIGINT(20) NOT NULL DEFAULT '0',
	m_key VARCHAR(100) NOT NULL default '',
	m_value TEXT NOT NULL default '',
	PRIMARY KEY  (m_id),
	INDEX m_key (m_key)
	);
	CREATE TABLE {$wpdb->prefix}now_reading_tags (
	t_id BIGINT(20) NOT NULL auto_increment,
	t_name VARCHAR(100) NOT NULL DEFAULT '',
	PRIMARY KEY  (t_id),
	INDEX t_name (t_name)
	);
	CREATE TABLE {$wpdb->prefix}now_reading_books2tags (
	rel_id BIGINT(20) NOT NULL auto_increment,
	book_id BIGINT(20) NOT NULL DEFAULT '0',
	tag_id BIGINT(20) NOT NULL DEFAULT '0',
	PRIMARY KEY  (rel_id),
	INDEX book (book_id)
	);
	");
	$log = ob_get_contents();
	ob_end_clean();
	
	$log_file = dirname(__FILE__) . '/install-log-' . date('Y-m-d') . '.txt';
	if ( is_writable($log_file) ) {
		$fh = @fopen( $log_file, 'w' );
		if ( $fh ) {
			fwrite($fh, strip_tags($log));
			fclose($fh);
		}
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
	
	// Update our nice titles/authors.
	$books = $wpdb->get_results("
	SELECT
		b_id AS id, b_title AS title, b_author AS author
	FROM
		{$wpdb->prefix}now_reading
	WHERE
		b_nice_title = '' OR b_nice_author = ''
	");
	foreach ( (array) $books as $book ) {
		$nice_title = $wpdb->escape(sanitize_title($book->title));
		$nice_author = $wpdb->escape(sanitize_title($book->author));
		$id = intval($book->id);
		$wpdb->query("
		UPDATE
			{$wpdb->prefix}now_reading
		SET
			b_nice_title = '$nice_title',
			b_nice_author = '$nice_author'
		WHERE
			b_id = '$id'
		");
	}
	
	// De-activate and attempt to delete the old widget.
	$active_plugins = get_option('active_plugins');
	foreach ( (array) $active_plugins as $key => $plugin ) {
		if ( $plugin == 'widgets/now-reading.php' ) {
			unset($active_plugins[$key]);
			sort($active_plugins);
			update_option('active_plugins', $active_plugins);
			break;
		}
	}
	$widget_file = ABSPATH . '/wp-content/plugins/widgets/now-reading.php';
	if ( file_exists($widget_file) ) {
		@chmod($widget_file, 0666);
		if ( !@unlink($widget_file) )
			die("Please delete your <code>wp-content/plugins/widgets/now-reading.php</code> file!");
	}
	
	// Set an option that stores the current installed versions of the database, options and rewrite.
	$versions = array('db' => NOW_READING_DB, 'options' => NOW_READING_OPTIONS, 'rewrite' => NOW_READING_REWRITE);
	update_option('nowReadingVersions', $versions);
}
register_activation_hook('now-reading/now-reading.php', 'nr_install');

/**
 * Checks to see if the library/book permalink query vars are set and, if so, loads the appropriate templates.
 */
function library_init() {
	global $wp, $wpdb, $q, $query, $wp_query;
	
	$wp->parse_request();
	
	if ( is_now_reading_page() )
		add_filter('wp_title', 'nr_page_title');
	else
		return;
	
	if ( $wp_query->get('now_reading_library') ) {
		// Library page:
		nr_load_template('library.php');
		die;
	}
	
	if ( $wp_query->get('now_reading_id') ) {
		// Book permalink:
		$GLOBALS['nr_id'] = intval($wp_query->get('now_reading_id'));
		
		$load = nr_load_template('single.php');
		if ( is_wp_error($load) )
			echo $load->get_error_message();
		
		die;
	}
	
	if ( $wp_query->get('now_reading_tag') ) {
		// Tag permalink:
		$GLOBALS['nr_tag'] = $wp_query->get('now_reading_tag');
		
		$load = nr_load_template('tag.php');
		if ( is_wp_error($load) )
			echo $load->get_error_message();
		
		die;
	}
	
	if ( $wp_query->get('now_reading_search') ) {
		// Search page:
		$GLOBALS['query'] = $_GET['q'];
		unset($_GET['q']); // Just in case
		
		$load = nr_load_template('search.php');
		if ( is_wp_error($load) )
			echo $load->get_error_message();
		
		die;
	}
	
	if ( $wp_query->get('now_reading_author') && $wp_query->get('now_reading_title') ) {
		// Book permalink with title and author.
		$author				= $wpdb->escape(urldecode($wp_query->get('now_reading_author')));
		$title				= $wpdb->escape(urldecode($wp_query->get('now_reading_title')));
		$GLOBALS['nr_id']	= $wpdb->get_var("
		SELECT
			b_id
		FROM
			{$wpdb->prefix}now_reading
		WHERE
			b_nice_title = '$title'
			AND
			b_nice_author = '$author'
		");
		
		$load = nr_load_template('single.php');
		if ( is_wp_error($load) )
			echo $load->get_error_message();
		
		die;
	}
	
	if ( $wp_query->get('now_reading_author') ) {
		// Author permalink.
		$author = $wpdb->escape(urldecode($wp_query->get('now_reading_author')));
		$GLOBALS['nr_author'] = $wpdb->get_var("SELECT b_author FROM {$wpdb->prefix}now_reading WHERE b_nice_author = '$author'");
		
		if ( empty($GLOBALS['nr_author']) )
			die("Invalid author");
		
		$load = nr_load_template('author.php');
		if ( is_wp_error($load) )
			echo $load->get_error_message();
		
		die;
	}
}
add_action('template_redirect', 'library_init');

/**
 * Loads the given filename from either the current theme's now-reading directory or, if that doesn't exist, the Now Reading templates directory.
 * @param string $filename The filename of the template to load.
 */
function nr_load_template( $filename ) {
	$filename = basename($filename);
	$template = TEMPLATEPATH ."/now-reading/$filename";
	
	if ( !file_exists($template) )
		$template = dirname(__FILE__)."/templates/$filename";
	
	if ( !file_exists($template) )
		return new WP_Error('template-missing', sprintf(__("Oops! The template file %s could not be found in either the Now Reading template directory or your theme's Now Reading directory.", NRTD), "<code>$filename</code>"));
	
	load_template($template);
}

/**
 * Provides a simple API for themes to load the sidebar template.
 */
function nr_display() {
	nr_load_template('sidebar.php');
}

/**
 * Checks for updates to Now Reading and optionally prints a message if one is found. Use only on admin pages; we don't want to expose old versions if there are flaws.
 * @param bool $echo Whether or not to print the message; defaults to true
 * @return bool True if a newer version exists, false if current version is the latest.
 * @todo See about a move to a unified framework once Wordpress releases one
 */
function nr_check_for_updates() {
	
	$cache		= dirname(__FILE__) . '/latest-version.txt';
	$check_url	= 'http://robm.me.uk/wp-content/plugins/downloads.php?name=now-reading&action=getlatest';
	
	// Some people don't have their plugins directory writable.
	if ( !is_writable($cache) )
		return;
	
	// If the cache file doesn't exist and we can't create it, return.
	if ( !file_exists($cache) ) {
		if ( !@touch($cache) )
			return;
	}
	
	// Only check for updates once a day.
	if ( ( filemtime($cache) + 86400 ) <= time() )
		return;
	
	
	if ( $options['httpLib'] == 'curl' ) {
		if ( !function_exists('curl_init') ) {
			return new WP_Error('curl-not-installed', __('cURL is not installed correctly.', NRTD));
		} else {
			$ch = curl_init();
			
			curl_setopt($ch, CURLOPT_URL, $check_url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			
			$latest = curl_exec($ch);
			
			curl_close($ch);
		}
	} else {
		require_once ABSPATH . WPINC . '/class-snoopy.php';
			
		$snoopy	= new snoopy;		
		$snoopy->fetch($check_url);
		$latest	= $snoopy->results;
	}
		
	$download_url	= 'http://robm.me.uk/wp-content/plugins/downloads.php?file=now-reading%2Ffiles%2F' . $latest . '%2Fnow-reading.zip&name=now-reading';
	$plugin_page	= 'http://robm.me.uk/projects/plugins/wordpress/now-reading/';
	
	// Cache the changes
	$fh = fopen($cache, 'w');
	@fwrite($fh, preg_replace('#[^a-z0-9\.\-]#i', '', $latest));
	fclose($fh);
	
	$current = NOW_READING_VERSION;
	
	do_action('nr_check_for_updates', compact('current', 'latest'));
	
	$newer_version_exists = apply_filters('nr_newer_version_exists', ( $latest > $current ));
		
	return $newer_version_exists;
}

/**
 * Adds our details to the title of the page - book title/author, "Library" etc.
 */
function nr_page_title( $title ) {
	global $wp, $wp_query;
	$wp->parse_request();
	
	$title = '';
	
	if ( $wp_query->get('now_reading_library') )
		$title = 'Library';
	
	if ( $wp_query->get('now_reading_id') ) {
		$book = get_book(intval($wp_query->get('now_reading_id')));
		$title = $book->title . ' by ' . $book->author;
	}
	
	if ( $wp_query->get('now_reading_tag') )
		$title = 'Books tagged with &ldquo;' . htmlentities($wp_query->get('now_reading_tag')) . '&rdquo;';
	
	if ( $wp_query->get('now_reading_search') )
		$title = 'Library Search';
	
	if ( !empty($title) ) {
		$title = apply_filters('now_reading_page_title', $title);
		$separator = apply_filters('now_reading_page_title_separator', ' - ');
		return $separator.$title;
	}
	return '';
}

/**
 * Adds information to the header for future statistics purposes.
 */
function nr_header_stats() {
	echo '
	<meta name="now-reading-version" content="' . NOW_READING_VERSION . '" />
	';
}
add_action('wp_head', 'nr_header_stats');

/**
 * Adds a link in the footer. This is the best method of promotion for Now Reading; whilst you are certainly allowed to remove it, consider supporting NR by leaving it in.
 */
function nr_promolink() {
	echo "
	<span class='now-reading-copyright'>
		Powered by
		<a href='http://robm.me.uk/'>Rob Miller</a>'s
		<a href='http://robm.me.uk/projects/plugins/wordpress/now-reading/'>Now Reading</a>
		plugin.
	</span>
	";
}
add_action('nr_footer', 'nr_promolink');

if ( !function_exists('robm_dump') ) {
	/**
	 * Dumps a variable in a pretty way.
	 */
	function robm_dump() {
		echo '<pre style="border:1px solid #000; padding:5px; margin:5px; max-height:150px; overflow:auto;" id="' . md5(serialize($object)) . '">';
		$i = 0; $args = func_get_args();
		foreach ( (array) $args as $object ) {
			if ( $i == 0 && count($args) > 1 && is_string($object) )
				echo "<h3>$object</h3>";
			var_dump($object);
			$i++;
		}
		echo '</pre>';
	}
}

?>
