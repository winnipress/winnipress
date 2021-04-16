<?php
/**
 * Bootstrap file for setting the ABSPATH constant
 * and loading the wp-config.php file. The wp-config.php
 * file will then load the wp-settings.php file, which
 * will then set up the WordPress environment.
 *
 * If the wp-config.php file is not found then an error
 * will be displayed asking the visitor to set up the
 * wp-config.php file.
 *
 * Will also search for wp-config.php in WordPress' parent
 * directory to allow the WordPress directory to remain
 * untouched.
 *
 * @package WordPress
 */

// Define ABSPATH as this file's directory
if(!defined('ABSPATH')){
	define('ABSPATH', dirname(__FILE__) . '/');
}

// Report everything
error_reporting(E_ALL);
ini_set('display_errors', TRUE);

// Require the wp-config.php file
if(file_exists(ABSPATH . 'wp-config.php')){

	require_once(ABSPATH . 'wp-config.php');

}elseif(@file_exists(dirname(ABSPATH) . '/wp-config.php') && !@file_exists(dirname(ABSPATH) . '/wp-settings.php')){

	// The config file is one level above ABSPATH and is not part of another installation
	require_once(dirname(ABSPATH) . '/wp-config.php');

}else{

	wp_die('Missing wp-config.php');
	
}