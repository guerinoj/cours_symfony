<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Navbar
{
  public array $links = [
    ['path' => 'home.index', 'label' => 'Home'],
    ['path' => 'actu.index', 'label' => 'Last news'],
  ];

  public array $news = [
    ['slug' => 'article-1', 'title' => 'Article 1'],
    ['slug' => 'article-2', 'title' => 'Article 2'],
    ['slug' => 'article-3', 'title' => 'Article 3'],
  ];
}
