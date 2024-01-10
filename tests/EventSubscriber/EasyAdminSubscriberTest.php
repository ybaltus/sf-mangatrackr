<?php

namespace App\Tests\EventSubscriber;

use App\Entity\UserInvitationCode;
use App\EventSubscriber\EasyAdminSubscriber;
use App\Services\Common\MailService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Event\AbstractLifecycleEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;

class EasyAdminSubscriberTest extends TestCase
{
    private function dispatchEvent(
        EasyAdminSubscriber $subscriber,
        AbstractLifecycleEvent $event
    ): void {
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber($subscriber);
        $dispatcher->dispatch($event);
    }

    private function getEasyAdminSubscriber(MailService $mailerService = null): EasyAdminSubscriber
    {
        // Mock the services
        $router = $this->createMock(UrlGeneratorInterface::class);
        $params = $this->createMock(ContainerBagInterface::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $mailService = $mailerService ?: $this->createMock(MailService::class);

        return new EasyAdminSubscriber($router, $params, $mailService, $em);
    }

    public function testBeforeEntityPersistedEventIsSubscribed(): void
    {
        $this->assertArrayHasKey(BeforeEntityPersistedEvent::class, EasyAdminSubscriber::getSubscribedEvents());
    }

    public function testAfterEntityPersistedEventIsSubscribed(): void
    {
        $this->assertArrayHasKey(AfterEntityPersistedEvent::class, EasyAdminSubscriber::getSubscribedEvents());
    }

    public function testInvitationCode(): void
    {
        // Create a new UserInvitationCode
        $userInvitationCode = (new UserInvitationCode())
            ->setEmail('testSendEmail@test.fr')
        ;

        // New EasyAdminSubscriber
        $subscriber = $this->getEasyAdminSubscriber();

        // Create BeforeEntityPersistedEvent
        $event = new BeforeEntityPersistedEvent($userInvitationCode);

        // Dispatch the event
        $this->dispatchEvent($subscriber, $event);

        // Check if the CodeInvitaion has been generated
        $this->assertNotNull($userInvitationCode->getCodeInvitation());
    }

    public function testSendEmailInvitationCode(): void
    {
        $mailService = $this->createMock(MailService::class);

        // New EasyAdminSubscriber
        $subscriber = $this->getEasyAdminSubscriber($mailService);

        // Create a new UserInvitationCode
        $userInvitationCode = (new UserInvitationCode())
            ->setEmail('testSendEmail@test.fr')
            ->setCodeInvitation(Uuid::v4())
        ;

        // Create AfterEntityPersistedEvent
        $event = new AfterEntityPersistedEvent($userInvitationCode);

        // Expect SendEmail function is called
        $mailService->expects($this->once())->method('sendEmail');

        // Dispatch the event
        $this->dispatchEvent($subscriber, $event);
    }
}
