<?php

namespace App\EventListener;

use App\Entity\User;
use App\Entity\UserNews;
use App\Entity\UserTrackList;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::prePersist, entity: User::class)]
class UserEntityListener
{
    public function prePersist(User $user, PrePersistEventArgs $args): void
    {
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
}
