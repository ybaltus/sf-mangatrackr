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
use Symfony\Component\Console\Question\Question;
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
            ->addUsage('release')
            ->addUsage('search or search myTerm')
            ->addArgument('mode', InputArgument::OPTIONAL, 'Mode release or search mangas')
            ->addArgument('searchTerm', InputArgument::OPTIONAL, 'Term for search mode')
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
        $searchTerm = $input->getArgument('searchTerm');

        // Select a mode
        $choiceMode = ['release', 'search'];
        if (!$mode || !in_array($mode, $choiceMode)) {
            $question = new ChoiceQuestion(
                'Please select a mode (defaults to calendar)',
                $choiceMode,
                0
            );
            $question->setErrorMessage('Color %s is invalid.');

            $mode = $helper->ask($input, $output, $question);
        }
        $io->info(sprintf('You choose the \'%s\' mode.', $mode));

        // Check if search mode
        if (0 === strcmp($mode, 'search')) {
            if (!$searchTerm) {
                $questionSearch = new Question('Enter a search term (Ex. one piece): ');
                $searchTerm = $helper->ask($input, $output, $questionSearch);
            }
        }

        // Save datas by mode
        switch ($mode) {
            case 'release':
                $results = $this->calendarResults();
                break;
            case 'search':
                $results = 'ok';
                break;
        }

        return Command::SUCCESS;
    }

    /**
     * @return array<string>
     */
    private function calendarResults(): array
    {
        $releases = $this->apiMangaUpdatesService->fetchMangaReleases();
        $releaseMangas = [];
        foreach ($releases as $result) {
            $entity = $this->apiMangaUpdatesService->saveReleaseDataInDb($result['record']);
            if ($entity) {
                $releaseMangas[] = $entity->getManga()->getTitle();
            }
        }

        return $releaseMangas;
    }
}
