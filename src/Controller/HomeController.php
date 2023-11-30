<?php

namespace App\Controller;

use App\Repository\MangaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home_index')]
    public function index(MangaRepository $mangaRepository): Response
    {
        $appName = $this->getParameter('app_name');
        $topMangas = $mangaRepository->getTopMangas();
        $latestMangas = $mangaRepository->getLatestMangas();
        // TODO Utiliser le cache pour les mangas

        return $this->render('pages/home/index.html.twig', [
            'appName' => $appName,
            'topMangas' => $topMangas,
            'latestMangas' => $latestMangas,
        ]);
    }
}
