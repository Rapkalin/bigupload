<?php

namespace App\Services;

use AllowDynamicProperties;
use Exception;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

#[AllowDynamicProperties] final class FileService
{
    private LoggerInterface $logger;
    private ZipService $zipService;

    public function __construct(
        LoggerInterface $logger,
        ZipService $zipService,
    )
    {
        $this->logger = $logger;
        $this->zipService = $zipService;
    }

    /**
     * @param $filename
     * @return string|null Retrieve the captured extension or null if no extension has been found
     */
    public function getFileExtension(string $filename): ?string
    {
        if (preg_match('/\.([a-zA-Z0-9]+)$/', $filename, $matches)) {
            return $matches[1];
        }
        return null;
    }

    public function getTitleNoExtension(string $filename): ?string
    {
        if (preg_match('/^(.*?)(?:\.[a-zA-Z0-9]+)?$/', $filename, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * @param string $filepath
     * @return string|null Retrieve the captured extension or null if no extension has been found
     */
    public function getFileSize(string $filepath): ?string
    {
        return (int) filesize($filepath);
    }

    /**
     * @param string $filepath
     * @return string|null Retrieve the captured extension or null if no extension has been found
     * @throws \DateMalformedStringException
     */
    public function getFileCreatedAt(string $filepath): ?string
    {
        $created_at = date('m/d/Y H:i:s', filectime($filepath));
        return (new \DateTimeImmutable($created_at))->format('Y-m-d H:i:s');
    }

    /**
     * @param string $filepath
     * @param string $created_at
     * @return string|null Retrieve the captured extension or null if no extension has been found
     */
    public function getFileSizeExpirationDate(string $created_at): ?string
    {
        $extraTime = ('+ 7 days');
        return  date("F d Y H:i:s.", strtotime($created_at . $extraTime));
    }

    /**
     * @throws Exception
     */
    public function getTinyUrl(string $url): string
    {
        $apiUrl = 'https://tinyurl.com/api-create.php?url=' . strip_tags($url);

        try {
            $curl = curl_init();
            $timeout = 10;

            // Check if initialization had gone wrong*
            if ($curl === false) {
                throw new Exception('failed to initialize');
            }

            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_URL, $apiUrl);

            $newUrl = curl_exec($curl);

            // Check the return value of curl_exec(), too
            if (!$newUrl) {
                throw new Exception(curl_error($curl), curl_errno($curl));
            }

            // Check HTTP return code, too; might be something else than 200
            // $httpReturnCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            /* Process $content here */
            curl_close($curl);

            return $newUrl;

        } catch (\Exception $e) {
            throw new Exception('something went wrong: ', $e);
        }
    }

    /**
     * @throws Exception
     */
    public function buildDownloadUrl(string $showId): string
    {
        $url =  getDomaineUrl() . DIRECTORY_SEPARATOR . "download/" . $showId;
        try {
            return $this->getTinyUrl($url);
        } catch (\Exception $e) {
            $this->logger->error("FileService::buildDownloadUrl Error: {$e->getMessage()}");
            return $url;
        }
    }

    public function getFileDownloadPageUrl(): string
    {
        return str_replace('.','a', uniqid('bgpld-', true));
    }

    public function moveFileToDirectory(
        string $showId,
        string $filePath,
        string $fileName,
        string $uploadDir
    ): false|string
    {
        $destinationPath = $uploadDir . DIRECTORY_SEPARATOR . $showId . DIRECTORY_SEPARATOR . $fileName;
        $zipName = $uploadDir . DIRECTORY_SEPARATOR . $showId . '.zip';
        if (!file_exists($zipName)) {
            mkdir($uploadDir . DIRECTORY_SEPARATOR . $showId, 0755, true);
            rename($filePath, $destinationPath);
            return $zipName;
        }
        return false;
    }

    /**
     * @throws Exception
     */
    public function directoryToZip(string $showId, string $uploadDir): bool
    {
        $dirPath = $uploadDir . DIRECTORY_SEPARATOR . $showId . DIRECTORY_SEPARATOR;

        if (!is_dir($dirPath)) {
            throw new \InvalidArgumentException("Source '$dirPath' is not a valid directory");
        }

        $zipName =  $showId . '.zip';
        $success = $this->zipService->zipDirectory($uploadDir, $zipName, $showId);
        if ($success) {
            $this->deleteDir($dirPath);
            return 1;
        }

        return 0;
    }

    private function deleteDir(string $dirPath): void
    {
        if (! is_dir($dirPath)) {
            throw new InvalidArgumentException("$dirPath must be a directory");
        }
        if (!str_ends_with($dirPath, '/')) {
            $dirPath .= '/';
        }
        $files = glob($dirPath . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file)) {
                $this->deleteDir($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dirPath);
    }

}