<?php

namespace App\DataFixtures;

use App\Entity\MangaStatus;
use Doctrine\Persistence\ObjectManager;

class MangaStatusFixtures extends DataFixturesAbstract
{
    public const LIST_MTSTATUS = [
        'En cours',
        'En pause',
        'Terminée',
        'Abandonnée ',
        'A venir ',
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::LIST_MTSTATUS as $key => $name) {
            $entity = (new MangaStatus())
            ->setTitle($name)
            ;
            $manager->persist($entity);
            $this->saveReferences($entity, $key, 'mStatus');
        }
        $manager->flush();
    }
}
