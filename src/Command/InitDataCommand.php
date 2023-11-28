<?php

namespace App\Command;

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
        private InitDataService $initDataService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->initDataService->initAllDatas();

        $io->success('app:init-datas executed with success');

        return Command::SUCCESS;
    }
}
