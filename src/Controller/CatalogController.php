<?php

namespace App\Controller;

use App\Repository\MangaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CatalogController extends AbstractController
{
    #[Route('/catalog', name: 'catalog_index')]
    public function index(MangaRepository $mangaRepository): Response
    {
        $mangas = $mangaRepository->getMangas();

        return $this->render('pages/catalog/index.html.twig', [
            'mangas' => $mangas,
        ]);
    }
}
