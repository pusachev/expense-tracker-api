<?php

namespace App\Tests\Controller;

use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;

final class CategoryControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private CategoryRepository $categoryRepository;
    private EntityManagerInterface $entityManager;
    private string $path = '/api/categories';
    private ?AbstractDatabaseTool $databaseTool;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->categoryRepository = $container->get(CategoryRepository::class);
        $this->entityManager = $container->get('doctrine')->getManager();

        // Load fixtures for each test
        $this->databaseTool = $container->get(DatabaseToolCollection::class)->get();
        $this->databaseTool->loadFixtures([
            'App\DataFixtures\AppFixtures'
        ]);
    }

    public function testIndex(): void
    {
        $this->client->request('GET', $this->path);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount(5, $response);

        $categoryNames = array_column($response, 'name');
        $this->assertContains('Products', $categoryNames);
        $this->assertContains('Transport', $categoryNames);
        $this->assertContains('Entertainment', $categoryNames);
        $this->assertContains('Utilities', $categoryNames);
        $this->assertContains('Health', $categoryNames);
    }

    public function testShow(): void
    {
        $category = $this->categoryRepository->findOneBy(['name' => 'Products']);
        $this->assertNotNull($category, 'Failed to find the "Products" category from fixtures');

        $this->client->request('GET', $this->path . '/' . $category->getId());

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('Products', $response['name']);
        $this->assertEquals($category->getId(), $response['id']);
    }

    public function testCreate(): void
    {
        $this->client->request('POST', $this->path, [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'Education',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Education', $response['name']);
        $this->assertNotNull($response['id']);

        $category = $this->categoryRepository->findOneBy(['name' => 'Education']);
        $this->assertNotNull($category);
        $this->assertEquals('Education', $category->getName());
    }

    public function testUpdate(): void
    {
        $category = $this->categoryRepository->findOneBy(['name' => 'Transport']);
        $this->assertNotNull($category, 'Failed to find the "Transport" category from fixtures');

        $this->client->request('PUT', $this->path . '/' . $category->getId(), [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'Public Transport',
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Public Transport', $response['name']);

        // Verify the category was updated in the database
        $this->entityManager->refresh($category);
        $this->assertEquals('Public Transport', $category->getName());
    }

    public function testDelete(): void
    {
        $category = $this->categoryRepository->findOneBy(['name' => 'Health']);
        $this->assertNotNull($category, 'Failed to find the "Health" category from fixtures');

        $categoryId = $category->getId();

        $this->client->request('DELETE', $this->path . '/' . $categoryId);

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $this->assertNull($this->categoryRepository->find($categoryId));
    }

    public function testShowNonExistentCategory(): void
    {
        $this->client->request('GET', $this->path . '/9999');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testInvalidCreateRequest(): void
    {
        $this->client->request('POST', $this->path, [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([]));

        $this->assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}