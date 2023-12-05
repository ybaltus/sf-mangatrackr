<?php

namespace App\Controller;

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
    #[Route('/search', name: 'manga_search', methods: ['POST'])]
    public function searchMangas(
        Request $request,
        MangaRepository $mangaRepository,
        ValidatorInterface $validator
    ): Response {
        $mangas = [];
        $errorMessage = false;
        $searchTerm = $request->request->get('searchTerm');
        $errors = $this->validateSearch($searchTerm, $validator);

        if (count($errors) > 0) {
            $errorMessage = true;
        } else {
            $searchTerm = htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8');
            if ($searchTerm && strlen($searchTerm) >= 3) {
                $mangas = $mangaRepository->searchManga($searchTerm);
            }
        }

        return $this->render('components/htmx/mangas_list.html.twig', [
            'mangasSearch' => $mangas,
            'errorMessage' => $errorMessage,
        ]);
    }

    #[Route('/search/api_jikan', name: 'manga_search_api_jikan', methods: ['POST'])]
    public function searchMangasApiJikan(
        Request $request,
        ApiJikanService $apiJikanService,
        ValidatorInterface $validator
    ): Response {
        $mangas = [];
        $errorMessage = false;
        $searchTerm = $request->request->get('searchTerm');
        $errors = $this->validateSearch($searchTerm, $validator);
        if (count($errors) > 0) {
            $errorMessage = true;
        } else {
            $searchTerm = htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8');

            if ($searchTerm && strlen($searchTerm) >= 3) {
                $results = $apiJikanService->fetchMangaByTitle($searchTerm);
                foreach ($results as $manga) {
                    $mangas[] = $apiJikanService->saveMangaDatasInDb($manga);
                }
            }
        }

        return $this->render('components/htmx/mangas_list.html.twig', [
            'mangasSearch' => $mangas,
            'errorMessage' => $errorMessage,
        ]);
    }

    private function validateSearch(string $searchTerm, ValidatorInterface $validator): mixed
    {
        return $validator->validate($searchTerm, [
            new Assert\Length([
                'min' => 3,
                'max' => 50,
            ]),
            new Assert\NotBlank(),
        ]);
    }
}
