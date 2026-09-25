<?php

namespace App\Command;

use App\Services\Api\ApiTenraiService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/** Console command for testing the Tenrai API. */
#[AsCommand(
    name: 'api:test-tenrai',
    description: 'Command to test Tenrai REST API',
)]
class TestApiTenraiCommand extends Command
{
    public function __construct(
        private ApiTenraiService $apiTenraiService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('searchTerm', InputArgument::REQUIRED, 'Search term with the Tenrai API')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $searchTerm = $input->getArgument('searchTerm');

        if ($searchTerm) {
            $io->note(sprintf('You passed an argument: %s', $searchTerm));

            // Test fetch a manga by title
            $datas = $this->apiTenraiService->fetchMangaByTitle($searchTerm);

            if (empty($datas)) {
                $io->info('No results found');

                return Command::SUCCESS;
            }

            // Test save datas only for the first entry
            $this->apiTenraiService->saveMangaDatasInDb($datas[0]);

        // Test fetch top mangas
        // $topManga = $this->apiTenraiService->fetchTopManga();
        } else {
            $io->error('Error: A search terms is required');

            return Command::INVALID;
        }

        $io->success('api:test-tenrai command executed with success !');

        return Command::SUCCESS;
    }
}
