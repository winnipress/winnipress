<?php
/**
 * Core Administration API
 *
 * @package WordPress
 * @subpackage Administration
 * @since 2.3.0
 */

if( !defined('WP_ADMIN')){
	/*
	 * This file is being included from a file other than admin/admin.php, so
	 * some setup was skipped. Make sure the admin message catalog is loaded since
	 * load_default_textdomain() will not have done so in this context.
	 */
	load_textdomain( 'default', WP_LANG_DIR . '/admin-' . get_locale() . '.mo');
}

/** WordPress Administration Hooks */
require_once(ABSPATH . 'admin/includes/admin-filters.php');

/** WordPress Administration File API */
require_once(ABSPATH . 'admin/includes/file.php');

/** WordPress Misc Administration API */
require_once(ABSPATH . 'admin/includes/misc.php');

/** WordPress Options Administration API */
require_once(ABSPATH . 'admin/includes/options.php');


/** WordPress Plugin Administration API */
require_once(ABSPATH . 'admin/includes/plugin.php');

/** WordPress Post Administration API */
require_once(ABSPATH . 'admin/includes/post.php');

/** WordPress Administration Screen API */
require_once(ABSPATH . 'admin/includes/class-wp-screen.php');
require_once(ABSPATH . 'admin/includes/screen.php');

/** WordPress Taxonomy Administration API */
require_once(ABSPATH . 'admin/includes/taxonomy.php');

/** WordPress Template Administration API */
require_once(ABSPATH . 'admin/includes/template.php');

/** WordPress List Table Administration API and base class */
require_once(ABSPATH . 'admin/includes/class-wp-list-table.php');
require_once(ABSPATH . 'admin/includes/class-wp-list-table-compat.php');
require_once(ABSPATH . 'admin/includes/list-table.php');

/** WordPress Theme Administration API */
require_once(ABSPATH . 'admin/includes/theme.php');

/** WordPress User Administration API */
require_once(ABSPATH . 'admin/includes/user.php');