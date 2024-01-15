<?php

namespace App\DataFixtures;

use App\Entity\MangaType;
use Doctrine\Persistence\ObjectManager;

class MangaTypeFixtures extends DataFixturesAbstract
{
    public const LIST_MTYPES = [
        'Shonen',
        'Shojo',
        'Seinen',
        'Josei ',
        'Yaoi',
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::LIST_MTYPES as $key => $name) {
            $entity = (new MangaType())
            ->setName($name)
            ;
            $manager->persist($entity);
            $this->saveReferences($entity, $key, 'mType');
        }
        $manager->flush();
    }
}
