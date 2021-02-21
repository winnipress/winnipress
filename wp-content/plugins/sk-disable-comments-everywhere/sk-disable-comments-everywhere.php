<?php

/**
* Plugin Name: Disable comments
* Description: Completely disable comments.
* Version: 1.0.0
* Author: Álvaro Franz
* GitHub Plugin URI: https://github.com/salamarkesa/sk-disable-comments-everywhere
**/

// Removes from admin menu
add_action('admin_menu', 'sk_remove_admin_menus');
function sk_remove_admin_menus(){
    remove_menu_page('edit-comments.php');
}
// Removes from post and pages
add_action('init', 'remove_comment_support', 100);

function remove_comment_support(){
    remove_post_type_support( 'post', 'comments' );
    remove_post_type_support( 'page', 'comments' );
}

// Removes from admin bar
function sk_admin_bar_render(){
    global $wp_admin_bar;
    $wp_admin_bar->remove_menu('comments');
}
add_action('wp_before_admin_bar_render', 'sk_admin_bar_render');