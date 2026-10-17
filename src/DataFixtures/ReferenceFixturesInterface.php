<?php

declare(strict_types=1);

namespace App\DataFixtures;

interface ReferenceFixturesInterface
{
    public function saveReferences(object $entity, int $key, string $prefixRef): void;
}
