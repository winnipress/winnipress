<?php
define('WPINC', 'wp-includes');

// Include files required for initialization.
require(ABSPATH . WPINC . '/functions/load.php');
require(ABSPATH . WPINC . '/default-constants.php');

// Functions to handle filters and actions and some plugin related stuff
require_once(ABSPATH . WPINC . '/plugin.php');

/*
 * These can't be directly globalized in version.php. When updating,
 * we're including version.php from another installation and don't want
 * these values to be overridden if already set.
 */
global $wp_version, $wp_db_version, $tinymce_version, $required_php_version, $required_mysql_version, $wp_local_package;
require(ABSPATH . WPINC . '/version.php');

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

// Turn register_globals off.
wp_unregister_GLOBALS();

// Standardize $_SERVER variables across setups.
wp_fix_server_vars();


/**
 * Filters whether to enable loading of the advanced-cache.php drop-in.
 *
 * This filter runs before it can be used by plugins. It is designed for non-web
 * run-times. If false is returned, advanced-cache.php will never be loaded.
 *
 * @since 4.6.0
 *
 * @param bool $enable_advanced_cache Whether to enable loading advanced-cache.php (if present).
 *                                    Default true.
 */
if(WP_CACHE && apply_filters('enable_loading_advanced_cache_dropin', true)){
	// For an advanced caching plugin to use. Uses a static drop-in because you would only want one.
	WP_DEBUG ? include(WP_CONTENT_DIR . '/advanced-cache.php') : @include(WP_CONTENT_DIR . '/advanced-cache.php');

	// Re-initialize any hooks added manually by advanced-cache.php
	if($wp_filter){
		$wp_filter = WP_Hook::build_preinitialized_hooks($wp_filter);
	}
}

// Define WP_LANG_DIR if not set.
wp_set_lang_dir();

// Load early WordPress files.
require(ABSPATH . WPINC . '/classes/list-util.php');
require(ABSPATH . WPINC . '/functions/options-transients.php');
require(ABSPATH . WPINC . '/functions/main.php');
require(ABSPATH . WPINC . '/classes/matchesmapregex.php');
require(ABSPATH . WPINC . '/classes/wp.php');
require(ABSPATH . WPINC . '/classes/error.php');
require(ABSPATH . WPINC . '/pomo/mo.php');

// Include the wpdb class and, if present, a db.php database drop-in.
global $wpdb;
require_wp_db();

// Set the database table prefix and the format specifiers for database table columns.
$GLOBALS['table_prefix'] = $table_prefix;
wp_set_wpdb_vars();

// Start the WordPress object cache
wp_start_object_cache();

// Attach the default filters.
require(ABSPATH . WPINC . '/default-filters.php');

register_shutdown_function('shutdown_action_hook');


// Load the L10n library.
require_once(ABSPATH . WPINC . '/l10n.php');
require_once(ABSPATH . WPINC . '/classes/locale.php');
require_once(ABSPATH . WPINC . '/classes/locale-switcher.php');


// Load most of WordPress.
require(ABSPATH . WPINC . '/formatting.php');
require(ABSPATH . WPINC . '/capabilities.php');
require(ABSPATH . WPINC . '/query.php');
require(ABSPATH . WPINC . '/date.php');
require(ABSPATH . WPINC . '/theme.php');
require(ABSPATH . WPINC . '/template.php');
require(ABSPATH . WPINC . '/user.php');
require(ABSPATH . WPINC . '/meta.php');
require(ABSPATH . WPINC . '/post.php');
require(ABSPATH . WPINC . '/post-formats.php');
require(ABSPATH . WPINC . '/category.php');
require(ABSPATH . WPINC . '/rewrite.php');
require(ABSPATH . WPINC . '/kses.php');
require(ABSPATH . WPINC . '/script-loader.php');
require(ABSPATH . WPINC . '/taxonomy.php');
require(ABSPATH . WPINC . '/canonical.php');
require(ABSPATH . WPINC . '/shortcodes.php');
require(ABSPATH . WPINC . '/media.php');
require(ABSPATH . WPINC . '/http.php');

require(ABSPATH . WPINC . '/classes/ajax-response.php');
require(ABSPATH . WPINC . '/classes/roles.php');
require(ABSPATH . WPINC . '/classes/role.php');
require(ABSPATH . WPINC . '/classes/user.php');
require(ABSPATH . WPINC . '/classes/query.php');
require(ABSPATH . WPINC . '/classes/theme.php');
require(ABSPATH . WPINC . '/classes/user-query.php');
require(ABSPATH . WPINC . '/classes/session-tokens.php');
require(ABSPATH . WPINC . '/classes/user-meta-session-tokens.php');
require(ABSPATH . WPINC . '/classes/meta-query.php');
require(ABSPATH . WPINC . '/classes/metadata-lazyloader.php');
require(ABSPATH . WPINC . '/classes/post-type.php');
require(ABSPATH . WPINC . '/classes/post.php');
require(ABSPATH . WPINC . '/classes/rewrite.php');
require(ABSPATH . WPINC . '/classes/taxonomy.php');
require(ABSPATH . WPINC . '/classes/term.php');
require(ABSPATH . WPINC . '/classes/term-query.php');
require(ABSPATH . WPINC . '/classes/tax-query.php');

