<?php

namespace App\Tests\Entity;

use App\Entity\Manga;
use App\Tests\Traits\MyUnitTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MangaTest extends KernelTestCase implements EntityTestInterface
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
        return (new Manga())
            ->setTitle($title)
            ->setTitleSlug($title.'_slug')
            ->setAuthor('je suis auteur')
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
        $entity->setAuthor('');
        $assertResults = $this->assertViolationsWithValidator($validatorService, $entity);
        $this->assertCount(4, $assertResults[0], $assertResults[1]);
    }
}
