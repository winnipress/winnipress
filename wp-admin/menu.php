<?php

// Constructs the admin menu.
// 0 = menu_title, 1 = capability, 2 = menu_slug, 3 = page_title, 4 = classes, 5 = hookname, 6 = icon_url

$menu[2] = array(__('Dashboard'), 'read', 'index.php', '', 'menu-top menu-top-first menu-icon-dashboard', 'menu-dashboard', 'la-home');

$submenu[ 'index.php' ][0] = array(__('Home'), 'read', 'index.php');


$menu[4] = array('', 'read', 'separator1', '', 'wp-menu-separator');

// $menu[5] = Posts

$menu[10] = array(__('Media'), 'upload_files', 'upload.php', '', 'menu-top menu-icon-media', 'menu-media', 'la-photo-video');
	$submenu['upload.php'][5] = array(__('Library'), 'upload_files', 'upload.php');
	/* translators: add new file */
	$submenu['upload.php'][10] = array(_x('Add New', 'file'), 'upload_files', 'media-new.php');
	$i = 15;
	foreach(get_taxonomies_for_attachments('objects') as $tax) {
		if(!$tax->show_ui || !$tax->show_in_menu)
			continue;

		$submenu['upload.php'][$i++] = array(esc_attr($tax->labels->menu_name), $tax->cap->manage_terms, 'edit-tags.php?taxonomy=' . $tax->name . '&amp;post_type=attachment');
	}
	unset($tax, $i);



$_wp_last_object_menu = 25; // The index of the last top-level menu in the object menu group

$types = (array) get_post_types(array('show_ui' => true, '_builtin' => false, 'show_in_menu' => true));
$builtin = array('post', 'page');
foreach(array_merge($builtin, $types) as $ptype) {
	$ptype_obj = get_post_type_object($ptype);
	// Check if it should be a submenu.
	if($ptype_obj->show_in_menu !== true)
		continue;
	$ptype_menu_position = is_int($ptype_obj->menu_position) ? $ptype_obj->menu_position : ++$_wp_last_object_menu; // If we're to use $_wp_last_object_menu, increment it first.
	$ptype_for_id = sanitize_html_class($ptype);

	$menu_icon = 'la-archive';
	if(is_string($ptype_obj->menu_icon) and substr($ptype_obj->menu_icon,0,3)=='la-'){
		$menu_icon = $ptype_obj->menu_icon;
	}

	$menu_class = 'menu-top menu-icon-' . $ptype_for_id;
	// 'post' special case
	if('post' === $ptype) {
		$menu_class .= ' open-if-no-js';
		$ptype_file = "edit.php";
		$post_new_file = "post-new.php";
		$edit_tags_file = "edit-tags.php?taxonomy=%s";
	} else {
		$ptype_file = "edit.php?post_type=$ptype";
		$post_new_file = "post-new.php?post_type=$ptype";
		$edit_tags_file = "edit-tags.php?taxonomy=%s&amp;post_type=$ptype";
	}

	if(in_array($ptype, $builtin)) {
		$ptype_menu_id = 'menu-' . $ptype_for_id . 's';
	} else {
		$ptype_menu_id = 'menu-posts-' . $ptype_for_id;
	}
	/*
	 * If $ptype_menu_position is already populated or will be populated
	 * by a hard-coded value below, increment the position.
	 */
	$core_menu_positions = array(59, 60, 65, 70, 75, 80, 85, 99);
	while (isset($menu[$ptype_menu_position]) || in_array($ptype_menu_position, $core_menu_positions))
		$ptype_menu_position++;

	$menu[$ptype_menu_position] = array(esc_attr($ptype_obj->labels->menu_name), $ptype_obj->cap->edit_posts, $ptype_file, '', $menu_class, $ptype_menu_id, $menu_icon);
	$submenu[ $ptype_file ][5]  = array($ptype_obj->labels->all_items, $ptype_obj->cap->edit_posts,  $ptype_file);
	$submenu[ $ptype_file ][10]  = array($ptype_obj->labels->add_new, $ptype_obj->cap->create_posts, $post_new_file);

	$i = 15;
	foreach(get_taxonomies(array(), 'objects') as $tax) {
		if(!$tax->show_ui || !$tax->show_in_menu || !in_array($ptype, (array) $tax->object_type, true))
			continue;

		$submenu[ $ptype_file ][$i++] = array(esc_attr($tax->labels->menu_name), $tax->cap->manage_terms, sprintf($edit_tags_file, $tax->name));
	}
}
unset($ptype, $ptype_obj, $ptype_for_id, $ptype_menu_position, $menu_icon, $i, $tax, $post_new_file);

$menu[59] = array('', 'read', 'separator2', '', 'wp-menu-separator');




$count = '';


$menu[65] = array(sprintf(__('Plugins %s'), $count), 'activate_plugins', 'plugins.php', '', 'menu-top menu-icon-plugins', 'menu-plugins', 'la-puzzle-piece');




if(current_user_can('list_users'))
	$menu[70] = array(__('Users'), 'list_users', 'users.php', '', 'menu-top menu-icon-users', 'menu-users', 'la-users');
else
	$menu[70] = array(__('Profile'), 'read', 'profile.php', '', 'menu-top menu-icon-users', 'menu-users', 'la-users');

if(current_user_can('list_users')) {
	$_wp_real_parent_file['profile.php'] = 'users.php'; // Back-compat for plugins adding submenus to profile.php.
	$submenu['users.php'][5] = array(__('All Users'), 'list_users', 'users.php');
	if(current_user_can('create_users')) {
		$submenu['users.php'][10] = array(_x('Add New', 'user'), 'create_users', 'user-new.php');
	}

	$submenu['users.php'][15] = array(__('Your Profile'), 'read', 'profile.php');
} else {
	$_wp_real_parent_file['users.php'] = 'profile.php';
	$submenu['profile.php'][5] = array(__('Your Profile'), 'read', 'profile.php');
	if(current_user_can('create_users')) {
		$submenu['profile.php'][10] = array(__('Add New User'), 'create_users', 'user-new.php');
	}
}



$change_notice = '';


// translators: %s is the update notification bubble, if updates are available.
$menu[80]                               = array(sprintf(__('Settings %s'), $change_notice), 'manage_options', 'options-general.php', '', 'menu-top menu-icon-settings', 'menu-settings', 'la-cog');
	$submenu['options-general.php'][10] = array(_x('General', 'settings screen'), 'manage_options', 'options-general.php');
	$submenu['options-general.php'][15] = array(__('Writing'), 'manage_options', 'options-writing.php');
	$submenu['options-general.php'][20] = array(__('Reading'), 'manage_options', 'options-reading.php');
	$submenu['options-general.php'][30] = array(__('Media'), 'manage_options', 'options-media.php');
	$submenu['options-general.php'][40] = array(__('Permalinks'), 'manage_options', 'options-permalink.php');

$_wp_last_utility_menu = 80; // The index of the last top-level menu in the utility menu group

$menu[99] = array('', 'read', 'separator-last', '', 'wp-menu-separator');

require_once(ABSPATH . 'wp-admin/includes/menu.php');