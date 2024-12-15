<?php

// Setup the following cron to remove the files on you server once a day
// CRON       PATH TO PHP                                  PATH TO SCRIPT                                               PATH TO LOG
// 0 0 * * * /Applications/MAMP/bin/php/php8.1.13/bin/php /Users/r.kalinowski/Sites/bigupload/scripts/cron/cron.php >> /Users/r.kalinowski/Sites/bigupload/scripts/cron/erreur_cron.log
// if log error only 2>&1: https://stackoverflow.com/questions/818255/what-does-21-mean

$serverPath = __DIR__ . "/../../public/uploads/";

echo " -------------- " . "\n";
echo " PATHS TO CHECK " . "\n";
echo "Current directory is $serverPath" . "\n";
echo "\n" . "\n";

if (count(scandir($serverPath)) > 0) { // check if there are files in the upload directory
    $arrayFiles = listFilesToDelete($serverPath); // Check if there are files to be deleted in the directory
    if (count($arrayFiles)) {
        foreach($arrayFiles as $file) {
            if (fileCanBeDeleted($file, $serverPath)) {
                deleteFile($file, $serverPath);
            } else {
                echo "    DELETE PROCESS STOPPED    " . "\n";
                echo "Why you ask? Well, this file is too young to die!!" . "\n";
                echo " -------------- " . "\n" . "\n";
            }
        }

        return true;
    }

    echo "No file to delete" . "\n";
    echo " -------------- " . "\n" . "\n";
}

function fileCanBeDeleted(string $file, string $serverPath) : bool
{
    $extraTime = ' + 6 day'; // How long we let a file on the server +1 current day
    $filePath = $serverPath . $file;
    $fileCreatedAt = date("F d Y H:i:s.", filectime($filePath));
    $fileCreatedAtExtraTime = date("F d Y H:i:s.", strtotime($fileCreatedAt . $extraTime));
    $todayDate = date("F d Y H:i:s.");

    echo "    CHECKING IF FILE IS OLD ENOUGH TO BE DELETED    " . "\n";
    echo "File uploaded date: " . $fileCreatedAt . " for file: $file" ."\n";
    echo "Today date is: " . $todayDate . "\n";
    echo "\n" . "\n";

    return $fileCreatedAtExtraTime <= $todayDate;
}

function listFilesToDelete(string $serverPath) : array
{
    echo "    CHECK FOR FILES TO DELETE    " . "\n" . "\n";
    echo "Analysing directory... $serverPath" . "\n";
    $filesToDelete = array_diff(scandir($serverPath), array('.', '..'));
    echo  count($filesToDelete) . " files found." . "\n";
    echo "\n" . "\n";

    return $filesToDelete;
}

function deleteFile(string $file, string $serverPath) : void
{
    echo "     DELETING IN PROGRESS     " . "\n";
    echo "Deleting: " . $file . "\n" . "\n";
    if (unlink($serverPath . $file)) {
        echo "\n";
        echo "File deleted: $file!" . "\n";
        echo "\n" . "\n";
    } else {
        echo "Couldn't delete file: $file" . "\n";
        echo " -------------- " . "\n" . "\n";
    }
}
