<?php

namespace App\Controller;

use App\Repository\MangaRepository;
use App\Services\Api\ApiJikanService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/manga')]
class MangaController extends AbstractController
{
    #[Route('/search', name: 'manga_search', methods: ['POST'])]
    public function searchMangas(Request $request, MangaRepository $mangaRepository): Response
    {
        $searchTerm = $request->request->get('searchTerm');

        $mangas = [];
        if ($searchTerm && strlen($searchTerm) >= 3) {
            $mangas = $mangaRepository->searchManga($searchTerm);
        }

        return $this->render('components/htmx/mangas_list.html.twig', [
            'mangasSearch' => $mangas,
        ]);
    }

    #[Route('/search/api_jikan', name: 'manga_search_api_jikan', methods: ['POST'])]
    public function searchMangasApiJikan(Request $request, ApiJikanService $apiJikanService): Response
    {
        $searchTerm = $request->request->get('searchTerm');

        $mangas = [];
        if ($searchTerm && strlen($searchTerm) >= 3) {
            $results = $apiJikanService->fetchMangaByTitle($searchTerm);

            foreach ($results as $manga) {
                $mangas[] = $apiJikanService->saveMangaDatasInDb($manga);
            }
        }

        return $this->render('components/htmx/mangas_list.html.twig', [
            'mangasSearch' => $mangas,
        ]);
    }
}
