<?php

namespace App\Tests\Entity;

use App\Entity\Manga;
use App\Entity\MangaJikanAPI;
use App\Tests\Traits\MyUnitTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MangaJikanAPITest extends KernelTestCase implements EntityTestInterface
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
        $manga = (new Manga())
            ->setTitle($title)
            ->setTitleSlug($title.'_slug')
            ->setAuthor('je suis auteur')
                ;

        return (new MangaJikanAPI())
            ->setManga($manga)
            ->setMalId(25)
            ->setMalImgJpg('https://jikan.moe/')
            ->setMalImgJpgLarge('https://jikan.moe/')
            ->setMalImgWebp('https://jikan.moe/')
            ->setMalImgWebpLarge('https://jikan.moe/')
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
        $entity->setMalImgJpg('jikan.moe');
        $entity->setMalImgJpgLarge('jikan.moe/');
        $entity->setMalImgWebp('jikan.moe/');
        $entity->setMalImgWebpLarge('jikan.moe/');

        $assertResults = $this->assertViolationsWithValidator($validatorService, $entity);
        $this->assertCount(4, $assertResults[0], $assertResults[1]);

    }
}
