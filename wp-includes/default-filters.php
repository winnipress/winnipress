<?php
// Default filters and actions for most of the WP hooks

// Strip, trim, kses, special chars for string saves
foreach(array('pre_user_display_name', 'pre_user_first_name', 'pre_user_last_name', 'pre_user_nickname') as $filter){
	add_filter($filter, 'sanitize_text_field' );
	add_filter($filter, 'wp_filter_kses'      );
	add_filter($filter, '_wp_specialchars', 30);
}

// Strip, kses, special chars for string display
foreach(array('term_name', 'user_display_name', 'user_first_name', 'user_last_name', 'user_nickname') as $filter){
	if(is_admin()){
		// These are expensive. Run only on admin pages for defense in depth.
		add_filter($filter, 'sanitize_text_field');
		add_filter($filter, 'wp_kses_data'      );
	}
	add_filter($filter, '_wp_specialchars', 30);
}

// Kses only for textarea saves
foreach(array('pre_term_description', 'pre_user_description') as $filter){
	add_filter($filter, 'wp_filter_kses');
}


if(is_admin()){
	add_action('admin_enqueue_scripts', 'wp_enqueue_editor');
	add_action('admin_enqueue_scripts', 'wp_enqueue_default_admin_styles');
}

// Email saves
add_filter('pre_user_email', 'trim'          );
add_filter('pre_user_email', 'sanitize_email');
add_filter('pre_user_email', 'wp_filter_kses');


// Email admin display
add_filter('user_email', 'sanitize_email');
if(is_admin()){
	add_filter('user_email', 'wp_kses_data');
}


// Slugs
add_filter('pre_term_slug', 'sanitize_title');
add_filter('wp_insert_post_data', '_wp_customize_changeset_filter_insert_post_data', 10, 2);

// Keys
foreach(array('pre_post_type', 'pre_post_status', 'pre_post_comment_status', 'pre_post_ping_status') as $filter){
	add_filter($filter, 'sanitize_key');
}

// Mime types
add_filter('pre_post_mime_type', 'sanitize_mime_type');
add_filter('post_mime_type', 'sanitize_mime_type');

// Meta
add_filter('register_meta_args', '_wp_register_meta_args_whitelist', 10, 2);

// Places to balance tags on input
foreach(array('content_save_pre', 'excerpt_save_pre') as $filter){
	add_filter($filter, 'convert_invalid_entities');
	add_filter($filter, 'balanceTags', 50);
}

// Format strings for display.
foreach(array('term_name', 'bloginfo', 'wp_title') as $filter){
	add_filter($filter, 'wptexturize'  );
	add_filter($filter, 'convert_chars');
	add_filter($filter, 'esc_html'     );
}

// Format WordPress
foreach(array('the_content', 'the_title', 'wp_title') as $filter)
	add_filter($filter, 'capital_P_dangit', 11);
add_filter('comment_text', 'capital_P_dangit', 31);

// Format titles
foreach(array('single_post_title', 'single_cat_title', 'single_tag_title', 'single_month_title') as $filter){
	add_filter($filter, 'wptexturize');
	add_filter($filter, 'strip_tags' );
}

// Format text area for display.
foreach(array('term_description', 'get_the_post_type_description') as $filter){
	add_filter($filter, 'wptexturize'     );
	add_filter($filter, 'convert_chars'   );
	add_filter($filter, 'wpautop'         );
	add_filter($filter, 'shortcode_unautop');
}

// Pre save hierarchy
add_filter('wp_insert_post_parent', 'wp_check_post_hierarchy_for_loops', 10, 2);
add_filter('wp_update_term_parent', 'wp_check_term_hierarchy_for_loops', 10, 3);

// Display filters
add_filter('the_title', 'wptexturize'  );
add_filter('the_title', 'convert_chars');
add_filter('the_title', 'trim'         );

add_filter('the_content', 'wptexturize'                      );
add_filter('the_content', 'wpautop'                          );
add_filter('the_content', 'shortcode_unautop'                );

