<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;

abstract class DataFixturesAbstract extends Fixture implements ReferenceFixturesInterface
{
    public function saveReferences(object $entity, int $key, string $prefixRef): void
    {
        $targetReference = match ($key) {
            0 => sprintf('%s_ref_%d', $prefixRef, 1),
            1 => sprintf('%s_ref_%d', $prefixRef, 2),
            2 => sprintf('%s_ref_%d', $prefixRef, 3),
            default => false
        };

        if (!$targetReference) {
            return;
        }

        $this->addReference($targetReference, $entity);
    }
}
