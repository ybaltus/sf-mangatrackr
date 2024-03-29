<?php

namespace App\Tests\Entity;

use App\Entity\User;
use App\Tests\Traits\AppTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UserTest extends KernelTestCase implements EntityTestInterface
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
        return (new User())
            ->setEmail($title)
            ->setUsername($title.'_username')
            ->setPlainPassword('password')
            ->setPassword('password')
            ;
    }

    public function testEntityIsValid(): void
    {
        $validatorService = $this->initBootKernelContainer()->get('validator');
        $entity = $this->getEntity('user@email.fr');
        $assertResults = $this->assertViolationsWithValidator($validatorService, $entity);
        $this->assertCount(0, $assertResults[0], $assertResults[1]);
    }

    public function testEntityIsInvalid(): void
    {
        $validatorService = $this->initBootKernelContainer()->get('validator');
        $entity = $this->getEntity('useremail.fr');
        $assertResults = $this->assertViolationsWithValidator($validatorService, $entity);
        $this->assertCount(1, $assertResults[0], $assertResults[1]);
    }
}
