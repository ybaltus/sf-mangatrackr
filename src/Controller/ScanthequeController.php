<?php

namespace App\Controller;

use App\Entity\MangaUserTrack;
use App\Entity\StatusTrack;
use App\Services\Common\ScanthequeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/scantheque')]
class ScanthequeController extends AbstractController
{
    #[Route('', name: 'scantheque_index')]
    public function index(): Response
    {
        $user = $this->getUser();

        return $this->render('pages/scantheque/index.html.twig', [
            'controller_name' => 'ScanthequeController',
            'user' => $user,
        ]);
    }

    #[Route('/smut_datas/{nameSlug}', name: 'scantheque_smutd', methods: ['POST'])]
    public function saveMangaUserTrackDatas(
        Request $request,
        ScanthequeService $scanthequeService,
        StatusTrack $statusTrack
    ): JsonResponse {
        $user = $this->getUser();
        $status = 200;
        $results = null;
        /*
         * Schema $mangaDatas :
         * [
         *   "mangas" => [
         *      0 => ["title", "titleSlug", "urlImg", "nbChapters", "mut", "statusTrack", "nbChaptersTrack"]
         *   ]
         * ]
         */
        $mangaDatas = $request->toArray();

        if (!array_key_exists('mangas', $mangaDatas)) {
            $status = 400;
        } else {
            $results = count($mangaDatas['mangas']) > 0
                && $scanthequeService->persistMangasDatas($statusTrack, $user, $mangaDatas['mangas']);
        }

        return $this->json($results, $status);
    }

    #[Route('/umut_datas/{id}', name: 'scantheque_umutd', methods: ['POST', 'PUT'])]
    #[IsGranted('mut_edit', 'mangaUserTrack')]
    public function updateMangaUserTrackDatas(
        Request $request,
        ScanthequeService $scanthequeService,
        MangaUserTrack $mangaUserTrack
    ): JsonResponse {
        $status = 200;
        $mangaData = $request->toArray();
        $results = null;

        if (!array_key_exists('mut', $mangaData)) {
            $status = 400;
        } else {
            $results = $scanthequeService->updateMangasData($mangaUserTrack, $mangaData);
        }

        return $this->json($results, $status);
    }

    #[Route('/dmut_datas/{id}', name: 'scantheque_dmutd', methods: ['DELETE'])]
    #[IsGranted('mut_delete', 'mangaUserTrack')]
    public function deleteMangaUserTrackDatas(
        EntityManagerInterface $em,
        MangaUserTrack $mangaUserTrack
    ): JsonResponse {
        $status = 200;
        $message = 'Delete with success';

        try {
            $em->remove($mangaUserTrack);
            $em->flush();
        } catch (\Exception $e) {
            $message = $e->getMessage();
            $status = 400;
        }

        return $this->json($message, $status);
    }
}
