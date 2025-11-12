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

                $statusMessage = $post->isPublished() ? 'publié' : 'sauvegardé en brouillon';
                $this->addFlash('success', "Article \"{$post->getTitle()}\" {$statusMessage} avec succès !");
                return $this->redirectToRoute('actu.show', ['id' => $post->getId()]);
            } else {
                $this->addFlash('warning', 'Le formulaire contient des erreurs. Veuillez corriger les champs en rouge.');
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
            $this->addFlash('error', 'L\'article demandé n\'existe pas.');
            throw $this->createNotFoundException('Article not found');
        }

        // Create the form for editing the post
        $form = $this->createForm(PostType::class, $post);

        // Handle the request data for the form
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    // Update the updatedAt field
                    $post->setUpdatedAt(new \DateTimeImmutable());
                    // Save the updated post entity
                    $entityManager->flush();

                    $this->addFlash('success', "Article \"{$post->getTitle()}\" modifié avec succès !");
                    return $this->redirectToRoute('actu.show', ['id' => $post->getId()]);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de la sauvegarde. Veuillez réessayer.');
                }
            } else {
                $this->addFlash('warning', 'Le formulaire contient des erreurs. Veuillez corriger les champs en rouge.');
            }
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
            $this->addFlash('error', 'L\'article à supprimer n\'existe pas.');
            throw $this->createNotFoundException('Article not found');
        }

        $postTitle = $post->getTitle(); // Sauvegarder le titre avant suppression

        try {
            // Remove the post
            $entityManager->remove($post);
            $entityManager->flush();

            $this->addFlash('success', "Article \"{$postTitle}\" supprimé avec succès.");
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la suppression. Veuillez réessayer.');
        }

        // Redirect to the index after deletion
        return $this->redirectToRoute('actu.index');
    }

    #[Route('/actu', name: 'actu.index')]
    public function index(PostRepository $postRepository, EntityManagerInterface $entityManager, Request $request): Response
    {
        // Récupérer les paramètres de recherche et filtrage
        $searchQuery = trim($request->query->get('search', ''));
        $categoryFilter = $request->query->get('category');

        // Utiliser la méthode dédiée du repository
        $posts = $postRepository->findPublishedWithFilters(
            !empty($searchQuery) ? $searchQuery : null,
            !empty($categoryFilter) ? $categoryFilter : null
        );

        // Récupérer toutes les catégories utilisées dans les posts publiés
        $categories = $entityManager->getRepository(\App\Entity\Category::class)
            ->createQueryBuilder('c')
            ->innerJoin('c.posts', 'p')
            ->where('p.is_published = :published')
            ->setParameter('published', true)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();

        // Messages informatifs selon le contexte
        $this->addContextualMessages($searchQuery, $categoryFilter, count($posts));

        return $this->render(
            'actu/index.html.twig',
            [
                'posts' => $posts,
                'categories' => $categories,
                'currentCategory' => $categoryFilter,
                'currentSearch' => $searchQuery,
            ]
        );
    }

    /**
     * Ajoute des messages contextuels selon les résultats de recherche/filtrage
     */
    private function addContextualMessages(?string $searchQuery, ?string $categoryFilter, int $resultsCount): void
    {
        if ($resultsCount === 0) {
            if (!empty($searchQuery) && $categoryFilter) {
                $this->addFlash('info', "Aucun article trouvé pour \"{$searchQuery}\" dans la catégorie \"{$categoryFilter}\".");
            } elseif (!empty($searchQuery)) {
                $this->addFlash('info', "Aucun article trouvé pour \"{$searchQuery}\".");
            } elseif ($categoryFilter) {
                $this->addFlash('info', "Aucun article trouvé dans la catégorie \"{$categoryFilter}\".");
            } else {
                $this->addFlash('info', 'Aucun article publié pour le moment.');
            }
        } elseif (!empty($searchQuery) && $resultsCount > 0) {
            $articleText = $resultsCount === 1 ? 'article trouvé' : 'articles trouvés';
            $this->addFlash('success', "{$resultsCount} {$articleText} pour \"{$searchQuery}\".");
        }
    }
}
