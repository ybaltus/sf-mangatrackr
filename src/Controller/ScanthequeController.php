<?php

namespace App\Controller;

use App\Entity\StatusTrack;
use App\Services\Common\ScanthequeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
        $mangaDatas = $request->toArray();
        $results = null;

        if (!array_key_exists('mangas', $mangaDatas)) {
            $status = 400;
        } else {
            $results = count($mangaDatas['mangas']) > 0
                && $scanthequeService->persistMangasDatas($statusTrack, $user, $mangaDatas['mangas']);
        }
        //        dd($results);

        return $this->json($results, $status);
    }
}
