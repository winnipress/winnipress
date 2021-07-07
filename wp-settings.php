<?php

// Include files required for initialization.
require(ABSPATH . 'wp-core/functions/load.php');
require(ABSPATH . 'wp-core/default-constants.php');

// Functions to handle filters and actions and some plugin related stuff
require_once(ABSPATH . 'wp-core/plugin.php');

/*
 * These can't be directly globalized in version.php. When updating,
 * we're including version.php from another installation and don't want
 * these values to be overridden if already set.
 */
global $wp_version, $wp_db_version, $tinymce_version, $required_php_version, $required_mysql_version, $wp_local_package;
require(ABSPATH . 'wp-core/version.php');

/**
 * If not already configured, `$blog_id` will default to 1 in a single site
 * configuration. In multisite, it will be overridden by default in ms-settings.php.
 *
 * @global int $blog_id
 * @since 2.0.0
 */
global $blog_id;

// Set initial default constants including WP_MEMORY_LIMIT, WP_MAX_MEMORY_LIMIT, WP_DEBUG, SCRIPT_DEBUG, WP_CONTENT_DIR and WP_CACHE.
wp_initial_constants();



// Disable magic quotes at runtime. Magic quotes are added using wpdb later in wp-settings.php.
@ini_set('magic_quotes_runtime', 0);
@ini_set('magic_quotes_sybase',  0);

// WordPress calculates offsets from UTC.
date_default_timezone_set('UTC');

// Define WP_LANG_DIR if not set.
wp_set_lang_dir();

// Load early WordPress files.
require(ABSPATH . 'wp-core/classes/list-util.php');
require(ABSPATH . 'wp-core/functions/options-transients.php');
require(ABSPATH . 'wp-core/functions/main.php');
require(ABSPATH . 'wp-core/classes/matchesmapregex.php');
require(ABSPATH . 'wp-core/classes/wp.php');
require(ABSPATH . 'wp-core/classes/error.php');
require(ABSPATH . 'wp-core/pomo/mo.php');

// Include the wpdb class and, if present, a db.php database drop-in.
global $wpdb;
require_wp_db();

// Set the database table prefix and the format specifiers for database table columns.
$GLOBALS['table_prefix'] = $table_prefix;
wp_set_wpdb_vars();

// Start the WordPress object cache
wp_start_object_cache();

// Attach the default filters.
require(ABSPATH . 'wp-core/default-filters.php');

register_shutdown_function('shutdown_action_hook');


// Load the L10n library.
require_once(ABSPATH . 'wp-core/l10n.php');
require_once(ABSPATH . 'wp-core/classes/locale.php');


// Load most of WordPress.
require(ABSPATH . 'wp-core/formatting.php');
require(ABSPATH . 'wp-core/capabilities.php');
require(ABSPATH . 'wp-core/query.php');
require(ABSPATH . 'wp-core/date.php');
require(ABSPATH . 'wp-core/theme.php');
require(ABSPATH . 'wp-core/template.php');
require(ABSPATH . 'wp-core/user.php');
require(ABSPATH . 'wp-core/meta.php');
require(ABSPATH . 'wp-core/post.php');
require(ABSPATH . 'wp-core/category.php');
require(ABSPATH . 'wp-core/rewrite.php');
require(ABSPATH . 'wp-core/kses.php');
require(ABSPATH . 'wp-core/script-loader.php');
require(ABSPATH . 'wp-core/taxonomy.php');
require(ABSPATH . 'wp-core/canonical.php');
require(ABSPATH . 'wp-core/shortcodes.php');
require(ABSPATH . 'wp-core/http.php');

