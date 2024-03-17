<?php

namespace App\Controller;

use App\Controller\Trait\DetectMobileDeviceTrait;
use App\Entity\ReleaseMangaUpdatesAPI;
use App\Repository\ReleaseMangaUpdatesAPIRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CalendarController extends AbstractController
{
    use DetectMobileDeviceTrait;

    #[Route('/calendar', name: 'calendar_index')]
    public function index(
        ReleaseMangaUpdatesAPIRepository $releaseMangaUpdatesAPIRepository,
        Request $request,
        PaginatorInterface $paginator
    ): Response {
        // Detect if the user is using a mobile device
        $isMobileDevice = $this->detectMobileDevice($request);

        // Get Releases per page
        $paginationReleases = $paginator->paginate(
            $releaseMangaUpdatesAPIRepository->paginationQuery(),
            $request->query->getInt('page', 1),
            $isMobileDevice ? 20 : 30
        );

        // Group the releases per dates
        $releasesFormatted = [];
        /**
         * @var ReleaseMangaUpdatesAPI $release
         */
        foreach ($paginationReleases as $release) {
            $releasedAt = $release->getReleasedAt()->format('d/m/Y');
            if (!array_key_exists($releasedAt, $releasesFormatted)) {
                $releasesFormatted[$releasedAt] = [];
            }
            $releasesFormatted[$releasedAt][] = $release;
        }

        return $this->render('pages/calendar/index.html.twig', [
            'paginationReleases' => $paginationReleases,
            'releasesFormatted' => $releasesFormatted,
        ]);
    }
}
