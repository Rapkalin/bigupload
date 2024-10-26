<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;

class DownloadController extends AbstractController
{
    #[Route('/download', name: 'file.download')]
    public function download(Request $request, KernelInterface $kernel): Response
    {
        $fileName = $request->query->get('fileName');

        // $fileName = $_GET['fileName'];
        $publicDir = $kernel->getProjectDir() . '/public';
        $path = $publicDir . "/uploads/" . $fileName;

        $size = filesize($path);
        $fp = fopen($path, "rb");
        $content = fread($fp, $size);
        fclose($fp);

        header("Content-length: ". $size);
        header("Content-type: application/octet-stream");
        header("Content-disposition: attachment; filename=".$fileName.";" );
        return new Response($content);
    }
}
