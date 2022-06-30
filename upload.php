<?php
/**
 * upload.php
 *
 * Copyright 2013, Moxiecode Systems AB
 * Released under GPL License.
 *
 * License: http://www.plupload.com/license
 * Contributing: http://www.plupload.com/contributing
 */

#!! IMPORTANT:
#!! this file is just an example, it doesn't incorporate any security checks and
#!! is not recommended to be used in production environment as it is. Be sure to
#!! revise it and customize to your needs.


// Make sure file is not cached (as it happens for example on iOS devices)
//header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
//header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
//header("Cache-Control: no-store, no-cache, must-revalidate");
//header("Cache-Control: post-check=0, pre-check=0", false);
//header("Pragma: no-cache");

/*
// Support CORS
header("Access-Control-Allow-Origin: *");
// other CORS headers if any...
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
	exit; // finish preflight CORS requests here
}
*/

// 5 minutes execution time
@set_time_limit(5 * 60);

// Uncomment this one to fake upload time
// usleep(5000);

// Settings
$targetDir = ini_get("upload_tmp_dir") . DIRECTORY_SEPARATOR . "plupload";
//$targetDir = 'uploads';
// $cleanupTargetDir = true; // Remove old files
$maxFileAge = 5 * 3600; // Temp file age in seconds

// Create target dir
if (!file_exists($targetDir)) {
    @mkdir($targetDir);
}

// For MacOS: Clean the hidden file in $targetDir by removing ".DS_STORE files
// To be noted noted that the to "." and ".." are just symbols and cannot be removed.
// . represents the directory you are in and .. represents the parent directory.
if (file_exists("{$targetDir}/.DS_STORE")) {
    unlink("{$targetDir}/.DS_STORE");
}

// Get a file name
if (isset($_REQUEST["name"])) {
    $fileName = $_REQUEST["name"];
} elseif (!empty($_FILES)) {
    $fileName = $_FILES["file"]["name"];
} else {
    $fileName = uniqid("file_");
}

// Create path to file
$filePath = $targetDir . DIRECTORY_SEPARATOR . $fileName;

// Chunking might be enabled
$chunk = isset($_REQUEST["chunk"]) ? (int) $_REQUEST["chunk"] : 0;
$chunks = isset($_REQUEST["chunks"]) ? (int) $_REQUEST["chunks"] : 0;

// Remove old temp files
// if ($cleanupTargetDir) {
//    if (!is_dir($targetDir) || !$dir = opendir($targetDir)) {
//        die('{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to open temp directory."}, "id" : "id"}');
//    }

//    $file = readdir($dir);

//    if ($file != "." && $file != ".." && $file != ".DS_Store") {
//        while ($file !== false) {
//            $tmpfilePath = $targetDir . DIRECTORY_SEPARATOR . $file;


            // If temp file is current file proceed to the next
//            if ($tmpfilePath == $filePath) {
//                continue;
//            }

            // Remove temp file if it is older than the max age and is not the current file
//            if (preg_match('/\.part$/', $file) && (filemtime($tmpfilePath) < time() - $maxFileAge)) {
//                @unlink($tmpfilePath);
//            }
//        }

//        closedir($dir);
//    }
//}


// Open temp file
if (!$out = @fopen("{$filePath}.part", $chunks ? "ab" : "wb")) {
    die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
}

if (!empty($_FILES)) {
    if ($_FILES["file"]["error"] || !is_uploaded_file($_FILES["file"]["tmp_name"])) {
        die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}');
    }

    // Read binary input stream and append it to temp file
    if (!$in = @fopen($_FILES["file"]["tmp_name"], "rb")) {
        die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
    }
} else {
    if (!$in = @fopen("php://input", "rb")) {
        die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
    }
}

while ($buff = fread($in, 4096)) {
    fwrite($out, $buff);
}

@fclose($out);
@fclose($in);

// Rebuild the file - WORK IN PROGRESS
// Check if file has been uploaded
if (!$chunks || $chunk === $chunks - 1) {
    // Strip the temp .part suffix off
    rename("{$filePath}.part", $filePath);

    // Check if it is the last uploaded file/chunk.
    if ($chunk === $chunks - 1) {

        // Scan the dir and retrieve all the temp files including
        // the last/final file in which we want to append the data of the other temp files.
        $files = scandir($targetDir);
        $finalFile = $files[count($files) - 1];
        $finalFilePath = $targetDir . "/" . $finalFile;
        $finalFileOpen = fopen($finalFilePath, "ab");
        $file_info = pathinfo($finalFilePath);
        $file_extension = $file_info['extension'];
        switch ($file_extension) {
            case 'mp4': $ctype= 'video/mp4'; break;
        }

        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        header('Content-Type: ' . $ctype);

        // We add the data of each file in the final file.
        foreach($files as $file) {
            if ($file !== "." && $file !== ".." && $file !== $finalFile) {
                // Appending all the temp files in one final file
                $filePath = $targetDir . "/" . $file;
                $ChunkPath = fopen($filePath, "rb");
                while ($ChunkFile = fread($ChunkPath, filesize($filePath))) {
                    fwrite($finalFileOpen, $ChunkFile);
                }
                fclose($ChunkPath);
                // We delete the temp files
                unlink($ChunksPath);
            }
        }

        // We close the final file.
        fclose($finalFileOpen);
    }

}

// Return Success JSON-RPC response
 die('{"jsonrpc" : "2.0", "result" : null, "id" : "id"}');
