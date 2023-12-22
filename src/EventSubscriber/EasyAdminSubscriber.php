<?php

namespace App\EventSubscriber;

use App\Entity\UserInvitationCode;
use App\Services\Common\MailService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;

class EasyAdminSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private UrlGeneratorInterface $router,
        private ContainerBagInterface $params,
        private MailService $mailService,
        private EntityManagerInterface $em
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeEntityPersistedEvent::class => ['setInvitationCode'],
            AfterEntityPersistedEvent::class => ['sendEmailInvitationCode'],
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

    public function sendEmailInvitationCode(AfterEntityPersistedEvent $event): void
    {
        $entity = $event->getEntityInstance();

        if (!($entity instanceof UserInvitationCode)) {
            return;
        }

        // Params
        $appName = $this->params->get('app_name');
        $emailFrom = $this->params->get('mailer_no_reply');
        $codeInvitation = $entity->getCodeInvitation();
        $emailTo = $entity->getEmail();
        $expiredAt = $entity->getExpiredAt();
        $context = [
            'emailTo' => $emailTo,
            'invitationUrl' => $this->router->generate('invitation_register', [
                'codeInvitation' => $codeInvitation,
            ]),
            'expiredAt' => $expiredAt,
        ];

        // Send email
        $result = $this->mailService->sendEmail(
            new Address($emailFrom, $appName),
            $emailTo,
            sprintf('%s - Invitation', $appName),
            'emails/invitation.html.twig',
            $context
        );

        // Save the result of email
        $entity->setSendingEmailStatus($result);
        $this->em->flush();
    }
}
