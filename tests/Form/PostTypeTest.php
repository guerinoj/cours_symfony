<?php

namespace App\Tests\Form;

use App\Entity\Author;
use App\Entity\Category;
use App\Entity\Post;
use App\Form\PostType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;

class PostTypeTest extends KernelTestCase
{
    private FormFactoryInterface $formFactory;
    private $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->formFactory = self::getContainer()->get('form.factory');
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    private function createAuthor(string $name): Author
    {
        $author = new Author();
        $author->setName($name);
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
    public function testSubmitValidData(): void
    {
        $author = $this->createAuthor('John Doe');
        $category = $this->createCategory('Technologie', 'technologie');

        $formData = [
            'title' => 'Mon article de test',
            'content' => 'Ceci est le contenu de mon article de test.',
            'is_published' => true,
            'author' => $author->getId(),
            'categories' => [$category->getId()],
        ];

        $model = new Post();
        $form = $this->formFactory->create(PostType::class, $model);

        $expected = new Post();
        $expected->setTitle($formData['title']);
        $expected->setContent($formData['content']);
        $expected->setIsPublished($formData['is_published']);

        // Submit the data to the form directly
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());

        // Vérifier que les données sont bien mappées sur l'objet
        $this->assertEquals($expected->getTitle(), $model->getTitle());
        $this->assertEquals($expected->getContent(), $model->getContent());
        $this->assertEquals($expected->isPublished(), $model->isPublished());
        $this->assertEquals($author->getId(), $model->getAuthor()->getId());
        $this->assertCount(1, $model->getCategories());

        $view = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children);
        }
    }

    public function testFormHasExpectedFields(): void
    {
        $form = $this->formFactory->create(PostType::class);
        $view = $form->createView();
        $children = $view->children;

        $this->assertArrayHasKey('title', $children);
        $this->assertArrayHasKey('content', $children);
        $this->assertArrayHasKey('is_published', $children);
        $this->assertArrayHasKey('author', $children);
        $this->assertArrayHasKey('categories', $children);
        $this->assertArrayHasKey('submit', $children);
    }

    public function testTitleFieldConfiguration(): void
    {
        $form = $this->formFactory->create(PostType::class);
        
        $this->assertTrue($form->has('title'));
    }

    public function testContentFieldConfiguration(): void
    {
        $form = $this->formFactory->create(PostType::class);
        
        $this->assertTrue($form->has('content'));
    }

    public function testIsPublishedFieldConfiguration(): void
    {
        $form = $this->formFactory->create(PostType::class);
        
        $this->assertTrue($form->has('is_published'));
    }

    public function testAuthorFieldConfiguration(): void
    {
        $form = $this->formFactory->create(PostType::class);
        $authorConfig = $form->get('author')->getConfig();
        
        $this->assertTrue($form->has('author'));
        $this->assertTrue($authorConfig->getRequired());
        $this->assertEquals('Auteur', $authorConfig->getOption('label'));
        $this->assertEquals('Choisir un auteur', $authorConfig->getOption('placeholder'));
    }

    public function testCategoriesFieldConfiguration(): void
    {
        $form = $this->formFactory->create(PostType::class);
        $categoriesConfig = $form->get('categories')->getConfig();
        
        $this->assertTrue($form->has('categories'));
        $this->assertFalse($categoriesConfig->getRequired());
        $this->assertTrue($categoriesConfig->getOption('multiple'));
        $this->assertTrue($categoriesConfig->getOption('expanded'));
        $this->assertEquals('Categories', $categoriesConfig->getOption('label'));
        $this->assertFalse($categoriesConfig->getOption('by_reference'));
    }

    public function testSubmitButtonConfiguration(): void
    {
        $form = $this->formFactory->create(PostType::class);
        $submitConfig = $form->get('submit')->getConfig();
        
        $this->assertTrue($form->has('submit'));
        $this->assertEquals('Enregistrer', $submitConfig->getOption('label'));
        $this->assertArrayHasKey('class', $submitConfig->getOption('attr'));
        $this->assertEquals('btn btn-primary', $submitConfig->getOption('attr')['class']);
    }

    public function testFormWithMinimalValidData(): void
    {
        $author = $this->createAuthor('Jane Doe');

        $formData = [
            'title' => 'Titre minimal valide',
            'content' => 'Contenu minimal',
            'is_published' => false,
            'author' => $author->getId(),
            'categories' => [], // Pas de catégories (optionnel)
        ];

        $model = new Post();
        $form = $this->formFactory->create(PostType::class, $model);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($formData['title'], $model->getTitle());
        $this->assertEquals($formData['content'], $model->getContent());
        $this->assertEquals($formData['is_published'], $model->isPublished());
        $this->assertEquals($author->getId(), $model->getAuthor()->getId());
        $this->assertCount(0, $model->getCategories());
    }

    public function testFormWithMultipleCategories(): void
    {
        $author = $this->createAuthor('John Doe');
        $category1 = $this->createCategory('Technologie', 'technologie');
        $category2 = $this->createCategory('Web', 'web');

        $formData = [
            'title' => 'Article multi-catégories',
            'content' => 'Contenu avec plusieurs catégories.',
            'is_published' => true,
            'author' => $author->getId(),
            'categories' => [$category1->getId(), $category2->getId()],
        ];

        $model = new Post();
        $form = $this->formFactory->create(PostType::class, $model);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertCount(2, $model->getCategories());
    }

    public function testDataClassConfiguration(): void
    {
        $form = $this->formFactory->create(PostType::class);
        $config = $form->getConfig();
        
        $this->assertEquals(Post::class, $config->getDataClass());
    }

    public function testFormWithPublishedTrue(): void
    {
        $author = $this->createAuthor('Author Name');

        $formData = [
            'title' => 'Article publié',
            'content' => 'Contenu article publié',
            'is_published' => true,
            'author' => $author->getId(),
            'categories' => [],
        ];

        $model = new Post();
        $form = $this->formFactory->create(PostType::class, $model);
        $form->submit($formData);

        $this->assertTrue($model->isPublished());
    }

    public function testFormWithPublishedFalse(): void
    {
        $author = $this->createAuthor('Author Name');

        $formData = [
            'title' => 'Article brouillon',
            'content' => 'Contenu brouillon',
            'is_published' => false,
            'author' => $author->getId(),
            'categories' => [],
        ];

        $model = new Post();
        $form = $this->formFactory->create(PostType::class, $model);
        $form->submit($formData);

        $this->assertFalse($model->isPublished());
    }
}
