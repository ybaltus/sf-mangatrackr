<?php

namespace App\Tests\Entity;

use Symfony\Component\DependencyInjection\ContainerInterface;

interface EntityTestInterface
{
    /**
     * BootKernel Symfony and retrieve the container service
     * @return ContainerInterface
     */
    public function initBootKernelContainer(): ContainerInterface;

    /**
     * Init a new entity
     * @param string $title
     * @return object
     */
    public function getEntity(string $title): object;

    /**
     * Check an valid entity
     * @return void
     */
    public function testEntityIsValid(): void;

    /**
     * Check an invalid entity
     * @return void
     */
    public function testEntityIsInvalid(): void;

}