add_filter('the_excerpt',     'wptexturize'     );
add_filter('the_excerpt',     'convert_chars'   );
add_filter('the_excerpt',     'wpautop'         );
add_filter('the_excerpt',     'shortcode_unautop');
add_filter('get_the_excerpt', 'wp_trim_excerpt' );

add_filter('the_post_thumbnail_caption', 'wptexturize'    );
add_filter('the_post_thumbnail_caption', 'convert_chars'  );



add_filter('list_cats',         'wptexturize');

add_filter('wp_sprintf', 'wp_sprintf_l', 10, 2);



add_filter('date_i18n', 'wp_maybe_decline_date');


// Mark site as no longer fresh
foreach(array('publish_post', 'publish_page', 'customize_save_after') as $action){
	add_action($action, '_delete_option_fresh_site', 0);
}

// Misc filters
add_filter('pre_kses',                 'wp_pre_kses_less_than'              );
add_filter('sanitize_title',           'sanitize_title_with_dashes',   10, 3);
add_filter('option_tag_base',          '_wp_filter_taxonomy_base'           );
add_filter('option_category_base',     '_wp_filter_taxonomy_base'           );
add_filter('editable_slug',            'urldecode'                          );
add_filter('editable_slug',            'esc_textarea'                       );
add_filter('title_save_pre',           'trim'                               );


// Actions
add_action('wp_head',             '_wp_render_title_tag',            1    );
add_action('wp_head',             'wp_enqueue_scripts',              1    );
add_action('wp_head',             'wp_resource_hints',               2    );
add_action('wp_head',             'adjacent_posts_rel_link_wp_head', 10, 0);
add_action('wp_head',             'locale_stylesheet'                     );
add_action('publish_future_post', 'check_and_publish_future_post',   10, 1);
add_action('wp_head',             'noindex',                          1   );
add_action('wp_head',             'wp_print_styles',                  8   );
add_action('wp_head',             'wp_print_head_scripts',            9   );
add_action('wp_head',             'rel_canonical'                         );
add_action('wp_head',             'wp_shortlink_wp_head',            10, 0);
add_action('wp_footer',           'wp_print_footer_scripts',         20   );
add_action('template_redirect',   'wp_shortlink_header',             11, 0);
add_action('wp_print_footer_scripts', '_wp_footer_scripts'                );
add_action('init',                'check_theme_switched',            99   );


// Login actions
add_filter('login_head',          'wp_resource_hints',             8    );
add_action('login_head',          'wp_print_head_scripts',         9    );
add_action('login_head',          'print_admin_styles',            9    );
add_action('login_footer',        'wp_print_footer_scripts',       20   );
add_action('login_init',          'send_frame_options_header',     10, 0);


// 2 Actions 2 Furious (<< wtf?)
add_action('do_robots',                  'do_robots'                                     );
add_action('admin_print_scripts',        'print_head_scripts',                      20   );
add_action('admin_print_footer_scripts', '_wp_footer_scripts'                            );
add_action('admin_print_styles',         'print_admin_styles',                      20   );
add_action('publish_post',               '_publish_post_hook',                       5, 1);
add_action('transition_post_status',     '_transition_post_status',                  5, 3);
add_action('transition_post_status',     '_update_term_count_on_transition_post_status', 10, 3);
add_action('admin_init',                 'send_frame_options_header',               10, 0);



// Cron tasks
add_action('wp_scheduled_delete',            'wp_scheduled_delete'      );
add_action('wp_scheduled_auto_draft_delete', 'wp_delete_auto_drafts'    );
add_action('importer_scheduled_cleanup',     'wp_delete_attachment'     );
add_action('upgrader_scheduled_cleanup',     'wp_delete_attachment'     );
add_action('delete_expired_transients',      'delete_expired_transients');

// Post Thumbnail CSS class filtering
add_action('begin_fetch_post_thumbnail_html', '_wp_post_thumbnail_class_filter_add'   );
add_action('end_fetch_post_thumbnail_html',   '_wp_post_thumbnail_class_filter_remove');

// Redirect Old Slugs
add_action('template_redirect',  'wp_old_slug_redirect'             );
add_action('post_updated',       'wp_check_for_changed_slugs', 12, 3);
add_action('attachment_updated', 'wp_check_for_changed_slugs', 12, 3);

