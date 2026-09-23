<?php

namespace App\Command;

use App\Services\Api\ApiJikanService;
use App\Services\Command\InitDataService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:init-datas',
    description: 'Initialize the datas for the production environment.',
)]
class InitDataCommand extends Command
{
    public function __construct(
        private InitDataService $initDataService,
        private ApiJikanService $apiJikanService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            // Initialize some MangaTypes, MangaStatus, Fantrad and StatusTrack
            $this->initDataService->initAllDatas();

            // Initialize some mangas for the homepage
            $topManga = $this->apiJikanService->fetchTopManga(25);
            if ($topManga) {
                foreach ($topManga as $manga) {
                    $this->apiJikanService->saveMangaDatasInDb($manga);
                }
            }

            $latestManga = $this->apiJikanService->fetchLastestManga(25);
            if ($latestManga) {
                foreach ($latestManga as $manga) {
                    $this->apiJikanService->saveMangaDatasInDb($manga);
                }
            }
            if (!$latestManga || !$topManga) {
                throw new \Exception('Error initDataCommand: No data fetched from API');
            }

            $io->success('app:init-datas executed with success !');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error(sprintf('Error initDataCommand: %s', $e->getMessage()));

            return Command::FAILURE;
        }
    }
}
