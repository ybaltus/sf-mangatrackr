<?php

namespace App\Tests\EventSubscriber;

use App\Controller\MangaController;
use App\Controller\ScanthequeController;
use App\Entity\Manga;
use App\Entity\MangaUserTrack;
use App\EventSubscriber\MangaStatisticSubscriber;
use App\Services\Common\ScanthequeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class MangaStatisticSubscriberTest extends KernelTestCase
{
    public function testHasKernelEventsControllerArguments(): void
    {
        $this->assertArrayHasKey(KernelEvents::CONTROLLER_ARGUMENTS, MangaStatisticSubscriber::getSubscribedEvents());
    }

    public function testOnMangaController(): void
    {
        // Boot kernel and retrieve the container service
        $kernel = self::bootKernel();
        $container = static::getContainer();

        // Instantiate ControllerArgumentsEvent
        $entityManager = $container->get(EntityManagerInterface::class);
        /**
         * @var Manga $manga
         */
        $manga = $entityManager->getRepository(Manga::class)->findOneByTitleSlug('one-piece');
        $oldNbView = $manga->getMangaStatistic()->getNbView();
        $subscriber = new MangaStatisticSubscriber($entityManager);
        $request = new Request();
        $controller = [new MangaController(), 'index'];
        $event = new ControllerArgumentsEvent(
            $kernel,
            $controller,
            [$manga],
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        // Call the onMangaCOntroller function
        $subscriber->onMangaController($event);

        $this->assertEquals($oldNbView + 1, $manga->getMangaStatistic()->getNbView());
    }

    public function testOnScanthequeController(): void
    {
        // Boot kernel and retrieve the container service
        $kernel = self::bootKernel();
        $container = static::getContainer();

        // Instantiate ControllerArgumentsEvent
        $entityManager = $container->get(EntityManagerInterface::class);
        $scanthequeService = $container->get(ScanthequeService::class);
        /**
         * @var MangaUserTrack $mut
         */
        $mut = $entityManager->getRepository(MangaUserTrack::class)->findBy([], [], 1)[0];
        $manga = $mut->getManga();
        $oldNbTrack = $manga->getMangaStatistic()->getNbTrack();
        $scanthequeDatasBody = json_encode(['mangas' => [
            [
                'title' => $manga->getTitle(),
                'titleSlug' => $manga->getTitleSlug(),
                'urlImg' => $manga->getMangaJikanAPI()->getMalImgWebp(),
                'nbChapters' => 100,
                'mut' => $mut->getId(),
                'statusTrack' => $mut->getStatusTrack()->getNameSlug(),
                'nbChaptersTrack' => 10,
            ],
        ]]);


        $subscriber = new MangaStatisticSubscriber($entityManager);
        $request = new Request([], [], [], [], [], [], $scanthequeDatasBody);
        $controller = [new ScanthequeController(), 'saveMangaUserTrackDatas'];
        $event = new ControllerArgumentsEvent(
            $kernel,
            $controller,
            [$request, $scanthequeService, $mut->getStatusTrack()],
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        // Call the onScanthequeController function
        $subscriber->onScanthequeController($event);

        $this->assertEquals($oldNbTrack + 1, $manga->getMangaStatistic()->getNbTrack());
    }
}
