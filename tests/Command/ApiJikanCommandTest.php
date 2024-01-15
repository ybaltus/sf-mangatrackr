<?php

namespace App\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class ApiJikanCommandTest extends KernelTestCase implements CommandTestInterface
{
    public function testSucessfulExecution(): void
    {
        $kernel = self::bootKernel();
        $application = new Application(self::$kernel);
        $command = $application->find('api:jikan');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'searchTerm' => 'One piece',
        ]);

        $commandTester->assertCommandIsSuccessful();

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('success', $output);
    }

    public function testExecutionFailed(): void
    {
        $kernel = self::bootKernel();
        $application = new Application(self::$kernel);

        $command = $application->find('api:jikan');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'searchTerm' => '',
        ]);

        $this->assertNotEquals(0, $commandTester->getStatusCode());
    }

    public function testEmptyResult(): void
    {
        $kernel = self::bootKernel();

        $application = new Application(self::$kernel);
        $command = $application->find('api:jikan');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'searchTerm' => 'c56d9d',
        ]);

        $commandTester->assertCommandIsSuccessful();

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('No', $output);
    }
}
