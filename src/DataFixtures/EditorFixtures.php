<?php

namespace App\DataFixtures;

use App\Entity\Editor;
use Doctrine\Persistence\ObjectManager;

final class EditorFixtures extends DataFixturesAbstract
{
    public const LIST_EDITORS = [
        'Shūeisha',
        'Glénat',
        'Ki-oon',
        'ANKAMA',
        'KAZÉ',
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::LIST_EDITORS as $key => $editorName) {
            $entity = (new Editor())
                ->setName($editorName)
            ;
            $manager->persist($entity);
            $this->saveReferences($entity, $key, 'editor');
        }
        $manager->flush();
    }
}
