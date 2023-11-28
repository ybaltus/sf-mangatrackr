<?php

namespace App\Command;

use App\Services\Api\ApiJikanService;
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

            // Test fetch a manga by title
            $datas = $this->apiJikanService->fetchMangaByTitle($searchTerm);

            if (empty($datas)) {
                $io->info('No results');
            }

            // Test save datas only for the first entry
            $this->apiJikanService->saveMangaDatasInDb($datas[0]);

            // Test fetch top mangas
            $topManga = $this->apiJikanService->fetchTopManga();
        } else {
            $io->error('A search terms is required');

            return Command::INVALID;
        }

        $io->success('api:jikan command executed with success !');

        return Command::SUCCESS;
    }
}
