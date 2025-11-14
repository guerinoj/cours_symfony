<?php

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
  name: 'app:user:add-role',
  description: 'Ajoute un rôle à un utilisateur',
)]
class AddUserRoleCommand extends Command
{
  public function __construct(
    private UserRepository $userRepository,
    private EntityManagerInterface $entityManager
  ) {
    parent::__construct();
  }

  protected function configure(): void
  {
    $this
      ->addArgument('email', InputArgument::REQUIRED, 'Email de l\'utilisateur')
      ->addArgument('role', InputArgument::REQUIRED, 'Rôle à ajouter (ROLE_USER ou ROLE_ADMIN)')
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $io = new SymfonyStyle($input, $output);
    $email = $input->getArgument('email');
    $role = $input->getArgument('role');

    // Validation du rôle
    if (!in_array($role, ['ROLE_USER', 'ROLE_ADMIN'])) {
      $io->error('Le rôle doit être ROLE_USER ou ROLE_ADMIN');
      return Command::FAILURE;
    }

    // Recherche de l'utilisateur
    $user = $this->userRepository->findOneBy(['email' => $email]);

    if (!$user) {
      $io->error(sprintf('Aucun utilisateur trouvé avec l\'email : %s', $email));
      return Command::FAILURE;
    }

    // Vérification si l'utilisateur a déjà ce rôle
    if (in_array($role, $user->getRoles())) {
      $io->warning(sprintf('L\'utilisateur %s a déjà le rôle %s', $email, $role));
      return Command::SUCCESS;
    }

    // Ajout du rôle
    $roles = $user->getRoles();
    $roles[] = $role;
    $user->setRoles(array_unique($roles));

    $this->entityManager->flush();

    $io->success(sprintf('Le rôle %s a été ajouté à l\'utilisateur %s', $role, $email));

    return Command::SUCCESS;
  }
}
