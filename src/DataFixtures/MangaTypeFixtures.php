<?php

namespace App\DataFixtures;

use App\Entity\MangaType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class MangaTypeFixtures extends Fixture
{
    public const REFOBJ1 = 'mType_1';
    public const REFOBJ2 = 'mType_2';
    public const REFOBJ3 = 'mType_3';

    public const LIST_MTYPES = [
        'Shonen',
        'Shojo',
        'Seinen',
        'Josei ',
        'Yaoi'
    ];

    public function load(ObjectManager $manager): void
    {
       foreach (self::LIST_MTYPES as $key => $name)
       {
           $entity= (new MangaType())
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
