<?php
// DB structure, option values and role values

/**
 * Declare these as global in case schema.php is included from a function.
 *
 * @global wpdb   $wpdb
 * @global array  $wp_queries
 * @global string $charset_collate
 */
global $wpdb, $wp_queries, $charset_collate;

// The database character collate
$charset_collate = $wpdb->get_charset_collate();

/**
 * Retrieve the SQL for creating database tables.
 *
 * @since 3.3.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param string $scope Optional. The tables for which to retrieve SQL. Can be all, global, ms_global, or blog tables. Defaults to all.
 * @param int $blog_id Optional. The site ID for which to retrieve SQL. Default is the current site ID.
 * @return string The SQL needed to create the requested tables.
 */
function wp_get_db_schema( $scope = 'all', $blog_id = null){
global $wpdb;

$charset_collate = $wpdb->get_charset_collate();

/*
	* Indexes have a maximum size of 767 bytes. Historically, we haven't need to be concerned about that.
	* As of 4.2, however, we moved to utf8mb4, which uses 4 bytes per character. This means that an index which
	* used to have room for floor(767/3) = 255 characters, now only has room for floor(767/4) = 191 characters.
	*/
$max_index_length = 191;

// Blog specific tables.
$sql_queries_to_create_tables = "CREATE TABLE $wpdb->termmeta (
meta_id bigint(20) unsigned NOT NULL auto_increment,
term_id bigint(20) unsigned NOT NULL default '0',
meta_key varchar(255) default NULL,
meta_value longtext,
PRIMARY KEY  (meta_id),
KEY term_id (term_id),
KEY meta_key (meta_key($max_index_length))
) $charset_collate;

CREATE TABLE $wpdb->terms (
term_id bigint(20) unsigned NOT NULL auto_increment,
name varchar(200) NOT NULL default '',
slug varchar(200) NOT NULL default '',
term_group bigint(10) NOT NULL default 0,
PRIMARY KEY  (term_id),
KEY slug (slug($max_index_length)),
KEY name (name($max_index_length))
) $charset_collate;

CREATE TABLE $wpdb->term_taxonomy (
term_taxonomy_id bigint(20) unsigned NOT NULL auto_increment,
term_id bigint(20) unsigned NOT NULL default 0,
taxonomy varchar(32) NOT NULL default '',
description longtext NOT NULL,
parent bigint(20) unsigned NOT NULL default 0,
count bigint(20) NOT NULL default 0,
PRIMARY KEY  (term_taxonomy_id),
UNIQUE KEY term_id_taxonomy (term_id,taxonomy),
KEY taxonomy (taxonomy)
) $charset_collate;

CREATE TABLE $wpdb->term_relationships (
object_id bigint(20) unsigned NOT NULL default 0,
term_taxonomy_id bigint(20) unsigned NOT NULL default 0,
term_order int(11) NOT NULL default 0,
PRIMARY KEY  (object_id,term_taxonomy_id),
KEY term_taxonomy_id (term_taxonomy_id)
) $charset_collate;

CREATE TABLE $wpdb->options (
option_id bigint(20) unsigned NOT NULL auto_increment,
option_name varchar(191) NOT NULL default '',
option_value longtext NOT NULL,
autoload varchar(20) NOT NULL default 'yes',
PRIMARY KEY  (option_id),
UNIQUE KEY option_name (option_name)
) $charset_collate;

CREATE TABLE $wpdb->postmeta (
meta_id bigint(20) unsigned NOT NULL auto_increment,
post_id bigint(20) unsigned NOT NULL default '0',
meta_key varchar(255) default NULL,
meta_value longtext,
PRIMARY KEY  (meta_id),
KEY post_id (post_id),
KEY meta_key (meta_key($max_index_length))
) $charset_collate;

