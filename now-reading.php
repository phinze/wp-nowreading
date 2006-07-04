<?php
/*
Plugin Name: Now Reading
Version: 4.1
Plugin URI: http://robm.me.uk/projects/plugins/wordpress/now-reading/
Description: Allows you to display the books you're reading, have read recently and plan to read, with cover art fetched automatically from Amazon.
Author: Rob Miller
Author URI: http://robm.me.uk/
*/

define('NOW_READING_VERSION', '4.1');
define('NOW_READING_DB', 14);
define('NOW_READING_OPTIONS', 3);
define('NOW_READING_REWRITE', 6);

define('NRTD', 'now-reading');

define('NR_MENU_SINGLE', 2);
define('NR_MENU_MULTIPLE', 4);

$nr_statuses = array(
	'unread'	=> __('Yet to read', NRTD),
	'reading'	=> __('Currently reading', NRTD),
	'read'		=> __('Finished', NRTD)
);

$nr_domains = array(
	'.com'		=> __('International', NRTD),
	'.co.uk'	=> __('United Kingdom', NRTD),
	'.fr'		=> __('France', NRTD),
	'.de'		=> __('Germany', NRTD),
	'.co.jp'	=> __('Japan', NRTD),
	'.ca'		=> __('Canada', NRTD)
);

class nr_url {
	var $urls, $multiple, $single;
    
    function nr_url() {
        $this->multiple = array(
            'add'		=> get_settings('wpurl'),
            'manage'	=> get_settings('wpurl') . '/wp-admin/edit.php?page=now-reading-manage.php',
            'options'	=> get_settings('wpurl') . '/wp-admin/options-general.php?page=now-reading-options.php'
        );
        $this->single = array(
            'add'		=> get_settings('wpurl') . '/wp-admin/admin.php?page=now-reading/now-reading-add.php',
            'manage'	=> get_settings('wpurl') . '/wp-admin/admin.php?page=now-reading/now-reading-manage.php',
            'options'	=> get_settings('wpurl') . '/wp-admin/admin.php?page=now-reading/now-reading-options.php'
        );
    }
	
	function load_scheme( $option ) {
		if ( file_exists( ABSPATH . '/wp-admin/post-new.php' ) )
			$this->multiple['add'] .= '/wp-admin/post-new.php?page=now-reading/now-reading-add.php';
		else
			$this->multiple['add'] .= '/wp-admin/post.php?page=now-reading/now-reading-add.php';
		
		if ( $option == NR_MENU_SINGLE )
			$this->urls = $this->single;
		else
			$this->urls = $this->multiple;
	}
}
$nr_url		= new nr_url();
$options	= get_option('nowReadingOptions');
$nr_url->load_scheme($options['menuLayout']);

/**
 * Load our l18n domain.
 */
load_plugin_textdomain(NRTD);

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
 * Registers our query vars so we can redirect to the library and book permalinks.
 * @param array $vars The existing array of query vars
 * @return array The modified array of query vars with our additions.
 */
function nr_query_vars( $vars ) {
	$vars[] = 'now_reading_library';
	$vars[] = 'now_reading_id';
	$vars[] = 'now_reading_tag';
	$vars[] = 'now_reading_search';
	$vars[] = 'now_reading_title';
	$vars[] = 'now_reading_author';
	return $vars;
}
add_filter('query_vars', 'nr_query_vars');

/**
 * Adds our rewrite rules for the library and book permalinks to the regular WordPress ones.
 * @param array $rules The existing array of rewrite rules we're filtering
 * @return array The modified rewrite rules with our additions.
 */
function nr_mod_rewrite( $rules ) {
	global $wp_rewrite;
	$rules['^library/([0-9]+)/?$']			= 'index.php?now_reading_id=' . $wp_rewrite->preg_index(1);
	$rules['^library/tag/([^/]+)/?$']		= 'index.php?now_reading_tag=' . $wp_rewrite->preg_index(1);
	$rules['^library/search/?$']			= 'index.php?now_reading_search=true';
	$rules['^library/([^/]+)/([^/]+)/?$']	= 'index.php?now_reading_author=' . $wp_rewrite->preg_index(1) . '&now_reading_title=' . $wp_rewrite->preg_index(2);
	$rules['^library/([^/]+)/?$']			= 'index.php?now_reading_author=' . $wp_rewrite->preg_index(1);
	$rules['^library/?$']					= 'index.php?now_reading_library=true';
	return $rules;
}
add_filter('rewrite_rules_array', 'nr_mod_rewrite');

