<?php

namespace App\Command;

use App\Services\Api\ApiTenraiService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/** Console command for fetching manga data from Tenrai. */
#[AsCommand(
    name: 'api:tenrai',
    description: 'Command to fetch Tenrai REST API',
)]
class ApiTenraiCommand extends Command
{
    public function __construct(
        private readonly ApiTenraiService $apiTenraiService
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
            $topMangas = $this->apiTenraiService->fetchTopManga(25);
            foreach ($topMangas as $manga) {
                $this->apiTenraiService->saveMangaDatasInDb($manga);
            }
            $io->success('Top 25 mangas collection');
        }

        // Fetch 25 latest mangas
        if ($latestMangasOption) {
            $latestMangas = $this->apiTenraiService->fetchLastestManga(25);
            foreach ($latestMangas as $manga) {
                $this->apiTenraiService->saveMangaDatasInDb($manga);
            }
            $io->success('Latest mangas');
        }

        $io->success('Command executed with success !');

        return Command::SUCCESS;
    }
}
