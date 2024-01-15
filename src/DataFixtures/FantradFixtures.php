<?php

namespace App\DataFixtures;

use App\Entity\Fantrad;
use Doctrine\Persistence\ObjectManager;

class FantradFixtures extends DataFixturesAbstract
{
    //    public const REFOBJ1 = 'fantrad_1';
    //    public const REFOBJ2 = 'fantrad_2';
    //    public const REFOBJ3 = 'fantrad_3';

    public const LIST_FANTRADS = [
        'Fantrad',
        'Manga scan',
        'FmTeam',
        'Otaku ',
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::LIST_FANTRADS as $key => $name) {
            $entity = (new Fantrad())
                ->setName($name)
                ->setUrl('https://www.manga-news.com/')
            ;
            $manager->persist($entity);
            $this->saveReferences($entity, $key, 'fantrad');
        }
        $manager->flush();
    }

    //    private function saveReferences(object $entity, int $key): void
    //    {
    //        $targetReference = match ($key) {
    //            0 => self::REFOBJ1,
    //            1 => self::REFOBJ2,
    //            2 => self::REFOBJ3,
    //            default => false
    //        };
    //
    //        if (!$targetReference) {
    //            return;
    //        }
    //
    //        $this->addReference($targetReference, $entity);
    //    }
}
