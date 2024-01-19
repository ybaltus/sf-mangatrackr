<?php

namespace App\EventSubscriber;

use App\Controller\MangaController;
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
            KernelEvents::CONTROLLER_ARGUMENTS => ['onMangaController'],
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

    private function incrementNbView(Manga $manga): void
    {
        $oldNbView = $manga->getMangaStatistic()->getNbView();
        $manga->getMangaStatistic()->setNbView($oldNbView + 1);
        $this->em->flush();
    }
}
