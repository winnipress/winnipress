<?php
/**
 * Administration API: Default admin hooks
 *
 * @package WordPress
 * @subpackage Administration
 * @since 4.3.0
 */


// Misc hooks.
add_action( 'admin_init', 'wp_admin_headers'         );
add_action( 'login_init', 'wp_admin_headers'         );
add_action( 'admin_head', 'wp_admin_canonical_url'   );




add_action( 'update_option_home',          'update_home_siteurl', 10, 2 );
add_action( 'update_option_siteurl',       'update_home_siteurl', 10, 2 );
add_action( 'update_option_page_on_front', 'update_home_siteurl', 10, 2 );
add_action( 'update_option_admin_email',   'wp_site_admin_email_change_notification', 10, 3 );

add_action( 'add_option_new_admin_email',    'update_option_new_admin_email', 10, 2 );
add_action( 'update_option_new_admin_email', 'update_option_new_admin_email', 10, 2 );

add_filter( 'heartbeat_received', 'wp_check_locked_posts',  10,  3 );
add_filter( 'heartbeat_received', 'wp_refresh_post_lock',   10,  3 );
add_filter( 'wp_refresh_nonces', 'wp_refresh_post_nonces', 10,  3 );
add_filter( 'heartbeat_received', 'heartbeat_autosave',     500, 2 );

add_filter( 'heartbeat_settings', 'wp_heartbeat_set_suspension' );

// Nav Menu hooks.
add_action( 'admin_head-nav-menus.php', '_wp_delete_orphaned_draft_menu_items' );

// Plugin hooks.
add_filter( 'whitelist_options', 'option_update_filter' );




// Theme hooks.
add_action( 'customize_controls_print_footer_scripts', 'customize_themes_print_templates' );



// User hooks.
add_action( 'admin_init', 'default_password_nag_handler' );

add_action( 'admin_notices', 'default_password_nag' );
add_action( 'admin_notices', 'new_user_email_admin_notice' );

add_action( 'profile_update', 'default_password_nag_edit_user', 10, 2 );

add_action( 'personal_options_update', 'send_confirmation_on_profile_email' );