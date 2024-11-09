<?php

namespace App\Services;

use AllowDynamicProperties;
use App\Kernel;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

#[AllowDynamicProperties] final class FileService
{
    public function __construct(KernelInterface $kernel)
    {
        $this->uploadPath = $kernel->getProjectDir() . '/public/';
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

    /**
     * @param string $filepath
     * @return string|null Retrieve the captured extension or null if no extension has been found
     */
    public function getFileSize(string $filepath): ?string
    {
        return filesize($this->uploadPath . $filepath);
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
    public function getFileSizeExpirationDate(string $filepath, string $created_at): ?string
    {
        // Todo: calcul expiration date
        return  $created_at;
    }

    /**
     * @throws Exception
     */
    public function getTinyUrl(string $url): Response
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
            return new Response(json_encode($newUrl));

        } catch (\Exception $e) {
            throw new Exception('something went wrong: ', $e);
        }
    }

    /**
     * @throws Exception
     */
    public function buildDownloadUrl(string $fileName, string $fileSize): string
    {
        $url = $this->download($fileName, $fileSize);
        dump('buildDownloadUrl', $this->getTinyUrl($url));
        die();
        return $this->getTinyUrl($url);
    }

    public function download(string $fileName, string $fileSize): Response
    {
        $path = $this->uploadPath . "/uploads/" . $fileName;
        $fp = fopen($path, "rb");
        $content = fread($fp, $fileSize);
        fclose($fp);

        header("Content-length: ". $fileSize);
        header("Content-type: application/octet-stream");
        header("Content-disposition: attachment; filename=".$fileName.";" );
        return new Response($content);
    }

}