<?php

// Setup the following cron to remove the files on you server once a day
// CRON       PATH TO PHP                                  PATH TO SCRIPT                                               PATH TO LOG                                     Prefix the file log name with the current date
// 0 * * * * /Applications/MAMP/bin/php/php8.1.13/bin/php /Users/r.kalinowski/Sites/bigupload/scripts/cron/cron.php >> /Users/r.kalinowski/Sites/bigupload/scripts/cron/$(date +\%Y-\%m-\%d)_debug_cron.log
// if log error only 2>&1: https://stackoverflow.com/questions/818255/what-does-21-mean

include(__DIR__ . '/../../vendor/autoload.php');
use Symfony\Component\Dotenv\Dotenv;
loadDotEnv();

$serverPath = __DIR__ . "/../../public/uploads/";
if (count(scandir($serverPath)) > 0) { // check if there are files in the upload directory
    $arrayFiles = listFilesToDelete($serverPath); // Check if there are files to be deleted in the directory
    if (count($arrayFiles) && $pdo = connectDatabase()) {
        foreach($arrayFiles as $file) {
            if (fileCanBeDeleted($file, $serverPath)) {
                deleteFile($file, $serverPath, $pdo);
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
//    $extraTime = ' + 6 day'; // How long we let a file on the server +1 current day
    $extraTime = ' + 1 minute'; // How long we let a file on the server +1 current day
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

function deleteFile(string $file, string $serverPath, PDO $pdo) : void
{
    echo "     DELETING IN PROGRESS     " . "\n";
    echo "Deleting: " . $file . "\n" . "\n";
    if (unlink($serverPath . $file)) {
        deleteFileFromDatabase($pdo, $file);
        echo "\n";
        echo "File deleted: $file!" . "\n";
        echo "\n" . "\n";
    } else {
        echo "Couldn't delete file: $file" . "\n";
        echo " -------------- " . "\n" . "\n";
    }
}

function connectDatabase() : PDO|bool
{
    try {
        $pdo = new PDO("mysql:host=" . $_ENV['DB_HOST'] . ";dbname=" . $_ENV['DB_NAME'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        return $pdo;
    } catch (PDOException $e) {
        echo 'connectDatabase ERROR: ' . $e->getMessage() . PHP_EOL;
        return false;
    }
}

function loadDotEnv() : void
{
    try {
        $dotenv = new Dotenv();
        $dotenv->load(__DIR__.'/../../.env');
    } catch (Exception $e) {
        echo 'loadDotEnv ERROR: ' . $e->getMessage() . PHP_EOL;
    }
}

function deleteFileFromDatabase(PDO $pdo, $file) : void
{
    $unbufferedResult = $pdo->prepare('DELETE FROM items WHERE zip_name = ?');
    $unbufferedResult->execute([$file]);
}
