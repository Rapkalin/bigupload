<?php

// Setup the following cron to remove the files on you server once a day
// 0 0 * * * php  /Users/r.kalinowski/Sites/bigupload/website/scripts/cron.php

// Uncomment the right path: $pathServer if you are on prod env and $pathLocal if you are on local env.
//Server : "/bigupload/website/app/uploads/";
// Local : "website/app/uploads/";
// $pathServer = "/bigupload/website/app/uploads/";
$path = "website/app/uploads/";

// retrieve the files in the upload
if (count(scandir($path)) > 0) {
    foreach(array_diff(scandir($path), array('.', '..')) as $file) {
        echo "Deleting: " . $path . $file . "\n";
            if (unlink($path . $file)) {
                echo "File deleted!" . "\n";
                echo "------" . "\n" . "\n";
            } else {
                echo "Couldn't delete file" . "\n";
            }
    }
} else {
    echo "No file to delete" . "\n";
}
