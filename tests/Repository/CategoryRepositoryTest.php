<?php

namespace App\Tests\Repository;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CategoryRepositoryTest extends KernelTestCase
{
  private CategoryRepository $repository;
  private \Doctrine\ORM\EntityManagerInterface $entityManager;

  protected function setUp(): void
  {
    self::bootKernel();
    $this->entityManager = self::getContainer()->get('doctrine')->getManager();
    $this->repository = $this->entityManager->getRepository(Category::class);

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
    $categories = $this->repository->findAll();
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
    $category->setCreatedAt(new \DateTimeImmutable());
    if ($description) {
      $category->setDescription($description);
    }
    $this->entityManager->persist($category);
    $this->entityManager->flush();
    return $category;
  }

  public function testRepositoryExists(): void
  {
    $this->assertInstanceOf(CategoryRepository::class, $this->repository);
  }

  public function testFindAll(): void
  {
    $this->createCategory('Technologie', 'technologie');
    $this->createCategory('Sport', 'sport');
    $this->createCategory('Culture', 'culture');

    $categories = $this->repository->findAll();

    $this->assertCount(3, $categories);
  }

  public function testFindById(): void
  {
    $category = $this->createCategory('Technologie', 'technologie');
    $id = $category->getId();

    $found = $this->repository->find($id);

    $this->assertNotNull($found);
    $this->assertSame($id, $found->getId());
    $this->assertSame('Technologie', $found->getName());
  }

  public function testFindByName(): void
  {
    $this->createCategory('Technologie', 'technologie');
    $this->createCategory('Sport', 'sport');

    $results = $this->repository->findBy(['name' => 'Technologie']);

    $this->assertCount(1, $results);
    $this->assertSame('Technologie', $results[0]->getName());
  }

  public function testFindBySlug(): void
  {
    $this->createCategory('Technologie', 'technologie');
    $this->createCategory('Sport', 'sport');

    $results = $this->repository->findBy(['slug' => 'sport']);

    $this->assertCount(1, $results);
    $this->assertSame('sport', $results[0]->getSlug());
  }

  public function testFindOneBy(): void
  {
    $this->createCategory('Technologie', 'technologie', 'Description tech');
    $this->createCategory('Sport', 'sport');

    $category = $this->repository->findOneBy(['slug' => 'technologie']);

    $this->assertNotNull($category);
    $this->assertSame('Technologie', $category->getName());
    $this->assertSame('Description tech', $category->getDescription());
  }

  public function testCount(): void
  {
    $this->createCategory('Technologie', 'technologie');
    $this->createCategory('Sport', 'sport');

    $count = $this->repository->count([]);

    $this->assertSame(2, $count);
  }

  public function testPersistAndFlush(): void
  {
    $category = new Category();
    $category->setName('Nouvelle catégorie');
    $category->setSlug('nouvelle-categorie');
    $category->setCreatedAt(new \DateTimeImmutable());

    $this->entityManager->persist($category);
    $this->entityManager->flush();

    $this->assertNotNull($category->getId());

    // Vérifier que la catégorie est bien en base
    $found = $this->repository->find($category->getId());
    $this->assertNotNull($found);
    $this->assertSame('Nouvelle catégorie', $found->getName());
  }

  public function testRemove(): void
  {
    $category = $this->createCategory('À supprimer', 'a-supprimer');
    $id = $category->getId();

    $this->entityManager->remove($category);
    $this->entityManager->flush();

    $found = $this->repository->find($id);
    $this->assertNull($found);
  }

  public function testUpdate(): void
  {
    $category = $this->createCategory('Ancien nom', 'ancien-slug');
    $id = $category->getId();

    $category->setName('Nouveau nom');
    $category->setSlug('nouveau-slug');
    $category->setUpdatedAt(new \DateTimeImmutable());
    $this->entityManager->flush();

    $this->entityManager->clear();

    $updated = $this->repository->find($id);
    $this->assertSame('Nouveau nom', $updated->getName());
    $this->assertSame('nouveau-slug', $updated->getSlug());
    $this->assertNotNull($updated->getUpdatedAt());
  }

  public function testFindAllOrdered(): void
  {
    $this->createCategory('Zebra', 'zebra');
    $this->createCategory('Alpha', 'alpha');
    $this->createCategory('Beta', 'beta');

    $categories = $this->repository->findBy([], ['name' => 'ASC']);

    $this->assertCount(3, $categories);
    $this->assertSame('Alpha', $categories[0]->getName());
    $this->assertSame('Beta', $categories[1]->getName());
    $this->assertSame('Zebra', $categories[2]->getName());
  }
}
