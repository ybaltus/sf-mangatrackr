<?php

namespace App\DataFixtures;

use App\Entity\StatusTrack;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class StatusTrackFixtures extends Fixture
{
    public const REFOBJ1 = 'sTrack_1';
    public const REFOBJ2 = 'sTrack_2';
    public const REFOBJ3 = 'sTrack_3';

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
        foreach (self::LIST_STRACKS as $key => $name)
        {
            $entity= (new StatusTrack())
                ->setName($name)
            ;
            $manager->persist($entity);
            $this->saveReferences($entity, $key);
        }
        $manager->flush();
    }

    private function saveReferences(object $entity, int $key):void
    {
        $targetReference = match($key){
            0 => self::REFOBJ1,
            1 => self::REFOBJ2,
            2 => self::REFOBJ3,
            default => false
        };

        if(!$targetReference)
        {
            return;
        }

        $this->addReference($targetReference, $entity);
    }
}
