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
        $hasError = false;
        $searchTerm = $request->request->get('searchTerm');
        $isAdult = $request->request->get('adult-input') ? true : false;

        $errors = $this->validateSearchTerm($searchTerm, $validator);

        if (count($errors) > 0) {
            $hasError = true;
        } else {
            $searchTerm = htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8');
            if ($searchTerm && strlen($searchTerm) >= 3) {
                $mangas = $mangaRepository->searchManga($searchTerm, $isAdult);
            }
        }

        return $this->render('components/htmx/mangas_list.html.twig', [
            'mangasSearch' => $mangas,
            'hasError' => $hasError,
            'searchTerm' => $searchTerm,
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
        $isAdult = $request->request->get('adult-input') ? true : false;

        $errors = $this->validateSearchTerm($searchTerm, $validator);

        if (count($errors) > 0) {
            $errorMessage = true;
        } else {
            $searchTerm = htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8');

            if ($searchTerm && strlen($searchTerm) >= 3) {
                $results = $apiJikanService->fetchMangaByTitle($searchTerm, $isAdult);
                foreach ($results as $manga) {
                    $mangas[] = $apiJikanService->saveMangaDatasInDb($manga);
                }
            }
        }

        return $this->render('components/htmx/mangas_list.html.twig', [
            'mangasSearch' => $mangas,
            'errorMessage' => $errorMessage,
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
            new Assert\Regex('/^[a-zA-Z0-9-_]*$/'),
        ]);
    }
}
