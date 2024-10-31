<?php

// Setup the following cron to remove the files on you server once a day
// 0 0 * * * /Applications/MAMP/bin/php/php8.1.13/bin/php /Users/r.kalinowski/Sites/bigupload/scripts/cron/cron.php >> /Users/r.kalinowski/Sites/bigupload/scripts/cron/erreur_cron.log

// if log error only 2>&1 : https://stackoverflow.com/questions/818255/what-does-21-mean

/*
 *
 CRON       PATH TO PHP                                  PATH TO SCRIPT                                                                          PATH TO LOG
 0 0 * * * /Applications/MAMP/bin/php/php8.1.13/bin/php /Users/r.kalinowski/Sites/p-design-wordpress/scripts/scrapping/scrapping.php pedrali >> /Users/r.kalinowski/Sites/p-design-wordpress/scripts/scrapping/erreur_cron.log 2>&1

 */
// Info for how long we can let a file live on the server
$extraTime = ' + 7 day';

// Use the right path if you are on prod env or if you are on local env.
// Server : "/bigupload/website/app/uploads/";
// Local : "website/app/uploads/";
$serverPath = __DIR__ . "/../../public/uploads/";

echo " PATHS TO CHECK " . "\n";
echo "Current directory is $serverPath" . "\n";
echo "\n" . "\n";

// retrieve the files in the upload
if (count(scandir($serverPath)) > 0) {
    echo "    CHECK FOR FILES TO DELETE    " . "\n" . "\n";
    echo "Analysing directory... $serverPath" . "\n";
    $arrayFiles = array_diff(scandir($serverPath), array('.', '..'));
    echo count($arrayFiles) . " files found." . "\n";
    echo "\n" . "\n";

    // Check if there are files to be deleted in the directory
    if (count($arrayFiles) > 0) {
        foreach($arrayFiles as $file) {
            $filePath = $serverPath . $file;
            $fileCreatedAt = date("F d Y H:i:s.", filectime($filePath));
            $fileCreatedAtExtraTime = date("F d Y H:i:s.", strtotime($fileCreatedAt . $extraTime));
            $todayDate = date("F d Y H:i:s.");

            echo "    CHECKING IF FILE IS OLD ENOUGH TO BE DELETED    " . "\n";
            echo "File uploaded date: " . $fileCreatedAt . " for file: $file" ."\n";
            echo "Today date is: " . $todayDate . "\n";
            echo "\n" . "\n";

            // If the created time of the file is more than 6 hours old so we delete the file
            if ($fileCreatedAtExtraTime <= $todayDate) {
                echo "     DELETING IN PROGRESS     " . "\n";
                echo "Deleting: " . $file . "\n" . "\n";
                if (unlink($serverPath . $file)) {
                    echo "\n";
                    echo "File deleted: $file!" . "\n";
                    echo "\n" . "\n";
                } else {
                    echo "Couldn't delete file: $file" . "\n";
                    echo "\n" . "\n";
                }
            } else {
                echo "    DELETE PROCESS STOPPED    " . "\n";
                echo "Why you ask? Well, this file is too young to die!!";
                echo "------" . "\n" . "\n";
            }
        }
    } else {
        echo "No file to delete" . "\n";
        echo "------" . "\n" . "\n";
    }
}
