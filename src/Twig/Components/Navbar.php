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
    return [
      ['path' => 'home.index', 'label' => 'Accueil', 'icon' => 'fa-solid fa-house'],
      ['path' => 'actu.index', 'label' => 'Actualités', 'icon' => 'fa-solid fa-newspaper'],
    ];
  }

  public function getAdminLinks(): array
  {
    if (!$this->security->isGranted('ROLE_ADMIN')) {
      return [];
    }

    return [
      ['path' => 'app_category_index', 'label' => 'Catégories', 'icon' => 'fa-solid fa-folder'],
      ['path' => 'author.index', 'label' => 'Auteurs', 'icon' => 'fa-solid fa-pen-fancy'],
      ['path' => 'app_admin_user_index', 'label' => 'Utilisateurs', 'icon' => 'fa-solid fa-users'],
      ['divider' => true],
      ['path' => 'app_category_new', 'label' => 'Nouvelle catégorie', 'icon' => 'fa-solid fa-circle-plus'],
    ];
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
