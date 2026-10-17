<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Entity\User;
use App\Entity\UserInvitationCode;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AccountService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * Persist the profile informations already hydrated on the user by the form.
     */
    public function updateProfile(): void
    {
        $this->em->flush();
    }

    /**
     * Change the password of the user if the current one is valid.
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): bool
    {
        if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
            return false;
        }

        $user->setPlainPassword($newPassword);

        // To trigger the UserEntityListener
        $user->setUpdatedAt($this->clock->now());

        $this->em->flush();

        return true;
    }

    /**
     * Remove the user and all his data (cascade with UserNews, UserTrackList and MangaUserTrack).
     */
    public function deleteAccount(User $user): void
    {
        $invitations = $this->em->getRepository(UserInvitationCode::class)->findByUser($user);
        foreach ($invitations as $invitation) {
            $this->em->remove($invitation);
        }

        $this->em->remove($user);
        $this->em->flush();
    }
}
