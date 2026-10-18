<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Tests\Traits\PurgeTestDataTrait;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class CreateUserCommandTest extends KernelTestCase implements CommandTestInterface
{
    use PurgeTestDataTrait;

    /** @var list<string> */
    private array $createdEmails = [];

    protected function tearDown(): void
    {
        $this->purgeUsersAndInvitationsByEmail($this->createdEmails);
        $this->createdEmails = [];
        parent::tearDown();
    }

    public function testSuccessfulExecution(): void
    {
        $kernel = self::bootKernel();
        $application = new Application(self::$kernel);
        $command = $application->find('app:create-user');
        $commandTester = new CommandTester($command);

        $email = sprintf('test_%s@test.fr', bin2hex(random_bytes(6)));
        $this->createdEmails[] = $email;
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

    public function testEmailAlreadyExistsIgnoresCase(): void
    {
        self::bootKernel();

        $application = new Application(self::$kernel);
        $command = $application->find('app:create-user');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'email' => strtoupper(self::USER_DEFAULT),
            'password' => 'password',
            'username' => 'Tester',
            'isAdmin' => 'no',
        ]);

        $this->assertNotEquals(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('already exists', $commandTester->getDisplay());
    }
}
