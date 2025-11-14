<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserRoleType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class AdminUserController extends AbstractController
{
  #[Route('/', name: 'app_admin_user_index', methods: ['GET'])]
  public function index(UserRepository $userRepository): Response
  {
    return $this->render('admin_user/index.html.twig', [
      'users' => $userRepository->findAll(),
    ]);
  }

  #[Route('/{id}/edit-roles', name: 'app_admin_user_edit_roles', methods: ['GET', 'POST'])]
  public function editRoles(Request $request, User $user, EntityManagerInterface $entityManager): Response
  {
    $form = $this->createForm(UserRoleType::class, $user);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $entityManager->flush();

      $this->addFlash('success', 'Les rôles ont été mis à jour avec succès.');

      return $this->redirectToRoute('app_admin_user_index');
    }

    return $this->render('admin_user/edit_roles.html.twig', [
      'user' => $user,
      'form' => $form,
    ]);
  }
}
