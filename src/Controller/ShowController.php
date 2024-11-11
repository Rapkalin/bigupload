<?php

namespace App\Controller;

use App\Repository\ItemRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ShowController extends BaseController
{
    #[Route('/download/{showId}', name: 'item.show', methods: ['GET'])]
    public function index(Request $request, ItemRepository $itemRepository): Response
    {
        $item = $itemRepository->findOneBy(['show_id' => $request->attributes->get('showId')]);
        return $this->render('show/index.html.twig', [
            'item' => $item->formatData(),
        ]);
    }
}
