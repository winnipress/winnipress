<?php

session_start();


// In WordPress Administration Screens
if(!defined('WP_ADMIN')){
	define('WP_ADMIN', true);
}

define('WP_BLOG_ADMIN', true);

// Require debug tools
require(dirname(dirname(__FILE__)) . '/wp-core/winni-debug-tools.php');

require(dirname(dirname(__FILE__)). '/wp-core/classes/walker.php');
require(dirname(dirname(__FILE__)). '/wp-core/classes/walker-page.php');
require(dirname(dirname(__FILE__)). '/wp-core/classes/walker-page-dropdown.php');
require(dirname(dirname(__FILE__)). '/wp-core/classes/walker-category.php');
require(dirname(dirname(__FILE__)). '/wp-core/classes/walker-category-dropdown.php');

require_once(dirname(dirname(__FILE__)) . '/wp-load.php');

nocache_headers();

require_once(ABSPATH . 'admin/includes/admin.php');

auth_redirect();

// Schedule trash collection
if(!wp_next_scheduled('wp_scheduled_delete') && !wp_installing())
	wp_schedule_event(time(), 'daily', 'wp_scheduled_delete');

// Schedule Transient cleanup.
if(!wp_next_scheduled('delete_expired_transients') && !wp_installing()){
	wp_schedule_event(time(), 'daily', 'delete_expired_transients');
}

set_screen_options();

$date_format = __('F j, Y');
$time_format = __('g:i a');

wp_enqueue_script('common');

/**
 * $pagenow is set in vars.php
 * The remaining variables are imported as globals elsewhere, declared as globals here
 *
 * @global string $pagenow
 * @global array  $wp_importers
 * @global string $hook_suffix
 * @global string $plugin_page
 * @global string $typenow
 * @global string $taxnow
 */
global $pagenow, $wp_importers, $hook_suffix, $plugin_page, $typenow, $taxnow;

$page_hook = null;

$editing = false;

if(isset($_GET['page'])){
	$plugin_page = wp_unslash($_GET['page']);
	$plugin_page = plugin_basename($plugin_page);
}

if(isset($_REQUEST['post_type']) && post_type_exists($_REQUEST['post_type']))
	$typenow = $_REQUEST['post_type'];
else
	$typenow = '';

if(isset($_REQUEST['taxonomy']) && taxonomy_exists($_REQUEST['taxonomy']))
	$taxnow = $_REQUEST['taxonomy'];
else
	$taxnow = '';


require(ABSPATH . 'admin/menu.php');


/**
 * Fires as an admin screen or script is being initialized.
 *
 *
 * This is roughly analogous to the more general{@see 'init'} hook, which fires earlier.
 *
 * @since 2.5.0
 */
do_action('admin_init');

if(isset($plugin_page)){
	if(!empty($typenow))
		$the_parent = $pagenow . '?post_type=' . $typenow;
	else
		$the_parent = $pagenow;
	if(!$page_hook = get_plugin_page_hook($plugin_page, $the_parent)){
		$page_hook = get_plugin_page_hook($plugin_page, $plugin_page);

		// Back-compat for plugins using add_management_page().
		if(empty($page_hook) && 'edit.php' == $pagenow && '' != get_plugin_page_hook($plugin_page, 'tools.php')){
			// There could be plugin specific params on the URL, so we need the whole query string
			if(!empty($_SERVER[ 'QUERY_STRING' ]))
				$query_string = $_SERVER[ 'QUERY_STRING' ];
			else
				$query_string = 'page=' . $plugin_page;
			wp_redirect(admin_url('tools.php?' . $query_string));
			exit;
		}
	}
	unset($the_parent);
}

$hook_suffix = '';
if(isset($page_hook)){
	$hook_suffix = $page_hook;
} elseif(isset($plugin_page)){
	$hook_suffix = $plugin_page;
} elseif(isset($pagenow)){
	$hook_suffix = $pagenow;
}

set_current_screen();

