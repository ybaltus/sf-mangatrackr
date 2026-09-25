<?php

namespace App\Tests\Entity;

use App\Entity\Manga;
use App\Entity\MangaTenraiAPI;
use App\Tests\Traits\AppTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MangaTenraiAPITest extends KernelTestCase implements EntityTestInterface
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
        $manga = (new Manga())
            ->setTitle($title)
            ->setTitleSlug($title.'_slug')
            ->setAuthor('je suis auteur')
                ;

        return (new MangaTenraiAPI())
            ->setManga($manga)
            ->setMalId(25)
            ->setMalImgJpg('https://api.tenrai.org/')
            ->setMalImgJpgLarge('https://api.tenrai.org/')
            ->setMalImgWebp('https://api.tenrai.org/')
            ->setMalImgWebpLarge('https://api.tenrai.org/')
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
        $entity->setMalImgJpg('tenrai.org');
        $entity->setMalImgJpgLarge('tenrai.org/');
        $entity->setMalImgWebp('tenrai.org/');
        $entity->setMalImgWebpLarge('tenrai.org/');

        $assertResults = $this->assertViolationsWithValidator($validatorService, $entity);
        $this->assertCount(4, $assertResults[0], $assertResults[1]);

    }
}
