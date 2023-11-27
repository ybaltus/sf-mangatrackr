<?php

namespace App\Command;

use App\Services\ApiJikanService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'api:jikan',
    description: 'Add a short description for your command',
)]
class ApiJikanCommand extends Command
{
    public function __construct(
        private ApiJikanService $apiJikanService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('searchTerm', InputArgument::REQUIRED, 'Search term with the JIKAN API')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $searchTerm = $input->getArgument('searchTerm');

        if ($searchTerm) {
            $io->note(sprintf('You passed an argument: %s', $searchTerm));
            $datas = $this->apiJikanService->fetchMangaByTitle($searchTerm);

            if (empty($datas)) {
                $io->info('No results');
            }
            var_dump($datas[0]);
        } else {
            $io->error('A search terms is required');

            return Command::INVALID;
        }

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return Command::SUCCESS;
    }
}
