<?php

namespace App\Services;

use Psr\Log\LoggerInterface;

class ZipService
{
    private LoggerInterface $uploadLogger;

    public function __construct(
        LoggerInterface $uploadLogger
    ) {
        $this->uploadLogger = $uploadLogger;
    }

    /**
     * Zip a directory et save it in the archive un a file.
     *
     * @param string $source Absolute path to the directory to zip
     * @param string $destination Absolute path to the zip file to create
     *
     * @return bool True if zip has been successfully create, False if not
     * @throws \Exception
     */
    public function zipDirectory(string $uploadDir, string $zipName, string $dirName): bool
    {
        try {
            return exec("cd $uploadDir && zip -r $zipName $dirName");
        } catch (\Exception $e) {
            formatDebug($this->uploadLogger, 'debug', 'ZIP ERROR' , [
                'status' => 'error',
                'message' => "Error while zipping item",
                'context' => __METHOD__,
                'error' => $e->getMessage(),
                'code' => 500,
            ]);

            throw new \Exception("ZIP ERROR: {$e->getMessage()}", 500);
        }
    }
}
