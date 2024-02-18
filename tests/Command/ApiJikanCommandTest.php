<?php

namespace App\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class ApiJikanCommandTest extends KernelTestCase implements CommandTestInterface
{
    public function testSuccessfulExecution(): void
    {
        $kernel = self::bootKernel();
        $application = new Application(self::$kernel);
        $command = $application->find('api:jikan');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                '--top-mangas' => 1,
            ]
        );

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
        ]);

        $this->assertNotEquals(2, $commandTester->getStatusCode());
    }

    public function testLatestMangaOption(): void
    {
        $kernel = self::bootKernel();
        $application = new Application(self::$kernel);
        $command = $application->find('api:jikan');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                '--latest-mangas' => 1,
            ]
        );

        $commandTester->assertCommandIsSuccessful();

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Latest', $output);
    }
}
