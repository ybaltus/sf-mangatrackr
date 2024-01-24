<?php

namespace App\Tests\Entity;

use App\Entity\Fantrad;
use App\Tests\Traits\AppTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FantradTest extends KernelTestCase implements EntityTestInterface
{
    use AppTestTrait;

    public function initBootKernelContainer(): ContainerInterface
    {
        // boot the Symfony kernel
        self::bootKernel();

        // use static::getContainer() to access the service container
        return static::getContainer();
    }

    public function getEntity(string $title): object
    {
        return (new Fantrad())
            ->setName($title)
            ->setNameSlug($title.'_slug')
            ->setUrl('https://fantrad.com')
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

    public function testUrlIsInvalid():void
    {
        $validatorService = $this->initBootKernelContainer()->get('validator');
        $entity = $this->getEntity('FantradValid');
        $entity->setUrl('f');
        $assertResults = $this->assertViolationsWithValidator($validatorService, $entity);
        $this->assertCount(2, $assertResults[0], $assertResults[1]);

    }
}
