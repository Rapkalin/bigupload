<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ShowController extends BaseController
{
    #[Route('/downloads/show', name: 'downloads.show')]
    public function index(Request $request): Response
    {
        $item = [
            'title' => 'Ceci est le titre du fichier',
            'download_url' => 'https://google.com',
            'expiration_date' => '12 janvier 2000',
            'expiration_time' => '3 jours', // calcul
            'size' => '18ko',
            'extension' => 'pdf',
        ];
        return $this->render('show/index.html.twig', [
            'item' => $item,
        ]);
    }
}
