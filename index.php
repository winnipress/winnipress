<?php
// WHAT IS THIS FILE: This is the WP Frontend starting point

function yeah($met){
    if(!isset($GLOBALS['metoditos'])){
        $GLOBALS['metoditos']=array();
    }

    if (!in_array($met, $GLOBALS['metoditos'])){
    $GLOBALS['metoditos'][] = $met;
    }
}

// Require debug tools
require_once(dirname(__FILE__) . '/wp-includes/winni-debug-tools.php');

// Load WP
require_once(dirname(__FILE__) . '/wp-load.php');

// Load the corresponding template
require(dirname(__FILE__) . '/wp-load-template.php');


// Register all called functions to see what we use and what not
global $wpdb;
foreach ($GLOBALS['metoditos'] as $the_calleddstuff) {
	$wpdb->get_results("INSERT IGNORE INTO calledfunc (funciii) VALUES ('".$the_calleddstuff."') ON DUPLICATE KEY UPDATE calls=calls+1");
}


// Register all called files to see what we use and what not
$all_included_files_so_far = get_included_files();
foreach ($all_included_files_so_far as $the_included_file) {
    $filennenemae = '.'.str_replace('\\','/',str_replace('C:\laragon\www\winnipress','',$the_included_file))."\n";
	$wpdb->get_results("INSERT IGNORE INTO calledfiles (filename) VALUES ('".sanitize_text_field($filennenemae)."') ON DUPLICATE KEY UPDATE calls=calls+1");
}