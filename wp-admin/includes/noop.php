<?php
/**
 * Noop functions for load-scripts.php and load-styles.php.
 *
 * @package WordPress
 * @subpackage Administration
 * @since 4.4.0
 */


function __() {}


function _x() {}


function add_filter() {}


function esc_attr() {}


function apply_filters() {}


function get_option() {}


function is_lighttpd_before_150() {}


function add_action() {}


function did_action() {}


function do_action_ref_array() {}


function get_bloginfo() {}


function is_admin() {return true;}


function site_url() {}


function admin_url() {}


function home_url() {}


function includes_url() {}


function wp_guess_url() {}

if ( !function_exists( 'json_encode')) :

function json_encode() {}
endif;

function get_file( $path) {

	if ( function_exists('realpath')) {
		$path = realpath( $path);
	}

	if ( !$path || !@is_file( $path)) {
		return '';
	}

	return @file_get_contents( $path);
}