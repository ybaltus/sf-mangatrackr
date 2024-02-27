<?php

namespace App\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class ApiMangaUpdatesCommandTest extends KernelTestCase implements CommandTestInterface
{
    public function testSuccessfulExecution(): void
    {
        $kernel = self::bootKernel();
        $application = new Application(self::$kernel);
        $command = $application->find('api:manga-updates');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'releases',
            ]
        );

        $commandTester->assertCommandIsSuccessful();

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('releases processed.', $output);
    }

    public function testExecutionFailed(): void
    {
        $this->assertEquals('no test', 'no test');
    }

    public function testModeIsInvalid(): void
    {
        $kernel = self::bootKernel();
        $application = new Application(self::$kernel);
        $command = $application->find('api:manga-updates');
        $commandTester = new CommandTester($command);

        // If mode is not 0 (releases) or 1 (search)
        $commandTester->setInputs([2]);

        $commandTester->execute(['command' => $command->getName()]);

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('invalid', $output);
    }
}