CREATE TABLE $wpdb->posts (
ID bigint(20) unsigned NOT NULL auto_increment,
post_author bigint(20) unsigned NOT NULL default '0',
post_date datetime NOT NULL default '0000-00-00 00:00:00',
post_content longtext NOT NULL,
post_title text NOT NULL,
post_status varchar(20) NOT NULL default 'publish',
post_name varchar(200) NOT NULL default '',
post_modified datetime NOT NULL default '0000-00-00 00:00:00',
post_parent bigint(20) unsigned NOT NULL default '0',
post_type varchar(20) NOT NULL default 'post',
PRIMARY KEY  (ID),
KEY post_name (post_name($max_index_length)),
KEY type_status_date (post_type,post_status,post_date,ID),
KEY post_parent (post_parent),
KEY post_author (post_author)
) $charset_collate;

CREATE TABLE $wpdb->users (
ID bigint(20) unsigned NOT NULL auto_increment,
user_login varchar(60) NOT NULL default '',
user_pass varchar(255) NOT NULL default '',
user_nicename varchar(50) NOT NULL default '',
user_email varchar(100) NOT NULL default '',
user_url varchar(100) NOT NULL default '',
user_registered datetime NOT NULL default '0000-00-00 00:00:00',
user_activation_key varchar(255) NOT NULL default '',
user_status int(11) NOT NULL default '0',
display_name varchar(250) NOT NULL default '',
PRIMARY KEY  (ID),
KEY user_login_key (user_login),
KEY user_nicename (user_nicename),
KEY user_email (user_email)
) $charset_collate;

CREATE TABLE $wpdb->usermeta (
umeta_id bigint(20) unsigned NOT NULL auto_increment,
user_id bigint(20) unsigned NOT NULL default '0',
meta_key varchar(255) default NULL,
meta_value longtext,
PRIMARY KEY  (umeta_id),
KEY user_id (user_id),
KEY meta_key (meta_key($max_index_length))
) $charset_collate;\n";

return $sql_queries_to_create_tables;
}

// Populate for back compat.
$wp_queries = wp_get_db_schema( 'all');

/**
 * Create WordPress options and set the default values.
 *
 * @since 1.5.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 * @global int  $wp_db_version
 * @global int  $wp_current_db_version
 */
function populate_options() {
	global $wpdb, $wp_db_version, $wp_current_db_version;

	$guessurl = wp_guess_url();
	/**
	 * Fires before creating WordPress options and populating their default values.
	 *
	 * @since 2.6.0
	 */
	do_action( 'populate_options');

	if( ini_get('safe_mode')) {
		// Safe mode can break mkdir() so use a flat structure by default.
		$uploads_use_yearmonth_folders = 0;
	} else {
		$uploads_use_yearmonth_folders = 1;
	}

	// If WP_DEFAULT_THEME doesn't exist, fall back to the latest core default theme.
	$stylesheet = $template = WP_DEFAULT_THEME;
	$theme = wp_get_theme( WP_DEFAULT_THEME);
	if( !$theme->exists()) {
		$theme = WP_Theme::get_core_default_theme();
	}

	// If we can't find a core default theme, WP_DEFAULT_THEME is the best we can do.
	if( $theme) {
		$stylesheet = $theme->get_stylesheet();
		$template   = $theme->get_template();
	}

	$timezone_string = '';
	$gmt_offset = 0;
	/* translators: default GMT offset or timezone string. Must be either a valid offset (-12 to 14)
	   or a valid timezone string (America/New_York). See https://secure.php.net/manual/en/timezones.php
	   for all timezone strings supported by PHP.
	*/
	$offset_or_tz = _x( '0', 'default GMT offset or timezone string');
	if( is_numeric( $offset_or_tz))
		$gmt_offset = $offset_or_tz;
	elseif( $offset_or_tz && in_array( $offset_or_tz, timezone_identifiers_list()))
			$timezone_string = $offset_or_tz;

	$options = array(
	'siteurl' => $guessurl,
	'home' => $guessurl,
	'website_title' => __('Website title'),
	'website_tagline' => __('This is the tagline'),
	'website_charset' => 'UTF-8',
	'users_can_register' => 0,
	'start_of_week' => _x( '1', 'start of week'), // 0 = Sunday, 1 = Monday
	'use_balanceTags' => 0,
	'default_category' => 1,
	'posts_per_page' => 10,
	'date_format' => __('F j, Y'),
	'time_format' => __('g:i a'),
	'permalink_structure' => '',
	'rewrite_rules' => '',
	'active_plugins' => array(),
	'category_base' => '',
	'gmt_offset' => $gmt_offset,
	'recently_edited' => '',
	'template' => $template,
	'stylesheet' => $stylesheet,
	'html_type' => 'text/html',
	'default_role' => 'subscriber',
	'db_version' => $wp_db_version,
	'uploads_use_yearmonth_folders' => $uploads_use_yearmonth_folders,
	'upload_path' => '',
	'blog_public' => '1',
	'default_link_category' => 2,
	'show_on_front' => 'posts',
	'tag_base' => '',
	'show_avatars' => '1',
	'uninstall_plugins' => array(),
	'timezone_string' => $timezone_string,
	'page_for_posts' => 0,
	'page_on_front' => 0,
	'default_post_format' => 0,
	'finished_splitting_shared_terms' => 1,
	);



	// Set autoload to no for these options
	$fat_options = array('recently_edited', 'uninstall_plugins');

	$keys = "'" . implode( "', '", array_keys( $options)) . "'";
	$existing_options = $wpdb->get_col( "SELECT option_name FROM $wpdb->options WHERE option_name in ( $keys)");

	$insert = '';
	foreach( $options as $option => $value) {
		if( in_array($option, $existing_options))
			continue;
		if( in_array($option, $fat_options))
			$autoload = 'no';
		else
			$autoload = 'yes';

		if( is_array($value))
			$value = serialize($value);
		if( !empty($insert))
			$insert .= ', ';
		$insert .= $wpdb->prepare( "(%s, %s, %s)", $option, $value, $autoload);
	}

	if( !empty($insert))
		$wpdb->query("INSERT INTO $wpdb->options (option_name, option_value, autoload) VALUES " . $insert);

	// In case it is set, but blank, update "home".
	if( !__get_option('home')) update_option('home', $guessurl);

	// Clear expired transients
	delete_expired_transients( true);
}



