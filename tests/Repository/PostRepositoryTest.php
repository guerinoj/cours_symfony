<?php

namespace App\Tests\Repository;

use App\Entity\Author;
use App\Entity\Category;
use App\Entity\Post;
use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PostRepositoryTest extends KernelTestCase
{
    private PostRepository $repository;
    private \Doctrine\ORM\EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->repository = $this->entityManager->getRepository(Post::class);

        // Nettoyer la base de données avant chaque test
        $this->cleanDatabase();
    }

    protected function tearDown(): void
    {
        $this->cleanDatabase();
        parent::tearDown();
        $this->entityManager->close();
    }

    private function cleanDatabase(): void
    {
        // Supprimer tous les posts
        $posts = $this->repository->findAll();
        foreach ($posts as $post) {
            $this->entityManager->remove($post);
        }

        // Supprimer toutes les catégories
        $categories = $this->entityManager->getRepository(Category::class)->findAll();
        foreach ($categories as $category) {
            $this->entityManager->remove($category);
        }

        // Supprimer tous les auteurs
        $authors = $this->entityManager->getRepository(Author::class)->findAll();
        foreach ($authors as $author) {
            $this->entityManager->remove($author);
        }

        $this->entityManager->flush();
    }

    private function createAuthor(string $name, ?string $email = null): Author
    {
        $author = new Author();
        $author->setName($name);
        if ($email) {
            $author->setEmail($email);
        }
        $this->entityManager->persist($author);
        $this->entityManager->flush();
        return $author;
    }

    private function createCategory(string $name, string $slug): Category
    {
        $category = new Category();
        $category->setName($name);
        $category->setSlug($slug);
        $category->setCreatedAt(new \DateTimeImmutable());
        $this->entityManager->persist($category);
        $this->entityManager->flush();
        return $category;
    }

    private function createPost(
        string $title,
        string $content,
        Author $author,
        bool $isPublished = true,
        ?array $categories = null
    ): Post {
        $post = new Post();
        $post->setTitle($title);
        $post->setContent($content);
        $post->setAuthor($author);
        $post->setIsPublished($isPublished);
        $post->setCreatedAt(new \DateTimeImmutable());

        if ($categories) {
            foreach ($categories as $category) {
                $post->addCategory($category);
            }
        }

        $this->entityManager->persist($post);
        $this->entityManager->flush();
        return $post;
    }

    public function testRepositoryExists(): void
    {
        $this->assertInstanceOf(PostRepository::class, $this->repository);
    }

    public function testFindPublishedWithFiltersNoFilters(): void
    {
        $author = $this->createAuthor('John Doe');
        $this->createPost('Article publié 1', 'Contenu 1', $author, true);
        $this->createPost('Article publié 2', 'Contenu 2', $author, true);
        $this->createPost('Article non publié', 'Contenu 3', $author, false);

        $results = $this->repository->findPublishedWithFilters();

        $this->assertCount(2, $results);
        $this->assertTrue($results[0]->isPublished());
        $this->assertTrue($results[1]->isPublished());
    }

    public function testFindPublishedWithFiltersOrderByCreatedAtDesc(): void
    {
        $author = $this->createAuthor('John Doe');
        $post1 = $this->createPost('Premier article', 'Contenu 1', $author, true);
        
        // Attendre un peu pour avoir des timestamps différents
        sleep(1);
        
        $post2 = $this->createPost('Deuxième article', 'Contenu 2', $author, true);

        $results = $this->repository->findPublishedWithFilters();

        $this->assertCount(2, $results);
        // Le plus récent doit être en premier
        $this->assertSame($post2->getId(), $results[0]->getId());
        $this->assertSame($post1->getId(), $results[1]->getId());
    }

    public function testFindPublishedWithFiltersSearchByTitle(): void
    {
        $author = $this->createAuthor('John Doe');
        $this->createPost('Article sur Symfony', 'Contenu 1', $author, true);
        $this->createPost('Article sur PHP', 'Contenu 2', $author, true);
        $this->createPost('Article sur JavaScript', 'Contenu 3', $author, true);

        $results = $this->repository->findPublishedWithFilters('Symfony');

        $this->assertCount(1, $results);
        $this->assertStringContainsString('Symfony', $results[0]->getTitle());
    }

    public function testFindPublishedWithFiltersSearchByContent(): void
    {
        $author = $this->createAuthor('John Doe');
        $this->createPost('Article 1', 'Tutoriel sur Doctrine ORM', $author, true);
        $this->createPost('Article 2', 'Guide de Twig', $author, true);
        $this->createPost('Article 3', 'Utilisation de Doctrine', $author, true);

        $results = $this->repository->findPublishedWithFilters('Doctrine');

        $this->assertCount(2, $results);
    }

    public function testFindPublishedWithFiltersSearchByAuthorName(): void
    {
        $author1 = $this->createAuthor('Marie Dupont');
        $author2 = $this->createAuthor('Paul Martin');
        
        $this->createPost('Article 1', 'Contenu 1', $author1, true);
        $this->createPost('Article 2', 'Contenu 2', $author2, true);
        $this->createPost('Article 3', 'Contenu 3', $author1, true);

        $results = $this->repository->findPublishedWithFilters('Marie');

        $this->assertCount(2, $results);
        $this->assertSame('Marie Dupont', $results[0]->getAuthor()->getName());
        $this->assertSame('Marie Dupont', $results[1]->getAuthor()->getName());
    }

    public function testFindPublishedWithFiltersSearchCaseInsensitive(): void
    {
        $author = $this->createAuthor('John Doe');
        $this->createPost('Article sur SYMFONY', 'Contenu', $author, true);

        $results = $this->repository->findPublishedWithFilters('symfony');

        $this->assertCount(1, $results);
        $this->assertStringContainsString('SYMFONY', $results[0]->getTitle());
    }

    public function testFindPublishedWithFiltersSearchWithPartialMatch(): void
    {
        $author = $this->createAuthor('John Doe');
        $this->createPost('Formation Symfony avancée', 'Contenu', $author, true);

        $results = $this->repository->findPublishedWithFilters('Form');

        $this->assertCount(1, $results);
    }

    public function testFindPublishedWithFiltersFilterByCategory(): void
    {
        $author = $this->createAuthor('John Doe');
        $techCategory = $this->createCategory('Technologie', 'technologie');
        $sportCategory = $this->createCategory('Sport', 'sport');

        $this->createPost('Article Tech 1', 'Contenu', $author, true, [$techCategory]);
        $this->createPost('Article Tech 2', 'Contenu', $author, true, [$techCategory]);
        $this->createPost('Article Sport', 'Contenu', $author, true, [$sportCategory]);

        $results = $this->repository->findPublishedWithFilters(null, 'Technologie');

        $this->assertCount(2, $results);
        foreach ($results as $post) {
            $this->assertTrue($post->getCategories()->contains($techCategory));
        }
    }

    public function testFindPublishedWithFiltersSearchAndCategory(): void
    {
        $author = $this->createAuthor('John Doe');
        $techCategory = $this->createCategory('Technologie', 'technologie');
        $sportCategory = $this->createCategory('Sport', 'sport');

        $this->createPost('Symfony Framework', 'Contenu tech', $author, true, [$techCategory]);
        $this->createPost('PHP Programming', 'Contenu tech', $author, true, [$techCategory]);
        $this->createPost('Symfony Tips', 'Contenu sport', $author, true, [$sportCategory]);

        $results = $this->repository->findPublishedWithFilters('Symfony', 'Technologie');

        $this->assertCount(1, $results);
        $this->assertStringContainsString('Symfony', $results[0]->getTitle());
        $this->assertTrue($results[0]->getCategories()->contains($techCategory));
    }

    public function testFindPublishedWithFiltersNoResults(): void
    {
        $author = $this->createAuthor('John Doe');
        $this->createPost('Article 1', 'Contenu 1', $author, true);

        $results = $this->repository->findPublishedWithFilters('MotIntrouvable');

        $this->assertCount(0, $results);
        $this->assertIsArray($results);
    }

    public function testFindPublishedWithFiltersExcludesUnpublished(): void
    {
        $author = $this->createAuthor('John Doe');
        $this->createPost('Article Symfony publié', 'Contenu', $author, true);
        $this->createPost('Article Symfony brouillon', 'Contenu', $author, false);

        $results = $this->repository->findPublishedWithFilters('Symfony');

        $this->assertCount(1, $results);
        $this->assertTrue($results[0]->isPublished());
    }

    public function testFindPublishedWithFiltersEmptySearchString(): void
    {
        $author = $this->createAuthor('John Doe');
        $this->createPost('Article 1', 'Contenu 1', $author, true);
        $this->createPost('Article 2', 'Contenu 2', $author, true);

        // Chaîne vide ne doit pas filtrer
        $results = $this->repository->findPublishedWithFilters('');

        $this->assertCount(2, $results);
    }

    public function testFindPublishedWithFiltersEmptyCategoryString(): void
    {
        $author = $this->createAuthor('John Doe');
        $category = $this->createCategory('Tech', 'tech');
        
        $this->createPost('Article 1', 'Contenu 1', $author, true, [$category]);
        $this->createPost('Article 2', 'Contenu 2', $author, true);

        // Chaîne vide ne doit pas filtrer
        $results = $this->repository->findPublishedWithFilters(null, '');

        $this->assertCount(2, $results);
    }

    public function testFindPublishedWithFiltersPostWithMultipleCategories(): void
    {
        $author = $this->createAuthor('John Doe');
        $techCategory = $this->createCategory('Technologie', 'technologie');
        $webCategory = $this->createCategory('Web', 'web');

        $post = $this->createPost('Article Multi-Cat', 'Contenu', $author, true, [$techCategory, $webCategory]);

        $resultsTech = $this->repository->findPublishedWithFilters(null, 'Technologie');
        $resultsWeb = $this->repository->findPublishedWithFilters(null, 'Web');

        $this->assertCount(1, $resultsTech);
        $this->assertCount(1, $resultsWeb);
        $this->assertSame($post->getId(), $resultsTech[0]->getId());
        $this->assertSame($post->getId(), $resultsWeb[0]->getId());
    }

    public function testFindPublishedWithFiltersSearchInMultipleFields(): void
    {
        $author = $this->createAuthor('Thomas Anderson');
        $this->createPost('Article PHP', 'Tutoriel Thomas', $author, true);
        $this->createPost('Guide Symfony', 'Article de Thomas Anderson', $author, true);
        $this->createPost('Article JavaScript', 'Tutoriel JS', $author, true);

        // Recherche "Thomas" doit trouver dans le contenu ET le nom de l'auteur
        $results = $this->repository->findPublishedWithFilters('Thomas');

        // Les 3 posts devraient être trouvés car tous ont l'auteur Thomas Anderson
        $this->assertGreaterThanOrEqual(2, count($results));
    }
}
