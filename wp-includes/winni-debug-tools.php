<?php
// WHAT IS THIS: This setup allows throwing logs into the box and list them at the end

$GLOBALS['winni_logs'] = array();

// Function to add logs
function winni_log($message){

    // Where has this function been called?
    $backtrace = debug_backtrace();
    $caller = array_shift($backtrace);

    // Compose the log HTML
    $log_html = '<b>'.basename($caller['file']) . ':' .  $caller['line'] . '</b><br>' . $message;

    // Add the new log
    $GLOBALS['winni_logs'][] = $log_html;

}


// Print logs
function winni_print_logs(){

    if(!empty($GLOBALS['winni_logs'])){

        // Open the logs div
        echo '<div onclick="this.style.display=\'none\';" style="position:fixed;right:0;top:0;height:100vh;width:25vw;min-width:400px;background-color:rgba(0,0,0,.88);box-shadow:0 0 .4rem rgba(0,0,0,.5);color:#fff;overflow-y:auto;z-index:9999">';

        // Print logs
        foreach($GLOBALS['winni_logs'] as $log){
            echo '<div style="font-family:Courier New,Courier,monospace;padding:1rem 1rem 0 1rem;line-height:1.5">'.$log.'</div>';
        }

        // Close the logs div
        echo '</div>';

    }

}