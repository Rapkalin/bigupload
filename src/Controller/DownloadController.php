<?php

namespace App\Controller;

use App\Repository\ItemRepository;
use JetBrains\PhpStorm\NoReturn;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;

class DownloadController extends AbstractController
{
    #[NoReturn] #[Route('/downloadFile/{showId}', name: 'file.download', methods: ['GET'])]
    public function download(
        Request $request,
        KernelInterface $kernel,
        ItemRepository $itemRepository
    ): void
    {
        $item = $itemRepository->findOneBy(['show_id' => $request->attributes->get('showId')]);
        $publicDir = $kernel->getProjectDir() . '/public';
        $path = $item ? $publicDir . "/uploads/" . $item->getZipName() : null;

        if (!$path || !is_file($path)) {
            throw $this->createNotFoundException(
                "It seems that the file you are looking for doesn't exist.\nIt might have been deleted."
            );
        }

        header("Content-Description: File Transfer");
        header("Content-Type: application/octet-stream");
        header('Content-Disposition: attachment; filename="' . $item->getZipName() . '"');
        header('Expires: Tue, 01 Jan 1980 1:00:00 GMT');
        header("Cache-Control: no-cache");
        header("Content-Length: {$item->getSize()}");

        if (ob_get_level()) {
            ob_end_clean();
        }

        // 20 minutes execution time
        set_time_limit(20 * 60);
        readfile($path);
        exit;
    }
}
