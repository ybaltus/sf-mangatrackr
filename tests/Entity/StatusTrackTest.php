<?php

namespace App\Tests\Entity;

use App\Entity\StatusTrack;
use App\Tests\Traits\MyUnitTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class StatusTrackTest extends KernelTestCase implements EntityTestInterface
{
    use MyUnitTestTrait;

    public function initBootKernelContainer(): ContainerInterface
    {
        // boot the Symfony kernel
        self::bootKernel();

        // use static::getContainer() to access the service container
        return static::getContainer();
    }

    public function getEntity(string $title): object
    {
        return (new StatusTrack())
            ->setName($title)
            ->setNameSlug($title.'_slug')
            ;
    }

    public function testEntityIsValid(): void
    {
        $validatorService = $this->initBootKernelContainer()->get('validator');
        $entity = $this->getEntity('EntityValid');
        $assertResults = $this->assertViolationsWithValidator($validatorService, $entity);
        $this->assertCount(0, $assertResults[0], $assertResults[1]);
    }

    public function testEntityIsInvalid(): void
    {
        $validatorService = $this->initBootKernelContainer()->get('validator');
        $entity = $this->getEntity('');
        $assertResults = $this->assertViolationsWithValidator($validatorService, $entity);
        $this->assertCount(2, $assertResults[0], $assertResults[1]);
    }
}
