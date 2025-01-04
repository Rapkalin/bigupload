<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends BaseController
{

    private Connection $connection;

    public function __construct(Connection $connection) {
        $this->connection = $connection;
    }

    #[Route('/', name: 'home.index')]
    public function index() : Response {
        return $this->render('home/index.html.twig', ['counter' => $this->getCounter()]);
    }

    private function getCounter(): int
    {
        $sql = 'SELECT * FROM count_downloads WHERE id = 1';
        $stmt = $this->connection->prepare($sql);
        $result = $stmt->executeQuery()->fetchAssociative();
        return $result['uploaded_files'];
    }
}
