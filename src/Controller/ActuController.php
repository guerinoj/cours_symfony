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
        return new Response('Article : ' . ($slug));
    }

    #[Route('/actu', name: 'actu.index')]
    public function index(): Response
    {
        return new Response('<h1>Liste des articles</h1>
        <ul>
            <li><a href="/actu/article-1">Article 1</a></li>
            <li><a href="/actu/article-2">Article 2</a></li>
            <li><a href="/actu/article-3">Article 3</a></li>
        </ul>');
    }
}
