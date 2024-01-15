<?php

namespace App\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class CreateUserCommandTest extends KernelTestCase implements CommandTestInterface
{
    public function testSuccessfulExecution(): void
    {
        $kernel = self::bootKernel();
        $application = new Application(self::$kernel);
        $command = $application->find('app:create-user');
        $commandTester = new CommandTester($command);

        $email = sprintf('test_%d@test.fr', mt_rand(1, 1000));
        $commandTester->setInputs([
            'email' => $email,
            'password' => 'password',
            'username' => 'Tester',
            'isAdmin' => 'no',
        ]);

        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('success', $output);
    }

    public function testExecutionFailed(): void
    {
        $kernel = self::bootKernel();
        $application = new Application(self::$kernel);

        $command = $application->find('app:create-user');
        $commandTester = new CommandTester($command);

        // Not set email input
        $commandTester->setInputs([
            'email' => '',
            'password' => 'password',
            'username' => 'Tester',
            'isAdmin' => 'no',
        ]);
        $commandTester->execute([]);

        $this->assertNotEquals(0, $commandTester->getStatusCode());
    }

    public function testEmailAlreadyExists(): void
    {
        $kernel = self::bootKernel();

        $application = new Application(self::$kernel);
        $command = $application->find('app:create-user');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'email' => self::USER_DEFAULT,
            'password' => 'password',
            'username' => 'Tester',
            'isAdmin' => 'no',
        ]);

        $this->assertNotEquals(0, $commandTester->getStatusCode());

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('already exists', $output);
    }
}
