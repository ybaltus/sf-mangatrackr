<?php

namespace App\Controller;

use App\Entity\Manga;
use App\Repository\MangaRepository;
use App\Services\Api\ApiJikanService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
        ApiJikanService $apiJikanService,
        ValidatorInterface $validator
    ): Response {
        $mangas = [];
        $hasError = false;
        $searchTerm = $request->request->get('searchTerm');
        $isAdvancedSearch = null !== $request->request->get('advanced-input');
        $isAdult = null !== $request->request->get('adult-input');

        $errorsTerm = $this->validateSearchTerm($searchTerm, $validator);

        if (count($errorsTerm) > 0) {
            $hasError = true;
        } else {
            $searchTerm = htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8');

            if ($searchTerm && strlen($searchTerm) >= 3) {
                if ($isAdvancedSearch) {
                    $results = $apiJikanService->fetchMangaByTitle($searchTerm, $isAdult);
                    foreach ($results as $manga) {
                        $mangas[] = $apiJikanService->saveMangaDatasInDb($manga);
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

    private function validateSearchTerm(string $searchTerm, ValidatorInterface $validator): mixed
    {
        return $validator->validate($searchTerm, [
            new Assert\Length([
                'min' => 3,
                'max' => 50,
            ]),
            new Assert\NotBlank(),
            new Assert\Regex('/^[a-zA-Z0-9-_\s]*$/'),
        ]);
    }
}
