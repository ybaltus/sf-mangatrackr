<?php

namespace App\Controller;

use App\Repository\TextContentPageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TermsOfUseController extends AbstractController
{
    #[Route('/terms_of_use', name: 'terms_of_use_index')]
    public function index(TextContentPageRepository $contentPageRepository): Response
    {
        $terms = $contentPageRepository->findOneByNameSlug('terms-of-use');

        return $this->render('pages/terms_of_use/index.html.twig', [
            'terms' => $terms,
        ]);
    }
}
