<?php

var_dump(scandir("website/app/uploads/"));
echo "basename: " . basename() . "\n";

foreach(scandir("website/app/uploads/") as $file) {
    if($file != "." && $file != ".." && $file != ".DS_STORE") {
        echo "Deleting: " . $file . "\n";

        /*        unlink("website/app/uploads/" . $file)*/
    } else {
        echo $file . " has not been added";
    }
}
/*echo realpath('/bigupload/website/scripts/cron.php');*/

