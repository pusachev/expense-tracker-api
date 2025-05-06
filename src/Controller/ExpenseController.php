<?php

namespace App\Controller;

use App\Entity\Expense;
use App\Repository\CategoryRepository;
use App\Repository\ExpenseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/expenses')]
final class ExpenseController extends AbstractController
{
    #[Route('', name: 'expense_list', methods: ['GET'])]
    public function list(ExpenseRepository $expenseRepository): JsonResponse
    {
        $expenses = $expenseRepository->findAll();
        return $this->json($expenses, 200, [], ['groups' => 'expense:read']);
    }

    #[Route('/stats', name: 'expense_stats', methods: ['GET'])]
    public function stats(ExpenseRepository $expenseRepository): JsonResponse
    {
        $total = $expenseRepository->createQueryBuilder('e')
            ->select('SUM(e.amount) as total')
            ->getQuery()
            ->getSingleScalarResult();

        $byCategory = $expenseRepository->createQueryBuilder('e')
            ->select('c.name as category, SUM(e.amount) as amount')
            ->leftJoin('e.category', 'c')
            ->groupBy('c.id')
            ->getQuery()
            ->getResult();

        return $this->json([
            'total' => $total,
            'by_category' => $byCategory
        ]);
    }

    #[Route('/{id}', name: 'expense_show', methods: ['GET'])]
    public function show(Expense $expense): JsonResponse
    {
        return $this->json($expense, 200, [], ['groups' => 'expense:read']);
    }

    #[Route('', name: 'expense_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        CategoryRepository $categoryRepository
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $expense = new Expense();
        $expense->setDescription($data['description']);
        $expense->setAmount($data['amount']);
        $expense->setDate(new \DateTime($data['date']));

        if (isset($data['category_id'])) {
            $category = $categoryRepository->find($data['category_id']);
            if ($category) {
                $expense->setCategory($category);
            }
        }

        if (isset($data['payment_method'])) {
            $expense->setPaymentMethod($data['payment_method']);
        }

        $entityManager->persist($expense);
        $entityManager->flush();

        return $this->json($expense, Response::HTTP_CREATED, [], ['groups' => 'expense:read']);
    }

    #[Route('/{id}', name: 'expense_update', methods: ['PUT'])]
    public function update(
        Expense $expense,
        Request $request,
        EntityManagerInterface $entityManager,
        CategoryRepository $categoryRepository
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['description'])) {
            $expense->setDescription($data['description']);
        }

        if (isset($data['amount'])) {
            $expense->setAmount($data['amount']);
        }

        if (isset($data['date'])) {
            $expense->setDate(new \DateTime($data['date']));
        }

        if (isset($data['category_id'])) {
            $category = $categoryRepository->find($data['category_id']);
            if ($category) {
                $expense->setCategory($category);
            }
        }

        if (isset($data['payment_method'])) {
            $expense->setPaymentMethod($data['payment_method']);
        }

        $entityManager->flush();

        return $this->json($expense, 200, [], ['groups' => 'expense:read']);
    }

    #[Route('/{id}', name: 'expense_delete', methods: ['DELETE'])]
    public function delete(
        Expense $expense,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        $entityManager->remove($expense);
        $entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
