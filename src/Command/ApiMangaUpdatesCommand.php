<?php

namespace App\Command;

use App\Services\Api\ApiMangaUpdatesService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\HelperInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'api:manga-updates',
    description: 'Command to fetch MangaUpdates\'s REST API',
)]
class ApiMangaUpdatesCommand extends Command
{
    public function __construct(
        private readonly ApiMangaUpdatesService $apiMangaUpdatesService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addUsage('calendar')
            ->addUsage('xxx')
            ->addArgument('mode', InputArgument::OPTIONAL, 'Select a mode')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        /**
         * @var HelperInterface|mixed $helper
         */
        $helper = $this->getHelper('question');
        $mode = $input->getArgument('mode');

        // Select a mode
        $choiceMode = ['calendar', 'xxx'];
        if (!$mode || !in_array($mode, $choiceMode)) {
            $question = new ChoiceQuestion(
                'Please select a mode (defaults to calendar)',
                $choiceMode,
                0
            );
            $question->setErrorMessage('Mode %s is invalid.');
            $mode = $helper->ask($input, $output, $question);
        }
        $io->info(sprintf('You choose the \'%s\' mode.', $mode));

        // Data management by mode
        switch ($mode) {
            case 'calendar':
                $results = $this->releaseResults();
                $io->comment(sprintf('%d releases processed.', count($results)));
                break;
            case 'xxx':
                $io->comment('You have to configure an other mode.');
                break;
        }

        $io->success('Command executed with success !');

        return Command::SUCCESS;
    }

    /**
     * @return array<string>
     */
    private function releaseResults(): array
    {
        $releases = $this->apiMangaUpdatesService->fetchMangaReleases();
        $releaseMangas = [];
        foreach ($releases as $result) {
            $entity = $this->apiMangaUpdatesService->saveReleaseDataInDb($result);
            if ($entity) {
                $releaseMangas[] = $entity->getManga()->getTitle().' / '.$entity->getManga()->getNbChapters();
            }
        }

        return $releaseMangas;
    }
}
