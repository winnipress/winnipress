<?php
// WHAT IS THIS FILE: This is the WP Frontend starting point

session_start();

// Require debug tools
require_once(dirname(__FILE__) . '/wp-includes/winni-debug-tools.php');

// Load WP
require_once(dirname(__FILE__) . '/wp-load.php');

// Load the corresponding template
require(dirname(__FILE__) . '/wp-load-template.php');