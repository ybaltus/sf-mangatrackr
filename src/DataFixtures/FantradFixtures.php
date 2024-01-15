<?php

namespace App\DataFixtures;

use App\Entity\Fantrad;
use Doctrine\Persistence\ObjectManager;

class FantradFixtures extends DataFixturesAbstract
{
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
}
