<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Entity\User;
use App\Entity\UserResetPassword;
use App\Services\Common\MailService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;

final class ResetPasswordService
{
    private const EMAIL_TEMPLATE = 'emails/reset_password.html.twig';
    private const EMAIL_SUBJECT = 'Modification de mot de passe';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UrlGeneratorInterface $router,
        private readonly MailService $mailService,
        private readonly ClockInterface $clock,
        #[Autowire('%app_name%')]
        private readonly string $appName,
        #[Autowire('%mailer_no_reply%')]
        private readonly string $emailFrom,
    ) {
    }

    /**
     * Create (or renew) the reset code of the user, then send it by email.
     */
    public function requestReset(User $user): UserResetPassword
    {
        $userResetPasswords = $user->getUserResetPasswords();

        if (0 === count($userResetPasswords)) {
            $userResetPassword = (new UserResetPassword())
                ->setUser($user)
                ->setResetCode(Uuid::v4()->toRfc4122())
            ;
            $this->em->persist($userResetPassword);
        } else {
            $createdAt = $this->clock->now();
            $userResetPassword = $userResetPasswords[0]
                ->setResetCode(Uuid::v4()->toRfc4122())
                ->setCreatedAt($createdAt)
                ->setExpiredAt($createdAt->add(new \DateInterval('P1D')))
                ->setSendingEmailStatus('')
                ->setIsActivated(true)
            ;
        }
        $this->em->flush();

        $this->sendEmail($userResetPassword);

        return $userResetPassword;
    }

    /**
     * Tell if the reset code can still be used.
     *
     * Side effect: a code that is expired or already used is deactivated.
     */
    public function isResetCodeValid(UserResetPassword $userResetPassword): bool
    {
        $isValid = $userResetPassword->isIsActivated()
            && ($this->clock->now() < $userResetPassword->getExpiredAt());

        if (!$isValid) {
            $userResetPassword->setIsActivated(false);
            $this->em->flush();
        }

        return $isValid;
    }

    /**
     * Hash the new plain password of the user and disable the reset code.
     */
    public function updatePassword(UserResetPassword $userResetPassword): void
    {
        $user = $userResetPassword->getUser();

        $user->setPassword($this->passwordHasher->hashPassword($user, (string) $user->getPlainPassword()));
        $userResetPassword->setIsActivated(false);

        $this->em->flush();
    }

    private function sendEmail(UserResetPassword $userResetPassword): void
    {
        $emailTo = $userResetPassword->getUser()->getEmail();
        $context = [
            'emailTo' => $emailTo,
            'resetUrl' => $this->router->generate('reset_password_update', [
                'resetCode' => $userResetPassword->getResetCode(),
            ], UrlGeneratorInterface::ABSOLUTE_URL),
            'expiredAt' => $userResetPassword->getExpiredAt(),
        ];

        $result = $this->mailService->sendEmail(
            new Address($this->emailFrom, $this->appName),
            $emailTo,
            sprintf('%s - %s', $this->appName, self::EMAIL_SUBJECT),
            self::EMAIL_TEMPLATE,
            $context
        );

        // Save the result of the email
        $userResetPassword->setSendingEmailStatus($result);
        $this->em->flush();
    }
}
