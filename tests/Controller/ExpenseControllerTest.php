<?php

namespace App\Tests\Controller;

use App\Repository\CategoryRepository;
use App\Repository\ExpenseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class ExpenseControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private CategoryRepository $categoryRepository;
    private ExpenseRepository $expenseRepository;

    private EntityManagerInterface $entityManager;
    private string $path = '/api/expenses';
    private ?AbstractDatabaseTool $databaseTool;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->expenseRepository = $container->get(ExpenseRepository::class);
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

        $this->assertCount(20, $response);

        $firstExpense = reset($response);
        $this->assertArrayHasKey('id', $firstExpense);
        $this->assertArrayHasKey('description', $firstExpense);
        $this->assertArrayHasKey('amount', $firstExpense);
        $this->assertArrayHasKey('date', $firstExpense);
        $this->assertArrayHasKey('payment_method', $firstExpense);
        $this->assertArrayHasKey('category', $firstExpense);
    }

    public function testStats(): void
    {
        $this->client->request('GET', $this->path . '/stats');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('total', $response);
        $this->assertArrayHasKey('by_category', $response);

        $this->assertIsNumeric($response['total']);

        $categoriesInStats = array_column($response['by_category'], 'category');
        $expectedCategories = ['Products', 'Transport', 'Entertainment', 'Utilities', 'Health'];
        foreach ($expectedCategories as $category) {
            $this->assertContains($category, $categoriesInStats);
        }
    }

    public function testShow(): void
    {
        $expense = $this->expenseRepository->findOneBy([]);
        $this->assertNotNull($expense, 'Failed to find any expense');

        $this->client->request('GET', $this->path . '/' . $expense->getId());

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals($expense->getId(), $response['id']);
        $this->assertEquals($expense->getDescription(), $response['description']);
        $this->assertEquals($expense->getAmount(), $response['amount']);
        $this->assertEquals($expense->getPaymentMethod(), $response['payment_method']);
        $this->assertArrayHasKey('category', $response);
        $this->assertEquals($expense->getCategory()->getName(), $response['category']['name']);
    }

    public function testCreate(): void
    {
        $category = $this->categoryRepository->findOneBy(['name' => 'Health']);
        $this->assertNotNull($category, 'Failed to find the "Health" category');

        $expenseData = [
            'description' => 'Doctor visit',
            'amount' => 250.0,
            'date' => '2023-07-15T10:00:00',
            'category_id' => $category->getId(),
            'payment_method' => 'Insurance'
        ];

        $this->client->request(
            'POST',
            $this->path,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($expenseData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('Doctor visit', $response['description']);
        $this->assertEquals(250.0, $response['amount']);
        $this->assertEquals('Insurance', $response['payment_method']);
        $this->assertArrayHasKey('category', $response);
        $this->assertEquals('Health', $response['category']['name']);

        $expense = $this->expenseRepository->findOneBy(['description' => 'Doctor visit']);
        $this->assertNotNull($expense);
        $this->assertEquals(250.0, $expense->getAmount());
    }

    public function testCreateWithoutCategory(): void
    {
        $expenseData = [
            'description' => 'Miscellaneous purchase',
            'amount' => 75.5,
            'date' => '2023-07-20T14:30:00',
            'payment_method' => 'Cash'
        ];

        $this->client->request(
            'POST',
            $this->path,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($expenseData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('Miscellaneous purchase', $response['description']);
        $this->assertEquals(75.5, $response['amount']);
        $this->assertEquals('Cash', $response['payment_method']);
        $this->assertNull($response['category']);
    }

    public function testUpdate(): void
    {
        $expense = $this->expenseRepository->findOneBy([]);
        $this->assertNotNull($expense, 'Failed to find any expense');

        $category = $this->categoryRepository->findOneBy(['name' => 'Entertainment']);
        $this->assertNotNull($category, 'Failed to find the "Entertainment" category');

        $updatedData = [
            'description' => 'Updated expense description',
            'amount' => 100.0,
            'date' => '2023-08-01T09:15:00',
            'category_id' => $category->getId(),
            'payment_method' => 'Online'
        ];

        $this->client->request(
            'PUT',
            $this->path . '/' . $expense->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($updatedData)
        );

        $this->assertResponseIsSuccessful();

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('Updated expense description', $response['description']);
        $this->assertEquals(100.0, $response['amount']);
        $this->assertEquals('Online', $response['payment_method']);
        $this->assertArrayHasKey('category', $response);
        $this->assertEquals('Entertainment', $response['category']['name']);

        $this->entityManager->refresh($expense);
        $this->assertEquals('Updated expense description', $expense->getDescription());
        $this->assertEquals(100.0, $expense->getAmount());
        $this->assertEquals('Online', $expense->getPaymentMethod());
        $this->assertEquals($category->getId(), $expense->getCategory()->getId());
    }

    public function testPartialUpdate(): void
    {
        $expense = $this->expenseRepository->findOneBy([]);
        $this->assertNotNull($expense, 'Failed to find any expense');

        $originalDescription = $expense->getDescription();
        $originalDate = $expense->getDate();
        $originalCategory = $expense->getCategory();

        $updatedData = [
            'amount' => 175.0,
            'payment_method' => 'Mobile'
        ];

        $this->client->request(
            'PUT',
            $this->path . '/' . $expense->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($updatedData)
        );

        $this->assertResponseIsSuccessful();

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals($originalDescription, $response['description']);
        $this->assertEquals(175.0, $response['amount']);
        $this->assertEquals('Mobile', $response['payment_method']);

        // Verify it was updated in the database
        $this->entityManager->refresh($expense);
        $this->assertEquals($originalDescription, $expense->getDescription());
        $this->assertEquals($originalDate, $expense->getDate());
        $this->assertEquals(175.0, $expense->getAmount());
        $this->assertEquals('Mobile', $expense->getPaymentMethod());
        $this->assertEquals($originalCategory->getId(), $expense->getCategory()->getId());
    }

    public function testDelete(): void
    {
        $expense = $this->expenseRepository->findOneBy([]);
        $this->assertNotNull($expense, 'Failed to find any expense');

        $expenseId = $expense->getId();

        $this->client->request('DELETE', $this->path . '/' . $expenseId);

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $this->assertNull($this->expenseRepository->find($expenseId));
    }

    public function testNonExistentExpense(): void
    {
        $this->client->request('GET', $this->path . '/9999');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
