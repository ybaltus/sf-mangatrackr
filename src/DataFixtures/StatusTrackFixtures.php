<?php

namespace App\DataFixtures;

use App\Entity\StatusTrack;
use Doctrine\Persistence\ObjectManager;

class StatusTrackFixtures extends DataFixturesAbstract
{
    public const LIST_STRACKS = [
        'En lecture',
        'En pause',
        'Arrété',
        'Archivé',
        'Plus tard',
        'Pas commencé',
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::LIST_STRACKS as $key => $name) {
            $entity = (new StatusTrack())
                ->setName($name)
            ;
            $manager->persist($entity);
            $this->saveReferences($entity, $key, 'sTrack');
        }
        $manager->flush();
    }
}
