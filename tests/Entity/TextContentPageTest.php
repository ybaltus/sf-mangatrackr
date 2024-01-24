<?php

namespace App\Tests\Entity;

use App\Entity\TextContentPage;
use App\Tests\Traits\AppTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TextContentPageTest extends KernelTestCase implements EntityTestInterface
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
        return (new TextContentPage())
            ->setName($title)
            ->setNameSlug($title . '_sl')
            ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit,
             sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
              Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip
              ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit
               esse cillum dolore eu fugiat nulla pariatur. ')
        ;
    }

    public function testEntityIsValid(): void
    {
        $validatorService = $this->initBootKernelContainer()->get('validator');
        $editor = $this->getEntity('EditorValid');
        $assertResults = $this->assertViolationsWithValidator($validatorService, $editor);
        $this->assertCount(0, $assertResults[0], $assertResults[1]);
    }

    public function testEntityIsInvalid(): void
    {
        $validatorService = $this->initBootKernelContainer()->get('validator');
        $editor = $this->getEntity('');
        $assertResults = $this->assertViolationsWithValidator($validatorService, $editor);
        $this->assertCount(2, $assertResults[0], $assertResults[1]);
    }
}
