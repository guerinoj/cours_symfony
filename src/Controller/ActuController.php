<?php

namespace App\Controller;

use Dom\Entity;
use App\Entity\Post;
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
        $post->setTitle('New Article');
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

    #[Route('/actu/{slug}', name: 'actu.show', requirements: ['slug' => '[a-z0-9\-]+'])]
    public function show(string $slug): Response
    {
        return $this->render('actu/show.html.twig', [
            'slug' => $slug,
        ]);
    }

    #[Route('/actu', name: 'actu.index')]
    public function index(): Response
    {
        return $this->render(
            'actu/index.html.twig'
        );
    }
}
