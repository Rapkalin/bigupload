<?php

// Setup the following cron to remove the files on you server once a day
// 0 0 * * * php  ~/Users/r.kalinowski/Sites/bigupload/website/scripts/cron.php

// Use the right path if you are on prod env or if you are on local env.
// Server : "/bigupload/website/app/uploads/";
// Local : "website/app/uploads/";
$path = getcwd() . "/website/app/uploads/";
$realPath = $_ENV['PWD'] . "/website/app/uploads/";
$serverPath = $_SERVER['PWD'] . "/website/app/uploads/";
echo "Current directory is $path" . "\n";
echo "Realpath is $realPath" . "\n";
echo "Server path is $serverPath" . "\n";

// retrieve the files in the upload
if (count(scandir($serverPath)) > 0) {
    echo "Analysing directory... $serverPath" . "\n";
    $arrayFiles = array_diff(scandir($serverPath), array('.', '..'));
    if (count($arrayFiles) > 0) {
        foreach($arrayFiles as $file) {
            $filePath = $serverPath . $file;
            $fileCreatedAt = date("F d Y H:i:s.", filectime($filePath));
            $todayDate = date("F d Y H:i:s.");
            // Si la date du fichier + 6  heures = date d'aujourd'hui
            if (strtotime($fileCreatedAt . ' + 6 hours') === $todayDate) {
                echo "Deleting: " . $file . "\n";
                echo "Uploaded date: " . $fileCreatedAt . " for file: $file" ."\n";
                echo "Today date is... " . $todayDate . "\n";
                if (unlink($serverPath . $file)) {
                    echo "------" . "\n";
                    echo "File deleted: $file!" . "\n";
                    echo "------" . "\n";
                } else {
                    echo "Couldn't delete file" . "\n";
                }
            } else {
                echo "This file is too young to die";
            }
        }
    } else {
    echo "No file to delete" . "\n";
    }
}