require(ABSPATH . 'wp-core/classes/roles.php');
require(ABSPATH . 'wp-core/classes/role.php');
require(ABSPATH . 'wp-core/classes/user.php');
require(ABSPATH . 'wp-core/classes/query.php');
require(ABSPATH . 'wp-core/classes/theme.php');
require(ABSPATH . 'wp-core/classes/user-query.php');
require(ABSPATH . 'wp-core/classes/session-tokens.php');
require(ABSPATH . 'wp-core/classes/user-meta-session-tokens.php');
require(ABSPATH . 'wp-core/classes/meta-query.php');
require(ABSPATH . 'wp-core/classes/metadata-lazyloader.php');
require(ABSPATH . 'wp-core/classes/post-type.php');
require(ABSPATH . 'wp-core/classes/post.php');
require(ABSPATH . 'wp-core/classes/rewrite.php');
require(ABSPATH . 'wp-core/classes/taxonomy.php');
require(ABSPATH . 'wp-core/classes/term.php');
require(ABSPATH . 'wp-core/classes/term-query.php');
require(ABSPATH . 'wp-core/classes/tax-query.php');

require(ABSPATH . 'wp-core/functions/cron.php');
require(ABSPATH . 'wp-core/functions/general.php'); // Functions to handle general stuff
require(ABSPATH . 'wp-core/functions/urls-and-links.php'); // Functions to handle urls and links
require(ABSPATH . 'wp-core/functions/authors.php'); // Functions to handle authors
require(ABSPATH . 'wp-core/functions/posts.php'); // Functions to handle posts
require(ABSPATH . 'wp-core/functions/post-thumbnails.php'); // Functions to handle post thumbnails
require(ABSPATH . 'wp-core/functions/categories.php'); // Functions to handle categories

// Define constants that rely on the API to obtain the default value.
// Define must-use plugin directory constants, which may be overridden in the sunrise.php drop-in.
wp_plugin_directory_constants();

$GLOBALS['wp_plugin_paths'] = array();

// Define constants after multisite is loaded.
wp_cookie_constants();

// Define and enforce our SSL constants
wp_ssl_constants();

// Create common globals.
require(ABSPATH . 'wp-core/vars.php');

// Make taxonomies and posts available to plugins and themes.
// @plugin authors: warning: these get registered again on the init hook.
create_initial_taxonomies();
create_initial_post_types();

wp_start_scraping_edited_file_errors();

// Register the default theme directory root
register_theme_directory(get_theme_root());

// Load active plugins.
foreach(wp_get_active_and_valid_plugins() as $plugin){
	wp_register_plugin_realpath($plugin);
	include_once($plugin);
}
unset($plugin);

// Load pluggable functions.
require(ABSPATH . 'wp-core/pluggable.php');

// Set internal encoding.
wp_set_internal_encoding();

// Fires once activated plugins have loaded
do_action('plugins_loaded');

// Define constants which affect functionality if not already defined.
wp_functionality_constants();

// Add magic quotes and set up $_REQUEST ($_GET + $_POST)
wp_magic_quotes();

// Main and global WP Query
$GLOBALS['wp_query'] = new WP_Query();

// Global Rewrite object for creating pretty URLs
$GLOBALS['wp_rewrite'] = new WP_Rewrite();

// Global WP object
$GLOBALS['wp'] = new WP();

// WP User Roles
$GLOBALS['wp_roles'] = new WP_Roles();

// Fires before the theme is loaded
do_action('setup_theme');

// Define the template related constants.
wp_templating_constants();

// Load the default text localization domain.
load_default_textdomain();

$locale = get_locale();
$locale_file = WP_LANG_DIR . "/$locale.php";
if((0 === validate_file($locale)) && is_readable($locale_file)){
	require($locale_file);
}
unset($locale_file);

// Locale object for loading locale domain date and various strings
$GLOBALS['wp_locale'] = new WP_Locale();

// Load the functions for the active theme, for both parent and child theme if applicable.
if(!wp_installing()){
	if(TEMPLATEPATH !== STYLESHEETPATH && file_exists(STYLESHEETPATH . '/functions.php'))
		include(STYLESHEETPATH . '/functions.php');
	if(file_exists(TEMPLATEPATH . '/functions.php'))
		include(TEMPLATEPATH . '/functions.php');
}

// Fires after the theme is loaded
do_action('after_setup_theme');

// Get current user
wp_get_current_user();

// Fires after WordPress has finished loading but before any headers are sent.
do_action('init');

// All plugins, and the theme are fully loaded and instantiated
do_action('wp_loaded');