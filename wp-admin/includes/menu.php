<?php
// Build Administration Menu

if(is_user_admin()){
	winni_log('IS USR ADMIN');

	/**
	 * Fires before the administration menu loads in the User Admin.
	 *
	 * The hook fires before menus and sub-menus are removed based on user privileges.
	 *
	 * @private
	 * @since 3.1.0
	 */
	do_action( '_user_admin_menu');
} else {

	/**
	 * Fires before the administration menu loads in the admin.
	 *
	 * The hook fires before menus and sub-menus are removed based on user privileges.
	 *
	 * @private
	 * @since 2.2.0
	 */
	do_action( '_admin_menu');
}

// Create list of page plugin hook names.
foreach($menu as $menu_page){
	if(false !== $pos = strpos($menu_page[2], '?')){
		// Handle post_type=post|page|foo pages.
		$hook_name = substr($menu_page[2], 0, $pos);
		$hook_args = substr($menu_page[2], $pos + 1);
		wp_parse_str($hook_args, $hook_args);
		// Set the hook name to be the post type.
		if(isset($hook_args['post_type']))
			$hook_name = $hook_args['post_type'];
		else
			$hook_name = basename($hook_name, '.php');
		unset($hook_args);
	}else{
		$hook_name = basename($menu_page[2], '.php');
	}
	$hook_name = sanitize_title($hook_name);

	if(!$hook_name){
		continue;
	}

	$admin_page_hooks[$menu_page[2]] = $hook_name;
}
unset($menu_page);

$_wp_submenu_nopriv = array();
$_wp_menu_nopriv = array();

// Loop over submenus and remove pages for which the user does not have privilege.
foreach($submenu as $parent => $sub){
	foreach($sub as $index => $data){
		if(!current_user_can($data[1])){
			unset($submenu[$parent][$index]);
			$_wp_submenu_nopriv[$parent][$data[2]] = true;
		}
	}
	unset($index, $data);

	// Remove if it's now empty
	if(empty($submenu[$parent])){
		unset($submenu[$parent]);
	}
}
unset($sub, $parent);

/*
 * Loop over the top-level menu.
 * Menus for which the original parent is not accessible due to lack of privileges
 * will have the next submenu in line be assigned as the new menu parent.
 */
foreach($menu as $id => $data) {
	if(empty($submenu[$data[2]])){
		continue;
	}

	$subs = $submenu[$data[2]];
	$first_sub = reset($subs);
	$old_parent = $data[2];
	$new_parent = $first_sub[2];

	// If the first submenu is not the same as the assigned parent, make the first submenu the new parent
	if($new_parent != $old_parent){
		$_wp_real_parent_file[$old_parent] = $new_parent;
		$menu[$id][2] = $new_parent;

		foreach($submenu[$old_parent] as $index => $data){
			$submenu[$new_parent][$index] = $submenu[$old_parent][$index];
			unset($submenu[$old_parent][$index]);
		}
		unset($submenu[$old_parent], $index);

		if(isset($_wp_submenu_nopriv[$old_parent])){
			$_wp_submenu_nopriv[$new_parent] = $_wp_submenu_nopriv[$old_parent];
		}
	}
}
unset($id, $data, $subs, $first_sub, $old_parent, $new_parent);

if(is_user_admin()){
winni_log('IS USER ADMIN');
	/**
	 * Fires before the administration menu loads in the User Admin.
	 *
	 * @since 3.1.0
	 *
	 * @param string $context Empty context.
	 */
	do_action( 'user_admin_menu', '');
}else{

	/**
	 * Fires before the administration menu loads in the admin.
	 *
	 * @since 1.5.0
	 *
	 * @param string $context Empty context.
	 */
	do_action( 'admin_menu', '');
}

/*
 * Remove menus that have no accessible submenus and require privileges
 * that the user does not have. Run re-parent loop again.
 */
foreach( $menu as $id => $data) {
	if( !current_user_can($data[1]))
		$_wp_menu_nopriv[$data[2]] = true;

	/*
	 * If there is only one submenu and it is has same destination as the parent,
	 * remove the submenu.
	 */
	if( !empty( $submenu[$data[2]]) && 1 == count ( $submenu[$data[2]])) {
		$subs = $submenu[$data[2]];
		$first_sub = reset( $subs);
		if( $data[2] == $first_sub[2])
			unset( $submenu[$data[2]]);
	}

	// If submenu is empty...
	if( empty($submenu[$data[2]])) {
		// And user doesn't have privs, remove menu.
		if( isset( $_wp_menu_nopriv[$data[2]])) {
			unset($menu[$id]);
		}
	}
}
unset($id, $data, $subs, $first_sub);

/**
 *
 * @param string $add
 * @param string $class
 * @return string
 */
function add_cssclass($add, $class) {
	$class = empty($class) ? $add : $class .= ' ' . $add;
	return $class;
}

/**
 *
 * @param array $menu
 * @return array
 */
function add_menu_classes($menu) {
	$first = $lastorder = false;
	$i = 0;
	$mc = count($menu);
	foreach( $menu as $order => $top) {
		$i++;

		if( 0 == $order) { // dashboard is always shown/single
			$menu[0][4] = add_cssclass('menu-top-first', $top[4]);
			$lastorder = 0;
			continue;
		}

		if( 0 === strpos($top[2], 'separator') && false !== $lastorder) { // if separator
			$first = true;
			$c = $menu[$lastorder][4];
			$menu[$lastorder][4] = add_cssclass('menu-top-last', $c);
			continue;
		}

		if( $first) {
			$c = $menu[$order][4];
			$menu[$order][4] = add_cssclass('menu-top-first', $c);
			$first = false;
		}

		if( $mc == $i) { // last item
			$c = $menu[$order][4];
			$menu[$order][4] = add_cssclass('menu-top-last', $c);
		}

		$lastorder = $order;
	}

	/**
	 * Filters administration menus array with classes added for top-level items.
	 *
	 * @since 2.7.0
	 *
	 * @param array $menu Associative array of administration menu items.
	 */
	return apply_filters( 'add_menu_classes', $menu);
}

uksort($menu, "strnatcasecmp"); // make it all pretty



// Prevent adjacent separators
$prev_menu_was_separator = false;
foreach( $menu as $id => $data) {
	if( false === stristr( $data[4], 'wp-menu-separator')) {

		// This item is not a separator, so falsey the toggler and do nothing
		$prev_menu_was_separator = false;
	} else {

		// The previous item was a separator, so unset this one
		if( true === $prev_menu_was_separator) {
			unset( $menu[ $id ]);
		}

		// This item is a separator, so truthy the toggler and move on
		$prev_menu_was_separator = true;
	}
}
unset( $id, $data, $prev_menu_was_separator);

// Remove the last menu item if it is a separator.
$last_menu_key = array_keys( $menu);
$last_menu_key = array_pop( $last_menu_key);
if( !empty( $menu) && 'wp-menu-separator' == $menu[ $last_menu_key ][ 4 ])
	unset( $menu[ $last_menu_key ]);
unset( $last_menu_key);

if( !user_can_access_admin_page()) {

	/**
	 * Fires when access to an admin page is denied.
	 *
	 * @since 2.5.0
	 */
	do_action( 'admin_page_access_denied');

	wp_die( __( 'Sorry, you are not allowed to access this page.'), 403);
}

$menu = add_menu_classes($menu);
