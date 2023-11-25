<?php

namespace App\EventListener;

use App\Entity\User;
use App\Entity\UserNews;
use App\Entity\UserTrackList;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsEntityListener(entity: User::class)]
class UserEntityListener
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function prePersist(User $user, PrePersistEventArgs $args): void
    {
        // Encode plain password
        $this->encodePassword($user);

        // Add UserNews
        $news = (new UserNews())
            ->setUser($user)
        ;
        $user->setUserNews($news);

        // Add UserTrackList
        $utl = (new UserTrackList())
            ->setUser($user)
        ;
        $user->setUserTrackList($utl);
    }

    public function preUpdate(User $user, PreUpdateEventArgs $args): void
    {
        // Encode plain password
        $this->encodePassword($user);
    }

    private function encodePassword(User $user): void
    {
        if (!$user->getPlainPassword()) {
            return;
        }

        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $user->getPlainPassword()
        );

        $user->setPassword($hashedPassword);
    }
}
