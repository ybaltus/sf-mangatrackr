<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Manga;
use App\Repository\MangaRepository;
use App\Services\Api\ApiMangaUpdatesService;
use App\Services\Api\ApiTenraiService;
use App\Services\Common\SearchTermValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/manga')]
class MangaController extends AbstractController
{
    #[Route('/{titleSlug}', name: 'manga_index', methods: ['GET'])]
    public function index(Manga $manga): Response
    {
        return $this->render('pages/manga/index.html.twig', [
            'manga' => $manga,
        ]);
    }

    #[Route('/search', name: 'manga_search', methods: ['POST'])]
    public function searchMangas(
        Request $request,
        MangaRepository $mangaRepository,
        ApiTenraiService $apiTenraiService,
        ApiMangaUpdatesService $apiMangaUpdatesService,
        SearchTermValidator $searchTermValidator,
    ): Response {
        $mangas = [];
        $hasError = false;
        // Front values are strings (ADR 0001): absent or non-scalar (array) field becomes '' and
        // fails validation instead of raising a TypeError.
        $rawSearchTerm = $request->request->all()['searchTerm'] ?? '';
        $searchTerm = is_scalar($rawSearchTerm) ? (string) $rawSearchTerm : '';
        $isAdvancedSearch = null !== $request->request->get('advanced-input');
        $isAdult = null !== $request->request->get('adult-input');

        $errorsTerm = $searchTermValidator->validate($searchTerm);

        if (count($errorsTerm) > 0) {
            $hasError = true;
        } else {
            $searchTerm = htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8');

            if ($searchTerm && strlen($searchTerm) >= 3) {
                if ($isAdvancedSearch) {
                    // Save manga id to avoid duplication
                    $tmpMangasId = [];

                    // First - We use Tenrai API
                    $tenraiResults = $apiTenraiService->fetchMangaByTitle($searchTerm, $isAdult);
                    foreach ($tenraiResults as $result) {
                        $manga = $apiTenraiService->saveMangaDatasInDb($result);
                        if (!in_array($manga->getId(), $tmpMangasId)) {
                            $mangas[] = $manga;
                            $tmpMangasId[] = $manga->getId();
                        }
                    }

                    // Second - We use MangaUpdates API
                    $mangaUpdatesResults = $apiMangaUpdatesService->fetchMangaByTitle($searchTerm);
                    foreach ($mangaUpdatesResults as $result) {
                        $mangaEntity = $apiMangaUpdatesService->saveMangaDatasInDb($result);
                        if ($mangaEntity && !in_array($mangaEntity->getId(), $tmpMangasId)) {
                            $tmpMangasId[] = $mangaEntity->getId();
                            $mangas[] = $mangaEntity;
                        }
                    }
                } else {
                    $mangas = $mangaRepository->searchManga($searchTerm, $isAdult);
                }
            }
        }

        return $this->render('components/htmx/mangas_list.html.twig', [
            'mangasSearch' => $mangas,
            'hasError' => $hasError,
            'searchTerm' => $searchTerm,
        ]);
    }
}
