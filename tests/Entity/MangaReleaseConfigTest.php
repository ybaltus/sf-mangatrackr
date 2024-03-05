<?php

namespace App\Tests\Entity;

use App\Entity\Manga;
use App\Entity\MangaReleaseConfig;
use App\Tests\Traits\AppTestTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class MangaReleaseConfigTest extends KernelTestCase implements EntityTestInterface
{
    use AppTestTrait;

    private Manga $manga;

    public function initBootKernelContainer(): ContainerInterface
    {
        // boot the Symfony kernel
        self::bootKernel();

        // use static::getContainer() to access the service container
        return static::getContainer();
    }

    public function getEntity(string $title): object
    {
        return (new MangaReleaseConfig())
            ->setManga($this->manga)
        ;
    }

    /**
     * @before
     */
    public function testEntityIsValid(): void
    {
        $container = $this->initBootKernelContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        /**
         * @var ValidatorInterface $validatorService
         */
        $validatorService = $container->get('validator');

        $this->manga = $entityManager->getRepository(Manga::class)->findOneBy(['isActivated' => true]);
        $entity = $this->getEntity('EntityValid');
        $assertResults = $this->assertViolationsWithValidator($validatorService, $entity);
        $this->assertCount(0, $assertResults[0], $assertResults[1]);
    }

    public function testEntityIsInvalid(): void
    {
        // There are no constraints
        $this->assertTrue(true);
    }
}
