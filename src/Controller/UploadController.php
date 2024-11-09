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

use App\Entity\Item;
use App\Services\FileService;
use DateMalformedStringException;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\KernelInterface;
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
    private EntityManagerInterface $entityManager;
    private FileService $fileService;
    private string $uploadPath;


    public function __construct(
        EntityManagerInterface $entityManager,
        FileService $fileService,
        KernelInterface $kernel,
    ) {
        $this->entityManager = $entityManager;
        $this->fileService = $fileService;
        $this->uploadPath = $kernel->getProjectDir() . '/public/uploads/';
    }

    /**
     * Return Success JSON-RPC response to FileUploaded event in upload.js
     *
     * @throws Exception
     */
    #[Route('/buildFile', name: 'file.build')]
    public function buildFile ()
    {
        try {
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
            $response = $this->checkIfRenameAndSaveFile();

            if ($response) {
                return formatJsonResponseData(
                    'success',
                    __METHOD__,
                    "{$this->fileName} was successfully uploaded and created.",
                    201,
                    extraData: ["filename" => $this->fileName],
                );
            }
        } catch (Exception $e) {
            return formatJsonResponseData(
                'error',
                __METHOD__,
                "{$this->fileName} was not uploaded.",
                500,
                "Error: {$e->getMessage()}",
            );
        }

        return formatJsonResponseData(
            'success',
            __METHOD__,
            "Chunk from {$this->fileName} was successfully uploaded.",
            201,
            extraData: ["filename" => $this->fileName],
        );
    }

    private function clearFile(string $filePath): void
    {
        $part = file_exists($this->filePath . '.part') ? '.part' : '';
        if(
            file_exists($this->filePath) ||
            file_exists($this->filePath . 'part')
        ) {
            unlink($filePath) . $part;
        }
    }

    /**
     * If item is successfully saved we return trye
     * Else the item isn't saved in DB we delete the created file and return a 500 error
     *
     * @throws Exception
     */
    private function handleFile (): bool
    {
        $retry = 0;
        do {
            if(file_exists($this->filePath)) {
                try {
                    $data = $this->buildItemData();
                    $item = (new Item())->setItem($data);
                    $this->entityManager->persist($item);
                    $this->entityManager->flush();
                    return true;
                } catch (Exception $e) {
                    $this->clearFile($this->filePath);
                    throw new Exception($e->getMessage(), 500);
                }
            }

            $retry++;
        }  while (
            !file_exists($this->filePath) &&
            $retry < 5
        );

        throw new Exception("File might not exist", 500);
    }

    /**
     * @throws DateMalformedStringException
     * @throws Exception
     */
    private function buildItemData(): false|array
    {
        $created_at = $this->fileService->getFileCreatedAt($this->filePath);
        return [
            'title' => $this->fileName,
            'download_url' => $this->fileService->buildDownloadUrl($this->fileName),
            'extension' => $this->fileService->getFileExtension($this->fileName),
            'size' => $this->fileService->getFileSize($this->filePath),
            'created_at' => $created_at,
            'expiration_date' => $this->fileService->getFileSizeExpirationDate($created_at)
        ];
    }

    private function createTargetDir(): void
    {
        if (!file_exists($this->targetDir) && !mkdir($this->targetDir) && !is_dir($this->targetDir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $this->targetDir));
        }
    }

    private function setFilePath(): void
    {
        if (isset($_REQUEST["name"])) {
            $this->fileName = $_REQUEST["name"];
        } elseif (!empty($_FILES)) {
            $this->fileName = $_FILES["file"]["name"];
        } else {
            $this->fileName = uniqid("file_", TRUE);
        }

        $this->filePath = $this->targetDir . DIRECTORY_SEPARATOR . $this->fileName;
    }

    private function setChunk(): void
    {
        // Chunking might be enabled
        $this->chunk = isset($_REQUEST["chunk"]) ? (int) $_REQUEST["chunk"] : 0;
        $this->chunks = isset($_REQUEST["chunks"]) ? (int) $_REQUEST["chunks"] : 0;
    }

    private function removeOldTempFiles(): true|JsonResponse
    {
        if (!is_dir($this->targetDir) || !$dir = opendir($this->targetDir)) {
            return formatJsonResponseData(
                'error',
                'removeOldTempFiles',
                "Failed to open temp directory.",
                100
            );
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

        return true;
    }

    private function rebuildFile()
    {
        // Open temp file and rebuild the file
        // create the finalfile ($filePath.part) that will be written with the chunk files
        if (!$out = @fopen("{$this->filePath}.part", $this->chunks ? "ab" : "wb")) {
            return formatJsonResponseData(
                'error',
                __METHOD__,
                "Failed to open output stream.",
                102
            );
        }

        if (!empty($_FILES)) {
            if ($_FILES["file"]["error"] || !is_uploaded_file($_FILES["file"]["tmp_name"])) {
                return formatJsonResponseData(
                    'error',
                    __METHOD__,
                    "Failed to move uploaded file.",
                    103
                );
            }

            // Read binary input stream and append it to temp file
            if (!$in = @fopen($_FILES["file"]["tmp_name"], "rb")) {
                return formatJsonResponseData(
                    'error',
                    __METHOD__,
                    "Failed to open input stream and append it to temp file.",
                    101
                );
            }
        } else {
            if (!$in = @fopen("php://input", "rb")) {
                return formatJsonResponseData(
                    'error',
                    __METHOD__,
                    "Failed to open input stream.",
                    101
                );
            }
        }

        // The file is rebuild here
        while ($buff = fread($in, 4096)) {
            fwrite($out, $buff);
        }

        fclose($out);
        fclose($in);
    }

    /**
     * @throws Exception
     */
    private function checkIfRenameAndSaveFile()
    {
        // Check if file has been uploaded
        if (!$this->chunks || $this->chunk === $this->chunks - 1) {
            // Strip the temp .part suffix off
            rename("{$this->filePath}.part", $this->filePath);
            return $this->handleFile();
        }
    }
}
