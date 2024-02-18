<?php

namespace App\Command;

use App\Services\Api\ApiJikanService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'api:jikan',
    description: 'Command to fetch Jikan\'s REST API',
)]
class ApiJikanCommand extends Command
{
    public function __construct(
        private readonly ApiJikanService $apiJikanService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addUsage('--top-mangas')
            ->addUsage('--latest-mangas')
            ->addOption('top-mangas', null, InputOption::VALUE_NONE, 'Fetch the 25 top mangas.')
            ->addOption('latest-mangas', null, InputOption::VALUE_NONE, 'Fetch the 25 latest mangas.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $topMangasOption = $input->getOption('top-mangas');
        $latestMangasOption = $input->getOption('latest-mangas');

        if (!$topMangasOption && !$latestMangasOption) {
            $io->warning('Empty options');

            return Command::SUCCESS;
        }

        // Fetch 25 top mangas
        if ($topMangasOption) {
            $topMangas = $this->apiJikanService->fetchTopManga(25);
            foreach ($topMangas as $manga) {
                $this->apiJikanService->saveMangaDatasInDb($manga);
            }
            $io->success('Top 25 mangas collection');
        }

        // Fetch 25 latest mangas
        if ($latestMangasOption) {
            $latestMangas = $this->apiJikanService->fetchLastestManga(25);
            foreach ($latestMangas as $manga) {
                $this->apiJikanService->saveMangaDatasInDb($manga);
            }
            $io->success('Latest mangas');
        }

        $io->success('Command executed with success !');

        return Command::SUCCESS;
    }
}
