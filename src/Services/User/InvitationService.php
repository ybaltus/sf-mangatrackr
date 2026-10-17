<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Entity\User;
use App\Entity\UserInvitationCode;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

final class InvitationService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * Tell if the invitation code can still be used.
     *
     * Side effect: a code that is expired or already used is deactivated.
     */
    public function isInvitationCodeValid(UserInvitationCode $userInvitationCode): bool
    {
        $isValid = !$userInvitationCode->getUser()
            && ($this->clock->now() < $userInvitationCode->getExpiredAt());

        if (!$isValid) {
            $userInvitationCode->setIsActivated(false);
            $this->em->flush();
        }

        return $isValid;
    }

    /**
     * Create the user from an invitation and disable the invitation code.
     */
    public function register(UserInvitationCode $userInvitationCode, User $user, string $plainPassword): void
    {
        // Use setPlainPassword to set the plainPassword for prePersist doctrineEvent(UserEntityListener)
        $user->setPlainPassword($plainPassword);

        $userInvitationCode->setUser($user);
        $userInvitationCode->setIsActivated(false);

        $this->em->persist($user);
        $this->em->flush();
    }
}
