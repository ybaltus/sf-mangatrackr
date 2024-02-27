<?php

namespace App\Command;

use App\Services\Api\ApiMangaUpdatesService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'api:test-manga-updates',
    description: 'Command to test Manga UpdatesREST API',
)]
class TestApiMangaUpdatesCommand extends Command
{
    public function __construct(
        private ApiMangaUpdatesService $apiMangaUpdatesService
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
            $datas = $this->apiMangaUpdatesService->fetchMangaByTitle($searchTerm);

            if (empty($datas)) {
                $io->info('No results found');

                return Command::SUCCESS;
            }
            // Test save datas only for the first entry
            $this->apiMangaUpdatesService->saveMangaDatasInDb($datas[0]);
        }

        $io->success('api:test-manga-updates command executed with success !');

        return Command::SUCCESS;
    }
}
