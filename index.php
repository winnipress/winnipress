<?php
// WHAT IS THIS FILE: This is the WP starting point

// Load WP
require_once(dirname(__FILE__) . '/wp-load.php');

// Load the corresponding template
require(dirname(__FILE__) . '/wp-load-template.php');


// Register all called files to see what we use and what not
$all_included_files_so_far = get_included_files();
global $wpdb;
foreach ($all_included_files_so_far as $the_included_file) {
    $filennenemae = '.'.str_replace('\\','/',str_replace('C:\laragon\www\winnipress','',$the_included_file))."\n";
	$wpdb->get_results("INSERT IGNORE INTO calledfiles (filename) VALUES ('".sanitize_text_field($filennenemae)."') ON DUPLICATE KEY UPDATE calls=calls+1");
}