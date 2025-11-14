<?php

namespace App\Tests\Controller;

use App\Entity\Category;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CategoryControllerTest extends WebTestCase
{
  private $client;
  private $entityManager;
  private $categoryRepository;
  private $adminUser;

  protected function setUp(): void
  {
    $this->client = static::createClient();
    $container = static::getContainer();
    $this->entityManager = $container->get(EntityManagerInterface::class);
    $this->categoryRepository = $container->get(CategoryRepository::class);

    // Nettoyer la base de données
    $this->cleanDatabase();

    // Créer un utilisateur admin pour les tests
    $userRepository = $container->get(UserRepository::class);
    $this->adminUser = $userRepository->findOneBy(['email' => 'admin@example.com']);

    if (!$this->adminUser) {
      $this->adminUser = new User();
      $this->adminUser->setEmail('admin@example.com');
      $this->adminUser->setPassword('$2y$13$hashedpassword'); // Mot de passe hashé
      $this->adminUser->setRoles(['ROLE_ADMIN']);

      $this->entityManager->persist($this->adminUser);
      $this->entityManager->flush();
    }
  }

  protected function tearDown(): void
  {
    parent::tearDown();
    $this->entityManager->close();
    $this->entityManager = null;
  }

  private function cleanDatabase(): void
  {
    // Supprimer toutes les catégories de test
    $categories = $this->categoryRepository->findAll();
    foreach ($categories as $category) {
      $this->entityManager->remove($category);
    }
    $this->entityManager->flush();
  }

  private function createCategory(string $name, string $slug, ?string $description = null): Category
  {
    $category = new Category();
    $category->setName($name);
    $category->setSlug($slug);
    $category->setDescription($description);
    $category->setCreatedAt(new \DateTimeImmutable());

    $this->entityManager->persist($category);
    $this->entityManager->flush();

    return $category;
  }

  public function testIndexPageRequiresAuthentication(): void
  {
    $this->client->request('GET', '/category');

    // Sans authentification, on doit être redirigé vers la page de login
    $this->assertResponseRedirects('/login');
  }

  public function testIndexPageWithAuthenticatedAdmin(): void
  {
    // Créer quelques catégories
    $this->createCategory('Technologie', 'technologie', 'Description tech');
    $this->createCategory('Sport', 'sport', 'Description sport');

    // S'authentifier en tant qu'admin
    $this->client->loginUser($this->adminUser);

    $crawler = $this->client->request('GET', '/category');

    $this->assertResponseIsSuccessful();
    $this->assertSelectorTextContains('h1', 'Gestion des catégories');

    // Vérifier que les catégories sont affichées
    $this->assertCount(2, $crawler->filter('tbody tr'));
  }

  public function testNewPageDisplaysForm(): void
  {
    $this->client->loginUser($this->adminUser);

    $crawler = $this->client->request('GET', '/category/new');

    $this->assertResponseIsSuccessful();
    $this->assertSelectorExists('form[name="category"]');
    $this->assertSelectorExists('input[name="category[name]"]');
    $this->assertSelectorExists('input[name="category[slug]"]');
    $this->assertSelectorExists('textarea[name="category[description]"]');
  }

  public function testNewCategorySubmission(): void
  {
    $this->client->loginUser($this->adminUser);

    $crawler = $this->client->request('GET', '/category/new');

    $form = $crawler->selectButton('Save')->form([
      'category[name]' => 'Nouvelle Catégorie',
      'category[slug]' => 'nouvelle-categorie',
      'category[description]' => 'Description de la nouvelle catégorie',
    ]);

    $this->client->submit($form);

    // Vérifier la redirection après création
    $this->assertResponseRedirects('/category');

    // Vérifier que la catégorie a été créée en base
    $category = $this->categoryRepository->findOneBy(['slug' => 'nouvelle-categorie']);
    $this->assertNotNull($category);
    $this->assertEquals('Nouvelle Catégorie', $category->getName());
    $this->assertEquals('Description de la nouvelle catégorie', $category->getDescription());
    $this->assertInstanceOf(\DateTimeImmutable::class, $category->getCreatedAt());
  }

  public function testNewCategoryWithInvalidData(): void
  {
    $this->client->loginUser($this->adminUser);

    $crawler = $this->client->request('GET', '/category/new');

    $form = $crawler->selectButton('Save')->form([
      'category[name]' => 'ab', // Trop court (min 3)
      'category[slug]' => 'ab', // Trop court (min 3)
      'category[description]' => '',
    ]);

    $this->client->submit($form);

    // Le formulaire doit être affiché à nouveau avec des erreurs
    $this->assertResponseStatusCodeSame(422);
    $this->assertSelectorExists('.invalid-feedback, .form-error-message');
  }

  public function testShowCategory(): void
  {
    $category = $this->createCategory('Technologie', 'technologie', 'Description tech');

    $this->client->loginUser($this->adminUser);

    $crawler = $this->client->request('GET', '/category/' . $category->getId());

    $this->assertResponseIsSuccessful();
    $this->assertSelectorTextContains('body', 'Technologie');
    $this->assertSelectorTextContains('body', 'Description tech');
  }

  public function testEditPageDisplaysFormWithData(): void
  {
    $category = $this->createCategory('Technologie', 'technologie', 'Description tech');

    $this->client->loginUser($this->adminUser);

    $crawler = $this->client->request('GET', '/category/' . $category->getId() . '/edit');

    $this->assertResponseIsSuccessful();
    $this->assertSelectorExists('form[name="category"]');

    // Vérifier que le formulaire est pré-rempli
    $this->assertInputValueSame('category[name]', 'Technologie');
    $this->assertInputValueSame('category[slug]', 'technologie');
  }

  public function testEditCategorySubmission(): void
  {
    $category = $this->createCategory('Technologie', 'technologie', 'Description tech');
    $categoryId = $category->getId();

    $this->client->loginUser($this->adminUser);

    $crawler = $this->client->request('GET', '/category/' . $categoryId . '/edit');

    $form = $crawler->selectButton('Update')->form([
      'category[name]' => 'Tech Modifié',
      'category[slug]' => 'tech-modifie',
      'category[description]' => 'Nouvelle description',
    ]);

    $this->client->submit($form);

    // Vérifier la redirection après modification
    $this->assertResponseRedirects('/category');

    // Vérifier que la catégorie a été mise à jour
    $this->entityManager->clear();
    $updatedCategory = $this->categoryRepository->find($categoryId);
    $this->assertEquals('Tech Modifié', $updatedCategory->getName());
    $this->assertEquals('tech-modifie', $updatedCategory->getSlug());
    $this->assertEquals('Nouvelle description', $updatedCategory->getDescription());
    $this->assertInstanceOf(\DateTimeImmutable::class, $updatedCategory->getUpdatedAt());
  }

  public function testEditCategoryWithInvalidData(): void
  {
    $category = $this->createCategory('Technologie', 'technologie', 'Description tech');

    $this->client->loginUser($this->adminUser);

    $crawler = $this->client->request('GET', '/category/' . $category->getId() . '/edit');

    $form = $crawler->selectButton('Update')->form([
      'category[name]' => 'ab', // Trop court (min 3)
      'category[slug]' => 'ab', // Trop court (min 3)
      'category[description]' => 'Description',
    ]);

    $this->client->submit($form);

    // Le formulaire doit être affiché à nouveau avec des erreurs
    $this->assertResponseStatusCodeSame(422);
  }

  public function testDeleteCategory(): void
  {
    $category = $this->createCategory('Technologie', 'technologie', 'Description tech');
    $categoryId = $category->getId();

    $this->client->loginUser($this->adminUser);

    // Aller sur la page edit qui contient le formulaire de suppression
    $crawler = $this->client->request('GET', '/category/' . $categoryId . '/edit');

    // Récupérer et soumettre le formulaire de suppression
    $deleteForm = $crawler->filter('form[method="post"]')->eq(1)->form(); // Le deuxième formulaire est le delete
    $this->client->submit($deleteForm);

    // Vérifier la redirection après suppression
    $this->assertResponseRedirects('/category');

    // Vérifier que la catégorie a été supprimée
    $this->entityManager->clear();
    $deletedCategory = $this->categoryRepository->find($categoryId);
    $this->assertNull($deletedCategory);
  }

  public function testDeleteCategoryWithInvalidCsrfToken(): void
  {
    $category = $this->createCategory('Technologie', 'technologie', 'Description tech');
    $categoryId = $category->getId();

    $this->client->loginUser($this->adminUser);

    // Soumettre avec un token CSRF invalide
    $this->client->request('POST', '/category/' . $categoryId, [
      '_token' => 'invalid_token',
    ]);

    // Vérifier que la redirection se fait quand même (comportement Symfony)
    $this->assertResponseRedirects('/category');

    // Vérifier que la catégorie n'a PAS été supprimée
    $this->entityManager->clear();
    $category = $this->categoryRepository->find($categoryId);
    $this->assertNotNull($category);
  }

  public function testCategoryNotFound(): void
  {
    $this->client->loginUser($this->adminUser);

    $this->client->request('GET', '/category/99999');

    // Doit retourner 404 pour une catégorie inexistante
    $this->assertResponseStatusCodeSame(404);
  }

  public function testEditNonExistentCategory(): void
  {
    $this->client->loginUser($this->adminUser);

    $this->client->request('GET', '/category/99999/edit');

    // Doit retourner 404 pour une catégorie inexistante
    $this->assertResponseStatusCodeSame(404);
  }

  public function testDeleteNonExistentCategory(): void
  {
    $this->client->loginUser($this->adminUser);

    $this->client->request('POST', '/category/99999', [
      '_token' => 'some_token',
    ]);

    // Doit retourner 404 pour une catégorie inexistante
    $this->assertResponseStatusCodeSame(404);
  }

  public function testNewCategoryWithMinimalData(): void
  {
    $this->client->loginUser($this->adminUser);

    $crawler = $this->client->request('GET', '/category/new');

    $form = $crawler->selectButton('Save')->form([
      'category[name]' => 'Cat', // Minimum 3 caractères
      'category[slug]' => 'cat',
      'category[description]' => null, // Description nullable
    ]);

    $this->client->submit($form);

    $this->assertResponseRedirects('/category');

    $category = $this->categoryRepository->findOneBy(['slug' => 'cat']);
    $this->assertNotNull($category);
    $this->assertEquals('Cat', $category->getName());
    $this->assertNull($category->getDescription());
  }

  public function testIndexShowsMultipleCategories(): void
  {
    // Créer plusieurs catégories
    $this->createCategory('Technologie', 'technologie');
    $this->createCategory('Sport', 'sport');
    $this->createCategory('Culture', 'culture');
    $this->createCategory('Politique', 'politique');

    $this->client->loginUser($this->adminUser);

    $crawler = $this->client->request('GET', '/category');

    $this->assertResponseIsSuccessful();

    // Vérifier qu'on a bien 4 catégories affichées
    $this->assertCount(4, $crawler->filter('tbody tr'));
  }
}
