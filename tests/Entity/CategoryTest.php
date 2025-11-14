<?php

namespace App\Tests\Entity;

use App\Entity\Category;
use App\Entity\Post;
use PHPUnit\Framework\TestCase;

class CategoryTest extends TestCase
{
  private Category $category;

  protected function setUp(): void
  {
    $this->category = new Category();
  }

  public function testInitialState(): void
  {
    $this->assertNull($this->category->getId());
    $this->assertNull($this->category->getName());
    $this->assertNull($this->category->getSlug());
    $this->assertNull($this->category->getDescription());
    $this->assertNull($this->category->getCreatedAt());
    $this->assertNull($this->category->getUpdatedAt());
    $this->assertCount(0, $this->category->getPosts());
  }

  public function testSetAndGetName(): void
  {
    $name = 'Technologie';
    $result = $this->category->setName($name);

    $this->assertSame($this->category, $result);
    $this->assertSame($name, $this->category->getName());
  }

  public function testSetAndGetSlug(): void
  {
    $slug = 'technologie';
    $result = $this->category->setSlug($slug);

    $this->assertSame($this->category, $result);
    $this->assertSame($slug, $this->category->getSlug());
  }

  public function testSetAndGetDescription(): void
  {
    $description = 'Catégorie pour les articles sur la technologie';
    $result = $this->category->setDescription($description);

    $this->assertSame($this->category, $result);
    $this->assertSame($description, $this->category->getDescription());
  }

  public function testSetAndGetDescriptionNull(): void
  {
    $this->category->setDescription(null);
    $this->assertNull($this->category->getDescription());
  }

  public function testSetAndGetCreatedAt(): void
  {
    $date = new \DateTimeImmutable('2024-01-15 10:30:00');
    $result = $this->category->setCreatedAt($date);

    $this->assertSame($this->category, $result);
    $this->assertSame($date, $this->category->getCreatedAt());
  }

  public function testSetAndGetUpdatedAt(): void
  {
    $date = new \DateTimeImmutable('2024-01-20 15:45:00');
    $result = $this->category->setUpdatedAt($date);

    $this->assertSame($this->category, $result);
    $this->assertSame($date, $this->category->getUpdatedAt());
  }

  public function testSetAndGetUpdatedAtNull(): void
  {
    $this->category->setUpdatedAt(null);
    $this->assertNull($this->category->getUpdatedAt());
  }

  public function testGetPosts(): void
  {
    $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $this->category->getPosts());
    $this->assertCount(0, $this->category->getPosts());
  }

  public function testAddPost(): void
  {
    $post = new Post();
    $post->setTitle('Article test');

    $result = $this->category->addPost($post);

    $this->assertSame($this->category, $result);
    $this->assertCount(1, $this->category->getPosts());
    $this->assertTrue($this->category->getPosts()->contains($post));
  }

  public function testAddPostMultipleTimes(): void
  {
    $post = new Post();
    $post->setTitle('Article test');

    $this->category->addPost($post);
    $this->category->addPost($post); // Ajout du même élément

    // Ne doit être présent qu'une seule fois
    $this->assertCount(1, $this->category->getPosts());
  }

  public function testAddMultiplePosts(): void
  {
    $post1 = new Post();
    $post1->setTitle('Article 1');

    $post2 = new Post();
    $post2->setTitle('Article 2');

    $this->category->addPost($post1);
    $this->category->addPost($post2);

    $this->assertCount(2, $this->category->getPosts());
    $this->assertTrue($this->category->getPosts()->contains($post1));
    $this->assertTrue($this->category->getPosts()->contains($post2));
  }

  public function testRemovePost(): void
  {
    $post = new Post();
    $post->setTitle('Article test');

    $this->category->addPost($post);
    $this->assertCount(1, $this->category->getPosts());

    $result = $this->category->removePost($post);

    $this->assertSame($this->category, $result);
    $this->assertCount(0, $this->category->getPosts());
    $this->assertFalse($this->category->getPosts()->contains($post));
  }

  public function testRemovePostNotInCollection(): void
  {
    $post = new Post();
    $post->setTitle('Article test');

    // Tenter de supprimer un post qui n'est pas présent
    $this->category->removePost($post);
    $this->assertCount(0, $this->category->getPosts());
  }

  public function testToStringWithName(): void
  {
    $this->category->setName('Technologie');
    $this->assertSame('Technologie', (string) $this->category);
  }

  public function testToStringWithoutName(): void
  {
    $this->assertSame('Catégorie sans nom', (string) $this->category);
  }

  public function testFluentInterface(): void
  {
    $post = new Post();
    $post->setTitle('Article test');

    $date = new \DateTimeImmutable();

    $result = $this->category
      ->setName('Web Development')
      ->setSlug('web-development')
      ->setDescription('Articles about web development')
      ->setCreatedAt($date)
      ->setUpdatedAt($date)
      ->addPost($post);

    $this->assertSame($this->category, $result);
    $this->assertSame('Web Development', $this->category->getName());
    $this->assertSame('web-development', $this->category->getSlug());
    $this->assertSame('Articles about web development', $this->category->getDescription());
    $this->assertSame($date, $this->category->getCreatedAt());
    $this->assertSame($date, $this->category->getUpdatedAt());
    $this->assertCount(1, $this->category->getPosts());
  }
}
