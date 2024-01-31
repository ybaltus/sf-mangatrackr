<?php

namespace App\DataFixtures;

interface ReferenceFixturesInterface
{
    public function saveReferences(object $entity, int $key, string $prefixRef): void;
}
