<?php

namespace App\Controller;

/**
 *
 * File content based on
 * Copyright 2013, Moxiecode Systems AB
 * Released under GPL License.
 *
 * License: http://www.plupload.com/license
 * Contributing: http://www.plupload.com/contributing
 *
 * And upgraded by
 * Rapkalin: https://github.com/Rapkalin
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

use App\Entity\Item;
use App\Services\FileService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;

class ItemController extends BaseController
{
    // Settings
    // $targetDir = ini_get("upload_tmp_dir") . DIRECTORY_SEPARATOR . "plupload";
    private string $targetDir = 'uploads';
    private bool $cleanupTargetDir = true; // Remove old files
    private int $maxFileAge = 5 * 3600; // Temp file age in seconds
    private string $filePath;
    private string $fileName;
    private string $zipName;
    private string $zipPath;
    private int $chunk;
    private int $chunks;
    private EntityManagerInterface $entityManager;
    private FileService $fileService;
    private LoggerInterface $uploadLogger;
    private string $uploadDir;
    private array $allowedFileExtensions = [
        'jpg',
        'jpeg',
        'png',
        'gif',
        'mp4',
        'mov',
        'pdf',
        'mp3',
        'vtt',
        'srt',
        'zip'
    ];

    public function __construct(
        EntityManagerInterface $entityManager,
        FileService $fileService,
        KernelInterface $kernel,
        LoggerInterface $uploadLogger
    ) {
        $this->entityManager = $entityManager;
        $this->fileService = $fileService;
        $this->uploadDir = $kernel->getProjectDir() . '/public/' . $this->targetDir;
        $this->uploadLogger = $uploadLogger;
    }

    /**
     * Return Success JSON-RPC response to FileUploaded event in upload.js
     *
     * @throws Exception
     */
    #[Route('/handleFile', name: 'file.handle')]
    public function handleFile (): JsonResponse
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
            set_time_limit(5 * 60);
            $this->setFileName();
            $this->createTargetDir();
            $this->setChunk();

            if (!$this->isAllowedToProceed()) {
                formatDebug($this->uploadLogger, 'debug', 'Proceed Error' , [
                    'status' => 'error',
                    'context' => __METHOD__,
                    'code' => 403,
                ]);

                return formatJsonResponseData([
                        'status' => 'error',
                        'message' => "{$this->fileName} is not allowed to proceed."
                    ],
                    403
                );
            }

            $this->setFilePath();
            if ($this->cleanupTargetDir) {
                $this->removeOldTempFiles();
            }

            $this->rebuildFile();
            $downloadUrl = $this->renameAndSaveFile();

            if ($downloadUrl) {
                return formatJsonResponseData([
                        'status' => 'success',
                        'message' => "{$this->fileName} has been created."
                    ],
                    201,
                    extraData: [
                        "filename" => $this->fileName,
                        "download_url" => $downloadUrl
                    ],
                );
            }
        } catch (Exception $e) {
            formatDebug($this->uploadLogger, 'debug', 'Success' , [
                'status' => 'error',
                'context' => __METHOD__,
                'error' => $e->getMessage(),
                'code' => 500,
            ]);

            return formatJsonResponseData([
                    'status' => 'error',
                    'message' => "{$this->fileName} was not uploaded.",
                    'error' => 'See debug file for debug'
                ],
                500
            );
        }

        return formatJsonResponseData([
                'status' => 'success',
                'message' => "{$this->fileName} chunk successfully was uploaded.",
            ],
            201,
            extraData: ["filename" => $this->fileName],
        );
    }

    private function isAllowedToProceed(): bool
    {
        if (
            $this->chunk >= 1 &&
            !file_exists($this->targetDir . DIRECTORY_SEPARATOR . $this->fileName . '.part')
        ) {
            return false;
        }
        return true;
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
    private function saveFile (string $showId) : string
    {
        $retry = 0;
        do {
            if(file_exists($this->uploadDir . DIRECTORY_SEPARATOR . $showId . '.zip')) {
                try {
                    $data = $this->buildItemData($showId);
                    $item = (new Item())->setItem($data);
                    $this->entityManager->persist($item);
                    $this->entityManager->flush();
                    $this->entityManager->getConnection()->executeQuery('UPDATE count_downloads SET uploaded_files = uploaded_files + 1 WHERE id = ?', [1]);
                    return $item->getDownloadPageUrl();
                } catch (Exception $e) {
                    $this->clearFile($this->filePath);
                    throw new Exception("Error while saving file: {$e->getMessage()}", 500);
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
    private function buildItemData(string $showId): false|array
    {
        $createdAt = $this->fileService->getFileCreatedAt($this->filePath);

        return [
            'title' => $this->fileName,
            'zip_name' => $this->zipName,
            'download_page_url' => $this->fileService->buildDownloadUrl($showId),
            'download_file_url' => getDomaineUrl() . DIRECTORY_SEPARATOR . "downloadFile/$showId",
            'extension' => $this->fileService->getFileExtension($this->fileName),
            'size' => $this->fileService->getFileSize($this->zipPath),
            'created_at' => $createdAt,
            'expiration_date' => $this->fileService->getFileSizeExpirationDate($createdAt),
            'show_id' => $showId
        ];
    }

    private function createTargetDir():  void
    {
        if (!file_exists($this->targetDir) && !mkdir($this->targetDir) && !is_dir($this->targetDir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $this->targetDir));
        }
    }

    private function setFilePath(): void
    {
        $this->setFileName();
        $this->filePath = $this->targetDir . DIRECTORY_SEPARATOR . $this->fileName;
    }

    private function setFileName(): void
    {
        if (isset($_REQUEST["name"])) {
            $this->fileName = $_REQUEST["name"];
        } elseif (!empty($_FILES)) {
            $this->fileName = $_FILES["file"]["name"];
        } else {
            $this->fileName = uniqid("file_", TRUE);
        }
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
            return formatJsonResponseData([
                    'status' => 'error',
                    'message' => "Failed to open temp directory.",
                ],
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
            formatDebug($this->uploadLogger, 'debug', 'REBUILD ERROR' , [
                'status' => 'error',
                'context' => __METHOD__,
                'error' => "Failed to open output stream.",
                'code' => 102,
            ]);

            return formatJsonResponseData([
                    'status' => 'error',
                    'message' => "Failed to open output stream.",
                ],
                102
            );
        }

        if (!empty($_FILES)) {
            if ($_FILES["file"]["error"] || !is_uploaded_file($_FILES["file"]["tmp_name"])) {
                formatDebug($this->uploadLogger, 'debug', 'REBUILD ERROR' , [
                    'status' => 'error',
                    'context' => __METHOD__,
                    'error' => "Failed to move uploaded file.",
                    'code' => 103,
                ]);

                return formatJsonResponseData([
                        'status' => 'error',
                        'message' => "Failed to move uploaded file.",
                    ],
                    103
                );
            }

            // Read binary input stream and append it to temp file
            if (!$in = @fopen($_FILES["file"]["tmp_name"], "rb")) {
                formatDebug($this->uploadLogger, 'debug', 'REBUILD ERROR' , [
                    'status' => 'error',
                    'context' => __METHOD__,
                    'error' => "Failed to open input stream and append it to temp file.",
                    'code' => 101,
                ]);

                return formatJsonResponseData([
                        'status' => 'error',
                        'message' => "Failed to open input stream and append it to temp file.",
                    ],
                    101
                );
            }
        } else {
            if (!$in = @fopen("php://input", "rb")) {
                formatDebug($this->uploadLogger, 'debug', 'REBUILD ERROR' , [
                    'status' => 'error',
                    'context' => __METHOD__,
                    'error' => "Failed to open input stream.",
                    'code' => 101,
                ]);

                return formatJsonResponseData([
                        'status' => 'error',
                        'message' => "Failed to open input stream.",
                    ],
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
    private function renameAndSaveFile(): bool|string
    {
        // Check if file has been uploaded
        if (!$this->chunks || $this->chunk === $this->chunks - 1) {
            // Strip the temp .part suffix off
            rename("{$this->filePath}.part", $this->filePath);
            if (in_array($this->fileService->getFileExtension($this->filePath), $this->allowedFileExtensions)) {
                $showId = $this->fileService->getFileDownloadPageUrl();
                try {
                    $zipPath = $this->fileService->moveFileToDirectory($showId, $this->filePath, $this->fileName, $this->uploadDir);
                    if ($zipPath) {
                        $this->fileService->directoryToZip($showId, $this->uploadDir);
                        $this->filePath = $zipPath;
                        $this->zipName = $showId . '.zip';
                        $this->zipPath = $zipPath;
                        return $this->saveFile($showId);
                    }
                } catch (\Exception $e) {
                    formatDebug($this->uploadLogger, 'debug', 'ZIP ERROR' , [
                        'status' => 'error',
                        'message' => "Something went wrong while trying to zip: {$this->fileName}.",
                        'context' => __METHOD__,
                        'error' => $e->getMessage(),
                        'code' => 500,
                    ]);
                }
            }

            return false;
        }
        return true;
    }
}
