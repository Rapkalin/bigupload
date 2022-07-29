<?php

// Setup the following cron to remove the files on you server once a day
// 0 0 * * * php  ~/Users/r.kalinowski/Sites/bigupload/website/scripts/cron.php

// Use the right path if you are on prod env or if you are on local env.
// Server : "/bigupload/website/app/uploads/";
// Local : "website/app/uploads/";
$path = getcwd() . "/website/app/uploads/";
$realPath = $_ENV['PWD'] . "/website/app/uploads/";
$serverPath = $_SERVER['PWD'] . "/website/app/uploads/";
echo "--- PATHS TO CHECK ---" . "\n";
echo "Current directory is $path" . "\n";
echo "Realpath is $realPath" . "\n";
echo "Server path is $serverPath" . "\n";
echo "------" . "\n" . "\n";

// retrieve the files in the upload
if (count(scandir($serverPath)) > 0) {
    echo "--- CHECK FOR FILES TO DELETE ---" . "\n" . "\n";
    echo "Analysing directory... $serverPath" . "\n";
    $arrayFiles = array_diff(scandir($serverPath), array('.', '..'));
    echo count($arrayFiles) . " files found to delete." . "\n";
    echo "------" . "\n" . "\n";

    // Check if there are files to be deleted in the directory
    if (count($arrayFiles) > 0) {
        foreach($arrayFiles as $file) {
            $filePath = $serverPath . $file;
            $fileCreatedAt = date("F d Y H:i:s.", filectime($filePath));
            $todayDate = date("F d Y H:i:s.");

            echo "--- CHECKING IF FILE IS OLD ENOUGH TO BE DELETED ---" . "\n";
            echo "File uploaded date: " . $fileCreatedAt . " for file: $file" ."\n";
            echo "Today date is: " . $todayDate . "\n";
            echo "------" . "\n" . "\n";

            // If the created time of the file is more than 6 hours old so we delete the file
            if (strtotime($fileCreatedAt . ' + 6 hours') <= $todayDate) {
                echo "--- DELETING IN PROGRESS ---" . "\n";
                echo "Deleting: " . $file . "\n" . "\n";
                if (unlink($serverPath . $file)) {
                    echo "------" . "\n";
                    echo "File deleted: $file!" . "\n";
                    echo "------" . "\n" . "\n";
                } else {
                    echo "Couldn't delete file" . "\n";
                    echo "------" . "\n" . "\n";
                }
            } else {
                echo "--- DELETE PROCESS STOPPED ---" . "\n";
                echo "Why you ask? Well, this file is too young to die!!";
                echo "------" . "\n" . "\n";
            }
        }
    } else {
        echo "No file to delete" . "\n";
        echo "------" . "\n" . "\n";
    }
}
