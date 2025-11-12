<?php

namespace App\Controller;

use Dom\Entity;
use App\Entity\Post;
use App\Form\PostType;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class ActuController extends AbstractController
{

    #[Route('/actu/create', name: 'actu.create')]
    public function create(EntityManagerInterface $entityManager, Request $request): Response
    {
        $postsCount = $entityManager->getRepository(Post::class)->count([]);

        $post = new Post();

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        // Debug: vérifier si le formulaire est soumis
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $post->setCreatedAt(new \DateTimeImmutable());
                // Persist the new post entity
                $entityManager->persist($post);
                // Flush to save it to the database
                $entityManager->flush();

                $this->addFlash('success', 'Article créé avec succès !');
                return $this->redirectToRoute('actu.show', ['id' => $post->getId()]);
            } else {
                $this->addFlash('error', 'Le formulaire contient des erreurs. Veuillez corriger les champs en rouge.');
            }
        }

        return $this->render('actu/create.html.twig', [
            'form' => $form->createView(),
            'postsCount' => $postsCount,
        ]);
    }

    #[Route('/actu/{id}', name: 'actu.show', requirements: ['id' => '\d+'])]
    public function show(PostRepository $postRepository, int $id): Response
    {
        // Find the post by its ID
        $post = $postRepository->find($id);

        if (!$post) {
            throw $this->createNotFoundException('Article not found');
        }

        // Render the post details
        return $this->render('actu/show.html.twig', [
            'post' => $post,
        ]);
    }

    #[Route('/actu/{id}/edit', name: 'actu.edit', requirements: ['id' => '\d+'])]
    public function edit(Post $post, EntityManagerInterface $entityManager, Request $request): Response
    {
        if (!$post) {
            throw $this->createNotFoundException('Article not found');
        }

        // Create the form for editing the post
        $form = $this->createForm(PostType::class, $post);

        // Handle the request data for the form
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Update the updatedAt field
            $post->setUpdatedAt(new \DateTimeImmutable());
            // Save the updated post entity
            $entityManager->flush();

            return $this->redirectToRoute('actu.show', ['id' => $post->getId()]);
        }

        // Render the edit form
        return $this->render('actu/edit.html.twig', [
            'post' => $post,
            'form' => $form
        ]);
    }

    #[Route('/actu/{id}/delete', name: 'actu.delete', requirements: ['id' => '\d+'])]
    public function delete(PostRepository $postRepository, int $id, EntityManagerInterface $entityManager): Response
    {
        // Find the post by its ID
        $post = $postRepository->find($id);

        if (!$post) {
            throw $this->createNotFoundException('Article not found');
        }

        // Remove the post
        $entityManager->remove($post);
        $entityManager->flush();

        // Redirect to the index after deletion
        return $this->redirectToRoute('actu.index');
    }

    #[Route('/actu', name: 'actu.index')]
    public function index(PostRepository $postRepository): Response
    {
        $posts = $postRepository->findBy(['is_published' => true], ['createdAt' => 'DESC']);

        return $this->render(
            'actu/index.html.twig',
            ['posts' => $posts]
        );
    }
}
