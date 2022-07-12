<?php


// Uncomment the right path: $pathServer if you are on prod env and $pathLocal if you are on local env.
// $pathServer = "/bigupload/website/app/uploads/";
$pathLocal = "website/app/uploads/";

// retrieve the files in the upload
if (scandir("website/app/uploads/") > 0) {
    foreach(array_diff(scandir("website/app/uploads/"), array('.', '..')) as $file) {
        echo "Deleting: " . $pathLocal . $file . "\n";
            if (unlink($pathLocal . $file)) {
                echo "File deleted!" . "\n";
                echo "------" . "\n" . "\n";
            } else {
                echo "Couldn't delete file" . "\n";
            }
    }
} else {
    echo "No file to delete" . "\n";
}
