<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
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
    name: 'app:create-user',
    description: 'Create a new user',
)]
class CreateUserCommand extends Command
{
    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct('CreateNewUser');
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, 'Email')
            ->addArgument('password', InputArgument::OPTIONAL, 'Password')
            ->addArgument('username', InputArgument::OPTIONAL, 'Username')
            ->addArgument('isAdmin', InputArgument::OPTIONAL, 'If administrator')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /**
         * @var HelperInterface|mixed $helper
         */
        $helper = $this->getHelper('question');
        $io = new SymfonyStyle($input, $output);

        // Email Helper
        $email = $input->getArgument('email');
        if (!$email) {
            $questionEmail = new Question('What is the user\'s email address ? : ');
            $email = $helper->ask($input, $output, $questionEmail);
        }

        // Password Helper
        $password = $input->getArgument('password');
        if (!$password) {
            $questionPassword = new Question('What is the user\'s password ? : ');
            $password = $helper->ask($input, $output, $questionPassword);
        }

        // Username Helper
        $username = $input->getArgument('username');
        if (!$username) {
            $questionUsername = new Question(
                'What is the username ? : ',
                true
            );
            $username = $helper->ask($input, $output, $questionUsername);
        }

        // Roles Helper
        $isAdmin = $input->getArgument('isAdmin');
        if (!$isAdmin) {
            $questionChoiceIsAdmin = new ChoiceQuestion(
                'Is he an administrator ? :',
                ['yes', 'no'],
                'yes'
            );
            $repAsk = $helper->ask($input, $output, $questionChoiceIsAdmin);
            if (str_contains($repAsk, 'yes')) {
                $isAdmin = true;
            } else {
                $isAdmin = false;
            }
        }

        // Save user
        if (!$email || !$password || !$username) {
            $io->error('Email, username and password are required.');

            return Command::FAILURE;
        } else {
            // Check if user already exists
            if ($this->em->getRepository(User::class)->findOneByEmail($email)) {
                $io->error('This email already exists.');
                return Command::FAILURE;
            }

            // Create a new user
            $user = (new User())
                ->setEmail($email)
                ->setPlainPassword($password)
                ->setUsername($username)
            ;

            if ($isAdmin) {
                $user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
            }

            $this->em->persist($user);
            $this->em->flush();
        }

        $io->success("L'utilisateur {$email} à bien été créé !");

        return Command::SUCCESS;
    }
}
