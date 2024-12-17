<?php

// Setup the following cron to remove the files on you server once a day
// CRON       PATH TO PHP                                  PATH TO SCRIPT                                               PATH TO LOG                                     Prefix the file log name with the current date
// 0 * * * * /Applications/MAMP/bin/php/php8.1.13/bin/php /Users/r.kalinowski/Sites/bigupload/scripts/cron/cron.php >> /Users/r.kalinowski/Sites/bigupload/scripts/cron/$(date +\%Y-\%m-\%d)_debug_cron.log
// if log error only 2>&1: https://stackoverflow.com/questions/818255/what-does-21-mean

include(__DIR__ . '/../../vendor/autoload.php');
use Symfony\Component\Dotenv\Dotenv;

$serverPath = __DIR__ . "/../../public/uploads/";
if (count(scandir($serverPath)) > 0) { // check if there are files in the upload directory
    $arrayFiles = listFilesToDelete($serverPath); // Check if there are files to be deleted in the directory
    loadDotEnv();
    cleanServerDirectory($serverPath, $arrayFiles);
} else {
    echo "No file to delete" . "\n";
    echo " -------------- " . "\n" . "\n";
}

function cleanServerDirectory(string $serverPath, array $arrayFiles) : void
{
    foreach($arrayFiles as $file) {
        if (!removeNonZipFile($file, $serverPath)) {
            // If file hasn't been remove before we can check if it needs to be removed now
            removeOldFile($file, $serverPath);
        }
    }
}

function removeNonZipFile(string $fileName, string $serverPath) : bool
{
    echo "CHECKING IF FILE IS ZIP -> Filename: " . $fileName . "\n";
    if (
        !isFileZip($fileName, $serverPath) &&
        isFileOldEnough($fileName, $serverPath)
    ) {
        echo "Deleting non .zip file -> Filename: " . $fileName . "\n"  . "\n";
        unlink($serverPath . $fileName);
        return true;
    }

    return false;
}

function removeOldFile(string $fileName, string $serverPath) : void
{
    if ($pdo = connectDatabase()) {
        if (fileCanBeDeleted($fileName, $serverPath)) {
            deleteFile($fileName, $serverPath, $pdo);
        } else {
            echo "File not deleted: " . $fileName . "\n"  . "\n";
        }
    }
}

function isFileZip(string $fileName, string $serverPath) : bool
{
    return preg_match("~\.zip$~i", $serverPath . $fileName);
}

function isFileOldEnough(string $fileName, string $serverPath) : bool
{
    $fileCreationTime = filemtime($serverPath . $fileName);
    return $fileCreationTime && (time() - $fileCreationTime) > 3600;
}

function fileCanBeDeleted(string $file, string $serverPath) : bool
{
    $extraTime = ' + 6 day'; // How long we let a file on the server +1 current day
    // $extraTime = ' + 1 minute'; // for test purpose
    $filePath = $serverPath . $file;
    $fileCreatedAt = date("F d Y H:i:s.", filectime($filePath));
    $fileCreatedAtExtraTime = date("F d Y H:i:s.", strtotime($fileCreatedAt . $extraTime));
    $todayDate = date("F d Y H:i:s.");

    echo "CHECKING IF FILE IS OLD -> Filename " . $file . "\n";
    echo "File uploaded date: " . $fileCreatedAt ."\n";
    echo "Today date is: " . $todayDate . "\n";
    return $fileCreatedAtExtraTime <= $todayDate;
}

function listFilesToDelete(string $serverPath) : array
{
    echo "\n" . "ANALYSING DIRECTORY... $serverPath" . "\n";
    $filesToDelete = array_diff(scandir($serverPath), array('.', '..'));
    echo  count($filesToDelete) . " files found." . "\n" . "\n";
    return $filesToDelete;
}

function deleteFile(string $file, string $serverPath, PDO $pdo) : void
{
    echo "DELETING IN PROGRESS" . "\n";
    echo "Deleting: " . $file . "\n" . "\n";
    if (unlink($serverPath . $file)) {
        deleteFileFromDatabase($pdo, $file);
        echo "\n" . "File deleted: $file!" . "\n";
        echo "\n" . "\n";
    } else {
        echo "Couldn't delete file: $file" . "\n";
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
        $dotenv->load(__DIR__ . '/../../.env');
    } catch (Exception $e) {
        echo 'loadDotEnv ERROR: ' . $e->getMessage() . PHP_EOL;
    }
}

function deleteFileFromDatabase(PDO $pdo, $file) : void
{
    $unbufferedResult = $pdo->prepare('DELETE FROM items WHERE zip_name = ?');
    $unbufferedResult->execute([$file]);
}
