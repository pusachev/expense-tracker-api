<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Expense;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $categories = [];

        $categoryNames = [
            'Products',
            'Transport',
            'Entertainment',
            'Utilities',
            'Health'
        ];

        foreach ($categoryNames as $name) {
            $category = new Category();
            $category->setName($name);
            $manager->persist($category);
            $categories[] = $category;
        }

        $descriptions = [
            'Store purchase',
            'Metro fare',
            'Cinema',
            'Apartment payment',
            'Pharmacy'
        ];

        $paymentMethods = ['Card', 'Cash', 'Online'];

        for ($i = 0; $i < 20; $i++) {
            $expense = new Expense();
            $expense->setDescription($descriptions[array_rand($descriptions)]);
            $expense->setAmount(mt_rand(100, 5000));

            $date = new \DateTime();
            $date->modify('-' . mt_rand(0, 30) . ' days');
            $expense->setDate($date);

            $expense->setCategory($categories[array_rand($categories)]);
            $expense->setPaymentMethod($paymentMethods[array_rand($paymentMethods)]);

            $manager->persist($expense);
        }

        $manager->flush();
    }
}
