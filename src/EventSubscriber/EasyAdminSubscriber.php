<?php

namespace App\EventSubscriber;

use App\Entity\UserInvitationCode;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Uid\Uuid;

class EasyAdminSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            BeforeEntityPersistedEvent::class => ['setInvitationCode'],
        ];
    }

    public function setInvitationCode(BeforeEntityPersistedEvent $event): void
    {
        $entity = $event->getEntityInstance();

        if (!($entity instanceof UserInvitationCode)) {
            return;
        }

        $codeInvitation = Uuid::v4();
        $entity->setCodeInvitation($codeInvitation);
    }
}