// Redirect Old Dates
add_action('post_updated',       'wp_check_for_changed_dates', 12, 3);
add_action('attachment_updated', 'wp_check_for_changed_dates', 12, 3);


// Timezone
add_filter('pre_option_gmt_offset','wp_timezone_override_offset');



// Default settings for heartbeat
add_filter('heartbeat_settings', 'wp_heartbeat_settings');

// Check if the user is logged out
add_action('admin_enqueue_scripts', 'wp_auth_check_load');

add_filter('heartbeat_send',        'wp_auth_check');
add_filter('heartbeat_nopriv_send', 'wp_auth_check');

// Default authentication filters
add_filter('authenticate', 'wp_authenticate_username_password',  20, 3);
add_filter('authenticate', 'wp_authenticate_email_password',     20, 3);
add_filter('authenticate', 'wp_authenticate_spam_check',         99   );
add_filter('determine_current_user', 'wp_validate_auth_cookie'         );
add_filter('determine_current_user', 'wp_validate_logged_in_cookie', 20);

// Split term updates.
add_action('admin_init',        '_wp_check_for_scheduled_split_terms');
add_action('split_shared_term', '_wp_check_split_default_terms',  10, 4);
add_action('split_shared_term', '_wp_check_split_terms_in_menus', 10, 4);
add_action('split_shared_term', '_wp_check_split_nav_menu_terms', 10, 4);
add_action('wp_split_shared_term_batch', '_wp_batch_split_terms');

// Email notifications
add_action('after_password_reset', 'wp_password_change_notification');
add_action('register_new_user',      'wp_send_new_user_notifications');
add_action('edit_user_created_user', 'wp_send_new_user_notifications', 10, 2);



// Calendar widget cache
add_action('save_post', 'delete_get_calendar_cache');
add_action('delete_post', 'delete_get_calendar_cache');
add_action('update_option_start_of_week', 'delete_get_calendar_cache');
add_action('update_option_gmt_offset', 'delete_get_calendar_cache');

// Author
add_action('transition_post_status', '__clear_multi_author_cache');

// Post
add_action('init', 'create_initial_post_types', 0); // highest priority
add_action('admin_menu', '_add_post_type_submenus');
add_action('before_delete_post', '_reset_front_page_settings_for_post');
add_action('wp_trash_post',      '_reset_front_page_settings_for_post');
add_action('change_locale', 'create_initial_post_types');

// KSES
add_action('init', 'kses_init');
add_action('set_current_user', 'kses_init');

// Script Loader
add_action('wp_default_styles', 'wp_default_styles');
add_filter('style_loader_src', 'wp_style_loader_src', 10, 2);

// Taxonomy
add_action('init', 'create_initial_taxonomies', 0); // highest priority
add_action('change_locale', 'create_initial_taxonomies');

// Canonical
add_action('template_redirect', 'redirect_canonical');
add_action('template_redirect', 'wp_redirect_admin_locations', 1000);

// Shortcodes
add_filter('the_content', 'do_shortcode', 11); // AFTER wpautop()

// Media
add_action('customize_controls_enqueue_scripts', 'wp_plupload_default_settings');

// Nav menu
add_filter('nav_menu_item_id', '_nav_menu_item_id_use_once', 10, 2);





// Former admin filters that can also be hooked on the front end
add_action('media_buttons', 'media_buttons');
add_filter('image_send_to_editor', 'image_add_caption', 20, 8);
add_filter('media_send_to_editor', 'image_media_send_to_editor', 10, 3);

// Embeds
add_action('embed_head',             'enqueue_embed_scripts',           1   );
add_action('embed_head',             'wp_print_head_scripts',          20   );
add_action('embed_head',             'wp_print_styles',                20   );
add_action('embed_head',             'wp_no_robots'                         );
add_action('embed_head',             'rel_canonical'                        );
add_action('embed_head',             'locale_stylesheet',              30   );
add_action('embed_footer',           'wp_print_footer_scripts',        20   );



// Capabilities
add_filter('user_has_cap', 'wp_maybe_grant_install_languages_cap', 1);

unset($filter, $action);