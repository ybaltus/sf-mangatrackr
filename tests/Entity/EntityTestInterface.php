<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use Symfony\Component\DependencyInjection\ContainerInterface;

interface EntityTestInterface
{
    /**
     * BootKernel Symfony and retrieve the container service.
     */
    public function initBootKernelContainer(): ContainerInterface;

    /**
     * Init a new entity.
     */
    public function getEntity(string $title): object;

    /**
     * Check an valid entity.
     */
    public function testEntityIsValid(): void;

    /**
     * Check an invalid entity.
     */
    public function testEntityIsInvalid(): void;
}
