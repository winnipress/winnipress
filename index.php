<?php
// WHAT IS THIS FILE: This is the WP Frontend starting point

session_start();

// Require debug tools
require_once(dirname(__FILE__) . '/wp-core/winni-debug-tools.php');

// Load WP
require_once(dirname(__FILE__) . '/wp-load.php');

// Execute the main WP query
$GLOBALS['wp']->execute_main_query();

// Load the theme
require_once(ABSPATH . 'wp-core/template-loader.php');