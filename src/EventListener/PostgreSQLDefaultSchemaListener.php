<?php

declare(strict_types=1);

namespace App\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\ORM\Tools\ToolEvents;

/**
 * Déclare le schéma "public" dans le schéma généré depuis le mapping.
 *
 * Sans cela, DBAL 3 voit "public" dans la base mais pas dans le mapping, et chaque
 * doctrine:migrations:diff produit une migration parasite (CREATE SCHEMA public dans down()).
 * Voir ADR 0012 et le rapport 010 du chantier migration-postgresql.
 */
#[AsDoctrineListener(event: ToolEvents::postGenerateSchema)]
final class PostgreSQLDefaultSchemaListener
{
    public function postGenerateSchema(GenerateSchemaEventArgs $args): void
    {
        $platform = $args->getEntityManager()->getConnection()->getDatabasePlatform();
        if (!$platform instanceof PostgreSQLPlatform) {
            return;
        }

        $schema = $args->getSchema();
        if (!$schema->hasNamespace('public')) {
            $schema->createNamespace('public');
        }
    }
}
