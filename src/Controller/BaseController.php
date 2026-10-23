<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class BaseController extends AbstractController
{
    protected function createItemNotFoundException(): NotFoundHttpException
    {
        return $this->createNotFoundException(
            "It seems that the file you are looking for doesn't exist.\nIt might have been deleted."
        );
    }
}
