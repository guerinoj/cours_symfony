<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ActuController extends AbstractController
{
    #[Route('/actu/{slug}', name: 'actu.show', requirements: ['slug' => '[a-z0-9\-]+'])]
    public function show(string $slug): Response
    {
        return $this->render('actu/show.html.twig', [
            'slug' => $slug,
            'news' => ['article-1', 'article-2', 'article-3']
        ]);
    }

    #[Route('/actu', name: 'actu.index')]
    public function index(): Response
    {
        return $this->render(
            'actu/index.html.twig',
            [
                'news' => ['article-1', 'article-2', 'article-3']
            ]
        );
    }
}
