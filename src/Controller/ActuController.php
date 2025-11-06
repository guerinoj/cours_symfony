<?php

namespace App\Controller;

use Dom\Entity;
use App\Entity\Post;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class ActuController extends AbstractController
{

    #[Route('/actu/create', name: 'actu.create')]
    public function create(EntityManagerInterface $entityManager): Response
    {
        $post = new Post();
        $post->setTitle('Article inconnu');
        $post->setContent('Content of the new article');
        $post->setCreatedAt(new \DateTimeImmutable());
        $post->setIsPublished(true);
        $post->setAuthor('John Doe');

        // Persist the new post entity
        $entityManager->persist($post);

        // Flush to save it to the database
        $entityManager->flush();

        return new Response('Create an article : ' . $post->getTitle());
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
