<?php

namespace App\EventListener;

use App\Entity\Manga;
use App\Entity\MangaStatistic;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::prePersist, entity: Manga::class)]
class MangaEntityListener
{
    public function prePersist(Manga $manga, PrePersistEventArgs $args): void
    {
        // Add MangaStatistic
        $mangaStatistic = new MangaStatistic();
        $manga->setMangaStatistic($mangaStatistic);
    }
}
