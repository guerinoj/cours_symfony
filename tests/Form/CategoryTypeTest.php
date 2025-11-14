<?php

namespace App\Tests\Form;

use App\Entity\Category;
use App\Form\CategoryType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;

class CategoryTypeTest extends KernelTestCase
{
  private FormFactoryInterface $formFactory;

  protected function setUp(): void
  {
    self::bootKernel();
    $this->formFactory = self::getContainer()->get('form.factory');
  }

  public function testSubmitValidData(): void
  {
    $formData = [
      'name' => 'Technologie',
      'slug' => 'technologie',
      'description' => 'Articles sur la technologie',
    ];

    $model = new Category();
    $form = $this->formFactory->create(CategoryType::class, $model);

    $expected = new Category();
    $expected->setName($formData['name']);
    $expected->setSlug($formData['slug']);
    $expected->setDescription($formData['description']);

    $form->submit($formData);

    $this->assertTrue($form->isSynchronized());
    $this->assertEquals($expected->getName(), $model->getName());
    $this->assertEquals($expected->getSlug(), $model->getSlug());
    $this->assertEquals($expected->getDescription(), $model->getDescription());

    $view = $form->createView();
    $children = $view->children;

    foreach (array_keys($formData) as $key) {
      $this->assertArrayHasKey($key, $children);
    }
  }

  public function testFormHasExpectedFields(): void
  {
    $form = $this->formFactory->create(CategoryType::class);
    $view = $form->createView();
    $children = $view->children;

    $this->assertArrayHasKey('name', $children);
    $this->assertArrayHasKey('slug', $children);
    $this->assertArrayHasKey('description', $children);
  }

  public function testNameFieldConfiguration(): void
  {
    $form = $this->formFactory->create(CategoryType::class);

    $this->assertTrue($form->has('name'));
  }

  public function testSlugFieldConfiguration(): void
  {
    $form = $this->formFactory->create(CategoryType::class);

    $this->assertTrue($form->has('slug'));
  }

  public function testDescriptionFieldConfiguration(): void
  {
    $form = $this->formFactory->create(CategoryType::class);

    $this->assertTrue($form->has('description'));
  }

  public function testDataClassConfiguration(): void
  {
    $form = $this->formFactory->create(CategoryType::class);
    $config = $form->getConfig();

    $this->assertEquals(Category::class, $config->getDataClass());
  }

  public function testFormWithMinimalValidData(): void
  {
    $formData = [
      'name' => 'Sport',
      'slug' => 'sport',
      'description' => null, // Description optionnelle
    ];

    $model = new Category();
    $form = $this->formFactory->create(CategoryType::class, $model);
    $form->submit($formData);

    $this->assertTrue($form->isSynchronized());
    $this->assertEquals($formData['name'], $model->getName());
    $this->assertEquals($formData['slug'], $model->getSlug());
    $this->assertNull($model->getDescription());
  }

  public function testFormWithLongDescription(): void
  {
    $longDescription = str_repeat('Lorem ipsum dolor sit amet', 100);

    $formData = [
      'name' => 'Category avec longue description',
      'slug' => 'category-avec-longue-description',
      'description' => $longDescription,
    ];

    $category = new Category();
    $form = $this->formFactory->create(CategoryType::class, $category);
    $form->submit($formData);

    $this->assertTrue($form->isSynchronized());
    $this->assertEquals('Category avec longue description', $category->getName());
    $this->assertEquals('category-avec-longue-description', $category->getSlug());
    $this->assertEquals($longDescription, $category->getDescription());
  }

  public function testFormWithEmptyDescription(): void
  {
    $formData = [
      'name' => 'Category sans description',
      'slug' => 'category-sans-description',
      'description' => null,
    ];

    $category = new Category();
    $form = $this->formFactory->create(CategoryType::class, $category);
    $form->submit($formData);

    $this->assertTrue($form->isSynchronized());
    $this->assertEquals('Category sans description', $category->getName());
    $this->assertEquals('category-sans-description', $category->getSlug());
    $this->assertNull($category->getDescription());
  }

  public function testFormPrePopulatedWithData(): void
  {
    $category = new Category();
    $category->setName('Existing Category');
    $category->setSlug('existing-category');
    $category->setDescription('Existing description');

    $form = $this->formFactory->create(CategoryType::class, $category);
    $view = $form->createView();

    $this->assertEquals('Existing Category', $view['name']->vars['value']);
    $this->assertEquals('existing-category', $view['slug']->vars['value']);
    $this->assertEquals('Existing description', $view['description']->vars['value']);
  }
}