// Handle plugin admin pages.
if(isset($plugin_page)){
	if($page_hook){
		/**
		 * Fires before a particular screen is loaded.
		 *
		 * The load-* hook fires in a number of contexts. This hook is for plugin screens
		 * where a callback is provided when the screen is registered.
		 *
		 * The dynamic portion of the hook name, `$page_hook`, refers to a mixture of plugin
		 * page information including:
		 * 1. The page type. If the plugin page is registered as a submenu page, such as for
		 *    Settings, the page type would be 'settings'. Otherwise the type is 'toplevel'.
		 * 2. A separator of '_page_'.
		 * 3. The plugin basename minus the file extension.
		 *
		 * Together, the three parts form the `$page_hook`. Citing the example above,
		 * the hook name used would be 'load-settings_page_pluginbasename'.
		 *
		 * @see get_plugin_page_hook()
		 *
		 * @since 2.1.0
		 */
		do_action("load-{$page_hook}");
		if(!isset($_GET['noheader']))
			require_once(ABSPATH . 'admin/admin-header.php');

		/**
		 * Used to call the registered callback for a plugin screen.
		 *
		 * @ignore
		 * @since 1.5.0
		 */
		do_action($page_hook);
	} else{
		if(validate_file($plugin_page)){
			wp_die(__('Invalid plugin page.'));
		}

		if(!(file_exists(WP_PLUGIN_DIR . "/$plugin_page") && is_file(WP_PLUGIN_DIR . "/$plugin_page")) && !(file_exists(WPMU_PLUGIN_DIR . "/$plugin_page") && is_file(WPMU_PLUGIN_DIR . "/$plugin_page")))
			wp_die(sprintf(__('Cannot load %s.'), htmlentities($plugin_page)));

		/**
		 * Fires before a particular screen is loaded.
		 *
		 * The load-* hook fires in a number of contexts. This hook is for plugin screens
		 * where the file to load is directly included, rather than the use of a function.
		 *
		 * The dynamic portion of the hook name, `$plugin_page`, refers to the plugin basename.
		 *
		 * @see plugin_basename()
		 *
		 * @since 1.5.0
		 */
		do_action("load-{$plugin_page}");

		if(!isset($_GET['noheader']))
			require_once(ABSPATH . 'admin/admin-header.php');

		if(file_exists(WPMU_PLUGIN_DIR . "/$plugin_page"))
			include(WPMU_PLUGIN_DIR . "/$plugin_page");
		else
			include(WP_PLUGIN_DIR . "/$plugin_page");
	}

	include(ABSPATH . 'admin/admin-footer.php');

	exit();
}else{
	/**
	 * Fires before a particular screen is loaded.
	 *
	 * The load-* hook fires in a number of contexts. This hook is for core screens.
	 *
	 * The dynamic portion of the hook name, `$pagenow`, is a global variable
	 * referring to the filename of the current page, such as 'admin.php',
	 * 'post-new.php' etc. A complete hook for the latter would be
	 * 'load-post-new.php'.
	 *
	 * @since 2.1.0
	 */
	do_action("load-{$pagenow}");

	/*
	 * The following hooks are fired to ensure backward compatibility.
	 * In all other cases, 'load-' . $pagenow should be used instead.
	 */
	if($typenow == 'page'){
		if($pagenow == 'post-new.php')
			do_action('load-page-new.php');
		elseif($pagenow == 'post.php')
			do_action('load-page.php');
	}  elseif($pagenow == 'edit-tags.php'){
		if($taxnow == 'category')
			do_action('load-categories.php');
	} elseif('term.php' === $pagenow){
		do_action('load-edit-tags.php');
	}
}

if(!empty($_REQUEST['action'])){
	/**
	 * Fires when an 'action' request variable is sent.
	 *
	 * The dynamic portion of the hook name, `$_REQUEST['action']`,
	 * refers to the action derived from the `GET` or `POST` request.
	 *
	 * @since 2.6.0
	 */
	do_action('admin_action_' . $_REQUEST['action']);
}
