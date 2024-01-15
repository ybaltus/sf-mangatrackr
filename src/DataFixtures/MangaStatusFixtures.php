<?php

namespace App\DataFixtures;

use App\Entity\MangaStatus;
use Doctrine\Persistence\ObjectManager;

class MangaStatusFixtures extends DataFixturesAbstract
{
    //    public const REFOBJ1 = 'mStatus_1';
    //    public const REFOBJ2 = 'mStatus_2';
    //    public const REFOBJ3 = 'mStatus_3';

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
