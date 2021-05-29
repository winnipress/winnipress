<?php

// Load the corresponding template if we didn't yet
if(!isset($wp_loaded_template)){

	$wp_loaded_template = true;

	// Set up the WP query
	wp();

	// Load the theme
	require_once(ABSPATH . WPINC . '/template-loader.php');

}