/**
 * Handler for the activation hook. Installs/upgrades the database table and adds/updates the nowReadingOptions option.
 */
function nr_install() {
	global $wpdb, $wp_rewrite;
	
	// WP's dbDelta function takes care of installing/upgrading our DB table.
	require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
	dbDelta("
	CREATE TABLE {$wpdb->prefix}now_reading (
	b_id bigint(20) NOT NULL auto_increment,
	b_added datetime NOT NULL default '0000-00-00 00:00:00',
	b_started datetime NOT NULL default '0000-00-00 00:00:00',
	b_finished datetime NOT NULL default '0000-00-00 00:00:00',
	b_title text NOT NULL default '',
	b_author text NOT NULL default '',
	b_image text NOT NULL default '',
	b_asin varchar(255) NOT NULL default '',
	b_status enum('read','reading','unread') NOT NULL default 'read',
	b_rating tinyint(4) NOT NULL default '0',
	b_review text NOT NULL,
	b_post bigint(20) NOT NULL default '0',
	PRIMARY KEY  (b_id)
	);
	CREATE TABLE {$wpdb->prefix}now_reading_meta (
	m_id BIGINT(20) NOT NULL auto_increment,
	m_book BIGINT(20) NOT NULL DEFAULT '0',
	m_key VARCHAR(255) NOT NULL default '',
	m_value TEXT NOT NULL default '',
	PRIMARY KEY  (m_id)
	);
	CREATE TABLE {$wpdb->prefix}now_reading_tags (
	t_id BIGINT(20) NOT NULL auto_increment,
	t_name VARCHAR(255) NOT NULL DEFAULT '',
	PRIMARY KEY  (t_id)
	);
	CREATE TABLE {$wpdb->prefix}now_reading_books2tags (
	rel_id BIGINT(20) NOT NULL auto_increment,
	book_id BIGINT(20) NOT NULL DEFAULT '0',
	tag_id BIGINT(20) NOT NULL DEFAULT '0',
	PRIMARY KEY  (rel_id)
	);
	");
	
	$defaultOptions = array(
		'formatDate'	=> '%D %b %Y',
		'associate'		=> 'roblog-21',
		'domain'		=> '.com',
		'imageSize'		=> 'Medium',
		'httpLib'		=> 'snoopy',
		'useModRewrite'	=> false,
		'debugMode'		=> false,
		'menuLayout'	=> NR_MENU_MULTIPLE
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

// Include other functionality
require_once dirname(__FILE__) . '/compat.php';
require_once dirname(__FILE__) . '/now-reading-admin.php';
require_once dirname(__FILE__) . '/default-filters.php';
require_once dirname(__FILE__) . '/template-functions.php';

/**
 * Fetches books from the database based on a given query.
 *
 * Example usage:
 * <code>
 * $books = get_books('status=reading&orderby=started&order=asc&num=-1');
 * </code>
 * @param string $query Query string containing restrictions on what to fetch. Valid variables: $num, $status, $orderby, $order, $search, $author, $title
 * @return array Returns a numerically indexed array in which each element corresponds to a book.
 */
function get_books( $query ) {
	
	global $wpdb;
	
	$options = get_option('nowReadingOptions');
	
	parse_str($query);

	// We're fetching a collection of books, not just one.
	switch ( $status ) {
		case 'unread':
		case 'reading':
		case 'read':
			break;
		default:
			$status = 'all';
			break;
	}
	if ( $status != 'all' )
		$status = "AND b_status = '$status'";
	else
		$status = '';
	
	if ( !empty($search) ) {
		$search = $wpdb->escape($search);
		$search = "AND ( b_author LIKE '%$search%' OR b_title LIKE '%$search%' OR m_value LIKE '%$search%')";
	} else
		$search = '';
	
	$order	= ( strtolower($order) == 'desc' ) ? 'DESC' : 'ASC';
	
	switch ( $orderby ) {
		case 'added':
			$orderby = 'b_added';
			break;
		case 'started':
			$orderby = 'b_started';
			break;
		case 'finished':
			$orderby = 'b_finished';
			break;
		case 'title':
			$orderby = 'b_title';
			break;
		case 'author':
			$orderby = 'b_author';
			break;
		case 'asin':
			$orderby = 'b_asin';
			break;
		case 'status':
			$orderby = "b_status $order, b_added";
			break;
		default:
			$orderby = 'b_added';
			break;
	}
	
	if ( empty($num) )
		$num = 5;
	
	if ( $num > -1 && $offset >= 0 ) {
		$offset	= intval($offset);
		$num 	= intval($num);
		$limit	= "LIMIT $offset, $num";
	} else
		$limit	= '';
	
	if ( !empty($author) ) {
		$author	= $wpdb->escape($author);
		$author	= "AND b_author = '$author'";
	}
	
	if ( !empty($title) ) {
		$title	= $wpdb->escape($title);
		$title	= "AND b_title = '$title'";
	}
	
	$books = $wpdb->get_results("
	SELECT 
		COUNT(*) AS count,
		b_id AS id, b_title AS title, b_author AS author, b_image AS image, b_status AS status,
		DATE_FORMAT(b_added, '".$wpdb->escape($options['formatDate'])."') AS added,
		DATE_FORMAT(b_started, '".$wpdb->escape($options['formatDate'])."') AS started,
		DATE_FORMAT(b_finished, '".$wpdb->escape($options['formatDate'])."') AS finished,
		b_asin AS asin, b_rating AS rating, b_review AS review, b_post AS post
	FROM
		{$wpdb->prefix}now_reading
	LEFT JOIN {$wpdb->prefix}now_reading_meta
		ON m_book = b_id
	WHERE
		1=1
		$status
		$id
		$search
		$author
		$title
	GROUP BY
		b_id
	ORDER BY
		$orderby $order
	$limit
	");
	
	$books = apply_filters('get_books', $books);
	return $books;
}

/**
 * Fetches a single book with the given ID.
 * @param int $id The b_id of the book you want to fetch.
 */
function get_book( $id ) {
	global $wpdb;
	
	$options = get_option('nowReadingOptions');
	
	$id = intval($id);
	
	$book = $wpdb->get_row("
	SELECT
		COUNT(*) AS count,
		b_id AS id, b_title AS title, b_author AS author, b_image AS image, b_status AS status,
		DATE_FORMAT(b_added, '".$wpdb->escape($options['formatDate'])."') AS added,
		DATE_FORMAT(b_started, '".$wpdb->escape($options['formatDate'])."') AS started,
		DATE_FORMAT(b_finished, '".$wpdb->escape($options['formatDate'])."') AS finished,
		b_asin AS asin, b_rating AS rating, b_review AS review, b_post AS post
	FROM {$wpdb->prefix}now_reading
	WHERE b_id = $id
	GROUP BY b_id
	");
	
	$book = apply_filters('get_single_book', $book);
	return $book;
}

/**
 * Adds a book to the database.
 * @param string $query Query string containing the fields to add.
 * @return boolean True on success, false on failure.
 */
function add_book( $query ) {
	global $wpdb;
	
	parse_str($query, $fields);
	
	$fields = apply_filters('add_book_fields', $fields);
	
	foreach ( (array) $fields as $field => $value ) {
		if ( empty($field) || empty($value) )
			continue;
		$columns .= ", $field";
		$values .= ", '$value'";
	}
	
	$query = "
	INSERT INTO {$wpdb->prefix}now_reading
	(b_id$columns)
	VALUES(''$values)
	";
	
	if ( $wpdb->query($query) ) {
		do_action('book_added', $wpdb->insert_id);
		return true;
	} else
		return false;
}

/**
 * Fetches and parses XML from Amazon for the given query.
 * @param string $query Query string containing variables to search Amazon for. Valid variables: $isbn, $title, $author
 * @return array Array containing each book's information.
 */
function query_amazon( $query ) {
	$options = get_option('nowReadingOptions');
	
	$using_isbn = false;
	
	parse_str($query);
	
	if ( empty($isbn) && empty($title) && empty($author) )
		return false;
	
	if ( !empty($isbn) )
		$using_isbn = true;
	
	// Our query needs different vars depending on whether or not we're searching by ISBN, so build it here.
	if ( $using_isbn ) {
		$isbn = preg_replace('#([^0-9]+)#', '', $isbn);
		$query = "&Power=asin%3A+$isbn";
	} else {
		$query = '&Title=' . urlencode($title);
		if ( !empty($author) )
			$query .= '&Author=' . urlencode($author);
	}
	
	$url =	'http://webservices.amazon' . $options['domain'] . '/onca/xml?Service=AWSECommerceService'
			 . '&AWSAccessKeyId=0BN9NFMF20HGM4ND8RG2&Operation=ItemSearch&SearchIndex=Books&ResponseGroup=Request,Small,Images'
			 . '&Version=2005-03-23&AssociateTag=' . urlencode($options['associate']).$query;
	
	// Fetch the XML using either Snoopy or cURL, depending on our options.
	if ( $options['httpLib'] == 'curl' ) {
		if ( !function_exists('curl_init') ) {
			return new WP_Error('curl-not-installed', __('cURL is not installed correctly.', NRTD));
		} else {
			$ch = curl_init();
			
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Now Reading ' . NOW_READING_VERSION);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			
			$xmlString = curl_exec($ch);
			
			curl_close($ch);
		}
	} else {
		require_once ABSPATH . WPINC . '/class-snoopy.php';
		
		$snoopy = new snoopy;
		$snoopy->agent = 'Now Reading ' . NOW_READING_VERSION;
		$snoopy->fetch($url);
		
		$xmlString = $snoopy->results;
	}
	
	if ( empty($xmlString) ) {
		do_action('nr_search_error', $query);
		echo '
		<div id="message" class="error fade">
			<p><strong>' . __("Oops!") . '</strong></p>
			<p>' . sprintf(__("For some reason, I couldn't search for your book on amazon%s.", NRTD), $options['domain']) . '</p>
			<p>' . __("Amazon's Web Services may be down, or there may be a problem with your server configuration.") . '</p>
								
					 ';
					 if ( $options['httpLib'] )
			 echo '<p>' . __("Try changing your HTTP Library setting to <strong>cURL</strong>.", NRTD) . '</p>';
					 echo '
		</div>
		';
		return false;
	}
	
	if ( $options['debugMode'] )
		robm_dump("raw XML:", htmlentities(str_replace(">", ">\n", str_replace("<", "\n<", $xmlString))));
	
	if ( !class_exists('MiniXMLDoc') )
		require_once dirname(__FILE__) . '/xml/minixml.inc.php';
	
	$xmlString = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xmlString);
	$doc = new MiniXMLDoc();
	$doc->fromString($xmlString);
	
	if ( $options['debugMode'] ) {
		robm_dump("doc:", $doc);
		robm_dump("doc xml:", htmlentities($doc->toString()));
	}
	
	$items = $doc->getElementByPath('ItemSearchResponse/Items');
	$items = $items->getAllChildren('Item');
	
	if ( count($items) > 0 ) {
		
		$results = array();
		
		if ( $options['debugMode'] )
			robm_dump("items:", $items);
		
		foreach ( (array) $items as $item ) {
			$author	= $item->getElementByPath('ItemAttributes/Author');
			if ( $author )
				$author	= $author->getValue();
			
			$title	= $item->getElementByPath('ItemAttributes/Title');
			if ( !$title )
				break;
			$title	= $title->getValue();
			
			$asin = $item->getElement('ASIN');
			if ( !$asin )
				break;
			$asin = $asin->getValue();
			
			if ( $options['debugMode'] )
				robm_dump("book:", $author, $title, $asin);
			
			$image	= $item->getElementByPath("{$options['imageSize']}Image/URL");
			if ( $image )
				$image	= $image->getValue();
			else
				$image = get_settings('wpurl') . '/wp-content/plugins/now-reading/no-image.png';
			
			$results[] = compact('author', 'title', 'image', 'asin');
		}
		
		$results = apply_filters('returned_books', $results);
		
	} else {
		
		return false;
		
	}
	
	return $results;
}

/**
 * Checks to see if the library/book permalink query vars are set and, if so, loads the appropriate templates.
 */
function library_init() {
	global $wp, $wpdb, $q, $query;
	
	$wp->parse_request();
	
	if ( is_now_reading_page() )
		add_filter('wp_title', 'nr_page_title');
	else
		return;
	
	if ( $wp->query_vars['now_reading_library'] ) {
		// Library page:
		nr_load_template('library.php');
		die;
	} elseif ( $wp->query_vars['now_reading_id'] ) {
		// Book permalink:
		$GLOBALS['nr_id'] = intval($wp->query_vars['now_reading_id']);
		
		$load = nr_load_template('single.php');
		if ( is_wp_error($load) )
			echo $load->get_error_message();
		
		die;
	} elseif ( $wp->query_vars['now_reading_tag'] ) {
		// Tag permalink:
		$GLOBALS['nr_tag'] = $wp->query_vars['now_reading_tag'];
		
		$load = nr_load_template('tag.php');
		if ( is_wp_error($load) )
			echo $load->get_error_message();
		
		die;
	} elseif ( $wp->query_vars['now_reading_search'] ) {
		// Search page:
		$GLOBALS['query'] = $_GET['q'];
		unset($_GET['q']); // Just in case
		
		$load = nr_load_template('search.php');
		if ( is_wp_error($load) )
			echo $load->get_error_message();
		
		die;
	} elseif ( $wp->query_vars['now_reading_author'] && $wp->query_vars['now_reading_title'] ) {
		// Book permalink with title and author.
		$author				= $wpdb->escape(urldecode($wp->query_vars['now_reading_author']));
		$title				= $wpdb->escape(urldecode($wp->query_vars['now_reading_title']));
		$GLOBALS['nr_id']	= $wpdb->get_var("SELECT b_id FROM {$wpdb->prefix}now_reading WHERE b_title = '$title' AND b_author = '$author'");
		
		$load = nr_load_template('single.php');
		if ( is_wp_error($load) )
			echo $load->get_error_message();
		
		die;
	} elseif ( $wp->query_vars['now_reading_author'] ) {
		// Author permalink.
		$GLOBALS['nr_author'] = $wpdb->escape(urldecode($wp->query_vars['now_reading_author']));
		
		$load = nr_load_template('author.php');
		if ( is_wp_error($load) )
			echo $load->get_error_message();
		
		die;
	} else
		return;
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
	@fwrite($fh, $latest);
	fclose($fh);
	
	$current = NOW_READING_VERSION;
	
	do_action('nr_check_for_updates', compact('current', 'latest'));
	
	$newer_version_exists = apply_filters('nr_newer_version_exists', ( $latest > $current ));
		
	return $newer_version_exists;
}

/**
 * Gets the tags for the given book.
 */
function get_book_tags( $id ) {
	global $wpdb;
	
	if ( !$id )
		return array();
	
	$tags = $wpdb->get_results("
	SELECT
		t_name AS name
	FROM
		{$wpdb->prefix}now_reading, {$wpdb->prefix}now_reading_tags, {$wpdb->prefix}now_reading_books2tags
	WHERE
		book_id = '$id'
		AND book_id = b_id
		AND tag_id = t_id
	");
	
	$array = array();
	if ( count($tags) > 0 ) {
		foreach ( (array) $tags as $tag ) {
			$array[] = $tag->name;
		}
	}
	
	return $array;
}

/**
 * Tags the book with the given tag.
 */
function tag_book( $id, $tag ) {
	global $wpdb;
	
	if ( !is_numeric($tag) )
		$tid = add_library_tag($tag);
	else
		$tid = $tag;
	
	$exists = $wpdb->get_var("
	SELECT
		COUNT(*) AS count
	FROM
		{$wpdb->prefix}now_reading_books2tags
	WHERE
		book_id = '$id'
		AND
		tag_id = '$tid'
	");
	
	if ( !$exists ) {
		$wpdb->query("
		INSERT INTO {$wpdb->prefix}now_reading_books2tags
		(book_id, tag_id)
		VALUES('$id', '$tid')
		");
	}
}

/**
 * Fetches all the books tagged with the given tag.
 */
function get_books_by_tag( $tag ) {
	global $wpdb;
	
	$tid = add_library_tag($tag);
	
	$books = $wpdb->get_results("
	SELECT
		b_id AS id, b_title AS title, b_author AS author, b_image AS image, b_status AS status,
		DATE_FORMAT(b_added, '".$wpdb->escape($options['formatDate'])."') AS added,
		DATE_FORMAT(b_started, '".$wpdb->escape($options['formatDate'])."') AS started,
		DATE_FORMAT(b_finished, '".$wpdb->escape($options['formatDate'])."') AS finished,
		b_asin AS asin, b_rating AS rating, b_review AS review
	FROM
		{$wpdb->prefix}now_reading, {$wpdb->prefix}now_reading_tags, {$wpdb->prefix}now_reading_books2tags
	WHERE
		t_id = tag_id
		AND
		tag_id = '$tid'
		AND
		book_id = b_id
	GROUP BY
		b_id
	");
	
	return $books;
}

/**
 * Adds a tag to the database.
 */
function add_library_tag( $tag ) {
	global $wpdb;
	
	$t = $wpdb->escape($tag);
	
	$count = $wpdb->get_var("
	SELECT
		COUNT(*) AS count
	FROM
		{$wpdb->prefix}now_reading_tags
	WHERE
		t_name = '$t'
	");
	
	if ( $count > 0 ) {
		$tid = $wpdb->get_var("
		SELECT
			t_id
		FROM
			{$wpdb->prefix}now_reading_tags
		WHERE
			t_name = '$t'
		");
	} else {
		$wpdb->query("
		INSERT INTO {$wpdb->prefix}now_reading_tags
		(t_name)
		VALUES('$t')
		");
		$tid = $wpdb->insert_id;
	}
	return $tid;
}

/**
 * Fetches meta-data for the given book.
 * @see print_book_meta()
 */
function get_book_meta( $id, $key = '' ) {
	global $wpdb;
	
	if ( !$id )
		return array();
	
	$id = intval($id);
	
	if ( !empty($key) )
		$key = 'AND m_key = "' . $wpdb->escape($key) . '"';
	else
		$key = '';
	
	$raws = $wpdb->get_results("
	SELECT
		m_key, m_value
	FROM
		{$wpdb->prefix}now_reading_meta
	WHERE
		m_book = '$id'
		$key
	");
	
	if ( !count($raws) )
		return array();
	
	$meta = array();
	foreach ( (array) $raws as $raw ) {
		$meta[$raw->m_key] = $raw->m_value;
	}
	
	$meta = apply_filters('book_meta', $meta);
	
	return $meta;
}

/**
 * Adds a meta key-value pairing for the given book.
 */
function add_book_meta( $id, $key, $value ) {
	return update_book_meta($id, $key, $value);
}

/**
 * Updates the meta key-value pairing for the given book. If the key does not exist, it will be created.
 */
function update_book_meta( $id, $key, $value ) {
	global $wpdb;
	
	$key = $wpdb->escape($key);
	$value = $wpdb->escape($value);
	
	$existing = $wpdb->get_var("
	SELECT
		m_id AS id
	FROM
		{$wpdb->prefix}now_reading_meta
	WHERE
		m_book = '$id'
		AND
		m_key = '$key'
	");
	
	if ( $existing != null ) {
		$result = $wpdb->query("
		UPDATE {$wpdb->prefix}now_reading_meta
		SET
			m_key = '$key',
			m_value = '$value'
		WHERE
			m_id = '$existing'
		");
	} else {
		$result = $wpdb->query("
		INSERT INTO {$wpdb->prefix}now_reading_meta
			(m_book, m_key, m_value)
			VALUES('$id', '$key', '$value')
		");
	}
	return $result;
}

/**
 * Deletes the meta key-value pairing for the given book with the given key.
 */
function delete_book_meta( $id, $key ) {
	global $wpdb;
	
	$id = intval($id);
	$key = $wpdb->escape($key);
	
	return $wpdb->query("
	DELETE FROM
		{$wpdb->prefix}now_reading_meta
	WHERE
		m_book = '$id'
		AND
		m_key = '$key'
	");
}

/**
 * Returns true if we're on a Now Reading page.
 */
function is_now_reading_page() {
	global $wp;
	$wp->parse_request();
	
	return ( 
		!empty($wp->query_vars['now_reading_library'])	||
		!empty($wp->query_vars['now_reading_search'])	||
		!empty($wp->query_vars['now_reading_id'])		||
		!empty($wp->query_vars['now_reading_tag'])		||
		!empty($wp->query_vars['now_reading_title'])	||
		!empty($wp->query_vars['now_reading_author'])
	);
}

/**
 * Adds our details to the title of the page - book title/author, "Library" etc.
 */
function nr_page_title( $title ) {
	global $wp;
	$wp->parse_request();
	
	$title = '';
	
	if ( !empty($wp->query_vars['now_reading_library']) )
		$title = 'Library';
	
	if ( !empty($wp->query_vars['now_reading_id']) ) {
		$book = get_book(intval($wp->query_vars['now_reading_id']));
		$title = $book->title . ' by ' . $book->author;
	}
	
	if ( !empty($wp->query_vars['now_reading_tag']) )
		$title = 'Books tagged with &ldquo;' . htmlentities($wp->query_vars['now_reading_tag']) . '&rdquo;';
	
	if ( !empty($wp->query_vars['now_reading_search']) )
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
