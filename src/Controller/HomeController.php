<?php

namespace App\Controller;

use App\Repository\UploadCounterRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends BaseController
{
    #[Route('/', name: 'home.index')]
    public function index(UploadCounterRepository $uploadCounterRepository): Response
    {
        return $this->render('home/index.html.twig', ['counter' => $uploadCounterRepository->get()]);
    }
}
