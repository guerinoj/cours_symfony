<?php

namespace App\Twig\Components;

use App\Repository\PostRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Navbar
{

  public function __construct(
    private PostRepository $postRepository,
    private Security $security
  ) {}

  public function getLinks(): array
  {
    $links = [
      ['path' => 'home.index', 'label' => 'Accueil', 'icon' => '🏠'],
      ['path' => 'actu.index', 'label' => 'Actualités', 'icon' => '📰'],
    ];

    // Ajouter des liens pour les utilisateurs connectés
    if ($this->security->isGranted('IS_AUTHENTICATED_FULLY')) {
      $links[] = ['path' => 'app_category_index', 'label' => 'Catégories', 'icon' => '📁'];
      $links[] = ['path' => 'author.index', 'label' => 'Auteurs', 'icon' => '✍️'];
    }

    // Ajouter des liens admin si l'utilisateur a le rôle ROLE_ADMIN
    if ($this->security->isGranted('ROLE_ADMIN')) {
      $links[] = ['path' => 'app_category_new', 'label' => 'Nouvelle catégorie', 'icon' => '➕'];
      $links[] = ['path' => 'app_admin_user_index', 'label' => 'Gestion des utilisateurs', 'icon' => '👥'];
    }

    return $links;
  }

  public function getPosts(): array
  {
    return $this->postRepository->findBy(
      ['is_published' => true],
      ['createdAt' => 'DESC'],
      5
    );
  }
}
