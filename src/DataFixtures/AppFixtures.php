<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Factory\TaskFactory;
use App\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Zenstruck\Foundry\Factory;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $existingAdmin = $manager->getRepository(User::class)->findOneBy(['username' => 'admin']);
        
        if (!$existingAdmin) {
            UserFactory::createOne([
                'username' => 'admin',
                'email' => 'admin@todolist.com',
                'roles' => ['ROLE_ADMIN'],
                'password' => 'admin',
            ]);
            echo "Utilisateur admin créé.\n";
        } else {
            echo "Utilisateur admin existe déjà, création ignorée.\n";
        }

        UserFactory::createMany(10);
        echo "10 utilisateurs aléatoires créés.\n";
        TaskFactory::createMany(50);
        echo "50 tâches aléatoires créées.\n";
    }
}