require(ABSPATH . WPINC . '/functions/cron.php');
require(ABSPATH . WPINC . '/functions/general.php'); // Functions to handle general stuff
require(ABSPATH . WPINC . '/functions/urls-and-links.php'); // Functions to handle urls and links
require(ABSPATH . WPINC . '/functions/authors.php'); // Functions to handle authors
require(ABSPATH . WPINC . '/functions/posts.php'); // Functions to handle posts
require(ABSPATH . WPINC . '/functions/post-thumbnails.php'); // Functions to handle post thumbnails
require(ABSPATH . WPINC . '/functions/categories.php'); // Functions to handle categories

// Define constants that rely on the API to obtain the default value.
// Define must-use plugin directory constants, which may be overridden in the sunrise.php drop-in.
wp_plugin_directory_constants();

$GLOBALS['wp_plugin_paths'] = array();



/**
 * Fires once all must-use and network-activated plugins have loaded.
 *
 * @since 2.8.0
 */
do_action('muplugins_loaded');


// Define constants after multisite is loaded.
wp_cookie_constants();

// Define and enforce our SSL constants
wp_ssl_constants();

// Create common globals.
require(ABSPATH . WPINC . '/vars.php');

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
require(ABSPATH . WPINC . '/pluggable.php');

// Set internal encoding.
wp_set_internal_encoding();

// Run wp_cache_postload() if object cache is enabled and the function exists.
if(WP_CACHE && function_exists('wp_cache_postload'))
	wp_cache_postload();

/**
 * Fires once activated plugins have loaded.
 *
 * Pluggable functions are also available at this point in the loading order.
 *
 * @since 1.5.0
 */
do_action('plugins_loaded');

// Define constants which affect functionality if not already defined.
wp_functionality_constants();

// Add magic quotes and set up $_REQUEST ($_GET + $_POST)
wp_magic_quotes();


/**
 * WordPress Query object
 * @global WP_Query $wp_the_query
 * @since 2.0.0
 */
$GLOBALS['wp_the_query'] = new WP_Query();

/**
 * Holds the reference to @see $wp_the_query
 * Use this global for WordPress queries
 * @global WP_Query $wp_query
 * @since 1.5.0
 */
$GLOBALS['wp_query'] = $GLOBALS['wp_the_query'];

/**
 * Holds the WordPress Rewrite object for creating pretty URLs
 * @global WP_Rewrite $wp_rewrite
 * @since 1.5.0
 */
$GLOBALS['wp_rewrite'] = new WP_Rewrite();

/**
 * WordPress Object
 * @global WP $wp
 * @since 2.0.0
 */
$GLOBALS['wp'] = new WP();


/**
 * WordPress User Roles
 * @global WP_Roles $wp_roles
 * @since 2.0.0
 */
$GLOBALS['wp_roles'] = new WP_Roles();

/**
 * Fires before the theme is loaded.
 *
 * @since 2.6.0
 */
do_action('setup_theme');

// Define the template related constants.
wp_templating_constants();

// Load the default text localization domain.
load_default_textdomain();

$locale = get_locale();
$locale_file = WP_LANG_DIR . "/$locale.php";
if((0 === validate_file($locale)) && is_readable($locale_file))
	require($locale_file);
unset($locale_file);

/**
 * WordPress Locale object for loading locale domain date and various strings.
 * @global WP_Locale $wp_locale
 * @since 2.1.0
 */
$GLOBALS['wp_locale'] = new WP_Locale();

/**
 *  WordPress Locale Switcher object for switching locales.
 *
 * @since 4.7.0
 *
 * @global WP_Locale_Switcher $wp_locale_switcher WordPress locale switcher object.
 */
$GLOBALS['wp_locale_switcher'] = new WP_Locale_Switcher();
$GLOBALS['wp_locale_switcher']->init();

// Load the functions for the active theme, for both parent and child theme if applicable.
if(!wp_installing()){
	if(TEMPLATEPATH !== STYLESHEETPATH && file_exists(STYLESHEETPATH . '/functions.php'))
		include(STYLESHEETPATH . '/functions.php');
	if(file_exists(TEMPLATEPATH . '/functions.php'))
		include(TEMPLATEPATH . '/functions.php');
}

/**
 * Fires after the theme is loaded.
 *
 * @since 3.0.0
 */
do_action('after_setup_theme');

// Set up current user.
$GLOBALS['wp']->init();

/**
 * Fires after WordPress has finished loading but before any headers are sent.
 *
 * Most of WP is loaded at this stage, and the user is authenticated. WP continues
 * to load on the{@see 'init'} hook that follows (e.g. widgets), and many plugins instantiate
 * themselves on it for all sorts of reasons (e.g. they need a user, a taxonomy, etc.).
 *
 * If you wish to plug an action once WP is loaded, use the{@see 'wp_loaded'} hook below.
 *
 * @since 1.5.0
 */
do_action('init');



/**
 * This hook is fired once WP, all plugins, and the theme are fully loaded and instantiated.
 *
 */
do_action('wp_loaded');
