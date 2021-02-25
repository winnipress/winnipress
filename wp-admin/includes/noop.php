<?php
/**
 * Noop functions for load-scripts.php and load-styles.php.
 *
 * @package WordPress
 * @subpackage Administration
 * @since 4.4.0
 */


function __() { yeah(__METHOD__);}


function _x() { yeah(__METHOD__);}


function add_filter() { yeah(__METHOD__);}


function esc_attr() { yeah(__METHOD__);}


function apply_filters() { yeah(__METHOD__);}


function get_option() { yeah(__METHOD__);}


function is_lighttpd_before_150() { yeah(__METHOD__);}


function add_action() { yeah(__METHOD__);}


function did_action() { yeah(__METHOD__);}


function do_action_ref_array() { yeah(__METHOD__);}


function get_bloginfo() { yeah(__METHOD__);}


function is_admin() { yeah(__METHOD__);return true;}


function site_url() { yeah(__METHOD__);}


function admin_url() { yeah(__METHOD__);}


function home_url() { yeah(__METHOD__);}


function includes_url() { yeah(__METHOD__);}


function wp_guess_url() { yeah(__METHOD__);}

if ( !function_exists( 'json_encode')) :

function json_encode() { yeah(__METHOD__);}
endif;

function get_file( $path) { yeah(__METHOD__);

	if ( function_exists('realpath')) { 
		$path = realpath( $path);
	}

	if ( !$path || !@is_file( $path)) {
		return '';
	}

	return @file_get_contents( $path);
}