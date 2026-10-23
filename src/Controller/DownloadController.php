<?php

namespace App\Controller;

use App\Repository\ItemRepository;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

class DownloadController extends BaseController
{
    // Max execution time of a download (multi GB files)
    private const int DOWNLOAD_TIME_LIMIT = 20 * 60;

    /**
     * Streams the zip; BinaryFileResponse supports HTTP Range requests so interrupted downloads can be resumed.
     */
    #[Route('/downloadFile/{showId}', name: 'file.download', methods: ['GET'])]
    public function download(
        string $showId,
        ItemRepository $itemRepository,
        string $uploadDir,
    ): BinaryFileResponse {
        $item = $itemRepository->findAvailableByShowId($showId);
        $path = $item ? $uploadDir . DIRECTORY_SEPARATOR . $item->getZipName() : null;

        if (!$path || !is_file($path)) {
            throw $this->createItemNotFoundException();
        }

        set_time_limit(self::DOWNLOAD_TIME_LIMIT);

        $response = new BinaryFileResponse($path, headers: ['Content-Type' => 'application/zip']);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $item->getZipName());
        $response->setPrivate();

        return $response;
    }
}
