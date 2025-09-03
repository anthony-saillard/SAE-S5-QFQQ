<?php

namespace App\Command;

use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'create-user',
    description: 'Create user',
)]
class CreateUserCommand extends Command
{
    protected static $defaultName = 'app:create-user';

    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    private ValidatorInterface $validator;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->validator = $validator;
    }

    protected function configure()
    {
        $this->setDescription('Crée un nouvel utilisateur interactivement.');
    }

    private function sanitizeInput(?string $input): ?string
    {
        if ($input === null || $input === '') {
            return null;
        }

        if (!mb_check_encoding($input, 'UTF-8')) {
            $input = mb_convert_encoding($input, 'UTF-8');
        }

        return preg_replace('/[^\p{L}\p{N}\s\-\_\.\@]/u', '', $input);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $login = $this->sanitizeInput($helper->ask($input, $output, new Question('Entrez le login : ')));
        $password = $helper->ask($input, $output, new Question('Entrez le mot de passe : '));
        $firstName = $this->sanitizeInput($helper->ask($input, $output, new Question('Entrez le prénom (optionnel) : ')));
        $lastName = $this->sanitizeInput($helper->ask($input, $output, new Question('Entrez le nom de famille (optionnel) : ')));
        $email = $this->sanitizeInput($helper->ask($input, $output, new Question('Entrez l\'email (optionnel) : ')));
        $role = $this->sanitizeInput($helper->ask($input, $output, new Question('Entrez le rôle (ROLE_USER par défault) : ')));

        if (empty($login)) {
            $output->writeln('Le login est obligatoire');
            return Command::FAILURE;
        }

        if (empty($password)) {
            $output->writeln('Le mot de passe est obligatoire');
            return Command::FAILURE;
        }

        $user = new Users();
        $user->setLogin($login);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setDisable(false);

        if (!empty($role)) {
            $user->setRole($role);
        } else {
            $user->setRole('ROLE_USER');
        }

        if (!empty($firstName)) {
            $user->setFirstName($firstName);
        }

        if (!empty($lastName)) {
            $user->setLastName($lastName);
        }

        if (!empty($email)) {
            $user->setEmail($email);
        }

        try {
            $errors = $this->validator->validate($user);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $output->writeln('Erreur de validation : ' . $error->getMessage());
                }
                return Command::FAILURE;
            }

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $output->writeln('Utilisateur créé avec succès !');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('Erreur lors de la création de l\'utilisateur : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}