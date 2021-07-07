<?php
// WHAT IS THIS: This setup allows throwing logs into the box and list them at the end

$GLOBALS['winni_logs'] = array();

// Function to add logs
function winni_log($thing){

    // Where has this function been called?
    $backtrace = debug_backtrace();
    $caller = array_shift($backtrace);

    // Use special printing depending on case
    if(is_bool($thing)){
        $thing = $thing ? '<i>{TRUE}</i>' : '<i>{FALSE}</i>';
    }elseif($thing == ''){
        $thing = '<i>{EMPTY STRING}</i>';
    }elseif(!is_string($thing)){
        $thing = '<pre>'.print_r($thing,true).'</pre>';
    }

    // Compose the log HTML
    $log_html = '<b>'.basename($caller['file']) . ':' .  $caller['line'] . '</b><br>' . $thing;

    // Add the new log
    $GLOBALS['winni_logs'][] = $log_html;

}


// Print logs
function winni_print_logs(){

    $GLOBALS['winni_logs'][] = memory_get_peak_usage()/(1024*1024);

    if(empty($GLOBALS['winni_logs'])){
        return false;
    }

    // Open the logs div
    echo '<div onclick="this.style.display=\'none\';" style="position:fixed;right:0;top:0;height:100vh;width:25vw;min-width:400px;background-color:rgba(0,0,0,.88);box-shadow:0 0 .4rem rgba(0,0,0,.5);color:#fff;overflow-y:auto;z-index:9999">';

    // Print logs
    foreach($GLOBALS['winni_logs'] as $log){
        echo '<div style="font-family:Courier New,Courier,monospace;padding:1rem 1rem 0 1rem;line-height:1.5">'.$log.'</div>';
    }

    // Close the logs div
    echo '</div>';

}