<?php

namespace App\Command;

use App\Services\Common\MailService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mime\Address;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:test-email',
    description: 'Test sending email',
)]
class TestEmailCommand extends Command
{
    public function __construct(
        private MailService $mailService,
        private ValidatorInterface $validator
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('emailFrom', InputArgument::REQUIRED, 'Email to send')
            ->addArgument('emailTo', InputArgument::REQUIRED, 'Email to send')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $emailTo = $input->getArgument('emailTo');
        $emailFrom = $input->getArgument('emailFrom');

        // Test if the argument is an email
        $errorsFrom = $this->validator->validate($emailFrom, [
            new Assert\Email(),
        ]);

        $errorsTo = $this->validator->validate($emailTo, [
            new Assert\Email(),
        ]);

        if (count($errorsFrom) > 0 || count($errorsTo) > 0) {
            $io->error('Email is not valid.');

            return Command::FAILURE;
        }

        if ($emailFrom && $emailTo) {
            $result = $this->mailService->sendEmail(
                new Address($emailFrom, 'MangaTracker'),
                $emailTo,
                'MangaTracker - Test email command',
                'emails/test.html.twig',
                ['contact' => 'Hello from MangaTracker']
            );

            $io->info($result);

            $io->success('You have a new command! Now make it your own! Pass --help to see your options.');
        }

        return Command::SUCCESS;
    }
}
