<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ScanthequeController extends AbstractController
{
    #[Route('/scantheque', name: 'scantheque_index')]
    public function index(): Response
    {
        $user = $this->getUser();

        return $this->render('pages/scantheque/index.html.twig', [
            'controller_name' => 'ScanthequeController',
            'user' => $user,
        ]);
    }
}
