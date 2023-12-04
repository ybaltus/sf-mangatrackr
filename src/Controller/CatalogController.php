<?php

namespace App\Controller;

use App\Repository\MangaRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CatalogController extends AbstractController
{
    #[Route('/catalog', name: 'catalog_index')]
    public function index(MangaRepository $mangaRepository, Request $request, PaginatorInterface $paginator): Response
    {
        $paginationMangas = $paginator->paginate(
            $mangaRepository->paginationQuery(),
            $request->query->getInt('page', 1),
            16
        );

        return $this->render('pages/catalog/index.html.twig', [
            'paginationMangas' => $paginationMangas,
        ]);
    }
}
