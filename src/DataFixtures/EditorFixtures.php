<?php

namespace App\DataFixtures;

use App\Entity\Editor;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class EditorFixtures extends Fixture
{
    public const REFOBJ1 = 'editor_1';
    public const REFOBJ2 = 'editor_2';
    public const REFOBJ3 = 'editor_3';

    public const LIST_EDITORS = [
        'Shūeisha',
        'Glénat',
        'Ki-oon',
        'ANKAMA ',
        'KAZÉ',
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::LIST_EDITORS as $key => $editorName) {
            $entity = (new Editor())
                ->setName($editorName)
            ;
            $manager->persist($entity);
            $this->saveReferences($entity, $key);
        }
        $manager->flush();
    }

    private function saveReferences(object $entity, int $key): void
    {
        $targetReference = match ($key) {
            0 => self::REFOBJ1,
            1 => self::REFOBJ2,
            2 => self::REFOBJ3,
            default => false
        };

        if (!$targetReference) {
            return;
        }

        $this->addReference($targetReference, $entity);
    }
}
