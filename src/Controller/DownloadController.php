<?php

namespace App\Controller;

use App\Repository\ItemRepository;
use App\Services\FileService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;

class DownloadController extends AbstractController
{
    #[Route('/downloadFile/{showId}', name: 'file.download', methods: ['GET'])]
    public function download(
        Request $request,
        KernelInterface $kernel,
        ItemRepository $itemRepository,
        FileService $fileService
    ): Response
    {
        $item = $itemRepository->findOneBy(['show_id' => $request->attributes->get('showId')]);
        $publicDir = $kernel->getProjectDir() . '/public';
        $path = $publicDir . "/uploads/" . $item->getTitle();
        $fp = fopen($path, "rb");
        $content = fread($fp, $item->getSize());
        fclose($fp);
        $filename = $fileService->getTitleNoExtension($item->getTitle()) . "." . strtoupper($item->getExtension());
        header("Content-length: ". $item->getSize());
        header("Content-type: application/octet-stream");
        header("Content-disposition: attachment; filename=\"$filename\";");
        return new Response($content);
    }
}
