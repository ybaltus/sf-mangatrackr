<?php

namespace App\Tests\Entity;

use App\Entity\Manga;
use App\Entity\MangaMangaUpdatesAPI;
use App\Tests\Traits\AppTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MangaMangaUpdatesAPITest extends KernelTestCase implements EntityTestInterface
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

        return (new MangaMangaUpdatesAPI())
            ->setManga($manga)
            ->setMuSeriesId("12345")
            ->setMuUrl('https://www.mangaupdates.com/')
            ->setMuImgJpg('https://www.mangaupdates.com/')
            ->setMuThumbJpg('https://www.mangaupdates.com/')
            ->setMuYear(2024)
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
        $entity->setMuUrl('mangaupdates.com');
        $entity->setMuImgJpg('mangaupdates.com');
        $entity->setMuThumbJpg('mangaupdates.com');

        $assertResults = $this->assertViolationsWithValidator($validatorService, $entity);
        $this->assertCount(3, $assertResults[0], $assertResults[1]);
    }
}
