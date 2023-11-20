<?php

namespace App\Tests\Unit\Entity;

use App\Entity\MangaStatistic;
use App\Tests\Unit\Traits\MyUnitTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MangaStatisticTest extends KernelTestCase implements EntityTestInterface
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
        return (new MangaStatistic())
            ->setNbTrack(50)
            ->setNbView(1000)
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
        $entity = $this->getEntity('EntityInvalidValid');
        $entity->setNbView(-1);
        $entity->setRating(-1);
        $entity->setNbTrack(-1);
        $assertResults = $this->assertViolationsWithValidator($validatorService, $entity);
        $this->assertCount(3, $assertResults[0], $assertResults[1]);

    }
}
