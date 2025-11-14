<?php

namespace App\Controller;

use App\Repository\PostRepository;
use App\Repository\CategoryRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'home.index')]
    public function index(PostRepository $postRepository, CategoryRepository $categoryRepository): Response
    {
        $latestPosts = $postRepository->findBy(
            ['is_published' => true],
            ['createdAt' => 'DESC'],
            6
        );

        $categories = $categoryRepository->findAll();

        return $this->render('home/index.html.twig', [
            'latestPosts' => $latestPosts,
            'categories' => $categories,
        ]);
    }
}
