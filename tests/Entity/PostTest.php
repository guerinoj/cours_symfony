<?php

namespace App\Tests\Entity;

use App\Entity\Author;
use App\Entity\Category;
use App\Entity\Post;
use PHPUnit\Framework\TestCase;

class PostTest extends TestCase
{
  private Post $post;

  protected function setUp(): void
  {
    $this->post = new Post();
  }

  public function testInitialState(): void
  {
    $this->assertNull($this->post->getId());
    $this->assertNull($this->post->getTitle());
    $this->assertNull($this->post->getContent());
    $this->assertNull($this->post->getCreatedAt());
    $this->assertNull($this->post->getUpdatedAt());
    $this->assertNull($this->post->isPublished());
    $this->assertNull($this->post->getAuthor());
    $this->assertCount(0, $this->post->getCategories());
  }

  public function testSetAndGetTitle(): void
  {
    $title = 'Mon premier article';
    $result = $this->post->setTitle($title);

    $this->assertSame($this->post, $result);
    $this->assertSame($title, $this->post->getTitle());
  }

  public function testSetAndGetContent(): void
  {
    $content = 'Ceci est le contenu de mon article.';
    $result = $this->post->setContent($content);

    $this->assertSame($this->post, $result);
    $this->assertSame($content, $this->post->getContent());
  }

  public function testSetAndGetCreatedAt(): void
  {
    $date = new \DateTimeImmutable('2024-01-15 10:30:00');
    $result = $this->post->setCreatedAt($date);

    $this->assertSame($this->post, $result);
    $this->assertSame($date, $this->post->getCreatedAt());
  }

  public function testSetAndGetUpdatedAt(): void
  {
    $date = new \DateTimeImmutable('2024-01-20 15:45:00');
    $result = $this->post->setUpdatedAt($date);

    $this->assertSame($this->post, $result);
    $this->assertSame($date, $this->post->getUpdatedAt());
  }

  public function testSetAndGetUpdatedAtNull(): void
  {
    $this->post->setUpdatedAt(null);
    $this->assertNull($this->post->getUpdatedAt());
  }

  public function testSetAndGetIsPublished(): void
  {
    $result = $this->post->setIsPublished(true);

    $this->assertSame($this->post, $result);
    $this->assertTrue($this->post->isPublished());

    $this->post->setIsPublished(false);
    $this->assertFalse($this->post->isPublished());
  }

  public function testSetAndGetAuthor(): void
  {
    $author = new Author();
    $author->setName('John Doe');

    $result = $this->post->setAuthor($author);

    $this->assertSame($this->post, $result);
    $this->assertSame($author, $this->post->getAuthor());
  }

  public function testSetAuthorNull(): void
  {
    $this->post->setAuthor(null);
    $this->assertNull($this->post->getAuthor());
  }

  public function testAddCategory(): void
  {
    $category = new Category();
    $category->setName('Technologie');

    $result = $this->post->addCategory($category);

    $this->assertSame($this->post, $result);
    $this->assertCount(1, $this->post->getCategories());
    $this->assertTrue($this->post->getCategories()->contains($category));
  }

  public function testAddCategoryMultipleTimes(): void
  {
    $category = new Category();
    $category->setName('Technologie');

    $this->post->addCategory($category);
    $this->post->addCategory($category); // Ajout du même élément

    // Ne doit être présent qu'une seule fois
    $this->assertCount(1, $this->post->getCategories());
  }

  public function testAddMultipleCategories(): void
  {
    $category1 = new Category();
    $category1->setName('Technologie');

    $category2 = new Category();
    $category2->setName('Sport');

    $this->post->addCategory($category1);
    $this->post->addCategory($category2);

    $this->assertCount(2, $this->post->getCategories());
    $this->assertTrue($this->post->getCategories()->contains($category1));
    $this->assertTrue($this->post->getCategories()->contains($category2));
  }

  public function testRemoveCategory(): void
  {
    $category = new Category();
    $category->setName('Technologie');

    $this->post->addCategory($category);
    $this->assertCount(1, $this->post->getCategories());

    $result = $this->post->removeCategory($category);

    $this->assertSame($this->post, $result);
    $this->assertCount(0, $this->post->getCategories());
    $this->assertFalse($this->post->getCategories()->contains($category));
  }

  public function testRemoveCategoryNotInCollection(): void
  {
    $category = new Category();
    $category->setName('Technologie');

    // Tenter de supprimer une catégorie qui n'est pas présente
    $this->post->removeCategory($category);
    $this->assertCount(0, $this->post->getCategories());
  }

  public function testGetCategories(): void
  {
    $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $this->post->getCategories());
    $this->assertCount(0, $this->post->getCategories());
  }

  public function testFluentInterface(): void
  {
    $author = new Author();
    $author->setName('Jane Doe');

    $category = new Category();
    $category->setName('Science');

    $date = new \DateTimeImmutable();

    $result = $this->post
      ->setTitle('Test Article')
      ->setContent('Test Content')
      ->setCreatedAt($date)
      ->setIsPublished(true)
      ->setAuthor($author)
      ->addCategory($category);

    $this->assertSame($this->post, $result);
    $this->assertSame('Test Article', $this->post->getTitle());
    $this->assertSame('Test Content', $this->post->getContent());
    $this->assertSame($date, $this->post->getCreatedAt());
    $this->assertTrue($this->post->isPublished());
    $this->assertSame($author, $this->post->getAuthor());
    $this->assertCount(1, $this->post->getCategories());
  }
}
