<?php
/**
 * WordPress Administration Template Header
 *
 * @package WordPress
 * @subpackage Administration
 */

@header('Content-Type: ' . get_option('html_type') . '; charset=utf-8');
if( !defined( 'WP_ADMIN'))
	require_once( dirname( __FILE__) . '/admin.php');

/**
 * In case admin-header.php is included in a function.
 *
 * @global string    $title
 * @global string    $hook_suffix
 * @global WP_Screen $current_screen
 * @global WP_Locale $wp_locale
 * @global string    $pagenow
 * @global string    $update_title
 * @global int       $total_update_count
 * @global string    $parent_file
 */
global $title, $hook_suffix, $current_screen, $wp_locale, $pagenow,
	$update_title, $total_update_count, $parent_file;

// Catch plugins that include admin-header.php before admin.php completes.
if( empty( $current_screen))
	set_current_screen();

get_admin_page_title();
$title = esc_html( strip_tags( $title));

if( is_network_admin()) {
	/* translators: Network admin screen title. 1: Network name */
	$admin_title = sprintf( __( 'Network Admin: %s'), esc_html( get_network()->site_name));
} elseif( is_user_admin()) {
	/* translators: User dashboard screen title. 1: Network name */
	$admin_title = sprintf( __( 'User Dashboard: %s'), esc_html( get_network()->site_name));
} else {
	$admin_title = get_bloginfo( 'name');
}

if( $admin_title == $title) {
	/* translators: Admin screen title. 1: Admin screen name */
	$admin_title = sprintf( __( '%1$s &#8212; WordPress'), $title);
} else {
	/* translators: Admin screen title. 1: Admin screen name, 2: Network or site name */
	$admin_title = sprintf( __( '%1$s &lsaquo; %2$s &#8212; WordPress'), $title, $admin_title);
}

/**
 * Filters the title tag content for an admin page.
 *
 * @since 3.1.0
 *
 * @param string $admin_title The page title, with extra context added.
 * @param string $title       The original page title.
 */
$admin_title = apply_filters( 'admin_title', $admin_title, $title);

wp_user_settings();

_wp_admin_html_begin();
?>
<title><?php echo $admin_title; ?></title>


<meta name="viewport" content="width=device-width,initial-scale=1.0">
<?php

/**
 * Enqueue scripts for all admin pages.
 *
 * @since 2.8.0
 *
 * @param string $hook_suffix The current admin page.
 */
do_action( 'admin_enqueue_scripts', $hook_suffix);


/**
 * Fires when styles are printed for a specific admin page based on $hook_suffix.
 *
 * @since 2.6.0
 */
do_action( "admin_print_styles-{$hook_suffix}");

/**
 * Fires when styles are printed for all admin pages.
 *
 * @since 2.6.0
 */
do_action( 'admin_print_styles');

/**
 * Fires when scripts are printed for a specific admin page based on $hook_suffix.
 *
 * @since 2.1.0
 */
do_action( "admin_print_scripts-{$hook_suffix}");

/**
 * Fires when scripts are printed for all admin pages.
 *
 * @since 2.1.0
 */
do_action( 'admin_print_scripts');

/**
 * Fires in head section for a specific admin page.
 *
 * The dynamic portion of the hook, `$hook_suffix`, refers to the hook suffix
 * for the admin page.
 *
 * @since 2.1.0
 */
do_action( "admin_head-{$hook_suffix}");

/**
 * Fires in head section for all admin pages.
 *
 * @since 2.1.0
 */
do_action( 'admin_head');

?>
</head>
<?php
/**
 * Filters the CSS classes for the body tag in the admin.
 *
 * This filter differs from the {@see 'post_class'} and {@see 'body_class'} filters
 * in two important ways:
 *
 * 1. `$classes` is a space-separated string of class names instead of an array.
 * 2. Not all core admin classes are filterable, notably: wp-admin, wp-core-ui,
 *    and no-js cannot be removed.
 *
 * @since 2.3.0
 *
 * @param string $classes Space-separated list of CSS classes.
 */
$admin_body_classes = apply_filters( 'admin_body_class', '');
?>
<body class="wp-admin">




<div id="wpwrap">
<?php require(ABSPATH . 'wp-admin/menu-header.php'); ?>
<div id="wpcontent">

<?php
/**
 * Fires at the beginning of the content section in an admin page.
 *
 * @since 3.0.0
 */
do_action( 'in_admin_header');
?>

<div id="wpbody" role="main">
<?php
unset($title_class, $blog_name, $total_update_count, $update_title);

$current_screen->set_parentage( $parent_file);

?>

<div id="wpbody-content" aria-label="<?php esc_attr_e('Main content'); ?>" tabindex="0">
<?php

$current_screen->render_screen_meta();

if( is_network_admin()) {
	/**
	 * Prints network admin screen notices.
	 *
	 * @since 3.1.0
	 */
	do_action( 'network_admin_notices');
} elseif( is_user_admin()) {
	/**
	 * Prints user admin screen notices.
	 *
	 * @since 3.1.0
	 */
	do_action( 'user_admin_notices');
} else {
	/**
	 * Prints admin screen notices.
	 *
	 * @since 3.1.0
	 */
	do_action( 'admin_notices');
}

/**
 * Prints generic admin screen notices.
 *
 * @since 3.1.0
 */
do_action( 'all_admin_notices');

if( $parent_file == 'options-general.php')
	require(ABSPATH . 'wp-admin/options-head.php');
