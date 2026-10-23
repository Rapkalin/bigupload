<?php

namespace App\Controller;

use App\Repository\ItemRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ShowController extends BaseController
{
    #[Route('/download/{showId}', name: 'item.show', methods: ['GET'])]
    public function index(string $showId, ItemRepository $itemRepository): Response
    {
        $item = $itemRepository->findAvailableByShowId($showId) ?? throw $this->createItemNotFoundException();

        return $this->render('show/index.html.twig', [
            'item' => $item->formatData(),
        ]);
    }
}
