<?php

namespace App\Controller;

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
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

use App\Services\CommonsService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UploadController extends BaseController
{
    // Settings
    // $targetDir = ini_get("upload_tmp_dir") . DIRECTORY_SEPARATOR . "plupload";
    private string $targetDir = 'uploads';
    private bool $cleanupTargetDir = true; // Remove old files
    private int $maxFileAge = 5 * 3600; // Temp file age in seconds
    private string $filePath;
    private string $fileName;
    private int $chunk;
    private int $chunks;


    #[Route('/uploadFile', name: 'file.upload')]
    public function upload (): Response
    {
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
        $this->createTargetDir();
        $this->setFilePath();
        $this->setChunk();

        if ($this->cleanupTargetDir) {
            $this->removeOldTempFiles();
        }

        $this->rebuildFile();
        $this->checkIfRenameFile();

        // Return Success JSON-RPC response to FileUploaded event in main.js
        return new Response('{"jsonrpc" : "2.0", "result" : { "fileName": "' . $this->fileName . '" }, "id" : "id"}');
    }

    private function createTargetDir(): void
    {
        // Create target dir
        if (!file_exists($this->targetDir) && !mkdir($this->targetDir) && !is_dir($this->targetDir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $this->targetDir));
        }
    }

    private function setFilePath(): void
    {
        // Get a file name
        if (isset($_REQUEST["name"])) {
            $this->fileName = $_REQUEST["name"];
        } elseif (!empty($_FILES)) {
            $this->fileName = $_FILES["file"]["name"];
        } else {
            $this->fileName = uniqid("file_", TRUE);
        }

        // Create path to file
        $this->filePath = $this->targetDir . DIRECTORY_SEPARATOR . $this->fileName;
    }

    private function setChunk(): void
    {
        // Chunking might be enabled
        $this->chunk = isset($_REQUEST["chunk"]) ? (int) $_REQUEST["chunk"] : 0;
        $this->chunks = isset($_REQUEST["chunks"]) ? (int) $_REQUEST["chunks"] : 0;
    }

    private function removeOldTempFiles(): void
    {
        if (!is_dir($this->targetDir) || !$dir = opendir($this->targetDir)) {
            die('{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to open temp directory."}, "id" : "id"}');
        }

        $file = readdir($dir);

        if ($file != "." && $file != ".." && $file != ".DS_Store") {
            while ($file = true) {
                $tmpfilePath = $this->targetDir . DIRECTORY_SEPARATOR . $file;


                // If temp file is current file proceed to the next
                if ($tmpfilePath === $this->filePath) {
                    continue;
                }

                // Remove temp file if it is older than the max age and is not the current file
                if (preg_match('/\.part$/', $file) && (filemtime($tmpfilePath) < time() - $this->maxFileAge)) {
                    unlink($tmpfilePath);
                }
            }

            closedir($dir);
        }
    }

    private function rebuildFile(): void
    {
        // Open temp file and rebuild the file
        // create the finalfile ($filePath.part) that will be written with the chunk files
        if (!$out = @fopen("{$this->filePath}.part", $this->chunks ? "ab" : "wb")) {
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

        // The file is rebuild here
        while ($buff = fread($in, 4096)) {
            fwrite($out, $buff);
        }

        fclose($out);
        fclose($in);
    }

    private function checkIfRenameFile(): void
    {
        // Check if file has been uploaded
        if (!$this->chunks || $this->chunk === $this->chunks - 1) {
            // Strip the temp .part suffix off
            rename("{$this->filePath}.part", $this->filePath);
        }
    }
}
