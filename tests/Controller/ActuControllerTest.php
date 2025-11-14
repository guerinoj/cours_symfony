<?php

namespace App\Tests\Controller;

use App\Entity\Author;
use App\Entity\Category;
use App\Entity\Post;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ActuControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();
        $this->cleanDatabase();
    }

    protected function tearDown(): void
    {
        $this->cleanDatabase();
        $this->entityManager->close();
        parent::tearDown();
    }

    private function cleanDatabase(): void
    {
        $postRepository = $this->entityManager->getRepository(Post::class);
        $posts = $postRepository->findAll();
        foreach ($posts as $post) {
            $this->entityManager->remove($post);
        }

        $categoryRepository = $this->entityManager->getRepository(Category::class);
        $categories = $categoryRepository->findAll();
        foreach ($categories as $category) {
            $this->entityManager->remove($category);
        }

        $authorRepository = $this->entityManager->getRepository(Author::class);
        $authors = $authorRepository->findAll();
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

    // Tests pour actu.index
    public function testIndexPageIsSuccessful(): void
    {
        $this->client->request('GET', '/actu');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Actualités');
    }

    public function testIndexDisplaysPublishedPosts(): void
    {
        $author = $this->createAuthor('John Doe');
        $this->createPost('Article publié 1', 'Contenu 1', $author, true);
        $this->createPost('Article publié 2', 'Contenu 2', $author, true);
        $this->createPost('Article brouillon', 'Contenu brouillon', $author, false);

        $crawler = $this->client->request('GET', '/actu');

        $this->assertResponseIsSuccessful();
        $this->assertCount(2, $crawler->filter('.card'));
    }

    public function testIndexWithSearchQuery(): void
    {
        $author = $this->createAuthor('John Doe');
        $this->createPost('Article sur Symfony', 'Contenu Symfony', $author, true);
        $this->createPost('Article sur PHP', 'Contenu PHP', $author, true);

        $crawler = $this->client->request('GET', '/actu?search=Symfony');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Symfony', $crawler->filter('body')->text());
    }

    public function testIndexWithCategoryFilter(): void
    {
        $author = $this->createAuthor('John Doe');
        $techCategory = $this->createCategory('Technologie', 'technologie');
        $sportCategory = $this->createCategory('Sport', 'sport');

        $this->createPost('Article Tech', 'Contenu tech', $author, true, [$techCategory]);
        $this->createPost('Article Sport', 'Contenu sport', $author, true, [$sportCategory]);

        $crawler = $this->client->request('GET', '/actu?category=Technologie');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Tech', $crawler->filter('body')->text());
    }

    // Tests pour actu.show
    public function testShowPageDisplaysPost(): void
    {
        $author = $this->createAuthor('John Doe');
        $post = $this->createPost('Mon article', 'Contenu de mon article', $author, true);

        $crawler = $this->client->request('GET', '/actu/' . $post->getId());

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Mon article', $crawler->filter('body')->text());
        $this->assertStringContainsString('Contenu de mon article', $crawler->filter('body')->text());
    }

    public function testShowPageWithNonExistentPost(): void
    {
        $this->client->request('GET', '/actu/99999');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    // Tests pour actu.create
    public function testCreatePageIsAccessible(): void
    {
        $this->client->request('GET', '/actu/create');
        $this->assertResponseIsSuccessful();
    }

    public function testCreatePageDisplaysForm(): void
    {
        $author = $this->createAuthor('John Doe');
        
        $crawler = $this->client->request('GET', '/actu/create');

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('form'));
        $this->assertCount(1, $crawler->filter('input[name="post[title]"]'));
        $this->assertCount(1, $crawler->filter('textarea[name="post[content]"]'));
    }

    public function testCreatePostWithValidData(): void
    {
        $author = $this->createAuthor('John Doe');
        
        $crawler = $this->client->request('GET', '/actu/create');
        $form = $crawler->selectButton('Enregistrer')->form();

        $form['post[title]'] = 'Nouvel article de test';
        $form['post[content]'] = 'Ceci est le contenu de mon nouvel article.';
        $form['post[is_published]'] = true;
        $form['post[author]'] = $author->getId();

        $this->client->submit($form);

        $this->assertResponseRedirects();
        $this->client->followRedirect();

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.alert-success');
    }

    public function testCreatePostWithInvalidData(): void
    {
        $author = $this->createAuthor('John Doe');
        
        $crawler = $this->client->request('GET', '/actu/create');
        $form = $crawler->selectButton('Enregistrer')->form();

        // Titre trop court (moins de 5 caractères)
        $form['post[title]'] = 'Test';
        $form['post[content]'] = 'Contenu valide';
        $form['post[is_published]'] = true;
        $form['post[author]'] = $author->getId();

        $this->client->submit($form);

        // Le formulaire doit afficher des erreurs
        $this->assertResponseIsSuccessful(); // Reste sur la page
        $this->assertSelectorExists('.alert-warning');
    }

    // Tests pour actu.edit
    public function testEditPageIsAccessible(): void
    {
        $author = $this->createAuthor('John Doe');
        $post = $this->createPost('Article à éditer', 'Contenu', $author, true);

        $this->client->request('GET', '/actu/' . $post->getId() . '/edit');
        $this->assertResponseIsSuccessful();
    }

    public function testEditPageDisplaysFormWithPostData(): void
    {
        $author = $this->createAuthor('John Doe');
        $post = $this->createPost('Article à éditer', 'Contenu à éditer', $author, true);

        $crawler = $this->client->request('GET', '/actu/' . $post->getId() . '/edit');

        $this->assertResponseIsSuccessful();
        $this->assertInputValueSame('post[title]', 'Article à éditer');
        $this->assertSelectorTextContains('textarea[name="post[content]"]', 'Contenu à éditer');
    }

    public function testEditPostWithValidData(): void
    {
        $author = $this->createAuthor('John Doe');
        $post = $this->createPost('Ancien titre', 'Ancien contenu', $author, true);

        $crawler = $this->client->request('GET', '/actu/' . $post->getId() . '/edit');
        $form = $crawler->selectButton('Enregistrer')->form();

        $form['post[title]'] = 'Nouveau titre modifié';
        $form['post[content]'] = 'Nouveau contenu modifié';

        $this->client->submit($form);

        $this->assertResponseRedirects();
        $this->client->followRedirect();

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.alert-success');

        // Vérifier que les données ont été mises à jour en base
        $this->entityManager->clear();
        $updatedPost = $this->entityManager->getRepository(Post::class)->find($post->getId());
        $this->assertEquals('Nouveau titre modifié', $updatedPost->getTitle());
        $this->assertEquals('Nouveau contenu modifié', $updatedPost->getContent());
        $this->assertNotNull($updatedPost->getUpdatedAt());
    }

    public function testEditPageWithNonExistentPost(): void
    {
        $this->client->request('GET', '/actu/99999/edit');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    // Tests pour actu.delete
    public function testDeletePostSuccessfully(): void
    {
        $author = $this->createAuthor('John Doe');
        $post = $this->createPost('Article à supprimer', 'Contenu', $author, true);
        $postId = $post->getId();

        $this->client->request('GET', '/actu/' . $postId . '/delete');

        $this->assertResponseRedirects('/actu');
        $this->client->followRedirect();

        $this->assertSelectorExists('.alert-success');
        
        // Vérifier que le post a bien été supprimé
        $deletedPost = $this->entityManager->getRepository(Post::class)->find($postId);
        $this->assertNull($deletedPost);
    }

    public function testDeleteNonExistentPost(): void
    {
        $this->client->request('GET', '/actu/99999/delete');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    // Tests pour les messages flash contextuels
    public function testIndexWithNoResultsSearch(): void
    {
        $author = $this->createAuthor('John Doe');
        $this->createPost('Article PHP', 'Contenu', $author, true);

        $this->client->request('GET', '/actu?search=MotInexistant');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.alert-info');
    }

    public function testIndexWithNoResultsCategory(): void
    {
        $author = $this->createAuthor('John Doe');
        $this->createPost('Article', 'Contenu', $author, true);

        $this->client->request('GET', '/actu?category=CategorieInexistante');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.alert-info');
    }

    public function testIndexWithSuccessfulSearch(): void
    {
        $author = $this->createAuthor('John Doe');
        $this->createPost('Article Symfony', 'Contenu', $author, true);

        $this->client->request('GET', '/actu?search=Symfony');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.alert-success');
    }

    // Test pour la méthode addContextualMessages
    public function testCreatePostAsPublished(): void
    {
        $author = $this->createAuthor('John Doe');
        
        $crawler = $this->client->request('GET', '/actu/create');
        $form = $crawler->selectButton('Enregistrer')->form();

        $form['post[title]'] = 'Article publié immédiatement';
        $form['post[content]'] = 'Contenu';
        $form['post[is_published]'] = true;
        $form['post[author]'] = $author->getId();

        $this->client->submit($form);
        $this->client->followRedirect();

        $this->assertSelectorTextContains('.alert-success', 'publié');
    }

    public function testCreatePostAsDraft(): void
    {
        $author = $this->createAuthor('John Doe');
        
        $crawler = $this->client->request('GET', '/actu/create');
        $form = $crawler->selectButton('Enregistrer')->form();

        $form['post[title]'] = 'Article brouillon';
        $form['post[content]'] = 'Contenu brouillon';
        $form['post[is_published]'] = false;
        $form['post[author]'] = $author->getId();

        $this->client->submit($form);
        $this->client->followRedirect();

        $this->assertSelectorTextContains('.alert-success', 'brouillon');
    }

    public function testIndexWithMultiplePublishedPosts(): void
    {
        $author = $this->createAuthor('John Doe');
        $this->createPost('Article 1', 'Contenu 1', $author, true);
        $this->createPost('Article 2', 'Contenu 2', $author, true);
        $this->createPost('Article 3', 'Contenu 3', $author, true);

        $crawler = $this->client->request('GET', '/actu');

        $this->assertResponseIsSuccessful();
        $this->assertCount(3, $crawler->filter('.card'));
    }

    public function testPostWithMultipleCategories(): void
    {
        $author = $this->createAuthor('John Doe');
        $category1 = $this->createCategory('Tech', 'tech');
        $category2 = $this->createCategory('Web', 'web');
        
        $post = $this->createPost('Article multi-cat', 'Contenu', $author, true, [$category1, $category2]);

        $crawler = $this->client->request('GET', '/actu/' . $post->getId());

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Tech', $crawler->filter('body')->text());
        $this->assertStringContainsString('Web', $crawler->filter('body')->text());
    }
}
