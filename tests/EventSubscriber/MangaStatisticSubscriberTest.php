<?php

namespace App\Tests\EventSubscriber;

use App\Controller\MangaController;
use App\Entity\Manga;
use App\EventSubscriber\MangaStatisticSubscriber;
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
}
