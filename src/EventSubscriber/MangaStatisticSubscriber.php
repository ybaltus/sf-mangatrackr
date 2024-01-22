<?php

namespace App\EventSubscriber;

use App\Controller\MangaController;
use App\Controller\ScanthequeController;
use App\Entity\Manga;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Controller\ErrorController;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class MangaStatisticSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER_ARGUMENTS => [
                ['onMangaController'],
                ['onScanthequeController'],
            ],
        ];
    }

    public function onMangaController(ControllerArgumentsEvent $event): void
    {
        $controller = $event->getController();

        // Check if Error Controller
        if ($controller instanceof ErrorController) {
            return;
        }

        // Check MangaController->index() is called
        if (
            $controller[0] instanceof MangaController
            && 0 === strcmp($controller[1], 'index')
        ) {
            /**
             * @var Manga $manga
             */
            $manga = $event->getArguments()[0];
            $this->incrementNbView($manga);
        }
    }

    public function onScanthequeController(ControllerArgumentsEvent $event): void
    {
        $controller = $event->getController();

        // Check if Error Controller
        if ($controller instanceof ErrorController) {
            return;
        }

        // Check ScanthequeController->saveMangaUserTrackDatas() is called
        if (
            $controller[0] instanceof ScanthequeController
            && 0 === strcmp($controller[1], 'saveMangaUserTrackDatas')
        ) {
            /*
             * Schema $requestParams (POST params) :
             * [
             *   "mangas" => [
             *      0 => ["title", "titleSlug", "urlImg", "nbChapters", "mut", "statusTrack", "nbChaptersTrack"]
             *   ]
             * ]
            */
            $requestParams = $event->getRequest()->toArray();
            if (array_key_exists('mangas', $requestParams)) {
                $this->incrementNbTrack($requestParams['mangas'][0]['titleSlug']);
            }
        }
    }

    private function incrementNbView(Manga $manga): void
    {
        $oldNbView = $manga->getMangaStatistic()->getNbView();
        $manga->getMangaStatistic()->setNbView($oldNbView + 1);
        $this->em->flush();
    }

    private function incrementNbTrack(string $titleSlug): void
    {
        /**
         * @var Manga $manga
         */
        $manga = $this->em->getRepository(Manga::class)->findOneByTitleSlug($titleSlug);
        $oldNbTrack = $manga->getMangaStatistic()->getNbTrack();
        $manga->getMangaStatistic()->setNbTrack($oldNbTrack + 1);
        $this->em->flush();
    }
}
