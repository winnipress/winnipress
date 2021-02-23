<?php

function listFolderFiles($dir){
    $ffs = scandir($dir);

    unset($ffs[array_search('.', $ffs, true)]);
    unset($ffs[array_search('..', $ffs, true)]);

    // prevent empty ordered elements
    if (count($ffs) < 1)
        return;

    
    foreach($ffs as $ff){
        if($ff=='.git'){
            continue;
        }

        if(is_dir($dir.'/'.$ff)){
            listFolderFiles($dir.'/'.$ff);
        }else{
            if(substr($ff, -4)=='.php'){
                echo '<li>'.$dir.'/'.$ff.'</li>';
                //$wpdb->get_results("INSERT IGNORE INTO calledfiles (filename) VALUES ('".$dir.'/'.$ff."') ON DUPLICATE KEY UPDATE calls=calls+1");
            }
        }

    }
}

echo '<ol>';
listFolderFiles('.');
echo '</ol>';