/**
 * Create the roles for WordPress 2.0
 *
 * @since 2.0.0
 */
function populate_roles(){

	add_role('administrator', 'Administrator');
	add_role('editor', 'Editor');
	add_role('author', 'Author');
	add_role('contributor', 'Contributor');
	add_role('subscriber', 'Subscriber');

	// Add caps for Administrator role
	$role = get_role('administrator');
	$role->add_cap('switch_themes');
	$role->add_cap('activate_plugins');
	$role->add_cap('edit_plugins');
	$role->add_cap('edit_users');
	$role->add_cap('edit_files');
	$role->add_cap('manage_options');
	$role->add_cap('manage_categories');
	$role->add_cap('manage_links');
	$role->add_cap('upload_files');
	$role->add_cap('import');
	$role->add_cap('unfiltered_html');
	$role->add_cap('edit_posts');
	$role->add_cap('edit_others_posts');
	$role->add_cap('edit_published_posts');
	$role->add_cap('publish_posts');
	$role->add_cap('edit_pages');
	$role->add_cap('read');

	// Add caps for Editor role
	$role = get_role('editor');
	$role->add_cap('manage_categories');
	$role->add_cap('manage_links');
	$role->add_cap('upload_files');
	$role->add_cap('unfiltered_html');
	$role->add_cap('edit_posts');
	$role->add_cap('edit_others_posts');
	$role->add_cap('edit_published_posts');
	$role->add_cap('publish_posts');
	$role->add_cap('edit_pages');
	$role->add_cap('read');

	// Add caps for Author role
	$role = get_role('author');
	$role->add_cap('upload_files');
	$role->add_cap('edit_posts');
	$role->add_cap('edit_published_posts');
	$role->add_cap('publish_posts');
	$role->add_cap('read');

	// Add caps for Contributor role
	$role = get_role('contributor');
	$role->add_cap('edit_posts');
	$role->add_cap('read');

	// Add caps for Subscriber role
	$role = get_role('subscriber');
	$role->add_cap('read');
}